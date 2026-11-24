<?php

namespace App\Http\Controllers;

use App\Models\PoCartItem;
use App\Models\PurchaseOrderItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Support\PurchaseOrderCreator;
use Illuminate\Database\QueryException;
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

            $itemBox = $item?->box_size;
            $supplierBox = $qi->supplier_box_size;
            $requiredOurBoxes = $remaining;
            $packageSuggestion = \App\Support\PackagingAdjuster::suggestion(
                $itemBox !== null ? (int) $itemBox : null,
                $supplierBox !== null ? (int) $supplierBox : null,
                (int) $requiredOurBoxes,
            );

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
                'itemBox' => $itemBox !== null ? (int) $itemBox : null,
                'supplierBox' => $supplierBox !== null ? (int) $supplierBox : null,
                'packageSuggestion' => $packageSuggestion,
                'allowedMax' => $this->cartLineAllowedMax($itemBox, $supplierBox, (int) $remaining),
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
    /**
     * Same packaging-aware ceiling used elsewhere in this controller, as a
     * plain helper for the cart view.
     */
    private function cartLineAllowedMax($itemBox, $supplierBox, int $remaining): int
    {
        $suggestion = \App\Support\PackagingAdjuster::suggestion(
            $itemBox !== null ? (int) $itemBox : null,
            $supplierBox !== null ? (int) $supplierBox : null,
            $remaining,
        );

        return $suggestion ? $suggestion['neededBoxes'] : $remaining;
    }

    /**
     * The ceiling for a line on the current packaging — the plain remaining
     * requirement unless the supplier packs differently, in which case it is
     * the number of their boxes needed to cover the requirement.
     */
    private function packagingAllowedMax(PoCartItem $cartItem, int $remaining): int
    {
        $qi = $cartItem->quotationItem;
        $item = $qi?->preplistItem?->item;
        $itemBox = $item?->box_size !== null ? (int) $item->box_size : null;
        $supplierBox = $qi?->supplier_box_size !== null ? (int) $qi->supplier_box_size : null;

        $suggestion = \App\Support\PackagingAdjuster::suggestion($itemBox, $supplierBox, $remaining);

        return $suggestion ? $suggestion['neededBoxes'] : $remaining;
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
            'qty' => ['nullable', 'array'],
            'qty.*' => ['integer', 'min:1', 'max:1000000'],
        ]);

        $submittedQty = $validated['qty'] ?? [];

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

        // Quantities live in the browser until checkout; find them here and
        // apply them to the in-memory cart lines before any further handling.
        foreach ($cartItems as $cartItem) {
            $submitted = $submittedQty[$cartItem->id] ?? $submittedQty[(string) $cartItem->id] ?? null;
            if ($submitted !== null) {
                $cartItem->qty = (int) $submitted;
            }
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
            if ($cartItem->qty < 1 || $cartItem->qty > $this->packagingAllowedMax($cartItem, (int) $requested)) {
                $invalid[] = $name.' (qty '.$cartItem->qty.' exceeds what remains on the supplier packaging)';

                continue;
            }
            if (PurchaseOrderItem::query()->where('quotation_item_id', $qi->id)->exists()) {
                $invalid[] = $name.' (already on a purchase order)';
            }
        }

        // Grouped guard: a prep list item may only be ordered once (one
        // winning supplier). Summing quantities across lines sharing the same
        // preplist item must not exceed what remains to procure.
        $remainingByPreplistItem = [];

        foreach ($cartItems as $cartItem) {
            $qi = $cartItem->quotationItem;

            if ($qi) {
                $remaining = $qi->preplistItem?->remainingQuantity() ?? 0;
                $itemBox = $qi->preplistItem?->item?->box_size !== null
                    ? (int) $qi->preplistItem->item->box_size
                    : null;
                $supplierBox = $qi->supplier_box_size !== null
                    ? (int) $qi->supplier_box_size
                    : null;
                $max = \App\Support\PackagingAdjuster::suggestion($itemBox, $supplierBox, (int) $remaining);

                $remainingByPreplistItem[$qi->preplist_item_id] = $max
                    ? $max['neededBoxes']
                    : (int) $remaining;
            }
        }

        foreach ($remainingByPreplistItem as $preplistItemId => $remaining) {
            $sum = $cartItems
                ->filter(fn ($ci) => (int) $ci->quotationItem?->preplist_item_id === (int) $preplistItemId)
                ->sum('qty');

            if ($sum > $remaining) {
                $invalid[] = 'Preplist item #'.$preplistItemId.' — lines together '.$sum.', remaining '.$remaining.'. A prep list item may only be ordered once (one winning supplier). Remove one of the duplicated quotation lines from the cart.';
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

        try {
            foreach ($cartItems->groupBy(fn ($ci) => $ci->quotationItem->quotation_id) as $quotationId => $group) {
                $lines = [];
                foreach ($group as $cartItem) {
                    // Quantities here live in the supplier's box units whenever
                    // the supplier packs differently (the checkout cap is the
                    // pack-adjusted ceiling), so snapshot that pack on the PO
                    // line: base pieces reconcile the requirement, the
                    // supplier's pack, and what actually arrives.
                    $itemBox = (int) ($cartItem->quotationItem?->preplistItem?->item?->box_size ?? 0);
                    $supplierBox = (int) ($cartItem->quotationItem?->supplier_box_size ?? 0);
                    $pack = $itemBox > 0 && $supplierBox > 0 ? $supplierBox : null;

                    $lines[] = [
                        'quotation_item' => $cartItem->quotationItem,
                        'preplist_item' => $cartItem->quotationItem->preplistItem,
                        'qty' => $cartItem->qty,
                        'unit_price' => $cartItem->quotationItem->unit_price,
                        'min_remaining_months' => $cartItem->quotationItem->min_remaining_months,
                        'box_size' => $pack,
                    ];
                }
                $created[] = PurchaseOrderCreator::createFor($request->user()->id, $lines, $details);
            }
        } catch (QueryException $exception) {
            if ($this->isDuplicateAwardedLineViolation($exception)) {
                throw ValidationException::withMessages([
                    'cart' => 'Another user already created a purchase order for one of these awarded lines. Refresh the page and check the purchase order list — nothing was created here.',
                ]);
            }

            throw $exception;
        }

        $request->user()->poCartItems()->delete();

        $numbers = collect($created)->pluck('po_number')->implode(', ');
        $message = 'Created '.count($created).' purchase order'.(count($created) === 1 ? '' : 's').': '.$numbers.'. The draft POs are ready to review and send.';

        session()->flash('status', $message);

        // Return to the cart blade after creating the draft POs — the checkout
        // modal is left behind with the now-empty cart instead of staying open.
        return $this->redirectBack($request, $message);
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

    /**
     * Detect the unique-index violation for quotation_item_id specifically, so
     * a legitimate cart around a different constraint is allowed to bubble up.
     */
    private function isDuplicateAwardedLineViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, '23000')
            && (str_contains($message, 'purchase_order_items_quotation_item_id_unique')
                || str_contains($message, "'purchase_order_items.quotation_item_id'"));
    }
}
