@extends('layouts.app')

@section('page-title', 'SETTINGS')
@section('title', 'Settings - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- System Logs --}}
        <a href="{{ route('settings.logs') }}"
           class="block bg-white rounded-lg border border-gray-200 shadow-sm p-6 hover:border-[#2a7a94] hover:shadow-md transition-all cursor-pointer group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 group-hover:text-[#2a7a94] transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">System Logs</h3>
                    <p class="text-sm text-gray-500 mt-1">View application logs and error traces.</p>
                </div>
            </div>
            <div class="mt-auto pt-4 border-t border-gray-100 text-xs font-medium text-[#2a7a94]">
                View logs &rarr;
            </div>
        </a>

        {{-- User Privilege --}}
        <a href="{{ route('settings.privileges') }}"
           class="block bg-white rounded-lg border border-gray-200 shadow-sm p-6 hover:border-[#2a7a94] hover:shadow-md transition-all cursor-pointer group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 group-hover:text-[#2a7a94] transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">User Privilege</h3>
                    <p class="text-sm text-gray-500 mt-1">Manage user roles and access permissions.</p>
                </div>
            </div>
            <div class="mt-auto pt-4 border-t border-gray-100 text-xs font-medium text-[#2a7a94]">
                Manage users &rarr;
            </div>
        </a>

        {{-- Back-up --}}
        <a href="{{ route('settings.backup') }}"
           class="block bg-white rounded-lg border border-gray-200 shadow-sm p-6 hover:border-[#2a7a94] hover:shadow-md transition-all cursor-pointer group">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 group-hover:text-[#2a7a94] transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Back-up</h3>
                    <p class="text-sm text-gray-500 mt-1">Create and download database backups.</p>
                </div>
            </div>
            <div class="mt-auto pt-4 border-t border-gray-100 text-xs font-medium text-[#2a7a94]">
                Create backup &rarr;
            </div>
        </a>
    </div>

    <div class="mt-12">
        <p class="text-xs text-gray-400 text-center">
            All settings modules are currently in development. Functionality will be enabled once
            corresponding database models and migrations are integrated.
        </p>
    </div>
@endsection
