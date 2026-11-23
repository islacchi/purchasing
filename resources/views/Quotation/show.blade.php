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

        // Aggregate figures for the item-breakdown header.
        $totalRequested = $items->sum('requested');
        $sourceProjectCount = $items
            ->flatMap(fn ($item) => collect($item['allocations'] ?? [])->pluck('project'))
            ->filter()
            ->unique()
            ->count();

        // Difference (in days) between the required expiry date and the
        // supplier's expiry that still counts as "close" rather than "good".
        $expiryCloseWindow = 30;

        // Uploading the supplier's file requires Manage access for the
        // Quotation module; View-only roles see the buttons disabled.
        $canManageQuotation = auth()->user()?->canModule('quotation', 'manage') ?? false;
        $canManagePurchaseOrder = auth()->user()?->canModule('purchase-order', 'manage') ?? false;

        // A view-only user never gets the destructive confirmation — the
        // same modal shows the access-restriction instead, before any POST.
        $removeAccessDenied = ! $canManageQuotation;
        if ($removeAccessDenied) {
            $removeBlockedReason = 'Your role does not have access to that module.';
        }

        $documentSize = $formatDocumentSize($quotation->document_size);
    @endphp

    @if ($canManageQuotation)
    {{-- The file chooser lives in this hidden form; the Replace / Submit buttons open it and submit on selection. --}}
    <form id="supplierQuotationForm" method="POST" action="{{ route('quotation.store-document', ['quotation' => $quotation->id]) }}" enctype="multipart/form-data" class="hidden">
        @csrf
        <input id="supplierQuotationFile" type="file" name="document" accept=".xlsx" required onchange="this.form.requestSubmit()">
        <input type="hidden" name="expected_document_fingerprint"
               value="{{ md5(($quotation->document_path ?? '').'|'.($quotation->document_name ?? '').'|'.((int) ($quotation->document_size ?? 0))) }}">
    </form>
    @endif

    <div class="flex min-h-full flex-col lg:h-full lg:min-h-0">

        @if (! $preplist)
            <div class="mb-4 flex shrink-0 items-start justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-5 py-4">
                <div class="flex min-w-0 items-start gap-3">
                    <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
                    </svg>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-red-800">This quotation’s prep list has been deleted</p>
                        <p class="mt-0.5 text-xs text-red-700">
                            Quoted prices are preserved for reference, but this quotation cannot be compared against the requested items, re-uploaded, or sent to a PO until the prep list is restored.
                        </p>
                    </div>
                </div>
                @if ($canManageQuotation)
                    <form method="POST" action="{{ route('quotation.restore-preplist', ['quotation' => $quotation->id]) }}" class="shrink-0 self-center">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-2 rounded-md border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-red-700 transition-colors hover:bg-red-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 14.5v4.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75v-4.25m-18 0 4.5 4.5 4.5-4.5m-4.5 4.5V9a5 5 0 0 1 10 0v10"/>
                            </svg>
                            Restore prep list
                        </button>
                    </form>
                @endif
            </div>
        @endif

        @if ($errors->any() && ! $errors->has('document'))
            <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Page header with status chips --}}
        <div class="mb-4 flex shrink-0 flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-gray-900">{{ $supplier?->name ?? 'Supplier removed' }}</h2>
                <p class="mt-1 text-xs text-gray-500">
                    {{ $preplist?->name ?? 'Prep list removed' }}
                    <span class="mx-1 text-gray-300">&middot;</span>
                    {{ $preplist?->reference_no ?? 'Prep list removed' }}
                    @if ($quotation->rfq_number)
                        <span class="mx-1 text-gray-300">&middot;</span>
                        {{ $quotation->rfq_number }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                    {{ $itemsQuoted }} / {{ $totalItems }} items quoted
                </span>
                @if ($status === 'Quoted')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                        Quoted
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Awaiting quote
                    </span>
                @endif
            </div>
        </div>

        {{-- Main two-column layout --}}
        <div class="grid grid-cols-1 items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[330px_minmax(0,1fr)]">

            {{-- LEFT RAIL --}}
            <div class="flex flex-col gap-4 self-start lg:max-h-full lg:overflow-y-auto">

                {{-- Quotation details --}}
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Quotation details</p>
                    <div class="mt-2 divide-y divide-gray-100">
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Supplier</span>
                            <span class="text-right font-semibold text-gray-900">{{ $supplier?->name ?? 'Supplier removed' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Prep list</span>
                            <span class="text-right font-semibold text-gray-900">{{ $preplist?->name ?? 'Removed' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Prep list ref</span>
                            <span class="text-right font-semibold text-gray-900">{{ $preplist?->reference_no ?? '—' }}</span>
                        </div>
                        @if ($quotation->rfq_number)
                            <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                                <span class="text-gray-500">RFQ number</span>
                                <span class="text-right font-semibold text-gray-900">{{ $quotation->rfq_number }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Items quoted</span>
                        <span class="text-2xl font-extrabold leading-none {{ $fractionColor }}">{{ $itemsQuoted }}<span class="text-sm font-semibold text-gray-400"> / {{ $totalItems }}</span></span>
                    </div>
                </div>

                {{-- Supplier's quotation document --}}
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Supplier's quotation document</p>

                    @if ($errors->has('document'))
                        <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                            {{ $errors->first('document') }}
                        </div>
                    @endif

                    @if ($quotation->document_path)
                        <div class="mt-2 flex items-center justify-between gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                            <div class="flex min-w-0 flex-1 items-center gap-3">
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
                                @if ($preplist && $canManageQuotation && ! ($removeBlockedReason ?? null))
                                    <label for="supplierQuotationFile"
                                           class="cursor-pointer text-sm font-medium text-gray-500 transition-colors hover:text-[#2a7a94] hover:underline">
                                        Replace
                                    </label>
                                @elseif ($preplist && ! ($removeBlockedReason ?? null))
                                    <span class="cursor-not-allowed text-sm font-medium text-gray-300" title="Your role does not have access to upload files.">Replace</span>
                                @elseif (! ($removeBlockedReason ?? null))
                                    <span class="cursor-not-allowed text-sm font-medium text-gray-300">Replace</span>
                                @else
                                    <span class="cursor-not-allowed text-sm font-medium text-gray-300" title="{{ $removeBlockedReason }}">Replace</span>
                                @endif
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
                        <div class="mt-2 flex items-center gap-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                            <svg class="h-5 w-5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v4a1 1 0 0 0 1 1h4"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9.5 13.5 5 5m0-5-5 5"/>
                            </svg>
                            <p class="text-xs leading-snug text-gray-500">No supplier quotation submitted yet. Use <span class="font-medium text-gray-700">Submit quotation</span> to upload the supplier’s completed file.</p>
                        </div>
                    @endif

                    @if (! $quotation->document_path)
                        @if ($preplist && $canManageQuotation && ! ($removeBlockedReason ?? null))
                            <label for="supplierQuotationFile"
                                   class="mt-3 flex w-full cursor-pointer items-center justify-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                </svg>
                                Submit quotation
                            </label>
                        @else
                            <span class="mt-3 flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-gray-300 px-4 py-2 text-sm font-medium text-gray-500"
                                  title="{{ $removeBlockedReason ?? ($preplist ? 'Your role does not have access to upload files.' : 'This quotation’s prep list has been deleted') }}">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                </svg>
                                Submit quotation
                            </span>
                        @endif
                    @endif
                </div>
            </div>

            {{-- RIGHT — ITEM BREAKDOWN --}}
            <div class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm lg:h-full lg:min-h-0">
                <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Item breakdown</p>
                        <p class="mt-0.5 text-[11px] text-gray-500">{{ $totalItems }} {{ $totalItems === 1 ? 'line' : 'lines' }} · across {{ $sourceProjectCount }} {{ $sourceProjectCount === 1 ? 'project' : 'projects' }} · Total requested: {{ number_format($totalRequested) }} units</p>
                    </div>
                    <p class="text-[11px] text-gray-500">Grand total: <span class="text-xs font-bold text-gray-900">{{ $peso($quotedTotal) }}</span></p>
                </div>

                <div class="lg:min-h-0 lg:flex-1 lg:overflow-y-auto">
                    <table class="w-full table-fixed">
                        <colgroup>
                            <col class="w-[38%]">
                            <col class="w-[11%]">
                            <col class="w-[15%]">
                            <col class="w-[12%]">
                            <col class="w-[24%]">
                        </colgroup>
                        <thead class="sticky top-0 z-10 bg-[#0e5266]">
                            <tr>
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Item</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5 text-center text-[10px] font-semibold uppercase tracking-wider text-white">Requested</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Shelf life</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5 text-center text-[10px] font-semibold uppercase tracking-wider text-white">Quoted</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-2.5 text-center text-[10px] font-semibold uppercase tracking-wider text-white">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $quotedItem = $quotationItems->get((int) $item['key']);
                                    $isQuoted = $quotedItem?->unit_price !== null;
                                    $requiredShelf = $item['min_remaining_months'] ?? 0;
                                    $brand = $quotedItem?->brand;
                                    $expiryDate = $quotedItem?->expiry;
                                    $expiry = $expiryDate?->format('M j, Y');
                                    $isZebra = ($loop->iteration % 2) === 0;
                                @endphp
                                <tr class="{{ $isZebra ? 'bg-[#f2f7f8]' : 'bg-white' }} transition-colors hover:bg-[#e4edf0]">
                                    <td class="px-4 py-2.5 align-top">
                                        <div class="flex items-start gap-1.5">
                                            @if ($isQuoted || $item['allocations'])
                                                <button type="button"
                                                        data-toggle-sources="{{ $item['key'] }}"
                                                        aria-expanded="false"
                                                        aria-label="Toggle breakdown for {{ $item['name'] }}"
                                                        title="Toggle breakdown"
                                                        class="mt-0.5 shrink-0 rounded p-0.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                                    <svg class="source-chevron h-4 w-4 -rotate-90 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>
                                            @endif
                                            <p class="break-words text-[13px] font-semibold leading-snug text-gray-900">{{ $item['name'] }}</p>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-center align-top text-xs font-semibold text-gray-900">{{ number_format($item['requested']) }} {{ $item['unit'] }}</td>
                                    <td class="px-4 py-2.5 align-top text-xs text-gray-700">
                                        @if ($requiredShelf > 0)
                                            &ge; {{ $requiredShelf }} month{{ $requiredShelf === 1 ? '' : 's' }}
                                        @else
                                            Non-perishable
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-center align-top">
                                        @if ($isQuoted)
                                            <span class="text-xs font-bold text-gray-900">{{ $peso($quotedItem->unit_price) }} / {{ $item['unit'] }}</span>
                                            <span class="block text-[9px] font-medium text-gray-500">{{ $peso($quotedItem->unit_price * $item['requested']) }} subtotal</span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-center align-top">
                                        @if ($isQuoted)
                                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-blue-800">Quoted</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-600">Awaiting quote</span>
                                        @endif
                                    </td>
                                </tr>
                                @if ($isQuoted)
                                    <tr class="{{ $isZebra ? 'bg-[#f2f7f8]' : 'bg-white' }} source-row hidden transition-colors hover:bg-[#e4edf0]" data-source-of="{{ $item['key'] }}">
                                        <td colspan="5" class="border-t border-dashed border-gray-200 px-4 py-2">
                                            <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-gray-500">
                                                <span>Brand: <span class="font-medium text-gray-700">{{ $brand ?: 'Not stated' }}</span></span>
                                                <span class="text-gray-300">·</span>
                                                <span>Expiry: <span class="font-medium text-gray-700">{{ $expiry ?: 'Not stated' }}</span></span>
                                                @if (! empty($item['box_size']) && $quotedItem->supplier_box_size)
                                                    <span class="text-gray-300">·</span>
                                                    <span>Supplier pack: <span class="font-semibold text-gray-700">Box of {{ $quotedItem->supplier_box_size }}</span></span>
                                                @endif
                                                @if (! empty($item['box_size']))
                                                    <span class="text-gray-300">·</span>
                                                    <span>Requirement: <span class="font-medium text-gray-700">Box of {{ $item['box_size'] }}</span></span>
                                                @endif
                                            </p>
                                            @if ($quotedItem->supplier_box_size && ! empty($item['box_size']) && $quotedItem->supplier_box_size !== $item['box_size'])
                                                @php
                                                    $needUnits = $item['requested'] * $item['box_size'];
                                                    $neededTheirBoxes = (int) ceil($needUnits / $quotedItem->supplier_box_size);
                                                @endphp
                                                <p class="mt-1.5 rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5 text-[10px] font-medium text-amber-800">
                                                    They ship <strong>Box of {{ $quotedItem->supplier_box_size }}</strong> (you asked for Box of {{ $item['box_size'] }}). To cover your requirement of {{ number_format($needUnits) }} units, you would need to buy <strong>{{ $neededTheirBoxes }} boxes</strong> from them instead of {{ $item['requested'] }}.
                                                </p>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                @foreach ($item['allocations'] as $allocation)
                                    @php
                                        // The supplier's expiry is judged against what this
                                        // particular project requires: its delivery due date
                                        // plus the prep list's minimum shelf life.
                                        $requiredExpiry = empty($allocation['required_expiry_date'])
                                            ? null
                                            : \Carbon\CarbonImmutable::parse($allocation['required_expiry_date'])->startOfDay();
                                        $requiredExpiryLabel = $requiredExpiry?->format('M j, Y');
                                        $allocationStatus = 'unknown';

                                        if ($isQuoted && $expiryDate && $requiredExpiry) {
                                            if ($expiryDate->lessThan($requiredExpiry)) {
                                                $allocationStatus = 'short';
                                            } elseif ($expiryDate->lessThanOrEqualTo($requiredExpiry->addDays($expiryCloseWindow))) {
                                                $allocationStatus = 'close';
                                            } else {
                                                $allocationStatus = 'good';
                                            }
                                        }

                                        $statusClass = match ($allocationStatus) {
                                            'short' => 'text-red-600',
                                            'close' => 'text-yellow-600',
                                            'good' => 'text-green-600',
                                            default => 'text-gray-400',
                                        };
                                    @endphp

                                    <tr class="{{ $isZebra ? 'bg-[#f2f7f8]' : 'bg-white' }} source-row hidden transition-colors hover:bg-[#e4edf0]" data-source-of="{{ $item['key'] }}">
                                        <td colspan="5" class="border-t border-dashed border-gray-200 py-1.5 pl-10 pr-4 text-[10px] text-gray-500">
                                            Contributes <span class="font-semibold text-gray-800">{{ number_format($allocation['qty']) }} {{ $allocation['unit'] }}</span> · <span class="text-gray-700">{{ $allocation['project'] }}</span> · <span class="text-gray-500">{{ $allocation['reference'] }}</span>
                                            @if ($isQuoted)
                                                <span class="ml-2 {{ $statusClass }}" data-expiry-status="{{ $allocationStatus }}">
                                                    @if ($allocationStatus === 'short')
                                                        Expiry {{ $expiry }} &middot; before the required {{ $requiredExpiryLabel }}
                                                    @elseif ($allocationStatus === 'close')
                                                        Expiry {{ $expiry }} &middot; close to the required {{ $requiredExpiryLabel }}
                                                    @elseif ($allocationStatus === 'good')
                                                        Expiry {{ $expiry }} &middot; meets the required {{ $requiredExpiryLabel }}
                                                    @elseif ($expiry && $requiredExpiryLabel)
                                                        Expiry {{ $expiry }} &middot; required {{ $requiredExpiryLabel }}
                                                    @elseif ($requiredExpiryLabel)
                                                        Supplier expiry not stated &middot; required {{ $requiredExpiryLabel }}
                                                    @else
                                                        Supplier expiry not stated
                                                    @endif
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center">
                                        <h3 class="text-base font-semibold text-gray-900">No items in this prep list</h3>
                                        <p class="mt-1 text-sm text-gray-500">This quotation has no items to track.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Footer actions --}}
        <div class="no-print mt-4 flex shrink-0 flex-wrap items-center justify-between gap-3">
            <a href="{{ route('quotation') }}"
               class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to list
            </a>

            <div class="flex flex-wrap items-center gap-3">
                @if ($quotation->document_path)
                    {{-- Removing a quotation only removes the supplier's uploaded file
                         (and its quoted prices); the entry itself is deleted elsewhere.
                         When a purchase order already uses these prices, the same modal
                         shows the reason instead of confirming the removal. --}}
                    @if (! ($removeBlockedReason ?? null))
                        <form id="quotationRemoveForm" method="POST" action="{{ route('quotation.destroy-document', ['quotation' => $quotation->id]) }}">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                    <button type="button" id="quotationRemoveBtn" aria-haspopup="dialog"
                            @if (($removeBlockedReason ?? null)) title="{{ $removeBlockedReason }}" @endif
                            class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                        </svg>
                        Remove quotation
                    </button>
                @endif

                @if ($status === 'Quoted')
                    @if ($preplist)
                        @if ($canManagePurchaseOrder)
                            <form method="POST" action="{{ route('po-cart.add') }}">
                                @csrf
                                <input type="hidden" name="quotation_id" value="{{ $quotation->id }}">
                                <button type="submit"
                                        class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 10a2 2 0 100 4m8-4a2 2 0 100 4M8 13h8"/>
                                    </svg>
                                    Add to cart
                                </button>
                            </form>
                        @else
                            <button type="button" data-access-restricted-modal-toggle
                                    title="Your role does not have access to that module."
                                    class="flex cursor-not-allowed items-center gap-2 rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 10a2 2 0 100 4m8-4a2 2 0 100 4M8 13h8"/>
                                </svg>
                                Add to cart
                            </button>
                        @endif
                    @else
                        <span class="flex cursor-not-allowed items-center gap-2 rounded-lg bg-gray-300 px-4 py-2 text-sm font-medium text-gray-500"
                              title="This quotation's prep list has been deleted">
                            Add to cart
                        </span>
                    @endif
                @endif
            </div>
        </div>

        {{-- Remove quotation confirmation modal --}}
        <div id="quotationRemoveModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="quotationRemoveTitle">
            <div id="quotationRemoveOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            @if (($removeBlockedReason ?? null) === null)
                {{-- Confirmation state: the supplier's file and prices will go. --}}
                <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
                    <div class="flex items-start gap-3 px-5 py-4">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-50">
                            <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <h3 id="quotationRemoveTitle" class="text-base font-bold text-gray-900">Remove quotation?</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                The quotation file uploaded by
                                <span class="font-medium text-gray-700">{{ $supplier?->name ?? 'this supplier' }}</span>
                                will be removed and its quoted prices cleared. The quotation will return to
                                <span class="font-medium text-gray-700">Awaiting quote</span>.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                        <button type="button" id="quotationRemoveCancel"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="button" id="quotationRemoveConfirm"
                                class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors cursor-pointer">
                            Remove
                        </button>
                    </div>
                </div>
            @else
                {{-- Blocked state: explain why it cannot be removed instead of confirming. --}}
                <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
                    <div class="flex items-start gap-3 px-5 py-4">
                        @include('partials.restricted-notice', [
                            'title' => $removeAccessDenied ? 'Access restricted' : 'This quotation cannot be removed',
                            'reason' => $removeBlockedReason,
                            'titleId' => 'quotationRemoveTitle',
                        ])
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                        <button type="button" id="quotationRemoveCancel"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                            Okay
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        // Remove quotation confirmation modal
        (function () {
            const btn = document.getElementById('quotationRemoveBtn');
            const modal = document.getElementById('quotationRemoveModal');
            const overlay = document.getElementById('quotationRemoveOverlay');
            const cancelBtn = document.getElementById('quotationRemoveCancel');
            const confirmBtn = document.getElementById('quotationRemoveConfirm');
            const form = document.getElementById('quotationRemoveForm');

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                if (confirmBtn) {
                    confirmBtn.focus();
                } else if (cancelBtn) {
                    cancelBtn.focus();
                }
            }
            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (btn) btn.focus();
            }

            if (btn) {
                btn.addEventListener('click', openModal);
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeModal);
            }
            if (overlay) {
                overlay.addEventListener('click', closeModal);
            }
            if (confirmBtn && form) {
                confirmBtn.addEventListener('click', function () {
                    form.submit();
                });
            }
        })();
    </script>
    @endpush

    @push('scripts')
    <script>
        // Collapsible item breakdown rows under each item row (mirrors the
        // prep list source-line collapse behaviour).
        (function () {
            document.querySelectorAll('[data-toggle-sources]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var itemId = button.getAttribute('data-toggle-sources');
                    var chevron = button.querySelector('.source-chevron');
                    var expanded = button.getAttribute('aria-expanded') === 'true';

                    document.querySelectorAll('.source-row[data-source-of="' + itemId + '"]').forEach(function (row) {
                        row.classList.toggle('hidden', expanded);
                    });

                    button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    if (chevron) {
                        chevron.classList.toggle('-rotate-90', expanded);
                    }
                });
            });
        })();
    </script>
    @endpush
@endsection
