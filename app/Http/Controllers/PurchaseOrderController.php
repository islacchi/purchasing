<?php

namespace App\Http\Controllers;

use App\Models\NumberSequence;
use App\Models\PreplistItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderItemAllocation;
use App\Models\PurchaseOrderReceipt;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Support\ActivityLogger;
use App\Support\Pagination;
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
                'rfq_number' => $purchaseOrder->quotation?->rfq_number ?? '—',
                'prep_list' => $purchaseOrder->quotation?->preplist?->name ?? 'Prep list removed',
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

                return [
                    'id' => $preplistItem->id,
                    'quotation_item_id' => $quotationItem?->id,
                    'name' => $preplistItem->item?->name ?? 'Item',
                    'requested' => $remaining,
                    'remaining' => $remaining,
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
            ->with(['preplist.items.sources.projectItem', 'items'])
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
            $qty = (int) ($row['qty'] ?? 0);

            if ($qty < 1) {
                throw ValidationException::withMessages([
                    'items' => 'Enter a purchase quantity of at least 1 for each selected item.',
                ]);
            }

            if ($requested > 0 && $qty > $requested) {
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
            ];
        }

        if (empty($lines)) {
            throw ValidationException::withMessages([
                'items' => 'Every selected item is fully covered by on-hand stock; there is nothing to order.',
            ]);
        }

        try {
            $purchaseOrder = DB::transaction(function () use ($request, $validated, $quotation, $lines) {
                $purchaseOrder = PurchaseOrder::create([
                    'quotation_id' => $quotation->id,
                    'supplier_id' => $quotation->supplier_id,
                    'created_by' => $request->user()->id,
                    'po_number' => $this->nextPoNumber(),
                    'status' => PurchaseOrder::STATUS_DRAFT,
                    'expected_delivery_on' => $validated['expected_delivery'] ?? null,
                    'payment_terms' => $validated['payment_terms'] ?? null,
                    'deliver_to' => $validated['deliver_to'] ?? null,
                    'delivery_fee' => 0,
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($lines as $line) {
                    /** @var PreplistItem $preplistItem */
                    $preplistItem = $line['preplist_item'];

                    $purchaseOrderItem = PurchaseOrderItem::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'quotation_item_id' => $line['quotation_item']->id,
                        'item_id' => $preplistItem->item_id,
                        'qty' => $line['qty'],
                        'unit_price' => $line['unit_price'],
                        'min_remaining_months' => $line['min_remaining_months'],
                    ]);

                    // Allocate the ordered quantity across the prep list's project
                    // sources in proportion to what each source still needs
                    // (awarded quantity minus on-hand stock).
                    $sources = $preplistItem->sources->values();
                    $shares = $sources->map(fn ($source) => $source->projectItem?->remainingQuantity() ?? 0);
                    $totalRequested = (int) $shares->sum();
                    $remaining = $line['qty'];
                    $lastPositiveIndex = $shares->filter(fn ($share) => $share > 0)->keys()->last();

                    foreach ($sources as $index => $source) {
                        $weight = $shares[$index];

                        if ($totalRequested > 0) {
                            $share = $lastPositiveIndex !== null && $index === $lastPositiveIndex
                                ? $remaining
                                : (int) floor($line['qty'] * $weight / $totalRequested);
                        } else {
                            $share = $index === 0 ? $line['qty'] : 0;
                        }

                        $remaining -= $share;

                        if ($share <= 0) {
                            continue;
                        }

                        PurchaseOrderItemAllocation::create([
                            'purchase_order_item_id' => $purchaseOrderItem->id,
                            'project_item_id' => $source->project_item_id,
                            'qty' => $share,
                        ]);
                    }
                }

                ActivityLogger::log('Created', 'Purchase Order', $purchaseOrder->po_number, $purchaseOrder);

                return $purchaseOrder;
            });
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
     * Toggle whether a purchase order line has been fully received.
     */
    public function toggleReceipt(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderItem $purchaseOrderItem)
    {
        if ($purchaseOrderItem->purchase_order_id !== $purchaseOrder->id) {
            abort(404);
        }

        if (! $purchaseOrder->canReceive()) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'Send the purchase order to the supplier before recording receipts.']);
        }

        $expected = $request->validate(['expected_received' => 'nullable|boolean']);

        if (isset($expected['expected_received']) && (bool) $expected['expected_received'] !== $purchaseOrderItem->isReceived()) {
            return redirect()
                ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
                ->withErrors(['purchase_order' => 'Someone else already recorded this receipt since the page was loaded. Refresh the page and try again.']);
        }

        DB::transaction(function () use ($request, $purchaseOrder, $purchaseOrderItem) {
            if ($purchaseOrderItem->receipts()->exists()) {
                $purchaseOrderItem->receipts()->delete();
            } else {
                PurchaseOrderReceipt::create([
                    'purchase_order_item_id' => $purchaseOrderItem->id,
                    'received_by' => $request->user()->id,
                    'received_qty' => $purchaseOrderItem->qty,
                    'received_on' => today(),
                ]);
            }

            $this->refreshReceiptStatus($purchaseOrder);

            ActivityLogger::log('Edited', 'Purchase Order', $purchaseOrder->po_number, $purchaseOrder);
        });

        return redirect()
            ->route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id])
            ->with('status', 'Receipt updated.');
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

        $purchaseOrder->update([
            'status' => match (true) {
                $received === 0 => PurchaseOrder::STATUS_SENT,
                $received >= $total => PurchaseOrder::STATUS_COMPLETED,
                default => PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            },
        ]);
    }

    /**
     * Generate the next purchase order number for the current year.
     */
    private function nextPoNumber(): string
    {
        $year = (int) now()->year;

        $sequence = NumberSequence::query()
            ->where('key', 'po')
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            $sequence = NumberSequence::query()->create([
                'key' => 'po',
                'year' => $year,
                'last_number' => 0,
            ]);
        }

        $sequence->last_number++;
        $sequence->save();

        return sprintf('PO-%d-%04d', $year, $sequence->last_number);
    }
}
