@extends('layouts.app')

@section('page-title', 'PROJECTS')
@section('title', 'Projects - Purchasing')

@section('header-actions')
@endsection

    @section('content')
    {{-- Flex column: the toolbar stays fixed while the content holder scrolls --}}
    <div class="flex h-full min-h-0 flex-col">
        {{-- Search, view options and actions bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4 flex-1 min-w-0">
            <form method="GET" action="{{ route('projects') }}" class="relative max-w-xl w-full">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                    </svg>
                </span>
                <input type="text" name="q" value="{{ $search ?? '' }}" placeholder="Search by project name / title..."
                       class="w-full pl-10 pr-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
            </form>

            {{-- View options dropdown: Content (default), Details, List --}}
            <div id="projectsViewWrap" class="relative">
                <button type="button" id="projectsViewBtn" aria-haspopup="true" aria-expanded="false" aria-controls="projectsViewMenu"
                        class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <span id="projectsViewIcon" class="flex items-center text-gray-400" aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="6" rx="1.5" stroke-width="1.8"/>
                            <rect x="3" y="14" width="18" height="6" rx="1.5" stroke-width="1.8"/>
                        </svg>
                    </span>
                    <span id="projectsViewLabel">Content</span>
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m19 9-7 7-7-7"/>
                    </svg>
                </button>
                <div id="projectsViewMenu" role="menu" aria-labelledby="projectsViewBtn"
                     class="hidden absolute left-0 top-full mt-2 z-10 w-48 overflow-hidden rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                    <button type="button" role="menuitemradio" aria-checked="true" data-view="content"
                            class="projects-view-item flex w-full items-center gap-2.5 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="6" rx="1.5" stroke-width="1.8"/>
                            <rect x="3" y="14" width="18" height="6" rx="1.5" stroke-width="1.8"/>
                        </svg>
                        <span class="flex-1 text-left">Content</span>
                        <svg class="projects-view-check h-4 w-4 text-[#2a7a94]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                        </svg>
                    </button>
                    <button type="button" role="menuitemradio" aria-checked="false" data-view="details"
                            class="projects-view-item flex w-full items-center gap-2.5 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h10"/>
                        </svg>
                        <span class="flex-1 text-left">Details</span>
                        <svg class="projects-view-check h-4 w-4 text-[#2a7a94] hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                        </svg>
                    </button>
                    <button type="button" role="menuitemradio" aria-checked="false" data-view="list"
                            class="projects-view-item flex w-full items-center gap-2.5 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                        </svg>
                        <span class="flex-1 text-left">List</span>
                        <svg class="projects-view-check h-4 w-4 text-[#2a7a94] hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        @if (auth()->user()?->canModule('projects', 'manage'))
            <a href="{{ route('projects.create') }}" class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="font-medium">Add project</span>
            </a>
        @else
            <span class="flex cursor-not-allowed items-center gap-2 px-4 py-2 rounded-lg bg-gray-300 text-sm font-medium text-gray-500"
                  title="Your role does not have access to that module.">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="font-medium">Add project</span>
            </span>
        @endif
    </div>

    @php
        $statusPillClasses = [
            'gray'  => 'bg-gray-100 text-gray-700',
            'blue'  => 'bg-blue-100 text-blue-700',
            'green' => 'bg-green-100 text-green-700',
        ];
    @endphp

    {{-- Scrollable content holder. Sits directly on the page background; each view panel fills the space, and the List table stretches to it. --}}
    <div class="flex-1 min-h-0 overflow-y-auto flex flex-col">

    {{-- ============================== CONTENT VIEW (default) ============================== --}}
    <div id="view-content" class="space-y-4">
        @forelse($projects as $project)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-4">
                    <a href="{{ route('projects.show', ['project' => $project->id]) }}" class="flex items-start gap-4 flex-1 min-w-0">
                        <div class="flex-shrink-0 w-12 h-12 bg-[#0e5266] rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="text-lg font-semibold text-gray-900 truncate">
                                {{ $project->title }}
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ $project->procuringEntity?->name ?? 'No entity' }} - {{ $project->reference_no }}
                            </p>
                        </div>
                    </a>

                    <div class="flex-shrink-0 flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusPillClasses[$project->statusColor()] }}">
                            {{ $project->statusLabel() }}
                        </span>

                        @if (auth()->user()?->canModule('projects', 'manage'))
                            <a href="{{ route('projects.edit', ['project' => $project->id]) }}"
                               class="p-2 text-gray-400 hover:text-[#2a7a94] hover:bg-gray-100 rounded-lg transition-colors"
                               title="Edit project">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        @else
                            <span class="flex p-2 text-gray-300 cursor-not-allowed rounded-lg"
                                  title="Your role does not have access to that module.">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </span>
                        @endif
                    </div>
                </div>

                <div class="mt-4 ml-16">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>{{ $project->fulfilledItemCount() }} / {{ $project->totalItemCount() }} items fulfilled</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-[#2a7a94] h-2 rounded-full" style="width: {{ $project->progressPercentage() }}%"></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <h3 class="text-base font-semibold text-gray-900">No projects yet</h3>
                <p class="mt-1 text-sm text-gray-500">Create a project to start tracking its procurement details.</p>
                @if (auth()->user()?->canModule('projects', 'manage'))
                    <a href="{{ route('projects.create') }}" class="mt-4 inline-flex items-center gap-2 rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white hover:bg-[#236b80]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add project
                    </a>
                @else
                    <span class="mt-4 inline-flex cursor-not-allowed items-center gap-2 rounded-md bg-gray-300 px-4 py-2 text-sm font-medium text-gray-500"
                          title="Your role does not have access to that module.">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Add project
                    </span>
                @endif
            </div>
        @endforelse
    </div>

    {{-- ============================== DETAILS VIEW ============================== --}}
    <div id="view-details" class="hidden space-y-5">
        @forelse($projects as $project)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    {{-- Title block --}}
                    <div class="flex items-start gap-4 min-w-0">
                        <a href="{{ route('projects.show', ['project' => $project->id]) }}" class="flex items-start gap-4 min-w-0">
                            <div class="flex-shrink-0 w-12 h-12 bg-[#0e5266] rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $project->title }}</h3>
                                <p class="text-sm text-gray-500 mt-0.5">{{ $project->procuringEntity?->name ?? 'No entity' }} - {{ $project->reference_no }}</p>
                            </div>
                        </a>
                    </div>

                    {{-- Status + edit --}}
                    <div class="flex flex-shrink-0 items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusPillClasses[$project->statusColor()] }}">
                            {{ $project->statusLabel() }}
                        </span>
                        @if (auth()->user()?->canModule('projects', 'manage'))
                            <a href="{{ route('projects.edit', ['project' => $project->id]) }}"
                               class="p-2 text-gray-400 hover:text-[#2a7a94] hover:bg-gray-100 rounded-lg transition-colors"
                               title="Edit project">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        @else
                            <span class="flex p-2 text-gray-300 cursor-not-allowed rounded-lg"
                                  title="Your role does not have access to that module.">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Metadata grid --}}
                <dl class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4 border-t border-gray-100 pt-4">
                    <div>
                        <dt class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Entity</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-800 truncate">{{ $project->procuringEntity?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Reference no.</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-800 truncate">{{ $project->reference_no }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Amount awarded</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-800">
                            {{ $project->amount_awarded !== null ? '₱ ' . number_format((float) $project->amount_awarded, 2) : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Date awarded</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-800">{{ $project->date_awarded?->format('F j, Y') ?? '—' }}</dd>
                    </div>
                </dl>

                @if(filled($project->notes))
                    <p class="mt-4 text-sm text-gray-500 truncate" title="{{ $project->notes }}">{{ $project->notes }}</p>
                @endif

                {{-- Progress --}}
                <div class="mt-4">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>{{ $project->fulfilledItemCount() }} / {{ $project->totalItemCount() }} items fulfilled</span>
                        <span>{{ round($project->progressPercentage()) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-[#2a7a94] h-2 rounded-full" style="width: {{ $project->progressPercentage() }}%"></div>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <h3 class="text-base font-semibold text-gray-900">No projects yet</h3>
                <p class="mt-1 text-sm text-gray-500">Create a project to start tracking its procurement details.</p>
            </div>
        @endforelse
    </div>

    {{-- ============================== LIST VIEW ============================== --}}
    <div id="view-list" class="hidden flex-1 min-h-0">
        <div class="h-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference no.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount awarded</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($projects as $project)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer" data-href="{{ route('projects.show', ['project' => $project->id]) }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900">{{ $project->title }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $project->reference_no }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $project->procuringEntity?->name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusPillClasses[$project->statusColor()] }}">
                                    {{ $project->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-700">{{ $project->fulfilledItemCount() }}/{{ $project->totalItemCount() }}</span>
                                    <div class="w-24 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-[#2a7a94] h-1.5 rounded-full" style="width: {{ $project->progressPercentage() }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                {{ $project->amount_awarded !== null ? '₱ ' . number_format((float) $project->amount_awarded, 2) : '—' }}
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if (auth()->user()?->canModule('projects', 'manage'))
                                    <a href="{{ route('projects.edit', ['project' => $project->id]) }}"
                                       class="inline-flex p-2 text-gray-400 hover:text-[#2a7a94] hover:bg-gray-100 rounded-lg transition-colors"
                                       title="Edit project">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                @else
                                    <span class="flex p-2 text-gray-300 cursor-not-allowed rounded-lg"
                                          title="Your role does not have access to that module.">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                No projects yet. Use “Add project” to create the first one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div><!-- /scrollable content holder -->
    </div><!-- /flex column -->

    <script>
        // View options dropdown (Content / Details / List) — persist the choice in localStorage.
        (function () {
            var STORAGE_KEY = 'projectsView';
            var VIEWS = ['content', 'details', 'list'];
            var LABELS = { content: 'Content', details: 'Details', list: 'List' };
            var ICONS = {
                content: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="6" rx="1.5" stroke-width="1.8"/><rect x="3" y="14" width="18" height="6" rx="1.5" stroke-width="1.8"/></svg>',
                details: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h10"/></svg>',
                list: '<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>'
            };

            var wrap = document.getElementById('projectsViewWrap');
            var btn = document.getElementById('projectsViewBtn');
            var menu = document.getElementById('projectsViewMenu');
            var label = document.getElementById('projectsViewLabel');
            var icon = document.getElementById('projectsViewIcon');
            var items = document.querySelectorAll('.projects-view-item');

            function closeMenu() {
                menu.classList.add('hidden');
                btn.setAttribute('aria-expanded', 'false');
            }

            function applyView(view) {
                VIEWS.forEach(function (v) {
                    var panel = document.getElementById('view-' + v);
                    if (panel) panel.classList.toggle('hidden', v !== view);
                });
                items.forEach(function (item) {
                    var active = item.dataset.view === view;
                    item.setAttribute('aria-checked', active ? 'true' : 'false');
                    var check = item.querySelector('.projects-view-check');
                    if (check) check.classList.toggle('hidden', !active);
                });
                icon.innerHTML = ICONS[view];
                label.textContent = LABELS[view];
            }

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var isHidden = menu.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', String(!isHidden));
            });

            items.forEach(function (item) {
                item.addEventListener('click', function (e) {
                    e.stopPropagation();
                    var view = item.dataset.view;
                    localStorage.setItem(STORAGE_KEY, view);
                    applyView(view);
                    closeMenu();
                });
            });

            document.addEventListener('click', function (e) {
                if (!wrap.contains(e.target)) closeMenu();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeMenu();
            });

            // Row click navigates to the project detail page unless the click
            // targeted the edit action (or any other a/button inside it).
            document.querySelectorAll('#view-list tr[data-href]').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('a, button')) return;
                    window.location.href = row.dataset.href;
                });
            });

            var saved = localStorage.getItem(STORAGE_KEY);
            if (saved && VIEWS.indexOf(saved) !== -1) {
                applyView(saved);
            }
        })();
    </script>
@endsection
