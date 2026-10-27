@extends('layouts.app')

@section('page-title', 'ENTITIES')
@section('title', 'Entity Form - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // ------------------------------------------------------------------
        // This single view serves Add and Edit for all three entity types:
        //   'user', 'procuring_entity', and 'supplier'.
        //
        //   - Add:  $id is null       -> blank form
        //   - Edit: $id is provided   -> prefill from the sample rows below
        //
        // @TODO: once models + migrations exist, replace the hardcoded $samples
        // rows with real Eloquent queries:
        //   - user:             App\Models\User::with('roles')->findOrFail($id)
        //   - procuring_entity: App\Models\Entity::findOrFail($id)
        //   - supplier:         App\Models\Supplier::findOrFail($id)
        // ------------------------------------------------------------------

        $isEdit = isset($id) && $id !== null;

        $samples = [
            'user' => [
                1 => [
                    'name'   => 'J. Santos',
                    'email'  => 'j.santos@company.com',
                    'role'   => 'Procurement officer',
                    'status' => 'Active',
                ],
                2 => [
                    'name'   => 'M. Reyes',
                    'email'  => 'm.reyes@company.com',
                    'role'   => 'Admin',
                    'status' => 'Active',
                ],
                3 => [
                    'name'   => 'A. Cruz',
                    'email'  => 'a.cruz@company.com',
                    'role'   => 'Procurement officer',
                    'status' => 'Inactive',
                ],
            ],
            'procuring_entity' => [
                1 => [
                    'name'           => 'DOH Region VII',
                    'type'            => 'Regional Office',
                    'contact_person'  => 'Dr. R. Villanueva',
                    'contact_no'      => '032-123-4567',
                    'email'           => 'doh.region7@gov.ph',
                    'address'         => 'Osmeña Blvd, Cebu City',
                ],
                2 => [
                    'name'           => 'PGH Manila',
                    'type'            => 'Government Hospital',
                    'contact_person'  => 'N. Aquino',
                    'contact_no'      => '02-8554-8400',
                    'email'           => 'procurement@pgh.gov.ph',
                    'address'         => 'Taft Ave, Ermita, Manila',
                ],
                3 => [
                    'name'           => 'Iloilo Provincial Hospital',
                    'type'            => 'Provincial Hospital',
                    'contact_person'  => 'V. Tan',
                    'contact_no'      => '033-778-2211',
                    'email'           => 'procurement@iph.gov.ph',
                    'address'         => 'Iloilo City',
                ],
            ],
            'supplier' => [
                1 => [
                    'name'           => 'Triple Tact',
                    'contact_person' => 'E. Lim',
                    'contact_no'     => '032-345-6789',
                    'email'          => 'sales@tripletact.com',
                    'address'        => 'Mandaue City, Cebu',
                ],
                2 => [
                    'name'           => 'GreenCore Pharmaceuticals',
                    'contact_person' => 'P. Domingo',
                    'contact_no'     => '02-8123-4455',
                    'email'          => 'orders@greencorepharma.ph',
                    'address'        => 'Quezon City, Metro Manila',
                ],
                3 => [
                    'name'           => 'MedSource Inc.',
                    'contact_person' => 'C. Fernandez',
                    'contact_no'     => '02-8990-1122',
                    'email'          => 'info@medsourceinc.ph',
                    'address'        => 'Pasig City, Metro Manila',
                ],
            ],
        ];

        // Route parameters arrive as strings; cast before using the id as an array key.
        $recordId = $isEdit ? (int) $id : null;
        $record   = $isEdit ? ($samples[$type][$recordId] ?? null) : [];

        if ($isEdit && $record === null) {
            abort(404);
        }

        // @TODO: add password / password_confirmation fields once real user
        // accounts and authentication exist. Intentionally omitted for now.

        $typeConfigs = [
            'user' => [
                'title'    => 'USER',
                'singular' => 'User',
                'fields'   => [
                    ['name' => 'name',   'label' => 'Name',   'type' => 'text',   'placeholder' => 'e.g. J. SANTOS'],
                    ['name' => 'email',  'label' => 'Email',  'type' => 'email',  'placeholder' => 'e.g. J.SANTOS@COMPANY.COM'],
                    ['name' => 'role',   'label' => 'Role',   'type' => 'select', 'placeholder' => 'Select role',   'options' => ['Admin', 'Procurement officer']],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'placeholder' => 'Select status', 'options' => ['Active', 'Inactive']],
                ],
            ],
            'procuring_entity' => [
                'title'    => 'PROCURING ENTITY',
                'singular' => 'Entity',
                'fields'   => [
                    ['name' => 'name',           'label' => 'Name',           'type' => 'text',     'placeholder' => 'e.g. DOH REGION VII'],
                    ['name' => 'type',           'label' => 'Type',           'type' => 'select',   'placeholder' => 'Select type', 'options' => ['Regional Office', 'Government Hospital', 'Provincial Hospital']],
                    ['name' => 'contact_person', 'label' => 'Contact person', 'type' => 'text',     'placeholder' => 'e.g. DR. R. VILLANUEVA'],
                    ['name' => 'contact_no',     'label' => 'Contact no.',    'type' => 'text',     'placeholder' => 'e.g. 032-123-4567'],
                    ['name' => 'email',          'label' => 'Email',          'type' => 'email',    'placeholder' => 'e.g. DOH.REGION7@GOV.PH'],
                    ['name' => 'address',        'label' => 'Address',        'type' => 'textarea', 'placeholder' => 'e.g. OSMEÑA BLVD, CEBU CITY', 'column' => 'right', 'rows' => 10],
                ],
            ],
            'supplier' => [
                'title'    => 'SUPPLIER',
                'singular' => 'Supplier',
                'fields'   => [
                    ['name' => 'name',           'label' => 'Name',           'type' => 'text',     'placeholder' => 'e.g. GREENCORE PHARMACEUTICALS'],
                    ['name' => 'contact_person', 'label' => 'Contact person', 'type' => 'text',     'placeholder' => 'e.g. P. DOMINGO'],
                    ['name' => 'contact_no',     'label' => 'Contact no.',    'type' => 'text',     'placeholder' => 'e.g. 02-8123-4455'],
                    ['name' => 'email',          'label' => 'Email',          'type' => 'email',    'placeholder' => 'e.g. ORDERS@GREENCOREPHARMA.PH'],
                    ['name' => 'address',        'label' => 'Address',        'type' => 'textarea', 'placeholder' => 'e.g. QUEZON CITY, METRO MANILA', 'column' => 'right', 'rows' => 10],
                ],
            ],
        ];

        $config    = $typeConfigs[$type];
        $typeLabel = $config['title'] . ' FORM';
        $formTitle = ($isEdit ? 'EDIT ' : 'ADD ') . $config['title'];
        $saveLabel = 'Save ' . $config['singular'];

        // Group the configured fields into their left/right columns while
        // preserving the order defined above.
        $formColumns = [];
        foreach ($config['fields'] as $field) {
            $formColumns[$field['column'] ?? 'left'][] = $field;
        }
        $formColumns = array_values($formColumns);
    @endphp

    {{-- Type label --}}
    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $typeLabel }}</p>

    {{-- @TODO: once the store/update routes and validation exist, replace action="#"
         with:
           add:  route('entities.store', ['type' => $type])
           edit: route('entities.update', ['type' => $type, 'id' => $id]) --}}
    <form action="#" method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf

        {{-- Form title --}}
        <h2 class="text-xl font-bold text-gray-900 uppercase tracking-wide">{{ $formTitle }}</h2>

        {{-- Fields --}}
        <div class="{{ count($formColumns) > 1 ? 'grid grid-cols-1 lg:grid-cols-2 gap-x-10 gap-y-5 mt-6' : 'max-w-2xl mt-6' }}">
            @foreach($formColumns as $columnFields)
                <div class="{{ $loop->first ? 'space-y-4' : 'flex flex-col' }}">
                    @foreach($columnFields as $field)
                        <div class="{{ $loop->parent->first ? '' : 'flex-1 flex flex-col' }}">
                            <label for="field-{{ $field['name'] }}"
                                   class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                {{ $field['label'] }}
                            </label>

                            @if($field['type'] === 'select')
                                <select id="field-{{ $field['name'] }}" name="{{ $field['name'] }}"
                                        class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                    <option value="" {{ (string) old($field['name'], $record[$field['name']] ?? '') === '' ? 'selected' : '' }}>
                                        {{ $field['placeholder'] }}
                                    </option>
                                    @foreach($field['options'] as $option)
                                        <option value="{{ $option }}" {{ (string) old($field['name'], $record[$field['name']] ?? '') === (string) $option ? 'selected' : '' }}>
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif($field['type'] === 'textarea')
                                <textarea id="field-{{ $field['name'] }}" name="{{ $field['name'] }}"
                                          rows="{{ $field['rows'] ?? 4 }}"
                                          placeholder="{{ $field['placeholder'] }}"
                                          class="w-full flex-1 min-h-[280px] px-3 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">{{ old($field['name'], $record[$field['name']] ?? '') }}</textarea>
                            @else
                                <input id="field-{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}"
                                       value="{{ old($field['name'], $record[$field['name']] ?? '') }}"
                                       placeholder="{{ $field['placeholder'] }}"
                                       class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            @endif

                            @error($field['name'])
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Form actions --}}
        <div class="flex items-center justify-end gap-3 mt-8 pt-5 border-t border-gray-100">
            <a href="{{ route('entities') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancel
            </a>
            <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 rounded-md bg-[#2a7a94] text-white text-sm font-medium hover:bg-[#236b80] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-6 0V3h6v4m-6 0h6"/>
                </svg>
                {{ $saveLabel }}
            </button>
        </div>
    </form>

    {{-- Bottom spacer --}}
    <div class="pb-4"></div>

@endsection
