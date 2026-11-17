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

        <p class="text-xs text-gray-500">From completed purchase orders · line fully received</p>
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
