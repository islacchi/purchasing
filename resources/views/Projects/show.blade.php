@extends('layouts.app')

@section('page-title', 'PROJECTS')
@section('title', 'Project Details - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // Dummy data for frontend development
        $project = [
            'name' => 'PROJECT A - Pharma supplies Q3',
            'entity' => 'DOH REGION V',
            'reference_no' => 'RFQ-2026-0042',
            'mode_of_procurement' => 'Small value',
            'amount_awarded' => 482000.00,
            'date_awarded' => 'July 3, 2026',
            'delivery_period' => 'Aug 1 - Aug 15, 2026',
            'status' => 'In-progress',
            'progress' => '3/5 items fulfilled',
        ];

        $items = [
            [
                'description' => 'Mefenamic Capsule - 500mg',
                'quantity' => 50,
                'unit' => 'Box',
                'unit_cost' => 320.00,
                'quoted_price' => 305.00,
                'total' => 15250.00,
                'status' => 'Fulfilled',
                'status_color' => 'green',
            ],
            [
                'description' => 'Amoxicillin 250mg suspension',
                'quantity' => 50,
                'unit' => 'Box',
                'unit_cost' => 320.00,
                'quoted_price' => 305.00,
                'total' => 15250.00,
                'status' => 'Pending',
                'status_color' => 'gray',
            ],
            [
                'description' => 'Amoxicillin 250mg suspension',
                'quantity' => 50,
                'unit' => 'Box',
                'unit_cost' => 320.00,
                'quoted_price' => 305.00,
                'total' => 15250.00,
                'status' => 'Waived',
                'status_color' => 'orange',
            ],
        ];
    @endphp

    {{-- Project Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ $project['name'] }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $project['progress'] }}</p>
        </div>
        <div>
            @if($project['status'] === 'In-progress')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                    {{ $project['status'] }}
                </span>
            @endif
        </div>
    </div>

    {{-- Main Content --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Project Details (Left Column) --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg border border-gray-200">
                <div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Entity</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project['entity'] }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Reference no.</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project['reference_no'] }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mode of procurement</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project['mode_of_procurement'] }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Amount awarded</label>
                        <p class="text-sm font-semibold text-gray-900">₱ {{ number_format($project['amount_awarded'], 2) }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Date awarded</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project['date_awarded'] }}</p>
                    </div>
                    <div class="p-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Delivery period</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project['delivery_period'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Table (Right Column) --}}
        <div class="lg:col-span-3">
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 border-b border-gray-300">
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
                            @foreach ($items as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item['description'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $item['quantity'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $item['unit'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">₱ {{ number_format($item['unit_cost'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">₱ {{ number_format($item['quoted_price'], 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">₱ {{ number_format($item['total'], 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if($item['status_color'] === 'green')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                                {{ $item['status'] }}
                                            </span>
                                        @elseif($item['status_color'] === 'gray')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                {{ $item['status'] }}
                                            </span>
                                        @elseif($item['status_color'] === 'orange')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-700">
                                                {{ $item['status'] }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex items-center justify-end gap-3 mt-6">
        <a href="{{ route('projects') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Delete project
        </a>
        <a href="{{ route('projects.edit', ['id' => 1]) }}"
           class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit project
        </a>
        <a href="{{ route('preplist') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Add items to Prep List
        </a>
    </div>

@endsection