@extends('layouts.app')

@section('page-title', 'PROJECTS')
@section('title', 'Project Details - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="flex h-full min-h-0 flex-col">

    @if (session('status'))
        <div class="mb-4 flex shrink-0 items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @php
        $totalItems = $project->totalItemCount();
        $fulfilledItems = $project->fulfilledItemCount();
        $status = $project->statusLabel();
        $statusColor = $project->statusColor();

        $statusPillClasses = [
            'gray' => 'bg-gray-100 text-gray-700',
            'blue' => 'bg-blue-100 text-blue-700',
            'green' => 'bg-green-100 text-green-700',
        ];

        $modeLabels = [
            'shopping' => 'Shopping',
            'small_value' => 'Small value',
            'competitive_bidding' => 'Competitive bidding',
            'direct_contracting' => 'Direct contracting',
        ];

        $modeLabel = $modeLabels[$project->mode_of_procurement]
            ?? ucfirst(str_replace('_', ' ', $project->mode_of_procurement));

        $deliveryPeriod = $project->delivery_days !== null
            ? $project->delivery_days.' days'
            : '—';

        if ($project->delivery_due_date !== null) {
            $deliveryPeriod .= ' (due '.$project->delivery_due_date->format('M j, Y').')';
        }

        $progressLabel = $totalItems > 0
            ? $fulfilledItems.' / '.$totalItems.' items fulfilled'
            : 'No items recorded yet';
    @endphp

    {{-- Project Header --}}
    <div class="flex shrink-0 items-start justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ $project->title }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $progressLabel }}</p>
        </div>
        <div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusPillClasses[$statusColor] }}">
                {{ $status }}
            </span>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="grid min-h-0 flex-1 grid-cols-1 gap-6 lg:grid-cols-4">
        {{-- Project Details (Left Column) — static --}}
        <div class="min-h-0 lg:col-span-1">
            <div class="bg-white rounded-lg border border-gray-200 h-full">
                <div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Entity</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project->procuringEntity?->name ?? '—' }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Reference no.</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project->reference_no }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mode of procurement</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $modeLabel }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Amount awarded</label>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $project->amount_awarded !== null ? '₱ ' . number_format((float) $project->amount_awarded, 2) : '—' }}
                        </p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Date awarded</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project->date_awarded?->format('F j, Y') ?? '—' }}</p>
                    </div>
                    <div class="p-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Delivery period</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $deliveryPeriod }}</p>
                    </div>
                    <div class="p-4 border-t border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                        <p class="text-sm text-gray-900">{{ $project->notes ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Table (Right Column) — scrolls vertically --}}
        <div class="min-h-0 lg:col-span-3">
            <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div class="min-h-[240px] flex-1 overflow-y-auto overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 border-b border-gray-300 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Item description</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700">Quantity</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Unit</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700">Unit cost</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700">Quoted price</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700">Total</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($project->items as $projectItem)
                                @php
                                    $unitPrice = $projectItem->awarded_unit_price ?? $projectItem->unit_cost;
                                    $lineTotal = $projectItem->quantity * (float) $unitPrice;
                                    $fulfillmentStatus = $projectItem->fulfillment_status ?: 'pending';

                                    $itemStatus = ucfirst(str_replace('_', ' ', $fulfillmentStatus));
                                    $itemStatusClasses = match ($fulfillmentStatus) {
                                        'fulfilled' => 'bg-green-100 text-green-700',
                                        'waived' => 'bg-orange-100 text-orange-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $projectItem->item?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $projectItem->quantity }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $projectItem->item?->unit ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">₱ {{ number_format((float) $projectItem->unit_cost, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                        {{ $projectItem->awarded_unit_price !== null ? '₱ ' . number_format((float) $projectItem->awarded_unit_price, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">₱ {{ number_format($lineTotal, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $itemStatusClasses }}">
                                            {{ $itemStatus }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">
                                        No items recorded for this project yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="mt-4 flex shrink-0 items-center justify-end gap-3">
        <form method="POST" action="{{ route('projects.destroy', ['project' => $project->id]) }}"
              onsubmit="return confirm('Delete this project? This action cannot be undone from the list.');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete project
            </button>
        </form>
        <a href="{{ route('projects.edit', ['project' => $project->id]) }}"
           class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit project
        </a>
    </div>
    </div><!-- /flex column -->

@endsection
