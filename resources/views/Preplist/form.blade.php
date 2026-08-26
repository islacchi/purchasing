@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', 'Edit Prep List - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // ------------------------------------------------------------------
        // This single view will serve both Add and Edit.
        //
        // @TODO: Edit/Add mode switch. Once models + migrations exist:
        //   - Edit:  $prepList = Preplist::with(['projects', 'items'])->findOrFail($id)
        //   - Add:   no $prepList passed (heading shows "Add prep list").
        // For now we only build the EDIT state — add-mode layout comes later.
        //
        // @TODO: replace the hardcoded arrays below with real Eloquent queries.
        $prepList = [
            'id'   => 1,
            'name' => 'PURCHASE OF PHARMA SUPPLIES',
        ];

        $projects = [
            [
                'id'   => 1,
                'name' => 'Pharma supplies Q3',
                'reference' => 'RFQ 2026-0042',
                'items' => [
                    ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true,  'original' => 50, 'reduced' => 40, 'unit' => 'box', 'waived' => false],
                    ['name' => 'Amoxicillin 500mg, box of 100',       'checked' => true,  'original' => 30, 'reduced' => 25, 'unit' => 'box', 'waived' => false],
                ],
            ],
            [
                'id'   => 2,
                'name' => 'Laboratory reagents',
                'reference' => 'RFQ 2026-0038',
                'items' => [
                    ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true,  'original' => 40, 'reduced' => 30, 'unit' => 'box', 'waived' => false],
                    ['name' => 'Surgical gloves, box of 50 pairs',    'checked' => false, 'original' => 40, 'reduced' => 40, 'unit' => 'box', 'waived' => true],
                ],
            ],
        ];

        // Summary counts derived from dummy data above.
        // @TODO: recompute from DB once Eloquent is wired up.
        $selectedCount = 0;
        $waivedCount   = 0;
        foreach ($projects as $project) {
            foreach ($project['items'] as $item) {
                if ($item['waived']) {
                    $waivedCount++;
                }
                if ($item['checked'] || $item['waived']) {
                    // A waived item is still part of the selected prep list, so it counts here.
                    $selectedCount++;
                }
            }
        }
        $projectCount = count($projects);
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
                   class="w-full px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
        </div>

        {{-- Project groups --}}
        <div class="space-y-6">
            @foreach($projects as $project)
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Card header: checkbox + project name + RFQ reference --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" class="w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300">
                            <span class="font-bold text-gray-900">{{ $project['name'] }}</span>
                        </div>
                        <span class="text-sm text-gray-400 font-medium">{{ $project['reference'] }}</span>
                    </div>

                    {{-- Item rows --}}
                    <div class="divide-y divide-gray-100">
                        @foreach($project['items'] as $item)
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

                                {{-- Row actions --}}
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
                    @endforeach
                </div>
            </div>
        @endforeach
        </div>

        {{-- Footer row: summary (left) + actions (right) --}}
        <div class="flex items-center justify-between gap-4 pt-2">
            <p class="text-sm text-gray-500" id="summaryText">
                {{ $projectCount }} projects &bull; {{ $selectedCount }} items selected ({{ $waivedCount }} waived)
            </p>

            <div class="flex items-center gap-3">
                <a href="{{ route('preplist.show', ['id' => isset($prepList) ? $prepList['id'] : 1]) }}"
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
                    Save changes
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

        function syncSummary() {
            const waived = document.querySelectorAll('.item-row[data-waived="true"]').length;
            const activeSelected = document.querySelectorAll('.item-row .item-check:checked').length;
            const selected = activeSelected + waived; // waived items still count toward "selected"
            summaryEl.textContent = projectsCount + ' projects \u2022 ' + selected + ' items selected (' + waived + ' waived)';
        }

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

        syncSummary();
    })();
</script>