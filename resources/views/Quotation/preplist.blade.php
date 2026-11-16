@extends('layouts.app')

@section('page-title', 'PREPLIST DETAILS')
@section('title', 'Preplist comparison - Purchasing')

@section('header-actions')
@endsection

@php
    $peso = fn ($amount) => '₱ '.number_format((float) $amount, 2);
@endphp

@section('content')

    {{-- Back to tracker --}}
    <div class="mb-3">
        <a href="{{ route('quotation') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 transition-colors hover:text-[#2a7a94]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to tracker
        </a>
    </div>

    {{-- Banner: PREPLIST DETAILS --}}
    <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-[#0e5266] shadow-sm">
        <div class="px-6 py-5 text-center">
            <h1 class="text-xs font-bold uppercase tracking-widest text-white/70">Preplist details</h1>
            <p class="mt-1 text-xl font-bold text-white">{{ $preplist->name }}</p>
            <p class="mt-0.5 text-xs text-white/60">{{ $preplist->reference_no }}</p>
        </div>
    </div>

    {{-- Quotation columns: one per supplier --}}
    @if (empty($sections))
        <div class="rounded-lg border border-dashed border-gray-300 bg-white px-6 py-12 text-center">
            <p class="text-sm font-semibold text-gray-700">No quotations yet for this prep list.</p>
            <p class="mt-1 text-xs text-gray-500">Generate the RFQ from the prep list, then come back once suppliers reply.</p>
        </div>
    @else
        <div class="mb-6">
            <div class="grid gap-4" style="grid-template-columns: repeat({{ count($sections) }}, minmax(min-content, 1fr));">
                @foreach ($sections as $section)
                    @php
                        $quotation = $section['quotation'];
                        $quoted = collect($section['lines'])->filter(fn ($line) => $line && $line['unit_price'] !== null)->count();
                    @endphp
                    <div class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 px-5 py-3">
                            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700">Quotation by {{ $section['supplier'] }}</h2>
                            <p class="mt-0.5 text-xs text-gray-400">
                                {{ $section['rfq_number'] }}
                                @if ($quoted > 0)
                                    · <span class="text-blue-600">Quoted</span>
                                @else
                                    · <span class="text-gray-400">Awaiting quote</span>
                                @endif
                            </p>
                        </div>

                        <div class="flex-1 divide-y divide-gray-100 px-5 py-3">
                            @foreach ($items as $preplistItem)
                                @php
                                    $line = $section['lines'][$preplistItem->id] ?? null;
                                    $itemName = $preplistItem->item?->name ?? 'Item';
                                    $unit = $preplistItem->item?->unit ?? '';
                                    $requested = $preplistItem->remainingQuantity();
                                @endphp
                                <div class="flex items-start justify-between gap-3 py-3 text-sm">
                                    <div class="min-w-0 break-words">
                                        <p class="text-gray-900"><span class="font-medium">{{ $itemName }}</span>
                                            <span class="text-xs text-gray-500"> · {{ $requested }} {{ $unit }}</span>
                                        </p>
                                        @if ($line && $line['unit_price'] !== null)
                                            <p class="mt-0.5 text-xs text-gray-500">
                                                {{ $line['brand'] ?: '—' }}
                                                @if ($line['expiry_label'])
                                                    · Exp. {{ $line['expiry_label'] }}
                                                @endif
                                            </p>
                                        @endif
                                    </div>

                                    @if ($line && $line['unit_price'] !== null)
                                        @php
                                            $lineInCart = in_array($line['id'], $cartQuoteItemIds ?? [], true);
                                            $linePreplistItemId = $preplistItem->id;
                                            $otherSupplierHolds = ! $lineInCart && in_array($linePreplistItemId, $cartPreplistIds ?? [], true);
                                            $otherSupplierName = $otherSupplierHolds ? ($cartPreplistSupplier[$linePreplistItemId] ?? 'that supplier') : null;
                                        @endphp
                                        <div class="flex shrink-0 items-center gap-2">
                                            <p class="whitespace-nowrap text-sm font-semibold text-gray-900">{{ $peso($line['unit_price']) }}</p>
                                            @if ($lineInCart)
                                                <button type="button"
                                                        data-cart-remove-open
                                                        data-item-name="{{ $itemName }}"
                                                        data-supplier="{{ $section['supplier'] }}"
                                                        data-cart-item-id="{{ $cartLineIdByQuoteItem[$line['id']] ?? '' }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 text-white transition-colors hover:bg-teal-700"
                                                        title="In your cart from {{ $section['supplier'] }} — click to remove"
                                                        aria-label="Remove {{ $itemName }} from cart">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </button>
                                            @elseif ($otherSupplierHolds)
                                                <button type="button" disabled
                                                        title="Remove from {{ $otherSupplierName }} before picking {{ $section['supplier'] }}"
                                                        aria-label="{{ $itemName }} already in cart from {{ $otherSupplierName }}"
                                                        class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg bg-gray-100 text-gray-300">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 10a2 2 0 100 4m8-4a2 2 0 100 4M8 13h8"/>
                                                    </svg>
                                                </button>
                                            @else
                                                <form method="POST" action="{{ route('po-cart.add') }}">
                                                    @csrf
                                                    <input type="hidden" name="quotation_item_id" value="{{ $line['id'] }}">
                                                    <button type="submit"
                                                            title="Add {{ $itemName }} at {{ $section['supplier'] }}'s price to your PO cart"
                                                            aria-label="Add {{ $itemName }} from {{ $section['supplier'] }} to the PO cart"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#2a7a94] text-white transition-colors hover:bg-[#236b80]">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 10a2 2 0 100 4m8-4a2 2 0 100 4M8 13h8"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @else
                                        <span class="shrink-0 text-sm text-gray-400">-</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-gray-100 px-5 py-3">
                            @if ($quoted >= count($items))
                                <form method="POST" action="{{ route('po-cart.add') }}">
                                    @csrf
                                    <input type="hidden" name="quotation_id" value="{{ $quotation->id }}">
                                    <button type="submit"
                                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#236b80]"
                                            title="Add all {{ $section['supplier'] }} quote to your PO cart at remaining quantities">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 11V7a4 4 0 0 0-8 0v4M5 9h14l1 12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2L5 9z"/>
                                        </svg>
                                        Add {{ $section['supplier'] }} to cart
                                    </button>
                                </form>
                            @else
                                <span class="block cursor-not-allowed rounded-lg bg-gray-100 px-4 py-2 text-center text-xs font-semibold text-gray-400"
                                      title="Cannot add yet — this quotation has unpriced items">
                                    Unpriced items in this quotation
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Suggestion: which supplier to buy per item --}}
    @if (! empty($items))
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 bg-gray-50 px-6 py-4 text-center">
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-600">Suggestion — which supplier to buy per item</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @if ($suggestions->isEmpty())
                    <div class="px-6 py-8 text-center text-sm text-gray-500">No items to evaluate.</div>
                @else
                    @foreach ($suggestions as $suggestion)
                        <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-3">
                            <div class="min-w-0">
                                <p class="break-words text-sm text-gray-900">
                                    <span class="font-medium">{{ $suggestion['name'] }}</span>
                                    <span class="text-xs text-gray-500"> · {{ $suggestion['requested'] }} {{ $suggestion['unit'] }}</span>
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                @if ($suggestion['winner'])
                                    <p class="text-sm font-semibold text-gray-900">{{ $peso($suggestion['winner']['quote']['unit_price']) }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $suggestion['winner']['supplier'] }}
                                        @if ($suggestion['winner']['quote']['expiry_label'])
                                            · Exp. {{ $suggestion['winner']['quote']['expiry_label'] }}
                                        @else
                                            · No expiry stated
                                        @endif
                                    </p>
                                @else
                                    <p class="text-sm text-gray-400">Awaiting quote</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endif

    {{-- Remove-from-cart confirmation modal --}}
    <div id="cartItemModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="cartItemModalTitle">
        <div id="cartItemModalOverlay" class="absolute inset-0 bg-gray-900/50" data-close-cart-item></div>

        <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
            <div class="flex items-start gap-3 px-5 py-4">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-teal-50">
                    <svg class="h-5 w-5 text-[#2a7a94]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 10a2 2 0 100 4m8-4a2 2 0 100 4M8 13h8"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 id="cartItemModalTitle" class="text-base font-bold text-gray-900">Item is in your cart</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        <span id="cartItemModalContext" class="font-medium text-gray-700"></span> is in your cart. Remove it, or keep it?
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                <button type="button" data-close-cart-item
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Keep it
                </button>
                <form id="cartItemRemoveForm" method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#236b80]">
                        Remove
                    </button>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            var modal = document.getElementById('cartItemModal');
            if (!modal) {
                return;
            }

            var overlay = document.getElementById('cartItemModalOverlay');
            var context = document.getElementById('cartItemModalContext');
            var closeButtons = modal.querySelectorAll('[data-close-cart-item]');
            var form = document.getElementById('cartItemRemoveForm');
            var triggers = document.querySelectorAll('[data-cart-remove-open]');

            function openModal(trigger) {
                var itemName = trigger.getAttribute('data-item-name') || 'Item';
                var supplier = trigger.getAttribute('data-supplier') || '';
                var cartItemId = trigger.getAttribute('data-cart-item-id');

                context.textContent = itemName + (supplier ? ' from ' + supplier : '');
                if (cartItemId) {
                    form.setAttribute('action', '{{ url('/cart/items') }}/' + cartItemId);
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            Array.prototype.forEach.call(triggers, function (trigger) {
                trigger.addEventListener('click', function () { openModal(trigger); });
            });
            Array.prototype.forEach.call(closeButtons, function (btn) {
                btn.addEventListener('click', closeModal);
            });
            if (overlay) {
                overlay.addEventListener('click', closeModal);
            }
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
    @endpush

@endsection
