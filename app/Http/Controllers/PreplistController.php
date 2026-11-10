<?php

namespace App\Http\Controllers;

use App\Models\NumberSequence;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return view('Preplist.index', compact('preplists', 'search'));
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

        return view('Preplist.show', compact('preplist'));
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
     * Soft-delete a prep list.
     */
    public function destroy(Preplist $preplist)
    {
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
