@extends('layouts.app')

@section('page-title', 'PROJECTS')
@section('title', 'Add Project - Purchasing')

{{-- @TODO: This same view will serve both Add and Edit.
     When database is integrated:
     - Route::get('/projects/create', ...) passes no $project
     - Route::get('/projects/{project}/edit', ...) passes $project = Project::with('items')->findOrFail($id)
     - Swap the hardcoded $project / $items arrays below for the real model + relation
     - Heading, submit label, and form action should switch based on isset($project)
--}}

@section('header-actions')
@endsection

@section('content')

    {{-- Hard-coded data for frontend development --}}
    @php
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
            ['description' => '', 'unit' => '', 'quantity' => '', 'unit_cost' => '', 'quoted_price' => ''],
        ];
    @endphp

    <h2 class="text-xl font-semibold text-gray-900 mb-6">Add project</h2>

    {{-- @TODO: wire this up to a real form once routes/controller exist --}}
    <form action="#" method="POST" class="space-y-8">
        @csrf

        {{-- Project fields --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-800 mb-1.5">Title</label>
                <input type="text" name="title" value="{{ $project['title'] }}"
                       class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-800 mb-1.5">Entity</label>
                {{-- @TODO: replace with a select populated from App\Models\Entity once Entities module is wired up --}}
                <input type="text" name="entity" value="{{ $project['entity'] }}"
                       class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-800 mb-1.5">Reference no.</label>
                <input type="text" name="reference_no" value="{{ $project['reference_no'] }}"
                       class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-800 mb-1.5">Notes</label>
                <textarea name="notes" rows="4"
                          class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">{{ $project['notes'] }}</textarea>
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
                        <option value="">Select...</option>
                        <option value="7">7 days</option>
                        <option value="15">15 days</option>
                        <option value="30">30 days</option>
                        <option value="45">45 days</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Date awarded</label>
                    <input type="date" name="date_awarded" value="{{ $project['date_awarded'] }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Mode of procurement</label>
                    <select name="mode_of_procurement"
                            class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                        <option value="">Select...</option>
                        <option value="shopping">Shopping</option>
                        <option value="small_value">Small value</option>
                        <option value="competitive_bidding">Competitive bidding</option>
                        <option value="direct_contracting">Direct contracting</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div>
            <label class="block text-sm font-medium text-gray-800 mb-2">Items</label>

            <div id="itemRows" class="bg-gray-100 rounded-lg p-4 space-y-3">
                @foreach ($items as $i => $item)
                    <div class="item-row grid grid-cols-[2fr_0.8fr_0.8fr_1fr_1fr_28px] gap-3 items-end">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                            <input type="text" name="items[{{ $i }}][description]" value="{{ $item['description'] }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Unit</label>
                            <input type="text" name="items[{{ $i }}][unit]" value="{{ $item['unit'] }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                            <input type="number" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Unit cost</label>
                            <input type="number" step="0.01" name="items[{{ $i }}][unit_cost]" value="{{ $item['unit_cost'] }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Quoted price</label>
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
                Save project
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