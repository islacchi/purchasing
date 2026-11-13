@extends('layouts.app')

@section('page-title', 'QUOTATION')
@section('title', 'Quotation - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <div class="pb-24">
        {{-- Page heading --}}
        <div class="mb-6 flex items-end justify-between gap-4">
            <!-- <h2 class="text-xl font-bold text-gray-900">Quotation</h2> -->
            <p class="text-xs text-gray-500">Supplier responses to sent RFQs</p>
        </div>

        {{-- Success messages are rendered once, globally, by layouts.app (partials.flash-success). --}}

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Quotation tracker --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th scope="col" class="whitespace-nowrap px-6 py-3">RFQ</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 expandable-col">Items quoted</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 expandable-col">Status</th>
                            <th scope="col" class="sticky right-0 z-20 bg-gray-50 px-6 py-3 text-center expandable-col">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- Group the RFQ tracker by prep list so quotations sent to
                             different suppliers for the same batch stay together. --}}
                        @forelse ($preplistGroups as $preplistGroup)
                            {{-- Group header: the row that contains the prep list. Clicking it
                                 expands/collapses the quotation rows underneath. --}}
                            <tr class="cursor-pointer select-none" data-group-toggle data-group-id="{{ $preplistGroup['preplist_id'] }}">
                                <td colspan="1" class="bg-gray-50 px-6 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold text-gray-900">{{ $preplistGroup['name'] }}</p>
                                            <p class="mt-0.5 text-xs text-gray-500">{{ $preplistGroup['ref'] }}</p>
                                        </div>
                                        <svg class="preplist-group-chevron h-4 w-4 flex-shrink-0 text-gray-400 transition-transform duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 18l6-6-6-6"/>
                                        </svg>
                                    </div>
                                </td>
                            </tr>

                            @foreach ($preplistGroup['quotations'] as $quotation)
                            @php
                                $isFullyQuoted = $quotation['total'] > 0 && $quotation['quoted'] >= $quotation['total'];
                                $isPartiallyQuoted = $quotation['quoted'] > 0 && ! $isFullyQuoted;

                                $fractionColor = $isFullyQuoted
                                    ? 'text-green-600'
                                    : ($isPartiallyQuoted ? 'text-amber-500' : 'text-gray-400');
                            @endphp
                            <tr class="group cursor-pointer transition-colors hover:bg-gray-50"
                                data-href="{{ route('quotation.show', ['quotation' => $quotation['id']]) }}"
                                data-quotation-group="{{ $quotation['preplist_id'] }}">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <p class="font-semibold text-gray-900">{{ $quotation['supplier'] }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ $quotation['rfq_number'] }}</p>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 expandable-col">
                                    <span class="font-bold {{ $fractionColor }}">
                                        {{ $quotation['quoted'] }} / {{ $quotation['total'] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 expandable-col">
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
                                <td class="sticky right-0 z-10 bg-white px-6 py-4 text-center group-hover:bg-gray-50 expandable-col">
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

                                        {{--
                                            The menu is positioned with `fixed` coordinates in JS.
                                            It lives inside an `overflow-x-auto` table wrapper, so an
                                            absolutely positioned menu would be clipped (which cut off the
                                            Delete item on rows whose menu opened upward).
                                        --}}
                                        <div data-quotation-menu
                                             role="menu"
                                             class="fixed z-50 hidden w-44 rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                                            @if ($quotation['status'] === 'Awaiting quote')
                                            <form method="POST" action="{{ route('quotation.destroy', ['quotation' => $quotation['id']]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" data-quotation-delete
                                                        data-quotation-supplier="{{ $quotation['supplier'] }}"
                                                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 transition-colors hover:bg-red-50"
                                                        role="menuitem">
                                                    <svg class="h-4 w-4 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                                                    </svg>
                                                    Delete
                                                </button>
                                            </form>
                                            @endif

                                            <a href="{{ route('quotation.show', ['quotation' => $quotation['id']]) }}" role="menuitem"
                                               class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50">
                                                <svg class="h-4 w-4 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                </svg>
                                                View
                                            </a>
                                           
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm text-gray-500">
                                    No quotations yet. Quotations are created when you generate the RFQ files from a prep list.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($groups->hasPages())
                <div class="border-t border-gray-200 px-6 py-3">
                    {{ $groups->links() }}
                </div>
            @endif
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

                    // Clear the coordinates set while the menu was positioned,
                    // so the next open starts from a clean slate.
                    openMenu.style.top = '';
                    openMenu.style.bottom = '';
                    openMenu.style.left = '';
                    openMenu.style.right = '';

                    var cell = openToggle.closest('td');
                    if (cell) cell.style.zIndex = '';

                    openToggle = null;
                    openMenu = null;
                }

                /**
                 * Place the menu just below the toggle, right-aligned to it.
                 * A fixed position escapes the table's horizontal scroll
                 * container, which would otherwise clip the menu.
                 * If there is no room below, flip it above the toggle.
                 */
                function positionMenu(toggle, menu) {
                    var rect = toggle.getBoundingClientRect();
                    var menuRect = menu.getBoundingClientRect();
                    var gap = 8;
                    var spaceBelow = window.innerHeight - rect.bottom;
                    var openUp = spaceBelow < menuRect.height + gap + 8 && rect.top > menuRect.height + gap;

                    menu.style.position = 'fixed';
                    menu.style.right = (window.innerWidth - rect.right) + 'px';
                    menu.style.left = 'auto';

                    if (openUp) {
                        menu.style.top = 'auto';
                        menu.style.bottom = (window.innerHeight - rect.top + gap) + 'px';
                    } else {
                        menu.style.bottom = 'auto';
                        menu.style.top = (rect.bottom + gap) + 'px';
                    }
                }

                // Exposed so the delete-modal script can collapse the open menu
                // without duplicating the toggle/menu bookkeeping.
                window.__quotationCloseMenu = closeMenu;

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

                        positionMenu(toggle, menu);

                        // The action cell is sticky and therefore its own stacking context.
                        // Raise it so the open menu paints above the rows below it.
                        var cell = toggle.closest('td');
                        if (cell) cell.style.zIndex = '30';
                    });
                });

                // Prep-list group toggle: clicking a prep list's group row expands
                // or collapses its quotation rows underneath with a smooth fade + slide.
                var quotationGroups = {};

                function quotationRows(groupId) {
                    return document.querySelectorAll('tr[data-quotation-group="' + groupId + '"]');
                }

                /**
                 * The Items quoted / Status / Action columns are only meaningful once
                 * at least one group is expanded, so hide them while everything is
                 * collapsed and reveal them on the first expansion.
                 */
                function refreshExpandableColumns() {
                    var anyOpen = Object.keys(quotationGroups).some(function (id) {
                        return quotationGroups[id];
                    });

                    document.querySelectorAll('.expandable-col').forEach(function (col) {
                        col.classList.toggle('hidden', !anyOpen);
                    });

                    // The group header row spans all columns while something is
                    // expanded, and only the base column while collapsed.
                    document.querySelectorAll('[data-group-toggle] td[colspan]').forEach(function (cell) {
                        cell.colSpan = anyOpen ? 4 : 1;
                    });
                }

                function setQuotationGroupOpen(groupId, open) {
                    quotationGroups[groupId] = open;
                    refreshExpandableColumns();

                    var rows = quotationRows(groupId);
                    // One uniform timing for every row in the group so the row
                    // background and its values always fade in/out together.
                    var DURATION = 180;            // ms
                    var EASE = 'cubic-bezier(0.16, 1, 0.3, 1)';

                    if (open) {
                        rows.forEach(function (row) {
                            row.classList.remove('hidden');
                        });
                        // Two-frame handoff so the display:none -> table-row change
                        // is committed before we start the fade.
                        requestAnimationFrame(function () {
                            rows.forEach(function (row) {
                                row.style.transition = 'none';
                                row.style.opacity = '0';
                            });
                            requestAnimationFrame(function () {
                                rows.forEach(function (row) {
                                    row.style.transition = 'opacity ' + DURATION + 'ms ' + EASE;
                                    row.style.opacity = '1';
                                });
                            });
                        });
                        // Reset inline styles once the transition has finished.
                        setTimeout(function () {
                            rows.forEach(function (row) {
                                row.style.transition = '';
                                row.style.opacity = '';
                            });
                        }, DURATION + 80);
                    } else {
                        rows.forEach(function (row) {
                            row.style.transition = 'opacity ' + DURATION + 'ms ' + EASE;
                            row.style.opacity = '0';
                        });
                        setTimeout(function () {
                            // Only hide if the group was not re-opened in the meantime.
                            if (!quotationGroups[groupId]) {
                                rows.forEach(function (row) {
                                    row.classList.add('hidden');
                                    row.style.transition = '';
                                    row.style.opacity = '';
                                });
                            }
                        }, DURATION + 60);
                    }

                    // Rotate the chevron to reflect the open/closed state.
                    var toggle = document.querySelector('[data-group-toggle][data-group-id="' + groupId + '"]');
                    var chevron = toggle ? toggle.querySelector('.preplist-group-chevron') : null;
                    if (chevron) chevron.style.transform = open ? 'rotate(180deg)' : '';
                }

                // Start with every prep-list group collapsed (no animation on load).
                Array.prototype.forEach.call(document.querySelectorAll('[data-group-toggle]'), function (toggle) {
                    var groupId = toggle.getAttribute('data-group-id');
                    quotationGroups[groupId] = false;
                    Array.prototype.forEach.call(quotationRows(groupId), function (row) {
                        row.classList.add('hidden');
                    });
                });

                // Hide the Items quoted / Status / Action columns on load since no
                // group is expanded yet.
                refreshExpandableColumns();

                Array.prototype.forEach.call(document.querySelectorAll('[data-group-toggle]'), function (toggle) {
                    var groupId = toggle.getAttribute('data-group-id');
                    toggle.addEventListener('click', function (event) {
                        event.stopPropagation();
                        setQuotationGroupOpen(groupId, !quotationGroups[groupId]);
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

                // A fixed menu would drift away from its toggle if the page or
                // table scrolls, so dismiss it instead of letting it float.
                window.addEventListener('scroll', closeMenu, true);
                window.addEventListener('resize', closeMenu);

                // Clicking anywhere on a row opens that quotation's detail page,
                // unless the click landed on the three-dot menu.
                document.querySelectorAll('tr[data-href]').forEach(function (row) {
                    row.addEventListener('click', function (event) {
                        if (event.target.closest('[data-quotation-menu-toggle]') || event.target.closest('[data-quotation-menu]')) {
                            return;
                        }
                        window.location.href = row.dataset.href;
                    });
                });
            })();
        </script>
        @endpush

        {{-- Delete quotation confirmation modal --}}
        <div id="quotationDeleteModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="quotationDeleteTitle">
            <div id="quotationDeleteOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
                <div class="flex items-start gap-3 px-5 py-4">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-50">
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 id="quotationDeleteTitle" class="text-base font-bold text-gray-900">Delete quotation?</h3>
                        <p id="quotationDeleteSummary" class="mt-1 text-sm text-gray-500"></p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                    <button type="button" id="quotationDeleteCancel"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="button" id="quotationDeleteConfirm"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors cursor-pointer">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        @push('scripts')
        <script>
            // Delete quotation confirmation modal
            (function () {
                const modal = document.getElementById('quotationDeleteModal');
                const overlay = document.getElementById('quotationDeleteOverlay');
                const cancelBtn = document.getElementById('quotationDeleteCancel');
                const confirmBtn = document.getElementById('quotationDeleteConfirm');
                const summary = document.getElementById('quotationDeleteSummary');
                let deleteForm = null;

                function openModal(btn) {
                    deleteForm = btn.closest('form');
                    summary.textContent = (btn.getAttribute('data-quotation-supplier') || 'This supplier') + ' quotation will be permanently deleted. This action cannot be undone.';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    confirmBtn.focus();
                    // The confirmation modal's overlay takes over the action area;
                    // collapse the open menu so the overlay is the only layer on top.
                    if (window.__quotationCloseMenu) window.__quotationCloseMenu();
                }
                function closeModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }

                document.querySelectorAll('[data-quotation-delete]').forEach(function (btn) {
                    btn.addEventListener('click', function () { openModal(btn); });
                });
                document.getElementById('quotationDeleteCancel').addEventListener('click', closeModal);
                document.getElementById('quotationDeleteOverlay').addEventListener('click', closeModal);
                document.getElementById('quotationDeleteConfirm').addEventListener('click', function () {
                    if (deleteForm) deleteForm.submit();
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
                });
            })();
        </script>
        @endpush

        {{-- layouts.app has no @stack('scripts') yet, so render the pushed script here. --}}
        @stack('scripts')
    </div>
@endsection
