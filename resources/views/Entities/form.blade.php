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
                    ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'placeholder' => 'Select type', 'options' => ['Regional Office', 'Government Hospital', 'Provincial Hospital']],
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
        $formTitle = ($isEdit ? 'EDIT ' : 'ADD ').$config['title'];
        $saveLabel = ($isEdit ? 'Save ' : 'Add ').$config['singular'];

        $formColumns = [];
        foreach ($config['fields'] as $field) {
            $formColumns[$field['column'] ?? 'left'][] = $field;
        }
        $formColumns = array_values($formColumns);
    @endphp

    {{-- Type label --}}
    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">{{ $typeLabel }}</p>

    <form action="{{ $isEdit ? route('entities.update', ['type' => $type, 'id' => $id]) : route('entities.store', ['type' => $type]) }}" method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <h2 class="text-xl font-bold text-gray-900 uppercase tracking-wide">{{ $formTitle }}</h2>

        <div class="{{ count($formColumns) > 1 ? 'grid grid-cols-1 lg:grid-cols-2 gap-x-10 gap-y-5 mt-6' : 'max-w-2xl mt-6' }}">
            @foreach ($formColumns as $columnFields)
                <div class="{{ $loop->first ? 'space-y-4' : 'flex flex-col' }}">
                    @foreach ($columnFields as $field)
                        <div>
                            <label for="field-{{ $field['name'] }}" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                {{ $field['label'] }}
                            </label>

                            @if ($field['type'] === 'select')
                                <select id="field-{{ $field['name'] }}" name="{{ $field['name'] }}"
                                        class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
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
                                          class="w-full min-h-[280px] px-3 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">{{ old($field['name'], $record[$field['name']] ?? '') }}</textarea>
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

    <div class="pb-4"></div>

@endsection
