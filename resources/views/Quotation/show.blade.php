@extends('layouts.app')

@section('page-title', 'QUOTATION')
@section('title', 'Quotation - Purchasing')

@section('header-actions')
@endsection

@section('content')
    {{-- @TODO: Replace the hardcoded records below with a real query, e.g.
         $quotation = Quotation::with(['supplier', 'prepList', 'document', 'items.allocations.sourceProject'])
             ->findOrFail($id);
         and compute "items quoted" / total from the relations instead of the array. --}}
    @php
        $quotations = [
            1 => [
                'id' => 1,
                'supplier' => 'Triple Tact',
                'prep_list' => 'July Region VII batch',
                'reference_no' => '#2026-0090',
                'document' => null,
                'items' => [
                    [
                        'name' => 'Amoxicillin 500mg, box of 100',
                        'requested' => 100,
                        'unit' => 'box',
                        'unit_price' => null,
                        'allocations' => [
                            ['project' => 'Pharma supplies Q3', 'reference' => 'RFQ-2026-0042', 'qty' => 50],
                            ['project' => 'Laboratory reagents', 'reference' => 'RFQ-2026-0038', 'qty' => 50],
                        ],
                    ],
                    [
                        'name' => 'Surgical gloves, box of 50 pairs',
                        'requested' => 40,
                        'unit' => 'box',
                        'unit_price' => null,
                        'allocations' => [
                            ['project' => 'Pharma supplies Q3', 'reference' => 'RFQ-2026-0042', 'qty' => 40],
                        ],
                    ],
                ],
            ],
            2 => [
                'id' => 2,
                'supplier' => 'GreenCore Pharmaceuticals',
                'prep_list' => 'July Region VII batch',
                'reference_no' => '#2026-0090',
                'document' => [
                    'name' => 'greencore_quotation_july2026.pdf',
                    'size' => '240 KB',
                ],
                'items' => [
                    [
                        'name' => 'Amoxicillin 500mg, box of 100',
                        'requested' => 100,
                        'unit' => 'box',
                        'unit_price' => 305.00,
                        'allocations' => [
                            ['project' => 'Pharma supplies Q3', 'reference' => 'RFQ-2026-0042', 'qty' => 50],
                            ['project' => 'Laboratory reagents', 'reference' => 'RFQ-2026-0038', 'qty' => 50],
                        ],
                    ],
                    [
                        'name' => 'Surgical gloves, box of 50 pairs',
                        'requested' => 40,
                        'unit' => 'box',
                        'unit_price' => null,
                        'allocations' => [
                            ['project' => 'Pharma supplies Q3', 'reference' => 'RFQ-2026-0042', 'qty' => 40],
                        ],
                    ],
                ],
            ],
            3 => [
                'id' => 3,
                'supplier' => 'MedSource Inc.',
                'prep_list' => 'Lab reagents – PGH',
                'reference_no' => '#2026-0089',
                'document' => [
                    'name' => 'medsource_quotation_july2026.pdf',
                    'size' => '318 KB',
                ],
                'items' => [
                    [
                        'name' => 'Sodium chloride 0.9% 500mL, box of 24',
                        'requested' => 60,
                        'unit' => 'box',
                        'unit_price' => 850.00,
                        'allocations' => [
                            ['project' => 'Laboratory reagents', 'reference' => 'RFQ-2026-0038', 'qty' => 30],
                            ['project' => 'Pharma supplies Q3', 'reference' => 'RFQ-2026-0042', 'qty' => 30],
                        ],
                    ],
                    [
                        'name' => 'Sterile gloves, box of 50 pairs',
                        'requested' => 40,
                        'unit' => 'box',
                        'unit_price' => 310.00,
                        'allocations' => [
                            ['project' => 'Laboratory reagents', 'reference' => 'RFQ-2026-0038', 'qty' => 40],
                        ],
                    ],
                    [
                        'name' => 'Microscope slides, box of 100',
                        'requested' => 25,
                        'unit' => 'box',
                        'unit_price' => 220.00,
                        'allocations' => [
                            ['project' => 'Laboratory reagents', 'reference' => 'RFQ-2026-0038', 'qty' => 15],
                            ['project' => 'Pharma supplies Q3', 'reference' => 'RFQ-2026-0042', 'qty' => 10],
                        ],
                    ],
                ],
            ],
        ];

        $quotation = $quotations[$id ?? 2] ?? $quotations[2];

        $totalItems = count($quotation['items']);
        $itemsQuoted = collect($quotation['items'])
            ->filter(fn ($item) => $item['unit_price'] !== null)
            ->count();
        $quotedTotal = collect($quotation['items'])
            ->filter(fn ($item) => $item['unit_price'] !== null)
            ->sum(fn ($item) => $item['unit_price'] * $item['requested']);

        $status = $itemsQuoted > 0 ? 'Quoted' : 'Awaiting quote';

        $fractionColor = $itemsQuoted === 0
            ? 'text-gray-400'
            : ($itemsQuoted >= $totalItems ? 'text-green-600' : 'text-amber-500');

        $peso = fn ($amount) => '₱ ' . number_format((float) $amount, 2);
    @endphp

    <div class="pb-12">
        {{-- Supplier / prep list summary --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-xl font-bold text-gray-900">{{ $quotation['supplier'] }}</h2>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $quotation['prep_list'] }}
                        <span class="mx-1 text-gray-300">&middot;</span>
                        {{ $quotation['reference_no'] }}
                    </p>

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

        {{-- Supplier's quotation document --}}
        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
            Supplier's quotation document
        </p>

        @if ($quotation['document'])
            <div class="mb-6 flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-red-50">
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-gray-900">{{ $quotation['document']['name'] }}</p>
                        <p class="text-xs text-gray-500">{{ $quotation['document']['size'] }}</p>
                    </div>
                </div>

                {{-- @TODO: Point this at the stored file URL and use a controller download response. --}}
                <a href="#"
                   class="flex flex-shrink-0 items-center gap-1.5 text-sm font-medium text-[#2a7a94] hover:underline"
                   aria-label="Download {{ $quotation['document']['name'] }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                    Download
                </a>
            </div>
        @else
            <div class="mb-6 flex items-center gap-3 rounded-lg border border-dashed border-gray-300 bg-white px-4 py-3">
                <svg class="h-5 w-5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3v4a1 1 0 0 0 1 1h4"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 21H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7l5 5v11a2 2 0 0 1-2 2Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9.5 13.5 5 5m0-5-5 5"/>
                </svg>
                <p class="text-sm text-gray-500">No document attached</p>
            </div>
        @endif

        {{-- Item breakdown --}}
        <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Item breakdown</p>

        <div class="space-y-4">
            @foreach ($quotation['items'] as $item)
                @php
                    $isQuoted = $item['unit_price'] !== null;
                @endphp

                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-start justify-between gap-4 px-5 py-4">
                        <div class="min-w-0">
                            <p class="font-bold text-gray-900">{{ $item['name'] }}</p>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ $item['requested'] }} {{ $item['unit'] }} requested
                            </p>
                        </div>

                        <div class="flex-shrink-0 text-right">
                            @if ($isQuoted)
                                <p class="text-sm font-bold text-gray-900">
                                    {{ $peso($item['unit_price']) }} / {{ $item['unit'] }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    {{ $peso($item['unit_price'] * $item['requested']) }} subtotal
                                </p>
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
                                    {{ $allocation['qty'] }} {{ $item['unit'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Grand total for quoted items only --}}
        @if ($quotedTotal > 0)
            <div class="mt-3 flex items-center justify-end gap-2 text-sm">
                <span class="font-bold text-gray-900">Total quoted so far:</span>
                <span class="font-bold text-gray-900">{{ $peso($quotedTotal) }}</span>
            </div>
        @endif

        {{-- Footer actions --}}
        <div class="mt-8 flex items-center justify-between gap-3">
            <a href="{{ route('quotation') }}"
               class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to list
            </a>

            {{-- @TODO: Point this at route('quotation.edit', ['id' => $quotation['id']]) once the edit route exists. --}}
            <a href="#"
               class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                @if ($status === 'Quoted')
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                    </svg>
                    Edit quotation
                @else
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 7.5 12 3m0 0L7.5 7.5M12 3v13.5"/>
                    </svg>
                    Submit quotation
                @endif
            </a>
        </div>
    </div>
@endsection
