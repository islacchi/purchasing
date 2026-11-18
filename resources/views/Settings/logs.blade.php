@extends('layouts.app')

@section('page-title', 'ACTIVITY LOGS')
@section('title', 'Activity Logs - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="flex min-h-full flex-col lg:h-full lg:min-h-0">

    @php
        $filtersActive = collect([
            ($filterAction ?? 'all') !== 'all' ? $filterAction : null,
            ($filterModule ?? 'all') !== 'all' ? $filterModule : null,
            ($filterUser ?? 'all') !== 'all' ? $filterUser : null,
            ($filterFrom ?? '') !== '' ? $filterFrom : null,
            ($filterTo ?? '') !== '' ? $filterTo : null,
        ])->filter()->count();
    @endphp

        {{-- Page header --}}
        <div class="mb-4 flex shrink-0 flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Activity Logs</h2>
                <p class="mt-1 text-xs text-gray-500">User actions across all modules (create, edit, delete), newest first.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span class="inline-flex items-center rounded-full border border-blue-100 bg-blue-50 px-3 py-1 text-[11px] font-semibold text-blue-700">
                    {{ $logs->total() }} records
                </span>
                <button onclick="window.print()"
                        class="no-print flex h-9 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2-4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V3a2 2 0 0 0-2-2H3a2 2 0 0 0-2 2v18a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7h-6z"/>
                    </svg>
                    Print
                </button>
            </div>
        </div>

        {{-- Print styles --}}
        <style>
            @media print {
                .no-print,
                .no-print * {
                    display: none !important;
                }
                body {
                    background: white !important;
                }
                #sidebar,
                header,
                .fixed,
                .z-50 {
                    display: none !important;
                }
                #mainContent {
                    margin-left: 0 !important;
                    width: 100% !important;
                    padding: 0 !important;
                }
                .bg-gray-100 {
                    background: white !important;
                }
                table {
                    border-collapse: collapse !important;
                }
                th, td {
                    border: 1px solid #ccc !important;
                }
            }
        </style>

        {{-- Date-range notice (friendly warning, kept outside the toolbar) --}}
        @if (! empty($dateNotice))
            <div class="no-print mb-4 flex shrink-0 items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-800" role="alert">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.72 13.4A1 1 0 0 0 3.43 19h17.14a1 1 0 0 0 .86-1.74l-7.72-13.4a1 1 0 0 0-1.72 0z"/>
                </svg>
                <span>{{ $dateNotice }}</span>
            </div>
        @endif

        {{-- Search and filter bar — same pattern as the other modules --}}
        <div class="mb-4 flex shrink-0 flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('settings.logs') }}" class="flex w-full max-w-xl items-center gap-2">
                <input type="hidden" name="action" value="{{ $filterAction ?? 'all' }}">
                <input type="hidden" name="module" value="{{ $filterModule ?? 'all' }}">
                <input type="hidden" name="user" value="{{ $filterUser ?? 'all' }}">
                <input type="hidden" name="from_date" value="{{ $filterFrom ?? '' }}">
                <input type="hidden" name="to_date" value="{{ $filterTo ?? '' }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">
                <div class="relative min-w-0 flex-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                        </svg>
                    </span>
                    <input type="text" name="search" value="{{ $search ?? '' }}"
                           placeholder="Search user, module, record, reference, or action..."
                           class="h-9 w-full rounded-lg border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                </div>
            </form>

            {{-- Filter popover: action · module · user · date range · page size --}}
            <div id="logsFilterWrap" class="relative">
                <button type="button" id="logsFilterBtn" aria-haspopup="true" aria-expanded="false" aria-controls="logsFilterMenu"
                        class="flex h-9 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 4h18M6 9h12M10 14h4"/>
                    </svg>
                    <span>Filter</span>
                    @if ($filtersActive > 0)
                        <span class="inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-[#2a7a94] px-1 text-[10px] font-bold text-white">{{ $filtersActive }}</span>
                    @endif
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m19 9-7 7-7-7"/>
                    </svg>
                </button>

                <div id="logsFilterMenu" role="group" aria-labelledby="logsFilterBtn" aria-hidden="true"
                     class="hidden absolute left-0 top-full z-30 mt-2 w-64 rounded-lg border border-gray-200 bg-white py-2 shadow-lg">
                    <form method="GET" action="{{ route('settings.logs') }}" id="logsFilterForm" class="space-y-2 px-2">
                        <input type="hidden" name="search" value="{{ $search ?? '' }}">

                        <div>
                            <label for="logsAction" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Action</label>
                            <select id="logsAction" name="action" aria-label="Filter by action" onchange="this.form.requestSubmit()"
                                    class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                <option value="all" {{ ($filterAction ?? 'all') === 'all' ? 'selected' : '' }}>All Actions</option>
                                <option value="Created" {{ ($filterAction ?? 'all') === 'Created' ? 'selected' : '' }}>Created</option>
                                <option value="Edited" {{ ($filterAction ?? 'all') === 'Edited' ? 'selected' : '' }}>Edited</option>
                                <option value="Deleted" {{ ($filterAction ?? 'all') === 'Deleted' ? 'selected' : '' }}>Deleted</option>
                            </select>
                        </div>

                        <div>
                            <label for="logsModule" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Module</label>
                            <select id="logsModule" name="module" aria-label="Filter by module" onchange="this.form.requestSubmit()"
                                    class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                <option value="all" {{ ($filterModule ?? 'all') === 'all' ? 'selected' : '' }}>All Modules</option>
                                @foreach ($modules ?? [] as $module)
                                    <option value="{{ $module }}" {{ ($filterModule ?? 'all') === $module ? 'selected' : '' }}>{{ $module }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="logsUser" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">User</label>
                            <select id="logsUser" name="user" aria-label="Filter by user" onchange="this.form.requestSubmit()"
                                    class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                <option value="all" {{ ($filterUser ?? 'all') === 'all' ? 'selected' : '' }}>All Users</option>
                                @foreach ($users ?? [] as $user)
                                    <option value="{{ $user }}" {{ ($filterUser ?? 'all') === $user ? 'selected' : '' }}>{{ $user }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="logsFrom" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">From</label>
                                <input type="date" id="logsFrom" name="from_date" value="{{ $filterFrom ?? '' }}" aria-label="From date"
                                       onchange="this.form.requestSubmit()"
                                       class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            </div>
                            <div>
                                <label for="logsTo" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">To</label>
                                <input type="date" id="logsTo" name="to_date" value="{{ $filterTo ?? '' }}" aria-label="To date"
                                       onchange="this.form.requestSubmit()"
                                       class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            </div>
                        </div>

                        <div>
                            <label for="logsPerPage" class="mb-1 block text-[11px] font-semibold uppercase tracking-wider text-gray-500">Per page</label>
                            <select id="logsPerPage" name="per_page" aria-label="Logs per page" onchange="this.form.requestSubmit()"
                                    class="w-full rounded-md border border-gray-200 bg-white px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                @foreach (\App\Support\Pagination::options() as $value => $label)
                                    <option value="{{ $value }}" @selected($perPage === $value)>{{ $value }} / page</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($filtersActive > 0)
                            <a href="{{ route('settings.logs', ['search' => ($search ?? '') !== '' ? $search : null, 'per_page' => $perPage]) }}"
                               class="mt-1 flex items-center justify-center rounded-md border border-gray-200 px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                Clear filters
                            </a>
                        @endif
                    </form>
                </div>
            </div>
        </div>        {{-- Logs table card --}}
        <div class="mt-4 flex min-h-[240px] flex-1 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm lg:min-h-0">
            <div class="lg:min-h-0 lg:flex-1 lg:overflow-auto">
                <table class="w-full table-fixed text-sm">
                    <colgroup>
                        <col class="w-[21%]">
                        <col class="w-[15%]">
                        <col class="w-[15%]">
                        <col class="w-[13%]">
                        <col class="w-[15%]">
                        <col class="w-[21%]">
                    </colgroup>
                    <thead class="sticky top-0 z-10 bg-[#0e5266]">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Time</th>
                            <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">User</th>
                            <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Role</th>
                            <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Action</th>
                            <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Module</th>
                            <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Record</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @if ($activityLogs->isEmpty())
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <p class="text-sm font-semibold text-gray-900">No activity records found</p>
                                    <p class="mt-1 text-xs text-gray-500">Try widening the date range or clearing the filters.</p>
                                </td>
                            </tr>
                        @else
                            @foreach ($activityLogs as $log)
                                <tr class="odd:bg-white even:bg-[#f2f7f8] transition-colors hover:bg-[#e4edf0]">
                                    <td class="px-4 py-2.5 align-top text-xs text-gray-700">{{ $log['time'] }}</td>
                                    <td class="px-4 py-2.5 align-top text-xs font-semibold text-gray-900">{{ $log['user'] }}</td>
                                    <td class="px-4 py-2.5 align-top">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold
                                            @if($log['role'] === 'Administrator') bg-blue-100 text-blue-700
                                            @else bg-gray-100 text-gray-700 @endif">
                                            {{ $log['role'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 align-top">
                                        @php
                                            $actionPill = match ($log['action']) {
                                                'Created' => 'bg-green-100 text-green-700',
                                                'Edited' => 'bg-blue-100 text-blue-700',
                                                'Deleted' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-600',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $actionPill }}">{{ $log['action'] }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 align-top text-xs font-semibold text-gray-900">{{ $log['module'] }}</td>
                                    <td class="px-4 py-2.5 align-top text-xs text-gray-700">{{ $log['record'] }}</td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- Footer strip --}}
            <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-2">
                <p class="text-[11px] text-gray-500">Showing <span class="font-semibold text-gray-700">{{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }}</span> of <span class="font-semibold text-gray-700">{{ $logs->total() }}</span> records</p>
                <div class="no-print">{{ $logs->links() }}</div>
            </div>
        </div>

        {{-- Back to settings — pinned to the very bottom like the privileges page --}}
        <div class="no-print mt-4 flex shrink-0 items-center justify-start">
            <a href="{{ route('settings') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to settings
            </a>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Filter popover: same behavior as the other modules — click outside or
    // Escape closes the menu, selects submit automatically.
    (function () {
        var filterWrap = document.getElementById('logsFilterWrap');
        var filterBtn = document.getElementById('logsFilterBtn');
        var filterMenu = document.getElementById('logsFilterMenu');
        if (!filterWrap || !filterBtn || !filterMenu) {
            return;
        }

        function setOpen(open) {
            filterMenu.classList.toggle('hidden', !open);
            filterBtn.setAttribute('aria-expanded', String(open));
            filterMenu.setAttribute('aria-hidden', String(!open));
        }

        filterBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            setOpen(filterMenu.classList.contains('hidden'));
        });

        document.addEventListener('click', function (e) {
            if (!filterWrap.contains(e.target)) {
                setOpen(false);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !filterMenu.classList.contains('hidden')) {
                setOpen(false);
                filterBtn.focus();
            }
        });
    })();
</script>
@endpush
