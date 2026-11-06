@extends('layouts.app')

@section('page-title', 'USER PRIVILEGE')
@section('title', isset($user) && $user ? 'Edit User - Purchasing' : 'Add User - Purchasing')

@section('header-actions')
@endsection

@section('content')

    @php
        // ------------------------------------------------------------------
        // This single view serves Add and Edit for user accounts under
        // Settings → User Privilege (moved out of the Entities module).
        //
        //   - Add:  $user is null      -> blank form
        //   - Edit: $user is provided  -> prefill from the sample record
        //
        // @TODO: once models + migrations exist, replace the dummy record with:
        //   App\Models\User::with('roles')->findOrFail($id)
        // and replace action="#" with the real store/update routes.
        // ------------------------------------------------------------------

        $isEdit  = isset($user) && $user !== null;
        $formTitle = $isEdit ? 'EDIT USER' : 'ADD USER';
        $saveLabel = $isEdit ? 'Save changes' : 'Create user';

        $roleNames = collect($roles ?? [])->pluck('name')->all();
    @endphp

    {{-- Type label --}}
    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">USER ACCOUNT</p>

    <form action="#" method="POST" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        @csrf

        <h2 class="text-xl font-bold text-gray-900 uppercase tracking-wide">{{ $formTitle }}</h2>
        <p class="mt-1 text-sm text-gray-500">
            {{ $isEdit
                ? 'Update the account details or change the role and access status.'
                : 'Create an account and assign the role that matches the user’s duties.' }}
        </p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-10 gap-y-5 mt-6">
            {{-- Left column: identity --}}
            <div class="space-y-4">
                <div>
                    <label for="field-name" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Full name
                    </label>
                    <input id="field-name" type="text" name="name"
                           value="{{ old('name', $user['name'] ?? '') }}"
                           placeholder="e.g. J. SANTOS"
                           class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="field-email" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Email
                    </label>
                    <input id="field-email" type="email" name="email"
                           value="{{ old('email', $user['email'] ?? '') }}"
                           placeholder="e.g. J.SANTOS@COMPANY.COM"
                           class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Right column: access --}}
            <div class="space-y-4">
                <div>
                    <label for="field-role" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Role
                    </label>
                    <select id="field-role" name="role"
                            class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                        <option value="" {{ (string) old('role', $user['role'] ?? '') === '' ? 'selected' : '' }}>
                            Select role
                        </option>
                        @foreach($roleNames as $roleName)
                            <option value="{{ $roleName }}" {{ (string) old('role', $user['role'] ?? '') === (string) $roleName ? 'selected' : '' }}>
                                {{ $roleName }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="field-status" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                        Status
                    </label>
                    <select id="field-status" name="status"
                            class="w-full px-3 py-1.5 rounded-md border border-gray-200 bg-white text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                        @foreach(['Active', 'Inactive'] as $statusOption)
                            <option value="{{ $statusOption }}" {{ (string) old('status', $user['status'] ?? 'Active') === (string) $statusOption ? 'selected' : '' }}>
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

        {{-- @TODO: add password / invite controls once authentication and the
             User model exist. Accounts will be created with an invite email
             rather than a password typed by an administrator. --}}

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
