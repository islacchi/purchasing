<?php

namespace App\Http\Controllers;

use App\Models\ProcuringEntity;
use App\Models\Supplier;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EntityController extends Controller
{
    /**
     * Show the entities and suppliers pages with their real database records.
     */
    public function index()
    {
        $entities = ProcuringEntity::query()
            ->orderBy('id', 'desc')
            ->get();
        $suppliers = Supplier::query()
            ->orderBy('id', 'desc')
            ->get();

        return view('Entities.index', compact('entities', 'suppliers'));
    }

    /**
     * Show the add form for the selected entity type.
     */
    public function create(string $type)
    {
        $this->assertType($type);

        return view('Entities.form', [
            'type' => $type,
            'id' => null,
            'record' => [],
        ]);
    }

    /**
     * Show a procuring entity or supplier.
     */
    public function show(string $type, $id)
    {
        $this->assertType($type);

        $model = $this->modelForType($type);
        $record = $model::query()->findOrFail($id);

        if ($type === 'procuring_entity') {
            return view('Entities.show', [
                'type' => $type,
                'id' => (int) $id,
                'entity' => $record->toArray(),
            ]);
        }

        return view('Entities.show', [
            'type' => $type,
            'id' => (int) $id,
            'supplier' => $record->toArray(),
        ]);
    }

    /**
     * Store a new entity or supplier.
     */
    public function store(Request $request, string $type)
    {
        $this->assertType($type);

        // Normalise the name before validation and storage so a stray space
        // can no longer produce a duplicate.
        $request->merge(['name' => trim((string) $request->input('name', ''))]);

        $validated = $request->validate($this->rules($type, null));

        if ($type === 'supplier') {
            $this->assertNoOnlyTrashedSupplier($validated['name']);
        }

        $record = DB::transaction(function () use ($type, $validated) {
            $record = $type === 'procuring_entity'
                ? ProcuringEntity::query()->create($validated)
                : Supplier::query()->create($validated);

            ActivityLogger::log('Created', 'Entities', $record->name, $record);

            return $record;
        });

        $label = $type === 'procuring_entity' ? 'Procuring Entity' : 'Supplier';

        return redirect()
            ->route('entities', ['tab' => $this->indexTab($type)])
            ->with('status', $label.' created successfully.');
    }

    /**
     * Show the edit form for the selected entity.
     */
    public function edit(string $type, $id)
    {
        $this->assertType($type);

        $model = $this->modelForType($type);
        $record = $model::query()->findOrFail($id)->toArray();

        return view('Entities.form', [
            'type' => $type,
            'id' => (int) $id,
            'record' => $record,
        ]);
    }

    /**
     * Update the selected entity.
     */
    public function update(Request $request, string $type, $id)
    {
        $this->assertType($type);

        $model = $this->modelForType($type);
        $record = $model::query()->findOrFail($id);

        // Normalise the name before validation and storage so a stray space
        // can no longer produce a duplicate.
        $request->merge(['name' => trim((string) $request->input('name', ''))]);

        $validated = $request->validate($this->rules($type, (int) $id));

        if ($type === 'supplier') {
            $this->assertNoOnlyTrashedSupplier($validated['name']);
        }

        DB::transaction(function () use ($record, $validated) {
            $record->update($validated);

            ActivityLogger::log('Edited', 'Entities', $record->name, $record);
        });

        $label = $type === 'procuring_entity' ? 'Procuring Entity' : 'Supplier';

        return redirect()
            ->route('entities', ['tab' => $this->indexTab($type)])
            ->with('status', $label.' updated successfully.');
    }

    /**
     * Delete the selected entity.
     */
    public function destroy(string $type, $id)
    {
        $this->assertType($type);

        $model = $this->modelForType($type);
        $entity = $model::query()->findOrFail($id);

        DB::transaction(function () use ($entity) {
            $entity->delete();

            ActivityLogger::log('Deleted', 'Entities', $entity->name, $entity);
        });

        $label = $type === 'procuring_entity' ? 'Procuring Entity' : 'Supplier';

        return redirect()
            ->route('entities', ['tab' => $this->indexTab($type)])
            ->with('status', $label.' deleted successfully.');
    }

    /**
     * Ensure no soft-deleted supplier holds the given name.
     *
     * The unique index on suppliers.name covers every row including trashed
     * ones, so the validation rule (which skips soft deletes) can pass while
     * the INSERT/UPDATE would still explode at the database level. Check
     * first and give the operator an actionable message instead.
     */
    private function assertNoOnlyTrashedSupplier(string $name): void
    {
        $existingDeleted = Supplier::onlyTrashed()
            ->where('name', $name)
            ->first();

        if ($existingDeleted) {
            throw ValidationException::withMessages([
                'name' => 'A supplier named "'.$existingDeleted->name.'" was deleted. Ask an administrator about restoring it.',
            ]);
        }
    }

    /**
     * Validate the request for the selected entity type.
     */
    private function rules(string $type, ?int $ignoreId = null): array
    {
        if ($type === 'procuring_entity') {
            return [
                'name' => ['required', 'string', 'max:255'],
                'type' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::in(['Regional Office', 'Government Hospital', 'Provincial Hospital']),
                ],
                'address' => ['nullable', 'string', 'max:255'],
                'contact_person' => ['nullable', 'string', 'max:255'],
                'contact_no' => ['nullable', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
            ];
        }

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $ignoreId === null
                    ? Rule::unique('suppliers', 'name')->withoutTrashed()
                    : Rule::unique('suppliers', 'name')->withoutTrashed()->ignore($ignoreId),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * Map the entity type to the matching entities index tab.
     */
    private function indexTab(string $type): string
    {
        return $type === 'supplier' ? 'suppliers' : 'entities';
    }

    /**
     * Return the valid model class for an entity type.
     */
    private function modelForType(string $type): string
    {
        if ($type === 'procuring_entity') {
            return ProcuringEntity::class;
        }

        if ($type === 'supplier') {
            return Supplier::class;
        }

        $this->assertType($type);

        return Supplier::class;
    }

    /**
     * Ensure the route only accepts the supported entity types.
     */
    private function assertType(string $type): void
    {
        if (! in_array($type, ['procuring_entity', 'supplier'], true)) {
            abort(404);
        }
    }
}
