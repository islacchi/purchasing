@extends('layouts.app')

@section('page-title', 'BACK-UP')
@section('title', 'Back-up - Purchasing')

@section('header-actions')
@endsection

@section('content')
    @php
        $formatBytes = function (int $bytes): string {
            if ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2).' MB';
            }

            return number_format($bytes / 1024, 2).' KB';
        };
    @endphp

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Current database card --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden lg:col-span-2">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Current Database</h2>
                <p class="mt-0.5 text-xs text-gray-500">Back-ups contain the entire database — structure and contents.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Driver</p>
                        <p class="text-sm text-gray-900">{{ $dbInfo['driver'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Database</p>
                        <p class="text-sm text-gray-900">{{ $dbInfo['database'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Host</p>
                        <p class="text-sm text-gray-900">{{ $dbInfo['host'] }}:{{ $dbInfo['port'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Latest back-up</p>
                        <p class="text-sm text-gray-900">{{ $latest['created'] ?? 'Not yet taken' }}</p>
                    </div>
                </div>

                @unless ($dbInfo['is_mysql'])
                    <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                        Back-up only supports MySQL. The current default connection is not MySQL, so “Download Database Back-up” will be disabled.
                    </p>
                @endunless

                {{-- Restore guidance --}}
                <div class="mt-6 border-t border-gray-100 pt-6">
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Restore guidance</p>
                    <p class="mt-2 text-xs text-gray-500">Restoring intentionally cannot be done from this page — it would destroy the live database. Run it by hand on a maintenance window instead:</p>
                    <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-4 py-3 text-xs text-gray-100">mysql -h {{ $dbInfo['host'] }} -P {{ $dbInfo['port'] }} -u &lt;user&gt; -p &lt; backups.sql
mysql -h {{ $dbInfo['host'] }} -P {{ $dbInfo['port'] }} -u &lt;user&gt; -p -e "CREATE DATABASE purchase_test;"</pre>
                    <p class="mt-2 text-xs text-gray-500">The dump file includes a drop-and-recreate of the database. Take a fresh back-up first and confirm the file runs on a scratch database before applying it to production.</p>
                </div>
            </div>
        </div>

        {{-- Actions and history --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Back-ups</h2>
                <p class="mt-0.5 text-xs text-gray-500">The last {{ count($backups) }} back-up{{ count($backups) === 1 ? '' : 's' }}; only the 15 newest are kept.</p>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('settings.backup.store') }}">
                    @csrf
                    <button type="submit" @disabled(! $dbInfo['is_mysql'])
                            class="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-[#2a7a94] px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#236b80] disabled:cursor-not-allowed disabled:border-gray-300 disabled:bg-gray-100 disabled:text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Download Database Back-up
                    </button>
                </form>

                <div class="pt-4 border-t border-gray-100 mt-4">
                    <p class="mb-3 text-xs font-medium uppercase tracking-wider text-gray-500">Back-up history</p>
                    <ul class="space-y-2">
                        @forelse ($backups as $backup)
                            <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm text-gray-900" title="{{ $backup['filename'] }}">{{ $backup['filename'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $backup['created'] }} &bull; {{ $formatBytes($backup['size']) }}</p>
                                </div>
                                <div class="flex flex-shrink-0 items-center gap-2">
                                    <a href="{{ route('settings.backup.download', ['file' => $backup['filename']]) }}"
                                       class="text-xs font-medium text-[#2a7a94] hover:text-[#236b80]">
                                        Download
                                    </a>
                                    <form method="POST" action="{{ route('settings.backup.destroy', ['file' => $backup['filename']]) }}"
                                          onsubmit="return confirm('Delete this back-up?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="rounded-lg border border-dashed border-gray-300 px-3 py-6 text-center text-xs text-gray-500">
                                No back-ups yet. Take one now — it contains the whole database.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
