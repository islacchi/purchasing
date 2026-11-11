@extends('layouts.app')

@section('page-title', 'QUOTATION')
@section('title', 'Quotation - Purchasing')

@section('header-actions')
@endsection

@section('content')
    @php
        $preplist = $quotation->preplist;
        $supplier = $quotation->supplier;

        $totalItems = $items->count();

        $itemsQuoted = $items
            ->filter(fn ($item) => $quotationItems->get((int) $item['key'])?->unit_price !== null)
            ->count();

        $quotedTotal = $items
            ->filter(fn ($item) => $quotationItems->get((int) $item['key'])?->unit_price !== null)
            ->sum(fn ($item) => (float) $quotationItems->get((int) $item['key'])->unit_price * $item['requested']);

        // Offers whose shelf life is below the required minimum. A cheap
        // short-dated quote should not win silently, so these are flagged.
        $shortDatedCount = $items
            ->filter(function ($item) use ($quotationItems) {
                $quotedItem = $quotationItems->get((int) $item['key']);
                $required = $item['min_remaining_months'] ?? 0;
                $offered = $quotedItem?->offered_shelf_life_months;

                return $quotedItem !== null
                    && $quotedItem->unit_price !== null
                    && $required > 0
                    && $offered !== null
                    && $offered < $required;
            })
            ->count();

        $status = $itemsQuoted > 0 ? 'Quoted' : 'Awaiting quote';

        $fractionColor = $itemsQuoted === 0
            ? 'text-gray-400'
            : ($itemsQuoted >= $totalItems ? 'text-green-600' : 'text-amber-500');

        $formatDocumentSize = function (?int $bytes): ?string {
            if (! $bytes) {
                return null;
            }

            if ($bytes < 1024 * 1024) {
                return number_format($bytes / 1024, 0).' KB';
            }

            return number_format($bytes / (1024 * 1024), 1).' MB';
        };

        $peso = fn ($amount) => '₱ '.number_format((float) $amount, 2);

        $documentSize = $formatDocumentSize($quotation->document_size);
    @endphp

    <div class="flex h-full min-h-0 flex-col">
        {{-- Supplier / prep list summary --}}
        <div class="mb-6 shrink-0 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-xl font-bold text-gray-900">{{ $supplier?->name ?? 'Supplier removed' }}</h2>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $preplist->name }}
                        <span class="mx-1 text-gray-300">&middot;</span>
                        {{ $preplist->reference_no }}
                    </p>
                    @if ($quotation->rfq_number)
                        <p class="mt-0.5 text-xs text-gray-400">{{ $quotation->rfq_number }}</p>
                    @endif

                    <div class="mt-4">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Items quoted</p>
                        <p class="mt-1 text-lg font-bold {{ $fractionColor }}">
                            {{ $itemsQuoted }} / {{ $totalItems }}
                        </p>
                    </div>
                </div>

                <span class="inline-flex flex-shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-medium
                    {{ $status === 'Quoted' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $status }}
                </span>
            </div>
        </div>

        @if ($shortDatedCount > 0)
            {{-- Shelf-life warning: this supplier offered stock shorter than required. --}}
            <div class="mb-6 flex shrink-0 items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
                </svg>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-800">
                        {{ $shortDatedCount }} {{ $shortDatedCount === 1 ? 'item' : 'items' }} below the required shelf life
                    </p>
                    <p class="mt-0.5 text-xs text-amber-700">
                        The supplier offered stock with less remaining shelf life than requested. Confirm before awarding, or ask for fresher stock.
                    </p>
                </div>
            </div>
        @endif

        {{-- Supplier's quotation document --}}
        <p class="mb-2 shrink-0 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
            Supplier's quotation document
        </p>

        @if (session('status'))
            <div class="mb-4 flex shrink-0 items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->has('document'))
            <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first('document') }}
            </div>
        @endif

        {{-- The file chooser lives in this hidden form; the buttons below open it and submit on selection. --}}
        <form id="supplierQuotationForm" method="POST" action="{{ route('quotation.store-document', ['quotation' => $quotation->id]) }}" enctype="multipart/form-data" class="hidden">
            @csrf
            <input id="supplierQuotationFile" type="file" name="document" accept=".xlsx" required onchange="this.form.requestSubmit()">
        </form>

        @if ($quotation->document_path)
            <div class="mb-6 flex shrink-0 items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-red-50">
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900">{{ $quotation->document_name }}</p>
                        @if ($documentSize)
                            <p class="text-xs text-gray-500">{{ $documentSize }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    <label for="supplierQuotationFile"
                           class="cursor-pointer text-sm font-medium text-gray-500 transition-colors hover:text-[#2a7a94] hover:underline">
                        Replace
                    </label>
                    <a href="{{ route('quotation.download', ['quotation' => $quotation->id]) }}"
                       class="flex items-center gap-1.5 text-sm font-medium text-[#2a7a94] hover:underline"
                       aria-label="Download {{ $quotation->document_name }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Download
                    </a>
                </div>
            </div>
        @else
            <div class="mb-6 flex shrink-0 items-center gap-3 rounded-lg border border-dashed border-gray-300 bg-white px-4 py-3">
                <svg class="h-5 w-5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v4a1 1 0 0 0 1 1h4"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9.5 13.5 5 5m0-5-5 5"/>
                </svg>
                <p class="text-sm text-gray-500">No supplier quotation submitted yet. Use “Submit quotation” to upload the supplier’s completed file.</p>
            </div>
        @endif

        {{-- Item breakdown --}}
        <p class="mb-2 shrink-0 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Item breakdown</p>

        <div class="min-h-[240px] flex-1 space-y-4 overflow-y-auto pr-1">
            @forelse ($items as $item)
                @php
                    $quotedItem = $quotationItems->get((int) $item['key']);
                    $isQuoted = $quotedItem?->unit_price !== null;
                    $requiredShelf = $item['min_remaining_months'] ?? 0;
                    $offeredShelf = $quotedItem?->offered_shelf_life_months;
                    $isShortDated = $isQuoted
                        && $requiredShelf > 0
                        && $offeredShelf !== null
                        && $offeredShelf < $requiredShelf;
                @endphp

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-start justify-between gap-4 px-5 py-4">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-bold text-gray-900">{{ $item['name'] }}</p>
                                @if ($isShortDated)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                                        Short-dated
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $item['requested'] }} {{ $item['unit'] }} requested
                            </p>
                            <p class="mt-0.5 text-xs text-gray-500">
                                @if ($requiredShelf > 0)
                                    Shelf life required:
                                    <span class="font-medium text-gray-700">&ge; {{ $requiredShelf }} months at delivery</span>
                                @else
                                    Non-perishable &mdash; no shelf-life requirement
                                @endif
                            </p>
                        </div>

                        <div class="flex-shrink-0 text-right">
                            @if ($isQuoted)
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $peso($quotedItem->unit_price) }} / {{ $item['unit'] }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $peso($quotedItem->unit_price * $item['requested']) }} subtotal
                                </p>
                                @if ($requiredShelf > 0)
                                    <p class="mt-1 text-xs {{ $isShortDated ? 'font-semibold text-amber-600' : 'text-gray-500' }}">
                                        @if ($offeredShelf !== null)
                                            Offered: {{ $offeredShelf }} months
                                            @if ($isShortDated)
                                                &mdash; below requirement
                                            @endif
                                        @else
                                            Offered shelf life not stated
                                        @endif
                                    </p>
                                @endif
                            @else
                                <p class="text-sm text-gray-400">Pending</p>
                            @endif
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 border-t border-gray-100">
                        @foreach ($item['allocations'] as $allocation)
                            <div class="flex items-center justify-between gap-4 px-5 py-3">
                                <div class="flex min-w-0 items-center gap-2">
                                    <svg class="h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z"/>
                                    </svg>
                                    <p class="truncate text-sm text-gray-600">
                                        {{ $allocation['project'] }}
                                        <span class="mx-1 text-gray-300">&middot;</span>
                                        <span class="text-xs text-gray-400">{{ $allocation['reference'] }}</span>
                                    </p>
                                </div>
                                <p class="flex-shrink-0 text-sm font-medium text-gray-900">
                                    {{ $allocation['qty'] }} {{ $allocation['unit'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                    <h3 class="text-base font-semibold text-gray-900">No items in this prep list</h3>
                    <p class="mt-1 text-sm text-gray-500">This quotation has no items to track.</p>
                </div>
            @endforelse
        </div>

        {{-- Grand total for quoted items only --}}
        @if ($quotedTotal > 0)
            <div class="mt-3 flex shrink-0 items-center justify-end gap-2 text-sm">
                <span class="font-bold text-gray-900">Total quoted so far:</span>
                <span class="font-bold text-gray-900">{{ $peso($quotedTotal) }}</span>
            </div>
        @endif

        {{-- Footer actions --}}
        <div class="mt-6 flex shrink-0 flex-wrap items-center justify-between gap-3">
            <a href="{{ route('quotation') }}"
               class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to list
            </a>

            <div class="flex flex-wrap items-center gap-3">
                @if ($status === 'Awaiting quote')
                <form id="quotationDeleteForm" method="POST" action="{{ route('quotation.destroy', ['quotation' => $quotation->id]) }}">
                    @csrf
                    @method('DELETE')
                    <button type="button" id="quotationDeleteBtn" aria-haspopup="dialog"
                            class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                        </svg>
                        Remove quotation
                    </button>
                </form>
                @endif

                @if ($status === 'Quoted')
                    <a href="{{ route('purchase-order.create', ['rfq' => $quotation->id]) }}"
                       class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3h-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                        Proceed to PO
                    </a>
                @else
                    <label for="supplierQuotationFile"
                           class="flex cursor-pointer items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                        </svg>
                        Submit quotation
                    </label>
                @endif
            </div>
        </div>

        {{-- Delete quotation confirmation modal --}}
        <div id="quotationDeleteModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="quotationDeleteTitle">
            <div id="quotationDeleteOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
                <div class="flex items-start gap-3 px-5 py-4">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-50">
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 id="quotationDeleteTitle" class="text-base font-bold text-gray-900">Delete quotation?</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <span class="font-medium text-gray-700">{{ $supplier?->name ?? 'Supplier removed' }}</span>
                            quotation for <span class="font-medium text-gray-700">{{ $preplist->name }}</span> will be permanently deleted. This action cannot be undone.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                    <button type="button" id="quotationDeleteCancel"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" id="quotationDeleteConfirm"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors cursor-pointer">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Delete quotation confirmation modal
        (function () {
            const btn = document.getElementById('quotationDeleteBtn');
            const modal = document.getElementById('quotationDeleteModal');
            const overlay = document.getElementById('quotationDeleteOverlay');
            const cancelBtn = document.getElementById('quotationDeleteCancel');
            const confirmBtn = document.getElementById('quotationDeleteConfirm');
            const form = document.getElementById('quotationDeleteForm');

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

            if (btn) {
                btn.addEventListener('click', openModal);
            }
            cancelBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', closeModal);
            confirmBtn.addEventListener('click', function () {
                if (form) form.submit();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
    @endpush

    @stack('scripts')
@endsection
