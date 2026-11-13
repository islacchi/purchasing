@extends('layouts.app')

@section('page-title', 'PRICE INDEX')
@section('title', 'Price Index - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        $peso = function ($amount) {
            return '₱' . number_format((float) $amount, 2);
        };
    @endphp
    <div class="pb-16">

        {{-- Page heading --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <p class="text-xs text-gray-500">
                Compare submitted supplier quotations and find the best price per item.
            </p>

            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    Live quotation data
                </span>
            </div>
        </div>

        {{-- KPI cards --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Items with quotes --}}
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Items with quotes</p>
                    <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-[#2a7a94]/10 text-[#2a7a94]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ $itemsWithQuotes }}</p>
                <p class="mt-1 text-xs text-gray-500">of {{ count($items) }} catalog items tracked</p>
            </div>

            {{-- Suppliers quoting --}}
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Suppliers quoting</p>
                    <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 0 0-3-3.87M9 20H4v-2a4 4 0 0 1 3-3.87m6-3.13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7-4a3 3 0 1 1-2.5-2.96"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ $activeSuppliers }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $totalQuoteLines }} current quotes across the catalogue</p>
            </div>

            {{-- Average price spread --}}
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Avg. price spread</p>
                    <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 17l6-6 4 4 8-8m0 0h-5m5 0v5"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-900">{{ number_format($avgSpread, 1) }}%</p>
                <p class="mt-1 text-xs text-gray-500">highest vs lowest across {{ $spreadCount }} comparable items</p>
            </div>

            {{-- Savings opportunity --}}
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Savings opportunity</p>
                    <span class="inline-flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v12m3-9.5c0-1.38-1.34-2.5-3-2.5s-3 1.12-3 2.5S10.34 11 12 11s3 1.12 3 2.5S13.66 16 12 16s-3-1.12-3-2.5"/>
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-2xl font-bold text-green-600">{{ $peso($totalSavings) }}</p>
                <p class="mt-1 text-xs text-gray-500">vs average quote price, at requested quantities</p>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                        </svg>
                    </span>
                    <input type="text" id="priceSearch" placeholder="Search item…"
                           class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm text-gray-700 placeholder-gray-400 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                </div>

                <select id="priceCategory"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                    <option value="">All categories</option>
                    @foreach (collect($items)->pluck('category')->unique()->sort() as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>

                <select id="priceProject"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                    <option value="">All projects</option>
                    @foreach ($projectOptions as $projectId => $projectLabel)
                        <option value="{{ $projectId }}">{{ $projectLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Best price board --}}

        <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm">

            {{-- Board header --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">Best price board</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Lowest normalised unit price per item, from submitted quotations.</p>
                </div>

                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5" role="tablist" aria-label="Board view">
                    <button type="button" id="viewTableBtn" role="tab" aria-selected="true"
                            class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-[#2a7a94] shadow-sm transition-colors">
                        Table
                    </button>
                    <button type="button" id="viewMatrixBtn" role="tab" aria-selected="false"
                            class="rounded-md px-3 py-1.5 text-xs font-semibold text-gray-500 transition-colors hover:text-gray-700">
                        Matrix
                    </button>
                </div>
            </div>

            {{-- TABLE VIEW --}}
            <div id="boardTable">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1040px] text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th scope="col" class="whitespace-nowrap px-6 py-3">Item</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Requested</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3 text-center">Quotes</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Best price</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3">Best supplier</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Average</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Variance</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3">Trend</th>
                                <th scope="col" class="w-14 px-6 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($items as $item)
                                @php
                                    $stats = $item['stats'];
                                    $hasQuotes = $stats['count'] > 0;
                                    $isComparable = $stats['count'] > 1;
                                    $bestSupplier = $stats['best_supplier'] ? $suppliers[$stats['best_supplier']] : null;
                                    $variance = $isComparable && $stats['avg'] > 0 ? ((1 - ($stats['best'] / $stats['avg'])) * 100) : null;
                                @endphp
                                <tr class="group price-row transition-colors {{ $hasQuotes ? 'cursor-pointer hover:bg-gray-50' : 'bg-gray-50/40' }}"
                                    data-item-id="{{ $item['id'] }}"
                                    data-name="{{ strtolower($item['name'] . ' ' . $item['unit']) }}"
                                    data-category="{{ $item['category'] }}"
                                    data-project-ids="{{ implode(',', $item['project_ids']) }}"
                                    data-has-quotes="{{ $hasQuotes ? '1' : '0' }}">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-gray-900 break-words">{{ $item['name'] }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="text-xs text-gray-500">{{ $item['unit'] }}</span>
                                            <span class="text-gray-300">·</span>
                                            <span class="text-xs text-gray-500">{{ $item['category'] }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-gray-700">
                                        {{ $item['requested'] > 0 ? $item['requested'] : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        @if ($hasQuotes)
                                            <span class="inline-flex items-center rounded-full {{ $isComparable ? 'bg-blue-50 text-blue-700' : 'bg-gray-100 text-gray-600' }} px-2.5 py-1 text-xs font-medium">
                                                {{ $stats['count'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500">
                                                0
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        @if ($hasQuotes)
                                            <span class="font-bold {{ $isComparable ? 'text-green-600' : 'text-gray-700' }}">{{ $peso($stats['best']) }}</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if ($bestSupplier)
                                            <span class="inline-flex items-center gap-2 text-gray-700">
                                                <span class="h-2 w-2 rounded-full" style="background-color: {{ $bestSupplier['color'] }}"></span>
                                                {{ $bestSupplier['short'] }}
                                            </span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-gray-500">
                                        {{ $hasQuotes ? $peso($stats['avg']) : '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right">
                                        @if ($variance !== null)
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-600">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 5v14m0 0 5-5m-5 5-5-5"/>
                                                </svg>
                                                {{ number_format($variance, 1) }}%
                                            </span>
                                        @elseif ($hasQuotes)
                                            <span class="text-xs text-gray-400">1 quote</span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if ($hasQuotes && $item['sparkline'])
                                            <svg class="h-5 w-16" viewBox="0 0 64 20" fill="none" preserveAspectRatio="none" aria-hidden="true">
                                                <polyline points="{{ $item['sparkline'] }}" stroke="{{ $isComparable ? '#2a7a94' : '#9ca3af' }}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        @else
                                            <span class="text-sm text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if ($hasQuotes)
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors group-hover:bg-[#2a7a94]/10 group-hover:text-[#2a7a94]" title="Compare quotes">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/>
                                                </svg>
                                            </span>
                                        @else
                                            <span class="text-gray-300">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-600">No items with quotation activity yet.</p>
                                        <p class="mt-1 text-xs text-gray-500">Items appear here once a project requests them or a supplier quotes them.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Empty filter state --}}
                <div id="tableEmptyState" class="hidden px-6 py-12 text-center">
                    <p class="text-sm font-medium text-gray-600">No items match the current filters.</p>
                    <p class="mt-1 text-xs text-gray-500">Try clearing the search or choosing another category.</p>
                </div>
            </div>

            {{-- MATRIX VIEW --}}
            <div id="boardMatrix" class="hidden">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th scope="col" class="sticky left-0 z-10 whitespace-nowrap border-r border-gray-200 bg-gray-50 px-6 py-3 text-left">Item</th>
                                @foreach ($suppliers as $supplier)
                                    <th scope="col" class="whitespace-nowrap px-5 py-3 text-center">
                                        <span class="inline-flex max-w-[12rem] items-center gap-2" title="{{ $supplier['name'] }}">
                                            <span class="h-2 w-2 flex-shrink-0 rounded-full" style="background-color: {{ $supplier['color'] }}"></span>
                                            <span class="truncate">{{ $supplier['short'] }}</span>
                                        </span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($items as $item)
                                <tr class="matrix-row cursor-pointer transition-colors hover:bg-gray-50"
                                    data-item-id="{{ $item['id'] }}"
                                    data-name="{{ strtolower($item['name'] . ' ' . $item['unit']) }}"
                                    data-category="{{ $item['category'] }}"
                                    data-project-ids="{{ implode(',', $item['project_ids']) }}">
                                    <td class="sticky left-0 z-10 border-r border-gray-200 bg-white px-6 py-4 break-words">
                                        <p class="font-semibold text-gray-900 break-words">{{ $item['name'] }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ $item['unit'] }}</p>
                                    </td>
                                    @foreach ($suppliers as $key => $supplier)
                                        @php
                                            $quote = collect($item['quotes'])->firstWhere('supplier', $key);
                                            $isBest = $quote && $item['stats']['best'] !== null && abs($quote['price'] - $item['stats']['best']) < 0.0001 && $item['stats']['count'] > 1;
                                            $isAbove = $quote && $item['stats']['avg'] !== null && $quote['price'] > $item['stats']['avg'];
                                        @endphp
                                        <td class="whitespace-nowrap px-5 py-4 text-center">
                                            @if ($quote)
                                                <span class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm font-medium
                                                    {{ $isBest ? 'bg-green-50 text-green-700' : ($isAbove ? 'bg-amber-50 text-amber-700' : 'bg-white text-gray-700') }}">
                                                    {{ $peso($quote['price']) }}
                                                    @if ($isBest)
                                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m5 13 4 4L19 7"/>
                                                        </svg>
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($suppliers) + 1 }}" class="px-6 py-12 text-center">
                                        <p class="text-sm font-medium text-gray-600">No items with quotation activity yet.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center gap-4 border-t border-gray-100 px-6 py-3 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-green-100 ring-1 ring-green-300"></span> Lowest price</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-amber-100 ring-1 ring-amber-300"></span> Above average</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-gray-100 ring-1 ring-gray-300"></span> No quote</span>
                </div>

                <div id="matrixEmptyState" class="hidden px-6 py-12 text-center">
                    <p class="text-sm font-medium text-gray-600">No items match the current filters.</p>
                </div>
            </div>
        </div>

        {{-- Item comparison spotlight + supplier competitiveness --}}
        <div class="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3">

            {{-- Comparison spotlight --}}
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">Item comparison</h2>
                        <p id="compareSubtitle" class="mt-0.5 text-xs text-gray-500">Select an item from the board to compare its quotes.</p>
                    </div>
                    <span id="compareBadge" class="hidden inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                        Lowest price
                    </span>
                </div>

                <div id="compareBody" class="px-6 py-5">
                    @if (count($items) === 0)
                        <div class="py-10 text-center">
                            <p class="text-sm font-medium text-gray-600">No items to compare yet.</p>
                            <p class="mt-1 text-xs text-gray-500">Create a project and submit supplier quotations first.</p>
                        </div>
                    @else
                        <div class="py-10 text-center">
                            <p class="text-sm font-medium text-gray-600">Select an item from the board.</p>
                            <p class="mt-1 text-xs text-gray-500">Choose a row with quotes to compare supplier prices.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Supplier competitiveness --}}
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">Supplier competitiveness</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Lowest-price wins on items with 2+ quotes.</p>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($suppliers as $key => $supplier)
                        @php
                            $row = $scorecard[$key];
                            $winRate = $row['compared'] ? ($row['wins'] / $row['compared']) * 100 : 0;
                            $premium = $row['compared'] ? $row['premium_sum'] / $row['compared'] : null;
                        @endphp
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex min-w-0 items-center gap-2">
                                    <span class="h-2 w-2 flex-shrink-0 rounded-full" style="background-color: {{ $supplier['color'] }}"></span>
                                    <span class="truncate text-sm font-medium text-gray-800" title="{{ $supplier['name'] }}">{{ $supplier['short'] }}</span>
                                </span>
                                <span class="text-xs font-semibold text-gray-700">
                                    {{ $row['wins'] }} / {{ $row['compared'] }} wins
                                </span>
                            </div>

                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full" style="width: {{ $winRate }}%; background-color: {{ $supplier['color'] }}"></div>
                            </div>

                            <div class="mt-2 flex items-center justify-between gap-3 text-xs text-gray-500">
                                <span>{{ $row['quotes'] }} current quotes</span>
                                @if ($premium === null)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-500">Not compared</span>
                                @elseif ($premium <= 1)
                                    <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 font-medium text-green-700">+{{ number_format($premium, 1) }}% vs best</span>
                                @elseif ($premium <= 8)
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 font-medium text-blue-700">+{{ number_format($premium, 1) }}% vs best</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 font-medium text-amber-700">+{{ number_format($premium, 1) }}% vs best</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center">
                            <p class="text-sm font-medium text-gray-600">No suppliers have quoted yet.</p>
                            <p class="mt-1 text-xs text-gray-500">The scorecard appears once suppliers submit prices.</p>
                        </div>
                    @endforelse
                </div>

                <div class="space-y-1 border-t border-gray-100 px-6 py-3">
                    <p class="text-xs text-gray-500">Single-quote items are excluded so they cannot inflate a win rate.</p>
                    <p class="text-xs text-gray-500">Ties count as a win for every tied supplier.</p>
                </div>
            </div>
        </div>

        {{-- Price trend --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">Price trend</h2>
                    <p id="trendSubtitle" class="mt-0.5 text-xs text-gray-500">Movement of the selected item's quoted price.</p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <div id="trendLegend" class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-gray-600"></div>

                    <label for="pricePeriod" class="sr-only">Price trend period</label>
                    <select id="pricePeriod"
                            aria-label="Price trend period"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                        <option value="3">Last 3 months</option>
                        <option value="6" selected>Last 6 months</option>
                        <option value="12">Last 12 months</option>
                    </select>
                </div>
            </div>

            <div class="px-6 py-5">
                <div class="h-72">
                    <canvas id="priceTrendChart"></canvas>
                </div>

                <div id="trendEmpty" class="hidden py-16 text-center">
                    <p class="text-sm font-medium text-gray-600">No price history for this item yet.</p>
                    <p class="mt-1 text-xs text-gray-500">The trend appears once suppliers start submitting quotes.</p>
                </div>

                <p class="mt-4 text-xs text-gray-500">
                    One point per supplier per month, from submitted quotation lines. The chart spans from the first quoted month within the selected period.
                </p>
            </div>
        </div>

        {{-- Savings opportunities --}}
        @php
            $savingsItems = array_values(array_filter($items, fn ($item) => $item['stats']['count'] > 1 && $item['stats']['savings'] > 0));
            usort($savingsItems, fn ($a, $b) => $b['stats']['savings'] <=> $a['stats']['savings']);
        @endphp

        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-gray-200 px-6 py-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">Savings opportunities</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Choosing the lowest quote instead of the average, at requested quantities.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                    {{ $peso($totalSavings) }} total
                </span>
            </div>

            @if (count($savingsItems))
                <div id="savingsTableWrapper" class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th scope="col" class="whitespace-nowrap px-6 py-3">Item</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Best price</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Average</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Qty</th>
                                <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Potential savings</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($savingsItems as $item)
                                @php
                                    $stats = $item['stats'];
                                    $bestSupplier = $stats['best_supplier'] !== null
                                        ? ($suppliers[$stats['best_supplier']] ?? null)
                                        : null;
                                @endphp
                                <tr class="savings-row transition-colors hover:bg-gray-50"
                                    data-name="{{ strtolower($item['name'] . ' ' . $item['unit']) }}"
                                    data-category="{{ $item['category'] }}"
                                    data-project-ids="{{ implode(',', $item['project_ids']) }}">
                                    <td class="px-6 py-4">
                                        <p class="font-semibold text-gray-900 break-words">{{ $item['name'] }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            <span class="text-xs text-gray-500">{{ $item['unit'] }}</span>
                                            @if ($bestSupplier)
                                                <span class="text-gray-300">·</span>
                                                <span class="inline-flex items-center gap-1.5 text-xs text-gray-500">
                                                    <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $bestSupplier['color'] }}"></span>
                                                    {{ $bestSupplier['short'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-green-600">{{ $peso($stats['best']) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-gray-500">{{ $peso($stats['avg']) }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-gray-700">{{ $item['requested'] }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right font-bold text-gray-900">{{ $peso($stats['savings']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-gray-200 bg-gray-50">
                                <td colspan="4" class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Total potential savings</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-base font-bold text-green-700">{{ $peso($totalSavings) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div id="savingsEmptyState" class="hidden px-6 py-12 text-center">
                    <p class="text-sm font-medium text-gray-600">No savings match the current filters.</p>
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <p class="text-sm font-medium text-gray-600">No comparable savings yet.</p>
                    <p class="mt-1 text-xs text-gray-500">Items need at least two quotes before a saving can be estimated.</p>
                </div>
            @endif

            <div class="border-t border-gray-100 px-6 py-3">
                <p class="text-xs text-gray-500">Estimates only. Freight, taxes, and lead time are not included.</p>
            </div>
        </div>

    </div>

    {{-- Chart.js is loaded inline because the layout has no @stack('scripts'). --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

    <style>
        /* Keep wide boards scrolling inside their card instead of widening the shell. */
        #mainContent { min-width: 0; }
        .price-row.selected-row { background-color: #eef7fa; }
    </style>

    <script>
        (function () {
            const priceData = @json($priceData);
            const quotationBase = @json(url('/quotation'));
            const periodSelect = document.getElementById('pricePeriod');

            const peso = function (value) {
                return '₱' + Number(value).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            };

            const escapeHtml = function (value) {
                return String(value).replace(/[&<>"']/g, function (character) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;',
                    }[character];
                });
            };

            const monthWindow = function (months) {
                const window = [];
                const now = new Date();

                for (let offset = months - 1; offset >= 0; offset--) {
                    const date = new Date(now.getFullYear(), now.getMonth() - offset, 1);
                    const key = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
                    const label = date.toLocaleString('en-US', { month: 'short' })
                        + ' ' + String(date.getFullYear()).slice(-2);

                    window.push({ key: key, label: label });
                }

                return window;
            };

            const compareBody = document.getElementById('compareBody');
            const compareSubtitle = document.getElementById('compareSubtitle');
            const compareBadge = document.getElementById('compareBadge');
            const trendSubtitle = document.getElementById('trendSubtitle');
            const trendLegend = document.getElementById('trendLegend');
            const trendEmpty = document.getElementById('trendEmpty');
            const trendCanvas = document.getElementById('priceTrendChart');
            const comparePlaceholder = compareBody.innerHTML;

            let chart = null;
            let selectedId = null;

            function findItem(id) {
                return priceData.find(function (item) { return item.id === id; });
            }

            function resetComparison() {
                selectedId = null;

                document.querySelectorAll('.price-row').forEach(function (row) {
                    row.classList.remove('selected-row');
                });

                compareSubtitle.textContent = 'Select an item from the board to compare its quotes.';
                compareBadge.classList.add('hidden');
                compareBody.innerHTML = comparePlaceholder;

                if (chart) {
                    chart.destroy();
                    chart = null;
                }
                trendLegend.innerHTML = '';
                trendCanvas.classList.remove('hidden');
                trendEmpty.classList.add('hidden');
                trendEmpty.querySelector('p').textContent = 'No price history for this item yet.';
                trendSubtitle.textContent = 'Movement of the selected item\'s quoted price.';
            }

            function expiryView(quote) {
                let className = 'text-gray-500';
                let note = '';

                if (quote.expiry) {
                    if (quote.expiry_state === 'expired') {
                        className = 'text-red-600 font-semibold';
                        note = ' · expired';
                    } else if (quote.expiry_state === 'soon') {
                        className = 'text-amber-600 font-medium';
                        note = ' · expires soon';
                    }
                }

                return {
                    text: quote.expiry ? escapeHtml(quote.expiry) + note : 'Not stated',
                    className: quote.expiry ? className : 'text-gray-400',
                };
            }

            function renderComparison(item) {
                selectedId = item.id;

                const requestedLabel = item.requested > 0 ? item.requested + ' requested' : 'no active request';
                const subtitleParts = [item.name, item.unit, requestedLabel];
                if (item.projects.length) {
                    subtitleParts.push(item.projects.join(', '));
                }
                compareSubtitle.textContent = subtitleParts.join(' · ');
                if (!item.count) {
                    compareBadge.classList.add('hidden');
                    compareBody.innerHTML =
                        '<div class="py-10 text-center">'
                        + '<p class="text-sm font-medium text-gray-600">No submitted quotes for this item yet.</p>'
                        + '<p class="mt-1 text-xs text-gray-500">It will become comparable once a supplier submits a price.</p>'
                        + '</div>';
                    return;
                }

                const sorted = item.quotes.slice().sort(function (a, b) { return a.price - b.price; });
                const bestQuote = sorted[0];
                const isComparable = item.count > 1;
                const delta = isComparable && item.avg > 0 ? ((1 - (item.best / item.avg)) * 100) : null;

                compareBadge.classList.toggle('hidden', !isComparable);

                let html = '';

                // Best price and best expiry, side by side: lowest price vs latest supplier-stated expiry.
                const bestExpiryQuote = item.quotes.reduce(function (best, quote) {
                    if (!quote.expiry_iso) {
                        return best;
                    }
                    return !best || quote.expiry_iso > best.expiry_iso ? quote : best;
                }, null);
                const bestExpiry = bestExpiryQuote ? expiryView(bestExpiryQuote) : null;
                let bestExpiryRemaining = '';

                if (bestExpiryQuote) {
                    const parts = bestExpiryQuote.expiry_iso.split('-');
                    const expiryDate = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    const days = Math.round((expiryDate - today) / 86400000);
                    const absoluteDays = Math.abs(days);
                    bestExpiryRemaining = days < 0
                        ? absoluteDays + ' day' + (absoluteDays === 1 ? '' : 's') + ' overdue'
                        : days + ' day' + (days === 1 ? '' : 's') + ' left';
                }

                const bestExpiryBrand = bestExpiryQuote && bestExpiryQuote.brand ? bestExpiryQuote.brand : null;

                html += '<div class="grid gap-4 md:grid-cols-2">';

                html += '<div class="flex flex-wrap items-start justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4">';
                html += '<div>';
                html += '<p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Best price</p>';
                html += '<p class="mt-1 text-2xl font-bold ' + (isComparable ? 'text-green-600' : 'text-gray-800') + '">'
                    + peso(item.best)
                    + ' <span class="text-xs font-normal text-gray-500">/ ' + escapeHtml(item.unit) + '</span></p>';
                html += '<p class="mt-1 text-xs text-gray-600">' + escapeHtml(bestQuote.short);
                if (delta !== null) {
                    html += ' <span class="font-semibold text-green-600">· ' + delta.toFixed(1) + '% below average</span>';
                }
                html += '</p>';
                html += '</div>';
                html += '<div class="text-right">';
                html += '<p class="text-xs text-gray-500">Extended cost · ' + requestedLabel + '</p>';
                html += '<p class="text-sm font-semibold text-gray-800">'
                    + (item.requested > 0 ? peso(item.best * item.requested) : '—') + '</p>';
                html += '<a href="' + quotationBase + '/' + bestQuote.quotation_id
                    + '" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[#2a7a94] hover:underline">'
                    + 'View quotation'
                    + '<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">'
                    + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/></svg>'
                    + '</a>';
                html += '</div>';
                html += '</div>';

                html += '<div class="flex flex-wrap items-start justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 p-4">';
                if (bestExpiryQuote) {
                    html += '<div>';
                    html += '<p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Best expiry</p>';
                    html += '<p class="mt-1 text-2xl font-bold ' + bestExpiry.className + '" title="Latest supplier-stated expiry">' + bestExpiry.text + '</p>';
                    html += '<p class="mt-1 text-xs text-gray-600">' + escapeHtml(bestExpiryQuote.short)
                        + ' · ' + bestExpiryRemaining + '</p>';
                    html += '</div>';
                    html += '<div class="text-right">';
                    html += '<p class="text-xs text-gray-500">Brand</p>';
                    html += '<p class="text-sm font-semibold ' + (bestExpiryBrand ? 'text-gray-800' : 'text-gray-400') + '">'
                        + (bestExpiryBrand ? escapeHtml(bestExpiryBrand) : 'Not stated') + '</p>';
                    html += '<a href="' + quotationBase + '/' + bestExpiryQuote.quotation_id
                        + '" class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-[#2a7a94] hover:underline">'
                        + 'View quotation'
                        + '<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">'
                        + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/></svg>'
                        + '</a>';
                    html += '</div>';
                } else {
                    html += '<div>';
                    html += '<p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Best expiry</p>';
                    html += '<p class="mt-1 text-2xl font-bold text-gray-400">—</p>';
                    html += '<p class="mt-1 text-xs text-gray-500">No submitted quote states an expiry.</p>';
                    html += '</div>';
                }
                html += '</div>';

                html += '</div>';

                if (!isComparable) {
                    html += '<div class="mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">'
                        + '<svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">'
                        + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/></svg>'
                        + '<p class="text-xs text-amber-700"><span class="font-semibold">Only one quote — no comparison available.</span> This price is shown for reference, not as a verified saving.</p>'
                        + '</div>';
                }

                // Supplier bars
                if (isComparable) {
                    const maxPrice = Math.max.apply(null, item.quotes.map(function (q) { return q.price; })) || 1;

                    html += '<div class="mt-5 space-y-3">';
                    sorted.forEach(function (quote) {
                        const isBest = Math.abs(quote.price - item.best) < 0.0001;
                        const width = Math.max(8, (quote.price / maxPrice) * 100);

                        html += '<div class="flex items-center gap-3">';
                        html += '<span class="w-24 flex-shrink-0 truncate text-xs font-medium text-gray-700" title="' + escapeHtml(quote.name) + '">' + escapeHtml(quote.short) + '</span>';
                        html += '<div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100">'
                            + '<div class="h-full rounded-full" style="width:' + width + '%; background-color:'
                            + (isBest ? '#16a34a' : quote.color) + '"></div></div>';
                        html += '<span class="w-24 flex-shrink-0 text-right text-xs font-semibold '
                            + (isBest ? 'text-green-600' : 'text-gray-700') + '">' + peso(quote.price) + '</span>';
                        html += '</div>';
                    });
                    html += '</div>';
                }

                // Comparison table
                html += '<div class="mt-5 overflow-x-auto rounded-lg border border-gray-200">';
                html += '<table class="w-full min-w-[900px] text-sm">';
                html += '<thead><tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">'
                    + '<th class="px-4 py-3">Supplier</th>'
                    + '<th class="px-4 py-3">Brand / expiry</th>'
                    + '<th class="px-4 py-3 text-right">Unit price</th>'
                    + '<th class="px-4 py-3 text-right">Extended</th>'
                    + '<th class="px-4 py-3 text-right">vs Best</th>'
                    + '<th class="px-4 py-3 text-center">Quoted</th>'
                    + '<th class="px-4 py-3 text-center">Source</th>'
                    + '</tr></thead><tbody class="divide-y divide-gray-100">';

                sorted.forEach(function (quote) {
                    const isBest = isComparable && Math.abs(quote.price - item.best) < 0.0001;
                    const premium = item.best > 0 ? ((quote.price - item.best) / item.best) * 100 : 0;
                    const brand = quote.brand ? escapeHtml(quote.brand) : '—';
                    const brandClass = quote.brand ? 'text-gray-800' : 'text-gray-400';
                    const expiry = expiryView(quote);

                    html += '<tr class="hover:bg-gray-50">';
                    html += '<td class="px-4 py-3"><span class="inline-flex items-center gap-2 text-gray-800">'
                        + '<span class="h-2 w-2 rounded-full" style="background-color:' + quote.color + '"></span>'
                        + escapeHtml(quote.name)
                        + (isBest ? ' <span class="rounded-full bg-green-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-green-700">Lowest</span>' : '')
                        + '</span></td>';
                    html += '<td class="px-4 py-3">'
                        + '<p class="text-xs font-medium ' + brandClass + '">' + brand + '</p>'
                        + '<p class="mt-0.5 text-xs ' + expiry.className + '" title="Supplier-stated expiry">' + expiry.text + '</p>'
                        + '</td>';
                    html += '<td class="px-4 py-3 text-right font-semibold ' + (isBest ? 'text-green-600' : 'text-gray-800') + '">' + peso(quote.price) + '</td>';
                    html += '<td class="px-4 py-3 text-right text-gray-600">' + (item.requested > 0 ? peso(quote.price * item.requested) : '—') + '</td>';
                    html += '<td class="px-4 py-3 text-right text-xs ' + (isBest ? 'text-green-600 font-semibold' : 'text-gray-500') + '">'
                        + (!isComparable ? '—' : (isBest ? '—' : '+' + premium.toFixed(1) + '%')) + '</td>';
                    html += '<td class="px-4 py-3 text-center text-xs text-gray-600">' + escapeHtml(quote.quoted_at) + '</td>';
                    html += '<td class="px-4 py-3 text-center">'
                        + '<a href="' + quotationBase + '/' + quote.quotation_id + '" class="inline-flex items-center gap-1 text-xs font-semibold text-[#2a7a94] hover:underline">View'
                        + '<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M9 5l7 7-7 7"/></svg>'
                        + '</a></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';

                if (isComparable) {
                    html += '<p class="mt-3 text-xs text-gray-500">Best price is the lowest normalised unit price.</p>';
                }

                html += '<p class="mt-2 text-xs text-gray-500">Best expiry is the latest supplier-stated expiry.</p>';
                html += '<p class="mt-2 text-xs text-gray-500">Brand and expiry are supplier-stated. '
                    + 'Check expiry against the project delivery date and required shelf life.</p>';

                compareBody.innerHTML = html;
            }

            function renderChart(item) {
                if (!item || !item.count) {
                    if (chart) {
                        chart.destroy();
                        chart = null;
                    }
                    trendLegend.innerHTML = '';
                    trendCanvas.classList.add('hidden');
                    trendEmpty.classList.remove('hidden');
                    trendEmpty.querySelector('p').textContent = 'No price history for this item yet.';
                    trendSubtitle.textContent = 'No quote history for the selected item.';
                    return;
                }

                const periodMonths = monthWindow(Number(periodSelect.value) || 6);
                const monthIndex = {};

                periodMonths.forEach(function (month, index) {
                    monthIndex[month.key] = index;
                });

                const dataMonthIndices = [];

                item.history.forEach(function (point) {
                    if (monthIndex[point.month] !== undefined) {
                        dataMonthIndices.push(monthIndex[point.month]);
                    }
                });

                if (!dataMonthIndices.length) {
                    if (chart) {
                        chart.destroy();
                        chart = null;
                    }
                    trendLegend.innerHTML = '';
                    trendCanvas.classList.add('hidden');
                    trendEmpty.classList.remove('hidden');
                    trendEmpty.querySelector('p').textContent = 'No price history in this period.';
                    trendSubtitle.textContent = item.name + ' — no quotes in the selected period.';
                    return;
                }

                // Span only the referenced data: from the first month with a quote
                // through the latest one, so leading/trailing empty months collapse.
                const firstMonth = Math.min.apply(null, dataMonthIndices);
                const lastMonth = Math.max.apply(null, dataMonthIndices);
                const months = periodMonths.slice(firstMonth, lastMonth + 1);
                const visibleMonthIndex = {};

                months.forEach(function (month, index) {
                    visibleMonthIndex[month.key] = index;
                });

                const quotedSuppliers = {};

                item.history.forEach(function (point) {
                    if (visibleMonthIndex[point.month] !== undefined) {
                        quotedSuppliers[point.supplier] = true;
                    }
                });

                const supplierIds = Object.keys(quotedSuppliers);

                trendCanvas.classList.remove('hidden');
                trendEmpty.classList.add('hidden');
                trendEmpty.querySelector('p').textContent = 'No price history for this item yet.';
                trendSubtitle.textContent = item.name + ' — quoted unit price by month.';

                const quoteBySupplier = {};

                item.quotes.forEach(function (quote) {
                    quoteBySupplier[quote.supplier] = quote;
                });

                const datasets = supplierIds.map(function (supplierId) {
                    const quote = quoteBySupplier[supplierId] || { short: 'Supplier', color: '#9ca3af' };

                    return {
                        label: quote.short,
                        data: months.map(function (month) {
                            const point = item.history.find(function (entry) {
                                return Number(entry.supplier) === Number(supplierId) && entry.month === month.key;
                            });

                            return point ? point.price : null;
                        }),
                        borderColor: quote.color,
                        backgroundColor: quote.color,
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 2.5,
                        pointHoverRadius: 4,
                        spanGaps: true,
                        fill: false,
                    };
                });

                trendLegend.innerHTML = datasets.map(function (dataset) {
                    return '<span class="inline-flex items-center gap-1.5">'
                        + '<span class="h-2 w-2 rounded-full" style="background-color:' + dataset.borderColor + '"></span>'
                        + escapeHtml(dataset.label) + '</span>';
                }).join('');

                if (typeof Chart === 'undefined') {
                    trendCanvas.classList.add('hidden');
                    trendEmpty.classList.remove('hidden');
                    trendEmpty.querySelector('p').textContent = 'Chart library unavailable.';
                    return;
                }

                const labels = months.map(function (month) { return month.label; });

                if (chart) {
                    chart.data.labels = labels;
                    chart.data.datasets = datasets;
                    chart.update();
                    return;
                }

                chart = new Chart(trendCanvas.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: datasets,
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return context.dataset.label + ': ' + peso(context.parsed.y);
                                    },
                                },
                            },
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                grace: '8%',
                                border: { display: false },
                                grid: { color: '#f3f4f6' },
                                ticks: {
                                    color: '#6b7280',
                                    font: { size: 11 },
                                    maxTicksLimit: 6,
                                    callback: function (value) {
                                        return '₱' + Number(value).toLocaleString('en-US', { maximumFractionDigits: 0 });
                                    },
                                },
                            },
                            x: {
                                border: { display: false },
                                grid: { display: false },
                                ticks: { color: '#6b7280', font: { size: 11 } },
                            },
                        },
                    },
                });
            }

            function selectItem(id, shouldScroll) {
                const item = findItem(id);
                if (!item) {
                    return;
                }

                renderComparison(item);
                renderChart(item);

                document.querySelectorAll('.price-row').forEach(function (row) {
                    row.classList.toggle('selected-row', Number(row.dataset.itemId) === id);
                });

                if (shouldScroll) {
                    // Only nudge the page when the comparison panel is actually
                    // off-screen — minimal scrolling, the top bar stays put.
                    compareBody.closest('.rounded-lg').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }

            // --- Table / matrix toggle ---
            const tableBtn = document.getElementById('viewTableBtn');
            const matrixBtn = document.getElementById('viewMatrixBtn');
            const boardTable = document.getElementById('boardTable');
            const boardMatrix = document.getElementById('boardMatrix');

            function setView(view) {
                const isTable = view === 'table';

                boardTable.classList.toggle('hidden', !isTable);
                boardMatrix.classList.toggle('hidden', isTable);

                tableBtn.setAttribute('aria-selected', String(isTable));
                matrixBtn.setAttribute('aria-selected', String(!isTable));

                tableBtn.className = 'rounded-md px-3 py-1.5 text-xs font-semibold transition-colors '
                    + (isTable ? 'bg-white text-[#2a7a94] shadow-sm' : 'text-gray-500 hover:text-gray-700');
                matrixBtn.className = 'rounded-md px-3 py-1.5 text-xs font-semibold transition-colors '
                    + (!isTable ? 'bg-white text-[#2a7a94] shadow-sm' : 'text-gray-500 hover:text-gray-700');
            }

            tableBtn.addEventListener('click', function () { setView('table'); });
            matrixBtn.addEventListener('click', function () { setView('matrix'); });

            periodSelect.addEventListener('change', function () {
                if (selectedId !== null) {
                    renderChart(findItem(selectedId));
                }
            });

            // --- Filters ---
            const searchInput = document.getElementById('priceSearch');
            const categoryInput = document.getElementById('priceCategory');
            const projectInput = document.getElementById('priceProject');

            function matchesFilters(dataset) {
                const term = searchInput.value.trim().toLowerCase();
                const category = categoryInput.value;
                const project = projectInput.value;

                if (term && dataset.name.indexOf(term) === -1) {
                    return false;
                }
                if (category && dataset.category !== category) {
                    return false;
                }
                if (project && dataset.projectIds.split(',').indexOf(project) === -1) {
                    return false;
                }

                return true;
            }

            function applyFilters() {
                let visibleRows = 0;
                document.querySelectorAll('.price-row').forEach(function (row) {
                    const match = matchesFilters(row.dataset);
                    row.classList.toggle('hidden', !match);
                    if (match) {
                        visibleRows++;
                    }
                });
                document.getElementById('tableEmptyState').classList.toggle('hidden', visibleRows > 0);

                let visibleMatrix = 0;
                document.querySelectorAll('.matrix-row').forEach(function (row) {
                    const match = matchesFilters(row.dataset);
                    row.classList.toggle('hidden', !match);
                    if (match) {
                        visibleMatrix++;
                    }
                });
                document.getElementById('matrixEmptyState').classList.toggle('hidden', visibleMatrix > 0);

                let visibleSavings = 0;
                document.querySelectorAll('.savings-row').forEach(function (row) {
                    const match = matchesFilters(row.dataset);
                    row.classList.toggle('hidden', !match);
                    if (match) {
                        visibleSavings++;
                    }
                });

                const savingsWrapper = document.getElementById('savingsTableWrapper');
                const savingsEmpty = document.getElementById('savingsEmptyState');

                if (savingsWrapper && savingsEmpty) {
                    savingsWrapper.classList.toggle('hidden', visibleSavings === 0);
                    savingsEmpty.classList.toggle('hidden', visibleSavings > 0);
                }

                if (selectedId !== null) {
                    const selectedRow = document.querySelector('.price-row[data-item-id="' + selectedId + '"]');

                    if (!selectedRow || selectedRow.classList.contains('hidden')) {
                        resetComparison();
                    }
                }
            }

            searchInput.addEventListener('input', applyFilters);
            categoryInput.addEventListener('change', applyFilters);
            projectInput.addEventListener('change', applyFilters);

            // --- Row selection ---
            document.querySelectorAll('.price-row').forEach(function (row) {
                row.addEventListener('click', function () {
                    if (row.dataset.hasQuotes !== '1') {
                        return;
                    }
                    selectItem(Number(row.dataset.itemId), true);
                });
            });

            document.querySelectorAll('.matrix-row[data-item-id]').forEach(function (row) {
                row.addEventListener('click', function () {
                    selectItem(Number(row.dataset.itemId), true);
                });
            });

            // --- Initial state ---
            const initialItem = priceData.find(function (item) { return item.count > 0; }) || priceData[0];
            if (initialItem) {
                selectItem(initialItem.id, false);
            }
        })();
    </script>
@endsection
