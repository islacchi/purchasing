@extends('layouts.app')

@section('page-title', 'DELETE ACTIVITY LOGS')
@section('title', 'Delete activity logs — Purchasing')

@section('content')
    <div class="flex min-h-full flex-col lg:h-full lg:min-h-0">

        {{-- Page header --}}
        <div class="mb-4 flex shrink-0 flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Delete activity logs</h2>
                <p class="mt-1 text-xs text-gray-500">Review the entries and the reason, then print this page. Deletion only happens when you press the final button.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button onclick="window.print()"
                        class="no-print flex h-9 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 17h2a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2m2-4h6a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2zm8-12V3a2 2 0 0 0-2-2H3a2 2 0 0 0-2 2v18a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7h-6z"/>
                    </svg>
                    Print
                </button>
            </div>
        </div>

        {{-- Print styles: hide chrome, keep the log rows compact --}}
        <style>
            @media print {
                .no-print,
                .no-print * {
                    display: none !important;
                }
                body { background: white !important; }
                #sidebar, header, .fixed, .z-50 { display: none !important; }
                #mainContent { margin-left: 0 !important; width: 100% !important; padding: 0 !important; }
                th, td { border: 1px solid #ccc !important; padding: 4px 8px !important; }
            }
        </style>

        {{-- Review card --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm lg:min-h-0 lg:flex-1">
            @if ($logs->isEmpty())
                <p class="text-sm text-gray-500">No entries are waiting to be deleted. Going back is a good idea.</p>
            @else
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Reason for deletion</p>
                <p class="mt-1 text-sm text-gray-900">{{ $reason !== '' ? $reason : '(none recorded)' }}</p>

                <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Log entries to delete ({{ $logs->count() }})</p>
                <table class="mt-2 w-full table-fixed border-collapse text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 text-left font-semibold text-gray-600">Time</th>
                            <th class="p-2 text-left font-semibold text-gray-600">User</th>
                            <th class="p-2 text-left font-semibold text-gray-600">Action</th>
                            <th class="p-2 text-left font-semibold text-gray-600">Module</th>
                            <th class="p-2 text-left font-semibold text-gray-600">Record</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr class="border-t border-gray-100">
                                <td class="p-2 text-gray-700">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="p-2 text-gray-700">{{ $log->user_name }}</td>
                                <td class="p-2 text-gray-700">{{ $log->action }}</td>
                                <td class="p-2 text-gray-700">{{ $log->module }}</td>
                                <td class="p-2 text-gray-700">{{ $log->record_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Actions --}}
        <div class="mt-4 flex shrink-0 flex-wrap items-center justify-between gap-3">
            <a href="{{ route('settings.logs') }}"
               class="no-print inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                Back — don't delete
            </a>
            <form method="POST" action="{{ route('settings.logs.delete.execute') }}" class="no-print">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700">
                    Delete these logs permanently
                </button>
            </form>
        </div>
    </div>
@endsection
