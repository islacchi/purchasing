<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\PreplistItemSource;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Support\ActivityLogger;
use App\Support\Pagination;
use App\Support\ProjectSummaryWorkbook;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
     * Server-side search, filters and per-page; Blade renders a single slice.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $entity = trim((string) $request->query('entity', ''));
        $mode = trim((string) $request->query('mode', ''));
        $status = trim((string) $request->query('status', ''));
        $perPage = Pagination::perPage($request, 10);

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
            ->when($entity !== '' && ctype_digit($entity), function ($query) use ($entity) {
                $query->where('procuring_entity_id', (int) $entity);
            })
            ->when($mode !== '' && in_array($mode, self::MODES_OF_PROCUREMENT, true), function ($query) use ($mode) {
                $query->where('mode_of_procurement', $mode);
            })
            ->when($status === 'open' || $status === 'completed', function ($query) use ($status) {
                // Match the isFulfilled() predicate: fulfilled = explicitly set OR on-hand covers quantity.
                $query->where(function ($query) use ($status) {
                    if ($status === 'open') {
                        $query->whereExists(function ($subQuery) {
                            $subQuery->select('project_items.id')
                                ->from('project_items')
                                ->whereColumn('project_items.project_id', 'projects.id')
                                ->where('fulfillment_status', '!=', 'fulfilled')
                                ->where(function ($inner) {
                                    $inner->whereColumn('in_stock', '<', 'quantity')
                                        ->orWhere('quantity', '<=', 0)
                                        ->orWhereNull('in_stock');
                                })
                                ->limit(1);
                        });
                    } else {
                        $query->whereNotExists(function ($subQuery) {
                            $subQuery->select('project_items.id')
                                ->from('project_items')
                                ->whereColumn('project_items.project_id', 'projects.id')
                                ->where('fulfillment_status', '!=', 'fulfilled')
                                ->where(function ($inner) {
                                    $inner->whereColumn('in_stock', '<', 'quantity')
                                        ->orWhere('quantity', '<=', 0)
                                        ->orWhereNull('in_stock');
                                })
                                ->limit(1);
                        });
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $entities = ProcuringEntity::query()->orderBy('name')->get(['id', 'name']);

        return view('Projects.index', compact('projects', 'search', 'perPage', 'entities', 'entity', 'mode', 'status'));
    }

    /**
     * Show the add project form.
     */
    public function create()
    {
        return view('Projects.form', [
            'project' => null,
            'entities' => ProcuringEntity::query()->orderBy('name')->get(),
            'items' => $this->formItems(null),
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
     * Download the project summary as an .xlsx workbook.
     *
     * Folio (8.5 × 13 in), landscape, fit-to-width — a print-ready export of
     * the same data shown on the project detail page. Opened in the normal
     * grid view so the sheet renders in every spreadsheet application.
     */
    public function download(Request $request, Project $project)
    {
        $project->load(['procuringEntity', 'items.item']);

        $reference = preg_replace('/[^a-z0-9_-]+/i', '-', (string) $project->reference_no) ?: 'project';

        $preparedBy = $request->user()?->name ?: $request->user()?->email;

        return response(ProjectSummaryWorkbook::build($project, $preparedBy), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$reference.'_Project_Summary.xlsx"',
        ]);
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
            'projectFingerprint' => $this->payloadFingerprint($project),
            'items' => $this->formItems($project),
        ]);
    }

    /**
     * Item rows for the form: honors old() input after a failed validation
     * round-trip (with lock flags), otherwise the project's stored rows with
     * lock flags, or a single blank row for a new project.
     *
     * @return array<int, array>
     */
    private function formItems(?Project $project): array
    {
        $items = old('items');

        if ($items === null || count($items) === 0) {
            if ($project && $project->items->isNotEmpty()) {
                $items = $project->items
                    ->map(fn (ProjectItem $projectItem) => [
                        'description' => $projectItem->item?->name ?? '',
                        'category' => $projectItem->item?->category?->name ?? '',
                        'unit' => $projectItem->item?->unit ?? '',
                        'quantity' => $projectItem->quantity,
                        'in_stock' => $projectItem->in_stock,
                        'unit_cost' => $projectItem->unit_cost,
                        'quoted_price' => $projectItem->awarded_unit_price,
                    ])
                    ->values()
                    ->all();
            } else {
                $items = [$this->emptyItemRow()];
            }
        }

        if ($project === null) {
            return collect($items)
                ->map(fn ($item) => array_merge($this->emptyItemRow(), $item))
                ->values()
                ->all();
        }

        [$locks, $lockRefs] = $this->itemLocks($project, $items);

        return collect($items)
            ->map(fn ($item, $index) => array_merge($this->emptyItemRow(), $item, [
                'locked' => $locks[$index] ?? false,
                'lockedIn' => $lockRefs[$index] ?? [],
            ]))
            ->values()
            ->all();
    }

    private function emptyItemRow(): array
    {
        return [
            'description' => '',
            'category' => '',
            'unit' => '',
            'quantity' => '',
            'in_stock' => '',
            'unit_cost' => '',
            'quoted_price' => '',
            'locked' => false,
            'lockedIn' => [],
        ];
    }

    /**
     * Which item rows submitted with this project are locked (sourced into a
     * prep list) and which prep lists hold them.
     *
     * Rows resolve to catalog items by (category, name, unit), matched
     * greedily in order against the project's existing lines, so the row
     * order of the payload and the database rows stay aligned.
     *
     * @param  array<int, array>  $rows  Item rows in their submitted order
     * @return array{0: array<int, bool>, 1: array<int, list<string>>}
     */
    private function itemLocks(Project $project, array $rows): array
    {
        $locked = [];
        $refs = [];

        $existing = $project->items->values();

        $pool = [];

        foreach ($existing as $projectItem) {
            $pool[$projectItem->item_id] ??= [];
            $pool[$projectItem->item_id][] = $projectItem;
        }

        $sourced = $this->sourcedPrepLists($existing->pluck('id')->all());

        foreach ($rows as $index => $row) {
            $resolved = $this->resolveRowItem($row, createMissing: false);

            if ($resolved !== null && ! empty($pool[$resolved->id])) {
                $match = array_shift($pool[$resolved->id]);

                if (isset($sourced[$match->id])) {
                    $locked[$index] = true;
                    $refs[$index] = $sourced[$match->id];

                    continue;
                }

                $locked[$index] = false;

                continue;
            }

            $locked[$index] = false;
        }

        return [$locked, $refs];
    }

    /**
     * The prep list reference(s) sourcing each project item id.
     *
     * @param  array<int>  $projectItemIds
     * @return array<int, list<string>>
     */
    private function sourcedPrepLists(array $projectItemIds): array
    {
        if ($projectItemIds === []) {
            return [];
        }

        return PreplistItemSource::query()
            ->whereIn('project_item_id', $projectItemIds)
            ->with('preplistItem.preplist')
            ->get()
            ->groupBy('project_item_id')
            ->map(function ($group) {
                return $group
                    ->map(fn (PreplistItemSource $source) => $source->preplistItem?->preplist?->reference_no
                        ?: $source->preplistItem?->preplist?->name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            })
            ->all();
    }

    /**
     * Resolve the catalog item for a submitted row.
     * When $createMissing is true, missing categories/items are created.
     */
    private function resolveRowItem(array $row, bool $createMissing): ?Item
    {
        $categoryName = trim((string) ($row['category'] ?? ''));
        $name = trim((string) ($row['description'] ?? ''));
        $unit = trim((string) ($row['unit'] ?? ''));

        if ($createMissing) {
            $category = ItemCategory::query()->firstOrCreate(['name' => $categoryName]);

            return Item::query()->firstOrCreate(
                ['category_id' => $category->id, 'name' => $name, 'unit' => $unit],
                ['default_min_shelf_life_months' => 0],
            );
        }

        $categoryId = ItemCategory::query()->where('name', $categoryName)->value('id');

        if ($categoryId === null) {
            return null;
        }

        return Item::query()
            ->where('category_id', $categoryId)
            ->where('name', $name)
            ->where('unit', $unit)
            ->first();
    }

    /**
     * Fingerprint of every field the edit form can change — used as the optimistic
     * concurrency checkpoint when the project is saved.
     */
    private function payloadFingerprint(Project $project): string
    {
        $itemRows = $project->items->map(fn (ProjectItem $item) => implode(':', [
            $item->id,
            (int) $item->quantity,
            (float) $item->unit_cost,
            (int) $item->in_stock,
        ]))->sort()->implode("\n");

        $fields = implode('|', [
            (string) $project->title,
            (string) $project->reference_no,
            (string) $project->mode_of_procurement,
            (string) $project->amount_awarded,
            (string) $project->date_awarded,
            (string) $project->delivery_due_date,
            (string) $project->procuring_entity_id,
            (string) $project->notes,
        ]);

        return md5($itemRows."\n".$fields);
    }

    /**
     * Update a project and replace its item rows.
     */
    public function update(Request $request, Project $project)
    {
        $expectedFingerprint = $request->input('expected_project_fingerprint');

        if (is_string($expectedFingerprint) && $expectedFingerprint !== '' && $expectedFingerprint !== $this->payloadFingerprint($project)) {
            return redirect()
                ->route('projects.edit', ['project' => $project->id])
                ->withErrors(['project' => 'Someone else changed this project since the page was loaded. Please review the current values, then submit your changes again.']);
        }

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
            ->filter(fn ($item) => is_array($item)
                && collect($item)->contains(fn ($value) => filled($value)))
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
            'items.*.in_stock' => ['nullable', 'integer', 'min:0'],
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
    /**
     * Reconcile the submitted item rows with the project's item lines.
     *
     * A line already sourced into a prep list is immutable — its stored values
     * are always kept, no matter what the form submits. Other rows are updated
     * in place, new rows are created, and rows the user removed are deleted
     * unless a prep list sources them (in which case the whole save is refused).
     * Fulfillment status of existing lines is preserved.
     */
    private function syncItems(Project $project, array $items): void
    {
        $project->load('items.item.category');
        $existing = $project->items->values();

        $pool = [];

        foreach ($existing as $projectItem) {
            $pool[$projectItem->item_id] ??= [];
            $pool[$projectItem->item_id][] = $projectItem;
        }

        $sourced = $this->sourcedPrepLists($existing->pluck('id')->all());

        foreach ($items as $row) {
            $resolved = $this->resolveRowItem($row, createMissing: true);

            if ($resolved !== null && ! empty($pool[$resolved->id])) {
                $match = array_shift($pool[$resolved->id]);

                if (isset($sourced[$match->id])) {
                    continue; // Locked line — keep the stored values untouched.
                }

                $match->update([
                    'quantity' => (int) $row['quantity'],
                    'in_stock' => (int) ($row['in_stock'] ?? 0),
                    'unit_cost' => $row['unit_cost'],
                    'awarded_unit_price' => $row['quoted_price'] ?? null,
                ]);

                continue;
            }

            if ($resolved === null) {
                continue;
            }

            ProjectItem::query()->create([
                'project_id' => $project->id,
                'item_id' => $resolved->id,
                'quantity' => (int) $row['quantity'],
                'in_stock' => (int) ($row['in_stock'] ?? 0),
                'unit_cost' => $row['unit_cost'],
                'awarded_unit_price' => $row['quoted_price'] ?? null,
                'fulfillment_status' => 'pending',
            ]);
        }

        // Rows the user removed: everything left unmatched in the pool.
        $blocked = [];

        foreach ($pool as $remaining) {
            foreach ($remaining as $projectItem) {
                if (isset($sourced[$projectItem->id])) {
                    $blocked[] = $projectItem->item?->name ?? 'Item';

                    continue;
                }

                $projectItem->delete();
            }
        }

        if ($blocked !== []) {
            throw ValidationException::withMessages([
                'items' => 'These items are already used in a prep list and cannot be removed from the project: '
                    .implode(', ', array_unique($blocked))
                    .'. Remove them in the prep list first, then save the project.',
            ]);
        }
    }
}
