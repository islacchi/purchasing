<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderItemAllocation;
use App\Models\Supplier;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ItemCatalogController extends Controller
{
    /**
     * Show the item catalog: one row per item that appears on at least one
     * fully-received purchase order line, aggregated server-side. Filters
     * (search, category, supplier, date range) apply server-side and
     * pagination links preserve them.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));
        $supplier = trim((string) $request->query('supplier', ''));
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));
        $perPage = Pagination::perPage($request, 10);

        $fromDate = $this->dateOrNull($from);
        $toDate = $this->dateOrNull($to);

        $lines = $this->purchasedLines($fromDate, $toDate);

        $items = $this->aggregateLines($lines);

        // Category filter mirrors the project-form taxonomy: two canonical categories.
        $categoryAllowed = $category === '' ? null : (in_array($category, ['drugs and medicines', 'supplies and equipments'], true) ? $category : null);

        $items = $items
            ->filter(function (array $item) use ($search, $categoryAllowed, $supplier) {
                if ($search !== '' && ! str_contains(mb_strtolower($item['name'].' '.$item['unit']), mb_strtolower($search))) {
                    return false;
                }
                if ($categoryAllowed !== null && $item['category'] !== $categoryAllowed) {
                    return false;
                }
                if (ctype_digit($supplier) && ! in_array((int) $supplier, $item['supplier_ids'], true)) {
                    return false;
                }

                return true;
            })
            ->sortByDesc('last_issued_on')
            ->values();

        $totalSpend = (float) $items->sum('spend');
        $totalUnits = (int) $items->sum('qty');
        $totalLines = (int) $items->sum('orders');
        $suppliersUsed = $items->flatMap(fn ($item) => $item['supplier_ids'])->unique()->count();
        $lastPurchase = $items->sortByDesc('last_issued_on')->first();

        $page = max(1, (int) $request->query('page', 1));

        $pager = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        $categoryOptions = ItemCategory::query()
            ->whereIn('name', ['drugs and medicines', 'supplies and equipments'])
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();

        // Suppliers that actually appear in the fetched set, so the dropdown only lists relevant ones.
        $filterSuppliers = Supplier::query()
            ->whereIn('id', $lines->map(fn ($line) => $line->purchaseOrder?->supplier_id)->unique()->filter()->values()->all() ?: [0])
            ->orderBy('name')
            ->get(['id', 'name']);

        // Human-readable label of the applied filters, used on the print sheet.
        $selectedSupplierName = ctype_digit($supplier) ? Supplier::query()->find((int) $supplier)?->name : null;

        $filterBits = [];
        if ($search !== '') {
            $filterBits[] = 'Search: "'.$search.'"';
        }
        if ($categoryAllowed) {
            $filterBits[] = ucfirst($categoryAllowed);
        }
        if ($selectedSupplierName) {
            $filterBits[] = 'Supplier: '.$selectedSupplierName;
        }
        if ($from !== '') {
            $filterBits[] = 'From '.$from;
        }
        if ($to !== '') {
            $filterBits[] = 'To '.$to;
        }
        $filterLabel = empty($filterBits) ? 'All items' : implode(' · ', $filterBits);

        return view('ItemCatalog.index', compact(
            'pager',
            'items',
            'search',
            'categoryAllowed',
            'supplier',
            'from',
            'to',
            'perPage',
            'categoryOptions',
            'filterSuppliers',
            'totalSpend',
            'totalUnits',
            'totalLines',
            'suppliersUsed',
            'lastPurchase',
            'filterLabel',
        ));
    }

    /**
     * Show the full purchase history of a single catalog item.
     */
    public function show(Item $item)
    {
        $lines = $this->purchasedLines(null, null)
            ->filter(fn (PurchaseOrderItem $line) => (int) $line->item_id === (int) $item->id)
            ->sortByDesc(fn (PurchaseOrderItem $line) => ($line->purchaseOrder?->issued_on?->toDateString() ?? '').'-'.$line->id)
            ->values();

        $items = $this->aggregateLines($lines);

        $stats = $items->first();

        $item->loadMissing('category');

        // Supplier breakdown for this item (all time), sorted by spend.
        $supplierBreakdown = $lines
            ->groupBy(fn (PurchaseOrderItem $line) => $line->purchaseOrder?->supplier_id)
            ->map(function (Collection $group, $supplierId) {
                $spend = (float) $group->sum(fn (PurchaseOrderItem $line) => $line->qty * (float) $line->unit_price);
                $qty = (int) $group->sum('qty');
                $bestPrice = (float) $group->min('unit_price');
                $lastLine = $group->sortByDesc('id')->first();
                $totalSpend = (float) $group->sum(fn (PurchaseOrderItem $line) => $line->qty * (float) $line->unit_price);

                return [
                    'supplier_id' => (int) $supplierId,
                    'supplier' => $lastLine?->purchaseOrder?->supplier?->name ?? 'Supplier removed',
                    'supplier_ref' => $lastLine?->purchaseOrder?->supplier?->reference_number ?? null,
                    'orders' => $group->count(),
                    'qty' => $qty,
                    'spend' => $spend,
                    'avg_price' => $qty > 0 ? round($spend / $qty, 2) : 0.0,
                    'best_price' => $bestPrice,
                    'latest_price' => (float) ($lastLine?->unit_price ?? 0),
                ];
            })
            ->sortByDesc('spend')
            ->values();

        $totalSpendAll = (float) $supplierBreakdown->sum('spend');

        $history = $lines
            ->map(function (PurchaseOrderItem $line) {
                $po = $line->purchaseOrder;
                $qty = (int) $line->qty;
                $price = (float) $line->unit_price;

                return [
                    'po_number' => $po?->po_number,
                    'po_id' => $po?->id,
                    'rfq_number' => $po?->quotation?->rfq_number,
                    'supplier' => $po?->supplier?->name ?? 'Supplier removed',
                    'qty' => $qty,
                    'unit_price' => $price,
                    'line_total' => round($qty * $price, 2),
                    'issued_on' => $po?->issued_on?->format('M j, Y'),
                    'status' => $po?->statusLabel(),
                ];
            })
            ->values();

        // Chart points: oldest first.
        $chartPoints = $lines
            ->sortBy(fn (PurchaseOrderItem $line) => ($line->purchaseOrder?->issued_on?->toDateString() ?? '').'-'.$line->id)
            ->map(fn (PurchaseOrderItem $line) => [
                'label' => $line->purchaseOrder?->issued_on?->format('M Y') ?? '',
                'price' => (float) $line->unit_price,
            ])
            ->values()
            ->all();

        $bestSupplier = $supplierBreakdown->first();

        // Which projects actually ordered this item (via the PO lines' allocations).
        $lineIds = $lines->pluck('id');
        $requestedProjects = PurchaseOrderItemAllocation::query()
            ->whereIn('purchase_order_item_id', $lineIds)
            ->with('projectItem.project:id,title,reference_no')
            ->get()
            ->map(fn ($allocation) => $allocation->projectItem?->project)
            ->filter()
            ->unique('id')
            ->values();

        return view('ItemCatalog.show', compact(
            'item',
            'stats',
            'supplierBreakdown',
            'totalSpendAll',
            'history',
            'chartPoints',
            'bestSupplier',
            'requestedProjects',
        ));
    }

    /**
     * All fully-received PO lines (line fully covered by receipts), eager
     * loaded for aggregation. Optionally filtered by issue date range.
     *
     * @return Collection<int, PurchaseOrderItem>
     */
    private function purchasedLines(?Carbon $from, ?Carbon $to): Collection
    {
        $lines = PurchaseOrderItem::query()
            ->with(['item.category', 'purchaseOrder.supplier', 'receipts'])
            ->whereHas('purchaseOrder', function ($query) use ($from, $to) {
                if ($from) {
                    $query->whereDate('issued_on', '>=', $from->toDateString());
                }
                if ($to) {
                    $query->whereDate('issued_on', '<=', $to->toDateString());
                }
            })
            ->get();

        // A line counts as bought only when fully received.
        return $lines
            ->filter(fn (PurchaseOrderItem $line) => (int) $line->receipts->sum('received_qty') >= (int) $line->qty)
            ->values();
    }

    /**
     * Aggregate raw lines by item.
     *
     * @return Collection<int, array>
     */
    private function aggregateLines(Collection $lines): Collection
    {
        return $lines
            ->groupBy(fn (PurchaseOrderItem $line) => $line->item_id)
            ->map(function (Collection $group, $itemId) {
                $item = $group->first()->item;
                $supplierIds = $group->map(fn ($line) => $line->purchaseOrder?->supplier_id)->filter()->unique()->values();
                $qty = (int) $group->sum('qty');
                $spend = (float) $group->sum(fn ($line) => (float) $line->unit_price * (int) $line->qty);
                $bestPrice = (float) $group->min('unit_price');
                $last = $group->sortByDesc('id')->first();
                $lastOn = $group->map(fn (PurchaseOrderItem $line) => $line->purchaseOrder?->issued_on)->filter()->sort()->last();
                $lastPo = $last?->purchaseOrder;

                return [
                    'id' => (int) $itemId,
                    'name' => $item?->name ?? 'Item',
                    'unit' => $item?->unit ?? '',
                    'category' => $item?->category?->name ?? 'Uncategorised',
                    'orders' => $group->count(),
                    'qty' => $qty,
                    'spend' => $spend,
                    'avg_price' => $qty > 0 ? round($spend / $qty, 2) : 0.0,
                    'best_price' => $bestPrice,
                    'last_price' => (float) ($last?->unit_price ?? 0),
                    'last_supplier' => $last?->purchaseOrder?->supplier?->name ?? 'Supplier removed',
                    'last_po' => $lastPo?->po_number,
                    'last_issued_on' => $lastOn,
                    'supplier_ids' => $supplierIds->map(fn ($id) => (int) $id)->all(),
                ];
            })
            ->values();
    }

    private function dateOrNull(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Exception) {
            return null;
        }
    }
}
