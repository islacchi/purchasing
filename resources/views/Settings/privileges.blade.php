@extends('layouts.app')

@section('page-title', 'USER PRIVILEGE')
@section('title', 'User Privilege - Purchasing')

@section('header-actions')
@endsection

@section('content')
    @php
        // Small helper for the avatar initials circle. The function_exists()
        // guard prevents redeclaration when a view renders more than once in
        // the same process.
        if (!function_exists('initialsFromName')) {
            function initialsFromName($name)
            {
                $initials = '';
                foreach (preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) as $part) {
                    $initials .= strtoupper(substr($part, 0, 1));
                    if (strlen($initials) >= 2) break;
                }
                return $initials;
            }
        }

        $rolePill = function ($role) {
            return match ($role) {
                'Administrator' => 'bg-blue-100 text-blue-700',
                'Procurement Officer' => 'bg-teal-100 text-teal-700',
                'Viewer' => 'bg-purple-100 text-purple-700',
                default => 'bg-gray-100 text-gray-700',
            };
        };
    @endphp

    {{-- Page heading + primary action --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">User Accounts</h2>
            <p class="text-sm text-gray-500">
                Create accounts and control what each user can access.
            </p>
        </div>
        <a href="{{ route('settings.privileges.create') }}"
           class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span class="font-medium">Add user</span>
        </a>
    </div>

    {{-- Flash messages --}}
    @if (session('status'))
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
            </svg>
            <span>{{ session('error') ?? $errors->first() }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Users table --}}
        <div class="xl:col-span-2 space-y-4">
            {{-- Search toolbar --}}
            <form method="GET" action="{{ route('settings.privileges') }}" class="flex items-center gap-3">
                <div class="relative max-w-md w-full">
                    <button type="submit" title="Search" aria-label="Search"
                            class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 transition-colors hover:text-[#2a7a94]">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                        </svg>
                    </button>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search users..."
                           class="w-full pl-10 pr-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 border border-gray-200">
                </div>
                @if (! empty($search))
                    <a href="{{ route('settings.privileges') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                        Clear
                    </a>
                @endif
            </form>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date added</th>
                            <th class="px-6 py-3 w-14 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($users as $user)
                            @php
                                $statusClass = $user->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600';
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                                data-href="{{ route('settings.privileges.edit', $user->id) }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center justify-center w-9 h-9 rounded-full bg-[#2a7a94]/10 text-[#2a7a94] text-xs font-semibold shrink-0">
                                            {{ initialsFromName($user->name) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $rolePill($user->role?->name) }}">{{ $user->role?->name ?? 'Unassigned' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    <div class="relative inline-block">
                                        <button type="button" class="dropdown-trigger p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors" title="Actions">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                                            </svg>
                                        </button>
                                        <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-40 bg-white rounded-lg border border-gray-200 shadow-lg z-10 py-1">
                                            <a href="{{ route('settings.privileges.edit', $user->id) }}"
                                               class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                                Edit
                                            </a>
                                            <form method="POST" action="{{ route('settings.privileges.toggle-status', $user->id) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                        title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                    </svg>
                                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('settings.privileges.destroy', $user->id) }}"
                                                  onsubmit="return confirm('Delete this user account? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                    {{ ! empty($search) ? 'No users match your search.' : 'No user accounts yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Available roles --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden self-start">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Available Roles</h2>
                <p class="text-xs text-gray-500 mt-1">Role determines what a user can see and do.</p>
            </div>
            <div class="p-6 space-y-4">
                @foreach ($roles as $role)
                    @php $count = $role->users_count; @endphp
                    <div class="flex items-start justify-between gap-3 p-3 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold text-gray-900">{{ $role->name }}</h3>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">
                                    {{ $count }} {{ $count === 1 ? 'user' : 'users' }}
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ $role->description }}</p>
                        </div>
                        {{-- @TODO: wire Configure to a role/permission editor once RBAC exists. --}}
                        <button type="button" class="shrink-0 text-xs font-medium text-[#2a7a94] hover:text-[#236b80]">
                            Configure
                        </button>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-gray-100 px-6 py-3">
                <p class="text-[11px] text-gray-400">
                    Role permissions are shared across every account assigned to that role.
                </p>
            </div>
        </div>
    </div>

    {{-- Bottom spacer so dropdowns on the last rows have room and are not clipped --}}
    <div class="pb-12"></div>

    <script>
        // Three-dot action dropdowns + row navigation (plain JS, matching the
        // rest of the app — no Alpine/Livewire).
        (function () {
            'use strict';

            function resetDropdown(menu) {
                menu.classList.add('hidden');
                menu.classList.remove('bottom-full', 'mb-2');
                menu.classList.add('top-full', 'mt-2');
            }

            document.querySelectorAll('.dropdown-trigger').forEach(function (trigger) {
                trigger.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const menu = this.nextElementSibling;
                    const isOpen = !menu.classList.contains('hidden');

                    document.querySelectorAll('.dropdown-menu').forEach(resetDropdown);

                    if (!isOpen) {
                        menu.classList.remove('hidden');
                        // Open upward when the menu would be clipped by the
                        // viewport or the table's overflow container.
                        const rect = menu.getBoundingClientRect();
                        const scrollBox = menu.closest('.overflow-x-auto');
                        const clipBottom = Math.min(window.innerHeight, scrollBox ? scrollBox.getBoundingClientRect().bottom : Infinity);
                        const opensUp = rect.bottom + 8 > clipBottom;
                        menu.classList.toggle('bottom-full', opensUp);
                        menu.classList.toggle('mb-2', opensUp);
                        menu.classList.toggle('top-full', !opensUp);
                        menu.classList.toggle('mt-2', !opensUp);
                    }
                });
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.dropdown-trigger')) {
                    document.querySelectorAll('.dropdown-menu').forEach(resetDropdown);
                }
            });

            document.querySelectorAll('.dropdown-action').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            });

            document.querySelectorAll('tr[data-href]').forEach(function (row) {
                row.addEventListener('click', function (e) {
                    if (e.target.closest('.dropdown-trigger') || e.target.closest('.dropdown-menu')) {
                        return;
                    }
                    window.location.href = row.dataset.href;
                });
            });
        })();
    </script>
@endsection
