<?php

namespace App\Http\Controllers;

use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Pagination;
use App\Support\QuotationWorkbookParser;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class QuotationController extends Controller
{
    /**
     * Convert a unique-violation into a validation error for the user.
     */
    private function throwIfUniqueViolation(QueryException $exception): never
    {
        if (str_contains($exception->getMessage(), 'UNIQUE') || str_contains($exception->getMessage(), 'unique')) {
            throw ValidationException::withMessages([
                'document' => 'This quotation was already submitted in another request. Please refresh the page.',
            ]);
        }

        throw $exception;
    }

    /**
     * Show the quotation tracker using real database records.
     * Paginates one prep list group per page instead of every RFQ in one page.
     * Server-side search and filters compose with per_page; pagination links
     * preserve them via withQueryString().
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $supplier = trim((string) $request->query('supplier', ''));
        $createdBy = trim((string) $request->query('created_by', ''));
        $status = trim((string) $request->query('status', ''));
        $perPage = Pagination::perPage($request, 5);

        $statusAllowed = in_array($status, ['quoted', 'pending'], true) ? $status : '';

        $base = Quotation::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('rfq_number', 'like', '%'.$search.'%')
                        ->orWhereHas('preplist', function ($query) use ($search) {
                            $query->where('name', 'like', '%'.$search.'%')
                                ->orWhere('reference_no', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('supplier', function ($query) use ($search) {
                            $query->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($supplier !== '' && ctype_digit($supplier), function ($query) use ($supplier) {
                $query->where('supplier_id', (int) $supplier);
            })
            ->when($createdBy !== '' && ctype_digit($createdBy), function ($query) use ($createdBy) {
                $query->where('created_by', (int) $createdBy);
            })
            ->when($statusAllowed !== '', function ($query) use ($statusAllowed) {
                if ($statusAllowed === 'quoted') {
                    $query->whereHas('items', fn ($query) => $query->whereNotNull('unit_price'));
                } else {
                    $query->whereDoesntHave('items', fn ($query) => $query->whereNotNull('unit_price'));
                }
            });

        // A prep list group shows when at least one of its quotations matches;
        // inside the group only the matching quotations are rendered.
        $groups = (clone $base)
            ->select('preplist_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('preplist_id')
            ->orderByDesc('latest_id')
            ->paginate($perPage)
            ->withQueryString();

        $pageIds = collect($groups->items())->pluck('preplist_id')->map(fn ($id) => (int) $id);

        $quotations = $pageIds->isEmpty()
            ? collect()
            : (clone $base)
                ->with(['preplist.items', 'supplier', 'items'])
                ->whereIn('preplist_id', $pageIds)
                ->orderByDesc('id')
                ->get()
                ->map(function (Quotation $quotation) {
                    $total = $quotation->preplist?->items->count() ?? 0;
                    $quoted = $quotation->items
                        ->filter(fn ($item) => $item->unit_price !== null)
                        ->count();

                    return [
                        'id' => $quotation->id,
                        'preplist_id' => (int) $quotation->preplist_id,
                        'prep_list' => $quotation->preplist?->name ?? 'Prep list removed',
                        'ref' => $quotation->preplist?->reference_no ?? 'Prep list removed',
                        'supplier' => $quotation->supplier?->name ?? 'Supplier removed',
                        'rfq_number' => $quotation->rfq_number,
                        'quoted' => $quoted,
                        'total' => $total,
                        'status' => $quoted > 0 ? 'Quoted' : 'Awaiting quote',
                    ];
                })->values();

        $preplistGroups = [];

        foreach ($quotations as $quotation) {
            $preplistGroups[$quotation['preplist_id']][] = $quotation;
        }

        // Quotation creation order matches the paginated prep list order.
        $orderedGroups = collect($pageIds)
            ->mapWithKeys(fn (int $id) => [$id => $preplistGroups[$id] ?? null])
            ->filter()
            ->map(function (array $groupQuotations, int $id) {
                return [
                    'preplist_id' => $id,
                    'name' => $groupQuotations[0]['prep_list'],
                    'ref' => $groupQuotations[0]['ref'],
                    'quotations' => $groupQuotations,
                ];
            })->values();

        $suppliers = Supplier::query()
            ->whereIn('id', Quotation::query()->select('supplier_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $developers = User::query()
            ->whereIn('id', Quotation::query()->select('created_by'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $activeFilters = collect([
            $supplier !== '' ? $supplier : null,
            $createdBy !== '' ? $createdBy : null,
            $statusAllowed !== '' ? $statusAllowed : null,
        ])->filter()->count();
        $filtersActive = $search !== '' || $activeFilters > 0;

        return view('Quotation.index', [
            'preplistGroups' => $orderedGroups,
            'groups' => $groups,
            'perPage' => $perPage,
            'search' => $search,
            'supplier' => $supplier,
            'createdBy' => $createdBy,
            'status' => $statusAllowed,
            'suppliers' => $suppliers,
            'developers' => $developers,
            'activeFilters' => $activeFilters,
            'filtersActive' => $filtersActive,
        ]);
    }

    /**
     * Full preplist comparison page: every supplier's quote for every item
     * side by side, plus the suggested supplier per item. Reached by clicking
     * a prep list title in the tracker.
     */
    public function showPreplist(Request $request, Preplist $preplist)
    {
        $preplist->load(['items.item', 'items.sources.projectItem.project']);

        $quotations = Quotation::query()
            ->with(['supplier', 'items.preplistItem'])
            ->where('preplist_id', $preplist->id)
            ->orderBy('id')
            ->get();

        $preplistItems = $preplist->items->values();

        // Per-supplier column data, keyed by preplist item id so missing lines
        // can render as "-".
        $sections = $quotations
            ->map(function (Quotation $quotation) use ($preplistItems) {
                $line = $quotation->items->keyBy('preplist_item_id');

                return [
                    'quotation' => $quotation,
                    'supplier' => $quotation->supplier?->name ?? 'Supplier removed',
                    'rfq_number' => $quotation->rfq_number,
                    'status' => ($quotation->items->contains(fn ($qi) => $qi->unit_price !== null)) ? 'Quoted' : 'Awaiting quote',
                    'lines' => $preplistItems->mapWithKeys(function (PreplistItem $preplistItem) use ($line) {
                        $lineItem = $line->get($preplistItem->id);

                        return [
                            $preplistItem->id => $lineItem === null
                                ? null
                                : [
                                    'id' => $lineItem->id,
                                    'preplist_item_id' => $preplistItem->id,
                                    'unit_price' => $lineItem->unit_price !== null ? (float) $lineItem->unit_price : null,
                                    'brand' => $lineItem->brand,
                                    'expiry' => $lineItem->expiry?->toDateString(),
                                    'expiry_label' => $lineItem->expiry?->format('M j, Y'),
                                    'min_remaining_months' => (int) $lineItem->min_remaining_months,
                                    'on_po' => \App\Models\PurchaseOrderItem::query()->where('quotation_item_id', $lineItem->id)->exists(),
                                ],
                        ];
                    })->all(),
                ];
            })
            ->values();

        // Suggestion: cheapest unit price for each item across all priced
        // lines for this preplist. User overrides by picking in the columns.
        $suggestions = $preplistItems->map(function (PreplistItem $preplistItem) use ($sections) {
            $candidates = collect();

            foreach ($sections as $section) {
                $line = $section['lines'][$preplistItem->id] ?? null;

                if ($line !== null && $line['unit_price'] !== null) {
                    $candidates->push([
                        'supplier' => $section['supplier'],
                        'rfq_number' => $section['rfq_number'],
                        'quotation_id' => $section['quotation']->id,
                        'quote' => $line,
                    ]);
                }
            }

            $candidates = $candidates->sortBy('quote.unit_price')->values();
            $winner = $candidates->first();

            // Annotation: raw price win vs freshest stated expiry, so the buyer
            // sees the trade-off even though the suggestion stays price-only.
            $expiryNote = null;
            $withExpiry = $candidates->filter(fn ($candidate) => $candidate['quote']['expiry'] !== null);

            if ($winner !== null && $withExpiry->isNotEmpty()) {
                $freshest = $withExpiry->sortByDesc('quote.expiry')->first();
                $winnerExpiry = $winner['quote']['expiry'];

                if ($freshest['supplier'] === $winner['supplier'] && $winnerExpiry === $freshest['quote']['expiry']) {
                    $expiryNote = 'Best price also owns the freshest expiry.';
                } elseif ($winnerExpiry === null) {
                    $expiryNote = $winner['supplier'].' states no expiry; '.$freshest['supplier']
                        .' has '.date('M j, Y', strtotime($freshest['quote']['expiry']));
                } else {
                    $extraDays = (int) round(abs((strtotime($freshest['quote']['expiry']) - strtotime($winnerExpiry)) / 86400));
                    $priceDelta = (float) $freshest['quote']['unit_price'] - (float) $winner['quote']['unit_price'];

                    $expiryNote = $freshest['supplier'].' lasts '.$extraDays.' days longer'
                        .($priceDelta > 0.004 ? ' for ₱'.number_format($priceDelta, 2).' more' : '');
                }
            }

            return [
                'preplist_item' => $preplistItem,
                'name' => $preplistItem->item?->name ?? 'Item',
                'unit' => $preplistItem->item?->unit ?? '',
                'requested' => $preplistItem->remainingQuantity(),
                'min_remaining_months' => (int) $preplistItem->min_shelf_life_months,
                'winner' => $winner,
                'expiry_note' => $expiryNote,
            ];
        })->values();

        // Per-item highlight map used to color the best price (green) and
        // best expiry (teal) inside the supplier columns.
        $highlights = $preplistItems->mapWithKeys(function (PreplistItem $preplistItem) use ($sections) {
            $lines = collect($sections)
                ->map(fn ($section) => $section['lines'][$preplistItem->id] ?? null)
                ->filter(fn ($line) => $line !== null && $line['unit_price'] !== null);

            $bestPrice = $lines->min('unit_price');
            $bestExpiry = $lines
                ->filter(fn ($line) => $line['expiry'] !== null)
                ->max('expiry');

            return [
                $preplistItem->id => [
                    'best_price' => $bestPrice !== null ? (float) $bestPrice : null,
                    'best_expiry' => $bestExpiry,
                ],
            ];
        })->all();

        $cartItems = $request->user()?->poCartItems()
            ->with(['quotationItem.quotation.supplier'])
            ->get();

        $cartQuoteItemIds = [];
        $cartPreplistIds = [];
        $cartPreplistSupplier = [];
        $cartLineIdByQuoteItem = [];

        foreach ($cartItems as $cartItem) {
            $cartQuoteItemIds[] = $cartItem->quotation_item_id;
            $cartLineIdByQuoteItem[$cartItem->quotation_item_id] = $cartItem->id;

            $preplistItemId = $cartItem->quotationItem?->preplist_item_id;
            if ($preplistItemId !== null) {
                $cartPreplistIds[] = (int) $preplistItemId;
                $cartPreplistSupplier[$preplistItemId] = $cartItem->quotationItem?->quotation?->supplier?->name ?? null;
            }
        }

        return view('Quotation.preplist', [
            'preplist' => $preplist,
            'items' => $preplistItems,
            'sections' => $sections,
            'suggestions' => $suggestions,
            'highlights' => $highlights,
            'cartQuoteItemIds' => $cartQuoteItemIds,
            'cartPreplistIds' => $cartPreplistIds,
            'cartPreplistSupplier' => $cartPreplistSupplier,
            'cartLineIdByQuoteItem' => $cartLineIdByQuoteItem,
        ]);
    }

    /**
     * Show one quotation and the per-item quote breakdown.
     */
    public function show(Quotation $quotation)
    {
        $quotation->load([
            'preplist.items.item',
            'preplist.items.sources.projectItem.project',
            'supplier',
            'items.preplistItem',
        ]);

        $preplist = $quotation->preplist;
        $items = $preplist?->items
            ? $preplist->items
                ->map(function (PreplistItem $preplistItem) {
                    $unit = $preplistItem->item?->unit ?? '';
                    $requiredShelfLife = (int) $preplistItem->min_shelf_life_months;
                    $requested = $preplistItem->remainingQuantity();
                    $allocations = $preplistItem->sources->map(function ($source) use ($unit, $requiredShelfLife) {
                        $project = $source->projectItem?->project;

                        // Fall back to the award date plus the project's delivery
                        // period when the due date was never persisted.
                        $deliveryDueDate = $project?->delivery_due_date
                            ?? ($project?->date_awarded && $project?->delivery_days
                                ? $project->date_awarded->copy()->addDays($project->delivery_days)
                                : null);

                        return [
                            'project' => $project?->title ?? 'Project removed',
                            'reference' => $project?->reference_no ?? '—',
                            'qty' => $source->projectItem?->remainingQuantity() ?? 0,
                            'unit' => $unit,
                            'delivery_due_date' => $deliveryDueDate?->toDateString(),
                            'required_expiry_date' => $deliveryDueDate
                                ? $deliveryDueDate->copy()->addMonths($requiredShelfLife)->toDateString()
                                : null,
                        ];
                    });

                    return [
                        'key' => (string) $preplistItem->id,
                        'name' => $preplistItem->item?->name ?? 'Item',
                        'requested' => (int) $requested,
                        'unit' => $unit,
                        'min_remaining_months' => $requiredShelfLife,
                        'allocations' => $allocations,
                    ];
                })
                ->values()
            : collect();

        $quotationItems = $quotation->items->keyBy('preplist_item_id');

        // Why removing the supplier's quotation would be refused — shown by
        // the Remove quotation modal when non-null.
        $removeBlockedReason = session('documentRemoveBlocked') ?? $this->blockedRemoveReason($quotation);

        return view('Quotation.show', [
            'quotation' => $quotation,
            'items' => $items,
            'quotationItems' => $quotationItems,
            'removeBlockedReason' => $removeBlockedReason,
        ]);
    }

    /**
     * The reason removing the supplier's quotation document would be refused,
     * or null when removal is allowed.
     *
     * Removing the document deletes the quoted line items together with it.
     * Once a purchase order was created from this quotation, its lines
     * reference those items, so deleting them violates a foreign key.
     */
    private function blockedRemoveReason(Quotation $quotation): ?string
    {
        $itemIds = $quotation->items()->pluck('id');

        if ($itemIds->isEmpty()) {
            return null;
        }

        $poNumbers = PurchaseOrder::query()
            ->whereHas('items', fn ($query) => $query->whereIn('quotation_item_id', $itemIds))
            ->pluck('po_number')
            ->unique()
            ->values();

        if ($poNumbers->isEmpty()) {
            return null;
        }

        return 'A purchase order was already created from this quotation ('
            .$poNumbers->join(', ')
            .'). Its quoted prices and expiry data are already in use, so the quotation file cannot be removed until the purchase order is deleted first.';
    }

    public function storeDocument(Request $request, Quotation $quotation)
    {
        $current = md5(
            ($quotation->document_path ?? '').'|'
            .($quotation->document_name ?? '').'|'
            .((int) ($quotation->document_size ?? 0))
        );

        $expectedFingerprint = $request->input('expected_document_fingerprint');

        if (is_string($expectedFingerprint) && $expectedFingerprint !== '' && $expectedFingerprint !== $current) {
            throw ValidationException::withMessages([
                'document' => 'Someone else already replaced the quotation file since this page was loaded. Refresh the page and check which document is current before uploading again.',
            ]);
        }

        $request->validate([
            'document' => ['required', 'file', 'extensions:xlsx', 'max:10240'],
        ], [
            'document.required' => 'Choose the supplier’s completed quotation file to upload.',
            'document.extensions' => 'Upload the completed quotation as an .xlsx file.',
        ]);

        $file = $request->file('document');

        try {
            $rows = QuotationWorkbookParser::parse($file->getRealPath());
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'document' => $exception->getMessage(),
            ]);
        }

        $quotation->load(['preplist.items.item']);

        if ($quotation->preplist === null) {
            throw ValidationException::withMessages([
                'document' => 'This quotation’s prep list no longer exists; the file cannot be processed.',
            ]);
        }

        $itemsByName = $quotation->preplist->items
            ->keyBy(fn (PreplistItem $preplistItem) => mb_strtolower(trim($preplistItem->item?->name ?? 'Item')));

        $updates = [];

        foreach ($rows as $row) {
            $key = mb_strtolower(trim($row['description']));
            $preplistItem = $itemsByName->get($key);

            if ($preplistItem === null) {
                throw ValidationException::withMessages([
                    'document' => 'The uploaded file does not match this prep list: "'.$row['description'].'".',
                ]);
            }

            if ($row['unit_price'] !== null && $row['unit_price'] <= 0) {
                throw ValidationException::withMessages([
                    'document' => 'The unit price for "'.$row['description'].'" must be greater than zero.',
                ]);
            }

            $updates[] = [
                'preplist_item_id' => $preplistItem->id,
                'min_remaining_months' => (int) $preplistItem->min_shelf_life_months,
                'brand' => $row['brand'],
                'expiry' => $row['expiry'],
                'unit_price' => $row['unit_price'],
            ];
        }

        try {
            DB::transaction(function () use ($quotation, $updates) {
                foreach ($updates as $data) {
                    // A blank price means the supplier did not quote that item;
                    // make sure no stale "quoted" row remains for it.
                    if ($data['unit_price'] === null) {
                        QuotationItem::query()
                            ->where('quotation_id', $quotation->id)
                            ->where('preplist_item_id', $data['preplist_item_id'])
                            ->delete();

                        continue;
                    }

                    QuotationItem::query()->updateOrCreate(
                        [
                            'quotation_id' => $quotation->id,
                            'preplist_item_id' => $data['preplist_item_id'],
                        ],
                        [
                            'min_remaining_months' => $data['min_remaining_months'],
                            'brand' => $data['brand'],
                            'expiry' => $data['expiry'],
                            'unit_price' => $data['unit_price'],
                            'quoted_at' => now(),
                        ],
                    );
                }

                ActivityLogger::log('Edited', 'Quotation', $quotation->rfq_number ?: 'Quotation #'.$quotation->id, $quotation);
            });
        } catch (QueryException $exception) {
            $this->throwIfUniqueViolation($exception);
        }

        $path = 'quotations/'.($quotation->rfq_number ?: 'quotation-'.$quotation->id).'.xlsx';
        $oldPath = $quotation->document_path;

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        $quotation->update([
            'document_path' => $path,
            'document_name' => $file->getClientOriginalName(),
            'document_size' => $file->getSize(),
        ]);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return redirect()
            ->route('quotation.show', ['quotation' => $quotation->id])
            ->with('status', 'Quotation submitted successfully.');
    }

    /**
     * Download the supplier's quotation document.
     */
    public function download(Quotation $quotation)
    {
        abort_unless(
            $quotation->document_path && Storage::disk('local')->exists($quotation->document_path),
            404,
        );

        return Storage::disk('local')->download(
            $quotation->document_path,
            $quotation->document_name ?: 'quotation.xlsx',
        );
    }

    /**
     * Remove the supplier's uploaded quotation document and clear the
     * quoted prices so the quotation returns to "Awaiting quote".
     */
    public function destroyDocument(Quotation $quotation)
    {
        // Refuse to remove the supplier's quotation when its prices are
        // already referenced by a purchase order — that is the FK the
        // delete would otherwise hit with a 500.
        $blocked = $this->blockedRemoveReason($quotation);

        if ($blocked !== null) {
            return redirect()
                ->route('quotation.show', ['quotation' => $quotation->id])
                ->with('documentRemoveBlocked', $blocked);
        }

        try {
            DB::transaction(function () use ($quotation) {
                if ($quotation->document_path) {
                    Storage::disk('local')->delete($quotation->document_path);
                }

                $quotation->items()->delete();

                $quotation->update([
                    'document_path' => null,
                    'document_name' => null,
                    'document_size' => null,
                ]);

                ActivityLogger::log('Deleted', 'Quotation', ($quotation->rfq_number ?: 'Quotation #'.$quotation->id).' (document)', $quotation);
            });
        } catch (QueryException $exception) {
            // Defensive net for an unexpected reference we did not predict —
            // surface the same friendly reason instead of a 500.
            if (str_contains($exception->getMessage(), '23000') || str_contains($exception->getMessage(), 'Integrity constraint')) {
                return redirect()
                    ->route('quotation.show', ['quotation' => $quotation->id])
                    ->with('documentRemoveBlocked', $this->blockedRemoveReason($quotation)
                        ?? 'This quotation is already in use by another record and cannot be removed.');
            }

            throw $exception;
        }

        return redirect()
            ->route('quotation.show', ['quotation' => $quotation->id])
            ->with('status', 'Quotation removed. The supplier can now submit a new file.');
    }

    /**
     * Delete a quotation record. Only quotations that are still awaiting a
     * quote can be deleted; quoted entries must have their uploaded file
     * removed first.
     */
    public function destroy(Quotation $quotation)
    {
        if ($quotation->quotedItems()->exists()) {
            return redirect()
                ->route('quotation')
                ->withErrors([
                    'quotation' => 'This quotation has already been answered. Remove the supplier’s quotation first before deleting the entry.',
                ]);
        }

        if ($quotation->document_path) {
            Storage::disk('local')->delete($quotation->document_path);
        }

        DB::transaction(function () use ($quotation) {
            $quotation->delete();

            ActivityLogger::log('Deleted', 'Quotation', $quotation->rfq_number ?: 'Quotation #'.$quotation->id, $quotation);
        });

        return redirect()
            ->route('quotation')
            ->with('status', 'Quotation deleted successfully.');
    }
}
