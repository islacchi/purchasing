@extends('layouts.app')

@section('page-title', 'CONFIGURE ROLE')
@section('title', 'Configure Role - Purchasing')

{{-- ============================================================
     DUMMY FRONT END — role permission editor.
     Permissions are NOT stored or enforced yet; every check.box
     is decorative. All wiring is marked TODO for the back-end.
     ============================================================ --}}

@section('header-actions')
@endsection

@section('content')
    {{-- TODO: read the `role` query parameter (set by the Configure link on the User Privileges page),
         look the role up in the roles table, and 404 if it does not exist. --}}
    {{-- TODO: load the role's description & user count from the database instead of hard-coding. --}}
    {{-- TODO: load the permission matrix from a real `permissions` table (or a JSON column on roles). --}}
    @php
        $roles = [
            'Administrator' => [
                'description' => 'Full system access',
                'users' => \App\Models\User::whereHas('role', fn ($q) => $q->where('name', 'Administrator'))->count(),
            ],
            'Procurement Officer' => [
                'description' => 'Can create and manage purchase orders',
                'users' => \App\Models\User::whereHas('role', fn ($q) => $q->where('name', 'Procurement Officer'))->count(),
            ],
            'Viewer' => [
                'description' => 'Read-only access to all modules',
                'users' => \App\Models\User::whereHas('role', fn ($q) => $q->where('name', 'Viewer'))->count(),
            ],
        ];

        $modules = [
            'Dashboard' => ['View'],
            'Projects' => ['View', 'Create', 'Edit', 'Delete'],
            'Prep Lists' => ['View', 'Compile', 'Edit', 'Delete'],
            'Quotations' => ['View', 'Upload document', 'Delete'],
            'Price Index' => ['View', 'Export'],
            'Purchase Orders' => ['View', 'Create', 'Edit', 'Delete'],
            'Entities' => ['View', 'Create', 'Edit', 'Delete'],
            'Settings' => ['View', 'Manage users', 'View logs', 'Manage configuration'],
        ];

        // TODO: build this matrix from a real permission system. Each role should persist the
        //       granted action keys instead of this hard-coded placeholder.
        $permitted = [
            'Administrator' => [
                'Dashboard' => ['View'],
                'Projects' => ['View', 'Create', 'Edit', 'Delete'],
                'Prep Lists' => ['View', 'Compile', 'Edit', 'Delete'],
                'Quotations' => ['View', 'Upload document', 'Delete'],
                'Price Index' => ['View', 'Export'],
                'Purchase Orders' => ['View', 'Create', 'Edit', 'Delete'],
                'Entities' => ['View', 'Create', 'Edit', 'Delete'],
                'Settings' => ['View', 'Manage users', 'View logs', 'Manage configuration'],
            ],
            'Procurement Officer' => [
                'Dashboard' => ['View'],
                'Projects' => ['View', 'Create', 'Edit', 'Delete'],
                'Prep Lists' => ['View', 'Compile', 'Edit', 'Delete'],
                'Quotations' => ['View', 'Upload document', 'Delete'],
                'Price Index' => ['View', 'Export'],
                'Purchase Orders' => ['View', 'Create', 'Edit'],
                'Entities' => ['View', 'Create', 'Edit'],
                'Settings' => ['View'],
            ],
            'Viewer' => [
                'Dashboard' => ['View'],
                'Projects' => ['View'],
                'Prep Lists' => ['View'],
                'Quotations' => ['View'],
                'Price Index' => ['View'],
                'Purchase Orders' => ['View'],
                'Entities' => ['View'],
                'Settings' => ['View'],
            ],
        ];

        // Read the role from the query string (set by the Configure links on the User Privileges page).
        $selectedRole = request()->query('role', 'Administrator');
        if (! array_key_exists($selectedRole, $roles)) {
            $selectedRole = 'Administrator';
        }
    @endphp

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-xl font-semibold text-gray-900">Configure role</h2>
            <p class="mt-1 text-sm text-gray-500">Choose a role, then enable or disable the permissions it grants.</p>
        </div>
        {{-- TODO: show the signed-in user / current role here once authentication-aware chrome is needed. --}}
        <a href="{{ route('settings.privileges') }}"
           class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to users
        </a>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[280px_1fr]">
        {{-- ============ Role selector ============ --}}
        <div class="space-y-3 self-start">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">Select role</p>

            @foreach ($roles as $roleName => $role)
                <button type="button"
                        data-role-select="{{ $roleName }}"
                        aria-pressed="{{ $roleName === $selectedRole ? 'true' : 'false' }}"
                        class="role-select block w-full rounded-lg border p-4 text-left transition-colors focus:outline-none focus:ring-2 focus:ring-teal-300
                               {{ $roleName === $selectedRole ? 'border-[#2a7a94] bg-[#2a7a94]/5 ring-1 ring-[#2a7a94]' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                    <span class="flex items-start justify-between gap-3">
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-gray-900">{{ $roleName }}</span>
                            <span class="mt-1 block text-xs text-gray-500">{{ $role['description'] }}</span>
                        </span>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">
                            {{ $role['users'] }} {{ $role['users'] === 1 ? 'user' : 'users' }}
                        </span>
                    </span>
                </button>
            @endforeach

            {{-- TODO: show how many permissions this role has once RBAC storage exists. --}}
            <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-xs text-gray-500">
                <p class="font-medium text-gray-700">How permissions work</p>
                {{-- TODO: link to docs / describe the permission model. --}}
                <p class="mt-1">Role permissions are shared across every account assigned to that role. Changes here affect every user with the role.</p>
            </div>
        </div>

        {{-- ============ Permission matrix ============ --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Permissions for <span data-role-heading>{{ $selectedRole }}</span></h3>
                    {{-- TODO: also show how many permissions are enabled by default. --}}
                    <p class="mt-0.5 text-xs text-gray-500">Check the actions this role may perform.</p>
                </div>
                {{-- TODO: reset-to-defaults action (restoring the seeded matrix) once RBAC exists. --}}
                <button type="button" data-role-reset
                        class="text-xs font-medium text-[#2a7a94] hover:text-[#236b80] transition-colors">
                    Reset to defaults
                </button>
            </div>

            @foreach ($roles as $roleName => $role)
                <div data-role-panel="{{ $roleName }}" class="{{ $roleName === $selectedRole ? '' : 'hidden' }} grid grid-cols-1 sm:grid-cols-2 gap-4 p-6">
                    @foreach ($modules as $module => $actions)
                        @php
                            $grantedActions = $permitted[$roleName][$module] ?? [];
                        @endphp

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">{{ $module }}</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($actions as $action)
                                    @php $isPermittedForRole = in_array($action, $grantedActions, true); @endphp

                                    <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:border-gray-300">
                                        {{-- TODO: replace decorative checkbox with a real permission toggle. --}}
                                        <input type="checkbox" name="permissions[{{ $module }}][{{ $action }}]" value="1"@if ($isPermittedForRole) checked @endif
                                               class="h-4 w-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300">
                                        <span>{{ $action }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="flex flex-col-reverse gap-3 border-t border-gray-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-gray-400">
                    {{-- TODO: remove this notice once RBAC storage and enforcement are in place. --}}
                    Changes here are placeholders only — nothing is stored until the back-end is wired.
                </p>
                <div class="flex items-center gap-3">
                    {{-- TODO: wire to a real update handler (POST + CSRF + validation + activity log + effective permissions cache refresh). --}}
                    <button type="button" data-role-save
                            class="flex items-center gap-2 rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white hover:bg-[#236b80] transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 12.5l4.5 4.5L19 7.5"/>
                        </svg>
                        Save permissions
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Dummy save toast --}}
    <div id="configurationSaveNotice" class="hidden fixed bottom-6 right-6 z-[60] flex items-center gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-lg max-w-sm" role="status">
        <svg class="h-5 w-5 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
        </svg>
        <div>
            <p class="font-semibold">Permissions not saved</p>
            <p class="mt-0.5 text-xs text-amber-700">Placeholder front end — the back-end permissions store is not wired up yet.</p>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Dummy front-end wiring for the role permission editor.
    // Every handler below is a placeholder; wire the real back-end later.
    (function () {
        var selectorButtons = document.querySelectorAll('[data-role-select]');
        var panelHeading = document.querySelector('[data-role-heading]');
        var notice = document.getElementById('configurationSaveNotice');

        // TODO: fetch the full permission matrix for the selected role from the back-end
        //       (e.g. GET /settings/configuration?role=NAME) and render checkboxes dynamically.
        // TODO: persist the matrix on save via POST + CSRF; refresh the cached effective permissions.
        function selectRole(roleName) {
            selectorButtons.forEach(function (button) {
                var selected = button.getAttribute('data-role-select') === roleName;
                button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                button.classList.toggle('border-[#2a7a94]', selected);
                button.classList.toggle('bg-[#2a7a94]/5', selected);
                button.classList.toggle('ring-1', selected);
                button.classList.toggle('ring-[#2a7a94]', selected);
                button.classList.toggle('border-gray-200', !selected);
                button.classList.toggle('bg-white', !selected);
                button.classList.toggle('hover:border-gray-300', !selected);
            });

            document.querySelectorAll('[data-role-panel]').forEach(function (panel) {
                panel.classList.toggle('hidden', panel.getAttribute('data-role-panel') !== roleName);
            });

            if (panelHeading) panelHeading.textContent = roleName;
        }

        selectorButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                selectRole(button.getAttribute('data-role-select'));
            });
        });

        // Reset button (front-end-only): revert every checkbox for every role to its initial checked state.
        // TODO: replace with a real "reset to defaults" action once permissions are persisted.
        document.querySelectorAll('[data-role-reset]').forEach(function (reset) {
            reset.addEventListener('click', function () {
                document.querySelectorAll('input[type="checkbox"][name^="permissions["]').forEach(function (checkbox) {
                    // Reset to the server-rendered default.
                    checkbox.checked = checkbox.defaultChecked;
                });
            });
        });

        // Save button (front-end-only).
        // TODO: POST the permission matrix to the back-end; show success/error flash; log to activity.
        document.querySelectorAll('[data-role-save]').forEach(function (save) {
            save.addEventListener('click', function () {
                if (!notice) return;

                notice.classList.remove('hidden');
                notice.style.opacity = '1';
                notice.style.transform = 'translateY(0)';

                window.setTimeout(function () {
                    notice.style.opacity = '0';
                    notice.style.transform = 'translateY(8px)';
                }, 3200);

                window.setTimeout(function () {
                    notice.classList.add('hidden');
                }, 3600);
            });
        });
    })();
</script>
@endpush
