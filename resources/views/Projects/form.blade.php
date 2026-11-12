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
        <div class="flex flex-1 min-h-0 flex-col space-y-6 overflow-hidden rounded-lg border border-gray-200 bg-white p-4 sm:p-6 shadow-sm">
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
        <div class="flex min-h-0 flex-1 flex-col">
            <label class="shrink-0 block text-sm font-medium text-gray-800 mb-2">Items</label>

                <div id="itemRows" class="overflow-y-auto border border-gray-300 rounded-lg">
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
            box.classList.toggle('min-h-[420px]', hasContent);
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
    })();
</script>
@endpush