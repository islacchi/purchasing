@extends('layouts.app')

@section('page-title', 'PREPLIST DETAILS')
@section('title', 'Preplist comparison - Purchasing')

@section('header-actions')
@endsection

@php
    $peso = fn ($amount) => '₱ '.number_format((float) $amount, 2);
@endphp

@section('content')

    {{-- Back to tracker --}}
    <div class="mb-3 flex items-center justify-between gap-3">
        <a href="{{ route('quotation') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 transition-colors hover:text-[#2a7a94]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to tracker
        </a>

        <button type="button" id="preplistPrintBtn"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50"
                title="Print an officio landscape sheet with the full comparison">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V4a1 1 0 011-1h10a1 1 0 011 1v5M6 17h12M6 13H4a2 2 0 01-2-2v-3a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2m-12 0v6a1 1 0 001 1h10a1 1 0 001-1v-6"/>
            </svg>
            Print comparison
        </button>
    </div>

    {{-- Banner: PREPLIST DETAILS --}}
    <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-[#0e5266] shadow-sm">
        <div class="px-6 py-5 text-center">
            <h1 class="text-xs font-bold uppercase tracking-widest text-white/70">Preplist details</h1>
            <p class="mt-1 text-xl font-bold text-white">{{ $preplist->name }}</p>
            <p class="mt-0.5 text-xs text-white/60">{{ $preplist->reference_no }}</p>
        </div>
    </div>

    {{-- Quotation columns: one per supplier --}}
    @if (empty($sections))
        <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <p class="text-sm font-semibold text-gray-700">No quotations yet for this prep list.</p>
            <p class="mt-1 text-xs text-gray-500">Generate the RFQ from the prep list, then come back once suppliers reply.</p>
        </div>
    @else
        <div class="mb-6">
            <p class="mb-3 text-[11px] text-gray-500">
                <span class="mr-3 inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-green-600"></span><span class="font-semibold text-green-800">Best price</span></span>
                <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-[#2a7a94]"></span><span class="font-semibold text-[#0e5266]">Best expiry</span></span>
                <span class="ml-3 inline-flex items-center gap-1.5"><span class="h-2.5 w-4 rounded-sm ring-1 ring-green-300 bg-[linear-gradient(90deg,#bbf7d0,#99f6e4)]"></span><span class="font-semibold text-green-800">Best price + expiry</span> row</span>
                <span class="ml-2 text-gray-400">Highlighted per item across supplier columns.</span>
            </p>
            <div class="grid gap-4" style="grid-template-columns: repeat({{ count($sections) }}, minmax(min-content, 1fr));">
                @foreach ($sections as $section)
                    @php
                        $quotation = $section['quotation'];
                        $quoted = collect($section['lines'])->filter(fn ($line) => $line && $line['unit_price'] !== null)->count();
                    @endphp
                    <div class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-5 py-3">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">Quotation by {{ $section['supplier'] }}</h2>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ $section['rfq_number'] }}
                                @if ($quoted > 0)
                                    · <span class="text-blue-600">Quoted</span>
                                @else
                                    · <span class="text-gray-400">Awaiting quote</span>
                                @endif
                            </p>
                        </div>

                        <div class="flex-1 divide-y divide-gray-100 px-5 py-3">
                            @foreach ($items as $preplistItem)
                                @php
                                    $line = $section['lines'][$preplistItem->id] ?? null;
                                    $itemName = $preplistItem->item?->name ?? 'Item';
                                    $unit = $preplistItem->item?->unit ?? '';
                                    $requested = $preplistItem->remainingQuantity();
                                    $highlight = $highlights[$preplistItem->id] ?? ['best_price' => null, 'best_expiry' => null];
                                    $lineIsBestPrice = $line !== null
                                        && $line['unit_price'] !== null
                                        && $highlight['best_price'] !== null
                                        && abs((float) $line['unit_price'] - $highlight['best_price']) < 0.0001;
                                    $lineIsBestExpiry = $line !== null
                                        && $line['expiry'] !== null
                                        && $highlight['best_expiry'] !== null
                                        && $line['expiry'] === $highlight['best_expiry'];
                                    $lineIsDoubleBest = $lineIsBestPrice && $lineIsBestExpiry;
                                @endphp
                                <div class="flex items-start justify-between gap-3 py-3 text-sm @if ($lineIsDoubleBest) -mx-2 rounded-md px-2 ring-1 ring-green-200 bg-[linear-gradient(90deg,#ecfdf3_0%,#f0fdfa_100%)] @endif">
                                    <div class="min-w-0 break-words">
                                        <p class="text-gray-900"><span class="font-medium">{{ $itemName }}</span>
                                            <span class="text-xs text-gray-500"> · {{ $requested }} {{ $unit }}</span>
                                        </p>
                                        @if ($line && $line['unit_price'] !== null)
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ $line['brand'] ?: '—' }}
                                                @if ($line['expiry_label'])
                                                    ·
                                                    @if ($lineIsBestExpiry)
                                                        <span class="font-bold text-[#0e5266]">Exp. {{ $line['expiry_label'] }}</span>
                                                        <span class="ml-0.5 inline-flex items-center rounded-full bg-[#2a7a94] px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">Best expiry</span>
                                                    @else
                                                        Exp. {{ $line['expiry_label'] }}
                                                    @endif
                                                @endif
                                            </p>
                                        @endif
                                    </div>

                                    @if ($line && $line['unit_price'] !== null)
                                        @php
                                            $lineInCart = in_array($line['id'], $cartQuoteItemIds ?? [], true);
                                            $linePreplistItemId = $preplistItem->id;
                                            $otherSupplierHolds = ! $lineInCart && in_array($linePreplistItemId, $cartPreplistIds ?? [], true);
                                            $otherSupplierName = $otherSupplierHolds ? ($cartPreplistSupplier[$linePreplistItemId] ?? 'that supplier') : null;
                                        @endphp
                                        <div class="flex shrink-0 flex-col items-end gap-0.5">
                                            <div class="flex items-center gap-2">
                                                <p class="whitespace-nowrap text-sm {{ $lineIsBestPrice ? 'font-bold text-green-800' : 'font-semibold text-gray-900' }}">{{ $peso($line['unit_price']) }}
                                                    @if ($lineIsBestPrice)
                                                        <span class="ml-0.5 inline-flex items-center rounded-full bg-green-600 px-1.5 py-0.5 align-middle text-[10px] font-semibold uppercase tracking-wide text-white">Best price</span>
                                                    @endif
                                                </p>
                                            @if ($lineInCart)
                                                <button type="button"
                                                        data-cart-remove-open
                                                        data-item-name="{{ $itemName }}"
                                                        data-supplier="{{ $section['supplier'] }}"
                                                        data-cart-item-id="{{ $cartLineIdByQuoteItem[$line['id']] ?? '' }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 text-white transition-colors hover:bg-teal-700"
                                                        title="In your cart from {{ $section['supplier'] }} — click to remove"
                                                        aria-label="Remove {{ $itemName }} from cart">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            @elseif (($line['on_po'] ?? false) === true)
                                                <button type="button" disabled
                                                        title="Already on a purchase order — cannot add"
                                                        aria-label="{{ $itemName }} from {{ $section['supplier'] }} is already on a purchase order"
                                                        class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg bg-gray-100 text-gray-300">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                </button>
                                            @elseif ($requested <= 0)
                                                <button type="button" disabled
                                                        title="Fully covered by on-hand stock — nothing to order"
                                                        aria-label="{{ $itemName }} fully covered by on-hand stock"
                                                        class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg bg-gray-100 text-gray-300">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0 0l-8-4m8 4V11M4 7l8 4m-8-4v10l8 4m0-10L4 7"/>
                                                    </svg>
                                                </button>
                                            @elseif ($otherSupplierHolds)
                                                <button type="button"
                                                        data-swap-open
                                                        data-item-name="{{ $itemName }}"
                                                        data-price="{{ $peso($line['unit_price']) }}"
                                                        data-from-supplier="{{ $otherSupplierName }}"
                                                        data-to-supplier="{{ $section['supplier'] }}"
                                                        data-quotation-item-id="{{ $line['id'] }}"
                                                        title="In your cart from {{ $otherSupplierName }} — click to switch to {{ $section['supplier'] }}"
                                                        aria-label="Switch {{ $itemName }} from {{ $otherSupplierName }} to {{ $section['supplier'] }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600 ring-1 ring-amber-200 transition-colors hover:bg-amber-100">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 4L3 8l4 4M3 8h13m4 8l-4-4m4 4l-4 4M21 16H8"/>
                                                    </svg>
                                                </button>
                                            @else
                                                <form method="POST" action="{{ route('po-cart.add') }}">
                                                    @csrf
                                                    <input type="hidden" name="quotation_item_id" value="{{ $line['id'] }}">
                                                    <button type="submit"
                                                            title="Add {{ $itemName }} at {{ $section['supplier'] }}'s price to your PO cart"
                                                            aria-label="Add {{ $itemName }} from {{ $section['supplier'] }} to the PO cart"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#2a7a94] text-white transition-colors hover:bg-[#236b80]">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 10a2 2 0 100 4m8-4a2 2 0 100 4M8 13h8"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            </div>
                                            @unless ($lineIsBestPrice)
                                                @php
                                                    $bestPrice = $highlight['best_price'] ?? 0.0;
                                                    $premium = (float) $line['unit_price'] - $bestPrice;
                                                    $premiumPct = $bestPrice > 0 ? ($premium / $bestPrice) * 100 : 0.0;
                                                @endphp
                                                @if ($premium > 0.004)
                                                    <p class="whitespace-nowrap text-[10px] font-semibold {{ $premiumPct > 10 ? 'text-red-600' : 'text-amber-600' }}"
                                                       title="Over the cheapest quote for this item">
                                                        +{{ $peso($premium) }} (+{{ number_format($premiumPct, 1) }}%)
                                                    </p>
                                                @endif
                                            @endunless
                                        </div>
                                    @else
                                        <span class="shrink-0 text-sm text-gray-400">-</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-gray-100 px-5 py-3">
                            @if ($quoted > 0)
                                @php
                                    $unquoted = count($items) - $quoted;
                                @endphp
                                <form method="POST" action="{{ route('po-cart.add') }}">
                                    @csrf
                                    <input type="hidden" name="quotation_id" value="{{ $quotation->id }}">
                                    <button type="submit"
                                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#236b80]"
                                            title="{{ $unquoted > 0
                                                ? 'Add the '.$quoted.' priced line'.($quoted === 1 ? '' : 's').' from '.$section['supplier'].' — the '.$unquoted.' unpriced line'.($unquoted === 1 ? ' will' : 's will').' be skipped'
                                                : 'Add all of '.$section['supplier'].'\'s quote to your PO cart at remaining quantities' }}">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2L5 9z"/>
                                        </svg>
                                        Add {{ $section['supplier'] }} to cart
                                        @if ($unquoted > 0)
                                            <span class="font-normal text-white/80">({{ $quoted }}/{{ count($items) }} priced)</span>
                                        @endif
                                    </button>
                                </form>
                            @else
                                <span class="block cursor-not-allowed rounded-lg bg-gray-100 px-4 py-2 text-center text-xs font-semibold text-gray-400"
                                      title="Nothing to add yet — this supplier hasn't quoted any priced line">
                                    Waiting on {{ $section['supplier'] }}'s quote
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Suggestion + price matrix --}}
    @if (! empty($items))
        <div class="grid gap-6 xl:grid-cols-2">
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gray-50 px-6 py-4 text-center">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">Suggestion — which supplier to buy per item</h2>
                </div>
                <div class="divide-y divide-gray-100">
                @if ($suggestions->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-gray-500">No items to evaluate.</div>
                @else
                    @foreach ($suggestions as $suggestion)
                        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-3">
                            <div class="min-w-0">
                                <p class="break-words text-sm text-gray-900">
                                    <span class="font-medium">{{ $suggestion['name'] }}</span>
                                    <span class="text-xs text-gray-500"> · {{ $suggestion['requested'] }} {{ $suggestion['unit'] }}</span>
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                @if ($suggestion['winner'])
                                    <p class="text-sm font-semibold text-gray-900">{{ $peso($suggestion['winner']['quote']['unit_price']) }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $suggestion['winner']['supplier'] }}
                                        @if ($suggestion['winner']['quote']['expiry_label'])
                                            · Exp. {{ $suggestion['winner']['quote']['expiry_label'] }}
                                        @else
                                            · No expiry stated
                                        @endif
                                    </p>
                                    @if (! empty($suggestion['expiry_note']))
                                        <p class="mt-0.5 text-[11px] font-semibold text-[#0e5266]">{{ $suggestion['expiry_note'] }}</p>
                                    @endif
                                @else
                                    <p class="text-sm text-gray-400">Awaiting quote</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
            </div>

            {{-- Price matrix: items × suppliers for this prep list --}}
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-gray-50 px-6 py-4 text-center">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">Price matrix — suppliers side by side</h2>
                </div>
                @if (empty($sections))
                    <div class="px-6 py-10 text-center text-sm text-gray-500">No quotations yet for this prep list.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] text-xs">
                            <thead>
                                <tr class="border-b border-gray-200 bg-gray-50 text-[10px] font-bold uppercase tracking-wider text-gray-500">
                                    <th scope="col" class="sticky left-0 z-10 whitespace-nowrap border-r border-gray-200 bg-gray-50 px-4 py-3 text-left">Item</th>
                                    @foreach ($sections as $section)
                                        <th scope="col" class="whitespace-nowrap px-3 py-3 text-center">{{ $section['supplier'] }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($items as $preplistItem)
                                    @php
                                        $matrixItemName = $preplistItem->item?->name ?? 'Item';
                                        $matrixUnit = $preplistItem->item?->unit ?? '';
                                        $matrixHighlight = $highlights[$preplistItem->id] ?? ['best_price' => null, 'best_expiry' => null];
                                        $matrixBest = $matrixHighlight['best_price'];
                                    @endphp
                                    <tr class="transition-colors hover:bg-gray-50">
                                        <td class="sticky left-0 z-10 border-r border-gray-200 bg-white px-4 py-2.5">
                                            <p class="font-semibold text-gray-900">{{ $matrixItemName }}</p>
                                            <p class="text-[10px] text-gray-500">{{ $preplistItem->remainingQuantity() }} {{ $matrixUnit }}</p>
                                        </td>
                                        @foreach ($sections as $section)
                                            @php
                                                $cellLine = $section['lines'][$preplistItem->id] ?? null;
                                                $cellPrice = $cellLine !== null && $cellLine['unit_price'] !== null ? (float) $cellLine['unit_price'] : null;
                                                $cellIsBest = $cellPrice !== null && $matrixBest !== null && abs($cellPrice - $matrixBest) < 0.0001;
                                            @endphp
                                            <td class="px-3 py-2.5 text-center">
                                                @if ($cellPrice !== null)
                                                    @if ($cellIsBest)
                                                        <span class="inline-flex items-center gap-1 rounded-md bg-green-50 px-2 py-1 font-semibold text-green-700">
                                                            {{ $peso($cellPrice) }}
                                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m5 13 4 4L19 7"/>
                                                            </svg>
                                                        </span>
                                                    @else
                                                        <span class="text-gray-700">{{ $peso($cellPrice) }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 border-t border-gray-100 px-4 py-2.5 text-[10px] text-gray-500">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded bg-green-100 ring-1 ring-green-300"></span> Lowest price per item</span>
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded bg-gray-100 ring-1 ring-gray-200"></span> No quote</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Print-only sheet: what window.print() sends to the printer (officio landscape). --}}
    @php
        $winnerByItem = collect($suggestions ?? [])->keyBy(fn ($s) => $s['preplist_item']->id);
    @endphp
    <div id="preplistPrintSheet" class="print-sheet" aria-hidden="true">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16pt; border-bottom:2.5pt solid #0e5266; padding-bottom:6pt; margin-bottom:8pt;">
            <div>
                <h1 style="margin:0; font-size:15pt; font-weight:700; color:#0e5266;">Quotation Comparison</h1>
                <p style="margin:2pt 0 0; font-size:9pt; color:#333;">{{ $preplist->name }} · {{ $preplist->reference_no }}</p>
                <p style="margin:1pt 0 0; font-size:8pt; color:#666;">Generated {{ now()->format('F j, Y · g:i A') }} by Purchasing</p>
            </div>
            <div style="text-align:right; font-size:8pt; color:#333;">
                @foreach ($sections as $section)
                    <p style="margin:0 0 1.5pt;"><span style="font-weight:700;">{{ $section['supplier'] }}</span> — {{ $section['rfq_number'] }} ({{ $section['status'] }})</p>
                @endforeach
            </div>
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:8pt;">
            <thead>
                <tr>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:left; font-size:7.5pt; text-transform:uppercase; letter-spacing:0.05em;">Item</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:right; font-size:7.5pt; text-transform:uppercase;">Qty</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:left; font-size:7.5pt; text-transform:uppercase;">Unit</th>
                    @foreach ($sections as $section)
                        <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:left; font-size:7.5pt; text-transform:uppercase;">{{ $section['supplier'] }}</th>
                    @endforeach
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:left; font-size:7.5pt; text-transform:uppercase;">Suggested</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $preplistItem)
                    @php
                        $printName = $preplistItem->item?->name ?? 'Item';
                        $printUnit = $preplistItem->item?->unit ?? '';
                        $printHighlight = $highlights[$preplistItem->id] ?? ['best_price' => null, 'best_expiry' => null];
                        $printBest = $printHighlight['best_price'];
                        $printBestExpiry = $printHighlight['best_expiry'];
                        $printWinner = $winnerByItem->get($preplistItem->id);
                    @endphp
                    <tr>
                        <td style="border:1px solid #000; padding:4pt; font-weight:600;">{{ $printName }}</td>
                        <td style="border:1px solid #000; padding:4pt; text-align:right;">{{ $preplistItem->remainingQuantity() }}</td>
                        <td style="border:1px solid #000; padding:4pt;">{{ $printUnit }}</td>
                        @foreach ($sections as $section)
                            @php
                                $printLine = $section['lines'][$preplistItem->id] ?? null;
                                $printPrice = $printLine !== null && $printLine['unit_price'] !== null ? (float) $printLine['unit_price'] : null;
                                $printIsBest = $printPrice !== null && $printBest !== null && abs($printPrice - $printBest) < 0.0001;
                                $printIsExpiry = $printLine !== null && $printLine['expiry'] !== null && $printBestExpiry !== null && $printLine['expiry'] === $printBestExpiry;
                            @endphp
                            <td style="border:1px solid #000; padding:4pt; {{ $printIsBest ? 'font-weight:700;' : '' }}">
                                @if ($printPrice !== null)
                                    {{ $peso($printPrice) }}{{ $printIsBest ? ' ✔' : '' }}<br>
                                    <span style="font-size:7pt; color:#555; {{ $printIsExpiry ? 'font-weight:700; color:#0e5266;' : '' }}">{{ ($printLine['brand'] ?: '—') }} · Exp. {{ $printLine['expiry_label'] ?? 'n/a' }}{{ $printIsExpiry ? ' ✔' : '' }}</span>
                                @else
                                    <span style="color:#999;">No quote</span>
                                @endif
                            </td>
                        @endforeach
                        <td style="border:1px solid #000; padding:4pt;">
                            @if ($printWinner && $printWinner['winner'])
                                <span style="font-weight:600;">{{ $printWinner['winner']['supplier'] }}</span><br>
                                <span style="font-size:7pt; color:#555;">{{ $peso($printWinner['winner']['quote']['unit_price']) }}</span>
                            @else
                                <span style="color:#999;">Awaiting quote</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="margin:6pt 0 0; font-size:7pt; color:#666;">✔ = best price (green on screen) or best expiry (teal on screen). Brand and expiry are exactly as the suppliers stated them — verify both before awarding.</p>
    </div>

    {{-- Remove-from-cart confirmation modal --}}
    <div id="cartItemModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="cartItemModalTitle">
        <div id="cartItemModalOverlay" class="absolute inset-0 bg-gray-900/50" data-close-cart-item></div>

        <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
            <div class="flex items-start gap-3 px-5 py-4">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-teal-50">
                    <svg class="h-5 w-5 text-[#2a7a94]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 10a2 2 0 100 4m8-4a2 2 0 100 4M8 13h8"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 id="cartItemModalTitle" class="text-base font-bold text-gray-900">Item is in your cart</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        <span id="cartItemModalContext" class="font-medium text-gray-700"></span> is in your cart. Remove it, or keep it?
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                <button type="button" data-close-cart-item
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Keep it
                </button>
                <form id="cartItemRemoveForm" method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#236b80]">
                        Remove
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Switch-supplier confirmation modal --}}
    <div id="swapSupplierModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="swapSupplierTitle">
        <div class="absolute inset-0 bg-gray-900/50" data-close-swap-modal></div>

        <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
            <div class="flex items-start gap-3 px-5 py-4">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-50">
                    <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 4L3 8l4 4M3 8h13m4 8l-4-4m4 4l-4 4M21 16H8"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 id="swapSupplierTitle" class="text-base font-bold text-gray-900">Switch supplier?</h3>
                    <p class="mt-1 text-sm text-gray-500"><span id="swapSupplierContext" class="font-medium text-gray-700"></span></p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                <button type="button" data-close-swap-modal
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Keep current
                </button>
                <form id="swapSupplierForm" method="POST" action="{{ route('po-cart.add') }}">
                    @csrf
                    <input type="hidden" name="quotation_item_id" id="swapSupplierItemId" value="">
                    <button type="submit" class="rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#236b80]">
                        Switch
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .print-sheet { display: none; }
        @media print {
            /* Officio (8.5 x 13 in), landscape */
            @page { size: 13in 8.5in; margin: 10mm; }
            body * { visibility: hidden; }
            #preplistPrintSheet, #preplistPrintSheet * { visibility: visible; }
            #preplistPrintSheet { display: block !important; position: absolute; inset: 0; background: #fff; }
            #preplistPrintSheet table { page-break-inside: auto; }
            #preplistPrintSheet tr { page-break-inside: avoid; }
            table thead { display: table-header-group; }
            html, body { overflow: visible !important; height: auto !important; background: #fff !important; }
        }
    </style>

    @push('scripts')
    <script>
        document.getElementById('preplistPrintBtn')?.addEventListener('click', function () {
            window.print();
        });
    </script>
    <script>
        (function () {
            var modal = document.getElementById('cartItemModal');
            if (!modal) {
                return;
            }

            var overlay = document.getElementById('cartItemModalOverlay');
            var context = document.getElementById('cartItemModalContext');
            var closeButtons = modal.querySelectorAll('[data-close-cart-item]');
            var form = document.getElementById('cartItemRemoveForm');
            var triggers = document.querySelectorAll('[data-cart-remove-open]');

            function openModal(trigger) {
                var itemName = trigger.getAttribute('data-item-name') || 'Item';
                var supplier = trigger.getAttribute('data-supplier') || '';
                var cartItemId = trigger.getAttribute('data-cart-item-id');

                context.textContent = itemName + (supplier ? ' from ' + supplier : '');
                if (cartItemId) {
                    form.setAttribute('action', '{{ url('/cart/items') }}/' + cartItemId);
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            Array.prototype.forEach.call(triggers, function (trigger) {
                trigger.addEventListener('click', function () { openModal(trigger); });
            });
            Array.prototype.forEach.call(closeButtons, function (btn) {
                btn.addEventListener('click', closeModal);
            });
            if (overlay) {
                overlay.addEventListener('click', closeModal);
            }
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
    <script>
        (function () {
            var modal = document.getElementById('swapSupplierModal');
            if (!modal) {
                return;
            }

            var context = document.getElementById('swapSupplierContext');
            var itemId = document.getElementById('swapSupplierItemId');
            var closeButtons = modal.querySelectorAll('[data-close-swap-modal]');
            var triggers = document.querySelectorAll('[data-swap-open]');

            function openModal(trigger) {
                var itemName = trigger.getAttribute('data-item-name') || 'Item';
                var from = trigger.getAttribute('data-from-supplier') || 'the other supplier';
                var to = trigger.getAttribute('data-to-supplier') || 'this supplier';
                var price = trigger.getAttribute('data-price') || '';

                context.textContent = itemName + ' is in your cart from ' + from
                    + '. Switch to ' + to + (price ? ' at ' + price : '') + '?';
                if (itemId) {
                    itemId.value = trigger.getAttribute('data-quotation-item-id') || '';
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            Array.prototype.forEach.call(triggers, function (trigger) {
                trigger.addEventListener('click', function () { openModal(trigger); });
            });
            Array.prototype.forEach.call(closeButtons, function (btn) {
                btn.addEventListener('click', closeModal);
            });
            var overlay = modal.querySelector('.absolute');
            if (overlay) {
                overlay.addEventListener('click', closeModal);
            }
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
    @endpush

@endsection
