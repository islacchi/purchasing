@extends('layouts.app')

@section('page-title', 'PROJECTS')
@section('title', isset($project) && $project ? 'Edit Project - Purchasing' : 'Add Project - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // The same view serves both Add and Edit. Edit receives a persisted
        // Project model; Add receives null.
        $isEdit = isset($project) && $project !== null;
        $entities = $entities ?? collect();

        $selectedEntityId = (string) old('procuring_entity_id', $project?->procuring_entity_id ?? '');
        $title = old('title', $project?->title ?? '');
        $referenceNo = old('reference_no', $project?->reference_no ?? '');
        $amountAwarded = old('amount_awarded', $project?->amount_awarded ?? '');
        $deliveryDays = (string) old('delivery_days', $project?->delivery_days ?? '');
        $dateAwarded = old('date_awarded', $project?->date_awarded?->format('Y-m-d') ?? '');
        $modeOfProcurement = old('mode_of_procurement', $project?->mode_of_procurement ?? '');
        $notes = old('notes', $project?->notes ?? '');

        $items = old('items');

        if ($items === null) {
            if ($isEdit && $project->items->isNotEmpty()) {
                $items = $project->items
                    ->map(fn ($projectItem) => [
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
                $items = [
                    ['description' => '', 'category' => '', 'unit' => '', 'quantity' => '', 'in_stock' => '', 'unit_cost' => '', 'quoted_price' => ''],
                ];
            }
        }
    @endphp

    <div class="flex h-full min-h-0 flex-col">
    <h2 class="shrink-0 text-xl font-semibold text-gray-900 mb-6">{{ $isEdit ? 'Edit project' : 'Add project' }}</h2>

    @if ($errors->any())
        <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $isEdit ? route('projects.update', ['project' => $project->id]) : route('projects.store') }}" method="POST" class="flex flex-1 min-h-0 flex-col">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif
        {{-- Content holder: project details stay static; only the items table scrolls --}}
        <div class="flex flex-1 min-h-0 flex-col space-y-6 overflow-auto rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow-sm">
        {{-- Project fields --}}
        <div class="grid shrink-0 grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Title</label>
                    <input type="text" name="title" value="{{ $title }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    @error('title')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Reference no.</label>
                    <input type="text" name="reference_no" value="{{ $referenceNo }}"
                           class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    @error('reference_no')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1.5">Amount awarded</label>
                        <input type="number" step="0.01" name="amount_awarded" value="{{ $amountAwarded }}"
                               class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-800 mb-1.5">Delivery period</label>
                        <select name="delivery_days"
                                class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            <option value="" {{ $deliveryDays === '' ? 'selected' : '' }}>Select...</option>
                            <option value="7" {{ $deliveryDays === '7' ? 'selected' : '' }}>7 days</option>
                            <option value="15" {{ $deliveryDays === '15' ? 'selected' : '' }}>15 days</option>
                            <option value="30" {{ $deliveryDays === '30' ? 'selected' : '' }}>30 days</option>
                            <option value="45" {{ $deliveryDays === '45' ? 'selected' : '' }}>45 days</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Date awarded</label>
                        <div class="relative">
                            <input type="date" name="date_awarded" value="{{ $dateAwarded }}"
                                   class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Mode of procurement</label>
                        <select name="mode_of_procurement"
                                class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            <option value="" {{ $modeOfProcurement === '' ? 'selected' : '' }}>Select...</option>
                            <option value="shopping" {{ $modeOfProcurement === 'shopping' ? 'selected' : '' }}>Shopping</option>
                            <option value="small_value" {{ $modeOfProcurement === 'small_value' ? 'selected' : '' }}>Small value</option>
                            <option value="competitive_bidding" {{ $modeOfProcurement === 'competitive_bidding' ? 'selected' : '' }}>Competitive bidding</option>
                            <option value="direct_contracting" {{ $modeOfProcurement === 'direct_contracting' ? 'selected' : '' }}>Direct contracting</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Entity</label>
                    <select name="procuring_entity_id"
                            class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">
                        <option value="">Select entity...</option>
                        @foreach ($entities as $entity)
                            <option value="{{ $entity->id }}" {{ $selectedEntityId === (string) $entity->id ? 'selected' : '' }}>
                                {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('procuring_entity_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @if ($entities->isEmpty())
                        <p class="mt-1 text-xs text-gray-500">
                            No procuring entities yet.
                            <a href="{{ route('entities.create', ['type' => 'procuring_entity']) }}" class="font-medium text-[#2a7a94] hover:underline">Add one first</a>.
                        </p>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">Notes</label>
                    <textarea name="notes" rows="7"
                              class="w-full px-3 py-2 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-300">{{ $notes }}</textarea>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="flex min-h-[220px] flex-1 flex-col">
            <label class="shrink-0 block text-sm font-medium text-gray-800 mb-2">Items</label>

                <div id="itemRows" class="min-h-0 overflow-y-auto border border-gray-300 rounded-lg">
                <div class="sticky top-0 z-10 grid grid-cols-[2fr_1fr_0.8fr_0.8fr_1fr_1fr_1fr_28px] gap-3 bg-gray-100 px-3 py-2">
                    <div class="text-xs font-semibold text-gray-700">Description</div>
                    <div class="text-xs font-semibold text-gray-700">Category</div>
                    <div class="text-xs font-semibold text-gray-700">Unit</div>
                    <div class="text-xs font-semibold text-gray-700">Quantity</div>
                    <div class="text-xs font-semibold text-gray-700">Unit cost</div>
                    <div class="text-xs font-semibold text-gray-700">Quoted price</div>
                    <div class="text-xs font-semibold text-gray-700">In Stocks</div>
                    <div></div>
                </div>
                @foreach ($items as $i => $item)
                    <div class="item-row grid grid-cols-[2fr_1fr_0.8fr_0.8fr_1fr_1fr_1fr_28px] gap-3 items-center border-t border-gray-300 bg-white px-3 py-2">
                        <div>
                            <input type="text" name="items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <select name="items[{{ $i }}][category]" class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                                <option value="" {{ empty($item['category']) ? 'selected' : '' }}>Select...</option>
                                <option value="drugs and medicines" {{ ($item['category'] ?? '') === 'drugs and medicines' ? 'selected' : '' }}>Drugs and medicines</option>
                                <option value="supplies and equipments" {{ ($item['category'] ?? '') === 'supplies and equipments' ? 'selected' : '' }}>Supplies and equipments</option>
                            </select>
                        </div>
                        <div>
                            <input type="text" name="items[{{ $i }}][unit]" value="{{ $item['unit'] ?? '' }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <input type="number" name="items[{{ $i }}][quantity]" value="{{ $item['quantity'] ?? '' }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <input type="number" step="0.01" name="items[{{ $i }}][unit_cost]" value="{{ $item['unit_cost'] ?? '' }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <input type="number" step="0.01" name="items[{{ $i }}][quoted_price]" value="{{ $item['quoted_price'] ?? '' }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        {{-- On-hand quantity; fully covered lines are fulfilled and are excluded from PO --}}
                        <div>
                            <input type="number" min="0" step="1" name="items[{{ $i }}][in_stock]" value="{{ $item['in_stock'] ?? '' }}"
                                   class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"
                                   placeholder="0">
                        </div>
                        <div class="checkbox-cell pb-2 hidden">
                            <input type="checkbox" class="item-select w-4 h-4 rounded border-gray-300">
                        </div>
                    </div>
                @endforeach
            </div>

        </div>


        {{-- Delete items confirmation modal --}}
        <div id="itemsDeleteModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="itemsDeleteTitle">
            <div id="itemsDeleteOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
                <div class="flex items-start gap-3 px-5 py-4">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-50">
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 id="itemsDeleteTitle" class="text-base font-bold text-gray-900">Delete items?</h3>
                        <p id="itemsDeleteSummary" class="mt-1 text-sm text-gray-500"></p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                    <button type="button" id="itemsDeleteCancel"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" id="itemsDeleteConfirm"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors cursor-pointer">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        {{-- Paste items from a spreadsheet (bulk add) --}}
        <div id="pasteItemsModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="pasteItemsTitle">
            <div id="pasteItemsOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative w-full max-w-3xl rounded-lg bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 id="pasteItemsTitle" class="text-base font-bold text-gray-900">Paste items</h3>
                        <p class="mt-0.5 text-xs text-gray-500">Copy rows from Excel / Google Sheets and paste them here.</p>
                    </div>
                    <button type="button" data-close-paste-modal aria-label="Close paste items"
                            class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3 px-5 py-4">
                    {{-- Step 1: paste the raw text --}}
                    <div id="pasteStep1">
                        <label for="pasteItemsText" class="block text-xs font-medium text-gray-600">Pasted rows</label>
                        <textarea id="pasteItemsText" rows="9" spellcheck="false"
                                  placeholder="Mefenamic 500mg tab&#9;Drugs and medicines&#9;box&#9;256&#9;10.00&#9;25.00&#10;Cetirizine 10mg tablet&#9;Drugs and medicines&#9;box&#9;230&#9;8.50&#9;20.00"
                                  class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-xs text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300"></textarea>
                        <div class="mt-1.5 flex items-center justify-between gap-2">
                            <p class="text-[11px] text-gray-500">Tab, comma, or semicolon separated — up to 200 rows. Quoted cells are supported.</p>
                            <p id="pasteRowCount" class="text-[11px] font-medium text-gray-400"></p>
                        </div>
                        <p id="pasteItemsError" class="hidden mt-1 text-xs font-medium text-red-600"></p>
                    </div>

                    {{-- Step 2: preview + map columns --}}
                    <div id="pasteStep2" class="hidden space-y-3">
                        <p class="text-xs font-medium text-gray-600">1. Check the preview &nbsp;·&nbsp; 2. Tell us which column is which</p>
                        <div class="max-h-40 overflow-auto rounded-md border border-gray-200">
                            <table id="pastePreviewTable" class="w-full border-collapse text-xs"></table>
                        </div>
                        <div id="pasteMappingList" class="grid grid-cols-1 gap-2 sm:grid-cols-2"></div>
                        <label id="pasteHeaderWrap" class="hidden select-none items-center gap-2 text-xs text-gray-600">
                            <input type="checkbox" id="pasteHeaderToggle" class="rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300" checked>
                            First row looks like a header — skip it
                        </label>
                        <p id="pasteSummary" class="text-xs text-gray-500"></p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-gray-100 px-5 py-3">
                    <button type="button" id="pasteCancelBtn"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button type="button" id="pasteBackBtn" class="hidden rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Back
                    </button>
                    <button type="button" id="pastePrimaryBtn"
                            class="rounded-md bg-[#2a7a94] px-5 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                        Check rows
                    </button>
                </div>
            </div>
        </div>

        {{-- Form actions --}}
        <div class="flex shrink-0 items-center justify-between gap-3 pt-2">
            <div class="flex items-center gap-3">
                <button type="button" id="addItemBtn"
                        class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:border-gray-200 disabled:text-gray-400 disabled:hover:bg-transparent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add item
                </button>
                <button type="button" id="pasteItemsBtn"
                        class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        title="Add many items by pasting rows copied from Excel or Google Sheets">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 13l2 2 4-4"/>
                    </svg>
                    Paste items
                </button>
                <button type="button" id="deleteItemBtn" class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:border-gray-200 disabled:text-gray-400 disabled:hover:bg-transparent">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete<span id="deleteItemCount"></span>
                </button>
                <button type="button" id="cancelSelectBtn" class="hidden flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
            </div>
            <div class="flex items-center gap-3">
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
        </div>
    </form>
    </div>

@endsection

@push('scripts')
<script>
    // Item row handling. Keys may become non-sequential when rows are removed;
    // the controller filters untouched rows before validating the items array.
    (function () {
        const rowsContainer = document.getElementById('itemRows');
        // Seed row HTML captured at load time so a fresh row can be rebuilt
        // even after every existing row has been deleted.
        const seedRow = rowsContainer.querySelector('.item-row');
        const seedRowHTML = seedRow ? seedRow.outerHTML : null;
        const addBtn = document.getElementById('addItemBtn');
        const deleteBtn = document.getElementById('deleteItemBtn');
        const cancelSelectBtn = document.getElementById('cancelSelectBtn');
        const deleteCount = document.getElementById('deleteItemCount');
        const modal = document.getElementById('itemsDeleteModal');
        const modalOverlay = document.getElementById('itemsDeleteOverlay');
        const modalCancel = document.getElementById('itemsDeleteCancel');
        const modalConfirm = document.getElementById('itemsDeleteConfirm');
        const modalSummary = document.getElementById('itemsDeleteSummary');

        // Selection (delete) mode: the item checkboxes are only shown after the
        // Delete button is pressed; Cancel exits the mode without deleting.
        let deleteMode = false;

        function tickedCheckboxes() {
            return rowsContainer.querySelectorAll('.item-select:checked');
        }

        function updateDeleteBtn() {
            const rows = rowsContainer.querySelectorAll('.item-row');
            const n = tickedCheckboxes().length;
            deleteCount.textContent = deleteMode ? ' (' + n + ')' : '';
            deleteBtn.disabled = deleteMode && n === 0;
            deleteBtn.title = ! deleteMode
                ? 'Select items to delete'
                : (n > 0 ? 'Delete ' + n + ' selected item' + (n > 1 ? 's' : '') : 'Tick items to delete');
            deleteBtn.classList.toggle('hidden', rows.length === 0);
        }

        function setDeleteMode(on) {
            deleteMode = on;
            rowsContainer.querySelectorAll('.item-select').forEach(function (cb) {
                cb.closest('.checkbox-cell').classList.toggle('hidden', ! deleteMode);
                if (! deleteMode) {
                    cb.checked = false;
                }
            });
            cancelSelectBtn.classList.toggle('hidden', ! deleteMode);
            updateDeleteBtn();
        }

        // Only allow adding another row when the last row is completely filled.
        function isRowFilled(row) {
            if (!row) return false;
            const fields = row.querySelectorAll('input, select');
            for (let i = 0; i < fields.length; i++) {
                const field = fields[i];
                if (field.classList.contains('item-select')) continue; // tick box is not completeness
                if ((field.name || '').indexOf('in_stock') !== -1) continue; // stock qty doesn't count as completeness
                if (String(field.value).trim() === '') return false;
            }
            return true;
        }

        function syncAddButton() {
            const rows = rowsContainer.querySelectorAll('.item-row');
            const lastRow = rows[rows.length - 1];
            const filled = rows.length === 0 || isRowFilled(lastRow);
            addBtn.disabled = !filled;
            addBtn.title = filled ? 'Add item' : 'Fill in the last row first';
        }

        rowsContainer.addEventListener('input', syncAddButton);
        rowsContainer.addEventListener('change', syncAddButton);

        rowsContainer.addEventListener('change', function (e) {
            if (e.target.classList && e.target.classList.contains('item-select')) {
                updateDeleteBtn();
            }
        });

        addBtn.addEventListener('click', function () {
            const rows = rowsContainer.querySelectorAll('.item-row');
            const newIndex = rows.length;

            let template;
            if (rows.length > 0) {
                template = rows[0].cloneNode(true);
            } else if (seedRowHTML) {
                const temp = document.createElement('div');
                temp.innerHTML = seedRowHTML;
                template = temp.firstElementChild;
            } else {
                return;
            }

            template.querySelectorAll('input[type="text"], input[type="number"]').forEach(function (input) {
                input.value = '';
                input.name = input.name.replace(/items\[\d+\]/, `items[${newIndex}]`);
            });
            const categorySelect = template.querySelector('select');
            if (categorySelect) {
                categorySelect.value = '';
                categorySelect.name = `items[${newIndex}][category]`;
            }
            template.querySelector('.item-select').checked = false;

            rowsContainer.appendChild(template);
            setDeleteMode(deleteMode);
            syncAddButton();
            updateItemListHeight();
        });

        // Keep the item list height content-driven: only expand when an item
        // row actually has data; collapse back when nothing is entered yet.
        function updateItemListHeight() {
            const box = document.getElementById('itemRows');
            const rows = box.querySelectorAll('.item-row');
            let hasContent = false;
            for (let i = 0; i < rows.length && ! hasContent; i++) {
                const row = rows[i];
                const fields = row.querySelectorAll('input, select');
                for (let j = 0; j < fields.length; j++) {
                    const field = fields[j];
                    if (field.classList.contains('item-select')) continue;
                    if (String(field.value || '').trim() !== '') { hasContent = true; break; }
                }
            }
            box.classList.toggle('flex-1', hasContent);
        }

        function openDeleteModal() {
            const n = tickedCheckboxes().length;
            modalSummary.textContent =
                n + ' selected item' + (n > 1 ? 's' : '') + ' will be removed from this project. This action cannot be undone.';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            modalConfirm.focus();
        }

        function closeDeleteModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        modalCancel.addEventListener('click', closeDeleteModal);
        modalOverlay.addEventListener('click', closeDeleteModal);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDeleteModal();
        });

        deleteBtn.addEventListener('click', function () {
            if (! deleteMode) {
                setDeleteMode(true);
                return;
            }
            if (tickedCheckboxes().length > 0) {
                openDeleteModal();
            }
        });

        cancelSelectBtn.addEventListener('click', function () {
            setDeleteMode(false);
        });

        modalConfirm.addEventListener('click', function () {
            rowsContainer.querySelectorAll('.item-row').forEach(function (row) {
                const checkbox = row.querySelector('.item-select');
                if (checkbox && checkbox.checked) {
                    row.remove();
                }
            });
            closeDeleteModal();
            setDeleteMode(false);
            syncAddButton();
            updateDeleteBtn();
            updateItemListHeight();
        });

        updateDeleteBtn();
        syncAddButton();
        updateItemListHeight();

        // Exposed for the bulk-paste module.
        window.__items = {
            setDeleteMode: setDeleteMode,
            updateDeleteBtn: updateDeleteBtn,
            syncAddButton: syncAddButton,
            updateItemListHeight: updateItemListHeight,
        };
    })();
</script>
@endpush

    @push('scripts')
    <script>
        // Bulk paste: paste rows copied from Excel / Google Sheets, map the
        // detected columns, and append them as ordinary editor rows. Works in
        // both create and edit modes; never touches existing rows.
        (function () {
            'use strict';

            var openBtn = document.getElementById('pasteItemsBtn');
            var modal = document.getElementById('pasteItemsModal');
            if (!openBtn || !modal) {
                return;
            }

            var overlay = document.getElementById('pasteItemsOverlay');
            var cancelBtn = document.getElementById('pasteCancelBtn');
            var backBtn = document.getElementById('pasteBackBtn');
            var primaryBtn = document.getElementById('pastePrimaryBtn');
            var closeButtons = modal.querySelectorAll('[data-close-paste-modal]');
            var step1 = document.getElementById('pasteStep1');
            var step2 = document.getElementById('pasteStep2');
            var textArea = document.getElementById('pasteItemsText');
            var errorEl = document.getElementById('pasteItemsError');
            var rowCountEl = document.getElementById('pasteRowCount');
            var previewTable = document.getElementById('pastePreviewTable');
            var mappingList = document.getElementById('pasteMappingList');
            var summaryEl = document.getElementById('pasteSummary');
            var headerWrap = document.getElementById('pasteHeaderWrap');
            var headerToggle = document.getElementById('pasteHeaderToggle');
            var rowsContainer = document.getElementById('itemRows');
            var itemsApi = window.__items;

            var MAX_ROWS = 200;

            var FIELD_OPTIONS = [
                { value: '', label: 'Ignore column' },
                { value: 'description', label: 'Description' },
                { value: 'category', label: 'Category' },
                { value: 'unit', label: 'Unit' },
                { value: 'quantity', label: 'Quantity' },
                { value: 'unit_cost', label: 'Unit cost' },
                { value: 'quoted_price', label: 'Quoted price' },
            ];

            var HEADER_HINTS = {
                description: ['desc', 'description', 'item', 'item name', 'itemname', 'name', 'product', 'medicine', 'medication', 'particular', 'particulars', 'drug'],
                category: ['category', 'cat', 'type', 'class', 'section', 'group'],
                unit: ['unit', 'uom', 'unit of measure', 'unitofmeasure', 'pack', 'form'],
                quantity: ['qty', 'qty.', 'quantity', 'reqd qty', 'requested', 'qty requested', 'needed', 'required qty', 'pcs', 'number'],
                unit_cost: ['unit cost', 'unitcost', 'cost', 'unit price', 'price', 'base cost', 'actual cost'],
                quoted_price: ['quoted price', 'quotedprice', 'quoted', 'awarded price', 'awarded', 'supplier price', 'bid price', 'budget'],
            };

            // Parsed paste state; null until "Check rows" runs.
            var parsed = null;

            function escapeHtml(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
            }

            function normalizeHeaderKey(value) {
                return String(value).toLowerCase().replace(/[^a-z0-9 ]/g, ' ').replace(/\s+/g, ' ').trim();
            }

            // Strict: the whole cell (currency chars removed) must be a number.
            function parseNumber(value) {
                if (value === null || value === undefined) {
                    return null;
                }
                var s = String(value)
                    .replace(/₱/g, '')
                    .replace(/php|ph\$/gi, '')
                    .replace(/,/g, '')
                    .replace(/\s/g, '');
                if (!/^-?\d+(\.\d+)?$/.test(s)) {
                    return null;
                }
                var n = parseFloat(s);
                return isFinite(n) ? n : null;
            }

            // Lenient: leading number wins ("256 boxes" → 256) — only used for the
            // Quantity column, where trailing unit words are expected.
            function parseQuantity(value) {
                if (value === null || value === undefined) {
                    return null;
                }
                var s = String(value).replace(/,/g, '').replace(/\s/g, '');
                var match = s.match(/-?\d+(\.\d+)?/);
                if (!match) {
                    return null;
                }
                var i = Math.floor(parseFloat(match[0]));
                return i >= 1 ? i : null;
            }

            function normalizeCategory(value) {
                var s = String(value == null ? '' : value).toLowerCase();
                if (!s.trim()) {
                    return null;
                }
                if (/(drug|medicine|meds|pharma|otc)/.test(s)) {
                    return 'drugs and medicines';
                }
                if (/(supply|equip|supplies)/.test(s)) {
                    return 'supplies and equipments';
                }
                return null;
            }

            function parseLine(line, delimiter) {
                var cells = [];
                var current = '';
                var inQuotes = false;
                for (var i = 0; i < line.length; i++) {
                    var ch = line.charAt(i);
                    if (inQuotes) {
                        if (ch === '"') {
                            if (line.charAt(i + 1) === '"') {
                                current += '"';
                                i++;
                            } else {
                                inQuotes = false;
                            }
                        } else {
                            current += ch;
                        }
                    } else if (ch === '"') {
                        inQuotes = true;
                    } else if (ch === delimiter) {
                        cells.push(current.trim());
                        current = '';
                    } else {
                        current += ch;
                    }
                }
                cells.push(current.trim());
                return cells;
            }

            function parseSpreadsheet(text) {
                var lines = String(text).replace(/\r\n?/g, '\n').split('\n');
                var firstLine = null;
                for (var i = 0; i < lines.length; i++) {
                    if (lines[i].trim() !== '') {
                        firstLine = lines[i];
                        break;
                    }
                }
                var candidates = ['\t', ';', ','];
                var delimiter = '\t';
                var bestCount = -1;
                candidates.forEach(function (d) {
                    var count = firstLine ? firstLine.split(d).length - 1 : 0;
                    if (count > bestCount) {
                        bestCount = count;
                        delimiter = d;
                    }
                });
                var rows = [];
                for (var j = 0; j < lines.length; j++) {
                    if (lines[j].trim() === '') {
                        continue;
                    }
                    rows.push(parseLine(lines[j], delimiter));
                }
                return rows;
            }

            // Returns column hints like [{index: 0, field: 'description'}, ...] when
            // the first line looks like a header row, otherwise null.
            function detectHeader(row) {
                var hints = [];
                for (var i = 0; i < row.length; i++) {
                    var key = normalizeHeaderKey(row[i]);
                    if (!key) {
                        continue;
                    }
                    for (var field in HEADER_HINTS) {
                        if (HEADER_HINTS.hasOwnProperty(field) && HEADER_HINTS[field].indexOf(key) !== -1) {
                            hints.push({ index: i, field: field });
                            break;
                        }
                    }
                }
                return hints.length >= 2 ? hints : null;
            }

            function cellIsNumeric(value) {
                return parseNumber(value) !== null;
            }

            function cellIsInteger(value) {
                var n = parseNumber(value);
                return n !== null && Math.abs(n - Math.floor(n)) < 0.0001;
            }

            // Guess a column → field mapping from header hints + content shape.
            function guessMapping(rows, headerHints) {
                var cols = rows.reduce(function (max, r) { return Math.max(max, r.length); }, 0);
                var mapping = [];
                var used = {};
                for (var i = 0; i < cols; i++) {
                    mapping.push('');
                }

                if (headerHints) {
                    headerHints.forEach(function (hint) {
                        if (hint.index < cols && !used[hint.field]) {
                            mapping[hint.index] = hint.field;
                            used[hint.field] = true;
                        }
                    });
                }

                var stats = [];
                for (var c = 0; c < cols; c++) {
                    var numeric = 0, integer = 0, category = 0, textLike = 0, filled = 0;
                    rows.forEach(function (row) {
                        var cell = row[c];
                        if (cell === undefined || String(cell).trim() === '') {
                            return;
                        }
                        filled++;
                        if (cellIsNumeric(cell)) {
                            numeric++;
                            if (cellIsInteger(cell)) {
                                integer++;
                            }
                        }
                        if (normalizeCategory(cell) !== null) {
                            category++;
                        }
                        if (/[a-zA-Z]/.test(cell) && !cellIsNumeric(cell)) {
                            textLike++;
                        }
                    });
                    stats.push({ numeric: numeric, integer: integer, category: category, textLike: textLike, filled: filled });
                }

                var i;
                for (i = 0; i < cols; i++) {
                    if (mapping[i] !== '') {
                        continue;
                    }
                    if (stats[i].filled > 0 && stats[i].category === stats[i].filled && !used.category) {
                        mapping[i] = 'category';
                        used.category = true;
                    }
                }
                for (i = 0; i < cols; i++) {
                    if (mapping[i] !== '') {
                        continue;
                    }
                    if (stats[i].filled > 0 && stats[i].integer === stats[i].filled && !used.quantity) {
                        mapping[i] = 'quantity';
                        used.quantity = true;
                    }
                }
                for (i = 0; i < cols; i++) {
                    if (mapping[i] !== '') {
                        continue;
                    }
                    if (stats[i].filled > 0 && stats[i].numeric === stats[i].filled && !used.unit_cost) {
                        mapping[i] = 'unit_cost';
                        used.unit_cost = true;
                    }
                }
                for (i = 0; i < cols; i++) {
                    if (mapping[i] !== '') {
                        continue;
                    }
                    if (stats[i].filled > 0 && stats[i].numeric === stats[i].filled && !used.quoted_price) {
                        mapping[i] = 'quoted_price';
                        used.quoted_price = true;
                    }
                }
                for (i = 0; i < cols; i++) {
                    if (mapping[i] !== '') {
                        continue;
                    }
                    if (stats[i].textLike > 0 && !used.description) {
                        mapping[i] = 'description';
                        used.description = true;
                    }
                }
                for (i = 0; i < cols; i++) {
                    if (mapping[i] !== '') {
                        continue;
                    }
                    if (stats[i].filled > 0 && stats[i].textLike === stats[i].filled && !used.unit && stats[i].numeric === 0) {
                        mapping[i] = 'unit';
                        used.unit = true;
                    }
                }

                return mapping;
            }

            // First column mapped to `field` wins.
            function valueFor(cells, mapping, field) {
                for (var i = 0; i < mapping.length; i++) {
                    if (mapping[i] === field && cells[i] !== undefined && String(cells[i]).trim() !== '') {
                        return cells[i];
                    }
                }
                return '';
            }

            function rowStatus(cells, mapping) {
                var problems = [];
                var description = String(valueFor(cells, mapping, 'description') || '').trim();
                var categoryRaw = String(valueFor(cells, mapping, 'category') || '').trim();
                var category = normalizeCategory(valueFor(cells, mapping, 'category'));
                var unit = String(valueFor(cells, mapping, 'unit') || '').trim();
                var quantity = parseQuantity(valueFor(cells, mapping, 'quantity'));
                var cost = parseNumber(valueFor(cells, mapping, 'unit_cost'));
                var quotedRaw = String(valueFor(cells, mapping, 'quoted_price') || '').trim();
                var quoted = parseNumber(valueFor(cells, mapping, 'quoted_price'));

                if (!description) {
                    problems.push('Description is required');
                }
                if (!categoryRaw) {
                    problems.push('Category is required');
                } else if (!category) {
                    problems.push('Category not recognized');
                }
                if (!unit) {
                    problems.push('Unit is required');
                }
                if (quantity === null) {
                    problems.push('Quantity must be a whole number ≥ 1');
                }
                if (cost === null || cost < 0) {
                    problems.push('Unit cost is required');
                }
                if (quotedRaw !== '' && (quoted === null || quoted < 0)) {
                    problems.push('Quoted price must be a number ≥ 0 (or left blank)');
                }

                return {
                    problems: problems,
                    values: {
                        description: description,
                        category: category,
                        unit: unit,
                        quantity: quantity,
                        unit_cost: cost,
                        quoted_price: quoted,
                    },
                };
            }

            function countStatuses(rows, mapping) {
                var flagged = 0;
                rows.forEach(function (cells) {
                    if (rowStatus(cells, mapping).problems.length) {
                        flagged++;
                    }
                });
                return { flagged: flagged, clean: rows.length - flagged };
            }

            function setVisible(el, visible) {
                el.classList.toggle('hidden', !visible);
            }

            function showError(message) {
                errorEl.textContent = message;
                setVisible(errorEl, true);
            }

            function hideError() {
                setVisible(errorEl, false);
            }

            function setStep(step) {
                setVisible(step1, step === 1);
                setVisible(step2, step === 2);
                setVisible(backBtn, step === 2);
                primaryBtn.textContent = step === 1
                    ? 'Check rows'
                    : 'Add ' + (parsed ? parsed.rows.length : 0) + ' row' + ((parsed && parsed.rows.length === 1) ? '' : 's');
            }

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                parsed = null;
                textArea.value = '';
                previewTable.innerHTML = '';
                mappingList.innerHTML = '';
                summaryEl.textContent = '';
                rowCountEl.textContent = '';
                hideError();
                setStep(1);
                textArea.focus();
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            function updateRowCount() {
                var n = parseSpreadsheet(textArea.value).length;
                rowCountEl.textContent = n ? n + ' row' + (n === 1 ? '' : 's') : '';
            }

            function refreshSummary() {
                var status = countStatuses(parsed.rows, parsed.mapping);
                var descMapped = parsed.mapping.indexOf('description') !== -1;
                summaryEl.textContent = parsed.rows.length + ' row' + (parsed.rows.length === 1 ? '' : 's')
                    + ' · ' + status.clean + ' clean · ' + status.flagged + ' flagged (fix red cells after adding)';
                primaryBtn.textContent = 'Add ' + parsed.rows.length + ' row' + (parsed.rows.length === 1 ? '' : 's');
                primaryBtn.disabled = !descMapped;
                if (!descMapped) {
                    summaryEl.textContent += ' · Map a Description column to continue';
                }
            }

            function buildStep2() {
                var cols = parsed.mapping.length;
                var html = '<thead><tr class="bg-gray-50">';
                for (var i = 0; i < cols; i++) {
                    html += '<th class="border border-gray-200 px-2 py-1 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-500">Col ' + (i + 1) + '</th>';
                }
                html += '</tr></thead><tbody>';
                parsed.rows.slice(0, 4).forEach(function (row) {
                    html += '<tr>';
                    for (var i = 0; i < cols; i++) {
                        html += '<td class="whitespace-nowrap border border-gray-200 px-2 py-1 text-gray-700">' + escapeHtml(row[i] || '') + '</td>';
                    }
                    html += '</tr>';
                });
                html += '</tbody>';
                previewTable.innerHTML = html;

                mappingList.innerHTML = '';
                for (var i = 0; i < cols; i++) {
                    (function (index) {
                        var wrap = document.createElement('label');
                        wrap.className = 'flex items-center gap-2 rounded-md border border-gray-200 px-2 py-1.5 text-xs text-gray-700';
                        var tag = document.createElement('span');
                        tag.className = 'w-12 shrink-0 font-semibold uppercase tracking-wider text-gray-400';
                        tag.textContent = 'Col ' + (index + 1);
                        var select = document.createElement('select');
                        select.className = 'w-full rounded border border-gray-200 bg-white px-2 py-1 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300';
                        FIELD_OPTIONS.forEach(function (opt) {
                            var o = document.createElement('option');
                            o.value = opt.value;
                            o.textContent = opt.label;
                            select.appendChild(o);
                        });
                        select.value = parsed.mapping[index] || '';
                        select.addEventListener('change', function () {
                            parsed.mapping[index] = select.value;
                            refreshSummary();
                        });
                        wrap.appendChild(tag);
                        wrap.appendChild(select);
                        mappingList.appendChild(wrap);
                    })(i);
                }

                refreshSummary();

                // Header toggle: only for pastes whose first line looked like a header.
                var hasHeader = !!parsed.header;
                headerWrap.classList.toggle('hidden', !hasHeader);
                headerWrap.classList.toggle('flex', hasHeader);
                if (hasHeader && headerToggle) {
                    headerToggle.checked = parsed.skipHeader;
                }
            }

            function applyHeaderSkip() {
                parsed.skipHeader = headerToggle.checked;
                parsed.rows = parsed.skipHeader ? parsed.allRows.slice(1) : parsed.allRows;
                parsed.mapping = guessMapping(parsed.rows, parsed.header);
                buildStep2();
                setStep(2);
            }

            function checkRows() {
                var raw = textArea.value;
                if (!raw.trim()) {
                    showError('Paste some rows first.');
                    return;
                }
                var rows = parseSpreadsheet(raw);
                if (!rows.length) {
                    showError('Paste some rows first.');
                    return;
                }
                if (rows.length > MAX_ROWS) {
                    showError('That is ' + rows.length + ' rows — paste up to ' + MAX_ROWS + ' at a time.');
                    return;
                }
                // First line is skipped if it looks like a header; the toggle in
                // step 2 lets the user include it again.
                var header = detectHeader(rows[0]);
                parsed = header
                    ? { allRows: rows, skipHeader: true, header: header, rows: rows.slice(1) }
                    : { allRows: rows, skipHeader: false, header: null, rows: rows };
                parsed.mapping = guessMapping(parsed.rows, header);
                if (!parsed.rows.length) {
                    showError('No data rows detected — the header was the only line.');
                    return;
                }
                hideError();
                buildStep2();
                setStep(2);
            }

            function seedRowTemplate() {
                var existing = rowsContainer.querySelector('.item-row');
                if (existing) {
                    return existing.cloneNode(true);
                }
                var wrapper = document.createElement('div');
                wrapper.innerHTML = '<div class="item-row grid grid-cols-[2fr_1fr_0.8fr_0.8fr_1fr_1fr_1fr_28px] gap-3 items-center border-t border-gray-300 bg-white px-3 py-2"><div><input type="text" name="items[0][description]" value="" class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"></div><div><select name="items[0][category]" class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"><option value="">Select...</option><option value="drugs and medicines">Drugs and medicines</option><option value="supplies and equipments">Supplies and equipments</option></select></div><div><input type="text" name="items[0][unit]" value="" class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"></div><div><input type="number" name="items[0][quantity]" value="" class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"></div><div><input type="number" step="0.01" name="items[0][unit_cost]" value="" class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"></div><div><input type="number" step="0.01" name="items[0][quoted_price]" value="" class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"></div><div><input type="number" min="0" step="1" name="items[0][in_stock]" value="" class="w-full px-2.5 py-1.5 rounded-md border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300" placeholder="0"></div><div class="checkbox-cell pb-2 hidden"><input type="checkbox" class="item-select w-4 h-4 rounded border-gray-300"></div></div>';
                return wrapper.firstElementChild;
            }

            function clearFlag(el) {
                el.style.borderColor = '';
                el.style.boxShadow = '';
                el.title = '';
            }

            function flagField(el, message) {
                el.style.borderColor = '#f87171';
                el.style.boxShadow = 'inset 0 0 0 1px #f87171';
                el.title = message;
            }

            function control(el, ok, problems, fallback) {
                if (ok) {
                    clearFlag(el);
                } else {
                    flagField(el, problems.length ? problems.join(' · ') : fallback);
                }
            }

            function insertRows() {
                var mapping = parsed.mapping;

                parsed.rows.forEach(function (cells) {
                    var status = rowStatus(cells, mapping);
                    var template = seedRowTemplate();
                    var newIndex = rowsContainer.querySelectorAll('.item-row').length;

                    template.querySelectorAll('input, select').forEach(function (field) {
                        if (field.name) {
                            field.name = field.name.replace(/items\[\d+\]/, 'items[' + newIndex + ']');
                        }
                        if (field.classList.contains('item-select')) {
                            field.checked = false;
                        }
                    });

                    var descriptionField = template.querySelector('[name$="[description]"]');
                    descriptionField.value = status.values.description;
                    control(descriptionField, status.values.description !== '', status.problems, 'Description is required');

                    var categoryField = template.querySelector('[name$="[category]"]');
                    categoryField.value = status.values.category || '';
                    control(categoryField, !!status.values.category, status.problems, 'Category not recognized');

                    var unitField = template.querySelector('[name$="[unit]"]');
                    unitField.value = status.values.unit;
                    control(unitField, status.values.unit !== '', status.problems, 'Unit is required');

                    var quantityField = template.querySelector('[name$="[quantity]"]');
                    quantityField.value = status.values.quantity !== null ? String(status.values.quantity) : '';
                    control(quantityField, status.values.quantity !== null, status.problems, 'Quantity must be a whole number ≥ 1');

                    var costField = template.querySelector('[name$="[unit_cost]"]');
                    costField.value = status.values.unit_cost !== null ? Number(status.values.unit_cost).toFixed(2) : '';
                    control(costField, status.values.unit_cost !== null && status.values.unit_cost >= 0, status.problems, 'Unit cost is required');

                    var quotedField = template.querySelector('[name$="[quoted_price]"]');
                    quotedField.value = status.values.quoted_price !== null ? Number(status.values.quoted_price).toFixed(2) : '';
                    var quotedRaw = String(valueFor(cells, mapping, 'quoted_price') || '').trim();
                    control(quotedField, quotedRaw === '' || (status.values.quoted_price !== null && status.values.quoted_price >= 0), status.problems, 'Quoted price must be a number ≥ 0');

                    // Stock stays at zero on paste.
                    var stockField = template.querySelector('[name$="[in_stock]"]');
                    if (stockField) {
                        stockField.value = '';
                    }

                    var tick = template.querySelector('.item-select');
                    if (tick) {
                        tick.checked = false;
                    }
                    rowsContainer.appendChild(template);
                });

                if (itemsApi) {
                    itemsApi.syncAddButton();
                    itemsApi.updateItemListHeight();
                }

                closeModal();
            }

            openBtn.addEventListener('click', openModal);
            cancelBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', closeModal);
            closeButtons.forEach(function (btn) {
                btn.addEventListener('click', closeModal);
            });
            backBtn.addEventListener('click', function () {
                hideError();
                setStep(1);
            });
            if (headerToggle) {
                headerToggle.addEventListener('change', applyHeaderSkip);
            }

            textArea.addEventListener('input', updateRowCount);

            primaryBtn.addEventListener('click', function () {
                if (step2.classList.contains('hidden')) {
                    checkRows();
                } else {
                    insertRows();
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
                if (e.key === 'Enter' && modal.classList.contains('flex') && e.target !== textArea) {
                    e.preventDefault();
                    primaryBtn.click();
                }
            });

            // Clear a flag as soon as the user starts fixing the field.
            rowsContainer.addEventListener('input', function (e) {
                if (e.target && e.target.style && e.target.style.borderColor) {
                    clearFlag(e.target);
                }
            });
            rowsContainer.addEventListener('change', function (e) {
                if (e.target && e.target.style && e.target.style.borderColor) {
                    clearFlag(e.target);
                }
            });
        })();
    </script>
@endpush