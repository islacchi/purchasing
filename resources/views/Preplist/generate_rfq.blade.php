@extends('layouts.app')

@section('page-title', 'PREP LIST')
@section('title', 'Generate Quotation - Purchasing')

@section('header-actions')
@endsection

@section('content')



    {{-- The prep list, its item totals, suppliers, and initial selection state
         are supplied by PreplistController@generateRfq. --}}

    <div class="flex h-full min-h-0 flex-col">
    {{-- Page heading --}}
    @php
        $rfqUnitTotal = $items->sum('total');
    @endphp
    <div class="shrink-0 mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Generate RFQ - {{ $prepList->name }}</h2>
        <p class="text-sm text-gray-500 mt-1">Choose which suppliers to ask for each item</p>
        <div class="mt-3 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-gray-600">
            <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 shadow-sm">
                <span class="font-bold text-gray-900">{{ $items->count() }}</span>
                <span class="ml-1">{{ $items->count() === 1 ? 'item' : 'items' }}</span>
            </span>
            <span class="text-gray-400" aria-hidden="true">&middot;</span>
            <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 shadow-sm">
                <span class="font-bold text-gray-900">{{ number_format($rfqUnitTotal) }}</span>
                <span class="ml-1">{{ $rfqUnitTotal === 1 ? 'unit' : 'units' }} to procure</span>
            </span>
            <span class="text-gray-400" aria-hidden="true">&middot;</span>
            <span class="inline-flex items-center rounded-full border border-gray-200 bg-white px-3 py-1 shadow-sm">
                <span class="font-bold text-gray-900">{{ $suppliers->count() }}</span>
                <span class="ml-1">{{ $suppliers->count() === 1 ? 'supplier' : 'suppliers' }}</span>
            </span>
            <span class="text-gray-400" aria-hidden="true">&middot;</span>
            <span class="text-gray-500">Ref {{ $prepList->reference_no }}</span>
        </div>
    </div>

    {{-- Quick select — one chip per supplier; toggling one checks that supplier on every item --}}
        @if($suppliers->isEmpty())
        <div class="shrink-0 mb-4 rounded-lg border border-gray-200 bg-white p-5 text-sm text-gray-500 shadow-sm">
            No suppliers found. Add suppliers from the Entities module first.
        </div>
    @endif

    <p class="shrink-0 mb-2 text-xs text-gray-500">
        Tick a supplier's header box to request every item from them, or tick individual cells.
    </p>

    {{-- Selection grid: rows = items, columns = suppliers --}}
    <div class="min-h-0 flex-1 overflow-auto overscroll-contain rounded-lg border border-gray-200 bg-white shadow-sm">
        @if($items->isEmpty())
            <div class="px-6 py-12 text-center">
                <h3 class="text-base font-semibold text-gray-900">No items to request</h3>
                <p class="mt-1 text-sm text-gray-500">This prep list has no compiled items yet. Edit the prep list to add project items first.</p>
            </div>
        @else
            <table class="w-full border-separate border-spacing-0 text-sm">
                <thead>
                    <tr>
                        <th class="sticky left-0 top-0 z-30 min-w-[280px] border-b border-r border-gray-200 bg-gray-50 px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                            Item
                        </th>
                        @foreach($suppliers as $supplier)
                            <th class="sticky top-0 z-20 min-w-[132px] border-b border-gray-200 bg-gray-50 px-3 py-3 text-center font-normal">
                                <label class="flex cursor-pointer flex-col items-center gap-1">
                                    <input type="checkbox"
                                           class="supplier-all-check h-4 w-4 cursor-pointer rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                           data-supplier="{{ $supplier['name'] }}"
                                           title="Request every item from {{ $supplier['name'] }}">
                                    <span class="text-xs font-semibold leading-tight text-gray-800">{{ $supplier['name'] }}</span>
                                    <span class="supplier-count text-[11px] text-gray-400" data-supplier="{{ $supplier['name'] }}">0 / {{ $items->count() }}</span>
                                </label>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="group">
                            <th scope="row" class="sticky left-0 z-10 border-b border-r border-gray-100 bg-white px-4 py-3 text-left font-normal group-hover:bg-gray-50">
                                <p class="break-words font-semibold text-gray-900">{{ $item['name'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    @if(!empty($item['shelf_life']))
                                        &ge; {{ $item['shelf_life'] }} months remaining at delivery
                                    @else
                                        Non-perishable
                                    @endif
                                    <span class="mx-1 text-gray-300">&middot;</span>
                                    {{ $item['total'] }} {{ $item['unit'] }} to procure
                                </p>
                            </th>
                            @foreach($suppliers as $supplier)
                                <td class="border-b border-gray-100 p-0 text-center group-hover:bg-gray-50">
                                    <label class="flex h-full w-full cursor-pointer items-center justify-center px-3 py-3">
                                        <input type="checkbox"
                                               class="item-supplier-check h-4 w-4 cursor-pointer rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                               data-supplier="{{ $supplier['name'] }}"
                                               data-item-key="{{ $item['key'] }}"
                                               aria-label="Ask {{ $supplier['name'] }} for {{ $item['name'] }}"
                                               {{ ($initialItemSelections[$item['key']][$supplier['name']] ?? false) ? 'checked' : '' }}>
                                    </label>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- RFQ files to generate — one row per supplier with at least one checked item.
         The checkbox toggles file generation; the supplier name opens the preview modal.
         NOTE: the checkbox and the supplier name are separate sibling elements with their
         own click handlers (never wrapped in a single <label>), so neither triggers the
         other's behavior. --}}
    <div class="shrink-0 rounded-lg border border-gray-200 bg-white p-5 mt-6 shadow-sm">
        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider mb-3">RFQ files to generate</p>
        <div class="flex flex-wrap gap-2 rounded-lg border border-gray-200 bg-gray-50 p-3">
            @foreach($suppliers as $supplier)
                <div class="gen-row flex items-center gap-2 rounded-lg border border-gray-200 bg-white pl-3 pr-2 py-2 shadow-sm transition-colors hover:bg-gray-100 {{ isset($initialGenSuppliers[$supplier['name']]) ? '' : 'hidden' }}"
                     data-supplier="{{ $supplier['name'] }}">
                    <input type="checkbox"
                           class="gen-check w-4 h-4 rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300 cursor-pointer"
                           title="Generate PDF + Excel for {{ $supplier['name'] }}"
                           {{ isset($initialGenSuppliers[$supplier['name']]) ? 'checked' : '' }}>
                    <button type="button"
                            class="gen-open-modal text-sm font-medium text-gray-800 hover:text-[#2a7a94] hover:underline cursor-pointer"
                            title="Preview the items selected for {{ $supplier['name'] }}">
                        {{ $supplier['name'] }}
                    </button>
                    <span class="gen-count text-xs text-gray-400"></span>
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

        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg max-h-[80vh] flex flex-col">
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

    {{-- Inline alert for Generate File — replaces the native alert() dialogs. --}}
    <div id="rfqError" class="mt-6 hidden shrink-0 items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 shadow-sm" role="alert" aria-live="assertive" tabindex="-1">
        <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-100">
            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
            </svg>
        </span>
        <div class="min-w-0 flex-1">
            <p id="rfqErrorTitle" class="text-sm font-bold text-amber-800">Cannot generate the RFQ files</p>
            <p id="rfqErrorMessage" class="mt-0.5 text-xs leading-relaxed text-amber-700"></p>
        </div>
        <button type="button" id="rfqErrorClose"
                class="rounded-md p-1 text-amber-500 transition-colors hover:bg-amber-100 hover:text-amber-700 cursor-pointer"
                title="Dismiss" aria-label="Dismiss">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
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
        // Chip toggling, the dynamic "RFQ files to generate" list, the
        // supplier preview modal, and the Excel export endpoint. The export
        // posts the checked supplier / item selections to the server, which
        // re-reads every item from the prep list and streams back one .xlsx
        // per supplier (or a ZIP archive containing one workbook per supplier).
        // ------------------------------------------------------------------
        (function () {
            'use strict';

            const GENERATE_URL = @json(route('preplist.generate-rfq.excel', ['preplist' => $prepList->id]));
            const PREP_LIST_REFERENCE = @json($prepList->reference_no);
            const PREPLIST_INDEX_URL = @json(route('preplist'));

            // Inline error banner for Generate File — replaces the native
            // alert() dialogs with the app's amber notice style.
            const rfqError = document.getElementById('rfqError');
            const rfqErrorMessage = document.getElementById('rfqErrorMessage');
            const rfqErrorClose = document.getElementById('rfqErrorClose');

            function showRfqError(message) {
                if (!rfqError || !rfqErrorMessage) {
                    return;
                }

                rfqErrorMessage.textContent = message;
                rfqError.classList.remove('hidden');
                rfqError.classList.add('flex');
                rfqError.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }

            function clearRfqError() {
                if (!rfqError) {
                    return;
                }

                rfqError.classList.add('hidden');
                rfqError.classList.remove('flex');
            }

            if (rfqErrorClose) {
                rfqErrorClose.addEventListener('click', clearRfqError);
            }

            // Item metadata lookup, keyed by each per-item chip's data-item-key.
            const ITEM_DATA_BY_KEY = {};
            @json($items).forEach(function (item) {
                ITEM_DATA_BY_KEY[item.key] = { name: item.name, total: item.total, unit: item.unit, shelf_life: item.shelf_life };
            });

            // ------------------------------------------------------------------
            // "RFQ files to generate" — shows one row per supplier that has
            // at least one checked item. Each row's checkbox remembers the user's
            // explicit choice; newly-appearing suppliers default to a checked row.
            // ------------------------------------------------------------------
            const genState = {}; // supplier -> user's explicit generate yes/no

            function anyItemCheckedFor(supplier) {
                return document.querySelector('.item-supplier-check[data-supplier="' + CSS.escape(supplier) + '"]:checked') !== null;
            }

            function syncGenerateList() {
                // Any selection change dismisses a previous Generate File error.
                clearRfqError();

                document.querySelectorAll('.gen-row').forEach(function (row) {
                    const supplier = row.dataset.supplier;
                    const checkbox = row.querySelector('.gen-check');
                    const hasItems = anyItemCheckedFor(supplier);

                    row.classList.toggle('hidden', !hasItems);
                    if (!hasItems) return;

                    checkbox.checked = Object.prototype.hasOwnProperty.call(genState, supplier)
                        ? genState[supplier]
                        : true;

                    const checkedCount = document.querySelectorAll('.item-supplier-check[data-supplier="' + CSS.escape(supplier) + '"]:checked').length;
                    const countLabel = row.querySelector('.gen-count');
                    if (countLabel) {
                        countLabel.textContent = checkedCount + (checkedCount === 1 ? ' item selected' : ' items selected');
                    }
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
            // checked in "RFQ files to generate". Each workbook contains
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
                    showRfqError('Tick at least one supplier in "RFQ files to generate" first.');
                    return;
                }

                clearRfqError();

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
                                let message = 'Unable to generate the RFQ files.';
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

                        // RFQ files were handed to the browser; take the user
                        // back to the prep list index.
                        generateFileBtn.disabled = true;
                        generateFileBtn.innerHTML = 'Generated — going back to the prep list…';
                        window.setTimeout(function () {
                            window.location.assign(PREPLIST_INDEX_URL);
                        }, 1200);
                    })
                    .catch(function (error) {
                        showRfqError(error && error.message ? error.message : 'Unable to generate the RFQ files.');
                    })
                    .finally(function () {
                        generateFileBtn.disabled = false;
                        generateFileBtn.innerHTML = generateFileBtnLabel;
                    });
            });

            // Initial render.

                        // ------------------------------------------------------------------
            // Grid header: select-all per supplier, with a dash when partial.
            // ------------------------------------------------------------------
            function columnBoxes(supplier) {
                return document.querySelectorAll('.item-supplier-check[data-supplier="' + CSS.escape(supplier) + '"]');
            }

            function syncColumn(supplier) {
                const boxes = columnBoxes(supplier);
                const checked = Array.prototype.filter.call(boxes, function (b) { return b.checked; }).length;
                const all = document.querySelector('.supplier-all-check[data-supplier="' + CSS.escape(supplier) + '"]');
                const label = document.querySelector('.supplier-count[data-supplier="' + CSS.escape(supplier) + '"]');

                if (all) {
                    all.checked = boxes.length > 0 && checked === boxes.length;
                    all.indeterminate = checked > 0 && checked < boxes.length;
                }
                if (label) {
                    label.textContent = checked + ' / ' + boxes.length;
                }
            }

            document.querySelectorAll('.supplier-all-check').forEach(function (all) {
                all.addEventListener('change', function () {
                    columnBoxes(all.dataset.supplier).forEach(function (b) { b.checked = all.checked; });
                    syncColumn(all.dataset.supplier);
                    syncGenerateList();
                });
            });

            document.querySelectorAll('.item-supplier-check').forEach(function (cb) {
                cb.addEventListener('change', function () { syncColumn(cb.dataset.supplier); });
            });

            document.querySelectorAll('.supplier-all-check').forEach(function (all) {
                syncColumn(all.dataset.supplier);
            });
            syncGenerateList();
        })();
    </script>

@endsection