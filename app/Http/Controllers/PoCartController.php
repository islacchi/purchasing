<?php

namespace App\Http\Controllers;

use App\Models\PoCartItem;
use App\Models\PreplistItem;
use App\Models\PurchaseOrderItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Support\PurchaseOrderCreator;
use App\Support\PurchaseOrderWorkbook;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Per-user staging cart of awarded quotation lines: what we intend to buy,
 * reviewed in one place before the purchase orders are drafted.
 *
 * Lines are stored against the quotation item itself (not a price snapshot),
 * so checkout always sees the current quotation file and re-validates
 * quantities and availability before creating anything.
 */
class PoCartController extends Controller
{
    /**
     * The cart review page, grouped by supplier.
     */
    public function index(Request $request)
    {
        $cartItems = $request->user()->poCartItems()
            ->with([
                'quotationItem.quotation.supplier',
                'quotationItem.quotation',
                'quotationItem.preplistItem.item',
            ])
            ->get();

        $preplistItemIds = $cartItems
            ->pluck('quotationItem.preplist_item_id')
            ->unique()
            ->values()
            ->all();

        $priceRows = QuotationItem::query()
            ->selectRaw('preplist_item_id, COUNT(*) as quotes, MIN(unit_price) as best')
            ->whereIn('preplist_item_id', $preplistItemIds ?: [0])
            ->whereNotNull('unit_price')
            ->groupBy('preplist_item_id')
            ->get()
            ->keyBy('preplist_item_id');

        $lines = [];
        $grandTotal = 0.0;

        foreach ($cartItems as $cartItem) {
            $qi = $cartItem->quotationItem;
            $preplistItem = $qi->preplistItem;
            $item = $preplistItem?->item;
            $remaining = $preplistItem?->remainingQuantity() ?? 0;
            $price = (float) ($qi->unit_price ?? 0);
            $lineTotal = $price * $cartItem->qty;
            $grandTotal += $lineTotal;

            $stats = $priceRows->get($preplistItem?->id);
            $best = $stats ? (float) $stats->best : 0.0;
            $quoteCount = (int) ($stats?->quotes ?? 0);
            $premium = $best > 0.0 && $quoteCount > 1 && $price > $best
                ? (($price - $best) / $best) * 100
                : null;

            $lines[] = [
                'cartItem' => $cartItem,
                'quotation' => $qi->quotation,
                'supplier' => $qi->quotation->supplier,
                'item' => $item,
                'name' => $item?->name ?? 'Item removed',
                'unit' => $item?->unit ?? '',
                'qty' => $cartItem->qty,
                'requested' => $remaining,
                'unit_price' => $price,
                'line_total' => $lineTotal,
                'min_remaining_months' => (int) $qi->min_remaining_months,
                'premium' => $premium,
            ];
        }

        $grouped = collect($lines)
            ->groupBy(fn (array $line) => $line['supplier']->id)
            ->map(fn ($items) => [
                'supplier' => $items->first()['supplier'],
                'items' => $items,
                'subtotal' => $items->sum('line_total'),
            ])
            ->values()
            ->all();

        return view('Cart.index', [
            'grouped' => $grouped,
            'grandTotal' => $grandTotal,
            'meta' => [
                'count' => $cartItems->count(),
                'quotations' => collect($lines)->pluck('quotation')->unique('id')->count(),
            ],
        ]);
    }

    /**
     * Add one quoted line, or all awarded lines of a quotation, to the cart.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'quotation_item_id' => ['nullable', 'integer'],
            'quotation_id' => ['nullable', 'integer'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        if (empty($validated['quotation_item_id']) && empty($validated['quotation_id'])) {
            throw ValidationException::withMessages(['cart' => 'Tell us which line to add to the cart.']);
        }

        if (! empty($validated['quotation_id'])) {
            return $this->addQuotation($request, (int) $validated['quotation_id']);
        }

        $quotationItem = QuotationItem::query()
            ->with(['preplistItem.item', 'quotation.supplier'])
            ->findOrFail((int) $validated['quotation_item_id']);

        if ($error = $this->notCartableReason($quotationItem)) {
            return back()->withErrors(['cart' => $error]);
        }

        $remaining = $quotationItem->preplistItem?->remainingQuantity() ?? 0;
        $qty = (int) ($validated['qty'] ?? $remaining);

        if ($qty > $remaining) {
            return back()->withErrors(['cart' => 'Only '.$remaining.' '.($quotationItem->preplistItem?->item?->unit ?? 'units').' remain for that line.']);
        }

        $preplistItemId = $quotationItem->preplistItem?->id;

        if ($preplistItemId !== null) {
            // One line per item: the chosen supplier replaces any other supplier
            // of the same line that was already in the cart.
            $request->user()->poCartItems()
                ->whereIn('quotation_item_id', function ($query) use ($preplistItemId) {
                    $query->select('qi.id')
                        ->from('quotation_items as qi')
                        ->where('qi.preplist_item_id', $preplistItemId);
                })
                ->delete();
        }

        PoCartItem::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'quotation_item_id' => $quotationItem->id],
            ['qty' => $qty],
        );

        return back()->with('status', 'Added "'.($quotationItem->preplistItem?->item?->name ?? 'item').'" to your cart.');
    }

    /**
     * Add the best-priced line (including single quotes) for every item the
     * board knows about: skips what is already on a PO, fully covered by
     * stock, or already in the cart.
     */
    public function bulkAddBestPrices(Request $request)
    {
        $quoted = QuotationItem::query()
            ->with(['preplistItem.item', 'quotation.supplier'])
            ->whereNotNull('unit_price')
            ->get()
            ->groupBy('preplist_item_id');

        $itemIds = $quoted->flatten()->pluck('id')->all();
        $orderedItemIds = PurchaseOrderItem::query()
            ->whereIn('quotation_item_id', $itemIds)
            ->pluck('quotation_item_id')
            ->all();
        // Preplist items whose line is already in the cart (any supplier) —
        // suggestion never overwrites the user's chosen line.
        $alreadyInCart = $request->user()->poCartItems()
            ->join('quotation_items as qi', 'qi.id', '=', 'po_cart_items.quotation_item_id')
            ->pluck('qi.preplist_item_id')
            ->unique()
            ->all();

        $added = 0;
        $already = 0;
        $suppliers = [];

        foreach ($quoted as $preplistItemId => $quotes) {
            $best = $quotes->sortBy(fn ($qi) => (float) $qi->unit_price)->first();
            $preplistItem = $best->preplistItem;
            $remaining = $preplistItem?->remainingQuantity() ?? 0;

            if ($remaining <= 0) {
                continue;
            }

            if (in_array($best->id, $orderedItemIds, true)) {
                continue;
            }

            if (in_array((int) $preplistItemId, $alreadyInCart, true)) {
                $already++;
                continue;
            }

            PoCartItem::query()->create([
                'user_id' => $request->user()->id,
                'quotation_item_id' => $best->id,
                'qty' => $remaining,
            ]);

            $added++;
            $name = $best->quotation->supplier?->name ?? 'Unknown supplier';
            $suppliers[$name] = ($suppliers[$name] ?? 0) + 1;
        }

        if ($added === 0) {
            $status = 'Nothing to add — the board either has no prices, or everything is covered by stock, on an order, or already in your cart.';
            if ($already > 0) {
                $status .= " ($already lines were already in your cart.)";
            }

            return redirect()->route('po-cart')->with('status', $status);
        }

        $top = collect($suppliers)
            ->sortDesc()
            ->take(4)
            ->map(fn ($count, $name) => $count.' '.$name)
            ->implode(' · ');

        $status = 'Added '.$added.' line'.($added === 1 ? '' : 's').' to your cart ('.$top.')';
        if (count($suppliers) > 4) {
            $status .= ' · +'.(count($suppliers) - 4).' more';
        }
        if ($already > 0) {
            $status .= ' · '.$already.' already in cart';
        }

        return redirect()->route('po-cart')->with('status', $status);
    }

    /**
     * Change a line's quantity in the cart (clamped to what is still needed).
     */
    public function update(Request $request, PoCartItem $cartItem)
    {
        $this->authorizeLine($request, $cartItem);

        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);

        $remaining = $cartItem->quotationItem->preplistItem?->remainingQuantity() ?? 0;

        if ($remaining <= 0) {
            $cartItem->delete();

            return back()->withErrors(['cart' => '"'.($cartItem->quotationItem->preplistItem?->item?->name ?? 'That line').'" is fully covered by on-hand stock and was removed from your cart.']);
        }

        if ($validated['qty'] > $remaining) {
            return back()->withErrors(['cart' => 'Only '.$remaining.' remain for that line.']);
        }

        $cartItem->update(['qty' => $validated['qty']]);

        return back()->with('status', 'Quantity updated.');
    }

    /**
     * Remove a single line from the cart.
     */
    public function destroy(Request $request, PoCartItem $cartItem)
    {
        $this->authorizeLine($request, $cartItem);
        $cartItem->delete();

        return $this->redirectBack($request, 'Removed from your cart.');
    }

    /**
     * Empty the cart entirely.
     */
    public function clear(Request $request)
    {
        $request->user()->poCartItems()->delete();

        return $this->redirectBack($request, 'Your cart is empty.');
    }

    private function redirectBack(Request $request, string $message)
    {
        $referer = $request->headers->get('referer');

        if ($referer && str_contains($referer, $request->getHttpHost())) {
            return redirect()->to($referer)->with('status', $message);
        }

        return redirect()->route('po-cart')->with('status', $message);
    }

    /**
     * Re-validate every line, create the draft purchase orders (one per
     * awarded quotation), and clear the cart.
     */
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'expected_delivery' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'deliver_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $cartItems = $request->user()->poCartItems()
            ->with([
                'quotationItem.quotation.supplier',
                'quotationItem.quotation',
                'quotationItem.preplistItem.sources.projectItem',
            ])
            ->get();

        if ($cartItems->isEmpty()) {
            return back()->withErrors(['cart' => 'Your cart is empty.']);
        }

        // All-or-nothing: a single bad line aborts everything, loudly.
        $invalid = [];
        foreach ($cartItems as $cartItem) {
            $qi = $cartItem->quotationItem;
            $preplistItem = $qi->preplistItem;
            $name = $preplistItem?->item?->name ?? 'Item '.$qi->preplist_item_id;
            $requested = $preplistItem?->remainingQuantity() ?? 0;

            if ($requested <= 0) {
                $invalid[] = $name.' (covered by on-hand stock)';
                continue;
            }
            if (! $qi->unit_price) {
                $invalid[] = $name.' (no unit price on the quotation)';
                continue;
            }
            if ($cartItem->qty < 1 || $cartItem->qty > $requested) {
                $invalid[] = $name.' (qty '.$cartItem->qty.' exceeds the remaining '.$requested.')';
                continue;
            }
            if (PurchaseOrderItem::query()->where('quotation_item_id', $qi->id)->exists()) {
                $invalid[] = $name.' (already on a purchase order)';
            }
        }

        if ($invalid) {
            throw ValidationException::withMessages([
                'cart' => 'Cannot check out — '.implode('; ', $invalid).'. Remove those lines and try again.',
            ]);
        }

        $details = [
            'expected_delivery' => $validated['expected_delivery'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'deliver_to' => $validated['deliver_to'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];

        $created = [];
        foreach ($cartItems->groupBy(fn ($ci) => $ci->quotationItem->quotation_id) as $quotationId => $group) {
            $lines = [];
            foreach ($group as $cartItem) {
                $lines[] = [
                    'quotation_item' => $cartItem->quotationItem,
                    'preplist_item' => $cartItem->quotationItem->preplistItem,
                    'qty' => $cartItem->qty,
                    'unit_price' => $cartItem->quotationItem->unit_price,
                    'min_remaining_months' => $cartItem->quotationItem->min_remaining_months,
                ];
            }
            $created[] = PurchaseOrderCreator::createFor($request->user()->id, $lines, $details);
        }

        $request->user()->poCartItems()->delete();

        $numbers = collect($created)->pluck('po_number')->implode(', ');
        $message = 'Created '.count($created).' purchase order'.(count($created) === 1 ? '' : 's').': '.$numbers.'. The draft POs are ready to review and send.';

        try {
            $files = [];
            foreach ($created as $po) {
                $po->load(['items.item', 'items.allocations']);
                $files[$po->po_number.'.xlsx'] = PurchaseOrderWorkbook::build($po, $po->items);
            }

            session()->flash('status', $message);

            if (count($files) === 1) {
                $name = array_key_first($files);

                return response($files[$name], 200, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Content-Disposition' => 'attachment; filename="'.$name.'"',
                    'Cache-Control' => 'no-store, no-cache, must-revalidate',
                    'Pragma' => 'no-cache',
                ]);
            }

            // Multiple awarded quotations on one checkout: one ZIP with every workbook.
            $temporaryPath = tempnam(sys_get_temp_dir(), 'po_batch_').'.zip';
            $zip = new \ZipArchive;

            if ($zip->open($temporaryPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Unable to create the purchase order batch archive.');
            }

            foreach ($files as $name => $content) {
                $zip->addFromString($name, $content);
            }

            $zip->close();

            $contents = file_get_contents($temporaryPath);
            unlink($temporaryPath);

            if ($contents === false) {
                throw new \RuntimeException('Unable to read the purchase order batch archive.');
            }

            return response($contents, 200, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="PurchaseOrders-'.count($files).'-'.now()->format('Ymd-His').'.zip"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ]);
        } catch (\Throwable $exception) {
            // The POs already exist and the cart is already emptied; never strand the user.
            report($exception);

            return redirect()
                ->route('purchase-order')
                ->with('status', $message.' (the file download failed — open each draft from the list)');
        }
    }

    /**
     * Only the awarded (quoted) lines with remaining need ever enter the cart.
     */
    private function notCartableReason(QuotationItem $quotationItem): ?string
    {
        if (! $quotationItem->unit_price) {
            return 'Only items with a supplier unit price can be added to the cart.';
        }

        $remaining = $quotationItem->preplistItem?->remainingQuantity() ?? 0;

        if ($remaining <= 0) {
            return 'That line is fully covered by on-hand stock; there is nothing to order.';
        }

        if (PurchaseOrderItem::query()->where('quotation_item_id', $quotationItem->id)->exists()) {
            return 'That line is already on a purchase order.';
        }

        return null;
    }

    /**
     * Add every orderable line of an awarded quotation to the cart.
     */
    private function addQuotation(Request $request, int $quotationId)
    {
        $quotation = Quotation::query()
            ->with(['items.preplistItem.item', 'items.preplistItem.sources.projectItem', 'supplier'])
            ->findOrFail($quotationId);

        $added = 0;
        $skipped = 0;

        foreach ($quotation->items as $quotationItem) {
            if ($reason = $this->notCartableReason($quotationItem)) {
                $skipped++;

                continue;
            }

            $preplistItemId = $quotationItem->preplistItem?->id;

            if ($preplistItemId !== null) {
                // One line per item; this supplier's line wins over any other.
                $request->user()->poCartItems()
                    ->whereIn('quotation_item_id', function ($query) use ($preplistItemId) {
                        $query->select('qi.id')
                            ->from('quotation_items as qi')
                            ->where('qi.preplist_item_id', $preplistItemId);
                    })
                    ->delete();
            }

            PoCartItem::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'quotation_item_id' => $quotationItem->id],
                ['qty' => $quotationItem->preplistItem->remainingQuantity() ?? 1],
            );
            $added++;
        }

        if ($added === 0) {
            return back()->withErrors(['cart' => 'Nothing from this quotation can be added — every line is covered by stock, an existing purchase order, or has no price.']);
        }

        return back()->with('status', 'Added '.$added.' line'.($added === 1 ? '' : 's').' from '.($quotation->supplier?->name ?? 'this supplier').' to your cart.'.($skipped ? ' ('.$skipped.' skipped)' : ''));
    }

    private function authorizeLine(Request $request, PoCartItem $cartItem): void
    {
        abort_unless($cartItem->user_id === $request->user()?->id, 403);
    }
}
