<?php

namespace App\Http\Controllers;

use App\Models\PreplistItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Support\ActivityLogger;
use App\Support\Pagination;
use App\Support\PurchaseOrderCreator;
use App\Support\PurchaseReceipts;
use App\Support\PurchaseOrderWorkbook;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    /**
     * Convert a unique-violation into a validation error for the user.
     */
    private function throwIfUniqueViolation(QueryException $exception): never
    {
        if (str_contains($exception->getMessage(), 'UNIQUE') || str_contains($exception->getMessage(), 'unique')) {
            throw ValidationException::withMessages([
                'items' => 'Something went wrong while creating the purchase order. Please try again.',
            ]);
        }

        throw $exception;
    }

    /**
     * Show the purchase order tracker using real database records.
     * Search and filter happen in SQL so pagination works across the full set.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $supplier = trim((string) $request->query('supplier', ''));
        $status = trim((string) $request->query('status', ''));
        $perPage = Pagination::perPage($request, 10);

        $query = PurchaseOrder::query()
            ->with(['supplier', 'quotation.preplist', 'items'])
            ->orderByDesc('id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('quotation', function ($query) use ($search) {
                            $query->where('rfq_number', 'like', "%{$search}%")
                                ->orWhereHas('preplist', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                        });
                });
            })
            ->when($supplier !== '' && ctype_digit($supplier), fn ($query) => $query->where('supplier_id', (int) $supplier))
            ->when($status !== '', fn ($query) => $query->where('status', $status));

        $purchaseOrders = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (PurchaseOrder $purchaseOrder) => [
                'id' => $purchaseOrder->id,
                'number' => $purchaseOrder->po_number,
                'supplier' => $purchaseOrder->supplier?->name ?? 'Supplier removed',
                'rfq_number' => $purchaseOrder->quotation?->rfq_number,
                'prep_list' => $purchaseOrder->quotation?->preplist?->name,
                'local' => $purchaseOrder->isLocalPurchase(),
                'pricing_tbd' => $purchaseOrder->isLocalPurchase() && $purchaseOrder->items->contains(fn ($item) => $item->unit_price === null),
                'items' => $purchaseOrder->items->count(),
                'total' => $purchaseOrder->grandTotal(),
                'status' => $purchaseOrder->statusLabel(),
                'issued' => $purchaseOrder->issued_on?->format('M j, Y'),
                'cancellable' => $purchaseOrder->isCancellable(),
                'deletable' => $purchaseOrder->isDeletable(),
            ]);

        $supplierOptions = Supplier::query()
            ->whereIn('id', PurchaseOrder::query()->select('supplier_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $statusOptions = [
            PurchaseOrder::STATUS_DRAFT => 'Draft',
            PurchaseOrder::STATUS_SENT => 'Sent to supplier',
            PurchaseOrder::STATUS_PARTIALLY_RECEIVED => 'Partially received',
            PurchaseOrder::STATUS_COMPLETED => 'Completed',
            PurchaseOrder::STATUS_CANCELLED => 'Cancelled',
        ];

        return view('Purchaseorder.index', compact(
            'purchaseOrders',
            'supplierOptions',
            'statusOptions',
            'search',
            'supplier',
            'status',
            'perPage',
        ));
    }

    /**
     * Prepare a draft purchase order from an awarded quotation.
     */
    public function create(int $rfq)
    {
        $quotation = Quotation::query()
            ->with([
                'supplier',
                'preplist.items.item',
                'preplist.items.sources.projectItem.project',
                'items.preplistItem',
            ])
            ->findOrFail($rfq);

        if ($quotation->preplist === null) {
            return redirect()
                ->route('quotation.show', ['quotation' => $quotation->id])
                ->withErrors(['quotation' => 'This quotation’s prep list no longer exists.']);
        }

        $quotationItems = $quotation->items->keyBy('preplist_item_id');

        $items = $quotation->preplist->items
            ->map(function (PreplistItem $preplistItem) use ($quotationItems) {
                $quotationItem = $quotationItems->get($preplistItem->id);
                $unit = $preplistItem->item?->unit ?? '';

                $remaining = $preplistItem->remainingQuantity();

                // A supplier packing differently changes the quantity unit: one
                // of their boxes covers theirBox pieces, so the orderable
                // quantity is the pack-adjusted ceiling of the requirement.
                $itemBox = $preplistItem->item?->box_size !== null
                    ? (int) $preplistItem->item->box_size
                    : null;
                $supplierBox = $quotationItem?->supplier_box_size !== null
                    ? (int) $quotationItem->supplier_box_size
                    : null;
                $suggestion = \App\Support\PackagingAdjuster::suggestion($itemBox, $supplierBox, $remaining);
                $maxQty = $suggestion ? $suggestion['neededBoxes'] : $remaining;

                return [
                    'id' => $preplistItem->id,
                    'quotation_item_id' => $quotationItem?->id,
                    'name' => $preplistItem->item?->name ?? 'Item',
                    'requested' => $remaining,
                    'remaining' => $remaining,
                    'max_qty' => $maxQty,
                    'supplier_pack' => $suggestion ? $suggestion['theirBox'] : null,
                    'item_pack' => $itemBox > 0 ? $itemBox : null,
                    'unit' => $unit,
                    'unit_price' => $quotationItem?->unit_price,
                    'min_remaining_months' => (int) $preplistItem->min_shelf_life_months,
                    'expiry' => $quotationItem?->expiry?->toDateString(),
                    'allocations' => $preplistItem->sources
                        ->map(fn ($source) => $source->projectItem?->project?->title ?? 'Project removed')
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            // Lines fully covered by on-hand stock never appear on the PO prep list.
            ->filter(fn (array $item) => $item['remaining'] > 0)
            ->values();

        return view('Purchaseorder.create', [
            'quotation' => $quotation,
            'items' => $items,
        ]);
    }

    /**
     * Persist the draft purchase order and its lines.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'quotation_id' => ['required', 'integer', 'exists:quotations,id'],
            'expected_delivery' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'deliver_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.quotation_item_id' => ['nullable', 'integer'],
            'items.*.included' => ['nullable', 'boolean'],
            'items.*.qty' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'items.*.min_remaining_months' => ['nullable', 'integer', 'min:0', 'max:1200'],
        ], [
            'items.required' => 'Select at least one item to order.',
        ]);

        $quotation = Quotation::query()
            ->with(['preplist.items.item', 'preplist.items.sources.projectItem', 'items'])
            ->findOrFail($validated['quotation_id']);

        $preplistItems = $quotation->preplist->items->keyBy('id');
        $quotationItems = $quotation->items->keyBy('id');

        $selected = collect($validated['items'])->filter(
            fn ($row) => filter_var($row['included'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );

        if ($selected->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Select at least one quoted item to order.']);
        }

        $lines = [];

        foreach ($selected as $row) {
            $quotationItem = $quotationItems->get((int) ($row['quotation_item_id'] ?? 0));

            if (! $quotationItem?->unit_price) {
                throw ValidationException::withMessages([
                    'items' => 'Only items with a supplier unit price can be ordered.',
                ]);
            }

            $preplistItem = $preplistItems->get($quotationItem->preplist_item_id);
            $requested = $preplistItem?->remainingQuantity() ?? 0;

            // Lines fully covered by on-hand stock are never ordered.
            if ($requested <= 0) {
                continue;
            }

            // When the supplier packs differently, quantities are their
            // boxes: one of their boxes covers theirBox pieces, so the
            // orderable quantity is the pack-adjusted ceiling of the
            // requirement, and the PO line snapshots that pack so receipt
            // accounting reconciles in base pieces.
            $itemBox = $preplistItem->item?->box_size !== null
                ? (int) $preplistItem->item->box_size
                : null;
            $supplierBox = $quotationItem->supplier_box_size !== null
                ? (int) $quotationItem->supplier_box_size
                : null;
            $suggestion = \App\Support\PackagingAdjuster::suggestion($itemBox, $supplierBox, (int) $requested);
            $maxQty = $suggestion ? $suggestion['neededBoxes'] : (int) $requested;

            $qty = (int) ($row['qty'] ?? 0);

            if ($qty < 1) {
                throw ValidationException::withMessages([
                    'items' => 'Enter a purchase quantity of at least 1 for each selected item.',
                ]);
            }

            if ($qty > $maxQty) {
                throw ValidationException::withMessages([
                    'items' => 'The purchase quantity cannot exceed the quantity requested internally.',
                ]);
            }

            $lines[] = [
                'quotation_item' => $quotationItem,
                'preplist_item' => $preplistItem,
                'qty' => $qty,
                'unit_price' => $quotationItem->unit_price,
                'min_remaining_months' => (int) ($row['min_remaining_months'] ?? $quotationItem->min_remaining_months ?? 0),
                'box_size' => $suggestion ? $suggestion['theirBox'] : null,
            ];
        }

        if (empty($lines)) {
            throw ValidationException::withMessages([
                'items' => 'Every selected item is fully covered by on-hand stock; there is nothing to order.',
            ]);
        }

        try {
            $purchaseOrder = PurchaseOrderCreator::createFor($request->user()->id, $lines, [
                'expected_delivery' => $validated['expected_delivery'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'deliver_to' => $validated['deliver_to'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        } catch (QueryException $exception) {
            $this->throwIfUniqueViolation($exception);
        }

        $purchaseOrder->load(['items.item', 'items.allocations']);

        $content = PurchaseOrderWorkbook::build($purchaseOrder, $purchaseOrder->items);

        session()->flash('status', 'Draft purchase order '.$purchaseOrder->po_number.' created.');

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$purchaseOrder->po_number.'.xlsx"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * Show one purchase order with its lines and receipts.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier',
            'quotation.preplist',
            'items.item',
            'items.receipts.receiver',
            'items.allocations',
        ]);

        return view('Purchaseorder.show', ['purchaseOrder' => $purchaseOrder]);
    }

    /**
     * Delete a purchase order. Only administrators can delete, and only when
     * the order is a draft or has been cancelled.
     */
    public function destroy(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($request->user()?->isAdministrator(), 403);

        if (! $purchaseOrder->isDeletable()) {
            return redirect()
                ->route('purchase-order')
                ->withErrors(['purchase_order' => 'Only draft or cancelled purchase orders can be deleted.']);
        }

        DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder->delete();

            ActivityLogger::log('Deleted', 'Purchase Order', $purchaseOrder->po_number, $purchaseOrder);
        });

        return redirect()
            ->route('purchase-order')
            ->with('status', 'Purchase order '.$purchaseOrder->po_number.' has been deleted.');
    }

    /**
     * Mark a draft purchase order as sent to the supplier.
     */
    public function send(Request $request, PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->isLocalPurchase()) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'Local purchases are not sent to suppliers. Pick the goods at the store, then use Mark as bought.']);
        }

        if ($purchaseOrder->status !== PurchaseOrder::STATUS_DRAFT) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'Only a draft purchase order can be sent to the supplier.']);
        }

        DB::transaction(function () use ($purchaseOrder) {
            $purchaseOrder->update([
                'status' => PurchaseOrder::STATUS_SENT,
                'issued_on' => today(),
            ]);

            ActivityLogger::log('Edited', 'Purchase Order', $purchaseOrder->po_number, $purchaseOrder);
        });

        return redirect()
            ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
            ->with('status', 'Purchase order '.$purchaseOrder->po_number.' marked as sent to the supplier.');
    }

    /**
     * Cancel a purchase order before it is completed.
     */
    public function cancel(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (! $purchaseOrder->isCancellable()) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'A completed or cancelled purchase order can no longer be cancelled.']);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($purchaseOrder, $validated) {
            $purchaseOrder->update([
                'status' => PurchaseOrder::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancel_reason' => $validated['reason'] ?? null,
            ]);

            ActivityLogger::log('Edited', 'Purchase Order', $purchaseOrder->po_number, $purchaseOrder);
        });

        return redirect()
            ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
            ->with('status', 'Purchase order '.$purchaseOrder->po_number.' has been cancelled.');
    }

    /**
     * Record a receipt against a purchase order line.
     *
     * Receipts accumulate: each call records how many units arrived this
     * time (defaulting to the full outstanding quantity). The order is only
     * completed once every line is fully received; until then it stays
     * partially received. Piece-tracked lines (a packing snapshot stored on
     * the line) are reconciled in base pieces, so a partial physical delivery
     * cannot silently complete a requirement ordered in another pack size.
     */
    public function toggleReceipt(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderItem $purchaseOrderItem)
    {
        if ($purchaseOrderItem->purchase_order_id !== $purchaseOrder->id) {
            abort(404);
        }

        if ($purchaseOrder->isLocalPurchase()) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'Local purchases are recorded with Mark as bought.']);
        }

        if (! $purchaseOrder->canReceive()) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'Send the purchase order to the supplier before recording receipts.']);
        }

        if ($purchaseOrderItem->isReceived()) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'This line is already recorded as received and cannot be unmarked.']);
        }

        $expected = $request->validate([
            'received_qty' => ['nullable', 'integer', 'min:1'],
            'expected_received' => ['nullable', 'boolean'],
        ]);

        if (isset($expected['expected_received']) && (bool) $expected['expected_received'] !== $purchaseOrderItem->isReceived()) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'Someone else already recorded this receipt since the page was loaded. Refresh the page and try again.']);
        }

        // Piece-tracked lines (one packing declared on the line: boxed local
        // purchases, and supplier lines whose supplier packs differently) are
        // reconciled in base pieces — a partial pack of the requirement stays
        // open and the project cannot complete on short physical stock.
        $remaining = $purchaseOrderItem->trackedInPieces()
            ? $purchaseOrderItem->remainingPieces()
            : $purchaseOrderItem->remainingQty();

        $qty = (int) ($expected['received_qty'] ?? $remaining);

        if ($qty < 1 || $qty > $remaining) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'Enter a received quantity between 1 and '.$remaining.' for this line.']);
        }

        DB::transaction(function () use ($request, $purchaseOrder, $purchaseOrderItem, $qty) {
            PurchaseReceipts::finalize($purchaseOrderItem, $request->user()->id, $qty);

            $this->refreshReceiptStatus($purchaseOrder);

            ActivityLogger::log('Edited', 'Purchase Order', $purchaseOrder->po_number, $purchaseOrder);
        });

        return redirect()
            ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
            ->with('status', $qty === $remaining
                ? 'Line marked as received.'
                : 'Recorded '.$qty.' of '.($purchaseOrderItem->trackedInPieces() ? $purchaseOrderItem->orderedPieces() : $purchaseOrderItem->qty).' received.');
    }

    /**
     * Record a local purchase trip.
     *
     * The form collects the quantity bought and the price paid on each
     * outstanding line. A local purchase settles in one trip: the order is
     * completed the moment the details are recorded. A line left partly
     * bought (or skipped) does not keep the order open — what the store
     * did not supply stays outstanding on the project items, where it can
     * be compiled again to source the rest from other suppliers.
     */
    public function bought(Request $request, PurchaseOrder $purchaseOrder)
    {
        if (! $purchaseOrder->isLocalPurchase()) {
            abort(403, 'Only local purchase orders can be marked as bought.');
        }

        if (in_array($purchaseOrder->status, [PurchaseOrder::STATUS_COMPLETED, PurchaseOrder::STATUS_CANCELLED], true)) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['bought' => 'This order is already completed or cancelled.']);
        }

        $purchaseOrder->load('items.item', 'items.receipts', 'items.preplistItem');

        $validated = $request->validate([
            'prices' => ['nullable', 'array'],
            'prices.*' => ['nullable', 'numeric', 'min:0'],
            'quantities' => ['nullable', 'array'],
            'quantities.*' => ['nullable', 'integer', 'min:0'],
            'boxes' => ['nullable', 'array'],
            'boxes.*' => ['nullable', 'integer', 'min:0'],
            'box_sizes' => ['nullable', 'array'],
            'box_sizes.*' => ['nullable', 'integer', 'min:1'],
        ]);

        $prices = $validated['prices'] ?? [];
        $quantities = $validated['quantities'] ?? [];
        $boxes = $validated['boxes'] ?? [];
        $boxSizes = $validated['box_sizes'] ?? [];

        // Every line still open for purchase is recorded in this trip —
        // set its quantity to 0 to leave it unbought.
        $pending = $purchaseOrder->items
            ->filter(fn (PurchaseOrderItem $item) => ! $item->isReceived())
            ->values();

        $missing = [];
        $boughtThisTrip = [];

        foreach ($pending as $line) {
            // Box logic only applies to piece-tracked lines: a boxed local
            // line is reconciled in base pieces no matter which pack the item
            // is actually bought in, and plain unit lines skip it. The box
            // size comes from the item actually bought (a line can outlive a
            // box-size edit); the requirement snapshot only backs packless
            // items, and the store's pack can still be recorded per purchase.
            $tracked = $line->trackedInPieces();
            $remaining = $tracked ? $line->remainingPieces() : $line->remainingQty();
            $requirementBox = $tracked ? (int) ($line->item?->box_size ?? $line->box_size) : 0;
            $boxSize = $requirementBox > 0 ? (int) ($boxSizes[$line->id] ?? $requirementBox) : 0;
            $boxCount = $boxSize > 0 ? (int) ($boxes[$line->id] ?? 0) : 0;
            $maxBoxes = $boxSize > 0 ? (int) ceil($remaining / $boxSize) : 0;

            if ($boxSize > 0 && $boxCount > $maxBoxes) {
                throw ValidationException::withMessages([
                    'boxes' => 'Box count for '.($line->item?->name ?? 'a line').' cannot exceed '.$maxBoxes.' for the '.$remaining.' still outstanding.',
                ]);
            }

            // A box count drives the bought quantity: buying N boxes of a
            // Box-of-M pack covers N x M base pieces (packaging may overage);
            // the pack itself can be overridden per purchase (a Box-of-10
            // bought against a Box-of-20 requirement weights the quantity by
            // 10). Without a box count the plain piece/unit quantity is used.
            $qty = $boxCount > 0 ? $boxCount * $boxSize : (int) ($quantities[$line->id] ?? $remaining);

            if ($boxCount === 0 && ($qty < 0 || $qty > $remaining)) {
                throw ValidationException::withMessages([
                    'quantities' => 'Quantity for '.($line->item?->name ?? 'a line').' must be between 0 and '.$remaining.($tracked ? ' pieces' : '').'.',
                ]);
            }

            $boughtThisTrip[$line->id] = $qty;

            if ($qty === 0 || $line->unit_price !== null) {
                continue;
            }

            $value = trim((string) ($prices[$line->id] ?? ''));

            if ($value === '' || ! is_numeric($value)) {
                $missing[] = $line->item?->name ?? 'Line #'.$line->id;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'prices' => 'Enter the price paid for: '.implode(', ', $missing).'.',
            ]);
        }

        if (array_sum($boughtThisTrip) === 0) {
            throw ValidationException::withMessages([
                'quantities' => 'Enter a quantity for at least one line.',
            ]);
        }

        $fullyBought = false;

        DB::transaction(function () use ($request, $purchaseOrder, $pending, $prices, $boughtThisTrip, &$fullyBought) {
            foreach ($pending as $line) {
                $qty = $boughtThisTrip[$line->id];

                if ($qty === 0) {
                    continue;
                }

                if ($line->unit_price === null) {
                    $line->update(['unit_price' => $prices[$line->id]]);
                }

                // Local purchases may overage through box math (buying a full
                // box beyond the outstanding need); supplier receipts stay strict.
                PurchaseReceipts::finalize($line, $request->user()->id, $qty, true);
            }

            if ($purchaseOrder->issued_on === null) {
                $purchaseOrder->update(['issued_on' => today()]);
            }

            $this->refreshReceiptStatus($purchaseOrder);

            // A local purchase is settled once the trip's details are in —
            // do not hold the order open for lines the store did not supply.
            $fullyBought = $purchaseOrder->items->every(fn (PurchaseOrderItem $item) => $item->isReceived());

            if ($purchaseOrder->isLocalPurchase()) {
                $purchaseOrder->update(['status' => PurchaseOrder::STATUS_COMPLETED]);
            }

            ActivityLogger::log(
                'Completed',
                'Purchase Order',
                $purchaseOrder->po_number.' (local purchase bought)',
                $purchaseOrder
            );
        });

        return redirect()
            ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
            ->with('status', $fullyBought
                ? 'Purchase order '.$purchaseOrder->po_number.' is fully bought and completed.'
                : 'Purchase order '.$purchaseOrder->po_number.' is completed. What its lines still lacked stays open with the projects — compile it again to source the rest from other suppliers.');
    }

    /**
     * Move the purchase order between Sent / Partially received / Completed
     * based on how many lines have been fully received.
     */
    private function refreshReceiptStatus(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status === PurchaseOrder::STATUS_CANCELLED) {
            return;
        }

        $purchaseOrder->load('items.receipts');

        $total = $purchaseOrder->items->count();
        $received = $purchaseOrder->items->filter(fn (PurchaseOrderItem $item) => $item->isReceived())->count();
        $hasAnyReceipt = $purchaseOrder->items->contains(fn (PurchaseOrderItem $item) => $item->receivedQty() > 0);

        $purchaseOrder->update([
            'status' => match (true) {
                $total > 0 && $received >= $total => PurchaseOrder::STATUS_COMPLETED,
                $hasAnyReceipt => PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                $purchaseOrder->isLocalPurchase() => PurchaseOrder::STATUS_DRAFT,
                default => PurchaseOrder::STATUS_SENT,
            },
        ]);
    }
}
