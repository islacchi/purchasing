@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', 'Generate Quotation - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <style>
        /* Selected supplier chips use a dedicated state class instead of
           dynamically added Tailwind utilities. This prevents the base
           bg-white / text-gray-700 classes from winning based on CSS order. */
        .supplier-token.is-selected {
            background-color: #2a7a94;
            border-color: #2a7a94;
            box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        }
        .supplier-token.is-selected:hover {
            background-color: #236b80; /* darker teal */
        }
        .supplier-token.is-selected .supplier-chip-label {
            color: #fff;
        }
        .supplier-token.is-selected .chip-box {
            display: none;
        }
        .supplier-token.is-selected .chip-check {
            display: block;
        }
    </style>


    {{-- The prep list, its item totals, suppliers, and initial selection state
         are supplied by PreplistController@generateRfq. --}}

    <div class="flex h-full min-h-0 flex-col">
    {{-- Page heading --}}
    <div class="shrink-0 mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Generate quotation - {{ $prepList->name }}</h2>
        <p class="text-sm text-gray-500 mt-1">Choose which suppliers to ask for each item</p>
    </div>

    {{-- Quick select — one chip per supplier; toggling one checks that supplier on every item --}}
    <div class="shrink-0 rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
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
        @if($suppliers->isEmpty())
            <p class="text-sm text-gray-500">No suppliers found. Add suppliers from the Entities module first.</p>
        @endif
    </div>

    {{-- Divider --}}
    <div class="flex shrink-0 items-center gap-4 my-6">
        <div class="flex-1 border-t border-gray-200"></div>
        <span class="text-xs font-medium text-gray-400 uppercase tracking-wider">or choose per item</span>
        <div class="flex-1 border-t border-gray-200"></div>
    </div>
    {{-- Per-item sections (item list scrolls here) — one box per prep list item --}}
    <div class="min-h-[180px] flex-1 space-y-4 overflow-y-auto pr-1">
        @forelse($items as $item)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-gray-100">
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900">{{ $item['name'] }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            @if(!empty($item['shelf_life']))
                                Shelf life required:
                                <span class="font-medium text-gray-700">&ge; {{ $item['shelf_life'] }} months remaining at delivery</span>
                            @else
                                Non-perishable &mdash; no shelf-life requirement
                            @endif
                        </p>
                    </div>
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
                                       {{ ($initialItemSelections[$item['key']][$supplier['name']] ?? false) ? 'checked' : '' }}
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
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
                <h3 class="text-base font-semibold text-gray-900">No items to request</h3>
                <p class="mt-1 text-sm text-gray-500">This prep list has no compiled items yet. Edit the prep list to add project items first.</p>
            </div>
        @endforelse
    </div>

    {{-- Quotation files to generate — one row per supplier with at least one checked item.
         The checkbox toggles file generation; the supplier name opens the preview modal.
         NOTE: the checkbox and the supplier name are separate sibling elements with their
         own click handlers (never wrapped in a single <label>), so neither triggers the
         other's behavior. --}}
    <div class="shrink-0 rounded-lg border border-gray-200 bg-white p-5 mt-6 shadow-sm">
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
            Suppliers receive the item list and the minimum shelf-life requirement only — our quantities stay internal and are never included in the generated files.
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
    <div class="flex shrink-0 items-center justify-end gap-3 mt-6">
        <a href="{{ route('preplist.show', ['preplist' => $prepList->id]) }}"
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
    </div><!-- /flex column -->
    {{-- Hidden token for the "Generate File" fetch request. --}}
    <input type="hidden" id="generateRfqToken" value="{{ csrf_token() }}">
    <script>
        // ------------------------------------------------------------------
        // Chip toggling, the dynamic "Quotation files to generate" list, the
        // supplier preview modal, and the Excel export endpoint. The export
        // posts the checked supplier / item selections to the server, which
        // re-reads every item from the prep list and streams back one .xlsx
        // per supplier (or a ZIP archive containing one workbook per supplier).
        // ------------------------------------------------------------------
        (function () {
            'use strict';

            const GENERATE_URL = @json(route('preplist.generate-rfq.excel', ['preplist' => $prepList->id]));
            const PREP_LIST_REFERENCE = @json($prepList->reference_no);

            // Item metadata lookup, keyed by each per-item chip's data-item-key.
            const ITEM_DATA_BY_KEY = {};
            @json($items).forEach(function (item) {
                ITEM_DATA_BY_KEY[item.key] = { name: item.name, total: item.total, unit: item.unit, shelf_life: item.shelf_life };
            });

            // ------------------------------------------------------------------
            // Chip visuals — reflect each hidden checkbox state on its token.
            // The selected styling lives in the .is-selected CSS rules above,
            // so it always wins over the chip's base white/gray classes.
            // ------------------------------------------------------------------
            function applyChipState(input) {
                const token = input.closest('.supplier-token');
                if (token) {
                    token.classList.toggle('is-selected', input.checked);
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
                        row.className = 'py-2.5 border-b border-gray-100 last:border-0';

                        const name = document.createElement('p');
                        name.className = 'text-sm text-gray-800';
                        name.textContent = item.name;

                        // Quantities are intentionally omitted: suppliers quote a
                        // unit price per item without knowing how much we need.
                        // The shelf-life requirement is a quality condition, so it
                        // is shown to the supplier.
                        const condition = document.createElement('p');
                        condition.className = 'mt-0.5 text-xs text-gray-500';
                        condition.textContent = item.shelf_life
                            ? '\u2265 ' + item.shelf_life + ' months remaining at delivery'
                            : 'Non-perishable \u2014 no shelf-life requirement';

                        row.appendChild(name);
                        row.appendChild(condition);
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
            // Generate File — produces an Excel workbook for every supplier
            // checked in "Quotation files to generate". Each workbook contains
            // ONLY that supplier's checked items, read back from the database.
            //
            // Quantities are deliberately NOT included: suppliers only receive
            // the item list and quote a unit price. Our requested quantities
            // stay internal and are captured on the purchase order.
            //
            // The minimum shelf life IS included per item, as a supply
            // condition (">= X months remaining at delivery"), because
            // suppliers must quote against the real requirement.
            //
            // One supplier selected → a single .xlsx download. Multiple
            // suppliers → a ZIP archive containing one .xlsx per supplier.
            // ------------------------------------------------------------------
            const generateFileBtn = document.getElementById('generateFileBtn');
            const generateFileBtnLabel = generateFileBtn.innerHTML;
            const csrfToken = document.getElementById('generateRfqToken').value;

            function filenameFromDisposition(header, fallback) {
                if (!header) return fallback;
                const match = header.match(/filename="?([^";]+)"?/i);
                return match ? match[1] : fallback;
            }

            generateFileBtn.addEventListener('click', function () {
                const payload = [];

                document.querySelectorAll('.gen-row:not(.hidden) .gen-check:checked').forEach(function (cb) {
                    const row = cb.closest('.gen-row');
                    const supplier = row.dataset.supplier;
                    const itemIds = [];

                    document.querySelectorAll('.item-supplier-check[data-supplier="' + CSS.escape(supplier) + '"]:checked').forEach(function (itemCb) {
                        const id = Number.parseInt(itemCb.dataset.itemKey, 10);
                        if (!Number.isNaN(id)) {
                            itemIds.push(id);
                        }
                    });

                    payload.push({ supplier: supplier, items: itemIds });
                });

                if (payload.length === 0) {
                    window.alert('Tick at least one supplier in "Quotation files to generate" first.');
                    return;
                }

                generateFileBtn.disabled = true;
                generateFileBtn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Generating…';

                fetch(GENERATE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/octet-stream',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ files: payload }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            return response.text().then(function (body) {
                                let message = 'Unable to generate the quotation files.';
                                try {
                                    const data = JSON.parse(body);
                                    if (data && data.message) {
                                        message = data.message;
                                    }
                                } catch (ignored) {
                                    // Not a JSON error body — keep the generic message.
                                }
                                throw new Error(message);
                            });
                        }

                        const fallback = 'RFQ-' + PREP_LIST_REFERENCE + (payload.length > 1 ? '.zip' : '.xlsx');
                        const filename = filenameFromDisposition(response.headers.get('Content-Disposition'), fallback);

                        return response.blob().then(function (blob) {
                            return { blob: blob, filename: filename };
                        });
                    })
                    .then(function (result) {
                        const url = URL.createObjectURL(result.blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = result.filename;
                        document.body.appendChild(link);
                        link.click();
                        link.remove();
                        URL.revokeObjectURL(url);
                    })
                    .catch(function (error) {
                        window.alert(error && error.message ? error.message : 'Unable to generate the quotation files.');
                    })
                    .finally(function () {
                        generateFileBtn.disabled = false;
                        generateFileBtn.innerHTML = generateFileBtnLabel;
                    });
            });

            // Initial render.
            syncGenerateList();
        })();
    </script>

@endsection