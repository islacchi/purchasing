@extends('layouts.app')

@section('page-title', 'ENTITIES')
@section('title', isset($id) && $id !== null ? 'Entity Form - Purchasing' : 'Add Entity - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        $isEdit = isset($id) && $id !== null;
        $record = $isEdit ? ($record ?? []) : [];

        $typeConfigs = [
            'procuring_entity' => [
                'title' => 'PROCURING ENTITY',
                'singular' => 'Entity',
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'placeholder' => 'e.g. DOH REGION VII'],
                    ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'placeholder' => 'Select type', 'options' => [
                        'Regional Office',
                        'Government Hospital',
                        'Provincial Hospital',
                        'Rural Health Unit',
                        'Non Government Organization',
                        'Government Agency',
                    ]],
                    ['name' => 'contact_person', 'label' => 'Contact person', 'type' => 'text', 'placeholder' => 'e.g. DR. R. VILLANUEVA'],
                    ['name' => 'contact_no', 'label' => 'Contact no.', 'type' => 'text', 'placeholder' => 'e.g. 032-123-4567'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'e.g. DOH.REGION7@GOV.PH'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'placeholder' => 'e.g. OSMEÑA BLVD, CEBU CITY', 'column' => 'right', 'rows' => 10],
                ],
            ],
            'supplier' => [
                'title' => 'SUPPLIER',
                'singular' => 'Supplier',
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'placeholder' => 'e.g. GREENCORE PHARMACEUTICALS'],
                    ['name' => 'contact_person', 'label' => 'Contact person', 'type' => 'text', 'placeholder' => 'e.g. P. DOMINGO'],
                    ['name' => 'contact_no', 'label' => 'Contact no.', 'type' => 'text', 'placeholder' => 'e.g. 02-8123-4455'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'placeholder' => 'e.g. ORDERS@GREENCOREPHARMA.PH'],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'placeholder' => 'e.g. QUEZON CITY, METRO MANILA', 'column' => 'right', 'rows' => 10],
                ],
            ],
        ];

        if (! isset($typeConfigs[$type])) {
            abort(404);
        }

        $config = $typeConfigs[$type];
        $typeLabel = $config['title'].' FORM';
        $formTitle = ($isEdit ? 'Edit ' : 'Add ').$config['singular'];
        $saveLabel = ($isEdit ? 'Save ' : 'Add ').$config['singular'];
        $returnTab = $type === 'supplier' ? 'suppliers' : 'entities';

        $formColumns = [];
        foreach ($config['fields'] as $field) {
            $formColumns[$field['column'] ?? 'left'][] = $field;
        }
        $formColumns = array_values($formColumns);
    @endphp

    {{-- Back link --}}
    <a href="{{ route('entities', ['tab' => $returnTab]) }}" class="mb-3 inline-flex items-center gap-2 text-xs text-gray-500 transition-colors hover:text-[#2a7a94]">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
        </svg>
        Back to Entities
    </a>

    {{-- Page header --}}
    <div class="mb-4">
        <h2 class="text-2xl font-bold text-gray-900">{{ $formTitle }}</h2>
        <p class="mt-1 text-xs text-gray-500">{{ $typeLabel }} — {{ $isEdit ? 'update the details below' : 'fill in the details below' }}. Fields marked with an asterisk are required.</p>
    </div>

    <form action="{{ $isEdit ? route('entities.update', ['type' => $type, 'id' => $id]) : route('entities.store', ['type' => $type]) }}" method="POST" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">{{ $config['title'] }} details</p>

        <div class="{{ count($formColumns) > 1 ? 'grid grid-cols-1 lg:grid-cols-2 gap-x-10 gap-y-4 mt-4' : 'max-w-2xl mt-4' }}">
            @foreach ($formColumns as $columnFields)
                <div class="{{ $loop->first ? 'space-y-4' : 'flex flex-col' }}">
                    @foreach ($columnFields as $field)
                        <div>
                            <label for="field-{{ $field['name'] }}" class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">
                                @php $required = in_array($field['name'], ['name', 'type'], true); @endphp
                                {{ $field['label'] }}
                                @if ($required)
                                    <span class="text-red-500">*</span>
                                @else
                                    <span class="font-normal normal-case tracking-normal text-gray-400">(optional)</span>
                                @endif
                            </label>

                            @if ($field['type'] === 'select')
                                <select id="field-{{ $field['name'] }}" name="{{ $field['name'] }}"
                                        class="h-9 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                    <option value="" {{ (string) old($field['name'], $record[$field['name']] ?? '') === '' ? 'selected' : '' }}>
                                        {{ $field['placeholder'] }}
                                    </option>
                                    @foreach ($field['options'] as $option)
                                        <option value="{{ $option }}" {{ (string) old($field['name'], $record[$field['name']] ?? '') === (string) $option ? 'selected' : '' }}>
                                            {{ $option }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif ($field['type'] === 'textarea')
                                <textarea id="field-{{ $field['name'] }}" name="{{ $field['name'] }}"
                                          rows="{{ $field['rows'] ?? 4 }}"
                                          placeholder="{{ $field['placeholder'] }}"
                                          class="min-h-[260px] w-full rounded-md border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">{{ old($field['name'], $record[$field['name']] ?? '') }}</textarea>
                            @else
                                <input id="field-{{ $field['name'] }}" type="{{ $field['type'] }}" name="{{ $field['name'] }}"
                                       value="{{ old($field['name'], $record[$field['name']] ?? '') }}"
                                       placeholder="{{ $field['placeholder'] }}"
                                       class="h-9 w-full rounded-md border border-gray-200 bg-white px-3 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            @endif

                            @error($field['name'])
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="no-print mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-gray-100 pt-5">
            <a href="{{ route('entities', ['tab' => $returnTab]) }}"
               class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancel
            </a>
            <button type="submit"
                    class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-6 0V3h6v4m-6 0h6"/>
                </svg>
                {{ $saveLabel }}
            </button>
        </div>
    </form>

@endsection
