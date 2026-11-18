@extends('layouts.app')

@section('page-title', 'ENTITIES')
@section('title', 'Entity Profile - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="flex min-h-full flex-col lg:h-full lg:min-h-0">

    @php
        // One view renders both profile layouts depending on $type.
        $type = $type ?? 'procuring_entity';
        $isSupplier = $type === 'supplier';
        $backUrl = route('entities', ['tab' => $isSupplier ? 'suppliers' : 'entities']);
        $show = $isSupplier ? ($supplier ?? []) : ($entity ?? []);
    @endphp

    @if ($isSupplier)
        @php
            $supplier = $supplier ?? [];
            $peso = fn ($amount) => '₱ ' . number_format((float) $amount, 2);
            $quotationHistory = $quotationHistory ?? [];
            $purchaseHistory = $purchaseHistory ?? [];

            $deliveredLines = count($purchaseHistory);
            $totalSpend = array_sum(array_map(fn ($row) => (float) ($row['spend'] ?? 0), $purchaseHistory));
            $totalQty = array_sum(array_map(fn ($row) => (float) ($row['qty'] ?? 0), $purchaseHistory));
            $avgPrice = $totalQty > 0 ? $totalSpend / $totalQty : 0;
            $bestPrice = $deliveredLines > 0 ? min(array_column($purchaseHistory, 'best_price')) : null;
        @endphp

        {{-- Header --}}
        <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-gray-900">{{ $supplier['name'] ?? 'Supplier' }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ $supplier['address'] ?? '—' }}</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-800">Supplier</span>
        </div>

        <div class="grid grid-cols-1 items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[300px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]">

            {{-- LEFT RAIL --}}
            <div class="flex flex-col gap-4 lg:max-h-full lg:overflow-y-auto">

                {{-- Supplier details --}}
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Supplier details</p>
                    <div class="mt-2 divide-y divide-gray-100">
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Contact person</span>
                            <span class="text-right font-semibold text-gray-900">{{ $supplier['contact_person'] ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Contact no.</span>
                            <span class="text-right font-semibold text-gray-900">{{ $supplier['contact_no'] ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Email</span>
                            <span class="text-right font-semibold text-gray-900">{{ $supplier['email'] ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Address</span>
                            <span class="max-w-[60%] text-right font-semibold text-gray-900">{{ $supplier['address'] ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Relationship (all real figures from purchase history) --}}
                <div class="rounded-lg border border-[#d9ecdf] bg-[#edf8f0] p-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-[#166534]">Relationship</p>
                    <div class="mt-2 divide-y divide-[#d9ecdf]">
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-[#166534]/70">Lines delivered</span>
                            <span class="text-right font-semibold text-gray-900">{{ $deliveredLines }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-[#166534]/70">Total spend</span>
                            <span class="text-right font-semibold text-gray-900">{{ $peso($totalSpend) }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-[#166534]/70">Avg price</span>
                            <span class="text-right font-semibold text-gray-900">{{ $peso($avgPrice) }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-[#166534]/70">Best price</span>
                            <span class="text-right font-semibold text-[#166534]">{{ $bestPrice !== null ? $peso($bestPrice) : '—' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="flex min-w-0 flex-col gap-4 lg:max-h-full lg:overflow-y-auto">

                {{-- Quotation history --}}
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Quotation history</p>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600">{{ count($quotationHistory) }} RFQ{{ count($quotationHistory) === 1 ? '' : 's' }}</span>
                    </div>
                    @if (empty($quotationHistory))
                        <p class="px-4 py-6 text-center text-sm text-gray-400">No RFQs sent to this supplier yet.</p>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach ($quotationHistory as $quotation)
                                @php
                                    $quotationPill = $quotation['status'] === 'Quoted' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600';
                                @endphp
                                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 transition-colors odd:bg-white even:bg-[#f2f7f8] hover:bg-[#e4edf0]">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900">{{ $quotation['name'] }}</p>
                                        <p class="mt-0.5 text-[11px] text-gray-500">
                                            <a href="{{ route('quotation.show', ['quotation' => $quotation['id']]) }}" class="font-semibold text-[#2a7a94] hover:underline">{{ $quotation['rfq_number'] }}</a>
                                            · {{ $quotation['reference'] }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $quotationPill }}">{{ $quotation['status'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Purchase history --}}
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Purchase history</p>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600">{{ $deliveredLines }} line{{ $deliveredLines === 1 ? '' : 's' }}</span>
                    </div>
                    @if (empty($purchaseHistory))
                        <p class="px-4 py-6 text-center text-sm text-gray-400">No completed orders from this supplier yet.</p>
                    @else
                        <div class="divide-y divide-gray-100">
                            @foreach ($purchaseHistory as $row)
                                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 transition-colors odd:bg-white even:bg-[#f2f7f8] hover:bg-[#e4edf0]">
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900">
                                            <a href="{{ route('item-catalog.show', ['item' => $row['item_id']]) }}" class="hover:text-[#2a7a94] hover:underline">{{ $row['name'] }}</a>
                                            <span class="ml-2 text-xs font-medium text-gray-400">{{ number_format($row['qty']) }} {{ $row['unit'] }}</span>
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-gray-500">{{ $row['orders'] }} order{{ $row['orders'] === 1 ? '' : 's' }} · avg {{ $peso($row['avg_price']) }} · best {{ $peso($row['best_price']) }} · latest {{ $peso($row['latest_price']) }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-semibold text-gray-900">{{ $peso($row['spend']) }}</p>
                                        <p class="mt-0.5 text-[10px] text-gray-400">@if ($row['last_po']){{ $row['last_po'] }}@else last order @endif</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

    @else
        @php
            $entity = $entity ?? [];
            $relatedProjects = $relatedProjects ?? [];
            $portfolioTotal = count($relatedProjects);
            $portfolioCompleted = count(array_filter($relatedProjects, fn ($p) => ($p['status'] ?? '') === 'Completed'));
            $portfolioInProgress = count(array_filter($relatedProjects, fn ($p) => ($p['status'] ?? '') === 'In progress'));
        @endphp

        {{-- Header --}}
        <div class="mb-4 flex flex-wrap items-start justify-between gap-4">
            <div class="min-w-0">
                <h2 class="text-2xl font-bold text-gray-900">{{ $entity['name'] ?? 'Entity' }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ $entity['address'] ?? '—' }}</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-blue-800">{{ $entity['type'] ?? 'Procuring entity' }}</span>
        </div>

        <div class="grid grid-cols-1 items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[300px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]">

            {{-- LEFT RAIL --}}
            <div class="flex flex-col gap-4 lg:max-h-full lg:overflow-y-auto">

                {{-- Entity details --}}
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Entity details</p>
                    <div class="mt-2 divide-y divide-gray-100">
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Type</span>
                            <span class="text-right font-semibold text-gray-900">{{ $entity['type'] ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Contact person</span>
                            <span class="text-right font-semibold text-gray-900">{{ $entity['contact_person'] ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Contact no.</span>
                            <span class="text-right font-semibold text-gray-900">{{ $entity['contact_no'] ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Email</span>
                            <span class="text-right font-semibold text-gray-900">{{ $entity['email'] ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Portfolio (real project counts) --}}
                <div class="rounded-lg border border-[#dce7f5] bg-[#eef4fb] p-4 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-[#1e4e79]">Portfolio</p>
                    <div class="mt-2 divide-y divide-[#dce7f5]">
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-[#1e4e79]/70">Projects</span>
                            <span class="text-right font-semibold text-gray-900">{{ $portfolioTotal }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-[#1e4e79]/70">Completed</span>
                            <span class="text-right font-semibold text-gray-900">{{ $portfolioCompleted }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-[#1e4e79]/70">In progress</span>
                            <span class="text-right font-semibold text-gray-900">{{ $portfolioInProgress }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN --}}
            <div class="flex min-w-0 flex-col gap-4 lg:max-h-full lg:overflow-y-auto">

                {{-- Related projects — real rows from the projects table --}}
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Related projects</p>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600">{{ $portfolioTotal }} project{{ $portfolioTotal === 1 ? '' : 's' }}</span>
                    </div>
                    @if (empty($relatedProjects))
                        <p class="px-4 py-8 text-center text-sm text-gray-400">No projects reference this entity yet. Projects created for this entity will appear here.</p>
                    @else
                        <table class="w-full">
                            <thead class="bg-[#0e5266]">
                                <tr class="text-left text-[10px] font-semibold uppercase tracking-wider text-white">
                                    <th scope="col" class="px-4 py-2.5">Project</th>
                                    <th scope="col" class="px-4 py-2.5">Reference</th>
                                    <th scope="col" class="px-4 py-2.5">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($relatedProjects as $project)
                                    @php
                                        $pill = match ($project['status']) {
                                            'Completed' => 'bg-green-100 text-green-700',
                                            'In progress' => 'bg-blue-100 text-blue-700',
                                            default => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <tr class="odd:bg-white even:bg-[#f2f7f8] transition-colors hover:bg-[#e4edf0]">
                                        <td class="px-4 py-2.5">
                                            <a href="{{ route('projects.show', ['project' => $project['id']]) }}" class="block text-xs font-semibold leading-snug text-gray-900 hover:text-[#2a7a94]">{{ $project['name'] }}</a>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-xs text-gray-600">{{ $project['reference'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-2.5"><span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-semibold {{ $pill }}">{{ $project['status'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

    @endif

    {{-- Footer actions — pinned to the bottom like the other detail pages --}}
    <div class="no-print mt-4 flex shrink-0 flex-wrap items-center justify-between gap-3">
        <a href="{{ $backUrl }}" class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
            Back to Entities
        </a>
        <div class="flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('entities.destroy', ['type' => $type, 'id' => $id]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/></svg>
                    Delete {{ $isSupplier ? 'supplier' : 'entity' }}
                </button>
            </form>
            @if (auth()->user()?->canModule('entities', 'manage'))
                <a href="{{ route('entities.edit', ['type' => $type, 'id' => $id]) }}"
                   class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11.8 15H9v-2.8z"/></svg>
                    Edit {{ $isSupplier ? 'supplier' : 'entity' }}
                </a>
            @else
                <span class="flex cursor-not-allowed items-center gap-2 rounded-lg bg-gray-300 px-4 py-2 text-sm font-medium text-gray-500" title="Your role does not have access to that module.">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h11a2 2 0 0 0 2-2v-5m-1.4-9.4a2 2 0 1 1 2.8 2.8L11.8 15H9v-2.8z"/></svg>
                    Edit {{ $isSupplier ? 'supplier' : 'entity' }}
                </span>
            @endif
        </div>
    </div>

    </div>{{-- /flex column --}}
@endsection
