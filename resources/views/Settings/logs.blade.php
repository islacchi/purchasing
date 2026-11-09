@extends('layouts.app')

@section('page-title', 'ACTIVITY LOGS')
@section('title', 'Activity Logs - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Activity Logs</h2>
            <p class="text-sm text-gray-500">User actions across all modules (create, edit, delete).</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                {{ count($activityLogs ?? []) }} records
            </span>
            <button onclick="window.print()"
                    class="no-print flex items-center gap-2 px-3 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2-4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V3a2 2 0 00-2-2H3a2 2 0 00-2 2v18a2 2 0 002 2h14a2 2 0 002-2V7h-6z"/>
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
            .placeholder-notice,
            .mt-8 > p {
                display: none !important;
            }
        }
    </style>

    <div class="mb-6 no-print">
        {{-- Search and filters bar --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <form method="GET" action="{{ route('settings.logs') }}" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Search field --}}
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">Search</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                                </svg>
                            </span>
                            <input type="text" name="search" value="{{ $search ?? '' }}"
                                   placeholder="Search user, module, record, reference, or action..."
                                   class="w-full pl-9 pr-3 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                        </div>
                    </div>

                    {{-- Action filter --}}
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">Action</label>
                        <select name="action" class="w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                            <option value="all" {{ ($filterAction ?? 'all') === 'all' ? 'selected' : '' }}>All Actions</option>
                            <option value="Created" {{ ($filterAction ?? 'all') === 'Created' ? 'selected' : '' }}>Created</option>
                            <option value="Edited" {{ ($filterAction ?? 'all') === 'Edited' ? 'selected' : '' }}>Edited</option>
                            <option value="Deleted" {{ ($filterAction ?? 'all') === 'Deleted' ? 'selected' : '' }}>Deleted</option>
                        </select>
                    </div>

                    {{-- Module filter --}}
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">Module</label>
                        <select name="module" class="w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                            <option value="all" {{ ($filterModule ?? 'all') === 'all' ? 'selected' : '' }}>All Modules</option>
                            @foreach ($modules ?? [] as $module)
                                <option value="{{ $module }}" {{ ($filterModule ?? 'all') === $module ? 'selected' : '' }}>{{ $module }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- User filter --}}
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">User</label>
                        <select name="user" class="w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                            <option value="all" {{ ($filterUser ?? 'all') === 'all' ? 'selected' : '' }}>All Users</option>
                            @foreach ($users ?? [] as $user)
                                <option value="{{ $user }}" {{ ($filterUser ?? 'all') === $user ? 'selected' : '' }}>{{ $user }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- From date --}}
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">From</label>
                        <input type="date" name="from_date" value="{{ $filterFrom ?? '' }}"
                               class="w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                    </div>

                    {{-- To date --}}
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1.5">To</label>
                        <input type="date" name="to_date" value="{{ $filterTo ?? '' }}"
                               class="w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300 focus:border-transparent">
                    </div>
                </div>

                {{-- Form actions --}}
                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <span class="text-xs font-medium text-gray-500">
                        Showing <strong>{{ count($activityLogs ?? []) }}</strong> record(s)
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="reset"
                                class="px-3 py-2 rounded-md text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
                            Reset
                        </button>
                        <button type="submit"
                                class="px-4 py-2 rounded-md bg-[#2a7a94] text-white text-sm font-medium hover:bg-[#236b80] transition-colors">
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Activity logs table --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm divide-y divide-gray-100">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Record</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @if (empty($activityLogs ?? []))
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">No activity records found.</td>
                        </tr>
                    @else
                        @foreach ($activityLogs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $log['time'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">{{ $log['user'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        @if($log['role'] === 'Admin') bg-blue-100 text-blue-700
                                        @else bg-gray-100 text-gray-700 @endif">
                                        {{ $log['role'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                                    @if($log['action'] === 'Created')
                                        <span class="text-green-600">Created</span>
                                    @elseif($log['action'] === 'Edited')
                                        <span class="text-blue-600">Edited</span>
                                    @elseif($log['action'] === 'Deleted')
                                        <span class="text-red-600">Deleted</span>
                                    @else
                                        <span class="text-gray-600">{{ $log['action'] }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $log['module'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $log['record'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    {{-- Placeholder notice --}}
    <div class="mt-8 no-print placeholder-notice">
        <p class="text-xs text-gray-400 text-center">
            Activity log is currently displaying dummy data. A real audit-log table and middleware
            will be implemented once user and module models are integrated.
        </p>
    </div>
@endsection