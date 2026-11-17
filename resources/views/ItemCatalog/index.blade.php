@extends('layouts.app')

@section('page-title', 'ITEM CATALOG')
@section('title', 'Item Catalog - Purchasing')

@section('header-actions')
@endsection

@section('content')
    @php
        $peso = fn ($amount) => '₱ ' . number_format((float) $amount, 2);
        $filtersActive = collect([$categoryAllowed, $supplier !== '' ? $supplier : null, $from !== '' ? $from : null, $to !== '' ? $to : null])->filter()->count();
    @endphp

    {{-- Search and filter bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4 flex-1 min-w-0">
            <form method="GET" action="{{ route('item-catalog') }}" class="flex max-w-xl w-full items-center gap-2">
                <input type="hidden" name="category" value="{{ $categoryAllowed }}">
                <input type="hidden" name="supplier" value="{{ $supplier }}">
                <input type="hidden" name="from" value="{{ $from }}">
                <input type="hidden" name="to" value="{{ $to }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <div class="relative min-w-0 flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                        </svg>
                    </span>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Search item or unit…"
                           class="w-full pl-10 pr-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 border border-gray-200">
                </div>
            </form>

            {{-- Filter dropdown: category · supplier · purchase date range · page size --}}
            <div id="itemCatalogFilterWrap" class="relative">
                <button type="button" id="itemCatalogFilterBtn" aria-haspopup="true" aria-expanded="false" aria-controls="itemCatalogFilterMenu"
                        class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h18M6 9h12M10 14h4"/>
                    </svg>
                    <span>Filter</span>
                    @if ($filtersActive > 0)
                        <span class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-[#2a7a94] px-1 text-[10px] font-bold text-white">{{ $filtersActive }}</span>
                    @endif
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m19 9-7 7-7-7"/>
                    </svg>
                </button>

                <div id="itemCatalogFilterMenu" role="group" aria-labelledby="itemCatalogFilterBtn" aria-hidden="true"
                     class="hidden absolute left-0 top-full mt-2 z-30 w-72 rounded-lg border border-gray-200 bg-white py-2 shadow-lg">
                    <form method="GET" action="{{ route('item-catalog') }}" id="itemCatalogFilterForm" class="space-y-2 px-2">
                        <input type="hidden" name="q" value="{{ $search }}">

                        <div>
                            <label for="catalogCategory" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Category</label>
                            <select id="catalogCategory" name="category" aria-label="Filter by category" onchange="this.form.requestSubmit()"
                                    class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                <option value="">All categories</option>
                                @foreach ($categoryOptions as $name)
                                    <option value="{{ $name }}" @selected($categoryAllowed === $name)>{{ ucfirst($name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="catalogSupplier" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Supplier</label>
                            <select id="catalogSupplier" name="supplier" aria-label="Filter by supplier" onchange="this.form.requestSubmit()"
                                    class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                <option value="">All suppliers</option>
                                @foreach ($filterSuppliers as $s)
                                    <option value="{{ $s->id }}" @selected($supplier === (string) $s->id)>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="catalogFrom" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">From</label>
                                <input type="date" id="catalogFrom" name="from" value="{{ $from }}" aria-label="Purchase date from"
                                       class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300"
                                       onchange="this.form.requestSubmit()">
                            </div>
                            <div>
                                <label for="catalogTo" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">To</label>
                                <input type="date" id="catalogTo" name="to" value="{{ $to }}" aria-label="Purchase date to"
                                       class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300"
                                       onchange="this.form.requestSubmit()">
                            </div>
                        </div>

                        <div>
                            <label for="catalogPerPage" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Items per page</label>
                            <select id="catalogPerPage" name="per_page" aria-label="Items per page" onchange="this.form.requestSubmit()"
                                    class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                @foreach (\App\Support\Pagination::options() as $value => $label)
                                    <option value="{{ $value }}" @selected($perPage === $value)>{{ $value }} / page</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($filtersActive > 0)
                            <a href="{{ route('item-catalog', array_filter(['q' => $search !== '' ? $search : null, 'per_page' => $perPage])) }}"
                               class="mt-1 flex items-center justify-center rounded-md border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                Clear filters
                            </a>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <button type="button" id="printCatalogBtn"
                    class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                    title="Print the filtered catalog (all matching rows)">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V4a1 1 0 011-1h10a1 1 0 011 1v5M6 17h12M6 13H4a2 2 0 01-2-2v-3a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2m-12 0v6a1 1 0 001 1h10a1 1 0 001-1v-6"/>
                </svg>
                Print
            </button>

      
        </div>
    </div>

    {{-- Summary strip --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Items tracked</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $pager->total() }}</p>
            <p class="mt-1 text-xs text-gray-500">items with ≥1 completed line</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Total spend</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $peso($totalSpend) }}</p>
            <p class="mt-1 text-xs text-gray-500">completed PO lines only</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Suppliers used</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $suppliersUsed }}</p>
            <p class="mt-1 text-xs text-gray-500">suppliers of delivered lines</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Lines bought</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $totalLines }}</p>
            <p class="mt-1 text-xs text-gray-500">fully-received PO lines</p>
        </div>
    </div>

    {{-- Catalog table --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">Items bought</h2>
                <p class="mt-0.5 text-xs text-gray-500">Click a row for the full purchase history and price comparison.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">{{ $pager->total() }} total</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#0e5266]">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-white">
                        <th scope="col" class="whitespace-nowrap px-6 py-3">Item</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3">Category</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3 text-center">Orders</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3 text-center">Total qty</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Best price</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Last price</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3">Last supplier</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Total spend</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3">Last purchase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($pager->items() as $item)
                        <tr class="group cursor-pointer transition-colors odd:bg-white even:bg-[#f2f7f8] hover:bg-[#e4edf0]"
                            data-href="{{ route('item-catalog.show', ['item' => $item['id']]) }}">
                            <td class="whitespace-nowrap px-6 py-3">
                                <p class="font-semibold text-gray-900">{{ $item['name'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $item['unit'] }}</p>
                            </td>
                            <td class="whitespace-nowrap px-6 py-3">
                                <span class="inline-flex items-center rounded-full bg-teal-50 px-2 py-0.5 text-[11px] font-medium text-teal-800">{{ ucfirst($item['category']) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-3 text-center text-gray-700">{{ $item['orders'] }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-center text-gray-700">{{ number_format($item['qty']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right font-medium text-gray-900">{{ $peso($item['best_price']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right font-medium text-gray-900">{{ $peso($item['last_price']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-gray-700">{{ $item['last_supplier'] }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right font-semibold text-gray-900">{{ $peso($item['spend']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-xs text-gray-500">{{ $item['last_issued_on']?->format('M j, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-sm text-gray-500">
                                {{ $filtersActive > 0 || ($search ?? '') !== ''
                                    ? 'No items match the current filters.'
                                    : 'No completed purchases yet. Once a purchase order is fully received, its items will appear here.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pager->hasPages())
            <div class="shrink-0 border-t border-gray-200 px-6 py-3">
                {{ $pager->links() }}
            </div>
        @endif
    </div>

    {{-- Hidden print-only sheet: the filtered catalog in sheet form. --}}
    <div id="catalogPrintSheet" class="print-sheet" aria-hidden="true">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:16pt; border-bottom:2.5pt solid #0e5266; padding-bottom:6pt; margin-bottom:8pt;">
            <div>
                <h1 style="margin:0; font-size:15pt; font-weight:700; color:#0e5266;">Item Catalog — Purchase Summary</h1>
                <p style="margin:2pt 0 0; font-size:9pt; color:#333;">{{ $filterLabel }}</p>
                <p style="margin:1pt 0 0; font-size:8pt; color:#666;">Generated {{ now()->format('F j, Y · g:i A') }} by Purchasing · fully-received PO lines only</p>
            </div>
            <div style="text-align:right; font-size:8pt; color:#333;">
                <p style="margin:0 0 1.5pt;"><span style="font-weight:700;">Items tracked:</span> {{ $pager->total() }}</p>
                <p style="margin:0 0 1.5pt;"><span style="font-weight:700;">Lines bought:</span> {{ $totalLines }}</p>
                <p style="margin:0;"><span style="font-weight:700;">Suppliers used:</span> {{ $suppliersUsed }}</p>
            </div>
        </div>

        <table style="width:100%; border-collapse:collapse; font-size:7.5pt;">
            <thead>
                <tr>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:left;">Item</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:left;">Category</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:right;">Orders</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:right;">Total qty</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:right;">Best price</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:right;">Last price</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:left;">Last supplier</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:right;">Total spend</th>
                    <th style="border:1px solid #000; padding:4pt; background:#0e5266; color:#fff; text-align:left;">Last purchase</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $printItem)
                    <tr>
                        <td style="border:1px solid #000; padding:4pt; font-weight:600;">{{ $printItem['name'] }}<br><span style="font-weight:400; color:#555; font-size:7pt;">{{ $printItem['unit'] }}</span></td>
                        <td style="border:1px solid #000; padding:4pt;">{{ ucfirst($printItem['category']) }}</td>
                        <td style="border:1px solid #000; padding:4pt; text-align:right;">{{ number_format($printItem['orders']) }}</td>
                        <td style="border:1px solid #000; padding:4pt; text-align:right;">{{ number_format($printItem['qty']) }}</td>
                        <td style="border:1px solid #000; padding:4pt; text-align:right;">{{ $peso($printItem['best_price']) }}</td>
                        <td style="border:1px solid #000; padding:4pt; text-align:right;">{{ $peso($printItem['last_price']) }}</td>
                        <td style="border:1px solid #000; padding:4pt;">{{ $printItem['last_supplier'] }}</td>
                        <td style="border:1px solid #000; padding:4pt; text-align:right;">{{ $peso($printItem['spend']) }}</td>
                        <td style="border:1px solid #000; padding:4pt;">{{ $printItem['last_issued_on']?->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="display:flex; justify-content:flex-end; gap:18pt; margin-top:6pt; border-top:1pt solid #ccc; padding-top:4pt; font-size:8pt;">
            <span><span style="font-weight:700;">Total qty:</span> {{ number_format($totalUnits) }}</span>
            <span><span style="font-weight:700;">Total spend:</span> {{ $peso($totalSpend) }}</span>
        </div>
    </div>

    <style>
        .print-sheet { display: none; }
        @media print {
            /* Officio (8.5 x 13 in), landscape */
            @page { size: 13in 8.5in; margin: 10mm; }
            body * { visibility: hidden; }
            #catalogPrintSheet, #catalogPrintSheet * { visibility: visible; }
            #catalogPrintSheet { display: block !important; position: absolute; inset: 0; background: #fff; }
            #catalogPrintSheet tr { page-break-inside: avoid; }
            table thead { display: table-header-group; }
            html, body { overflow: visible !important; height: auto !important; background: #fff !important; }
        }
    </style>

    <script> document.getElementById('printCatalogBtn')?.addEventListener('click', function () { window.print(); }); </script>
    <script>
        document.querySelectorAll('tr[data-href]').forEach(function (row) {
            row.addEventListener('click', function () {
                window.location.href = row.dataset.href;
            });
        });

        // Filter dropdown: open/close with Escape and click-outside.
        var filterWrap = document.getElementById('itemCatalogFilterWrap');
        var filterBtn = document.getElementById('itemCatalogFilterBtn');
        var filterMenu = document.getElementById('itemCatalogFilterMenu');

        function closeCatalogMenu() {
            filterMenu.classList.add('hidden');
            filterBtn.setAttribute('aria-expanded', 'false');
            filterMenu.setAttribute('aria-hidden', 'true');
        }

        if (filterWrap && filterBtn && filterMenu) {
            filterBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isHidden = filterMenu.classList.toggle('hidden');
                filterBtn.setAttribute('aria-expanded', String(!isHidden));
                filterMenu.setAttribute('aria-hidden', String(isHidden));
            });
            document.addEventListener('click', function (e) {
                if (!filterWrap.contains(e.target)) closeCatalogMenu();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeCatalogMenu();
            });
        }
    </script>
@endsection
