@extends('layouts.app')

@section('page-title', 'QUOTATION')
@section('title', 'Quotation - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="pb-24">
        {{-- Page heading --}}
        <div class="mb-6 flex items-end justify-between gap-4">
            <h2 class="text-xl font-bold text-gray-900">Quotation</h2>
            <p class="text-xs text-gray-500">Supplier responses to sent RFQs</p>
        </div>

        {{-- @TODO: Add search and filter controls here, following the same pattern used on the Projects and Entities index pages. --}}

        {{-- @TODO: Replace the hardcoded array below with a real query, e.g.
             $quotations = Rfq::with(['prepList', 'suppliers'])->latest()->get();
             and compute "items quoted" from the supplier's quoted items against the prep list's total items. --}}
        @php
            $quotations = [
                [
                    'id' => 1,
                    'prep_list' => 'July Region VII batch',
                    'ref' => '#2026-0090',
                    'supplier' => 'Triple Tact',
                    'quoted' => 0,
                    'total' => 2,
                    'status' => 'Awaiting quote',
                ],
                [
                    'id' => 2,
                    'prep_list' => 'July Region VII batch',
                    'ref' => '#2026-0090',
                    'supplier' => 'GreenCore Pharmaceuticals',
                    'quoted' => 1,
                    'total' => 2,
                    'status' => 'Quoted',
                ],
                [
                    'id' => 3,
                    'prep_list' => 'Lab reagents – PGH',
                    'ref' => '#2026-0089',
                    'supplier' => 'MedSource Inc.',
                    'quoted' => 3,
                    'total' => 3,
                    'status' => 'Quoted',
                ],
            ];
        @endphp

        {{-- Quotation tracker --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th scope="col" class="whitespace-nowrap px-6 py-3">Prep list</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3">Supplier</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3">Items quoted</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3">Status</th>
                            <th scope="col" class="sticky right-0 z-20 w-16 border-l border-gray-200 bg-gray-50 px-4 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($quotations as $quotation)
                            @php
                                $isFullyQuoted = $quotation['total'] > 0 && $quotation['quoted'] >= $quotation['total'];
                                $isPartiallyQuoted = $quotation['quoted'] > 0 && ! $isFullyQuoted;

                                $fractionColor = $isFullyQuoted
                                    ? 'text-green-600'
                                    : ($isPartiallyQuoted ? 'text-amber-500' : 'text-gray-400');

                                // Menus on the last two rows open upward so they are not clipped.
                                $openMenuUp = $loop->remaining < 2;
                            @endphp
                            <tr class="group transition-colors hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <p class="font-semibold text-gray-900">{{ $quotation['prep_list'] }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ $quotation['ref'] }}</p>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-gray-700">
                                    {{ $quotation['supplier'] }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span class="font-bold {{ $fractionColor }}">
                                        {{ $quotation['quoted'] }} / {{ $quotation['total'] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if ($quotation['status'] === 'Quoted')
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">
                                            {{ $quotation['status'] }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                            {{ $quotation['status'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="sticky right-0 z-10 border-l border-gray-100 bg-white px-4 py-4 text-right group-hover:bg-gray-50">
                                    <div class="relative inline-block text-left">
                                        <button type="button"
                                                data-quotation-menu-toggle
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                aria-label="Open actions for {{ $quotation['supplier'] }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/30">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1.8"/>
                                                <circle cx="12" cy="12" r="1.8"/>
                                                <circle cx="12" cy="19" r="1.8"/>
                                            </svg>
                                        </button>

                                        <div data-quotation-menu
                                             role="menu"
                                             class="absolute right-0 z-50 hidden w-44 rounded-lg border border-gray-200 bg-white py-1 shadow-lg {{ $openMenuUp ? 'bottom-full mb-2' : 'top-full mt-2' }}">
                                            {{-- @TODO: Point these at route('quotation.show', $quotation['id']) and route('quotation.edit', $quotation['id']) once those routes exist. --}}
                                            <a href="#" role="menuitem"
                                               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50">
                                                <svg class="h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                </svg>
                                                View
                                            </a>
                                            <a href="#" role="menuitem"
                                               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50">
                                                <svg class="h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                                </svg>
                                                Edit
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @push('scripts')
        <script>
            (function () {
                // Guard against double execution if the layout ever renders @stack('scripts') itself.
                if (window.__quotationMenusBound) return;
                window.__quotationMenusBound = true;

                var toggles = document.querySelectorAll('[data-quotation-menu-toggle]');
                var openToggle = null;
                var openMenu = null;

                function closeMenu() {
                    if (!openMenu) return;

                    openMenu.classList.add('hidden');
                    openToggle.setAttribute('aria-expanded', 'false');

                    var cell = openToggle.closest('td');
                    if (cell) cell.style.zIndex = '';

                    openToggle = null;
                    openMenu = null;
                }

                Array.prototype.forEach.call(toggles, function (toggle) {
                    toggle.addEventListener('click', function (event) {
                        event.stopPropagation();

                        var menu = toggle.parentElement.querySelector('[data-quotation-menu]');
                        var wasOpen = openMenu === menu;

                        closeMenu();
                        if (wasOpen) return;

                        openToggle = toggle;
                        openMenu = menu;

                        menu.classList.remove('hidden');
                        toggle.setAttribute('aria-expanded', 'true');

                        // The action cell is sticky and therefore its own stacking context.
                        // Raise it so the open menu paints above the rows below it.
                        var cell = toggle.closest('td');
                        if (cell) cell.style.zIndex = '30';
                    });
                });

                // Clicking anywhere outside the open menu closes it.
                document.addEventListener('click', function (event) {
                    if (!openMenu) return;
                    if (openMenu.contains(event.target) || openToggle.contains(event.target)) return;
                    closeMenu();
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape') closeMenu();
                });
            })();
        </script>
        @endpush

        {{-- layouts.app has no @stack('scripts') yet, so render the pushed script here. --}}
        @stack('scripts')
    </div>
@endsection
