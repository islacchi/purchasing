@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', 'Generate Quotation - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <style>
        /* Keep a checked supplier chip readable on hover —
           the base hover:bg-gray-50 would turn it near-white while
           the label stays text-white. Use a slightly darker teal
           instead, which also gives clear hover feedback. */
        .supplier-token.bg-\[\#2a7a94\]:hover {
            background-color: #236b80; /* darker teal */
        }
    </style>


    @php
        // ------------------------------------------------------------------
        // Dummy data for frontend development (no DB integration yet).
        //
        // @TODO: Replace the hardcoded prep list / items / suppliers below with
        // real Eloquent queries once the preplist + supplier DB tables exist,
        // e.g.:
        //   $prepList  = Preplist::with('items')->findOrFail($id);
        //   $items     = $prepList->items;
        //   $suppliers = Supplier::query()->get();
        // ------------------------------------------------------------------

        // @TODO: replace with Preplist::findOrFail($id); the heading below
        // renders $prepList->name.
        $prepList = (object) [
            'id'   => 1,
            'name' => 'July Region VII batch',
        ];

        // @TODO: replace with Preplist::find($id)->items; each item carries its
        // own distinct total quantity — do not reuse one total for every item.
        $items = [
            [
                'key'   => 'amoxicillin',
                'name'  => 'Amoxicillin 500mg, box of 100',
                'total' => 100,
                'unit'  => 'box',
            ],
            [
                'key'   => 'surgical-gloves',
                'name'  => 'Surgical gloves, box 50 pairs',
                'total' => 40,
                'unit'  => 'box',
            ],
        ];

        // @TODO: replace with Supplier::query()->get() (mirrors the Entities module).
        $suppliers = [
            ['name' => 'Triple Tact'],
            ['name' => 'GreenCore Pharmaceuticals'],
            ['name' => 'MedSource Inc.'],
        ];

        // Initial per-item chip state (dummy). Triple Tact and GreenCore are
        // checked on every item; MedSource is unchecked everywhere.
        // @TODO: seed from the real preplist item / supplier state once Eloquent exists.
        $initialItemSelections = [
            'amoxicillin'     => ['Triple Tact' => true, 'GreenCore Pharmaceuticals' => true, 'MedSource Inc.' => false],
            'surgical-gloves' => ['Triple Tact' => true, 'GreenCore Pharmaceuticals' => true, 'MedSource Inc.' => false],
        ];

        // Suppliers that start with at least one checked item — their rows are visible
        // (and by default checked) in "Quotation files to generate".
        $initialGenSuppliers = [];
        foreach ($items as $item) {
            foreach ($suppliers as $supplier) {
                if (!empty($initialItemSelections[$item['key']][$supplier['name']])) {
                    $initialGenSuppliers[$supplier['name']] = true;
                }
            }
        }
    @endphp

    {{-- Page heading --}}
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Generate quotation - {{ $prepList->name }}</h2>
        <p class="text-sm text-gray-500 mt-1">Choose which suppliers to ask for each item</p>
    </div>

    {{-- Quick select — one chip per supplier; toggling one checks that supplier on every item --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">
            Quick select - request the whole prep list from a supplier
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($suppliers as $supplier)
                <label class="supplier-token flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 bg-white cursor-pointer select-none transition-all hover:border-[#2a7a94] hover:bg-gray-50 focus-within:ring-2 focus-within:ring-teal-200">
                    <input type="checkbox" class="quick-supplier-check sr-only" data-supplier="{{ $supplier['name'] }}"
                           title="Request the whole prep list from {{ $supplier['name'] }}">
                    <svg class="chip-box w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="2"/>
                    </svg>
                    <svg class="chip-check w-4 h-4 text-white hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="2"/>
                        <path d="M9 12l2 2 4-4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span class="supplier-chip-label text-sm font-medium text-gray-700">{{ $supplier['name'] }} - All Items</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Divider --}}
    <div class="flex items-center gap-4 my-6">
        <div class="flex-1 border-t border-gray-200"></div>
        <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">or choose per item</span>
        <div class="flex-1 border-t border-gray-200"></div>
    </div>
    {{-- Per-item sections — one box per prep list item --}}
    <div class="space-y-4">
        @foreach($items as $item)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-gray-100">
                    <p class="font-bold text-gray-900">{{ $item['name'] }}</p>
                    <span class="text-sm font-semibold text-gray-900 whitespace-nowrap">{{ $item['total'] }} {{ $item['unit'] }} total</span>
                </div>
                <div class="px-5 py-4">
                    <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">Suppliers</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($suppliers as $supplier)
                            <label class="supplier-token flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 bg-white cursor-pointer select-none transition-all hover:border-[#2a7a94] hover:bg-gray-50 focus-within:ring-2 focus-within:ring-teal-200">
                                <input type="checkbox"
                                       class="item-supplier-check sr-only"
                                       data-supplier="{{ $supplier['name'] }}"
                                       data-item-key="{{ $item['key'] }}"
                                       {{ $initialItemSelections[$item['key']][$supplier['name']] ? 'checked' : '' }}
                                       title="Ask {{ $supplier['name'] }} for {{ $item['name'] }}">
                                <svg class="chip-box w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="2"/>
                                </svg>
                                <svg class="chip-check w-4 h-4 text-white hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="3" y="3" width="18" height="18" rx="3" stroke-width="2"/>
                                    <path d="M9 12l2 2 4-4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <span class="supplier-chip-label text-sm font-medium text-gray-700">{{ $supplier['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Quotation files to generate — one row per supplier with at least one checked item.
         The checkbox toggles file generation; the supplier name opens the preview modal.
         NOTE: the checkbox and the supplier name are separate sibling elements with their
         own click handlers (never wrapped in a single <label>), so neither triggers the
         other's behavior. --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 mt-6">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">Quotation files to generate</p>
        <div class="divide-y divide-gray-100 rounded-lg border border-gray-200 overflow-hidden bg-white">
            @foreach($suppliers as $supplier)
                <div class="gen-row flex items-center gap-3 px-4 py-3 transition-colors hover:bg-gray-50 {{ isset($initialGenSuppliers[$supplier['name']]) ? '' : 'hidden' }}"
                     data-supplier="{{ $supplier['name'] }}">
                    <input type="checkbox"
                           class="gen-check w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300 cursor-pointer"
                           title="Generate PDF + Excel for {{ $supplier['name'] }}"
                           {{ isset($initialGenSuppliers[$supplier['name']]) ? 'checked' : '' }}>
                    <button type="button"
                            class="gen-open-modal flex-1 text-left text-sm font-medium text-gray-800 hover:text-[#2a7a94] hover:underline cursor-pointer"
                            title="Preview the items selected for {{ $supplier['name'] }}">
                        {{ $supplier['name'] }}
                    </button>
                </div>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-gray-400">
            Tick a supplier's checkbox to include its files when you click Generate File, or click the supplier's name to preview its selected items.
        </p>
    </div>
    {{-- Supplier item preview modal (read-only) --}}
    <div id="supplierModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="supplierModalTitle">
        <div id="supplierModalOverlay" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md max-h-[80vh] flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h3 id="supplierModalTitle" class="text-lg font-bold text-gray-900">Supplier</h3>
                <button type="button" id="supplierModalClose" class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer" title="Close" aria-label="Close">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div id="supplierModalBody" class="px-5 py-4 overflow-y-auto flex-1">
                <p class="text-sm text-gray-500">No items selected for this supplier</p>
            </div>

            <div class="flex items-center justify-end px-5 py-4 border-t border-gray-100">
                <button type="button" id="supplierModalCloseFooter"
                        class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    {{-- Footer actions --}}
    <div class="flex items-center justify-end gap-3 mt-8">
        <a href="{{ route('preplist') }}"
           class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Cancel
        </a>
        <button type="button" id="generateFileBtn"
                class="flex items-center gap-2 px-4 py-2 rounded-md bg-[#0e5266] text-white text-sm font-medium hover:bg-[#0c4757] transition-colors cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Generate File
        </button>
    </div>
    <script>
        // ------------------------------------------------------------------
        // Frontend-only logic: chip toggling, the dynamic "Quotation files to
        // generate" list, the supplier preview modal, and a stub for the actual
        // PDF + Excel generation.
        // @TODO: once models + controller exist, persist the per-item supplier
        // choices and wire the generation below to real endpoints.
        // ------------------------------------------------------------------
        (function () {
            'use strict';

            // Item metadata lookup, keyed by each per-item chip's data-item-key.
            const ITEM_DATA_BY_KEY = {};
            @json($items).forEach(function (item) {
                ITEM_DATA_BY_KEY[item.key] = { name: item.name, total: item.total, unit: item.unit };
            });

            // ------------------------------------------------------------------
            // Chip visuals — reflect each hidden checkbox state on its token.
            // ------------------------------------------------------------------
            function applyChipState(input) {
                const token = input.closest('.supplier-token');
                if (!token) return;
                const box = token.querySelector('.chip-box');
                const check = token.querySelector('.chip-check');
                const label = token.querySelector('.supplier-chip-label');

                if (input.checked) {
                    token.classList.add('border-[#2a7a94]', 'bg-[#2a7a94]', 'shadow-sm');
                    box.classList.add('hidden');
                    check.classList.remove('hidden');
                    label.classList.add('text-white');
                } else {
                    token.classList.remove('border-[#2a7a94]', 'bg-[#2a7a94]', 'shadow-sm');
                    box.classList.remove('hidden');
                    check.classList.add('hidden');
                    label.classList.remove('text-white');
                }
            }

            document.querySelectorAll('.supplier-token input[type="checkbox"]').forEach(function (input) {
                applyChipState(input);
                input.addEventListener('change', function () {
                    applyChipState(this);
                });
            });

            // ------------------------------------------------------------------
            // Quick select — ticking a chip checks that supplier on every item.
            // Each per-item chip afterwards stays independent of Quick Select.
            // ------------------------------------------------------------------
            document.querySelectorAll('.quick-supplier-check').forEach(function (cb) {
                cb.addEventListener('change', function () {
                    const supplier = cb.dataset.supplier;
                    document.querySelectorAll('.item-supplier-check[data-supplier="' + CSS.escape(supplier) + '"]').forEach(function (itemCb) {
                        itemCb.checked = cb.checked;
                        applyChipState(itemCb);
                    });
                    syncGenerateList();
                });
            });

            // ------------------------------------------------------------------
            // "Quotation files to generate" — shows one row per supplier that has
            // at least one checked item. Each row's checkbox remembers the user's
            // explicit choice; newly-appearing suppliers default to a checked row.
            // ------------------------------------------------------------------
            const genState = {}; // supplier -> user's explicit generate yes/no

            function anyItemCheckedFor(supplier) {
                return document.querySelector('.item-supplier-check[data-supplier="' + CSS.escape(supplier) + '"]:checked') !== null;
            }

            function syncGenerateList() {
                document.querySelectorAll('.gen-row').forEach(function (row) {
                    const supplier = row.dataset.supplier;
                    const checkbox = row.querySelector('.gen-check');
                    const hasItems = anyItemCheckedFor(supplier);

                    row.classList.toggle('hidden', !hasItems);
                    if (!hasItems) return;

                    checkbox.checked = Object.prototype.hasOwnProperty.call(genState, supplier)
                        ? genState[supplier]
                        : true;
                });
            }

            document.querySelectorAll('.gen-check').forEach(function (cb) {
                // The checkbox and the supplier name are separate siblings, each with
                // its own handler. stopPropagation keeps one from triggering the other.
                cb.addEventListener('click', function (e) {
                    e.stopPropagation();
                });
                cb.addEventListener('change', function () {
                    const row = cb.closest('.gen-row');
                    if (row) genState[row.dataset.supplier] = cb.checked;
                });
            });

            // Per-item chips only re-derive the generate list — nothing else.
            document.querySelectorAll('.item-supplier-check').forEach(function (cb) {
                cb.addEventListener('change', syncGenerateList);
            });
    // ------------------------------------------------------------------
            // Supplier preview modal — read-only. It never changes chip/checkbox
            // state and never triggers file generation itself.
            // ------------------------------------------------------------------
            const modal = document.getElementById('supplierModal');
            const modalTitle = document.getElementById('supplierModalTitle');
            const modalBody = document.getElementById('supplierModalBody');

            document.querySelectorAll('.gen-open-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const row = btn.closest('.gen-row');
                    if (row) openSupplierModal(row.dataset.supplier);
                });
            });

            function openSupplierModal(supplier) {
                modalTitle.textContent = supplier;

                const rows = [];
                document.querySelectorAll('.item-supplier-check[data-supplier="' + CSS.escape(supplier) + '"]:checked').forEach(function (itemCb) {
                    const item = ITEM_DATA_BY_KEY[itemCb.dataset.itemKey];
                    if (item) rows.push(item);
                });

                modalBody.replaceChildren();
                if (rows.length === 0) {
                    const p = document.createElement('p');
                    p.className = 'text-sm text-gray-500';
                    p.textContent = 'No items selected for this supplier';
                    modalBody.appendChild(p);
                } else {
                    rows.forEach(function (item) {
                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between gap-4 py-2.5 border-b border-gray-100 last:border-0';

                        const name = document.createElement('p');
                        name.className = 'text-sm text-gray-800';
                        name.textContent = item.name;

                        const qty = document.createElement('p');
                        qty.className = 'text-sm font-semibold text-gray-900 whitespace-nowrap';
                        qty.textContent = item.total + ' ' + item.unit;

                        row.appendChild(name);
                        row.appendChild(qty);
                        modalBody.appendChild(row);
                    });
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            }

            function closeSupplierModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = '';
            }

            document.getElementById('supplierModalClose').addEventListener('click', closeSupplierModal);
            document.getElementById('supplierModalCloseFooter').addEventListener('click', closeSupplierModal);
            document.getElementById('supplierModalOverlay').addEventListener('click', closeSupplierModal);
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeSupplierModal();
            });

            // ------------------------------------------------------------------
            // Generate File — ONE click produces BOTH a PDF and an Excel file for
            // every supplier checked in "Quotation files to generate". There is
            // no separate PDF button and no separate Excel button.
            //
            // @TODO: replace this stub with the real generation logic. It must
            // emit ONE pair of files (PDF + Excel) PER checked supplier, each
            // containing ONLY that supplier's checked items and their quantities.
            // ------------------------------------------------------------------
            document.getElementById('generateFileBtn').addEventListener('click', function () {
                const payload = [];

                document.querySelectorAll('.gen-row:not(.hidden) .gen-check:checked').forEach(function (cb) {
                    const row = cb.closest('.gen-row');
                    const supplier = row.dataset.supplier;
                    const items = [];

                    document.querySelectorAll('.item-supplier-check[data-supplier="' + CSS.escape(supplier) + '"]:checked').forEach(function (itemCb) {
                        const item = ITEM_DATA_BY_KEY[itemCb.dataset.itemKey];
                        if (item) {
                            items.push({ name: item.name, qty: item.total + ' ' + item.unit });
                        }
                    });

                    payload.push({ supplier: supplier, items: items });
                });

                console.log('Generate File clicked — checked suppliers:', payload);
                // @TODO: PDF + Excel generation goes here (one file pair per checked supplier).
            });

            // Initial render.
            syncGenerateList();
        })();
    </script>

@endsection