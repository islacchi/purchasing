@extends('layouts.app')

@section('page-title', 'BACK-UP')
@section('title', 'Back-up - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Current database card --}}
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Current Database</h2>
            </div>
            <div class="p-6">
                @if ($dbInfo)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 rounded-lg border border-gray-100 bg-gray-50">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</p>
                            <p class="text-sm text-gray-900">{{ $dbInfo['name'] }}</p>
                        </div>
                        <div class="p-4 rounded-lg border border-gray-100 bg-gray-50">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Size</p>
                            <p class="text-sm text-gray-900">{{ number_format($dbInfo['size'] / 1024, 2) }} KB</p>
                        </div>
                        <div class="p-4 rounded-lg border border-gray-100 bg-gray-50">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Last Modified</p>
                            <p class="text-sm text-gray-900">{{ $dbInfo['modified'] }}</p>
                        </div>
                        <div class="p-4 rounded-lg border border-gray-100 bg-gray-50">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Type</p>
                            <p class="text-sm text-gray-900">SQLite</p>
                        </div>
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500">
                        <p>No database file found.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Backup actions card --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Create Backup</h2>
            </div>
            <div class="p-6 space-y-4">
                <button class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Download Database Backup
                </button>

                <div class="pt-4 border-t border-gray-100">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-3">Backup History</p>
                    <ul class="space-y-3">
                        @foreach ($backups ?? [] as $backup)
                            <li class="flex items-center justify-between p-2 rounded-lg border border-gray-100 bg-gray-50">
                                <div>
                                    <p class="text-sm text-gray-900">{{ $backup['filename'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $backup['created'] }} &bull; {{ number_format($backup['size'] / 1024, 2) }} KB</p>
                                </div>
                                <button class="text-xs font-medium text-[#2a7a94] hover:text-[#236b80]">
                                    Restore
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Placeholder notice --}}
    <div class="mt-8">
        <p class="text-xs text-gray-400 text-center">
            Backup functionality is in development. This page shows dummy backup history.
            Real database backup and restore will be enabled once the backup engine is integrated.
        </p>
    </div>
@endsection