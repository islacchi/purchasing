@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', 'Prep List - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="flex h-full min-h-0 flex-col">

    @if (session('status'))
        <div class="mb-4 flex shrink-0 items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @php
        $sourceProjects = $preplist->sourceProjects();

        $itemGroups = $preplist->items->map(function ($preplistItem) {
            $unit = $preplistItem->item?->unit ?? '';

            return [
                'name' => $preplistItem->item?->name ?? 'Item',
                'total' => $preplistItem->totalQuantity().' '.($unit !== '' ? $unit : 'unit').' total',
                'sources' => $preplistItem->sources->map(function ($source) use ($unit) {
                    return [
                        'project' => $source->projectItem?->project?->title ?? 'Project removed',
                        'qty' => $source->qty.' '.$unit,
                    ];
                }),
            ];
        });
    @endphp

    {{-- Top row: heading + edit button --}}
    <div class="flex shrink-0 items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $preplist->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $preplist->reference_no }}</p>
        </div>
        <a href="{{ route('preplist.edit', ['preplist' => $preplist->id]) }}"
           class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit prep list
        </a>
    </div>

    {{-- Main two-column layout --}}
    <div class="grid min-h-0 flex-1 grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">

        {{-- LEFT COLUMN --}}
        <div class="min-h-0 space-y-6">
            {{-- Info boxes (2x2 grid) --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3">
                    <p class="text-[11px] italic text-gray-400">Created by</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ $preplist->creator?->name ?? '—' }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3">
                    <p class="text-[11px] italic text-gray-400">Projects</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ $sourceProjects->count() }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3">
                    <p class="text-[11px] italic text-gray-400">Date Created</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ $preplist->created_at?->format('M d, Y g:i A') ?? '—' }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3">
                    <p class="text-[11px] italic text-gray-400">Total Items</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ $preplist->items->count() }}</p>
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <form method="POST" action="{{ route('preplist.notes', ['preplist' => $preplist->id]) }}" class="p-4">
                    @csrf
                    @method('PATCH')
                    <label for="notes" class="block text-xs font-medium text-gray-500 mb-1">Notes:</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Add notes here..."
                              class="w-full px-3 py-2 rounded-md border border-gray-200 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 resize-none">{{ old('notes', $preplist->notes) }}</textarea>
                    <div class="mt-2 flex justify-end">
                        <button type="submit"
                                class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Save notes
                        </button>
                    </div>
                </form>
            </div>

            {{-- Source projects --}}
            <div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Source Projects</p>
                <div class="space-y-3">
                    @forelse($sourceProjects as $project)
                        <a href="{{ route('projects.show', ['project' => $project->id]) }}"
                           class="block bg-white rounded-lg border border-gray-200 shadow-sm p-4 hover:border-[#2a7a94] hover:shadow-md transition-all cursor-pointer">
                            <p class="text-sm font-bold text-gray-900">{{ $project->title }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $project->reference_no }}</p>
                        </a>
                    @empty
                        <p class="rounded-lg border border-dashed border-gray-300 bg-white px-4 py-6 text-center text-sm text-gray-500">
                            No source projects recorded.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN — item list scrolls here --}}
        <div class="flex min-h-0 flex-col">
            <p class="shrink-0 mb-3 text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Items</p>
            <div class="min-h-[220px] flex-1 space-y-4 overflow-y-auto pr-1">
                @forelse($itemGroups as $group)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                        {{-- Item header --}}
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <p class="font-bold text-gray-900">{{ $group['name'] }}</p>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                {{ $group['total'] }}
                            </span>
                        </div>
                        {{-- Source rows --}}
                        <div class="divide-y divide-gray-100">
                            @foreach($group['sources'] as $source)
                                <div class="flex items-center justify-between px-5 py-3">
                                    <p class="text-sm text-gray-500">{{ $source['project'] }}</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $source['qty'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                        <p class="text-sm text-gray-500">No items recorded for this prep list.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- Bottom action buttons --}}
    <div class="flex shrink-0 items-center justify-end gap-3 mt-6">
        <form id="preplistDeleteForm" method="POST" action="{{ route('preplist.destroy', ['preplist' => $preplist->id]) }}">
            @csrf
            @method('DELETE')
            <button type="button" id="preplistDeleteBtn" aria-haspopup="dialog"
                    class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete prep list
            </button>
        </form>
        <button class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Export to Excel
        </button>
        <a href="{{ route('preplist.generate-rfq', ['preplist' => $preplist->id]) }}"
           class="flex items-center gap-2 px-4 py-2 rounded-md bg-[#2a7a94] text-white text-sm font-medium hover:bg-[#236b80] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
            </svg>
            Generate RFQ for supplier
        </a>
    </div>

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
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                <button type="button" id="preplistDeleteCancel"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="preplistDeleteConfirm"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors cursor-pointer">
                    Delete
                </button>
            </div>
        </div>
    </div>
    </div><!-- /flex column -->

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
            btn.focus();
        }

        btn.addEventListener('click', openModal);
        cancelBtn.addEventListener('click', closeModal);
        overlay.addEventListener('click', closeModal);
        confirmBtn.addEventListener('click', function () {
            form.submit();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
@endpush
