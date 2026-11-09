@extends('layouts.app')

@section('page-title', 'USER PRIVILEGE')
@section('title', isset($user) && $user ? 'Edit User - Purchasing' : 'Add User - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // This single view serves Add and Edit for user accounts under
        // Settings → User Privilege (moved out of the Entities module).
        $isEdit    = isset($user) && $user !== null;
        $formTitle = $isEdit ? 'EDIT USER' : 'ADD USER';
        $saveLabel = $isEdit ? 'Save changes' : 'Create user';

        $selectedRoleId = old('role_id', $isEdit ? $user->role_id : '');
        $selectedStatus = old('status', $isEdit ? ($user->is_active ? 'Active' : 'Inactive') : 'Active');
    @endphp

    {{-- Type label --}}
    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">USER ACCOUNT</p>

    <form action="{{ $isEdit ? route('settings.privileges.update', $user->id) : route('settings.privileges.store') }}"
          method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <h2 class="text-xl font-bold text-gray-900 uppercase tracking-wide">{{ $formTitle }}</h2>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit
                ? 'Update the account details or change the role and access status.'
                : 'Create an account and assign the role that matches the user’s duties.' }}
        </p>

        @if (session('error') || $errors->any())
            <div class="mt-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
                </svg>
                <span>{{ session('error') ?? $errors->first() }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-10 gap-y-5 mt-6">
            {{-- Left column: identity --}}
            <div class="space-y-4">
                <div>
                    <label for="field-name" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Full name
                    </label>
                    <input id="field-name" type="text" name="name"
                           value="{{ old('name', $isEdit ? $user->name : '') }}"
                           placeholder="e.g. J. SANTOS" required
                           class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    <p class="mt-1 text-[11px] text-gray-400">
                        This is also the username used to sign in.
                    </p>
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="field-email" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Email
                    </label>
                    <input id="field-email" type="email" name="email"
                           value="{{ old('email', $isEdit ? $user->email : '') }}"
                           placeholder="e.g. J.SANTOS@COMPANY.COM" required
                           class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="field-password" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Password {{ $isEdit ? '(leave blank to keep current)' : '' }}
                    </label>
                    <input id="field-password" type="password" name="password"
                           placeholder="At least 8 characters" @if (! $isEdit) required @endif
                           autocomplete="new-password"
                           class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="field-password-confirmation" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Confirm password
                    </label>
                    <input id="field-password-confirmation" type="password" name="password_confirmation"
                           placeholder="Repeat the password" @if (! $isEdit) required @endif
                           autocomplete="new-password"
                           class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                </div>
            </div>

            {{-- Right column: access --}}
            <div class="space-y-4">
                <div>
                    <label for="field-role" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Role
                    </label>
                    <select id="field-role" name="role_id" required
                            class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                        <option value="" {{ (string) $selectedRoleId === '' ? 'selected' : '' }}>
                            Select role
                        </option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ (string) $selectedRoleId === (string) $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="field-status" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Status
                    </label>
                    <select id="field-status" name="status" required
                            class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                        @foreach(['Active', 'Inactive'] as $statusOption)
                            <option value="{{ $statusOption }}" {{ (string) $selectedStatus === (string) $statusOption ? 'selected' : '' }}>
                                {{ $statusOption }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">
                        Inactive accounts keep their history but cannot sign in.
                    </p>
                    @error('status')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Form actions --}}
        <div class="flex items-center justify-end gap-3 mt-8 pt-5 border-t border-gray-100">
            <a href="{{ route('settings.privileges') }}"
               class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Cancel
            </a>
            <button type="submit"
                    class="flex items-center gap-2 px-4 py-2 rounded-md bg-[#2a7a94] text-white text-sm font-medium hover:bg-[#236b80] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-6 0V3h6v4m-6 0h6"/>
                </svg>
                {{ $saveLabel }}
            </button>
        </div>
    </form>

    {{-- Bottom spacer --}}
    <div class="pb-4"></div>

@endsection
