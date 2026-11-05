@extends('layouts.app')

@section('page-title', 'USER PRIVILEGE')
@section('title', 'User Privilege - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Users table card --}}
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">User Accounts</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($users ?? [] as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $user['name'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $user['email'] }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($user['role'] === 'Admin') bg-blue-100 text-blue-700
                                        @else bg-gray-100 text-gray-700 @endif">
                                        {{ $user['role'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($user['status'] === 'Active') bg-green-100 text-green-700
                                        @else bg-gray-100 text-gray-600 @endif">
                                        {{ $user['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <button class="text-xs font-medium text-[#2a7a94] hover:text-[#236b80]">
                                        Edit
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Roles card --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Available Roles</h2>
            </div>
            <div class="p-6 space-y-4">
                @foreach ($roles ?? [] as $role)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">{{ $role['name'] }}</h3>
                            <p class="text-xs text-gray-500 mt-1">{{ $role['description'] }}</p>
                        </div>
                        <button class="text-xs font-medium text-[#2a7a94] hover:text-[#236b80]">
                                        Configure
                                    </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Placeholder notice --}}
    <div class="mt-8">
        <p class="text-xs text-gray-400 text-center">
            User privilege management is in development. This page shows dummy data.
            Real role-based access control will be enabled once User and Role models are integrated.
        </p>
    </div>
@endsection