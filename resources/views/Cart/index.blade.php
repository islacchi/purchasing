@extends('layouts.app')

@section('page-title', 'PO CART')
@section('title', 'PO Cart - Purchasing')

@section('header-actions')
@endsection

@php
    $peso = fn ($amount) => '₱ '.number_format((float) $amount, 2);
    $totalLines = $meta['count'];
    $poCount = $meta['quotations'] ?? 0;
    $canManage = auth()->user()?->canModule('purchase-order', 'manage') ?? false;
@endphp

@section('content')
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Page header --}}
    <div class="mb-4 mt-1 flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0">
            <h2 class="text-2xl font-bold text-gray-900">Things to buy</h2>
            <p class="mt-1 text-xs text-gray-500">
                {{ $totalLines }} line{{ $totalLines === 1 ? '' : 's' }}
                @if (! empty($grouped))
                    &middot; {{ count($grouped) }} supplier{{ count($grouped) === 1 ? '' : 's' }}
                @endif
                &middot; quantities re-checked at checkout
            </p>
        </div>
    </div>

    @if (empty($grouped))
        {{-- Empty state --}}
        <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-100">
                <svg class="h-7 w-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h2 class="mt-4 text-base font-semibold text-gray-900">Your cart is empty</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
                Add items from a quotation's page, or use <span class="font-medium text-gray-700">Add all best prices to cart</span> on the Item Catalog.
            </p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <a href="{{ route('item-catalog') }}"
                   class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#236b80]">
                    Go to Item Catalog
                </a>
                <a href="{{ route('quotation') }}"
                   class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    View quotations
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            {{-- Left: lines, grouped by supplier --}}
            <div class="space-y-6 xl:col-span-2">
                @foreach ($grouped as $group)
                    @php
                        $supplier = $group['supplier'];
                        $items = $group['items'];
                        $subtotal = $group['subtotal'];
                    @endphp
                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 bg-[#fbfdfe] px-4 py-3">
                            <div class="min-w-0">
                                <h2 class="text-sm font-extrabold uppercase tracking-wider text-gray-900">{{ $supplier->name }}</h2>
                                <p class="mt-0.5 text-[10px] text-gray-500">{{ count($items) }} line{{ count($items) === 1 ? '' : 's' }}</p>
                            </div>
                            <p class="text-sm font-extrabold text-gray-900">{{ $peso($subtotal) }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="sticky top-0 z-10 bg-[#0e5266]">
                                    <tr>
                                        <th scope="col" class="px-4 py-2.5 text-left text-[10px] font-semibold uppercase tracking-wider text-white">Item</th>
                                        <th scope="col" class="px-3 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-white">Qty</th>
                                        <th scope="col" class="px-3 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-white">Unit price</th>
                                        <th scope="col" class="px-3 py-2.5 text-right text-[10px] font-semibold uppercase tracking-wider text-white">Total</th>
                                        <th scope="col" class="w-12 px-2 py-2.5"><span class="sr-only">Remove</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($items as $lineIndex => $line)
                                        <tr class="align-top transition-colors hover:bg-[#e4edf0] {{ $lineIndex % 2 === 1 ? 'bg-[#f2f7f8]' : 'bg-white' }}">
                                            <td class="px-4 py-2.5">
                                                <p class="break-words text-sm font-medium text-gray-900">{{ $line['name'] }}</p>
                                                <p class="mt-0.5 text-[11px] text-gray-400">
                                                    {{ $line['unit'] }}
                                                    @if ($line['min_remaining_months'] > 0)
                                                        · min. {{ $line['min_remaining_months'] }} mo. shelf life
                                                    @endif
                                                    · {{ $line['requested'] }} needed
                                                    @if ($line['itemBox'])
                                                        · pack: Box of {{ $line['itemBox'] }}
                                                    @endif
                                                    @if ($line['supplierBox'])
                                                        · supplier: Box of {{ $line['supplierBox'] }}
                                                    @endif
                                                </p>

                                                @if ($line['packageSuggestion'])
                                                    @php $suggest = $line['packageSuggestion']; @endphp
                                                    <div class="mt-1.5 max-w-[22rem] rounded-md border border-amber-200 bg-amber-50 px-2 py-1.5 text-[10px] font-medium leading-snug text-amber-800">
                                                        Your requirement is packed differently from this supplier. Order
                                                        <span class="font-bold">{{ $suggest['neededBoxes'] }} boxes (Box of {{ $suggest['theirBox'] }})</span>
                                                        to cover {{ number_format($suggest['totalUnits']) }} units.
                                                        @if ($suggest['over'] > 0)
                                                            Covers {{ $suggest['over'] }} extra units.
                                                        @endif
                                                        <button type="button" data-apply-packaging="{{ $suggest['neededBoxes'] }}" data-target="qty-{{ $line['cartItem']->id }}"
                                                                class="mt-1 rounded border border-amber-300 bg-white px-2 py-0.5 text-[10px] font-semibold text-amber-800 hover:bg-amber-100">
                                                            Apply: set qty to {{ $suggest['neededBoxes'] }} boxes
                                                        </button>
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2.5 text-right">
                                                <label class="sr-only" for="qty-{{ $line['cartItem']->id }}">Quantity for {{ $line['name'] }}</label>
                                                <input id="qty-{{ $line['cartItem']->id }}"
                                                       type="number" class="cart-qty h-[30px] w-14 rounded-md border border-gray-300 px-1 py-1 text-center text-xs font-semibold text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-300"
                                                       name="qty[{{ $line['cartItem']->id }}]" min="1" max="{{ $line['allowedMax'] }}"
                                                       data-cart-item-id="{{ $line['cartItem']->id }}"
                                                       data-unit-price="{{ $line['unit_price'] }}"
                                                       data-supplier-id="{{ $group['supplier']->id ?? '' }}"
                                                       value="{{ $line['qty'] }}"
                                                       form="checkoutForm">
                                            </td>
                                            <td class="px-3 py-2.5 text-right">
                                                <p class="text-sm text-gray-700">{{ $peso($line['unit_price']) }}</p>
                                                @if ($line['premium'] !== null)
                                                    <span class="mt-0.5 inline-flex items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">
                                                        +{{ number_format($line['premium'], 1) }}% vs best
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right text-sm font-semibold text-gray-900" id="lineTotal-{{ $line['cartItem']->id }}">{{ $peso($line['line_total']) }}</td>
                                            <td class="px-2 py-2.5 text-center">
                                                <form method="POST" action="{{ route('po-cart.remove', $line['cartItem']) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            title="Remove {{ $line['name'] }}"
                                                            aria-label="Remove {{ $line['name'] }} from cart"
                                                            class="inline-flex rounded-md p-1.5 text-gray-300 transition-colors hover:bg-red-50 hover:text-red-500">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Right: summary --}}
            <div class="xl:col-span-1">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm xl:sticky xl:top-6">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Order summary</p>
                    <div class="mt-2 divide-y divide-gray-100">
                        <div class="flex items-baseline justify-between gap-4 py-2.5 text-xs">
                            <span class="text-gray-500">Lines</span>
                            <span class="font-semibold text-gray-900" data-cart-lines-count>{{ $totalLines }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 py-2.5 text-xs">
                            <span class="text-gray-500">Draft POs to create</span>
                            <span class="text-right font-semibold text-gray-900" data-cart-po-count>{{ $poCount }}</span><span class="ml-1 font-normal text-gray-400">(one per awarded quotation)</span>
                        </div>
                    </div>

                    <div class="mt-2 border-t border-gray-100 pt-2">
                        @foreach ($grouped as $group)
                            <div class="flex items-baseline justify-between gap-3 py-1.5 text-[11px]">
                                <span class="min-w-0 truncate font-semibold uppercase text-gray-700">{{ $group['supplier']->name }}</span>
                                <span class="whitespace-nowrap text-gray-500" data-supplier-subtotal="{{ $group['supplier']->id }}">{{ $peso($group['subtotal']) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-2 flex items-baseline justify-between gap-4 border-t border-gray-200 pt-3">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Grand total</span>
                        <span class="text-2xl font-extrabold text-[#0e5266]" data-grand-total>{{ $peso($grandTotal) }}</span>
                    </div>

                    <p class="mt-2 text-[10px] leading-snug text-gray-500">
                        Checkout re-checks quantities, prices, and on-hand stock. If anything is no longer orderable, the whole checkout stops and tells you which lines. No order is sent to suppliers yet — the draft POs wait on the Purchase Orders list.
                    </p>
                    <div class="mt-3 flex items-stretch gap-2">
                        <form method="POST" action="{{ route('po-cart.clear') }}" class="shrink-0"
                              onsubmit="return confirm('Clear your cart? Nothing will be ordered.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="flex h-full items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-3 text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                <span class="hidden sm:inline">Clear cart</span>
                            </button>
                        </form>
                        <button type="button" id="checkoutOpenBtnSummary"
                                class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-3 text-sm font-bold text-white transition-colors hover:bg-[#236b80]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Checkout<span data-checkout-lines-wrap>{{ $totalLines > 0 ? ' ('.$totalLines.' line'.($totalLines === 1 ? '' : 's').')' : '' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Checkout confirmation modal --}}
    @if (! empty($grouped))
        <div id="checkoutModal" class="fixed inset-0 z-[70] hidden items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="checkoutTitle">
            <div id="checkoutOverlay" class="absolute inset-0 bg-gray-900/60"></div>

            <form id="checkoutForm" method="POST" action="{{ route('po-cart.checkout') }}"
                  class="relative w-full max-w-lg rounded-lg bg-white shadow-xl">
                @csrf
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h3 id="checkoutTitle" class="text-base font-bold text-gray-900">Checkout</h3>
                    <button type="button" data-close-checkout aria-label="Close checkout"
                            class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-4 px-5 py-4">
                    <p class="text-sm text-gray-600">
                        This will create <strong data-checkout-po-count>{{ $poCount }}</strong> draft purchase order<span data-checkout-po-plural>{{ $poCount === 1 ? '' : 's' }}</span>
                        with <strong data-checkout-lines>{{ $totalLines }}</strong> line<span data-checkout-lines-plural>{{ $totalLines === 1 ? '' : 's' }}</span>
                        totalling <strong data-checkout-grand-total>{{ $peso($grandTotal) }}</strong>. Nothing goes to suppliers yet.
                    </p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="expectedDelivery" class="mb-1.5 block text-xs font-medium text-gray-600">Expected delivery</label>
                            <input id="expectedDelivery" type="date" name="expected_delivery"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                        <div>
                            <label for="paymentTerms" class="mb-1.5 block text-xs font-medium text-gray-600">Payment terms</label>
                            <input id="paymentTerms" type="text" name="payment_terms" placeholder="e.g. 30 days from delivery"
                                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                        </div>
                    </div>

                    <div>
                        <label for="deliverTo" class="mb-1.5 block text-xs font-medium text-gray-600">Deliver to</label>
                        <input id="deliverTo" type="text" name="deliver_to" placeholder="e.g. Central Warehouse, Annex Building"
                               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300">
                    </div>

                    <div>
                        <label for="checkoutNotes" class="mb-1.5 block text-xs font-medium text-gray-600">Notes</label>
                        <textarea id="checkoutNotes" name="notes" rows="2"
                                  placeholder="Any instructions for the supplier (optional)"
                                  class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-300"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                    <button type="button" data-close-checkout
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="rounded-md bg-[#2a7a94] px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#236b80]">
                        Create draft POs
                    </button>
                </div>
            </form>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    (function () {
        var modal = document.getElementById('checkoutModal');
        if (!modal) {
            return;
        }

        var openButtons = [document.getElementById('checkoutOpenBtnSummary')].filter(Boolean);
        var closeButtons = modal.querySelectorAll('[data-close-checkout]');
        var overlay = document.getElementById('checkoutOverlay');

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            var first = modal.querySelector('input, textarea, button');
            if (first) {
                first.focus();
            }
        }
        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        openButtons.forEach(function (btn) {
            btn.addEventListener('click', openModal);
        });
        closeButtons.forEach(function (btn) {
            btn.addEventListener('click', closeModal);
        });
        if (overlay) {
            overlay.addEventListener('click', closeModal);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.classList.contains('flex')) {
                closeModal();
            }
        });
    })();

    // Live recompute: quantities are held client-side until checkout; the
    // totals shown here follow the numbers the user typed.
    (function () {
        var qtyInputs = document.querySelectorAll('.cart-qty');
        if (!qtyInputs.length) {
            return;
        }

        function toNumber(value) {
            var n = Number(value);
            return Number.isFinite(n) ? n : 0;
        }

        function recompute() {
            var grand = 0;
            var lines = 0;
            var perSupplier = {};

            qtyInputs.forEach(function (input) {
                var qty = Math.max(0, Math.floor(toNumber(input.value)));
                var price = toNumber(input.getAttribute('data-unit-price'));
                var lineEl = document.getElementById('lineTotal-' + input.getAttribute('data-cart-item-id'));
                if (lineEl) {
                    lineEl.textContent = '₱' + (qty * price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
                grand += qty * price;
                if (qty > 0) {
                    lines += 1;
                }
                var supplier = input.getAttribute('data-supplier-id');
                if (supplier) {
                    perSupplier[supplier] = (perSupplier[supplier] || 0) + qty * price;
                }
            });

            function renderPeso(value) {
                return '₱' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            var grandEl = document.querySelector('[data-grand-total]');
            if (grandEl) {
                grandEl.textContent = renderPeso(grand);
            }
            var checkoutTotal = document.querySelector('[data-checkout-grand-total]');
            if (checkoutTotal) {
                checkoutTotal.textContent = renderPeso(grand);
            }
            var linesEl = document.querySelector('[data-cart-lines-count]');
            if (linesEl) {
                linesEl.textContent = String(lines);
            }
            var checkoutLines = document.querySelector('[data-checkout-lines]');
            if (checkoutLines) {
                checkoutLines.textContent = String(lines);
            }
            var checkoutLinesWrap = document.querySelector('[data-checkout-lines-wrap]');
            if (checkoutLinesWrap) {
                checkoutLinesWrap.textContent = lines > 0 ? ' (' + lines + ' line' + (lines === 1 ? '' : 's') + ')' : '';
            }
            var checkoutLinesPlural = document.querySelector('[data-checkout-lines-plural]');
            if (checkoutLinesPlural) {
                checkoutLinesPlural.textContent = lines === 1 ? '' : 's';
            }

            Object.keys(perSupplier).forEach(function (supplier) {
                var el = document.querySelector('[data-supplier-subtotal="' + supplier + '"]');
                if (el) {
                    el.textContent = renderPeso(perSupplier[supplier]);
                }
            });
        }

        qtyInputs.forEach(function (input) {
            input.addEventListener('input', recompute);
            input.addEventListener('change', recompute);
        });

        // Apply button on a packaging-suggestion callout
        document.querySelectorAll('[data-apply-packaging]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-target');
                var desired = parseInt(btn.getAttribute('data-apply-packaging'), 10);
                var input = document.getElementById(target);
                if (input) {
                    input.value = String(desired);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        });

        recompute();
    })();
</script>
@endpush
