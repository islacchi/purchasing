@extends('layouts.app')

@section('page-title', 'ACTIVITY LOGS')
@section('title', 'Activity Logs - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="flex min-h-full flex-col lg:h-full lg:min-h-0">

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

        {{-- Filter card --}}
        <div class="no-print shrink-0 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            {{-- Friendly notice for inverted or malformed date ranges --}}
            @if (! empty($dateNotice))
                <div class="mb-3 flex items-start gap-2 rounded-md border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-800" role="alert">
                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86l-7.72 13.4A1 1 0 0 0 3.43 19h17.14a1 1 0 0 0 .86-1.74l-7.72-13.4a1 1 0 0 0-1.72 0z"/>
                    </svg>
                    <span>{{ $dateNotice }}</span>
                </div>
            @endif

            <form method="GET" action="{{ route('settings.logs') }}" class="space-y-3">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-6">
                    {{-- Search field --}}
                    <div class="col-span-2 flex flex-col">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Search</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                                </svg>
                            </span>
                            <input type="text" name="search" value="{{ $search ?? '' }}"
                                   placeholder="Search user, module, record, reference, or action..."
                                   class="h-9 w-full rounded-md border border-gray-200 bg-white pl-9 pr-3 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                        </div>
                    </div>

                    {{-- Action filter --}}
                    <div class="flex flex-col">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Action</label>
                        <select name="action" class="h-9 w-full rounded-md border border-gray-200 bg-white px-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                            <option value="all" {{ ($filterAction ?? 'all') === 'all' ? 'selected' : '' }}>All Actions</option>
                            <option value="Created" {{ ($filterAction ?? 'all') === 'Created' ? 'selected' : '' }}>Created</option>
                            <option value="Edited" {{ ($filterAction ?? 'all') === 'Edited' ? 'selected' : '' }}>Edited</option>
                            <option value="Deleted" {{ ($filterAction ?? 'all') === 'Deleted' ? 'selected' : '' }}>Deleted</option>
                        </select>
                    </div>

                    {{-- Module filter --}}
                    <div class="flex flex-col">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Module</label>
                        <select name="module" class="h-9 w-full rounded-md border border-gray-200 bg-white px-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                            <option value="all" {{ ($filterModule ?? 'all') === 'all' ? 'selected' : '' }}>All Modules</option>
                            @foreach ($modules ?? [] as $module)
                                <option value="{{ $module }}" {{ ($filterModule ?? 'all') === $module ? 'selected' : '' }}>{{ $module }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- User filter --}}
                    <div class="flex flex-col">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">User</label>
                        <select name="user" class="h-9 w-full rounded-md border border-gray-200 bg-white px-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                            <option value="all" {{ ($filterUser ?? 'all') === 'all' ? 'selected' : '' }}>All Users</option>
                            @foreach ($users ?? [] as $user)
                                <option value="{{ $user }}" {{ ($filterUser ?? 'all') === $user ? 'selected' : '' }}>{{ $user }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- From date --}}
                    <div class="flex flex-col">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">From</label>
                        <input type="date" name="from_date" value="{{ $filterFrom ?? '' }}"
                               class="h-9 w-full rounded-md border border-gray-200 bg-white px-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                    </div>

                    {{-- To date --}}
                    <div class="flex flex-col">
                        <label class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">To</label>
                        <input type="date" name="to_date" value="{{ $filterTo ?? '' }}"
                               class="h-9 w-full rounded-md border border-gray-200 bg-white px-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                    </div>
                </div>

                {{-- Form actions --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-3">
                    <label class="flex items-center gap-2 text-[11px] font-medium text-gray-500">
                        Per page
                        <select name="per_page" aria-label="Logs per page" onchange="this.form.requestSubmit()" class="h-8 rounded-md border border-gray-200 bg-white px-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            @foreach (\App\Support\Pagination::options() as $value => $label)
                                <option value="{{ $value }}" @selected($perPage === $value)>{{ $value }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="reset"
                                class="h-9 rounded-md px-3 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50">
                            Reset
                        </button>
                        <button type="submit"
                                class="h-9 rounded-md bg-[#2a7a94] px-4 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Logs table card --}}
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
    </div>
@endsection
