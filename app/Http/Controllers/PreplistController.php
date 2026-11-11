<?php

namespace App\Http\Controllers;

use App\Models\NumberSequence;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Support\RfqQuotationWorkbook;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class PreplistController extends Controller
{
    /**
     * Show the prep lists list using real database records.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $preplists = Preplist::query()
            ->with(['creator', 'items.sources.projectItem.project'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('reference_no', 'like', '%'.$search.'%')
                        ->orWhereHas('creator', function ($query) use ($search) {
                            $query->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('id')
            ->get();

        $quotationCounts = Quotation::query()
            ->select('preplist_id', DB::raw('COUNT(*) as aggregate'))
            ->whereIn('preplist_id', $preplists->pluck('id'))
            ->groupBy('preplist_id')
            ->pluck('aggregate', 'preplist_id');

        return view('Preplist.index', compact('preplists', 'search', 'quotationCounts'));
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

        $preplist = DB::transaction(function () use ($request, $validated) {
            $preplist = Preplist::query()->create([
                'created_by' => $request->user()->id,
                'name' => $validated['name'],
                'reference_no' => $this->nextReferenceNumber(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncItems($preplist, $validated);

            return $preplist;
        });

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

        $quotationCount = Quotation::query()->where('preplist_id', $preplist->id)->count();

        return view('Preplist.show', compact('preplist', 'quotationCount'));
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
            'projects' => $this->projectsWithItems(),
            'selectedSources' => $selectedSources,
        ]);
    }

    /**
     * Update a prep list and replace its source items.
     */
    public function update(Request $request, Preplist $preplist)
    {
        $validated = $this->validatePreplist($request);

        DB::transaction(function () use ($preplist, $validated) {
            $preplist->update([
                'name' => $validated['name'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->syncItems($preplist, $validated);
        });

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

        $preplist->update(['notes' => $validated['notes'] ?? null]);

        return redirect()
            ->route('preplist.show', ['preplist' => $preplist->id])
            ->with('status', 'Prep list notes updated successfully.');
    }

    /**
     * Soft-delete a prep list. A prep list that is already referenced by at
     * least one quotation cannot be deleted.
     */
    public function destroy(Preplist $preplist)
    {
        $quotationCount = Quotation::query()->where('preplist_id', $preplist->id)->count();

        if ($quotationCount > 0) {
            return back()
                ->withErrors(['preplist' => 'This prep list cannot be deleted because it is already referenced by '.$quotationCount.' quotation'.($quotationCount === 1 ? '' : 's').'. Remove it from the quotation(s) first.']);
        }

        $preplist->delete();

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
                    'total' => $preplistItem->totalQuantity(),
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
                } else {
                    Quotation::query()->create(array_merge($payload, [
                        'preplist_id' => $preplist->id,
                        'supplier_id' => $supplier->id,
                        'created_by' => $request->user()->id,
                        'rfq_number' => $this->nextRfqNumber(),
                    ]));
                }
            }
            unset($workbook);
        });

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
     */
    private function projectsWithItems()
    {
        return Project::query()
            ->with(['procuringEntity', 'items.item.category'])
            ->whereHas('items')
            ->orderByDesc('id')
            ->get();
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
