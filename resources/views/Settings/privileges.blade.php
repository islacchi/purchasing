@extends('layouts.app')

@section('page-title', 'USER PRIVILEGE')
@section('title', 'User Privilege - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        $totalUsers = $users->count();
        $administratorCount = $users->filter(fn ($u) => $u->role?->name === 'Administrator')->count();
        $moduleCount = count(\App\Support\ModuleAccess::MODULES);
    @endphp

    <div class="flex h-full min-h-0 flex-col">
    {{-- Page heading + primary action --}}
    <div class="mb-4 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">User Accounts</h2>
            <p class="mt-1 text-xs text-gray-500">Create accounts and control what each user can access.</p>
        </div>
        <a href="{{ route('settings.privileges.create') }}"
           class="flex h-9 shrink-0 items-center gap-2 rounded-lg bg-[#2a7a94] px-4 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14M5 12h14"/>
            </svg>
            <span>Add user</span>
        </a>
    </div>

    {{-- Error messages --}}
    @if (session('error') || $errors->any())
        <div class="mb-4 flex shrink-0 items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
            </svg>
            <span>{{ session('error') ?? $errors->first() }}</span>
        </div>
    @endif

    {{-- Quick stats — all derived from the users table the page already loads --}}
    <div class="mb-4 flex shrink-0 flex-wrap items-stretch gap-3">
        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white py-2.5 pl-3 pr-5 shadow-sm">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1m12-11a4 4 0 1 1-5.7 0M20 19v-1a4 4 0 0 0-3-3.87m0-9.26A4 4 0 0 1 17 11"/>
                </svg>
            </span>
            <span>
                <span class="block text-[8.5px] font-extrabold uppercase tracking-[0.09em] text-gray-500">Users</span>
                <span class="block text-xl font-extrabold leading-none text-gray-900">{{ $totalUsers }}</span>
            </span>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white py-2.5 pl-3 pr-5 shadow-sm">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-700">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m5.5-6.25 2.75 5.5-2.75 5.5m-9.5 1.75L4.5 10l2-4 5.5 9.5z"/>
                </svg>
            </span>
            <span>
                <span class="block text-[8.5px] font-extrabold uppercase tracking-[0.09em] text-gray-500">Administrators</span>
                <span class="block text-xl font-extrabold leading-none text-gray-900">{{ $administratorCount }}</span>
            </span>
        </div>
        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white py-2.5 pl-3 pr-5 shadow-sm">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-600">
                <svg class="h-[18px] w-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <rect x="4" y="4" width="16" height="16" rx="2"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 10h6M9 14h6M10 18h4"/>
                </svg>
            </span>
            <span>
                <span class="block text-[8.5px] font-extrabold uppercase tracking-[0.09em] text-gray-500">Modules</span>
                <span class="block text-xl font-extrabold leading-none text-gray-900">{{ $moduleCount }}</span>
            </span>
        </div>
    </div>

    <div class="grid items-start gap-4 grid-cols-1 xl:grid-cols-[minmax(0,1fr)_420px]">
    {{-- Users card --}}
    <div class="flex min-w-0 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-2.5">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Users</p>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-600">{{ $totalUsers }} account{{ $totalUsers === 1 ? '' : 's' }}</span>
        </div>

        {{-- Search toolbar --}}
        <div class="shrink-0 border-b border-gray-100 px-4 py-2.5">
            <form id="user-search-form" method="GET" action="{{ route('settings.privileges') }}" data-live-search-url="{{ route('settings.privileges.search') }}" class="flex items-center gap-3">
                <div class="relative h-8 w-full max-w-md">
                    <button type="submit" title="Search" aria-label="Search"
                            class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 transition-colors hover:text-[#2a7a94]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                        </svg>
                    </button>
                    <input id="user-search-input" type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search users..." autocomplete="off"
                           aria-controls="user-table-body"
                           class="h-full w-full rounded-lg border border-gray-200 bg-white pl-10 pr-3 text-xs text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                </div>
                <a id="user-search-clear" href="{{ route('settings.privileges') }}" class="{{ empty($search) ? 'hidden ' : '' }}text-xs font-medium text-gray-500 hover:text-gray-700">
                    Clear
                </a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <colgroup>
                    <col class="w-[34%]">
                    <col class="w-[22%]">
                    <col class="w-[17%]">
                    <col class="w-[19%]">
                    <col class="w-[8%]">
                </colgroup>
                <thead class="sticky top-0 z-10 bg-[#0e5266]">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Name</th>
                        <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Role</th>
                        <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Status</th>
                        <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Date added</th>
                        <th scope="col" class="px-4 py-2.5 text-center text-[10px] font-semibold uppercase tracking-wider text-white">Action</th>
                    </tr>
                </thead>
                <tbody id="user-table-body" class="divide-y divide-gray-100 bg-white">
                    @include('Settings.privileges_user_rows', ['users' => $users, 'search' => $search ?? ''])
                </tbody>
            </table>
        </div>

        <div class="mt-auto flex shrink-0 flex-wrap items-center justify-between gap-2 border-t border-gray-200 px-4 py-2 text-[11px] text-gray-500">
            <span>Showing <span class="font-semibold text-gray-700">{{ $totalUsers }}</span> of <span class="font-semibold text-gray-700">{{ $totalUsers }}</span> users · live search</span>
            <span>Click a row to open the account editor</span>
        </div>
    </div>

    {{-- Role module access --}}
    <form method="POST" action="{{ route('settings.privileges.access.update') }}" id="role-access"
          class="flex min-w-0 flex-col self-start overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        @csrf
        <div class="shrink-0 border-b border-gray-200 px-4 py-3">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Role module access</p>
            <p class="mt-1.5 text-[11px] leading-snug text-gray-500">
                Administrator is always full access. Set each module to View or Manage — View reads the module; Manage also creates, edits and deletes.
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <colgroup>
                    <col>
                    @foreach ($matrixRoles as $role)
                        <col class="w-[34%]">
                    @endforeach
                </colgroup>
                <thead class="bg-[#0e5266]">
                    <tr>
                        <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Module</th>
                        @foreach ($matrixRoles as $role)
                            <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">{{ strtoupper($role->name) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach (\App\Support\ModuleAccess::MODULES as $moduleKey => $moduleLabel)
                        <tr class="odd:bg-white even:bg-[#f2f7f8] transition-colors hover:bg-[#e4edf0]">
                            <td class="px-4 py-2.5 text-xs font-semibold text-gray-900">{{ $moduleLabel }}</td>
                            @foreach ($matrixRoles as $role)
                                @php
                                    $current = $moduleAccess->get($role->id)?->firstWhere('module', $moduleKey)?->level ?? 'view';
                                @endphp
                                <td class="px-4 py-2">
                                    <select name="access[{{ $role->id }}][{{ $moduleKey }}]"
                                            aria-label="{{ $moduleLabel }} access for {{ $role->name }}"
                                            class="h-8 w-full rounded-md border border-gray-300 px-2 text-xs text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-teal-300">
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
        <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 bg-[#fbfdfe] px-4 py-2.5">
            <p class="min-w-0 flex-1 text-[10px] leading-snug text-gray-400">
                Changes apply immediately to every account of that role. Settings stays administrator-only and cannot be changed here.
            </p>
            <button type="submit"
                    class="flex h-9 shrink-0 items-center gap-2 rounded-md bg-[#2a7a94] px-4 text-xs font-semibold text-white transition-colors hover:bg-[#236b80]">
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

    {{-- Back to settings — pinned to the very bottom of the content area --}}
    <div class="mt-auto flex shrink-0 items-center justify-start pt-4">
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
