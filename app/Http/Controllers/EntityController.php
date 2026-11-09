<?php

namespace App\Http\Controllers;

use App\Models\ProcuringEntity;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        $validated = $request->validate($this->rules($type, null));

        if ($type === 'procuring_entity') {
            ProcuringEntity::query()->create($validated);

            $label = 'Procuring Entity';
        } else {
            Supplier::query()->create($validated);

            $label = 'Supplier';
        }

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

        $validated = $request->validate($this->rules($type, (int) $id));

        if ($type === 'procuring_entity') {
            $record->update($validated);

            $label = 'Procuring Entity';
        } else {
            $record->update($validated);

            $label = 'Supplier';
        }

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
        $model::query()->findOrFail($id)->delete();

        $label = $type === 'procuring_entity' ? 'Procuring Entity' : 'Supplier';

        return redirect()
            ->route('entities', ['tab' => $this->indexTab($type)])
            ->with('status', $label.' deleted successfully.');
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
                    ? Rule::unique('suppliers', 'name')
                    : Rule::unique('suppliers', 'name')->ignore($ignoreId),
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
