<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ProjectItem;
use App\Models\QuotationItem;
use Illuminate\Support\Carbon;

class PriceIndexController extends Controller
{
    /**
     * Distinct line colours for the dynamically discovered suppliers.
     */
    private const SUPPLIER_COLORS = [
        '#2a7a94', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6',
        '#ef4444', '#14b8a6', '#f97316', '#6366f1', '#84cc16',
    ];

    /**
     * Number of days before expiry that counts as "expires soon".
     */
    private const EXPIRY_SOON_DAYS = 30;

    /**
     * Show the price index built from submitted quotation lines.
     */
    public function index()
    {
        // Requested quantities and the projects that requested each item.
        $projectRows = ProjectItem::query()
            ->join('projects', 'projects.id', '=', 'project_items.project_id')
            ->whereNull('projects.deleted_at')
            ->select(['project_items.item_id', 'project_items.quantity', 'projects.title'])
            ->get();

        $requestedByItem = [];
        $projectsByItem = [];

        foreach ($projectRows as $row) {
            $requestedByItem[$row->item_id] = ($requestedByItem[$row->item_id] ?? 0) + (int) $row->quantity;
            $projectsByItem[$row->item_id][$row->title] = true;
        }

        // Every priced line, oldest first so the last row per supplier/month wins.
        $quoteRows = QuotationItem::query()
            ->join('quotations', 'quotations.id', '=', 'quotation_items.quotation_id')
            ->join('suppliers', 'suppliers.id', '=', 'quotations.supplier_id')
            ->join('preplist_items', 'preplist_items.id', '=', 'quotation_items.preplist_item_id')
            ->where('quotation_items.unit_price', '>', 0)
            ->select([
                'quotation_items.id',
                'quotation_items.unit_price',
                'quotation_items.brand',
                'quotation_items.expiry',
                'quotation_items.quoted_at',
                'quotations.id as quotation_id',
                'quotations.generated_at',
                'suppliers.id as supplier_id',
                'suppliers.name as supplier_name',
                'preplist_items.item_id',
            ])
            ->get()
            ->sortBy(fn ($row) => ($row->quoted_at ?? $row->generated_at ?? '')
                .'|'.str_pad((string) $row->id, 12, '0', STR_PAD_LEFT))
            ->values();

        $quotesByItem = $quoteRows->groupBy('item_id');

        // Suppliers that have quoted at least once, in a stable order.
        $supplierNames = $quoteRows->pluck('supplier_name', 'supplier_id')->unique()->sort();
        $suppliers = [];

        foreach ($supplierNames as $supplierId => $supplierName) {
            $suppliers[$supplierId] = [
                'name' => $supplierName,
                'short' => $supplierName,
                'color' => self::SUPPLIER_COLORS[count($suppliers) % count(self::SUPPLIER_COLORS)],
            ];
        }

        $catalogItems = Item::query()
            ->with('category:id,name')
            ->orderBy('name')
            ->get()
            ->filter(fn (Item $item) => isset($requestedByItem[$item->id]) || $quotesByItem->has($item->id))
            ->values();

        $items = [];
        $scorecard = array_fill_keys(
            array_keys($suppliers),
            ['quotes' => 0, 'compared' => 0, 'wins' => 0, 'premium_sum' => 0.0],
        );

        $totalSavings = 0.0;
        $spreadSum = 0.0;
        $spreadCount = 0;
        $itemsWithQuotes = 0;
        $totalQuoteLines = 0;

        foreach ($catalogItems as $catalogItem) {
            $rows = $quotesByItem->get($catalogItem->id, collect());
            $requested = $requestedByItem[$catalogItem->id] ?? 0;

            // Comparison uses one current price per supplier: the latest line.
            $quotes = $rows
                ->groupBy('supplier_id')
                ->map(fn ($group) => $group->last())
                ->map(function ($row) use ($suppliers) {
                    $quotedAt = $row->quoted_at ?? $row->generated_at;
                    $expiry = $row->expiry ? Carbon::parse($row->expiry)->startOfDay() : null;
                    $expiryState = null;

                    if ($expiry !== null) {
                        if ($expiry->lt(Carbon::today())) {
                            $expiryState = 'expired';
                        } elseif ($expiry->lte(Carbon::today()->addDays(self::EXPIRY_SOON_DAYS))) {
                            $expiryState = 'soon';
                        }
                    }

                    return [
                        'supplier' => (int) $row->supplier_id,
                        'name' => $suppliers[$row->supplier_id]['name'] ?? $row->supplier_name,
                        'short' => $suppliers[$row->supplier_id]['short'] ?? $row->supplier_name,
                        'color' => $suppliers[$row->supplier_id]['color'] ?? '#9ca3af',
                        'price' => round((float) $row->unit_price, 2),
                        'brand' => $row->brand,
                        'expiry' => $expiry?->format('M j, Y'),
                        'expiry_state' => $expiryState,
                        'quoted_at' => $quotedAt ? Carbon::parse($quotedAt)->format('M j, Y') : '—',
                        'quotation_id' => (int) $row->quotation_id,
                    ];
                })
                ->sortBy('price')
                ->values();

            $prices = $quotes->pluck('price');
            $count = $prices->count();
            $best = $count ? $prices->min() : null;
            $max = $count ? $prices->max() : null;
            $avg = $count ? $prices->avg() : null;

            $bestSupplier = null;
            if ($count > 0) {
                $bestQuote = $quotes->first(fn ($quote) => abs($quote['price'] - $best) < 0.0001);
                $bestSupplier = $bestQuote['supplier'] ?? null;
            }

            $spread = ($count > 1 && $best > 0) ? (($max - $best) / $best) * 100 : null;
            $itemSavings = ($count > 1) ? ($avg - $best) * $requested : 0.0;

            if ($count > 0) {
                $itemsWithQuotes++;
                $totalQuoteLines += $count;
            }

            if ($spread !== null) {
                $spreadSum += $spread;
                $spreadCount++;
            }

            $totalSavings += $itemSavings;

            foreach ($quotes as $quote) {
                $supplierId = $quote['supplier'];
                $scorecard[$supplierId]['quotes']++;

                if ($count > 1) {
                    $scorecard[$supplierId]['compared']++;

                    if ($best > 0) {
                        $scorecard[$supplierId]['premium_sum'] += (($quote['price'] - $best) / $best) * 100;
                    }

                    if (abs($quote['price'] - $best) < 0.0001) {
                        $scorecard[$supplierId]['wins']++;
                    }
                }
            }

            // Full quote history, reduced to one price per supplier per month.
            $history = $rows
                ->groupBy(function ($row) {
                    $date = $row->quoted_at ?? $row->generated_at;

                    return $row->supplier_id.'|'.($date ? Carbon::parse($date)->format('Y-m') : 'unknown');
                })
                ->map(function ($group) {
                    $row = $group->last();
                    $date = $row->quoted_at ?? $row->generated_at;

                    return [
                        'supplier' => (int) $row->supplier_id,
                        'month' => $date ? Carbon::parse($date)->format('Y-m') : null,
                        'price' => round((float) $row->unit_price, 2),
                    ];
                })
                ->filter(fn ($point) => $point['month'] !== null)
                ->sortBy('month')
                ->values();

            $monthlyBest = $history
                ->groupBy('month')
                ->map(fn ($group) => $group->min('price'))
                ->sortKeys()
                ->slice(-6)
                ->values()
                ->all();

            $projects = array_keys($projectsByItem[$catalogItem->id] ?? []);
            sort($projects);

            $items[] = [
                'id' => $catalogItem->id,
                'name' => $catalogItem->name,
                'unit' => $catalogItem->unit,
                'category' => $catalogItem->category?->name ?? 'Uncategorised',
                'requested' => $requested,
                'projects' => $projects,
                'quotes' => $quotes->all(),
                'history' => $history->all(),
                'sparkline' => $this->sparkline($monthlyBest),
                'stats' => [
                    'count' => $count,
                    'best' => $best,
                    'max' => $max,
                    'avg' => $avg,
                    'spread' => $spread,
                    'savings' => $itemSavings,
                    'best_supplier' => $bestSupplier,
                ],
            ];
        }

        $priceData = array_map(function (array $item) {
            return [
                'id' => $item['id'],
                'name' => $item['name'],
                'unit' => $item['unit'],
                'category' => $item['category'],
                'requested' => $item['requested'],
                'projects' => $item['projects'],
                'best' => $item['stats']['best'],
                'avg' => $item['stats']['avg'],
                'count' => $item['stats']['count'],
                'spread' => $item['stats']['spread'],
                'savings' => $item['stats']['savings'],
                'quotes' => $item['quotes'],
                'history' => $item['history'],
            ];
        }, $items);

        $avgSpread = $spreadCount ? $spreadSum / $spreadCount : 0;
        $activeSuppliers = count(array_filter($scorecard, fn ($row) => $row['quotes'] > 0));

        return view('Priceindex.index', compact(
            'items',
            'suppliers',
            'scorecard',
            'priceData',
            'totalSavings',
            'itemsWithQuotes',
            'totalQuoteLines',
            'avgSpread',
            'spreadCount',
            'activeSuppliers',
        ));
    }

    /**
     * Build a 64x18 polyline from up to six monthly best prices.
     *
     * @param  array<int, float|int>  $values
     */
    private function sparkline(array $values): ?string
    {
        $count = count($values);

        if ($count < 2) {
            return null;
        }

        $min = min($values);
        $max = max($values);
        $range = ($max - $min) ?: 1;
        $points = [];

        foreach ($values as $index => $value) {
            $x = ($index / ($count - 1)) * 64;
            $y = 18 - (($value - $min) / $range) * 14;
            $points[] = round($x, 1).','.round($y, 1);
        }

        return implode(' ', $points);
    }
}
