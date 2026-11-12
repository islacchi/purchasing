@extends('layouts.app')

@section('page-title', 'USER PRIVILEGE')
@section('title', 'User Privilege - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="flex h-full min-h-0 flex-col">
    {{-- Page heading + primary action --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">User Accounts</h2>
            <p class="text-sm text-gray-500">
                Create accounts and control what each user can access.
            </p>
        </div>
        <a href="{{ route('settings.privileges.create') }}"
           class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="font-medium">Add user</span>
        </a>
    </div>

    {{-- Error messages --}}
    @if (session('error') || $errors->any())
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
            </svg>
            <span>{{ session('error') ?? $errors->first() }}</span>
        </div>
    @endif

    <div class="grid gap-6 grid-cols-1 xl:grid-cols-3">
    {{-- Users table --}}
    <div class="xl:col-span-2 space-y-4">
            {{-- Search toolbar --}}
            <form id="user-search-form" method="GET" action="{{ route('settings.privileges') }}" data-live-search-url="{{ route('settings.privileges.search') }}" class="flex items-center gap-3">
                <div class="relative max-w-md w-full">
                    <button type="submit" title="Search" aria-label="Search"
                            class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 transition-colors hover:text-[#2a7a94]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                        </svg>
                    </button>
                    <input id="user-search-input" type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search users..." autocomplete="off"
                           aria-controls="user-table-body"
                           class="w-full pl-10 pr-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 border border-gray-200">
                </div>
                <a id="user-search-clear" href="{{ route('settings.privileges') }}" class="{{ empty($search) ? 'hidden ' : '' }}text-sm font-medium text-gray-500 hover:text-gray-700">
                    Clear
                </a>
            </form>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date added</th>
                            <th class="px-6 py-3 w-14 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body" class="divide-y divide-gray-100 bg-white">
                        @include('Settings.privileges_user_rows', ['users' => $users, 'search' => $search ?? ''])
                    </tbody>
                </table>
            </div>
    </div>

    {{-- Role module access matrix --}}
    <form method="POST" action="{{ route('settings.privileges.access.update') }}" id="role-access"
          class="mt-6 mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        @csrf
        <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-6 py-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Role module access</h2>
                <p class="mt-1 text-xs text-gray-500">
                    Administrator is always full access. Set each module to View or Manage — View reads the module; Manage also creates, edits and deletes.
                </p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Module</th>
                        @foreach ($matrixRoles as $role)
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach (\App\Support\ModuleAccess::MODULES as $moduleKey => $moduleLabel)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3.5 text-sm font-medium text-gray-900">{{ $moduleLabel }}</td>
                            @foreach ($matrixRoles as $role)
                                @php
                                    $current = $moduleAccess->get($role->id)?->firstWhere('module', $moduleKey)?->level ?? 'view';
                                @endphp
                                <td class="px-6 py-3">
                                    <select name="access[{{ $role->id }}][{{ $moduleKey }}]"
                                            aria-label="{{ $moduleLabel }} access for {{ $role->name }}"
                                            class="w-32 rounded-md border border-gray-300 px-2 py-1.5 text-sm text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-teal-300">
                                            <option value="view" {{ $current === 'view' ? 'selected' : '' }}>View</option>
                                        <option value="manage" {{ $current === 'manage' ? 'selected' : '' }}>Manage</option>
                                    </select>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-100 px-6 py-3">
            <p class="min-w-0 flex-1 text-[11px] leading-snug text-gray-400">
                Changes apply immediately to every account of that role. Settings stays administrator-only and cannot be changed here.
            </p>
            <button type="submit"
                    class="flex shrink-0 items-center gap-2 rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white hover:bg-[#236b80] transition-colors">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3m-6 0V3h6v4m-6 0h6"/>
                </svg>
                Save role access
            </button>
        </div>
    </form>
    </div>

    {{-- Bottom spacer so dropdowns on the last rows have room and are not clipped --}}
    <div class="pb-12"></div>

    {{-- Back to settings landing page — pinned to the very bottom of the content area --}}
    <div class="mt-auto flex shrink-0 items-center justify-start pt-6">
        <a href="{{ route('settings') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to settings
        </a>
    </div>

    </div><!-- /flex column -->

    <script>
        // Live user search and three-dot action dropdowns (plain JS, matching
        // the rest of the app — no Alpine/Livewire).
        (function () {
            'use strict';

            function resetDropdown(menu) {
                menu.classList.add('hidden');
                menu.classList.remove('bottom-full', 'mb-2');
                menu.classList.add('top-full', 'mt-2');
            }

            function resetDropdowns(exceptMenu) {
                document.querySelectorAll('.dropdown-menu').forEach(function (menu) {
                    if (menu !== exceptMenu) {
                        resetDropdown(menu);
                    }
                });
            }

            document.addEventListener('click', function (e) {
                var trigger = e.target.closest('.dropdown-trigger');

                if (trigger) {
                    var menu = trigger.nextElementSibling;
                    var isOpen = !menu.classList.contains('hidden');

                    resetDropdowns();

                    if (!isOpen) {
                        menu.classList.remove('hidden');
                        // Open upward when the menu would be clipped by the
                        // viewport or the table's overflow container.
                        var rect = menu.getBoundingClientRect();
                        var scrollBox = menu.closest('.overflow-x-auto');
                        var clipBottom = Math.min(window.innerHeight, scrollBox ? scrollBox.getBoundingClientRect().bottom : Infinity);
                        var opensUp = rect.bottom + 8 > clipBottom;
                        menu.classList.toggle('bottom-full', opensUp);
                        menu.classList.toggle('mb-2', opensUp);
                        menu.classList.toggle('top-full', !opensUp);
                        menu.classList.toggle('mt-2', !opensUp);
                    }

                    return;
                }

                if (!e.target.closest('.dropdown-menu')) {
                    resetDropdowns();
                }

                var row = e.target.closest('tr[data-href]');
                if (row && !e.target.closest('.dropdown-trigger') && !e.target.closest('.dropdown-menu')) {
                    window.location.href = row.dataset.href;
                }
            });

            var searchForm = document.getElementById('user-search-form');
            var searchInput = document.getElementById('user-search-input');
            var clearSearch = document.getElementById('user-search-clear');
            var tableBody = document.getElementById('user-table-body');
            var liveSearchUrl = searchForm ? searchForm.dataset.liveSearchUrl : null;
            var searchTimer = null;
            var searchController = null;

            function updateClearVisibility() {
                if (!clearSearch || !searchInput) {
                    return;
                }

                clearSearch.classList.toggle('hidden', searchInput.value.trim() === '');
            }

            function fetchUsers(query) {
                if (!liveSearchUrl || !tableBody) {
                    return;
                }

                if (searchController) {
                    searchController.abort();
                }

                searchController = new AbortController();
                var requestUrl = new URL(liveSearchUrl, window.location.origin);
                requestUrl.searchParams.set('search', query);

                fetch(requestUrl.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: searchController.signal
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Unable to load matching users.');
                        }

                        return response.text();
                    })
                    .then(function (html) {
                        tableBody.innerHTML = html;
                        resetDropdowns();
                    })
                    .catch(function (error) {
                        if (error.name !== 'AbortError') {
                            tableBody.innerHTML = '<tr class="no-results-row"><td colspan="5" class="px-6 py-10 text-center text-sm text-red-600">Unable to load matching users. Please try again.</td></tr>';
                        }
                    });
            }

            function updateUrl(query) {
                var url = window.location.pathname;
                if (query !== '') {
                    url += '?search=' + encodeURIComponent(query);
                }

                window.history.replaceState(null, '', url);
            }

            function runSearch(query) {
                query = query.trim();
                updateClearVisibility();
                updateUrl(query);
                fetchUsers(query);
            }

            if (searchForm && searchInput) {
                searchInput.addEventListener('input', function () {
                    var query = searchInput.value;

                    updateClearVisibility();
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function () {
                        runSearch(query);
                    }, 150);
                });

                searchForm.addEventListener('submit', function (e) {
                    e.preventDefault();
                    clearTimeout(searchTimer);
                    runSearch(searchInput.value);
                });
            }

            if (clearSearch && searchInput) {
                clearSearch.addEventListener('click', function (e) {
                    e.preventDefault();
                    searchInput.value = '';
                    clearTimeout(searchTimer);
                    runSearch('');
                    searchInput.focus();
                });
            }
        })();
    </script>
@endsection
