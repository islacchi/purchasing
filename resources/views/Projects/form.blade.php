@extends('layouts.app')

@section('page-title', 'PROJECTS')
@section('title', 'Add Project - Purchasing')

{{-- @TODO: This same view will serve both Add and Edit.
     When database is integrated:
     - Route::get('/projects/create', ...) passes no $project
     - Route::get('/projects/{project}/edit', ...) passes $project = Project::with('items')->findOrFail($id)
     - Heading, submit label, and form action should switch based on isset($project)
--}}

@section('header-actions')
@endsection

@section('content')

    @php
        // Determine if we're in edit mode based on the route parameter
        $isEdit = isset($id) && $id !== null;

        if ($isEdit) {
            // Pre-filled dummy data for edit mode
            $project = [
                'title' => 'Purchase of pharma supplies',
                'entity' => 'DOH Region VII',
                'reference_no' => 'DOH Region VII - RF-2026-0042',
                'notes' => 'Quarterly procurement of pharmaceutical supplies for regional health facilities.',
                'amount_awarded' => 482000.00,
                'delivery_period' => '30',
                'date_awarded' => '2026-07-03',
                'mode_of_procurement' => 'small_value',
            ];

            $items = [
                [
                    'description' => 'Mefenamic Capsule - 500mg',
                    'unit' => 'Box',
                    'quantity' => 50,
                    'unit_cost' => 320.00,
                    'quoted_price' => 305.00,
                ],
                [
                    'description' => 'Amoxicillin 250mg suspension',
                    'unit' => 'Box',
                    'quantity' => 50,
                    'unit_cost' => 320.00,
                    'quoted_price' => 305.00,
                ],
                [
                    'description' => 'Paracetamol 500mg tablet',
                    'unit' => 'Box',
                    'quantity' => 100,
                    'unit_cost' => 150.00,
                    'quoted_price' => 140.00,
                ],
            ];
        } else {
            // Empty data for create mode
            $project = [
                'title' => '',
                'entity' => '',
                'reference_no' => '',
                'notes' => '',
                'amount_awarded' => '',
                'delivery_period' => '',
                'date_awarded' => '',
                'mode_of_procurement' => '',
            ];

            $items = [
                ['description' => '', 'unit' => '', 'quantity' => '', 'unit_cost' => '', 'quoted_price' => ''],
            ];
        }
    @endphp

    <h2 class="text-xl font-semibold text-gray-900 mb-6">{{ $isEdit ? 'Edit project' : 'Add project' }}</h2>

    {{-- @TODO: wire this up to a real form once routes/controller exist --}}
    <form action="#" method="POST" class="space-y-8">
        @csrf

        {{-- Project fields --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-5">
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Title</label>
                    <input type="text" name="title" value="{{ $project['title'] }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Reference no.</label>
                    <input type="text" name="reference_no" value="{{ $project['reference_no'] }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1.5">Amount awarded</label>
                        <input type="number" step="0.01" name="amount_awarded" value="{{ $project['amount_awarded'] }}"
                               class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1.5">Delivery period</label>
                        <select name="delivery_period"
                                class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            <option value="" {{ $project['delivery_period'] === '' ? 'selected' : '' }}>Select...</option>
                            <option value="7" {{ $project['delivery_period'] === '7' ? 'selected' : '' }}>7 days</option>
                            <option value="15" {{ $project['delivery_period'] === '15' ? 'selected' : '' }}>15 days</option>
                            <option value="30" {{ $project['delivery_period'] === '30' ? 'selected' : '' }}>30 days</option>
                            <option value="45" {{ $project['delivery_period'] === '45' ? 'selected' : '' }}>45 days</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1.5">Date awarded</label>
                        <div class="relative">
                            <input type="date" name="date_awarded" value="{{ $project['date_awarded'] }}"
                                   class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1.5">Mode of procurement</label>
                        <select name="mode_of_procurement"
                                class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            <option value="" {{ $project['mode_of_procurement'] === '' ? 'selected' : '' }}>Select...</option>
                            <option value="shopping" {{ $project['mode_of_procurement'] === 'shopping' ? 'selected' : '' }}>Shopping</option>
                            <option value="small_value" {{ $project['mode_of_procurement'] === 'small_value' ? 'selected' : '' }}>Small value</option>
                            <option value="competitive_bidding" {{ $project['mode_of_procurement'] === 'competitive_bidding' ? 'selected' : '' }}>Competitive bidding</option>
                            <option value="direct_contracting" {{ $project['mode_of_procurement'] === 'direct_contracting' ? 'selected' : '' }}>Direct contracting</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Entity</label>
                    <input type="text" name="entity" value="{{ $project['entity'] }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Notes</label>
                    <textarea name="notes" rows="10"
                              class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">{{ $project['notes'] }}</textarea>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div>
            <label class="block text-sm font-medium text-gray-800 mb-2">Items</label>

                <div id="itemRows" class="border border-gray-300 rounded-lg overflow-hidden">
                <div class="grid grid-cols-[2fr_0.8fr_0.8fr_1fr_1fr_28px] gap-3 bg-gray-100 px-3 py-2">
                    <div class="text-xs font-semibold text-gray-700">Description</div>
                    <div class="text-xs font-semibold text-gray-700">Unit</div>
                    <div class="text-xs font-semibold text-gray-700">Quantity</div>
                    <div class="text-xs font-semibold text-gray-700">Unit cost</div>
                    <div class="text-xs font-semibold text-gray-700">Quoted price</div>
                    <div></div>
                </div>
                @foreach ($items as $i => $item)
                    <div class="item-row grid grid-cols-[2fr_0.8fr_0.8fr_1fr_1fr_28px] gap-3 items-center border-t border-gray-300 bg-white px-3 py-2">
                        <div>
                            <input type="text" name="items[{{ $i }}][description]" value="{{ $item['description'] }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <input type="text" name="items[{{ $i }}][unit]" value="{{ $item['unit'] }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <input type="number" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <input type="number" step="0.01" name="items[{{ $i }}][unit_cost]" value="{{ $item['unit_cost'] }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <input type="number" step="0.01" name="items[{{ $i }}][quoted_price]" value="{{ $item['quoted_price'] }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div class="pb-2">
                            <input type="checkbox" class="item-select w-4 h-4 rounded border-gray-300">
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-3 mt-3">
                <button type="button" id="addItemBtn"
                        class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add item
                </button>
                <button type="button" id="deleteItemBtn"
                        class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete
                </button>
            </div>
        </div>

        {{-- Form actions --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('projects') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancel
            </a>
            <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 rounded-md bg-[#0e5266] text-white text-sm font-medium hover:bg-[#0c4757]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-6 0V3h6v4m-6 0h6"/>
                </svg>
                {{ $isEdit ? 'Update project' : 'Save project' }}
            </button>
        </div>
    </form>

@endsection

@push('scripts')
<script>
    // @TODO: Frontend-only item row handling. Once DB is integrated, item index
    // numbering (items[N]) still needs to stay sequential when rows are removed,
    // or switch to Livewire's wire:model array binding instead of raw DOM cloning.
    (function () {
        const rowsContainer = document.getElementById('itemRows');
        const addBtn = document.getElementById('addItemBtn');
        const deleteBtn = document.getElementById('deleteItemBtn');

        addBtn.addEventListener('click', function () {
            const rows = rowsContainer.querySelectorAll('.item-row');
            const newIndex = rows.length;
            const template = rows[0].cloneNode(true);

            template.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (input) {
                input.value = '';
                input.name = input.name.replace(/items\[\d+\]/, `items[${newIndex}]`);
            });
            template.querySelector('.item-select').checked = false;

            rowsContainer.appendChild(template);
        });

        deleteBtn.addEventListener('click', function () {
            rowsContainer.querySelectorAll('.item-row').forEach(function (row) {
                const checkbox = row.querySelector('.item-select');
                if (checkbox.checked) {
                    row.remove();
                }
            });
        });
    })();
</script>
@endpush