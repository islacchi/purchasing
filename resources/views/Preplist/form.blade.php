@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', isset($prepList) ? 'Edit Prep List - Purchasing' : 'Add Prep List - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // This single view serves both Edit and Add (compile) modes.
        //   - Edit: $prepList is set     → heading "Edit prep list", Save changes.
        //   - Add:  $prepList is NOT set → heading "Add prep list", Compile prep list.
        // Quantities are read-only in both modes (no reduce / waive here).
        //
        // @TODO: once models + migrations exist, replace the hardcoded arrays
        // below with real Eloquent queries:
        //   - Edit: $prepList = Preplist::with(['projects', 'items'])->findOrFail($id)
        //   - Add:  pull available source projects with their items.
        $isEdit = isset($prepList) && $prepList !== null;

        if ($isEdit) {
            // EDIT MODE: dummy data
            $projects = [
                [
                    'id'   => 1,
                    'name' => 'Pharma supplies Q3',
                    'reference' => 'RFQ 2026-0042',
                    'checked' => true,
                    'items' => [
                        // shelf_life = minimum months of shelf life remaining at
                        // delivery. 0 / null means non-perishable.
                        // @TODO: default from the Item master once it exists.
                        ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true, 'qty' => 40, 'unit' => 'box', 'shelf_life' => 12],
                        ['name' => 'Amoxicillin 500mg, box of 100',       'checked' => true, 'qty' => 25, 'unit' => 'box', 'shelf_life' => 12],
                    ],
                ],
                [
                    'id'   => 2,
                    'name' => 'Laboratory reagents',
                    'reference' => 'RFQ 2026-0038',
                    'checked' => true,
                    'items' => [
                        ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true,  'qty' => 30, 'unit' => 'box', 'shelf_life' => 12],
                        ['name' => 'Surgical gloves, box of 50 pairs',    'checked' => false, 'qty' => 40, 'unit' => 'box', 'shelf_life' => 36],
                    ],
                ],
            ];
        } else {
            // ADD (compile) MODE: dummy data
            $projects = [
                [
                    'id'   => 1,
                    'name' => 'Pharma supplies Q3',
                    'reference' => 'RFQ 2026-0042',
                    'checked' => true,
                    'items' => [
                        ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true, 'qty' => 40, 'unit' => 'box', 'shelf_life' => 12],
                        ['name' => 'Amoxicillin 500mg, box of 100',       'checked' => true, 'qty' => 40, 'unit' => 'box', 'shelf_life' => 12],
                    ],
                ],
                [
                    'id'   => 2,
                    'name' => 'Laboratory reagents',
                    'reference' => 'RFQ 2026-0038',
                    'checked' => true,
                    'items' => [
                        ['name' => 'Mefenamic capsule 500mg, box of 100', 'checked' => true,  'qty' => 40, 'unit' => 'box', 'shelf_life' => 12],
                        ['name' => 'Acetaminophen 500mg, box of 100',     'checked' => false, 'qty' => 40, 'unit' => 'box', 'shelf_life' => 12],
                    ],
                ],
                [
                    'id'   => 3,
                    'name' => 'PPE bulk order',
                    'reference' => 'RFQ 2026-0031',
                    'checked' => false, // project entirely unselected
                    'items' => [
                        ['name' => 'Disposable masks, box of 50', 'checked' => false, 'qty' => 40, 'unit' => 'box', 'shelf_life' => 36],
                    ],
                ],
            ];
        }

        // Initial summary: projects with at least one checked item, and checked items.
        // @TODO: recompute from DB once Eloquent is wired up.
        $selectedCount = 0;
        $projectCount  = 0;
        foreach ($projects as $project) {
            $projectSelected = 0;
            foreach ($project['items'] as $item) {
                if ($item['checked']) {
                    $projectSelected++;
                }
            }
            $selectedCount += $projectSelected;
            if ($projectSelected > 0) {
                $projectCount++;
            }
        }
    @endphp

    {{-- Page heading --}}
    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ $isEdit ? 'Edit prep list' : 'Add prep list' }}</h2>

    {{-- @TODO: wire up a real form action once routes/controller exist
         (Edit will also need @method('PUT')) --}}
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
                <div class="project-card bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Card header: checkbox + project name + RFQ reference --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox"
                                   class="project-check w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                   title="Select all items in this project"
                                   {{ collect($project['items'])->every(fn ($i) => $i['checked']) ? 'checked' : '' }}>
                            <span class="font-bold text-gray-900">{{ $project['name'] }}</span>
                        </label>
                        <span class="text-sm text-gray-400 font-medium">{{ $project['reference'] }}</span>
                    </div>

                    {{-- Item rows (same markup in Edit and Add) --}}
                    <div class="divide-y divide-gray-100">
                        @foreach($project['items'] as $item)
                            <div class="item-row flex items-center justify-between gap-4 px-5 py-3 transition-colors hover:bg-gray-50">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox"
                                           class="item-check mt-0.5 w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                           {{ $item['checked'] ? 'checked' : '' }}>
                                    <div class="min-w-0">
                                        <span class="text-sm text-gray-700">{{ $item['name'] }}</span>

                                        {{-- Shelf life required at delivery (per item) --}}
                                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                            <label class="text-[11px] font-medium text-gray-500">Min. shelf life at delivery</label>
                                            <input type="number" min="0" step="1"
                                                   value="{{ $item['shelf_life'] ?? 0 }}"
                                                   class="shelf-life-input w-16 px-2 py-0.5 rounded-md border border-gray-300 text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-300"
                                                   title="Minimum months of shelf life remaining at delivery"
                                                   aria-label="Minimum shelf life at delivery in months for {{ $item['name'] }}">
                                            <span class="text-[11px] text-gray-500">months</span>
                                            <span class="text-[11px] text-gray-400">&middot; 0 = non-perishable</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Read-only quantity --}}
                                <span class="text-sm font-medium text-gray-900 whitespace-nowrap">{{ $item['qty'] }} {{ $item['unit'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Footer row: summary (left) + actions (right) --}}
        <div class="flex items-center justify-between gap-4 pt-2">
            <p class="text-sm text-gray-500" id="summaryText">
                {{ $projectCount }} {{ $projectCount === 1 ? 'project' : 'projects' }} &bull; {{ $selectedCount }} {{ $selectedCount === 1 ? 'item' : 'items' }} selected
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

    {{-- Kept inside the content section so it runs after the form exists. --}}
      <script>
        // Frontend-only selection handling.
        // @TODO: replace with Livewire wire:model bindings once models + controller exist.
        (function () {
            const summaryEl = document.getElementById('summaryText');

            // Keeps a project's header checkbox in step with its items:
            // all checked → checked, none → unchecked, some → indeterminate (dash).
            function syncProjectCheck(card) {
                const items = card.querySelectorAll('.item-check');
                const checked = card.querySelectorAll('.item-check:checked').length;
                const header = card.querySelector('.project-check');
                header.checked = items.length > 0 && checked === items.length;
                header.indeterminate = checked > 0 && checked < items.length;
            }

            function syncSummary() {
                let projects = 0;
                let items = 0;
                document.querySelectorAll('.project-card').forEach(function (card) {
                    const checked = card.querySelectorAll('.item-check:checked').length;
                    items += checked;
                    if (checked > 0) projects++;
                });
                summaryEl.textContent =
                    projects + (projects === 1 ? ' project' : ' projects') + ' \u2022 ' +
                    items + (items === 1 ? ' item' : ' items') + ' selected';
            }

            // Header checkbox selects / deselects every item in that card.
            document.querySelectorAll('.project-check').forEach(function (projectCheck) {
                projectCheck.addEventListener('change', function () {
                    const card = projectCheck.closest('.project-card');
                    card.querySelectorAll('.item-check').forEach(function (itemCheck) {
                        itemCheck.checked = projectCheck.checked;
                    });
                    syncProjectCheck(card);
                    syncSummary();
                });
            });

            // Item checkbox updates its header, so the two can never disagree.
            document.querySelectorAll('.item-check').forEach(function (itemCheck) {
                itemCheck.addEventListener('change', function () {
                    syncProjectCheck(itemCheck.closest('.project-card'));
                    syncSummary();
                });
            });

            // Correct any mismatch on first load.
            document.querySelectorAll('.project-card').forEach(syncProjectCheck);
            syncSummary();
        })();
    </script>
@endsection