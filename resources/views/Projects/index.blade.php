@extends('layouts.app')

@section('page-title', 'PROJECTS')
@section('title', 'Projects - Purchasing')

{{-- @TODO: When integrating database, replace hard-coded $projects with:
   $projects = App\Models\Project::with(['status', 'items'])->latest()->get();
--}}

{{-- @TODO: Pass projects from controller: return view('projects', compact('projects')); --}}

{{-- @TODO: When database is integrated, move filter and add-project back into header-actions --}}

@section('header-actions')
@endsection

@section('content')
    {{-- Search and actions bar --}}
    <div class="mb-6 flex items-center justify-between gap-4">
        <div class="flex items-center gap-4 flex-1">
            <div class="max-w-xl w-full">
                <input type="text" placeholder="Search by project name / title..."
                       class="w-full px-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
            </div>

            <button class="p-2 bg-[#2a7a94] text-white rounded hover:bg-[#236b80] transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
            </button>
        </div>

        <a href="{{ route('projects.create') }}" class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="font-medium">Add project</span>
        </a>
    </div>

    {{-- Hard-coded project data for frontend development --}}
    @php
        $projects = [
            [
                'name' => 'Purchase of pharma supplies',
                'code' => 'DOH Region VII - RF-2026-0042',
                'status' => 'Not started',
                'status_color' => 'gray',
                'progress' => 0,
                'total' => 6,
            ],
            [
                'name' => 'Purchase of pharma supplies',
                'code' => 'DOH Region VII - RF-2026-0042',
                'status' => 'In progress',
                'status_color' => 'blue',
                'progress' => 3,
                'total' => 6,
            ],
            [
                'name' => 'Purchase of pharma supplies',
                'code' => 'DOH Region VII - RF-2026-0042',
                'status' => 'In progress',
                'status_color' => 'green',
                'progress' => 2,
                'total' => 6,
            ],
        ];
    @endphp

    <div class="space-y-4">
        @foreach($projects as $project)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-[#0e5266] rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900 truncate">
                            {{ $project['name'] }}
                        </h3>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $project['code'] }}
                        </p>
                    </div>

                    <div class="flex-shrink-0">
                        @if($project['status_color'] === 'gray')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                {{ $project['status'] }}
                            </span>
                        @elseif($project['status_color'] === 'blue')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                {{ $project['status'] }}
                            </span>
                        @elseif($project['status_color'] === 'green')
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                {{ $project['status'] }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-4 ml-16">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>{{ $project['progress'] }} / {{ $project['total'] }} items fulfilled</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-[#2a7a94] h-2 rounded-full" style="width: {{ ($project['progress'] / $project['total']) * 100 }}%"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection