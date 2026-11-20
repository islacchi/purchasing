@extends('layouts.app')

@section('page-title', 'LOCAL PURCHASE')
@section('title', 'Local Purchase - ' . $prepList->name)

@section('header-actions')
@endsection

@section('content')
    <style>
        /* Native checkbox accent in the app's teal. */
        input[type="checkbox"].teal-check {
            accent-color: #2a7a94;
        }
    </style>

    <div class="flex h-full min-h-0 flex-col">

        {{-- Page heading --}}
        <div class="mb-4 flex shrink-0 flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Local purchase - {{ $prepList->name }}</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Buy these lines directly from a local store instead of through quotation.
                    A draft purchase order is raised — send and receive it as usual.
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-7h14l2 7M3 12v6h18v-6M3 12h18M12 12V6"/>
                </svg>
                No quotation needed
            </span>
        </div>

        @error('local_purchase')
            <div class="mb-3 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror
        @error('selected')
            <div class="mb-3 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        <form id="localPurchaseForm" method="POST" action="{{ route('preplist.local-purchase.store', ['preplist' => $prepList->id]) }}"
              class="flex min-h-0 flex-1 flex-col">
            @csrf

            <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-[1fr_340px]">

                {{-- Lines — checkbox-selectable, scrolls --}}
                <div class="flex min-h-0 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Lines to buy · {{ $lines->count() }}</h3>
                            <p class="mt-0.5 text-xs text-gray-500">Set <span class="font-medium">Qty</span> to how many units you are buying on this order \u2014 anything left over stays pending on the prep list. <span class="font-medium">Local Price</span> stays blank by default; it is only recorded once the purchase is marked as bought.</p>
                        </div>
                        <button type="button" id="lpToggleAll"
                                class="shrink-0 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-teal-300">
                            Select all
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto">
                        <table class="w-full table-fixed">
                            <colgroup>
                                <col class="w-[3rem]">
                                <col class="w-[36%]">
                                <col class="w-[11%]">
                                <col class="w-[7%]">
                                <col class="w-[13%]">
                                <col class="w-[20%]">
                            </colgroup>
                            <thead class="sticky top-0 z-10 bg-[#0e5266]">
                                <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-white">
                                    <th scope="col" class="px-3 py-2.5 text-center"><span class="sr-only">Select</span></th>
                                    <th scope="col" class="px-3 py-2.5">Item</th>
                                    <th scope="col" class="px-3 py-2.5 text-center">Remaining</th>
                                    <th scope="col" class="px-3 py-2.5 text-center">Shelf life</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">Qty</th>
                                    <th scope="col" class="px-3 py-2.5 text-right">Local Price</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($lines as $line)
                                    <tr class="transition-colors hover:bg-[#f2f7f8]" data-lp-row>
                                        <td class="px-3 py-2.5 text-center align-top">
                                            <input type="checkbox"
                                                   name="selected[]"
                                                   value="{{ $line['id'] }}"
                                                   class="teal-check h-4 w-4 rounded border-gray-300 focus:ring-teal-400"
                                                   data-lp-select
                                                   checked>
                                        </td>
                                        <td class="px-3 py-2.5 align-top">
                                            <p class="break-words text-sm font-medium leading-snug text-gray-900">{{ $line['name'] }}</p>
                                            <p class="mt-0.5 text-[10px] text-gray-500">{{ $line['source_count'] }} source{{ $line['source_count'] === 1 ? '' : 's' }} · {{ $line['remaining'] }} {{ $line['unit'] }} needed</p>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2.5 text-center align-top text-sm font-semibold text-gray-900">
                                            {{ number_format($line['remaining']) }} <span class="text-xs font-normal text-gray-500">{{ $line['unit'] }}</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-2.5 align-top text-center text-xs text-gray-600">
                                            @if ($line['shelf_life'] > 0)
                                                &ge; {{ $line['shelf_life'] }} month{{ $line['shelf_life'] === 1 ? '' : 's' }}
                                            @else
                                                Non-perishable
                                            @endif
                                        </td>
                                        <td class="px-3 py-2.5 align-top text-right">
                                            <input type="number" min="1" max="{{ $line['remaining'] }}" step="1"
                                                   name="lines[{{ $line['id'] }}][qty]"
                                                   value="{{ $line['remaining'] }}"
                                                   class="w-20 rounded-lg border border-gray-300 px-2 py-1.5 text-right text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"
                                                   aria-label="Quantity to buy">
                                        </td>
                                        <td class="px-3 py-2.5 align-top text-right">
                                            <div class="flex items-center justify-end gap-1">
                                                <span class="text-xs text-gray-400">₱</span>
                                                <input type="number" min="0" step="0.01"
                                                       name="lines[{{ $line['id'] }}][unit_price]"
                                                       value=""
                                                       placeholder="TBD"
                                                       data-lp-price
                                                       class="w-24 rounded-lg border border-gray-300 px-2 py-1.5 text-right text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300"
                                                       aria-label="Local price; leave blank to record it when the purchase is bought">
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Supplier + details --}}
                <div class="flex min-h-0 flex-col gap-4">
                    <div class="shrink-0 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Supplier</h3>
                        <p class="mt-1 text-xs text-gray-500">Where these will be bought.</p>
                        @if ($suppliers->isEmpty())
                            <p class="mt-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-3 text-xs text-gray-500">
                                No suppliers on file. Add the local store under Suppliers first.
                            </p>
                        @else
                            <select name="supplier_id" required
                                    class="mt-3 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"
                                    aria-label="Supplier">
                                <option value="">Select supplier…</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        @endif

                        <label class="mt-4 block text-[11px] font-semibold uppercase tracking-wider text-gray-500" for="expected_delivery">Expected delivery</label>
                        <input type="date" id="expected_delivery" name="expected_delivery"
                               class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">

                        <label class="mt-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500" for="payment_terms">Payment terms</label>
                        <input type="text" id="payment_terms" name="payment_terms" maxlength="255"
                               placeholder="e.g. Cash on pickup"
                               class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">

                        <label class="mt-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500" for="deliver_to">Deliver to</label>
                        <input type="text" id="deliver_to" name="deliver_to" maxlength="255"
                               placeholder="e.g. DOH Region VII, Maasin City"
                               class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300">

                        <label class="mt-3 block text-[11px] font-semibold uppercase tracking-wider text-gray-500" for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3"
                                  placeholder="Anything the recipient should know"
                                  class="mt-1.5 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"></textarea>
                    </div>

                    <div class="shrink-0 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Summary</h3>
                        <dl class="mt-2 space-y-1.5 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-500">Lines selected</dt>
                                <dd class="font-semibold text-gray-900"><span id="lpSelectedCount">{{ $lines->count() }}</span> of {{ $lines->count() }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-500">Total units</dt>
                                <dd class="font-semibold text-gray-900"><span id="lpTotalUnits">0</span></dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-gray-500">Estimated total</dt>
                                <dd class="font-semibold text-gray-900">₱<span id="lpEstimatedTotal">0.00</span><span id="lpTbdMark" class="hidden">&nbsp;*</span></dd>
                            </div>
                        </dl>
                        <p id="lpTbdNote" class="hidden mt-2 text-[10px] leading-snug text-amber-700">* TBD lines are excluded — prices are recorded on the purchase order when it is marked as bought.</p>
                    </div>
                </div>
            </div>

            {{-- Footer actions --}}
            <div class="mt-4 flex shrink-0 flex-wrap items-center justify-between gap-3">
                <a href="{{ route('preplist.show', ['preplist' => $prepList->id]) }}"
                   class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                    </svg>
                    Back to prep list
                </a>
                <button type="submit"
                        class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#236b80] focus:outline-none focus:ring-2 focus:ring-teal-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v12m0 0 3.75 3.75M6.75 15 3 11.25M17.25 21v-12m0 0-3.75-3.75M17.25 9 21 12.75"/>
                    </svg>
                    Create draft purchase order
                </button>
            </div>
        </form>
    </div>

    <script>
        // Live totals + select-all for the local purchase builder.
        (function () {
            var table = document.getElementById('localPurchaseForm');
            if (!table) return;

            var rowEls = table.querySelectorAll('[data-lp-row]');
            var selectAllBtn = document.getElementById('lpToggleAll');
            var countEl = document.getElementById('lpSelectedCount');
            var unitsEl = document.getElementById('lpTotalUnits');
            var totalEl = document.getElementById('lpEstimatedTotal');

            function fmt(n) {
                return n.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function recalc() {
                var selectedCount = 0, units = 0, total = 0, anyTbd = false;
                rowEls.forEach(function (row) {
                    var check = row.querySelector('[data-lp-select]');
                    var qtyInput = row.querySelector('input[name$="[qty]"]');
                    var priceInput = row.querySelector('input[name$="[unit_price]"]');
                    if (!check || !check.checked) return;
                    selectedCount++;
                    var qty = parseInt(qtyInput.value, 10) || 0;
                    var price = parseFloat(priceInput.value);
                    units += qty;
                    if (isNaN(price)) {
                        anyTbd = true;
                    } else {
                        total += qty * price;
                    }
                });
                if (countEl) countEl.textContent = String(selectedCount);
                if (unitsEl) unitsEl.textContent = units.toLocaleString('en-PH');
                if (totalEl) totalEl.textContent = fmt(total);
                var tbdMark = document.getElementById('lpTbdMark');
                var tbdNote = document.getElementById('lpTbdNote');
                if (tbdMark) tbdMark.classList.toggle('hidden', !anyTbd);
                if (tbdNote) tbdNote.classList.toggle('hidden', !anyTbd);
            }

            table.addEventListener('change', recalc);
            table.addEventListener('input', recalc);

            if (selectAllBtn) {
                selectAllBtn.addEventListener('click', function () {
                    var checks = table.querySelectorAll('[data-lp-select]');
                    var allChecked = Array.prototype.every.call(checks, function (c) { return c.checked; });
                    checks.forEach(function (c) { c.checked = !allChecked; });
                    recalc();
                });
            }

            recalc();
        })();
    </script>
@endsection
