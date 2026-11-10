@extends('layouts.app')

@section('page-title', 'ENTITIES')
@section('title', 'Entities - Purchasing')

@section('content')

    @php
        $activeTab = request('tab') === 'suppliers' ? 'suppliers' : 'entities';
    @endphp

    {{-- Tabs --}}
    <div class="flex flex-wrap items-center gap-2 mb-6" role="tablist" aria-label="Entity records" data-active-tab="{{ $activeTab }}">
        <button type="button" role="tab" aria-selected="{{ $activeTab === 'entities' ? 'true' : 'false' }}" data-tab="entities"
                class="tab-btn px-4 py-2 rounded-md border text-sm font-medium transition-colors {{ $activeTab === 'entities' ? 'bg-[#2a7a94] text-white border-[#2a7a94] shadow-sm' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
            Procuring Entities
        </button>
        <button type="button" role="tab" aria-selected="{{ $activeTab === 'suppliers' ? 'true' : 'false' }}" data-tab="suppliers"
                class="tab-btn px-4 py-2 rounded-md border text-sm font-medium transition-colors {{ $activeTab === 'suppliers' ? 'bg-[#2a7a94] text-white border-[#2a7a94] shadow-sm' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
            Suppliers
        </button>
    </div>

    {{-- PROCURING ENTITIES TAB --}}
    <div class="tab-panel{{ $activeTab === 'entities' ? '' : ' hidden' }}" id="tab-panel-entities" data-panel="entities">
        {{-- Toolbar --}}
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1">
                <div class="relative max-w-md w-full">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                        </svg>
                    </span>
                    <input type="text" placeholder="Search procuring entities..."
                           class="w-full pl-10 pr-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 border border-gray-200">
                </div>
            </div>
            <a href="{{ route('entities.create', ['type' => 'procuring_entity']) }}"
               class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="font-medium">Add entity</span>
            </a>
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
                        <th class="px-6 py-3 w-14 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($entities as $entity)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                             data-href="{{ route('entities.show', ['type' => 'procuring_entity', 'id' => $entity['id']]) }}">
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
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="relative inline-block">
                                    <button type="button" class="dropdown-trigger p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors" title="Actions">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-36 bg-white rounded-lg border border-gray-200 shadow-lg z-10 py-1">
                                        <a href="{{ route('entities.edit', ['type' => 'procuring_entity', 'id' => $entity['id']]) }}"
                                           class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('entities.destroy', ['type' => 'procuring_entity', 'id' => $entity['id']]) }}">
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
                            <td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No procuring entities found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- SUPPLIERS TAB --}}
    <div class="tab-panel{{ $activeTab === 'suppliers' ? '' : ' hidden' }}" id="tab-panel-suppliers" data-panel="suppliers">
        {{-- Toolbar --}}
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1">
                <div class="relative max-w-md w-full">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                        </svg>
                    </span>
                    <input type="text" placeholder="Search suppliers..."
                           class="w-full pl-10 pr-4 py-2 rounded-lg bg-white text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300 border border-gray-200">
                </div>
            </div>
            <a href="{{ route('entities.create', ['type' => 'supplier']) }}"
               class="flex items-center gap-2 px-4 py-2 bg-[#2a7a94] text-white rounded-lg hover:bg-[#236b80] transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="font-medium">Add Supplier</span>
            </a>
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
                        <th class="px-6 py-3 w-14 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse ($suppliers as $supplier)
                        <tr class="hover:bg-gray-50 transition-colors cursor-pointer"
                             data-href="{{ route('entities.show', ['type' => 'supplier', 'id' => $supplier['id']]) }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <p class="text-sm font-bold text-gray-900">{{ $supplier['name'] }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $supplier['address'] }}</p>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $supplier['contact_person'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $supplier['contact_no'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $supplier['email'] }}</td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <div class="relative inline-block">
                                    <button type="button" class="dropdown-trigger p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors" title="Actions">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu hidden absolute right-0 top-full mt-2 w-36 bg-white rounded-lg border border-gray-200 shadow-lg z-10 py-1">
                                        <a href="{{ route('entities.edit', ['type' => 'supplier', 'id' => $supplier['id']]) }}"
                                           class="dropdown-action w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('entities.destroy', ['type' => 'supplier', 'id' => $supplier['id']]) }}">
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
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">No suppliers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Tab switching + three-dot action dropdowns (plain JS — the rest of the
        // app uses vanilla JS + the Tailwind CDN, no Alpine/Livewire).
        (function () {
            'use strict';

            const ACTIVE_TAB_CLASSES = ['bg-[#2a7a94]', 'text-white', 'border-[#2a7a94]', 'shadow-sm'];
            const INACTIVE_TAB_CLASSES = ['bg-white', 'text-gray-700', 'border-gray-300', 'hover:bg-gray-50'];

            function selectTab(tab) {
                document.querySelectorAll('.tab-btn').forEach(function (button) {
                    ACTIVE_TAB_CLASSES.forEach(function (c) { button.classList.remove(c); });
                    INACTIVE_TAB_CLASSES.forEach(function (c) { button.classList.add(c); });
                    button.setAttribute('aria-selected', button.dataset.tab === tab ? 'true' : 'false');
                });

                const selectedButton = document.querySelector('.tab-btn[data-tab="' + tab + '"]');
                if (selectedButton) {
                    ACTIVE_TAB_CLASSES.forEach(function (c) { selectedButton.classList.add(c); });
                    INACTIVE_TAB_CLASSES.forEach(function (c) { selectedButton.classList.remove(c); });
                }

                document.querySelectorAll('.tab-panel').forEach(function (panel) {
                    panel.classList.toggle('hidden', panel.dataset.panel !== tab);
                });

                const url = new URL(window.location.href);
                url.searchParams.set('tab', tab);
                window.history.replaceState(window.history.state, '', url);
            }

            document.querySelectorAll('.tab-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    selectTab(btn.dataset.tab);
                });
            });

            function resetDropdown(menu) {
                menu.classList.add('hidden');
                menu.classList.remove('bottom-full', 'mb-2');
                menu.classList.add('top-full', 'mt-2');
                menu.style.position = '';
                menu.style.left = '';
                menu.style.top = '';
                menu.style.right = '';
                menu.style.width = '';
            }

            document.querySelectorAll('.dropdown-trigger').forEach(function (trigger) {
                trigger.addEventListener('click', function (e) {
                    const menu = this.nextElementSibling;
                    const isOpen = !menu.classList.contains('hidden');

                    document.querySelectorAll('.dropdown-menu').forEach(resetDropdown);

                    if (isOpen) {
                        return;
                    }

                    menu.classList.remove('hidden');

                    // Render the menu with position: fixed so the table's
                    // overflow clipping cannot cut it off. Align its right
                    // edge with the trigger; flip above if there is not
                    // enough room below.
                    const rect = trigger.getBoundingClientRect();
                    const menuSize = menu.getBoundingClientRect();
                    let left = rect.right - menuSize.width;
                    if (left < 8) left = 8;

                    let top = rect.bottom + 6;
                    if (top + menuSize.height > window.innerHeight - 8) {
                        top = rect.top - menuSize.height - 6;
                    }

                    menu.style.position = 'fixed';
                    menu.style.left = left + 'px';
                    menu.style.top = top + 'px';
                    menu.style.right = 'auto';
                    menu.style.width = menuSize.width + 'px';
                    menu.classList.remove('mt-2', 'mb-2', 'top-full', 'bottom-full');
                });
            });

            // Menus are fixed-position; close them on any scroll (capture so we
            // catch the inner <main> scroller) or resize.
            window.addEventListener('scroll', function () {
                document.querySelectorAll('.dropdown-menu').forEach(resetDropdown);
            }, true);
            window.addEventListener('resize', function () {
                document.querySelectorAll('.dropdown-menu').forEach(resetDropdown);
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
