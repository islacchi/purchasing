@extends('layouts.app')

@section('page-title', 'PURCHASE ORDER')
@section('title', 'Purchase Order - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <style>
        /* Keep the table scrolling inside its card only on very narrow
           windows; otherwise it fits the 100% zoom width. */
        #mainContent { min-width: 0; }

        /* Sticky cells create their own stacking context. Lift the whole cell
           while its menu is open so the dropdown paints above the sticky
           action cells of the rows below it. */
        td.po-menu-open { z-index: 40; }

        /* The table sits in an overflow-x-auto box (which is also a vertical
           scroll container), so an absolutely positioned dropdown is clipped
           near the last rows. While a menu is open it is anchored to the
           viewport instead, which keeps it whole and stops the scroll box
           from growing a scrollbar. */
        [data-po-menu].po-menu-floating {
            position: fixed;
            right: auto;
            bottom: auto;
            margin: 0;
        }
    </style>

    @php
        // Real records come from PurchaseOrderController@index. The view only
        // formats them; statuses are canonical (see PurchaseOrder::statusLabel).
        $statusStyles = [
            'Draft' => 'bg-gray-100 text-gray-600',
            'Sent to supplier' => 'bg-blue-100 text-blue-700',
            'Partially received' => 'bg-amber-100 text-amber-700',
            'Completed' => 'bg-green-100 text-green-700',
            'Cancelled' => 'bg-red-100 text-red-700',
        ];

        $supplierOptions = $purchaseOrders->pluck('supplier')->unique()->sort()->values();
        $statusOptions = $purchaseOrders->pluck('status')->unique()->sort()->values();

        $peso = function ($amount) {
            return '₱' . number_format($amount, 2);
        };
    @endphp

    <div class="pb-24">

        {{-- Page heading --}}
        <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
            <p class="text-xs text-gray-500">
                Orders generated from awarded supplier quotations.
            </p>
   
        </div>

        {{-- Filters --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <div class="relative md:col-span-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                        </svg>
                    </span>
                    <input type="text" id="poSearch" placeholder="Search PO number, supplier, prep list…"
                           class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm text-gray-700 placeholder-gray-400 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                </div>

                <select id="poSupplier"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                    <option value="">All suppliers</option>
                    @foreach ($supplierOptions as $supplier)
                        <option value="{{ $supplier }}">{{ $supplier }}</option>
                    @endforeach
                </select>

                <select id="poStatus"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}">{{ $status }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Success messages are rendered once, globally, by layouts.app (partials.flash-success). --}}

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Purchase order tracker --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-3 py-4">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">Purchase orders</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Track issued, partially received, and completed orders.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                    {{ $purchaseOrders->count() }} total
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th scope="col" class="whitespace-nowrap px-3 py-3">PO number</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Supplier</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Prep list</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-center">Items</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">Total</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Status</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-3">Date issued</th>
                            <th scope="col" class="sticky right-0 z-20 bg-gray-50 px-3 py-3 text-center">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($purchaseOrders as $po)
                            <tr class="group cursor-pointer transition-colors hover:bg-gray-50"
                                data-href="{{ route('purchase-order.show', ['purchaseOrder' => $po['id']]) }}"
                                data-search="{{ strtolower($po['number'] . ' ' . $po['supplier'] . ' ' . $po['prep_list'] . ' ' . $po['rfq_number']) }}"
                                data-supplier="{{ $po['supplier'] }}"
                                data-status="{{ $po['status'] }}">
                                <td class="whitespace-nowrap px-3 py-4">
                                    <p class="font-semibold text-gray-900">{{ $po['number'] }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">from quotation {{ $po['rfq_number'] }}</p>
                                </td>
                                <td class="px-3 py-4 text-gray-700">
                                    {{ $po['supplier'] }}
                                </td>
                                <td class="px-3 py-4 text-gray-700">
                                    {{ $po['prep_list'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-center text-gray-700">
                                    {{ $po['items'] }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-right font-semibold text-gray-900">
                                    {{ $peso($po['total']) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusStyles[$po['status']] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ $po['status'] }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-gray-500">
                                    {{ $po['issued'] ?? 'Not issued' }}
                                </td>
                                <td class="sticky right-0 z-10 bg-white px-3 py-4 text-center group-hover:bg-gray-50">
                                    <div class="relative inline-block text-left">
                                        <button type="button"
                                                data-po-menu-toggle
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                aria-label="Open actions for {{ $po['number'] }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/30">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                                <circle cx="12" cy="5" r="1.8"/>
                                                <circle cx="12" cy="12" r="1.8"/>
                                                <circle cx="12" cy="19" r="1.8"/>
                                            </svg>
                                        </button>

                                        <div data-po-menu
                                             class="hidden absolute right-0 top-full mt-2 w-48 rounded-lg border border-gray-200 bg-white py-1 shadow-lg z-30">
                                            <a href="{{ route('purchase-order.show', ['purchaseOrder' => $po['id']]) }}"
                                               role="menuitem"
                                               class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                                </svg>
                                                View purchase order
                                            </a>

                                            {{-- @TODO: Generate the printable PO document. --}}
                                            <button type="button" role="menuitem"
                                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50">
                                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659"/>
                                                </svg>
                                                Download PDF
                                            </button>

                                            @if ($po['cancellable'])
                                                <form method="POST" action="{{ route('purchase-order.cancel', ['purchaseOrder' => $po['id']]) }}">
                                                    @csrf
                                                    <button type="button" role="menuitem"
                                                            data-po-cancel
                                                            data-po-number="{{ $po['number'] }}"
                                                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                        </svg>
                                                        Cancel order
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($po['deletable'] && auth()->user()?->isAdministrator())
                                                <form method="POST" action="{{ route('purchase-order.destroy', ['purchaseOrder' => $po['id']]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" role="menuitem"
                                                            data-po-delete
                                                            data-po-number="{{ $po['number'] }}"
                                                            class="flex w-full items-center gap-2 border-t border-gray-100 px-3 py-2 text-left text-sm text-red-700 hover:bg-red-50">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                                                        </svg>
                                                        Delete order
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-sm text-gray-500">
                                    No purchase orders yet. Purchase orders are created from a supplier quotation using “Proceed to PO”.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div id="poEmptyState" class="hidden px-6 py-12 text-center">
                <p class="text-sm font-medium text-gray-600">No purchase orders match the current filters.</p>
                <p class="mt-1 text-xs text-gray-500">Try clearing the search or choosing another status.</p>
            </div>

            <div class="border-t border-gray-100 px-3 py-3">
                <p id="poCount" class="text-xs text-gray-500">Showing {{ $purchaseOrders->count() }} of {{ $purchaseOrders->count() }} purchase orders</p>
            </div>
        </div>

        {{-- Cancel order confirmation modal --}}
        <div id="poCancelModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="poCancelTitle">
            <div id="poCancelOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <form id="poCancelForm" method="POST" class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
                @csrf
                <div class="flex items-start gap-3 px-5 py-4">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-50">
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 id="poCancelTitle" class="text-base font-bold text-gray-900">Cancel purchase order?</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <span id="poCancelNumber" class="font-medium text-gray-700"></span>
                            will be cancelled. The supplier should be informed as well.
                        </p>
                        <label for="poCancelReason" class="mt-3 block text-xs font-medium text-gray-600">Reason (optional)</label>
                        <input type="text" id="poCancelReason" name="reason" maxlength="255"
                               class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-100"
                               placeholder="e.g. quantities were renegotiated">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                    <button type="button" id="poCancelDismiss"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                        Keep order
                    </button>
                    <button type="submit"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors cursor-pointer">
                        Cancel order
                    </button>
                </div>
            </form>
        </div>

        {{-- Delete order confirmation modal --}}
        <div id="poDeleteModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="poDeleteTitle">
            <div id="poDeleteOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <form id="poDeleteForm" method="POST" class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
                @csrf
                @method('DELETE')
                <div class="flex items-start gap-3 px-5 py-4">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-50">
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 id="poDeleteTitle" class="text-base font-bold text-gray-900">Delete purchase order?</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <span id="poDeleteNumber" class="font-medium text-gray-700"></span>
                            will be permanently deleted along with its lines and allocations. This action cannot be undone.
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                    <button type="button" id="poDeleteDismiss"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                        Keep order
                    </button>
                    <button type="submit"
                            class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors cursor-pointer">
                        Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            // --- Three-dot action menus ---
            const triggers = Array.from(document.querySelectorAll('[data-po-menu-toggle]'));
            const MENU_GAP = 8;      // space between the button and the menu
            const VIEW_MARGIN = 8;   // keep the menu this far from the viewport edge

            function closeMenus() {
                triggers.forEach(function (trigger) {
                    const menu = trigger.parentElement.querySelector('[data-po-menu]');
                    menu.classList.add('hidden');
                    menu.classList.remove('po-menu-floating');
                    menu.style.top = '';
                    menu.style.left = '';
                    trigger.setAttribute('aria-expanded', 'false');
                    trigger.closest('td').classList.remove('po-menu-open');
                });
            }

            function openMenu(trigger, menu) {
                // Show it first so its real height can be measured.
                menu.classList.remove('hidden');
                menu.classList.add('po-menu-floating');

                const triggerRect = trigger.getBoundingClientRect();
                const menuWidth = menu.offsetWidth;
                const menuHeight = menu.offsetHeight;

                // Prefer opening below the button. Flip above only when the
                // menu would run off the bottom and there is more room up top.
                const spaceBelow = window.innerHeight - triggerRect.bottom - MENU_GAP - VIEW_MARGIN;
                const spaceAbove = triggerRect.top - MENU_GAP - VIEW_MARGIN;
                const opensUp = menuHeight > spaceBelow && spaceAbove > spaceBelow;

                let top = opensUp
                    ? triggerRect.top - menuHeight - MENU_GAP
                    : triggerRect.bottom + MENU_GAP;

                // Keep the whole menu inside the viewport.
                top = Math.max(VIEW_MARGIN, Math.min(top, window.innerHeight - menuHeight - VIEW_MARGIN));

                // Align its right edge with the button, then clamp horizontally.
                let left = triggerRect.right - menuWidth;
                left = Math.max(VIEW_MARGIN, Math.min(left, window.innerWidth - menuWidth - VIEW_MARGIN));

                menu.style.top = top + 'px';
                menu.style.left = left + 'px';

                trigger.setAttribute('aria-expanded', 'true');
                trigger.closest('td').classList.add('po-menu-open');
            }

            triggers.forEach(function (trigger) {
                trigger.addEventListener('click', function (event) {
                    event.stopPropagation();

                    const menu = trigger.parentElement.querySelector('[data-po-menu]');
                    const isOpen = !menu.classList.contains('hidden');

                    closeMenus();

                    if (!isOpen) {
                        openMenu(trigger, menu);
                    }
                });
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('[data-po-menu]') && !event.target.closest('[data-po-menu-toggle]')) {
                    closeMenus();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenus();
                }
            });

            // The menu is anchored to the viewport while open, so close it
            // rather than let it drift away from its row.
            window.addEventListener('scroll', closeMenus, true);
            window.addEventListener('resize', closeMenus);

            // --- Row navigation ---
            document.querySelectorAll('tr[data-href]').forEach(function (row) {
                row.addEventListener('click', function (event) {
                    if (event.target.closest('[data-po-menu-toggle]') || event.target.closest('[data-po-menu]')) {
                        return;
                    }
                    window.location.href = row.dataset.href;
                });
            });

            // --- Filters ---
            const searchInput = document.getElementById('poSearch');
            const supplierInput = document.getElementById('poSupplier');
            const statusInput = document.getElementById('poStatus');
            const rows = Array.from(document.querySelectorAll('tr[data-href]'));
            const emptyState = document.getElementById('poEmptyState');
            const countLabel = document.getElementById('poCount');

            function applyFilters() {
                const term = searchInput.value.trim().toLowerCase();
                const supplier = supplierInput.value;
                const status = statusInput.value;
                let visible = 0;

                rows.forEach(function (row) {
                    const match = (!term || row.dataset.search.indexOf(term) !== -1)
                        && (!supplier || row.dataset.supplier === supplier)
                        && (!status || row.dataset.status === status);

                    row.classList.toggle('hidden', !match);
                    if (match) {
                        visible++;
                    }
                });

                emptyState.classList.toggle('hidden', visible > 0);
                countLabel.textContent = 'Showing ' + visible + ' of ' + rows.length + ' purchase orders';
            }

            searchInput.addEventListener('input', applyFilters);
            supplierInput.addEventListener('change', applyFilters);
            statusInput.addEventListener('change', applyFilters);

            // --- Cancel order modal ---
            const cancelModal = document.getElementById('poCancelModal');
            const cancelForm = document.getElementById('poCancelForm');
            const cancelNumber = document.getElementById('poCancelNumber');
            const cancelOverlay = document.getElementById('poCancelOverlay');
            const cancelDismiss = document.getElementById('poCancelDismiss');

            function closeCancelModal() {
                cancelModal.classList.add('hidden');
                cancelModal.classList.remove('flex');
            }

            Array.prototype.forEach.call(document.querySelectorAll('[data-po-cancel]'), function (btn) {
                btn.addEventListener('click', function () {
                    cancelForm.action = btn.closest('form').action;
                    cancelNumber.textContent = btn.getAttribute('data-po-number') || 'This purchase order';
                    cancelModal.classList.remove('hidden');
                    cancelModal.classList.add('flex');
                    if (closeMenus) closeMenus();
                });
            });

            if (cancelOverlay) cancelOverlay.addEventListener('click', closeCancelModal);
            if (cancelDismiss) cancelDismiss.addEventListener('click', closeCancelModal);

            // --- Delete order modal ---
            const deleteModal = document.getElementById('poDeleteModal');
            const deleteForm = document.getElementById('poDeleteForm');
            const deleteNumber = document.getElementById('poDeleteNumber');
            const deleteOverlay = document.getElementById('poDeleteOverlay');
            const deleteDismiss = document.getElementById('poDeleteDismiss');

            function closeDeleteModal() {
                deleteModal.classList.add('hidden');
                deleteModal.classList.remove('flex');
            }

            Array.prototype.forEach.call(document.querySelectorAll('[data-po-delete]'), function (btn) {
                btn.addEventListener('click', function () {
                    deleteForm.action = btn.closest('form').action;
                    deleteNumber.textContent = btn.getAttribute('data-po-number') || 'This purchase order';
                    deleteModal.classList.remove('hidden');
                    deleteModal.classList.add('flex');
                    closeMenus();
                });
            });

            if (deleteOverlay) deleteOverlay.addEventListener('click', closeDeleteModal);
            if (deleteDismiss) deleteDismiss.addEventListener('click', closeDeleteModal);

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeCancelModal();
                    closeDeleteModal();
                }
            });
        })();
    </script>
@endsection


