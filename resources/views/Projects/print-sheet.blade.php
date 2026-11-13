{{-- Print sheet: shared by the on-screen preview modal and the printed output. --}}
<div class="flex items-start justify-between border-b border-gray-300 pb-4">
    <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-500">Project Summary</p>
        <h1 class="mt-1 text-xl font-bold text-gray-900">{{ $project->title }}</h1>
        <p class="mt-0.5 text-xs text-gray-500">Reference no. <span class="font-semibold text-gray-800">{{ $project->reference_no }}</span></p>
    </div>
    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusPillClasses[$statusColor] }}">
        {{ $status }}
    </span>
</div>

<div class="mt-4 grid grid-cols-2 gap-x-8 gap-y-1.5 text-xs">
    <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-1">
        <span class="text-gray-500">Entity</span>
        <span class="font-medium text-gray-900 text-right">{{ $project->procuringEntity?->name ?? '—' }}</span>
    </div>
    <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-1">
        <span class="text-gray-500">Mode</span>
        <span class="font-medium text-gray-900 text-right">{{ $modeLabel }}</span>
    </div>
    <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-1">
        <span class="text-gray-500">Amount awarded</span>
        <span class="font-medium text-gray-900 text-right">{{ $project->amount_awarded !== null ? '₱ ' . number_format((float) $project->amount_awarded, 2) : '—' }}</span>
    </div>
    <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-1">
        <span class="text-gray-500">Date awarded</span>
        <span class="font-medium text-gray-900 text-right">{{ $project->date_awarded?->format('F j, Y') ?? '—' }}</span>
    </div>
    <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-1">
        <span class="text-gray-500">Delivery period</span>
        <span class="font-medium text-gray-900 text-right">{{ $deliveryPeriod }}</span>
    </div>
    <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 py-1">
        <span class="text-gray-500">Fulfillment</span>
        <span class="font-medium text-gray-900 text-right">{{ $progressLabel }}</span>
    </div>
</div>

@if ($project->notes)
    <div class="mt-4 rounded-md border border-gray-200 bg-gray-50 px-4 py-3">
        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Notes</p>
        <p class="mt-1 text-xs leading-relaxed text-gray-700">{{ $project->notes }}</p>
    </div>
@endif

<table class="mt-4 w-full border-collapse text-xs">
    <thead>
        <tr class="bg-gray-100">
            {{-- New print-only columns (not sourced from the database) — intentionally blank --}}
            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700">Item<br>No.</th>
            <th class="border border-gray-300 px-3 py-2 text-left font-semibold text-gray-700">DESCRIPTION</th>
            <th class="border border-gray-300 px-2 py-2 text-right font-semibold text-gray-700">Qty.</th>
            <th class="border border-gray-300 px-2 py-2 text-left font-semibold text-gray-700">Unit</th>
            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700">RQ</th>
            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700">Brand for Unit<br>Cost Basis</th>
            <th class="border border-gray-300 px-2 py-2 text-left font-semibold text-gray-700">Brand</th>
            <th class="border border-gray-300 px-2 py-2 text-left font-semibold text-gray-700">Lot #</th>
            <th class="border border-gray-300 px-2 py-2 text-left font-semibold text-gray-700">Exp. Date</th>
            <th class="border border-gray-300 px-2 py-2 text-right font-semibold text-gray-700">UNIT COST</th>
            <th class="border border-gray-300 px-2 py-2 text-right font-semibold text-gray-700">UNIT PRICE</th>
            <th class="border border-gray-300 px-2 py-2 text-right font-semibold text-gray-700">TOTAL UNIT<br>PRICE</th>
            {{-- New print-only columns (not sourced from the database) — intentionally blank --}}
            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700">COMPLETE/<br>LACKING(S)</th>
            <th class="border border-gray-300 px-2 py-2 text-center font-semibold text-gray-700">BOX<br>NO.</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($project->items as $projectItem)
            @php
                $unitPrice = $projectItem->awarded_unit_price ?? $projectItem->unit_cost;
                $remaining = $projectItem->remainingQuantity();
                $lineTotal = $remaining * (float) $unitPrice;
            @endphp
            <tr>
                {{-- Increment from 1 across the item rows; not sourced from the database. --}}
                <td class="border border-gray-300 px-2 py-2 text-center text-gray-900">{{ $loop->iteration }}</td>
                <td class="border border-gray-300 px-3 py-2 text-gray-900 break-words">{{ $projectItem->item?->name ?? '-' }}</td>
                <td class="border border-gray-300 px-2 py-2 text-right text-gray-900">{{ $remaining }}</td>
                <td class="border border-gray-300 px-2 py-2 text-gray-900">{{ $projectItem->item?->unit ?? '—' }}</td>
                {{-- Not sourced from the database — intentionally blank --}}
                <td class="border border-gray-300 px-2 py-2 text-center text-gray-900"></td>
                {{-- Not sourced from the database — intentionally blank --}}
                <td class="border border-gray-300 px-2 py-2 text-center text-gray-900"></td>
                {{-- Not sourced from the database — intentionally blank --}}
                <td class="border border-gray-300 px-2 py-2 text-gray-900"></td>
                {{-- Not sourced from the database — intentionally blank --}}
                <td class="border border-gray-300 px-2 py-2 text-gray-900"></td>
                {{-- Not sourced from the database — intentionally blank --}}
                <td class="border border-gray-300 px-2 py-2 text-gray-900"></td>
                <td class="border border-gray-300 px-2 py-2 text-right text-gray-900">₱ {{ number_format((float) $projectItem->unit_cost, 2) }}</td>
                <td class="border border-gray-300 px-2 py-2 text-right text-gray-900">{{ $projectItem->awarded_unit_price !== null ? '₱ ' . number_format((float) $projectItem->awarded_unit_price, 2) : '—' }}</td>
                <td class="border border-gray-300 px-2 py-2 text-right text-gray-900">₱ {{ number_format($lineTotal, 2) }}</td>
                {{-- Not sourced from the database — intentionally blank --}}
                <td class="border border-gray-300 px-2 py-2 text-center text-gray-900"></td>
                {{-- Not sourced from the database — intentionally blank --}}
                <td class="border border-gray-300 px-2 py-2 text-center text-gray-900"></td>
            </tr>
        @empty
            <tr>
                <td colspan="14" class="border border-gray-300 px-3 py-6 text-center text-gray-500">No items recorded for this project.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<p class="mt-4 text-[10px] text-gray-400">Generated {{ now()->format('F j, Y g:i A') }} · Purchasing</p>
