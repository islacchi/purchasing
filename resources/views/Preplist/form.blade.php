@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', isset($prepList) ? 'Edit Prep List - Purchasing' : 'Add Prep List - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <style>
        /* Notes textarea: suppress the native resize gripper (up/down arrows) used by some browsers. */
        #prepListNotes,
        #prepListNotes::-webkit-inner-spin-button,
        #prepListNotes::-webkit-outer-spin-button { appearance: none; -webkit-appearance: none; }
        #prepListNotes { resize: none; }

        /* Hide native number-spinner arrows on the shelf-life input while keeping the input editable. */
        .shelf-life-input::-webkit-outer-spin-button,
        .shelf-life-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        .shelf-life-input[type=number] { -moz-appearance: textfield; appearance: textfield; }

        /* Zebra rows inside each project group. */
        .item-row:nth-child(even) { background: #f2f7f8; }

        /* Selected rows keep the brand highlight. The hover rule in
           Tailwind would otherwise turn a selected row gray, so the selected
           state explicitly overrides it — and must win over the zebra rule. */
        .item-row.is-selected { background-color: rgba(42, 122, 148, 0.08); }
        .item-row.is-selected:hover { background-color: rgba(42, 122, 148, 0.14); }
        .item-row.is-selected .item-name,
        .item-row.is-selected .item-quantity { color: #0e5266; }
        .item-row.is-selected .item-name { font-weight: 500; }

        /* Group header: teal accent bar marks a group with selections. */
        [data-group-head] { border-left: 3px solid transparent; }
        [data-group-head].has-selection { border-left-color: #2a7a94; background: #fbfdfe; }
    </style>

    @php
        // This single view serves both Edit and Compile modes.
        //   - Edit:    $prepList is set     -> heading "Edit prep list", Save changes.
        //   - Compile: $prepList is null    -> heading "Add prep list", Compile prep list.
        $isEdit = isset($prepList) && $prepList !== null;
        $projects = $projects ?? collect();
        $selectedSources = $selectedSources ?? [];

        $selectedSourceIds = old('sources', array_keys($selectedSources));
        $selectedSourceIds = array_map(
            fn ($id) => (string) $id,
            is_array($selectedSourceIds) ? $selectedSourceIds : [],
        );

        $selectedCount = 0;
        $projectCount = 0;
        $unitsCount = 0;

        foreach ($projects as $project) {
            $projectSelected = $project->items
                ->filter(fn ($projectItem) => in_array((string) $projectItem->id, $selectedSourceIds, true))
                ->count();

            $selectedCount += $projectSelected;
            $unitsCount += $project->items
                ->filter(fn ($projectItem) => in_array((string) $projectItem->id, $selectedSourceIds, true))
                ->sum(fn ($projectItem) => $projectItem->remainingQuantity());

            if ($projectSelected > 0) {
                $projectCount++;
            }
        }

        // Per-project figures for the collapsed/expanded headers and the rail.
        $projectStats = [];
        foreach ($projects as $project) {
            $items = $project->items
                ->filter(fn ($projectItem) => in_array((string) $projectItem->id, $selectedSourceIds, true));
            $projectStats[$project->id] = [
                'count' => $items->count(),
                'units' => (int) $items->sum(fn ($projectItem) => $projectItem->remainingQuantity()),
                'total' => $project->items->count(),
            ];
        }
    @endphp

    <div class="flex min-h-full flex-col lg:h-full lg:min-h-0">
    <div class="mb-4 shrink-0">
        <h2 class="text-2xl font-bold text-gray-900">{{ $isEdit ? 'Edit prep list' : 'Add prep list' }}</h2>
        <p class="mt-1 text-xs text-gray-500">Pick the item lines to procure — unticked lines stay out of this prep list.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $isEdit ? route('preplist.update', ['preplist' => $prepList->id]) : route('preplist.store') }}" method="POST" class="flex flex-1 min-h-0 flex-col gap-4">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        {{-- Prep list details --}}
        <div class="shrink-0 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="mb-3 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Prep list details</p>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                <div class="lg:w-[320px]">
                    <label for="prepListName" class="mb-1 block text-[11px] font-semibold text-gray-600">Prep list name</label>
                    <input id="prepListName" type="text" name="name"
                           value="{{ old('name', $prepList?->name ?? '') }}"
                           placeholder="{{ $isEdit ? '' : 'PURCHASE OF PHARMA SUPPLIES' }}"
                           class="h-9 w-full rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    <p class="mt-1 text-xs text-gray-500">
                        @if($isEdit)
                            Reference no. <span class="font-medium text-gray-700">{{ $prepList->reference_no }}</span>
                        @else
                            A reference number will be generated automatically when the prep list is compiled.
                        @endif
                    </p>
                </div>
                <div class="min-w-0 flex-1">
                    <label for="prepListNotes" class="mb-1 block text-[11px] font-semibold text-gray-600">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                    <input id="prepListNotes" type="text" name="notes" value="{{ old('notes', $prepList?->notes ?? '') }}"
                           placeholder="Add notes here..."
                           class="h-9 w-full rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                </div>
            </div>
        </div>

        {{-- Two-column main: selection summary rail + available projects --}}
        <div class="grid grid-cols-1 gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[280px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]">

            {{-- LEFT RAIL — SELECTION SUMMARY --}}
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm lg:min-h-0 lg:overflow-y-auto">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Selection summary</p>

                <div class="divide-y divide-gray-100">
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <span class="text-[11px] font-semibold text-gray-500">Projects</span>
                        <span class="text-xl font-extrabold leading-none text-gray-900"><span id="statProjects">{{ $projectCount }}</span><span class="text-xs font-semibold text-gray-400"> / {{ $projects->count() }}</span></span>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <span class="text-[11px] font-semibold text-gray-500">Items</span>
                        <span class="text-xl font-extrabold leading-none text-gray-900" id="statItems">{{ $selectedCount }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3 py-2.5">
                        <span class="text-[11px] font-semibold text-gray-500">Units to procure</span>
                        <span class="text-xl font-extrabold leading-none text-gray-900" id="statUnits">{{ $unitsCount }}</span>
                    </div>
                </div>

                <p class="mt-3 border-t border-gray-100 pt-3 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Selected</p>
                <div id="selectedList" class="mt-2 space-y-2">
                    @php $anySelected = false; @endphp
                    @foreach ($projects as $project)
                        @php
                            $stat = $projectStats[$project->id] ?? ['count' => 0, 'units' => 0];
                        @endphp
                        @if ($stat['count'] > 0)
                            @php $anySelected = true; @endphp
                            <div class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-[#fbfdfe] p-2.5">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold leading-snug text-[#2a7a94]">{{ $project->title }}</p>
                                    <p class="mt-1 text-[10px] text-gray-500">{{ $project->procuringEntity?->name ? $project->procuringEntity->name.' · ' : '' }}{{ $project->reference_no }}</p>
                                </div>
                                <span class="whitespace-nowrap rounded-full bg-[#2a7a94]/10 px-2 py-0.5 text-[10px] font-bold text-[#2a7a94]">{{ $stat['count'] }} {{ $stat['count'] === 1 ? 'item' : 'items' }} · {{ $stat['units'] }} u</span>
                            </div>
                        @endif
                    @endforeach
                    @if (! $anySelected)
                        <p class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-center text-[11px] text-gray-400">Nothing selected yet.</p>
                    @endif
                </div>
            </div>

            {{-- RIGHT — AVAILABLE PROJECTS --}}
            <div class="flex flex-col self-start overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm lg:h-full lg:min-h-0 lg:self-auto">
                <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Available projects</p>
                        <p class="mt-0.5 text-[11px] text-gray-500">Lines already fulfilled or sourced elsewhere are hidden.</p>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] font-semibold">
                        <button type="button" id="expandAllBtn" class="text-[#2a7a94] transition-colors hover:text-[#236b80] hover:underline">Expand all</button>
                        <button type="button" id="collapseAllBtn" class="text-[#2a7a94] transition-colors hover:text-[#236b80] hover:underline">Collapse all</button>
                    </div>
                </div>

                <div id="projectGroups" class="lg:min-h-0 lg:flex-1 lg:overflow-y-auto">
                    {{-- Sticky teal column header --}}
                    <div class="sticky top-0 z-10 grid grid-cols-[36px_minmax(0,1fr)_92px_160px_110px] gap-2 bg-[#0e5266] px-4 py-2.5">
                        <div></div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-white">Item</div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-white">Status</div>
                        <div class="text-[10px] font-semibold uppercase tracking-wider text-white">Min. shelf life</div>
                        <div class="text-right text-[10px] font-semibold uppercase tracking-wider text-white">To procure</div>
                    </div>

                    @forelse($projects as $project)
                        @php
                            $selectedProjectItems = $project->items
                                ->filter(fn ($projectItem) => in_array((string) $projectItem->id, $selectedSourceIds, true))
                                ->count();

                            $allProjectItemsSelected = $project->items->isNotEmpty()
                                && $selectedProjectItems === $project->items->count();

                            $projectUnitTotal = (int) $project->items
                                ->filter(fn ($projectItem) => in_array((string) $projectItem->id, $selectedSourceIds, true))
                                ->sum(fn ($projectItem) => $projectItem->remainingQuantity());

                            $projectTotalLines = $project->items->count();
                        @endphp

                        <div class="project-card border-b border-gray-100 last:border-b-0"
                             data-project-collapsible
                             data-project-id="{{ $project->id }}"
                             data-project-title="{{ $project->title }}"
                             data-project-entity="{{ $project->procuringEntity?->name ?? '' }}"
                             data-project-ref="{{ $project->reference_no }}">

                            {{-- Group header — clicking the row toggles the items; the checkbox selects/deselects --}}
                            <div class="flex items-center gap-3 border-b border-dashed border-gray-200 px-4 py-2.5 {{ $selectedProjectItems > 0 ? 'has-selection' : '' }}" data-group-head>
                                <input type="checkbox"
                                       class="project-check w-4 h-4 shrink-0 cursor-pointer rounded border-gray-300 accent-[#2a7a94] text-[#2a7a94] focus:ring-teal-300"
                                       title="Select all items in this project"
                                       {{ $allProjectItemsSelected ? 'checked' : '' }}>
                                <button type="button" class="flex min-w-0 flex-1 items-center justify-between gap-3 rounded text-left focus:outline-none focus:ring-2 focus:ring-inset focus:ring-teal-300"
                                        data-project-toggle aria-expanded="false">
                                    <div class="min-w-0">
                                        <p class="truncate text-[13px] font-bold text-gray-900" title="{{ $project->title }}">{{ $project->title }}</p>
                                        <p class="mt-0.5 text-[10px] text-gray-500">{{ $project->procuringEntity?->name ? $project->procuringEntity->name.' · ' : '' }}{{ $project->reference_no }}</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-bold {{ $selectedProjectItems > 0 ? 'bg-[#2a7a94]/10 text-[#2a7a94]' : 'bg-gray-100 text-gray-500' }}" data-group-selected>{{ $selectedProjectItems }} / {{ $projectTotalLines }}</span>
                                        <span class="inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-bold {{ $selectedProjectItems > 0 ? 'bg-[#2a7a94]/10 text-[#2a7a94]' : 'bg-gray-100 text-gray-500' }}" data-group-units>{{ $projectUnitTotal }} units</span>
                                        <svg class="chevron-down h-4 w-4 text-gray-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9l6 6 6-6"/>
                                        </svg>
                                    </div>
                                </button>
                            </div>

                            {{-- Item rows --}}
                            <div class="hidden" data-project-items>
                                @foreach($project->items as $projectItem)
                                    @php
                                        $isSelected = in_array((string) $projectItem->id, $selectedSourceIds, true);
                                        $selectedPreplistItem = $selectedSources[$projectItem->id] ?? null;
                                        $isFulfilled = $projectItem->isFulfilled();
                                        $shelfLife = old(
                                            'shelf_life.'.$projectItem->id,
                                            $selectedPreplistItem?->min_shelf_life_months
                                                ?? $projectItem->item?->default_min_shelf_life_months
                                                ?? 0,
                                        );
                                        $remaining = $projectItem->remainingQuantity();
                                    @endphp

                                    <div class="item-row grid grid-cols-[36px_minmax(0,1fr)_92px_160px_110px] gap-2 items-center px-4 py-1.5 transition-colors hover:bg-[#e4edf0] {{ $isSelected ? 'is-selected' : '' }}" data-remaining="{{ $remaining }}">
                                        <input type="checkbox"
                                               name="sources[]"
                                               value="{{ $projectItem->id }}"
                                               class="item-check w-4 h-4 shrink-0 cursor-pointer rounded border-gray-300 accent-[#2a7a94] text-[#2a7a94] focus:ring-teal-300"
                                               {{ $isSelected ? 'checked' : '' }}>
                                        <span class="item-name min-w-0 truncate text-xs text-gray-700" title="{{ $projectItem->item?->name ?? 'Item' }}">{{ $projectItem->item?->name ?? 'Item' }}</span>
                                        @if ($isFulfilled)
                                            <span class="inline-flex items-center justify-self-start whitespace-nowrap rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-green-700">Fulfilled</span>
                                        @else
                                            <span class="inline-flex items-center justify-self-start whitespace-nowrap rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600">Pending</span>
                                        @endif

                                        {{-- Shelf life required at delivery (per item), inline on the same row --}}
                                        <div class="flex items-center gap-1.5">
                                            <input type="number" min="0" max="1200" step="1"
                                                   name="shelf_life[{{ $projectItem->id }}]"
                                                   value="{{ $shelfLife }}"
                                                   class="shelf-life-input h-7 w-14 rounded-md border border-gray-300 px-1.5 text-right text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-300"
                                                   title="Minimum months of shelf life remaining at delivery. 0 = non-perishable."
                                                   aria-label="Minimum shelf life at delivery in months for {{ $projectItem->item?->name ?? 'item' }}; 0 means non-perishable">
                                            <span class="whitespace-nowrap text-[10px] text-gray-500">months</span>
                                        </div>

                                        {{-- Read-only quantity to procure --}}
                                        <div class="item-quantity text-right"
                                             title="Awarded: {{ $projectItem->quantity }} · On-hand stock: {{ $projectItem->in_stock }}">
                                            <p class="text-xs font-bold leading-tight text-gray-900">{{ $remaining }} {{ $projectItem->item?->unit ?? '' }}</p>
                                            <p class="mt-0.5 text-[9px] font-medium leading-tight text-gray-500">awarded {{ $projectItem->quantity }} · {{ $projectItem->in_stock }} on-hand</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="border-t border-dashed border-gray-200 px-6 py-12 text-center">
                            <h3 class="text-base font-semibold text-gray-900">No project items available</h3>
                            <p class="mt-1 text-sm text-gray-500">Nothing to compile — lines already fulfilled, or already sourced into another prep list, are hidden.</p>
                            <a href="{{ route('projects.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white hover:bg-[#236b80]">
                                Add project
                            </a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Footer row: summary (left) + actions (right) --}}
        <div class="no-print flex shrink-0 flex-wrap items-center justify-between gap-4">
            <p class="text-xs text-gray-500" id="summaryText">
                <b>{{ $projectCount }} {{ $projectCount === 1 ? 'project' : 'projects' }}</b> &bull; <b>{{ $selectedCount }} {{ $selectedCount === 1 ? 'item' : 'items' }}</b> selected &bull; <b>{{ $unitsCount }} units</b>
            </p>

            <div class="flex items-center gap-3">
                <a href="{{ route('preplist') }}"
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
                    {{ $isEdit ? 'Save changes' : 'Compile prep list' }}
                </button>
            </div>
        </div>
    </form>
    </div><!-- /flex column -->

    {{-- Kept inside the content section so it runs after the form exists. --}}
      <script>
        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Selection handling for project headers, item checkboxes, and the summary.
        (function () {
            const summaryEl = document.getElementById('summaryText');
            const statProjects = document.getElementById('statProjects');
            const statItems = document.getElementById('statItems');
            const statUnits = document.getElementById('statUnits');
            const selectedList = document.getElementById('selectedList');

            const CHIP_ON = 'inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-bold bg-[#2a7a94]/10 text-[#2a7a94]';
            const CHIP_OFF = 'inline-flex items-center whitespace-nowrap rounded-full px-2 py-0.5 text-[10px] font-bold bg-gray-100 text-gray-500';

            // Keeps the row highlight in step with its checkbox. Selected rows
            // get the brand tint; unselected rows fall back to the hover style.
            function applyItemState(itemCheck) {
                const row = itemCheck.closest('.item-row');
                if (row) {
                    row.classList.toggle('is-selected', itemCheck.checked);
                }
            }

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
                let units = 0;
                const selectedHtml = [];

                document.querySelectorAll('.project-card').forEach(function (card) {
                    let itemsN = 0;
                    let unitsN = 0;
                    card.querySelectorAll('.item-check:checked').forEach(function (itemCheck) {
                        itemsN++;
                        const row = itemCheck.closest('.item-row');
                        unitsN += parseFloat(row?.dataset.remaining || '0') || 0;
                    });

                    items += itemsN;
                    units += unitsN;

                    const total = card.querySelectorAll('.item-check').length;
                    const countChip = card.querySelector('[data-group-selected]');
                    if (countChip) {
                        countChip.textContent = itemsN + ' / ' + total;
                        countChip.className = itemsN > 0 ? CHIP_ON : CHIP_OFF;
                    }
                    const unitsChip = card.querySelector('[data-group-units]');
                    if (unitsChip) {
                        unitsChip.textContent = unitsN + ' units';
                        unitsChip.className = itemsN > 0 ? CHIP_ON : CHIP_OFF;
                    }
                    const head = card.querySelector('[data-group-head]');
                    if (head) {
                        head.classList.toggle('has-selection', itemsN > 0);
                    }

                    if (itemsN > 0) {
                        projects++;
                        const entity = card.dataset.projectEntity ? escapeHtml(card.dataset.projectEntity) + ' · ' : '';
                        selectedHtml.push(
                            '<div class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-[#fbfdfe] p-2.5">'
                            + '<div class="min-w-0">'
                            + '<p class="text-xs font-semibold leading-snug text-[#2a7a94]">' + escapeHtml(card.dataset.projectTitle) + '</p>'
                            + '<p class="mt-1 text-[10px] text-gray-500">' + entity + escapeHtml(card.dataset.projectRef) + '</p>'
                            + '</div>'
                            + '<span class="whitespace-nowrap rounded-full bg-[#2a7a94]/10 px-2 py-0.5 text-[10px] font-bold text-[#2a7a94]">'
                            + itemsN + (itemsN === 1 ? ' item' : ' items') + ' · ' + unitsN + ' u</span>'
                            + '</div>'
                        );
                    }
                });

                if (statProjects) statProjects.textContent = projects;
                if (statItems) statItems.textContent = items;
                if (statUnits) statUnits.textContent = units;
                if (summaryEl) {
                    summaryEl.innerHTML =
                        '<b>' + projects + (projects === 1 ? ' project' : ' projects') + '</b> &bull; ' +
                        '<b>' + items + (items === 1 ? ' item' : ' items') + '</b> selected &bull; ' +
                        '<b>' + units + ' units</b>';
                }
                if (selectedList) {
                    selectedList.innerHTML = selectedHtml.length
                        ? selectedHtml.join('')
                        : '<p class="rounded-lg border border-dashed border-gray-300 px-3 py-4 text-center text-[11px] text-gray-400">Nothing selected yet.</p>';
                }
            }

            // Header checkbox selects / deselects every item in that card.
            document.querySelectorAll('.project-check').forEach(function (projectCheck) {
                projectCheck.addEventListener('change', function () {
                    const card = projectCheck.closest('.project-card');
                    card.querySelectorAll('.item-check').forEach(function (itemCheck) {
                        itemCheck.checked = projectCheck.checked;
                        applyItemState(itemCheck);
                    });
                    syncProjectCheck(card);
                    syncSummary();
                });
            });

            // Item checkbox updates its header, so the two can never disagree.
            document.querySelectorAll('.item-check').forEach(function (itemCheck) {
                itemCheck.addEventListener('change', function () {
                    applyItemState(itemCheck);
                    syncProjectCheck(itemCheck.closest('.project-card'));
                    syncSummary();
                });
            });

            // Correct any mismatch on first load.
            document.querySelectorAll('.item-check').forEach(applyItemState);
            document.querySelectorAll('.project-card').forEach(syncProjectCheck);
            syncSummary();
        })();

        // Collapsible project groups: toggle one, or all, of the item lists.
        (function () {
            function setExpanded(toggle, expand) {
                const card = toggle.closest('[data-project-collapsible]');
                const items = card ? card.querySelector('[data-project-items]') : null;
                const chevron = toggle.querySelector('.chevron-down');
                toggle.setAttribute('aria-expanded', expand ? 'true' : 'false');
                if (items) items.classList.toggle('hidden', !expand);
                if (chevron) chevron.style.transform = expand ? 'rotate(180deg)' : '';
            }

            document.querySelectorAll('[data-project-toggle]').forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    setExpanded(toggle, toggle.getAttribute('aria-expanded') !== 'true');
                });
            });

            const expandAllBtn = document.getElementById('expandAllBtn');
            const collapseAllBtn = document.getElementById('collapseAllBtn');
            if (expandAllBtn) {
                expandAllBtn.addEventListener('click', function () {
                    document.querySelectorAll('[data-project-toggle]').forEach(function (toggle) { setExpanded(toggle, true); });
                });
            }
            if (collapseAllBtn) {
                collapseAllBtn.addEventListener('click', function () {
                    document.querySelectorAll('[data-project-toggle]').forEach(function (toggle) { setExpanded(toggle, false); });
                });
            }
        })();
    </script>
@endsection
