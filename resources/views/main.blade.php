@extends('layouts.app')

@section('page-title', 'GOODS RECEIVED NOTE')
@section('title', 'Main - Purchasing')

@section('header-actions')
    <button class="bg-gray-800 text-white uppercase text-sm font-semibold px-5 py-2 rounded-full hover:bg-gray-700 transition-colors">
        New GRN
    </button>
    <button class="bg-gray-800 text-white uppercase text-sm font-semibold px-5 py-2 rounded-full hover:bg-gray-700 transition-colors">
        Export
    </button>
@endsection

@section('content')
    <div class="bg-white rounded-lg shadow p-6">
        <p class="text-gray-500">Main dashboard content goes here.</p>
    </div>
@endsection