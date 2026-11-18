@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', 'Prep List - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <style>
        /* Anti-delete warning overlay — floats above the content area. */
        #quotationWarningBanner {
            position: fixed;
            top: 9rem;
            left: calc(5rem + 2rem);
            right: 2rem;
            z-index: 60;
            pointer-events: none;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 300ms ease, transform 300ms ease;
        }
        html.sidebar-expanded #quotationWarningBanner { left: calc(16rem + 2rem); }
        #quotationWarningBanner.is-hiding { opacity: 0; transform: translateY(-4px); }
    </style>
    @php
        $linked = $linkedQuotations ?? collect();
        $linkedCount = $linked->count();
        $quotedCount = $linked->filter(fn ($q) => (int) ($q->priced_items_count ?? 0) > 0)->count();
        $awaitingCount = $linkedCount - $quotedCount;

        $sourceProjects = $preplist->sourceProjects();

        $perProject = [];
        foreach ($preplist->items as $preplistItem) {
            foreach ($preplistItem->sources as $source) {
                $projectId = $source->projectItem?->project?->id;
                if ($projectId === null) {
                    continue;
                }
                $perProject[$projectId] = ($perProject[$projectId] ?? 0) + (int) ($source->projectItem?->remainingQuantity() ?? 0);
            }
        }
        $projectTotals = collect($perProject);

        $itemRows = $preplist->items->map(function ($preplistItem) {
            $unit = $preplistItem->item?->unit ?? '';
            $requested = $preplistItem->remainingQuantity();
            $sources = $preplistItem->sources->map(function ($source) use ($unit) {
                return [
                    'name' => $source->projectItem?->project?->title ?? 'Project removed',
                    'reference' => $source->projectItem?->project?->reference_no ?? '—',
                    'qty' => (int) ($source->projectItem?->remainingQuantity() ?? 0),
                    'unit' => $unit !== '' ? $unit : 'unit',
                ];
            });

            return [
                'id' => $preplistItem->id,
                'name' => $preplistItem->item?->name ?? 'Item',
                'unit' => $unit !== '' ? $unit : 'unit',
                'requested' => $requested,
                'shelf_life' => (int) $preplistItem->min_shelf_life_months,
                'source_count' => $sources->count(),
                'source_unit_total' => $sources->sum('qty'),
                'sources' => $sources,
                'status' => $requested > 0 ? 'To procure' : 'Fulfilled',
            ];
        });

        $totalRequested = $itemRows->sum('requested');
        $hasBlocked = ($blockedQuotations ?? collect())->isNotEmpty();
    @endphp

    <div class="flex h-full min-h-0 flex-col">

        {{-- Page header with status chips --}}
        <div class="mb-3 flex shrink-0 flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $preplist->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ $preplist->reference_no }}</p>
            </div>

            @if ($linkedCount > 0)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                        {{ $linkedCount }} linked quotation{{ $linkedCount === 1 ? '' : 's' }}
                    </span>
                    @if ($awaitingCount > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                            {{ $awaitingCount }} awaiting quote
                        </span>
                    @endif
                    @if ($quotedCount > 0)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                            {{ $quotedCount }} quoted
                        </span>
                    @endif
                </div>
            @endif
        </div>

        @if ($errors->any())
            <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($hasBlocked)
            {{-- Fixed overlay — floats above the view; does not affect layout --}}
            <div id="quotationWarningBanner" class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-lg" role="alert">
                <div>
                    <p class="font-semibold">This prep list cannot be deleted yet.</p>
                    <p class="mt-0.5 text-xs text-amber-700">
                        It is linked to progressed quotation{{ ($blockedQuotations ?? collect())->count() === 1 ? '' : 's' }}. Remove the supplier data first:
                    </p>
                    <ul class="mt-2 space-y-0.5 text-xs text-amber-700">
                        @foreach ($blockedQuotations as $quotation)
                            <li>&bull; {{ $quotation->rfq_number ?: 'Quotation #'.$quotation->id }} ({{ $quotation->supplier?->name ?? 'Supplier removed' }}) —
                                @php
                                    $reasons = array_values(array_filter([
                                        (int) ($quotation->priced_items_count ?? 0) > 0 ? 'has priced items' : null,
                                        $quotation->document_path ? 'a file was uploaded' : null,
                                        \App\Models\PurchaseOrder::query()->where('quotation_id', $quotation->id)->exists() ? 'a purchase order references it' : null,
                                    ]));
                                    echo implode(', ', $reasons);
                                @endphp
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- Main two-column layout --}}
        <div class="grid min-h-0 flex-1 grid-cols-1 items-start gap-4 lg:grid-cols-[300px_1fr]">

            {{-- LEFT COLUMN --}}
            <div class="min-h-0 space-y-3">
                {{-- Prep list info --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                    <p class="px-4 pt-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Prep list info</p>
                    <div class="divide-y divide-gray-100 px-4 pb-2">
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-500">Created by</span>
                            <span class="font-semibold text-gray-900">{{ $preplist->creator?->name ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-500">Date created</span>
                            <span class="font-semibold text-gray-900">{{ $preplist->created_at?->format('M d, Y g:i A') ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-500">Projects</span>
                            <span class="font-semibold text-gray-900">{{ $sourceProjects->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 text-sm">
                            <span class="text-gray-500">Total items</span>
                            <span class="font-semibold text-gray-900">{{ $itemRows->count() }}</span>
                        </div>
                        <div class="py-2 text-sm">
                            <span class="block text-gray-500">Notes</span>
                            @if ($preplist->notes)
                                <p class="mt-1 whitespace-pre-line text-gray-800">{{ $preplist->notes }}</p>
                            @else
                                <p class="mt-1 text-sm italic text-gray-400">No notes</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Source projects --}}
                <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Source projects</p>
                    <div class="mt-2 space-y-3">
                        @forelse ($sourceProjects as $project)
                            <a href="{{ route('projects.show', ['project' => $project->id]) }}"
                               class="block rounded-lg border border-gray-200 bg-white p-3 transition-all hover:border-[#2a7a94] hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-300"
                               title="Open project details">
                                <p class="text-sm font-semibold leading-snug text-[#2a7a94]">{{ $project->title }}</p>
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p class="text-xs text-gray-500">{{ $project->reference_no }}</p>
                                    @if ($projectTotals->get($project->id))
                                        <span class="whitespace-nowrap rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-800">{{ number_format($projectTotals[$project->id]) }} units</span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <p class="rounded-lg border border-dashed border-gray-300 bg-white px-3 py-4 text-center text-sm text-gray-500">
                                No source projects recorded.
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN — Items table --}}
            <div class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Items · {{ $itemRows->count() }} {{ $itemRows->count() === 1 ? 'line' : 'lines' }} · across {{ $sourceProjects->count() }} {{ $sourceProjects->count() === 1 ? 'project' : 'projects' }}</h3>
                        <p class="mt-0.5 text-xs text-gray-500">Total to procure: {{ number_format($totalRequested) }} units</p>
                    </div>
                </div>
                <div class="min-h-[180px] overflow-auto">
                    <table class="w-full table-fixed">
                        <colgroup>
                            <col class="w-[32%]">
                            <col class="w-[14%]">
                            <col class="w-[17%]">
                            <col class="w-[21%]">
                            <col class="w-[16%]">
                        </colgroup>
                        <thead class="sticky top-0 z-10 bg-[#0e5266]">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-white">
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5">Item</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5 text-center">Requested</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5">Shelf life</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5">Sources</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5 text-center">Status</th>
                            </tr>
                        </thead>
                        @forelse ($itemRows as $row)
                            <tbody class="{{ $loop->odd ? 'bg-white' : 'bg-[#f2f7f8]' }}">
                                <tr class="transition-colors hover:bg-[#e4edf0]">
                                    <td class="px-4 py-2.5 align-top">
                                        <p class="break-words text-sm font-medium leading-snug text-gray-900">{{ $row['name'] }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-center align-top">
                                        <span class="text-sm font-semibold text-gray-900">{{ number_format($row['requested']) }}</span>
                                        <span class="text-xs text-gray-500">{{ $row['unit'] }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 align-top text-sm text-gray-700">
                                        @if ($row['shelf_life'] > 0)
                                            &ge; {{ $row['shelf_life'] }} month{{ $row['shelf_life'] === 1 ? '' : 's' }}
                                        @else
                                            Non-perishable
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 align-top text-sm text-gray-700">
                                        {{ $row['source_count'] }} project{{ $row['source_count'] === 1 ? '' : 's' }} · {{ number_format($row['source_unit_total']) }} {{ $row['unit'] }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-center align-top">
                                        @if ($row['status'] === 'To procure')
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                                                To procure
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">
                                                Fulfilled
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @foreach ($row['sources'] as $source)
                                    <tr class="transition-colors hover:bg-[#e4edf0]">
                                        <td colspan="5" class="border-t border-dashed border-gray-200 py-1.5 pl-10 pr-4 text-xs text-gray-500">
                                            <span class="mr-1 text-gray-400">↳</span>Contributes <span class="font-semibold text-gray-800">{{ number_format($source['qty']) }} {{ $source['unit'] }}</span> · <span class="text-gray-700">{{ $source['name'] }}</span> · <span class="text-gray-500">{{ $source['reference'] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        @empty
                            <tbody class="bg-white">
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No items recorded for this prep list.
                                    </td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>
            </div>

        </div>

        {{-- Footer actions — same placement and design as the other detail pages --}}
        <div class="no-print mt-4 flex shrink-0 flex-wrap items-center justify-between gap-3">
            <a href="{{ route('preplist') }}"
               class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to list
            </a>
            <div class="flex flex-wrap items-center gap-3">
                @if ($hasBlocked)
                    <button type="button" disabled
                            class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-400 cursor-not-allowed"
                            title="Linked quotations with supplier data (prices/documents/POs) must be cleared first">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                @else
                    <form id="preplistDeleteForm" method="POST" action="{{ route('preplist.destroy', ['preplist' => $preplist->id]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="button" id="preplistDeleteBtn" aria-haspopup="dialog"
                                class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Delete
                        </button>
                    </form>
                @endif

                @if (auth()->user()?->canModule('preplist', 'manage'))
                    <a href="{{ route('preplist.edit', ['preplist' => $preplist->id]) }}"
                       class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit prep list
                    </a>
                @else
                    <button type="button" data-access-restricted-modal-toggle
                            title="Your role does not have access to that module."
                            class="flex cursor-not-allowed items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit prep list
                    </button>
                @endif

                @if (auth()->user()?->canModule('preplist', 'manage'))
                    <a href="{{ route('preplist.generate-rfq', ['preplist' => $preplist->id]) }}"
                       class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                        </svg>
                        Generate RFQ for supplier
                    </a>
                @else
                    <button type="button" data-access-restricted-modal-toggle
                            title="Your role does not have access to that module."
                            class="flex cursor-not-allowed items-center gap-2 rounded-lg bg-gray-300 px-4 py-2 text-sm font-medium text-gray-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                        </svg>
                        Generate RFQ for supplier
                    </button>
                @endif
            </div>
        </div>
    </div>{{-- /flex column --}}

    {{-- Delete prep list confirmation modal --}}
    <div id="preplistDeleteModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="preplistDeleteTitle">
        <div id="preplistDeleteOverlay" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
            <div class="flex items-start gap-3 px-5 py-4">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-50">
                    <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 id="preplistDeleteTitle" class="text-base font-bold text-gray-900">Delete prep list?</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        <span class="font-medium text-gray-700">{{ $preplist->name }}</span>
                        and all of its items will be permanently deleted. This action cannot be undone.
                    </p>
                    @if (isset($linkedQuotations) && $linkedQuotations->isNotEmpty())
                        <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            <p class="font-semibold">These quotations will also be deleted (no supplier data yet):</p>
                            <ul class="mt-1 space-y-0.5">
                                @foreach ($linkedQuotations as $quotation)
                                    <li>&bull; {{ $quotation->rfq_number ?: 'Quotation #'.$quotation->id }} ({{ $quotation->supplier?->name ?? 'Supplier removed' }})</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                <button type="button" id="preplistDeleteCancel"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="preplistDeleteConfirm"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 cursor-pointer">
                    Delete
                </button>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Delete prep list confirmation modal
    (function () {
        const btn = document.getElementById('preplistDeleteBtn');
        const modal = document.getElementById('preplistDeleteModal');
        const overlay = document.getElementById('preplistDeleteOverlay');
        const cancelBtn = document.getElementById('preplistDeleteCancel');
        const confirmBtn = document.getElementById('preplistDeleteConfirm');
        const form = document.getElementById('preplistDeleteForm');

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            confirmBtn.focus();
        }
        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (btn) btn.focus();
        }

        if (btn) { btn.addEventListener('click', openModal); }
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        if (overlay) overlay.addEventListener('click', closeModal);
        if (confirmBtn && form) confirmBtn.addEventListener('click', () => form.submit());
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
@endpush

@push('scripts')
<script>
    // Auto-dismiss the anti-delete warning after a few seconds.
    (function () {
        var banner = document.getElementById('quotationWarningBanner');
        if (!banner) return;

        setTimeout(function () { banner.classList.add('is-hiding'); }, 4000);
        setTimeout(function () { banner.classList.add('hidden'); }, 4350);
    })();
</script>
@endpush
