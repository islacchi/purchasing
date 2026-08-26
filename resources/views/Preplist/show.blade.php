@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', 'Prep List - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // Dummy data for frontend development.
        // TODO: Replace with real Eloquent queries once the preplist DB tables exist,
        // e.g. $preplist = Preplist::with(['projects', 'items.sources'])->find($id);

        $preplist = [
            'name'            => 'July Region VII batch',
            'reference_no'    => '#2026-0090',
            'created_by'      => 'J. Santos',
            'projects_count'  => 2,
            'date_created'    => 'July 12, 2026',
            'total_items'     => 2,
        ];

        // TODO: Replace with Preplist::find($id)->projects->pluck('name', 'reference_no')
        $sourceProjects = [
            ['name' => 'Pharma supplies Q3',  'reference' => 'RFQ-2026-0042'],
            ['name' => 'Laboratory reagents', 'reference' => 'RFQ-2026-0038'],
        ];

        // TODO: Replace with Preplist::find($id)->items and group their line items per source project.
        $itemGroups = [
            [
                'name'    => "AMOXICILLIN 500, Box of 100's",
                'total'   => '100 BOXES TOTAL',
                'sources' => [
                    ['project' => 'Pharma supplies Q3',  'qty' => '50 Box'],
                    ['project' => 'Laboratory reagents', 'qty' => '50 Box'],
                ],
            ],
            [
                'name'    => 'Surgical gloves, box of 50 pairs',
                'total'   => '40 BOXES TOTAL',
                'sources' => [
                    ['project' => 'Pharma supplies Q3', 'qty' => '40 Box'],
                ],
            ],
        ];
    @endphp

    {{-- Top row: heading + edit button --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ $preplist['name'] }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $preplist['reference_no'] }}</p>
        </div>
        <button class="flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit prep list
        </button>
    </div>

    {{-- Main two-column layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-6">

        {{-- LEFT COLUMN --}}
        <div class="space-y-6">
            {{-- Info boxes (2x2 grid) --}}
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3">
                    <p class="text-[11px] italic text-gray-400">Created by</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ $preplist['created_by'] }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3">
                    <p class="text-[11px] italic text-gray-400">Projects</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ $preplist['projects_count'] }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3">
                    <p class="text-[11px] italic text-gray-400">Date Created</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ $preplist['date_created'] }}</p>
                </div>
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-3">
                    <p class="text-[11px] italic text-gray-400">Total Items</p>
                    <p class="text-sm font-bold text-gray-900 mt-1">{{ $preplist['total_items'] }}</p>
                </div>
            </div>

            {{-- Notes --}}
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="p-4">
                    <label for="notes" class="block text-xs font-medium text-gray-500 mb-1">Notes:</label>
                    <textarea id="notes" rows="4" placeholder="Add notes here..."
                              class="w-full px-3 py-2 rounded-md border border-gray-200 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 resize-none"></textarea>
                </div>
            </div>

            {{-- Source projects --}}
            <div>
                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Source Projects</p>
                <div class="space-y-3">
                    @foreach($sourceProjects as $project)
                        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                            <p class="text-sm font-bold text-gray-900">{{ $project['name'] }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $project['reference'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN --}}
        <div>
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">Items</p>
            <div class="space-y-4">
                @foreach($itemGroups as $group)
                    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                        {{-- Item header --}}
                        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                            <p class="font-bold text-gray-900">{{ $group['name'] }}</p>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                {{ $group['total'] }}
                            </span>
                        </div>
                        {{-- Source rows --}}
                        <div class="divide-y divide-gray-100">
                            @foreach($group['sources'] as $source)
                                <div class="flex items-center justify-between px-5 py-3">
                                    <p class="text-sm text-gray-500">{{ $source['project'] }}</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $source['qty'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Bottom action buttons --}}
    <div class="flex items-center justify-end gap-3 mt-8">
        <button class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Delete prep list
        </button>
        <button class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Export to Excel
        </button>
        <button class="flex items-center gap-2 px-4 py-2 rounded-md bg-[#2a7a94] text-white text-sm font-medium hover:bg-[#236b80] transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
            </svg>
            Generate prep list for supplier
        </button>
    </div>

@endsection