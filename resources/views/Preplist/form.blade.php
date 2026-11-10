@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', isset($prepList) ? 'Edit Prep List - Purchasing' : 'Add Prep List - Purchasing')

@section('header-actions')
@endsection

@section('content')

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

        foreach ($projects as $project) {
            $projectSelected = $project->items
                ->filter(fn ($projectItem) => in_array((string) $projectItem->id, $selectedSourceIds, true))
                ->count();

            $selectedCount += $projectSelected;

            if ($projectSelected > 0) {
                $projectCount++;
            }
        }
    @endphp

    <div class="flex h-full min-h-0 flex-col">
    <h2 class="shrink-0 text-2xl font-bold text-gray-900 mb-6">{{ $isEdit ? 'Edit prep list' : 'Add prep list' }}</h2>

    @if ($errors->any())
        <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $isEdit ? route('preplist.update', ['preplist' => $prepList->id]) : route('preplist.store') }}" method="POST" class="flex flex-1 min-h-0 flex-col space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        {{-- Prep list name + notes --}}
        <div class="shrink-0 max-w-2xl space-y-4">
            <div>
                <label for="prepListName" class="block text-sm font-medium text-gray-800 mb-1.5">Prep list name</label>
                <input id="prepListName" type="text" name="name"
                       value="{{ old('name', $prepList?->name ?? '') }}"
                       placeholder="{{ $isEdit ? '' : 'PURCHASE OF PHARMA SUPPLIES' }}"
                       class="w-full px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                <p class="mt-1 text-xs text-gray-500">
                    @if($isEdit)
                        Reference no. <span class="font-medium text-gray-700">{{ $prepList->reference_no }}</span>
                    @else
                        A reference number will be generated automatically when the prep list is compiled.
                    @endif
                </p>
            </div>

            <div>
                <label for="prepListNotes" class="block text-sm font-medium text-gray-800 mb-1.5">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                <textarea id="prepListNotes" name="notes" rows="3"
                          placeholder="Add notes here..."
                          class="w-full px-3 py-2 rounded-md border border-gray-300 bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">{{ old('notes', $prepList?->notes ?? '') }}</textarea>
            </div>
        </div>

        {{-- Project groups (item list scrolls here) --}}
        <div class="min-h-[220px] flex-1 space-y-6 overflow-y-auto pr-1">
            @forelse($projects as $project)
                @php
                    $selectedProjectItems = $project->items
                        ->filter(fn ($projectItem) => in_array((string) $projectItem->id, $selectedSourceIds, true))
                        ->count();

                    $allProjectItemsSelected = $project->items->isNotEmpty()
                        && $selectedProjectItems === $project->items->count();
                @endphp

                <div class="project-card bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                    {{-- Card header: checkbox + project name + RFQ reference --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <label class="flex items-center gap-3 cursor-pointer select-none">
                            <input type="checkbox"
                                   class="project-check w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                   title="Select all items in this project"
                                   {{ $allProjectItemsSelected ? 'checked' : '' }}>
                            <span class="font-bold text-gray-900">{{ $project->title }}</span>
                        </label>
                        <span class="text-sm text-gray-400 font-medium">
                            {{ $project->procuringEntity?->name ? $project->procuringEntity->name.' - ' : '' }}{{ $project->reference_no }}
                        </span>
                    </div>

                    {{-- Item rows --}}
                    <div class="divide-y divide-gray-100">
                        @foreach($project->items as $projectItem)
                            @php
                                $isSelected = in_array((string) $projectItem->id, $selectedSourceIds, true);
                                $selectedPreplistItem = $selectedSources[$projectItem->id] ?? null;
                                $shelfLife = old(
                                    'shelf_life.'.$projectItem->id,
                                    $selectedPreplistItem?->min_shelf_life_months
                                        ?? $projectItem->item?->default_min_shelf_life_months
                                        ?? 0,
                                );
                            @endphp

                            <div class="item-row flex items-center justify-between gap-4 px-5 py-3 transition-colors hover:bg-gray-50">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox"
                                           name="sources[]"
                                           value="{{ $projectItem->id }}"
                                           class="item-check mt-0.5 w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                           {{ $isSelected ? 'checked' : '' }}>
                                    <div class="min-w-0">
                                        <span class="text-sm text-gray-700">{{ $projectItem->item?->name ?? 'Item' }}</span>

                                        {{-- Shelf life required at delivery (per item) --}}
                                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                            <label class="text-[11px] font-medium text-gray-500">Min. shelf life at delivery</label>
                                            <input type="number" min="0" max="1200" step="1"
                                                   name="shelf_life[{{ $projectItem->id }}]"
                                                   value="{{ $shelfLife }}"
                                                   class="shelf-life-input w-16 px-2 py-0.5 rounded-md border border-gray-300 text-xs text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-300"
                                                   title="Minimum months of shelf life remaining at delivery"
                                                   aria-label="Minimum shelf life at delivery in months for {{ $projectItem->item?->name ?? 'item' }}">
                                            <span class="text-[11px] text-gray-500">months</span>
                                            <span class="text-[11px] text-gray-400">&middot; 0 = non-perishable</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Read-only quantity --}}
                                <span class="text-sm font-medium text-gray-900 whitespace-nowrap">
                                    {{ $projectItem->quantity }} {{ $projectItem->item?->unit ?? '' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                    <h3 class="text-base font-semibold text-gray-900">No project items available</h3>
                    <p class="mt-1 text-sm text-gray-500">Add items to a project before compiling a prep list.</p>
                    <a href="{{ route('projects.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white hover:bg-[#236b80]">
                        Add project
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Footer row: summary (left) + actions (right) --}}
        <div class="flex shrink-0 items-center justify-between gap-4 pt-2">
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
    </div><!-- /flex column -->

    {{-- Kept inside the content section so it runs after the form exists. --}}
      <script>
        // Selection handling for project headers, item checkboxes, and the summary.
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
