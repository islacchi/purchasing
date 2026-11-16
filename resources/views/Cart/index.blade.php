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

    {{-- Header bar --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 flex-wrap items-center gap-3">
            <div>
                <h1 class="text-base font-bold text-gray-900">Things to buy</h1>
                <p class="mt-0.5 text-xs text-gray-500">
                    {{ $totalLines }} line{{ $totalLines === 1 ? '' : 's' }}
                    @if (! empty($grouped))
                        · {{ count($grouped) }} supplier{{ count($grouped) === 1 ? '' : 's' }}
                    @endif
                </p>
            </div>
        </div>

        @if (! empty($grouped))
            <div class="flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('po-cart.clear') }}"
                      onsubmit="return confirm('Clear your cart? Nothing will be ordered.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:border-red-200 hover:text-red-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Clear cart
                    </button>
                </form>
                <button type="button" id="checkoutOpenBtn"
                        class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#236b80]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Checkout{{ $totalLines > 0 ? ' ('.$totalLines.')' : '' }}
                </button>
            </div>
        @endif
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
                Add items from a quotation's page, or use <span class="font-medium text-gray-700">Add all best prices to cart</span> on the Price Index.
            </p>
            <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                <a href="{{ route('priceindex') }}"
                   class="flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#236b80]">
                    Go to Price Index
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
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-5 py-3">
                            <div class="min-w-0">
                                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">{{ $supplier->name }}</h2>
                                <p class="mt-0.5 text-[11px] text-gray-400">{{ count($items) }} line{{ count($items) === 1 ? '' : 's' }}</p>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">{{ $peso($subtotal) }}</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-5 py-2 text-left text-[10px] font-semibold uppercase tracking-wider text-gray-500">Item</th>
                                        <th class="px-3 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-500">Qty</th>
                                        <th class="px-3 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-500">Unit price</th>
                                        <th class="px-3 py-2 text-right text-[10px] font-semibold uppercase tracking-wider text-gray-500">Total</th>
                                        <th class="w-12 px-2 py-2"><span class="sr-only">Remove</span></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($items as $line)
                                        <tr class="align-top hover:bg-gray-50/60">
                                            <td class="px-5 py-2.5">
                                                <p class="break-words text-sm font-medium text-gray-900">{{ $line['name'] }}</p>
                                                <p class="mt-0.5 text-[11px] text-gray-400">
                                                    {{ $line['unit'] }}
                                                    @if ($line['min_remaining_months'] > 0)
                                                        · min. {{ $line['min_remaining_months'] }} mo. shelf life
                                                    @endif
                                                    · {{ $line['requested'] }} needed
                                                </p>
                                            </td>
                                            <td class="px-3 py-2.5 text-right">
                                                <form method="POST" action="{{ route('po-cart.update', $line['cartItem']) }}" onchange="this.requestSubmit()">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="sr-only" for="qty-{{ $line['cartItem']->id }}">Quantity for {{ $line['name'] }}</label>
                                                    <input id="qty-{{ $line['cartItem']->id }}"
                                                           type="number" name="qty" min="1" max="{{ min($line['requested'], 1000000) }}"
                                                           value="{{ $line['qty'] }}"
                                                           class="w-20 rounded-md border border-gray-300 px-2 py-1 text-right text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-teal-300">
                                                </form>
                                            </td>
                                            <td class="px-3 py-2.5 text-right">
                                                <p class="text-sm text-gray-700">{{ $peso($line['unit_price']) }}</p>
                                                @if ($line['premium'] !== null)
                                                    <span class="mt-0.5 inline-flex items-center rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-700">
                                                        +{{ number_format($line['premium'], 1) }}% vs best
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-2.5 text-right text-sm font-semibold text-gray-900">{{ $peso($line['line_total']) }}</td>
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
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm xl:sticky xl:top-6">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-gray-500">Order summary</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 pb-3">
                            <dt class="text-gray-500">Lines</dt>
                            <dd class="font-semibold text-gray-900">{{ $totalLines }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 pb-3">
                            <dt class="text-gray-500">Draft POs to create</dt>
                            <dd class="font-semibold text-gray-900">{{ $poCount }}<span class="ml-1 font-normal text-gray-400">(one per awarded quotation)</span></dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-b border-gray-100 pb-3">
                            <dt class="text-gray-500">Grand total</dt>
                            <dd class="text-lg font-bold text-[#0e5266]">{{ $peso($grandTotal) }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-xs text-gray-500">
                        Checkout re-checks quantities, prices, and on-hand stock. If anything is no longer orderable, the whole checkout stops and tells you which lines. No order is sent to suppliers yet — the draft POs wait on the Purchase Orders list.
                    </p>
                    <button type="button" id="checkoutOpenBtnSummary"
                            class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#236b80]">
                        Checkout{{ $totalLines > 0 ? ' ('.$totalLines.' line'.($totalLines === 1 ? '' : 's').')' : '' }}
                    </button>
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
                        This will create <strong>{{ $poCount }} draft purchase order{{ $poCount === 1 ? '' : 's' }}</strong>
                        with <strong>{{ $totalLines }} line{{ $totalLines === 1 ? '' : 's' }}</strong>
                        totalling <strong>{{ $peso($grandTotal) }}</strong>. Nothing goes to suppliers yet.
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
                            class="rounded-md bg-[#0e5266] px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#0c4757]">
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

        var openButtons = [document.getElementById('checkoutOpenBtn'), document.getElementById('checkoutOpenBtnSummary')].filter(Boolean);
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
</script>
@endpush
