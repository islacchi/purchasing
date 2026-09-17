@extends('layouts.app')

@section('page-title', 'ENTITIES')
@section('title', 'Entities - Purchasing')

@section('content')

    @php
        // ------------------------------------------------------------------
        // Dummy data for the three tabs below (no DB integration yet).
        //
        // @TODO: Replace the hardcoded arrays with real Eloquent queries once the
        // User/Role, Entity, and Supplier models + migrations exist:
        //   $users     = App\Models\User::with('roles')->get();
        //   $entities  = App\Models\Entity::all();
        //   $suppliers = App\Models\Supplier::all();
        // ------------------------------------------------------------------
        // @TODO: users should come from App\Models\User::with('roles')->get(),
        // with role pills derived from the user's role relation.
        $users = [
            ['name' => 'J. Santos', 'email' => 'j.santos@company.com', 'role' => 'Procurement officer', 'status' => 'Active'],
            ['name' => 'M. Reyes',  'email' => 'm.reyes@company.com',  'role' => 'Admin', 'status' => 'Active'],
            ['name' => 'A. Cruz',   'email' => 'a.cruz@company.com',   'role' => 'Procurement officer', 'status' => 'Inactive'],
        ];

        // @TODO: procuring entities should come from App\Models\Entity::all().
        $entities = [
            [
                'name'           => 'DOH Region VII',
                'address'        => 'Osmeña Blvd, Cebu City',
                'type'            => 'Regional Office',
                'contact_person'  => 'Dr. R. Villanueva',
                'contact_no'      => '032-123-4567',
                'email'           => 'doh.region7@gov.ph',
            ],
            [
                'name'           => 'PGH Manila',
                'address'        => 'Taft Ave, Ermita, Manila',
                'type'            => 'Government Hospital',
                'contact_person'  => 'N. Aquino',
                'contact_no'      => '02-8554-8400',
                'email'           => 'procurement@pgh.gov.ph',
            ],
            [
                'name'           => 'Iloilo Provincial Hospital',
                'address'        => 'Iloilo City',
                'type'            => 'Provincial Hospital',
                'contact_person'  => 'V. Tan',
                'contact_no'      => '033-778-2211',
                'email'           => 'procurement@iph.gov.ph',
            ],
        ];

        // @TODO: suppliers should come from App\Models\Supplier::all().
        $suppliers = [
            [
                'name'           => 'Triple Tact',
                'address'        => 'Mandaue City, Cebu',
                'contact_person' => 'E. Lim',
                'contact_no'     => '032-345-6789',
                'email'          => 'sales@tripletact.com',
            ],
            [
                'name'           => 'GreenCore Pharmaceuticals',
                'address'        => 'Quezon City, Metro Manila',
                'contact_person' => 'P. Domingo',
                'contact_no'     => '02-8123-4455',
                'email'          => 'orders@greencorepharma.ph',
            ],
            [
                'name'           => 'MedSource Inc.',
                'address'        => 'Pasig City, Metro Manila',
                'contact_person' => 'C. Fernandez',
                'contact_no'     => '02-8990-1122',
                'email'          => 'info@medsourceinc.ph',
            ],
        ];

        // Small helper for the avatar initials circle in the Users table.
        // @TODO: once Eloquent is wired up, expose the initials directly on the model.
        function initialsFromName($name)
        {
            $initials = '';
            foreach (preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) as $part) {
                $initials .= strtoupper(substr($part, 0, 1));
                if (strlen($initials) >= 2) break;
            }
            return $initials;
        }
    @endphp
    {{-- Tabs --}}
    <div class="flex flex-wrap items-center gap-2 mb-6" role="tablist" aria-label="Entity records">
        <button type="button" role="tab" aria-selected="true" data-tab="users"
                class="tab-btn px-4 py-2 rounded-full border text-sm font-medium transition-colors bg-[#2a7a94] text-white border-[#2a7a94] shadow-sm">
            Users
        </button>
        <button type="button" role="tab" aria-selected="false" data-tab="entities"
                class="tab-btn px-4 py-2 rounded-full border text-sm font-medium transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50">
            Procuring Entities
        </button>
        <button type="button" role="tab" aria-selected="false" data-tab="suppliers"
                class="tab-btn px-4 py-2 rounded-full border text-sm font-medium transition-colors bg-white text-gray-700 border-gray-300 hover:bg-gray-50">
            Suppliers
        </button>
    </div>

    {{-- USERS TAB --}}
    <div class="tab-panel" id="tab-panel-users" data-panel="users">
        {{-- Toolbar --}}
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1">
                <div class="max-w-md w-full">
                    <input type="text" placeholder="Search users..."
                           class="w-full px-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 border border-gray-200">
                </div>
                <button type="button" class="p-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors" title="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </button>
            </div>
            {{-- @TODO: route the Add button to the User controller's create method
                 (e.g. route('users.create')) once real routes/controllers exist. --}}
            <button type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="font-medium">+ Add user</span>
            </button>
        </div>
    {{-- Table --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 w-14"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($users as $user)
                        @php
                            $roleClass   = $user['role'] === 'Admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700';
                            $statusClass = $user['status'] === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600';
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-9 h-9 rounded-full bg-[#2a7a94]/10 text-[#2a7a94] text-xs font-semibold shrink-0">
                                        {{ initialsFromName($user['name']) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">{{ $user['name'] }}</p>
                                        <p class="text-xs text-gray-500 mt-0.5">{{ $user['email'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $roleClass }}">{{ $user['role'] }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $statusClass }}">{{ $user['status'] }}</span>
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="relative inline-block">
                                    <button type="button" class="dropdown-trigger p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors" title="Actions">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-36 bg-white rounded-lg border border-gray-200 shadow-lg z-10 py-1">
                                        {{-- @TODO: route Edit/Delete to the User controller's
                                             edit/destroy methods once real routes exist. --}}
                                        <button type="button" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </button>
                                        <button type="button" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    {{-- PROCURING ENTITIES TAB --}}
    <div class="tab-panel hidden" id="tab-panel-entities" data-panel="entities">
        {{-- Toolbar --}}
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1">
                <div class="max-w-md w-full">
                    <input type="text" placeholder="Search procuring entities..."
                           class="w-full px-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 border border-gray-200">
                </div>
                <button type="button" class="p-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors" title="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </button>
            </div>
            {{-- @TODO: route the Add button to the Entity controller's create method
                 (e.g. route('entities.create')) once real routes/controllers exist. --}}
            <button type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="font-medium">+ Add entity</span>
            </button>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact person</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact no.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 w-14"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($entities as $entity)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-bold text-gray-900">{{ $entity['name'] }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $entity['address'] }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">{{ $entity['type'] }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $entity['contact_person'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $entity['contact_no'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $entity['email'] }}</td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="relative inline-block">
    <button type="button" class="dropdown-trigger p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors" title="Actions">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-36 bg-white rounded-lg border border-gray-200 shadow-lg z-10 py-1">
                                        {{-- @TODO: route Edit/Delete to the Entity controller's
                                             edit/destroy methods once real routes exist. --}}
                                        <button type="button" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </button>
                                        <button type="button" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    {{-- SUPPLIERS TAB --}}
    <div class="tab-panel hidden" id="tab-panel-suppliers" data-panel="suppliers">
        {{-- Toolbar --}}
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1">
                <div class="max-w-md w-full">
                    <input type="text" placeholder="Search suppliers..."
                           class="w-full px-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 border border-gray-200">
                </div>
                <button type="button" class="p-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors" title="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </button>
            </div>
            {{-- @TODO: route the Add button to the Supplier controller's create method
                 (e.g. route('suppliers.create')) once real routes/controllers exist. --}}
            <button type="button"
                    class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="font-medium">+ Add Supplier</span>
            </button>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact person</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact no.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 w-14"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($suppliers as $supplier)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-bold text-gray-900">{{ $supplier['name'] }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $supplier['address'] }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $supplier['contact_person'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $supplier['contact_no'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $supplier['email'] }}</td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="relative inline-block">
    <button type="button" class="dropdown-trigger p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors" title="Actions">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-36 bg-white rounded-lg border border-gray-200 shadow-lg z-10 py-1">
                                        {{-- @TODO: route Edit/Delete to the Supplier controller's
                                             edit/destroy methods once real routes exist. --}}
                                        <button type="button" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </button>
                                        <button type="button" class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    {{-- Bottom spacer so dropdowns on the last rows have room and are not clipped --}}
    <div class="pb-12"></div>

    <script>
        // Tab switching + three-dot action dropdowns (plain JS — the rest of the
        // app uses vanilla JS + the Tailwind CDN, no Alpine/Livewire).
        // @TODO: once real controllers exist, persist actions (Add/Edit/Delete)
        // by routing to their controller methods instead of being visual-only.
        (function () {
            'use strict';

            const ACTIVE_TAB_CLASSES   = ['bg-[#2a7a94]', 'text-white', 'border-[#2a7a94]', 'shadow-sm'];
            const INACTIVE_TAB_CLASSES = ['bg-white', 'text-gray-700', 'border-gray-300', 'hover:bg-gray-50'];

            // ------------------------------------------------------------------
            // Pill tabs — show/hide the matching table panel in place.
            // ------------------------------------------------------------------
            document.querySelectorAll('.tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const tab = btn.dataset.tab;

                    document.querySelectorAll('.tab-btn').forEach(function (b) {
                        ACTIVE_TAB_CLASSES.forEach(function (c) { b.classList.remove(c); });
                        INACTIVE_TAB_CLASSES.forEach(function (c) { b.classList.add(c); });
                        b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
                    });

                    ACTIVE_TAB_CLASSES.forEach(function (c) { btn.classList.add(c); });
                    INACTIVE_TAB_CLASSES.forEach(function (c) { btn.classList.remove(c); });

                    document.querySelectorAll('.tab-panel').forEach(function (panel) {
                        panel.classList.toggle('hidden', panel.dataset.panel !== tab);
                    });
                });
            });

            // ------------------------------------------------------------------
            // Three-dot action dropdowns (Edit / Delete) — one shared handler
            // for all three tables.
            // ------------------------------------------------------------------
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

                    // Close any other open dropdown first.
                    document.querySelectorAll('.dropdown-menu').forEach(resetDropdown);

                    if (!isOpen) {
                        menu.classList.remove('hidden');
                        // Open the menu upward when it would otherwise overflow
                        // the bottom of the viewport (e.g. last table rows).
                        const rect = menu.getBoundingClientRect();
                        const opensUp = rect.bottom + 8 > window.innerHeight;
                        menu.classList.toggle('bottom-full', opensUp);
                        menu.classList.toggle('mb-2', opensUp);
                        menu.classList.toggle('top-full', !opensUp);
                        menu.classList.toggle('mt-2', !opensUp);
                    }
                });
            });

            // Close a dropdown when clicking anywhere outside of it.
            document.addEventListener('click', function (e) {
                if (!e.target.closest('.dropdown-trigger')) {
                    document.querySelectorAll('.dropdown-menu').forEach(resetDropdown);
                }
            });

            // Keep dropdown-item clicks from bubbling and closing the menu first.
            document.querySelectorAll('.dropdown-action').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
            });
        })();
    </script>

@endsection