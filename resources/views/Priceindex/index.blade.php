@extends('layouts.app')

@section('page-title', 'PRICE INDEX')
@section('title', 'Price Index - Purchasing')

@section('header-actions')
@endsection

@section('content')
    {{--
        The comparison board that used to live here (price board, supplier
        competitiveness, item comparison spotlight, price trend, savings
        opportunities) was removed. The module is being repurposed into a
        consolidated items summary; nothing has been built yet.
    --}}
    <div class="flex items-center justify-center py-24">
        <div class="max-w-sm text-center">
            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 12h6m-6 4h6"/>
            </svg>
            <h2 class="mt-4 text-base font-semibold text-gray-900">Being rebuilt</h2>
            <p class="mt-1 text-sm text-gray-500">This module is being repurposed into a consolidated summary of items. Its comparison features were removed. Content will be added once the new version is ready.</p>
        </div>
    </div>
@endsection
