<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    /**
     * Procurement modes accepted by the project form.
     *
     * @var list<string>
     */
    private const MODES_OF_PROCUREMENT = [
        'shopping',
        'small_value',
        'competitive_bidding',
        'direct_contracting',
    ];

    /**
     * Item categories accepted by the project form.
     *
     * @var list<string>
     */
    private const ITEM_CATEGORIES = [
        'drugs and medicines',
        'supplies and equipments',
    ];

    /**
     * Show the projects list using real database records.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $projects = Project::query()
            ->with(['procuringEntity', 'items'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('reference_no', 'like', '%'.$search.'%')
                        ->orWhereHas('procuringEntity', function ($query) use ($search) {
                            $query->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderByDesc('id')
            ->get();

        return view('Projects.index', compact('projects', 'search'));
    }

    /**
     * Show the add project form.
     */
    public function create()
    {
        return view('Projects.form', [
            'project' => null,
            'entities' => ProcuringEntity::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a new project and its item rows.
     */
    public function store(Request $request)
    {
        $validated = $this->validateProject($request);
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $validated['created_by'] = $request->user()->id;
        $validated['delivery_due_date'] = $this->deliveryDueDate($validated);

        $project = DB::transaction(function () use ($validated, $items) {
            $project = Project::query()->create($validated);
            $this->syncItems($project, $items);

            ActivityLogger::log('Created', 'Projects', $project->title, $project);

            return $project;
        });

        return redirect()
            ->route('projects.show', ['project' => $project->id])
            ->with('status', 'Project created successfully.');
    }

    /**
     * Show a project and its items.
     */
    public function show(Project $project)
    {
        $project->load(['procuringEntity', 'creator', 'items.item.category']);
        $preplistSources = $this->preplistsReferencing($project);

        return view('Projects.show', compact('project', 'preplistSources'));
    }

    /**
     * Show the edit form for a project.
     */
    public function edit(Project $project)
    {
        $project->load(['items.item.category']);

        return view('Projects.form', [
            'project' => $project,
            'entities' => ProcuringEntity::query()->orderBy('name')->get(),
        ]);
    }

    /**
     * Update a project and replace its item rows.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $this->validateProject($request);
        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $validated['delivery_due_date'] = $this->deliveryDueDate($validated);

        DB::transaction(function () use ($validated, $items, $project) {
            $project->update($validated);
            $this->syncItems($project, $items);

            ActivityLogger::log('Edited', 'Projects', $project->title, $project);
        });

        return redirect()
            ->route('projects.show', ['project' => $project->id])
            ->with('status', 'Project updated successfully.');
    }

    /**
     * Soft-delete a project.
     */
    public function destroy(Project $project)
    {
        $references = $this->preplistsReferencing($project);

        if ($references->isNotEmpty()) {
            $names = $references
                ->map(fn ($preplist) => $preplist->reference_no.' ('.$preplist->name.')')
                ->implode(', ');

            return redirect()
                ->route('projects.show', ['project' => $project->id])
                ->withErrors(['project' => 'This project can no longer be deleted because it is used by the prep list(s): '.$names.'.']);
        }

        DB::transaction(function () use ($project) {
            $project->delete();

            ActivityLogger::log('Deleted', 'Projects', $project->title, $project);
        });

        return redirect()
            ->route('projects')
            ->with('status', 'Project deleted successfully.');
    }

    /**
     * The prep lists referencing this project's items, if any.
     *
     * @return Collection<int, Preplist>
     */
    private function preplistsReferencing(Project $project)
    {
        $preplistIds = PreplistItem::query()
            ->whereHas('sources', fn ($query) => $query->whereHas('projectItem', fn ($query) => $query->where('project_id', $project->id)))
            ->pluck('preplist_id');

        return Preplist::query()
            ->whereIn('id', $preplistIds)
            ->orderBy('reference_no')
            ->get();
    }

    /**
     * Validate the project form and return only the expected fields.
     */
    private function validateProject(Request $request): array
    {
        $items = collect($request->input('items', []))
            ->filter(fn ($item) => is_array($item) && filled($item['description'] ?? null))
            ->values()
            ->all();

        // Drop untouched item rows before validation so an empty row does not
        // block saving the project.
        $request->merge(['items' => $items]);

        return $request->validate([
            'procuring_entity_id' => ['required', 'integer', 'exists:procuring_entities,id'],
            'title' => ['required', 'string', 'max:255'],
            'reference_no' => ['required', 'string', 'max:255'],
            'mode_of_procurement' => ['required', 'string', Rule::in(self::MODES_OF_PROCUREMENT)],
            'amount_awarded' => ['nullable', 'numeric', 'min:0'],
            'date_awarded' => ['nullable', 'date'],
            'delivery_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'notes' => ['nullable', 'string'],
            'items' => ['array'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.category' => ['required', 'string', Rule::in(self::ITEM_CATEGORIES)],
            'items.*.unit' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.quoted_price' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    /**
     * Calculate the delivery due date from the award date and delivery days.
     */
    private function deliveryDueDate(array $project): ?string
    {
        if (empty($project['date_awarded']) || empty($project['delivery_days'])) {
            return null;
        }

        return Carbon::parse($project['date_awarded'])
            ->addDays((int) $project['delivery_days'])
            ->toDateString();
    }

    /**
     * Replace a project's item rows with the submitted form rows.
     */
    private function syncItems(Project $project, array $items): void
    {
        $project->items()->delete();

        foreach ($items as $row) {
            $category = ItemCategory::query()->firstOrCreate([
                'name' => $row['category'],
            ]);

            $item = Item::query()->firstOrCreate(
                [
                    'category_id' => $category->id,
                    'name' => $row['description'],
                    'unit' => $row['unit'],
                ],
                [
                    'default_min_shelf_life_months' => 0,
                ],
            );

            ProjectItem::query()->create([
                'project_id' => $project->id,
                'item_id' => $item->id,
                'quantity' => (int) $row['quantity'],
                'unit_cost' => $row['unit_cost'],
                'awarded_unit_price' => $row['quoted_price'] ?? null,
                'fulfillment_status' => 'pending',
            ]);
        }
    }
}
