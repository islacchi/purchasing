@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', 'Edit Prep List - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // ------------------------------------------------------------------
        // This single view serves both Edit and Add (compile) modes.
        //
        //   - Edit:  $prepList is set   → heading "Edit prep list", per-item
        //            reduce/waive controls, Save changes.
        //   - Add:   $prepList is NOT set → heading "Add prep list", plain
        //            quantities (no reduce/waive), Compile prep list button.
        //
        // @TODO: once models + migrations exist, replace the hardcoded arrays
        // below with real Eloquent queries and only keep the branch on mode:
        //   - Edit:  $prepList = Preplist::with(['projects', 'items'])->findOrFail($id)
        //   - Add:   pull available source projects with their items.
        $isEdit = isset($prepList) && $prepList !== null;

        if ($isEdit) {
            // ------------------------------------------------------------------
            // EDIT MODE — dummy data (reduced/waived states are meaningful here).
            // ------------------------------------------------------------------
            $projects = [
                [
                    'id'   => 1,
                    'name' => 'Pharma supplies Q3',
                    'reference' => 'RFQ 2026-0042',
                    'checked' => true,
                    'items' => [
                        ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true,  'original' => 50, 'reduced' => 40, 'unit' => 'box', 'waived' => false],
                        ['name' => 'Amoxicillin 500mg, box of 100',       'checked' => true,  'original' => 30, 'reduced' => 25, 'unit' => 'box', 'waived' => false],
                    ],
                ],
                [
                    'id'   => 2,
                    'name' => 'Laboratory reagents',
                    'reference' => 'RFQ 2026-0038',
                    'checked' => true,
                    'items' => [
                        ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true,  'original' => 40, 'reduced' => 30, 'unit' => 'box', 'waived' => false],
                        ['name' => 'Surgical gloves, box of 50 pairs',    'checked' => false, 'original' => 40, 'reduced' => 40, 'unit' => 'box', 'waived' => true],
                    ],
                ],
            ];

            // Summary counts derived from the dummy data above.
            // @TODO: recompute from DB once Eloquent is wired up.
            $selectedCount = 0;
            $waivedCount   = 0;
            foreach ($projects as $project) {
                foreach ($project['items'] as $item) {
                    if ($item['waived']) {
                        $waivedCount++;
                    }
                    if ($item['checked'] || $item['waived']) {
                        $selectedCount++;
                    }
                }
            }
            $projectCount = count($projects);
        } else {
            // ------------------------------------------------------------------
            // ADD (compile) MODE — dummy data. No reduce/waive: just checked
            // projects + items with plain quantities.
            // ------------------------------------------------------------------
            $projects = [
                [
                    'id'   => 1,
                    'name' => 'Pharma supplies Q3',
                    'reference' => 'RFQ 2026-0042',
                    'checked' => true,
                    'items' => [
                        ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true,  'qty' => 40, 'unit' => 'box'],
                        ['name' => 'Amoxicillin 500mg, box of 100',       'checked' => true,  'qty' => 40, 'unit' => 'box'],
                    ],
                ],
                [
                    'id'   => 2,
                    'name' => 'Laboratory reagents',
                    'reference' => 'RFQ 2026-0038',
                    'checked' => true,
                    'items' => [
                        ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true,  'qty' => 40, 'unit' => 'box'],
                        ['name' => 'Acetaminophen 500mg, box of 100',     'checked' => false, 'qty' => 40, 'unit' => 'box'],
                    ],
                ],
                [
                    'id'   => 3,
                    'name' => 'PPE bulk order',
                    'reference' => 'RFQ 2026-0038',
                    'checked' => false, // project entirely unselected
                    'items' => [
                        ['name' => 'Disposable masks, box of 50', 'checked' => false, 'qty' => 40, 'unit' => 'box'],
                    ],
                ],
            ];

            // Add-mode summary: no waiver text — count checked projects and
            // their checked items. "Waived" concept does not exist here.
            // @TODO: recompute from DB once Eloquent is wired up.
            $selectedCount = 0;
            $projectCount  = 0;
            foreach ($projects as $project) {
                if ($project['checked']) {
                    $projectCount++;
                    foreach ($project['items'] as $item) {
                        if ($item['checked']) {
                            $selectedCount++;
                        }
                    }
                }
            }
            $waivedCount = 0;
        }
    @endphp

    {{-- Page heading --}}
    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ isset($prepList) ? 'Edit prep list' : 'Add prep list' }}</h2>

    {{-- @TODO: wire up a real form action once routes/controller exist --}}
    <form action="#" method="POST" class="space-y-8">
        @csrf

        {{-- Prep list name --}}
        <div class="max-w-2xl">
            <label for="prepListName" class="block text-sm font-medium text-gray-800 mb-1.5">Prep list name</label>
            <input id="prepListName" type="text" name="name"
                   value="{{ $prepList['name'] ?? '' }}"
                   placeholder="{{ $isEdit ? '' : 'PURCHASE OF PHARMA SUPPLIES' }}"
                   class="w-full px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
        </div>

        {{-- Project groups --}}
        <div class="space-y-6">
            @foreach($projects as $project)
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Card header: checkbox + project name + RFQ reference --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        {{-- Clicking the project name behaves like the checkbox: selects/deselects all items in this card --}}
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox" class="project-check w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                   title="Select all items in this project"
                                   {{ $project['checked'] ? 'checked' : '' }}>
                            <span class="font-bold text-gray-900">{{ $project['name'] }}</span>
                        </label>
                        <span class="text-sm text-gray-400 font-medium">{{ $project['reference'] }}</span>
                    </div>

                    {{-- Item rows --}}
                    <div class="divide-y divide-gray-100">
                        @foreach($project['items'] as $item)
                            @if($isEdit)
                                @php
                                    $isReduced = !$item['waived'] && $item['reduced'] < $item['original'];
                                @endphp
                                <div class="item-row flex items-center justify-between gap-4 px-5 py-3 transition-colors hover:bg-gray-50"
                                     data-original="{{ $item['original'] }}"
                                     data-reduced="{{ $isReduced ? $item['reduced'] : $item['original'] }}"
                                     data-unit="{{ $item['unit'] }}"
                                     data-waived="{{ $item['waived'] ? 'true' : 'false' }}">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox"
                                               class="item-check w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                               {{ $item['checked'] ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">{{ $item['name'] }}</span>
                                    </div>

                                    <div class="flex items-center gap-6">
                                        {{-- Quantity display --}}
                                        <div class="flex items-center justify-end gap-2 min-w-[140px]">
                                            <span class="qty-display text-sm {{ $item['waived'] ? 'hidden' : '' }}">
                                                @if($isReduced)
                                                    <span class="old-qty text-gray-400 line-through mr-1">{{ $item['original'] }}</span>
                                                    <span class="new-qty font-medium text-gray-900">{{ $item['reduced'] }}</span>
                                                @else
                                                    <span class="old-qty text-gray-400 line-through mr-1 hidden"></span>
                                                    <span class="new-qty font-medium text-gray-900">{{ $item['original'] }}</span>
                                                @endif
                                                <span class="ml-1 text-gray-400">{{ $item['unit'] }}</span>
                                            </span>
                                            <span class="waived-pill {{ $item['waived'] ? '' : 'hidden' }} inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                                Waived
                                            </span>

                                            {{-- Editable quantity input (shown during Reduce) --}}
                                            <div class="qty-edit hidden items-center gap-1">
                                                <input type="number" min="1" step="1"
                                                       class="qty-input w-16 px-2 py-1 rounded-md border border-gray-300 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                            </div>
                                        </div>

                                        {{-- Row actions (Edit mode only) --}}
                                        <div class="flex items-center gap-2">
                                            <button type="button"
                                                    class="reduce-btn {{ $item['waived'] ? 'hidden' : '' }} flex items-center gap-1.5 px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                                    title="Reduce quantity">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                                </svg>
                                                Reduce
                                            </button>

                                            <button type="button"
                                                    class="waive-btn {{ $item['waived'] ? 'hidden' : '' }} flex items-center gap-1.5 px-2.5 py-1.5 rounded-md border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                                    title="Waive item">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 110 12.728m0-12.728a9 9 0 00-12.728 12.728"/>
                                                </svg>
                                                Waive
                                            </button>
                                            <button type="button"
                                                    class="undo-btn {{ $item['waived'] ? '' : 'hidden' }} flex items-center gap-1.5 px-2.5 py-1.5 rounded-md border border-amber-300 text-xs font-medium text-amber-700 hover:bg-amber-50 transition-colors"
                                                    title="Undo waiver">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v6h6M21 17a9 9 0 01-15 3.7L3 13m0 0v6h6"/>
                                                </svg>
                                                Undo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="item-row flex items-center justify-between gap-4 px-5 py-3 transition-colors"
                                     data-original="0" data-reduced="0" data-unit="{{ $item['unit'] }}" data-waived="false">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox"
                                               class="item-check w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                               {{ $item['checked'] ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-700">{{ $item['name'] }}</span>
                                    </div>

                                    {{-- Plain quantity — Add mode has no reduce/waive --}}
                                    <span class="text-sm text-gray-900">{{ $item['qty'] }} {{ $item['unit'] }}</span>
                                </div>
                            @endif
                        @endforeach
                </div>
            </div>
        @endforeach
        </div>

        {{-- Footer row: summary (left) + actions (right) --}}
        <div class="flex items-center justify-between gap-4 pt-2">
            <p class="text-sm text-gray-500" id="summaryText">
                @if($isEdit)
                    {{ $projectCount }} projects &bull; {{ $selectedCount }} items selected ({{ $waivedCount }} waived)
                @else
                    {{ $projectCount }} projects &bull; {{ $selectedCount }} items selected
                @endif
            </p>

            <div class="flex items-center gap-3">
                <a href="{{ route('preplist') }}"
                   class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancel
                </a>
                <button type="submit"
                        class="flex items-center gap-2 px-4 py-2 rounded-md bg-[#0e5266] text-white text-sm font-medium hover:bg-[#0c4757] transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-6 0V3h6v4m-6 0h6"/>
                    </svg>
                    {{ $isEdit ? 'Save changes' : 'Compile prep list' }}
                </button>
            </div>
        </div>
    </form>

@endsection

<script>
    // Frontend-only reduce / waive / undo handling for this prep-list editor.
    // @TODO: Once models + controller exist, persist these mutations via real
    // endpoints (or Livewire wire:model bindings) instead of only updating the DOM.
    (function () {
        const summaryEl = document.getElementById('summaryText');
        const projectsCount = {{ $projectCount }};
        const isEdit = {{ $isEdit ? 'true' : 'false' }};

        function syncSummary() {
            if (isEdit) {
                const waived = document.querySelectorAll('.item-row[data-waived="true"]').length;
                const activeSelected = document.querySelectorAll('.item-row .item-check:checked').length;
                const selected = activeSelected + waived; // waived items still count toward "selected"
                summaryEl.textContent = projectsCount + ' projects \u2022 ' + selected + ' items selected (' + waived + ' waived)';
            } else {
                // Add mode has no "waived" concept — count checked item rows only.
                const selected = document.querySelectorAll('.item-row .item-check:checked').length;
                summaryEl.textContent = projectsCount + ' projects \u2022 ' + selected + ' items selected';
            }
        }

        // "Select all items" for each project header checkbox (both modes).
        // Clicking the project name works too, since the name sits inside the
        // same <label> as the checkbox and toggles it natively.
        document.querySelectorAll('.project-check').forEach(function (projectCheck) {
            projectCheck.addEventListener('change', function () {
                const card = projectCheck.closest('.bg-white.rounded-lg');
                if (!card) return;
                card.querySelectorAll('.item-check').forEach(function (itemCheck) {
                    // Edit mode: waived items remain deselected until un-waived,
                    // so they can't be bulk-checked by the project header.
                    const row = itemCheck.closest('.item-row');
                    if (isEdit && row && row.dataset.waived === 'true') {
                        itemCheck.checked = false;
                        return;
                    }
                    itemCheck.checked = projectCheck.checked;
                });
                syncSummary();
            });
        });

        if (isEdit) {
            document.querySelectorAll('.item-row').forEach(function (row) {
            const original = parseInt(row.dataset.original, 10);
            const reduced = parseInt(row.dataset.reduced, 10);
            const unit = row.dataset.unit;
            const checkbox = row.querySelector('.item-check');
            const display = row.querySelector('.qty-display');
            const oldQty = row.querySelector('.old-qty');
            const newQty = row.querySelector('.new-qty');
            const pill = row.querySelector('.waived-pill');
            const edit = row.querySelector('.qty-edit');
            const input = row.querySelector('.qty-input');
            const reduceBtn = row.querySelector('.reduce-btn');
            const waiveBtn = row.querySelector('.waive-btn');
            const undoBtn = row.querySelector('.undo-btn');

            let current = reduced || original;

            function renderQty() {
                newQty.textContent = current;
                if (current < original) {
                    oldQty.textContent = original;
                    oldQty.classList.remove('hidden');
                } else {
                    oldQty.textContent = original;
                    oldQty.classList.add('hidden');
                }
            }

            function hideQtyEditor() {
                edit.classList.add('hidden');
                edit.classList.remove('flex');
                display.classList.remove('hidden');
            }

            function commitQty() {
                let value = parseInt(input.value, 10);
                if (isNaN(value) || value < 1) value = current;
                current = value;
                hideQtyEditor();
                renderQty();
                syncSummary();
            }

            function setWaived(waived) {
                row.dataset.waived = waived ? 'true' : 'false';
                checkbox.checked = !waived;
                pill.classList.toggle('hidden', !waived);
                display.classList.toggle('hidden', waived);
                waiveBtn.classList.toggle('hidden', waived);
                reduceBtn.classList.toggle('hidden', waived);
                undoBtn.classList.toggle('hidden', !waived);
                if (waived) {
                    hideQtyEditor();
                } else {
                    // Restore to normal (original) quantity and re-check the item.
                    current = original;
                    renderQty();
                }
                syncSummary();
            }

            reduceBtn.addEventListener('click', function () {
                if (display.classList.contains('hidden')) return;
                input.value = current;
                display.classList.add('hidden');
                edit.classList.remove('hidden');
                edit.classList.add('flex');
                input.focus();
                input.select();
            });

            input.addEventListener('change', commitQty);
            input.addEventListener('blur', commitQty);
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    commitQty();
                    input.blur();
                }
            });

            waiveBtn.addEventListener('click', function () { setWaived(true); });
            undoBtn.addEventListener('click', function () { setWaived(false); });

            checkbox.addEventListener('change', syncSummary);

            // Initial state
            if (row.dataset.waived === 'true') {
                setWaived(true);
            } else {
                renderQty();
            }
            });
        } else {
            // Add mode: no reduce/waive UI in markup — just keep the summary
            // in sync as the user ticks/unticks item checkboxes.
            document.querySelectorAll('.item-row .item-check').forEach(function (checkbox) {
                checkbox.addEventListener('change', syncSummary);
            });
        }

        syncSummary();
    })();
</script>