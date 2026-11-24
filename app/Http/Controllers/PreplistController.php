<?php

namespace App\Http\Controllers;

use App\Models\NumberSequence;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\PreplistItemSource;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\Pagination;
use App\Support\PurchaseOrderCreator;
use App\Support\PurchaseOrderWorkbook;
use App\Support\RfqQuotationWorkbook;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class PreplistController extends Controller
{
    /**
     * Allowed sort keys for the index list. Each is a column slug plus a
     * direction suffix (_asc / _desc); anything else falls back to date_desc.
     */
    private const ALLOWED_SORTS = [
        'name_asc',
        'name_desc',
        'created_by_asc',
        'created_by_desc',
        'projects_asc',
        'projects_desc',
        'items_asc',
        'items_desc',
        'date_asc',
        'date_desc',
    ];

    /**
     * Item categories accepted as filters and produced by the RFQ/flow.
     *
     * @var list<string>
     */
    private const ITEM_CATEGORIES = [
        'drugs and medicines',
        'supplies and equipments',
    ];

    /**
     * Show the prep lists list using real database records.
     * Server-side search, filters, sort and pagination.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $createdBy = trim((string) $request->query('created_by', ''));
        $category = trim((string) $request->query('category', ''));
        $quote = trim((string) $request->query('quote', ''));
        $perPage = Pagination::perPage($request, 10);
        $requestedSort = (string) $request->query('sort', 'date_desc');
        $sort = in_array($requestedSort, self::ALLOWED_SORTS, true) ? $requestedSort : 'date_desc';
        $direction = str_ends_with($sort, '_asc') ? 'asc' : 'desc';
        $sortColumn = substr($sort, 0, (int) strrpos($sort, '_'));

        $categoryAllowed = $category === '' ? null : (in_array($category, self::ITEM_CATEGORIES, true) ? $category : null);

        $query = Preplist::query()
            ->with(['creator', 'items.sources.projectItem.project', 'quotations.items'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('reference_no', 'like', '%'.$search.'%')
                        ->orWhereHas('creator', function ($query) use ($search) {
                            $query->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($createdBy !== '' && ctype_digit($createdBy), function ($query) use ($createdBy) {
                $query->where('created_by', (int) $createdBy);
            })
            ->when($categoryAllowed !== null, function ($query) use ($categoryAllowed) {
                $query->whereHas('items.item.category', fn ($query) => $query->where('name', $categoryAllowed));
            })
            ->when(in_array($quote, ['quoted', 'pending'], true), function ($query) use ($quote) {
                $hasQuotes = function ($subQuery) {
                    $subQuery->whereHas('items', fn ($sub) => $sub->whereNotNull('unit_price'));
                };

                if ($quote === 'quoted') {
                    $query->whereHas('quotations', $hasQuotes);
                } else {
                    $query->whereDoesntHave('quotations', $hasQuotes);
                }
            });

        switch ($sortColumn) {
            case 'name':
                $query->orderBy('preplists.name', $direction);
                break;
            case 'created_by':
                $query->orderByRaw('(SELECT name FROM users WHERE users.id = preplists.created_by) '.($direction === 'asc' ? 'ASC' : 'DESC'));
                break;
            case 'projects':
                $query->orderByRaw('(SELECT COUNT(DISTINCT project_items.project_id)'
                    .' FROM preplist_items'
                    .' INNER JOIN preplist_item_sources ON preplist_item_sources.preplist_item_id = preplist_items.id'
                    .' INNER JOIN project_items ON project_items.id = preplist_item_sources.project_item_id'
                    .' WHERE preplist_items.preplist_id = preplists.id) '
                    .($direction === 'asc' ? 'ASC' : 'DESC'));
                break;
            case 'items':
                $query->withCount('items')->orderBy('items_count', $direction);
                break;
            case 'date':
            default:
                $query->orderBy('preplists.created_at', $direction);
                break;
        }

        $preplists = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        // Compute which prep lists are blocked from deletion, using the same
        // progressed-quotation rule as the detail page: a quotation is progressed
        // once it has priced lines, an uploaded document, or a PO reference.
        $pageIds = $preplists->pluck('id');
        $linked = Quotation::query()
            ->whereIn('preplist_id', $pageIds)
            ->withCount(['items as priced_items_count' => fn ($q) => $q->whereNotNull('unit_price')])
            ->get(['id', 'preplist_id', 'document_path']);

        $poRefIds = $linked->isEmpty()
            ? collect()
            : PurchaseOrder::query()
                ->whereIn('quotation_id', $linked->pluck('id'))
                ->distinct()
                ->pluck('quotation_id');

        $poRefLookup = array_flip($poRefIds->all());
        $blockedByPreplistId = [];

        foreach ($linked as $quotation) {
            $blocked = ($quotation->priced_items_count ?? 0) > 0
                || $quotation->document_path !== null
                || isset($poRefLookup[$quotation->id]);

            if ($blocked) {
                $blockedByPreplistId[$quotation->preplist_id] = true;
            }
        }

        $developers = User::query()
            ->whereIn('id', Preplist::query()->select('created_by'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $quoteOptions = ['quoted' => 'Quoted', 'pending' => 'Awaiting quote'];

        $activeFilters = collect([$createdBy !== '' ? $createdBy : null, $categoryAllowed, $quote !== '' ? $quote : null])->filter()->count();
        $filtersActive = $activeFilters > 0;

        return view('Preplist.index', compact('preplists', 'search', 'sort', 'perPage', 'createdBy', 'categoryAllowed', 'quote', 'developers', 'quoteOptions', 'filtersActive', 'activeFilters', 'blockedByPreplistId'));
    }

    /**
     * Show the compile prep list form.
     */
    public function create()
    {
        return view('Preplist.form', [
            'prepList' => null,
            'projects' => $this->projectsWithItems(),
            'selectedSources' => [],
        ]);
    }

    /**
     * Store a compiled prep list and its source items.
     */
    public function store(Request $request)
    {
        $validated = $this->validatePreplist($request);
        $this->assertNoFulfilledSources($validated);
        $this->assertNoAlreadySourcedElsewhere($validated);

        try {
            $preplist = DB::transaction(function () use ($request, $validated) {
                $preplist = Preplist::query()->create([
                    'created_by' => $request->user()->id,
                    'name' => $validated['name'],
                    'reference_no' => $this->nextReferenceNumber(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                $this->syncItems($preplist, $validated);

                ActivityLogger::log('Created', 'Preplist', $preplist->name, $preplist);

                return $preplist;
            });
        } catch (QueryException $exception) {
            $this->throwIfUniqueViolation($exception, 'That combination of items has already been compiled into a prep list.');
        }

        return redirect()
            ->route('preplist.show', ['preplist' => $preplist->id])
            ->with('status', 'Prep list compiled successfully.');
    }

    /**
     * Show a prep list and its grouped source items.
     */
    public function show(Preplist $preplist)
    {
        $preplist->load(['creator', 'items.item', 'items.sources.projectItem.project']);

        // Linked quotations with enough information to render the deletion UI.
        // a quotation is "progressed" once it carries anything supplier-side:
        // an uploaded document, priced lines, or a PO reference.
        $linkedQuotations = Quotation::query()
            ->with('supplier')
            ->withCount(['items as priced_items_count' => fn ($query) => $query->whereNotNull('unit_price')])
            ->where('preplist_id', $preplist->id)
            ->get();

        foreach ($linkedQuotations as $quotation) {
            $quotation->blocked = ($quotation->priced_items_count ?? 0) > 0
                || $quotation->document_path !== null
                || PurchaseOrder::query()->where('quotation_id', $quotation->id)->exists();
        }

        $blockedQuotations = $linkedQuotations->where('blocked', true)->values();
        $quotationCount = $linkedQuotations->count();

        return view('Preplist.show', compact('preplist', 'quotationCount', 'linkedQuotations', 'blockedQuotations'));
    }

    /**
     * Local purchase builder for lines that will not go through quotation.
     * Shows only the remaining (To procure) lines with suggested prices
     * taken from the awarded source prices.
     */
    public function localPurchaseForm(Preplist $preplist)
    {
        $preplist->load(['items.item', 'items.sources.projectItem']);

        $lines = $preplist->items
            ->map(function (PreplistItem $preplistItem) {
                $sources = $preplistItem->sources;
                $awardedPrices = $sources
                    ->map(fn ($source) => $source->projectItem?->awarded_unit_price)
                    ->filter();
                $remaining = $preplistItem->remainingQuantity();

                return [
                    'id' => $preplistItem->id,
                    'name' => $preplistItem->item?->name ?? 'Item',
                    'unit' => $preplistItem->item?->unit ?? 'unit',
                    'remaining' => $remaining,
                    'remaining_pieces' => $preplistItem->remainingPieces(),
                    'box_size' => $preplistItem->boxSize(),
                    'shelf_life' => (int) $preplistItem->min_shelf_life_months,
                    'suggested_price' => $awardedPrices->isNotEmpty() ? (float) $awardedPrices->max() : null,
                    'source_count' => $sources->count(),
                ];
            })
            ->filter(fn (array $line) => $line['remaining'] > 0)
            ->values();

        // Nothing to buy locally — the user only ever sees buyable lines.
        if ($lines->isEmpty()) {
            return redirect()
                ->route('preplist.show', ['preplist' => $preplist->id])
                ->with('status', 'Nothing left to procure on this prep list.');
        }

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get();

        return view('Preplist.local_purchase', [
            'prepList' => $preplist,
            'lines' => $lines,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Raise a draft purchase order directly against a local store. No
     * quotation is required; the order is flagged source=local and follows
     * the same draft → send → receive flow as any other purchase order.
     */
    public function storeLocalPurchase(Request $request, Preplist $preplist)
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'expected_delivery' => ['nullable', 'date'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
            'deliver_to' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.qty' => ['required', 'integer', 'min:1'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ], [
            'selected.required' => 'Select at least one line to buy locally.',
            'selected.min' => 'Select at least one line to buy locally.',
        ]);

        $preplistItemById = $preplist->items()
            ->with('sources.projectItem')
            ->get()
            ->keyBy('id');

        $lines = [];
        $invalid = [];

        foreach ($validated['selected'] as $preplistItemId) {
            $preplistItem = $preplistItemById->get((int) $preplistItemId);
            $qty = (int) ($validated['lines'][$preplistItemId]['qty'] ?? 0);
            $priceStr = trim((string) ($validated['lines'][$preplistItemId]['unit_price'] ?? ''));
            $name = $preplistItem?->item?->name ?? 'Item #'.$preplistItemId;

            if (! $preplistItem) {
                $invalid[] = $name.' (not on this prep list)';

                continue;
            }

            $remaining = $preplistItem->remainingQuantity();

            if ($remaining <= 0) {
                $invalid[] = $name.' (covered by on-hand stock)';

                continue;
            }

            if ($qty > $remaining) {
                $invalid[] = $name.' (quantity '.$qty.' exceeds the remaining '.$remaining.' needed)';

                continue;
            }

            $lines[] = [
                'preplist_item' => $preplistItem,
                'qty' => $qty,
                'unit_price' => $priceStr === '' ? null : $priceStr,
                'min_remaining_months' => (int) $preplistItem->min_shelf_life_months,
                'bought_now' => false,
            ];
        }

        if ($invalid) {
            throw ValidationException::withMessages([
                'local_purchase' => 'Cannot create the order — '.implode('; ', $invalid).'.',
            ]);
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'selected' => 'Select at least one line to buy locally.',
            ]);
        }

        $purchaseOrder = PurchaseOrderCreator::createForLocalPurchase(
            $request->user()->id,
            $lines,
            [
                'supplier_id' => (int) $validated['supplier_id'],
                'expected_delivery' => $validated['expected_delivery'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'deliver_to' => $validated['deliver_to'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        $purchaseOrder->load(['items.item', 'items.allocations']);

        // Generate the Excel workbook now, so it exists right after the PO
        // is created; the user downloads it from the purchase order page.
        Storage::disk('local')->put(
            'po-workbooks/'.$purchaseOrder->po_number.'.xlsx',
            PurchaseOrderWorkbook::build($purchaseOrder, $purchaseOrder->items)
        );

        return redirect()->route('preplist')->with('status', 'Local purchase order '.$purchaseOrder->po_number.' created.');
    }

    /**
     * Show the edit form for a prep list.
     */
    public function edit(Preplist $preplist)
    {
        $preplist->load(['items.sources']);

        $selectedSources = [];

        foreach ($preplist->items as $preplistItem) {
            foreach ($preplistItem->sources as $source) {
                $selectedSources[$source->project_item_id] = $preplistItem;
            }
        }

        return view('Preplist.form', [
            'prepList' => $preplist,
            'projects' => $this->projectsWithItems($preplist),
            'selectedSources' => $selectedSources,
        ]);
    }

    /**
     * Update a prep list and replace its source items.
     */
    public function update(Request $request, Preplist $preplist)
    {
        $validated = $this->validatePreplist($request);
        $this->assertNoFulfilledSources($validated, $preplist);
        $this->assertNoAlreadySourcedElsewhere($validated, $preplist);

        try {
            DB::transaction(function () use ($preplist, $validated) {
                $preplist->update([
                    'name' => $validated['name'],
                    'notes' => $validated['notes'] ?? null,
                ]);

                $this->syncItems($preplist, $validated);

                ActivityLogger::log('Edited', 'Preplist', $preplist->name, $preplist);
            });
        } catch (QueryException $exception) {
            $this->throwIfUniqueViolation($exception, 'That combination of items has already been compiled into a prep list.');
        }

        return redirect()
            ->route('preplist.show', ['preplist' => $preplist->id])
            ->with('status', 'Prep list updated successfully.');
    }

    /**
     * Update only the notes shown on the prep list detail page.
     */
    public function updateNotes(Request $request, Preplist $preplist)
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($preplist, $validated) {
            $preplist->update(['notes' => $validated['notes'] ?? null]);

            ActivityLogger::log('Edited', 'Preplist', $preplist->name, $preplist);
        });

        return redirect()
            ->route('preplist.show', ['preplist' => $preplist->id])
            ->with('status', 'Prep list notes updated successfully.');
    }

    /**
     * Soft-delete a prep list. Hard-deletes every linked quotation that is
     * still unprogressed (no prices, no document, no PO reference). A prep
     * list with at least one progressed quotation cannot be deleted.
     */
    public function destroy(Preplist $preplist)
    {
        $linkedQuotations = Quotation::query()
            ->with('supplier:id,name')
            ->where('preplist_id', $preplist->id)
            ->get();

        $blocking = [];

        foreach ($linkedQuotations as $quotation) {
            $poCount = PurchaseOrder::query()
                ->where('quotation_id', $quotation->id)
                ->count();

            $reasons = array_values(array_filter([
                $quotation->quotedItems()->exists() ? 'has priced items' : null,
                $quotation->document_path ? 'a file was uploaded' : null,
                $poCount > 0 ? "referenced by {$poCount} purchase order".($poCount === 1 ? '' : 's') : null,
            ]));

            if ($reasons !== []) {
                $blocking[] = [
                    'rfq_number' => $quotation->rfq_number ?: 'Quotation #'.$quotation->id,
                    'supplier' => $quotation->supplier?->name ?? 'Supplier removed',
                    'reasons' => implode(', ', $reasons),
                ];
            }
        }

        if ($blocking !== []) {
            $summary = collect($blocking)
                ->map(fn ($block) => $block['rfq_number'].' ('.$block['supplier'].') — '.$block['reasons'])
                ->implode('; ');

            return back()
                ->withErrors(['preplist' => 'This prep list cannot be deleted because it is linked to progressed quotation(s): '.$summary.'.']);
        }

        DB::transaction(function () use ($linkedQuotations, $preplist) {
            foreach ($linkedQuotations as $quotation) {
                if ($quotation->document_path) {
                    Storage::disk('local')->delete($quotation->document_path);
                }

                $quotation->items()->delete();

                ActivityLogger::log(
                    'Deleted',
                    'Quotation',
                    $quotation->rfq_number ?: 'Quotation #'.$quotation->id,
                    $quotation,
                );

                $quotation->delete();
            }

            $preplist->delete();

            ActivityLogger::log('Deleted', 'Preplist', $preplist->name, $preplist);
        });

        return redirect()
            ->route('preplist')
            ->with('status', 'Prep list deleted successfully.');
    }

    /**
     * Show the RFQ generation screen for a prep list.
     */
    public function generateRfq(Preplist $preplist)
    {
        $preplist->load(['items.item', 'items.sources.projectItem.project']);

        $items = $preplist->items
            ->map(function (PreplistItem $preplistItem) {
                return [
                    'key' => (string) $preplistItem->id,
                    'name' => $preplistItem->item?->name ?? 'Item',
                    'total' => $preplistItem->remainingQuantity(),
                    'unit' => $preplistItem->item?->unit ?? '',
                    'shelf_life' => (int) $preplistItem->min_shelf_life_months,
                ];
            })
            ->values();

        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Supplier $supplier) => ['name' => $supplier->name])
            ->values();

        return view('Preplist.generate_rfq', [
            'prepList' => $preplist,
            'items' => $items,
            'suppliers' => $suppliers,
            'initialItemSelections' => [],
            'initialGenSuppliers' => [],
        ]);
    }

    /**
     * Build and download the supplier quotation workbooks for the selected
     * prep list items.
     *
     * The request carries the supplier names to generate for and, per supplier,
     * the prep list item ids that were checked. Quantities are deliberately
     * not included in the generated files; the client-side selection is only
     * used to decide which rows to emit, while names and shelf-life
     * requirements are always read from the database.
     */
    public function generateRfqExcel(Request $request, Preplist $preplist)
    {
        $validated = $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*.supplier' => ['required', 'string', 'max:255'],
            'files.*.items' => ['required', 'array', 'min:1'],
            'files.*.items.*' => ['integer'],
        ], [
            'files.required' => 'Select at least one supplier to generate files.',
            'files.*.items.required' => 'Select at least one item for each supplier whose files should be generated.',
            'files.*.items.min' => 'Select at least one item for each supplier whose files should be generated.',
        ]);

        $preplist->load(['items.item']);

        $itemsById = $preplist->items->keyBy('id');
        $supplierNames = collect($validated['files'])->pluck('supplier')->unique()->values();

        $suppliers = Supplier::query()
            ->whereIn('name', $supplierNames)
            ->get()
            ->keyBy('name');

        if ($suppliers->count() !== $supplierNames->count()) {
            throw ValidationException::withMessages([
                'files' => 'One or more selected suppliers no longer exist.',
            ]);
        }

        foreach ($validated['files'] as $file) {
            $invalidItem = collect($file['items'])
                ->first(fn ($id) => ! $itemsById->has((int) $id));

            if ($invalidItem !== null) {
                throw ValidationException::withMessages([
                    'files' => 'One or more selected items no longer exist on this prep list.',
                ]);
            }
        }

        $workbooks = [];

        foreach ($validated['files'] as $file) {
            $supplier = $suppliers->get($file['supplier']);
            $items = collect($file['items'])
                ->map(fn ($id) => $itemsById->get((int) $id))
                ->filter()
                ->values();

            $workbooks[] = [
                'filename' => $this->quotationFilename($preplist, $supplier),
                'content' => RfqQuotationWorkbook::build($preplist, $supplier, $items),
                'supplier' => $supplier,
            ];
        }

        // Persist one quotation record per supplier so the Quotation module
        // can track responses from the moment the RFQ is generated. Reusing
        // the same prep list + supplier pair updates the existing record
        // instead of piling up duplicates. The generated workbook itself is
        // delivered as a download here; the Quotation detail page stores the
        // supplier's completed file once it is uploaded there separately.
        DB::transaction(function () use ($preplist, $request, &$workbooks) {
            foreach ($workbooks as &$workbook) {
                $supplier = $workbook['supplier'];

                $existing = Quotation::query()
                    ->where('preplist_id', $preplist->id)
                    ->where('supplier_id', $supplier->id)
                    ->first();

                $payload = [
                    'generated_at' => now(),
                ];

                if ($existing) {
                    $existing->update($payload);

                    ActivityLogger::log('Edited', 'Quotation', $existing->rfq_number ?: 'Quotation #'.$existing->id, $existing);
                } else {
                    $quotation = Quotation::query()->create(array_merge($payload, [
                        'preplist_id' => $preplist->id,
                        'supplier_id' => $supplier->id,
                        'created_by' => $request->user()->id,
                        'rfq_number' => $this->nextRfqNumber(),
                    ]));

                    ActivityLogger::log('Created', 'Quotation', $quotation->rfq_number ?: 'Quotation #'.$quotation->id, $quotation);
                }
            }
            unset($workbook);
        });

        $supplierCount = count($workbooks);
        session()->flash('status', 'RFQ generated for '.$supplierCount.' supplier'.($supplierCount === 1 ? '' : 's').'. Quotation tracker'.($supplierCount === 1 ? ' was' : 's were').' created.');

        if (count($workbooks) === 1) {
            return response($workbooks[0]['content'], 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$workbooks[0]['filename'].'"',
            ]);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'rfq_zip_').'.zip';

        $zip = new ZipArchive;

        if ($zip->open($temporaryPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create the RFQ ZIP archive.');
        }

        foreach ($workbooks as $workbook) {
            $zip->addFromString($workbook['filename'], $workbook['content']);
        }

        $zip->close();

        $contents = file_get_contents($temporaryPath);
        unlink($temporaryPath);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read the RFQ ZIP archive.');
        }

        return response($contents, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$this->quotationFilename($preplist, null, 'zip').'"',
        ]);
    }

    /**
     * Available projects that have item rows to compile.
     *
     * Hidden from the compile page: fulfilled lines, and any project line
     * already sourced into another prep list. A partially bought line is
     * still available — its remaining quantity can be compiled again to
     * source the rest of the requirement from other suppliers.
     *
     * Lines sourced into the prep being edited stay visible so editing
     * cannot silently drop them.
     */
    private function projectsWithItems(?Preplist $prepList = null)
    {
        $sourcedItemIds = $prepList === null
            ? []
            : $prepList->items->flatMap(fn (PreplistItem $item) => $item->sources->pluck('project_item_id'))->all();

        // Project lines already promised to a different prep list are not
        // offered for a new compile — unless they are only partly bought,
        // in which case their remaining quantity goes to other suppliers.
        $sourcedElsewhere = $this->sourcedElsewhereByItem($prepList);

        return Project::query()
            ->with(['procuringEntity', 'items.item.category'])
            ->orderByDesc('id')
            ->get()
            ->map(function (Project $project) use ($sourcedItemIds, $sourcedElsewhere) {
                $project->setRelation('items', $project->items
                    ->reject(fn (ProjectItem $item) => ($item->isFulfilled() || ($sourcedElsewhere->has($item->id) && ! $item->isPartiallyFulfilled())) && ! in_array($item->id, $sourcedItemIds, true))
                    ->values());

                return $project;
            })
            ->filter(fn (Project $project) => $project->items->isNotEmpty())
            ->values();
    }

    /**
     * Reject source lines already sourced into a different prep list — they
     * cannot be compiled again. A partially bought line is still open for
     * its remaining requirement, so it may be compiled again to source the
     * rest from other suppliers. Own lines (editing the prep list that
     * holds them) remain valid.
     */
    private function assertNoAlreadySourcedElsewhere(array $validated, ?Preplist $prepList = null): void
    {
        $ids = $validated['sources'] ?? [];

        if ($ids === []) {
            return;
        }

        $alreadySourced = PreplistItemSource::query()
            ->with(['preplistItem.preplist', 'projectItem'])
            ->whereIn('project_item_id', $ids)
            ->get()
            ->filter(fn (PreplistItemSource $source) => $prepList === null
                || $source->preplistItem?->preplist_id !== $prepList->id)
            ->filter(fn (PreplistItemSource $source) => ! $source->projectItem?->isPartiallyFulfilled());

        if ($alreadySourced->isEmpty()) {
            return;
        }

        $refs = $alreadySourced
            ->map(fn (PreplistItemSource $source) => $source->preplistItem?->preplist?->reference_no ?? 'another prep list')
            ->unique()
            ->join(', ');

        throw ValidationException::withMessages([
            'sources' => 'Already sourced into: '.$refs.'. Remove it from there first.',
        ]);
    }

    /**
     * Project item ids already sourced into prep lists other than the one
     * being edited (null edits = new prep list, everything is "elsewhere"),
     * keyed by source id.
     */
    private function sourcedElsewhereByItem(?Preplist $prepList): Collection
    {
        return PreplistItemSource::query()
            ->with('preplistItem.preplist')
            ->get()
            ->filter(fn (PreplistItemSource $source) => $prepList === null
                || $source->preplistItem?->preplist_id !== $prepList->id)
            ->mapWithKeys(fn (PreplistItemSource $source) => [
                $source->project_item_id => $source->preplistItem?->preplist?->reference_no ?? 'another prep list',
            ]);
    }

    /**
     * Reject source rows that are already fulfilled (received via PO, or fully
     * covered by on-hand stock) — they cannot be compiled into a prep list.
     * Lines already sourced into the same prep list keep working, so editing
     * never fails on its own previously accepted selections.
     */
    private function assertNoFulfilledSources(array $validated, ?Preplist $prepList = null): void
    {
        $ids = $validated['sources'] ?? [];

        if ($ids === []) {
            return;
        }

        $fulfilled = ProjectItem::query()
            ->with('item')
            ->whereIn('id', $ids)
            ->get()
            ->filter(fn (ProjectItem $item) => $item->isFulfilled());

        if ($prepList !== null) {
            $prepList->loadMissing('items.sources');

            $alreadySourced = $prepList->items
                ->flatMap(fn (PreplistItem $item) => $item->sources->pluck('project_item_id'))
                ->all();

            $fulfilled = $fulfilled->reject(fn (ProjectItem $item) => in_array($item->id, $alreadySourced, true));
        }

        if ($fulfilled->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'sources' => 'Already fulfilled and cannot be compiled: '
                .$fulfilled->map(fn (ProjectItem $item) => $item->item?->name ?? ('item #'.$item->id))->join(', ').'.',
        ]);
    }

    /**
     * Validate the prep list form.
     */
    private function validatePreplist(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['integer', 'distinct', 'exists:project_items,id'],
            'shelf_life' => ['nullable', 'array'],
            'shelf_life.*' => ['nullable', 'integer', 'min:0', 'max:1200'],
        ], [
            'sources.required' => 'Select at least one project item to compile.',
            'sources.min' => 'Select at least one project item to compile.',
        ]);
    }

    /**
     * Replace a prep list's compiled items with the selected project items.
     */
    private function throwIfUniqueViolation(QueryException $exception, string $message): never
    {
        if (str_contains($exception->getMessage(), 'UNIQUE') || str_contains($exception->getMessage(), 'unique')) {
            throw ValidationException::withMessages(['sources' => $message]);
        }

        throw $exception;
    }

    private function syncItems(Preplist $preplist, array $validated): void
    {
        $preplist->items()->get()->each(function (PreplistItem $item) {
            $item->sources()->delete();
        });

        $preplist->items()->delete();

        $projectItems = ProjectItem::query()
            ->with('item')
            ->whereIn('id', $validated['sources'])
            ->get()
            ->keyBy('id');

        $shelfLife = $validated['shelf_life'] ?? [];

        foreach ($projectItems->groupBy('item_id') as $itemId => $group) {
            $minimumShelfLife = $group
                ->map(fn (ProjectItem $projectItem) => (int) ($shelfLife[$projectItem->id]
                    ?? $projectItem->item?->default_min_shelf_life_months
                    ?? 0))
                ->max() ?? 0;

            $preplistItem = PreplistItem::query()->create([
                'preplist_id' => $preplist->id,
                'item_id' => $itemId,
                'min_shelf_life_months' => $minimumShelfLife,
            ]);

            foreach ($group as $projectItem) {
                $preplistItem->sources()->create([
                    'project_item_id' => $projectItem->id,
                    'qty' => $projectItem->quantity,
                ]);
            }
        }
    }

    /**
     * Build the download filename for a quotation workbook or ZIP archive.
     */
    private function quotationFilename(Preplist $preplist, ?Supplier $supplier, string $extension = 'xlsx'): string
    {
        $base = 'RFQ-'.$preplist->reference_no;

        if ($supplier !== null) {
            $base .= '-'.Str::slug($supplier->name);
        }

        return $base.'.'.$extension;
    }

    /**
     * Generate the next quotation RFQ number for the current year.
     */
    private function nextRfqNumber(): string
    {
        $year = (int) now()->year;

        $sequence = NumberSequence::query()
            ->where('key', 'rfq')
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            $sequence = NumberSequence::query()->create([
                'key' => 'rfq',
                'year' => $year,
                'last_number' => 0,
            ]);
        }

        $sequence->last_number++;
        $sequence->save();

        return sprintf('RFQ-%d-%04d', $year, $sequence->last_number);
    }

    /**
     * Generate the next prep list reference number for the current year.
     */
    private function nextReferenceNumber(): string
    {
        $year = (int) now()->year;

        $sequence = NumberSequence::query()
            ->where('key', 'preplist')
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            $sequence = NumberSequence::query()->create([
                'key' => 'preplist',
                'year' => $year,
                'last_number' => 0,
            ]);
        }

        $sequence->last_number++;
        $sequence->save();

        return sprintf('PL-%d-%04d', $year, $sequence->last_number);
    }
}
