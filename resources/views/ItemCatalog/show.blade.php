@extends('layouts.app')

@section('page-title', 'ITEM CATALOG')
@section('title', 'Item Catalog - '.$item->name)

@section('header-actions')
@endsection

@section('content')
    @php
        $peso = fn ($amount) => '₱ ' . number_format((float) $amount, 2);
        $maxPoints = max(count($chartPoints), 1);
        $minPrice = $chartPoints ? min(array_column($chartPoints, 'price')) : 0;
        $maxPrice = $chartPoints ? max(array_column($chartPoints, 'price')) : 1;
        $priceRange = ($maxPrice - $minPrice) ?: 1;
        $bestPrice = $stats['best_price'] ?? null;
    @endphp

    <div class="mb-2">
        <a href="{{ route('item-catalog') }}" class="inline-flex items-center gap-2 text-xs text-gray-500 hover:text-[#2a7a94]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to Item Catalog
        </a>
    </div>

    {{-- Header --}}
    <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-bold text-gray-900">{{ $item->name }}</h2>
                @if ($item->category)
                    <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-teal-800">{{ $item->category->name }}</span>
                @endif
            </div>
            <p class="mt-1 text-xs text-gray-500">Unit: {{ $item->unit }} · Default min shelf life: {{ $item->default_min_shelf_life_months ?: 0 }} month{{ $item->default_min_shelf_life_months == 1 ? '' : 's' }}</p>
        </div>
    </div>

    {{-- Two-column layout: left rail + right content --}}
    <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[300px_minmax(0,1fr)]">

        {{-- LEFT RAIL --}}
        <div class="flex flex-col gap-4">

            {{-- Item details --}}
            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Item details</p>
                <div class="mt-2 divide-y divide-gray-100">
                    <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                        <span class="text-gray-500">Unit</span>
                        <span class="text-right font-semibold text-gray-900">{{ $item->unit }}</span>
                    </div>
                    <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                        <span class="text-gray-500">Category</span>
                        <span class="text-right font-semibold text-gray-900">{{ $item->category?->name ?? 'Uncategorised' }}</span>
                    </div>
                    <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                        <span class="text-gray-500">Default min shelf life</span>
                        <span class="text-right font-semibold text-gray-900">{{ $item->default_min_shelf_life_months ?: 0 }} month{{ $item->default_min_shelf_life_months == 1 ? '' : 's' }}</span>
                    </div>
                    @if ($requestedProjects->isNotEmpty())
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Requested by</span>
                            <span class="text-right font-semibold text-gray-900">{!! $requestedProjects->map(fn ($p) => '<a class="text-[#2a7a94] hover:underline" href="'.route('projects.show', ['project' => $p->id]).'">'.e($p->reference_no ?: $p->title).'</a>')->join(', ') !!}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Best supplier by spend --}}
            @if ($bestSupplier)
                <div class="rounded-lg border border-[#d9ecdf] bg-[#edf8f0] p-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-[#166534]">Best supplier by spend</p>
                    <div class="mt-2 flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#dcefe3] text-sm font-bold text-[#166534]">{{ strtoupper(substr($bestSupplier['supplier'], 0, 1)) }}</span>
                        <div class="min-w-0">
                            <p class="truncate font-bold tracking-wide text-gray-900">{{ $bestSupplier['supplier'] }}</p>
                            <p class="mt-0.5 text-[10px] text-gray-500">{{ $bestSupplier['orders'] }} order{{ $bestSupplier['orders'] === 1 ? '' : 's' }} · {{ $peso($bestSupplier['spend']) }}</p>
                        </div>
                    </div>
                    <div class="mt-3 flex items-baseline justify-between gap-3 border-t border-[#d9ecdf] pt-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-[#166534]">Avg cost</span>
                        <span class="text-xl font-extrabold text-gray-900">{{ $peso($bestSupplier['avg_price']) }}</span>
                    </div>
                </div>
            @endif

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="flex min-w-0 flex-col gap-4">

    {{-- Stat strip --}}
    @if ($stats)
        <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-lg border border-gray-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Times ordered</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $stats['orders'] }}</p>
                <p class="text-[10px] text-gray-400">across {{ $supplierBreakdown->count() }} supplier{{ $supplierBreakdown->count() === 1 ? '' : 's' }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Total qty</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ number_format($stats['qty']) }}</p>
                <p class="text-[10px] text-gray-400">{{ $item->unit }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Total spend</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $peso($stats['spend']) }}</p>
                <p class="text-[10px] text-gray-400">completed PO lines</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Weighted avg</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $peso($stats['avg_price']) }}</p>
                <p class="text-[10px] text-gray-400">per {{ $item->unit }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Best price paid</p>
                <p class="mt-1 text-lg font-bold text-green-600">{{ $peso($stats['best_price']) }}</p>
                <p class="text-[10px] text-gray-400"> lowest unit price this year</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white px-3 py-3 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Last purchase</p>
                <p class="mt-1 text-lg font-bold text-gray-900">{{ $peso($stats['last_price']) }}</p>
                <p class="text-[10px] text-gray-400 truncate">{{ $stats['last_supplier'] }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 2xl:grid-cols-2">
        {{-- Supplier comparison --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Supplier comparison</h3>
                <p class="mt-0.5 text-xs text-gray-500">What we actually paid per supplier, across all completed orders.</p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-[#0e5266]">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-white">
                        <th scope="col" class="whitespace-nowrap px-4 py-3">Supplier</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-center">Orders</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Avg price</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Best</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Latest</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Spend</th>
                        <th scope="col" class="whitespace-nowrap px-4 py-3">Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @if ($supplierBreakdown->isEmpty())
                        <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No deliveries yet</td></tr>
                    @else
                        @foreach ($supplierBreakdown as $row)
                            <tr class="odd:bg-white even:bg-[#f2f7f8]">
                                <td class="px-4 py-3">
                                    <a href="{{ route('entities.show', ['type' => 'supplier', 'id' => $row['supplier_id']]) }}"
                                       class="text-xs font-semibold text-gray-900 hover:text-[#2a7a94] hover:underline">{{ $row['supplier'] }}</a>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ $row['orders'] }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ $peso($row['avg_price']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900 {{ $bestPrice !== null && abs($row['best_price'] - $bestPrice) < 0.0001 ? 'text-green-600' : '' }}">{{ $peso($row['best_price']) }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ $peso($row['latest_price']) }}</td>
                                <td class="px-4 py-3 text-right text-gray-700">{{ $peso($row['spend']) }}</td>
                                <td class="px-4 py-3 w-28">
                                    @php $share = $totalSpendAll > 0 ? ($row['spend'] / $totalSpendAll) * 100 : 0; @endphp
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-20 overflow-hidden rounded-full bg-gray-200">
                                            <div class="h-full bg-[#2a7a94]" style="width: {{ min(100, $share) }}%"></div>
                                        </div>
                                        <span class="text-[10px] text-gray-500">{{ round($share) }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
            <div class="border-t border-gray-100 px-6 py-3 text-[11px] text-gray-500">
                @if ($bestSupplier)
                    Best deal: <span class="font-semibold text-gray-800">{{ $bestSupplier['supplier'] }}</span> at {{ $peso($bestSupplier['avg_price']) }} average; latest price {{ $peso($bestSupplier['latest_price']) }}.
                @endif
            </div>
        </div>

        {{-- Price over time --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Price over time</h3>
                <p class="mt-0.5 text-xs text-gray-500">Unit price paid per completed order.</p>
            </div>
            <div class="px-6 py-5">
                @if (count($chartPoints) < 2)
                    <p class="py-10 text-center text-sm text-gray-400">Needs at least two completed purchases to chart a trend.</p>
                @else
                    @php
                        $w = 400; $h = 120; $padX = 24; $padY = 16;
                        $count = count($chartPoints);
                        $step = $count > 1 ? ($w - $padX * 2) / ($count - 1) : 0;
                        $scale = fn ($price) => (int) round(($h - $padY) - (($price - $minPrice) / $priceRange) * ($h - 2 * $padY));
                        $points = collect($chartPoints)->map(fn ($p, $i) => ($padX + $i * $step) . ',' . $scale($p['price']))->join(' ');
                        $labels = collect($chartPoints)->map(fn ($p) => $p['label'])->all();
                        $ticks = [$minPrice, ($minPrice + $maxPrice) / 2, $maxPrice];
                    @endphp
                    <svg viewBox="0 0 {{ $w }} {{ $h + 22 }}" class="w-full" role="img" aria-label="Unit price paid across orders">
                        <line x1="{{ $padX }}" y1="{{ $h - $padY }}" x2="{{ $w - $padX }}" y2="{{ $h - $padY }}" stroke="#e5e7eb"/>
                        <line x1="{{ $padX }}" y1="{{ $padY }}" x2="{{ $w - $padX }}" y2="{{ $padY }}" stroke="#e5e7eb" stroke-dasharray="3 4"/>
                        <polyline points="{{ $points }}" fill="none" stroke="#2a7a94" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"/>
                        @foreach ($chartPoints as $i => $p)
                            <circle cx="{{ $padX + $i * $step }}" cy="{{ $scale($p['price']) }}" r="3.5" fill="#fff" stroke="#0e5266" stroke-width="2"/>
                        @endforeach
                        @foreach ($ticks as $tick)
                            <text x="{{ $padX - 2 }}" y="{{ $scale($tick) }}" text-anchor="end" font-size="8" fill="#9ca3af">₱{{ number_format($tick, 2) }}</text>
                        @endforeach
                        @foreach ($labels as $i => $label)
                            <text x="{{ $padX + $i * $step }}" y="{{ $h + 14 }}" text-anchor="middle" font-size="8" fill="#9ca3af">{{ $label }}</text>
                        @endforeach
                    </svg>
                @endif
            </div>
            <div class="border-t border-gray-100 px-6 py-3 text-[11px] text-gray-500">
                Min ₱{{ number_format($minPrice, 2) }} · Max ₱{{ number_format($maxPrice, 2) }}{{ $stats ? ' across '.$stats['orders'].' orders' : '' }}
            </div>
        </div>
    </div>

    {{-- Purchase history --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-4">
            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Purchase history</h3>
            <p class="mt-0.5 text-xs text-gray-500">Every completed line for this item, newest first.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#0e5266]">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-white">
                        <th scope="col" class="whitespace-nowrap px-6 py-3">PO</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3">RFQ</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3">Supplier</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Qty</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Unit price</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Line total</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3">Status</th>
                        <th scope="col" class="whitespace-nowrap px-6 py-3">Issued</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($history as $line)
                        <tr class="odd:bg-white even:bg-[#f2f7f8] transition-colors hover:bg-[#e4edf0]">
                            <td class="whitespace-nowrap px-6 py-3"><a href="{{ $line['po_id'] ? route('purchase-order.show', ['purchaseOrder' => $line['po_id']]) : '#' }}" class="font-semibold text-[#0e5266] hover:underline">{{ $line['po_number'] }}</a></td>
                            <td class="whitespace-nowrap px-6 py-3 text-gray-600">{{ $line['rfq_number'] ?? '—' }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-gray-700">{{ $line['supplier'] }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-gray-700">{{ number_format($line['qty']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-gray-700">{{ $peso($line['unit_price']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right font-semibold text-gray-900">{{ $peso($line['line_total']) }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-xs text-gray-600">{{ $line['status'] }}</td>
                            <td class="whitespace-nowrap px-6 py-3 text-xs text-gray-500">{{ $line['issued_on'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">No completed purchases yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

        </div>{{-- /right column --}}
    </div>{{-- /grid --}}
@endsection
