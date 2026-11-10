@extends('layouts.app')

@section('page-title', 'PURCHASE ORDER')
@section('title', 'Prepare Purchase Order - Purchasing')

@section('header-actions')
@endsection

@section('content')
    <style>
        /* Keep the wide items table scrolling inside its card instead of
           widening the shell. */
        #mainContent { min-width: 0; }
    </style>

    @php
        // ------------------------------------------------------------------
        // Dummy data for the preparation screen (no DB integration yet).
        // Reached from the quotation page via "Proceed to PO"; submitting
        // "Generate purchase order" prepares the draft PO.
        //
        // @TODO: Replace with a real lookup once the Quotation / PurchaseOrder
        // models exist, e.g.:
        //   $quotation = Quotation::with(['supplier', 'prepList', 'items.allocations.sourceProject'])
        //       ->findOrFail($quotationId);
        // Only items that carry a unit_price can be ordered. Purchase quantity
        // must never exceed the quantity requested internally (or the quantity
        // allocated to this supplier for split awards).
        // The dataset mirrors Quotation/show.blade.php for ids 1-3.
        // ------------------------------------------------------------------
        $quotations = [
            1 => [
                'id' => 1,
                'supplier' => 'Triple Tact',
                'prep_list' => 'July Region VII batch',
                'reference_no' => '#2026-0090',
                'contact' => 'Liza Tan',
                'email' => 'sales@tripletact.com',
                'deliver_to' => 'DOH Region VII Warehouse, Cebu City',
                'payment_terms' => '30 days from delivery',
                'expected' => '2026-07-25',
                'items' => [
                    [
                        'name' => 'Amoxicillin 500mg, box of 100',
                        'requested' => 100,
                        'unit' => 'box',
                        'unit_price' => null,
                        'min_remaining_months' => 12,
                        'offered_shelf_life_months' => null,
                        'allocations' => ['Pharma supplies Q3', 'Laboratory reagents'],
                    ],
                    [
                        'name' => 'Surgical gloves, box of 50 pairs',
                        'requested' => 40,
                        'unit' => 'box',
                        'unit_price' => null,
                        'min_remaining_months' => 24,
                        'offered_shelf_life_months' => null,
                        'allocations' => ['Pharma supplies Q3'],
                    ],
                ],
            ],
            2 => [
                'id' => 2,
                'supplier' => 'GreenCore Pharmaceuticals',
                'prep_list' => 'July Region VII batch',
                'reference_no' => '#2026-0090',
                'contact' => 'Ana Lim',
                'email' => 'sales@greencore.ph',
                'deliver_to' => 'DOH Region VII Warehouse, Cebu City',
                'payment_terms' => '30 days from delivery',
                'expected' => '2026-07-25',
                'items' => [
                    [
                        'name' => 'Amoxicillin 500mg, box of 100',
                        'requested' => 100,
                        'unit' => 'box',
                        'unit_price' => 305.00,
                        'min_remaining_months' => 12,
                        'offered_shelf_life_months' => 9,
                        'allocations' => ['Pharma supplies Q3', 'Laboratory reagents'],
                    ],
                    [
                        'name' => 'Surgical gloves, box of 50 pairs',
                        'requested' => 40,
                        'unit' => 'box',
                        'unit_price' => null,
                        'min_remaining_months' => 24,
                        'offered_shelf_life_months' => null,
                        'allocations' => ['Pharma supplies Q3'],
                    ],
                ],
            ],
            3 => [
                'id' => 3,
                'supplier' => 'MedSource Inc.',
                'prep_list' => 'Lab reagents – PGH',
                'reference_no' => '#2026-0089',
                'contact' => 'Marco Reyes',
                'email' => 'orders@medsource.ph',
                'deliver_to' => 'PGH Manila — Central Supply Room',
                'payment_terms' => '15 days from delivery',
                'expected' => '2026-07-22',
                'items' => [
                    [
                        'name' => 'Sodium chloride 0.9% 500mL, box of 24',
                        'requested' => 60,
                        'unit' => 'box',
                        'unit_price' => 850.00,
                        'min_remaining_months' => 12,
                        'offered_shelf_life_months' => 12,
                        'allocations' => ['Laboratory reagents', 'Pharma supplies Q3'],
                    ],
                    [
                        'name' => 'Sterile gloves, box of 50 pairs',
                        'requested' => 40,
                        'unit' => 'box',
                        'unit_price' => 310.00,
                        'min_remaining_months' => 24,
                        'offered_shelf_life_months' => 30,
                        'allocations' => ['Laboratory reagents'],
                    ],
                    [
                        'name' => 'Microscope slides, box of 100',
                        'requested' => 25,
                        'unit' => 'box',
                        'unit_price' => 220.00,
                        'min_remaining_months' => 0,
                        'offered_shelf_life_months' => null,
                        'allocations' => ['Laboratory reagents', 'Pharma supplies Q3'],
                    ],
                ],
            ],
        ];

        $quotation = $quotations[$rfqId] ?? $quotations[2];

        $peso = fn ($amount) => '₱' . number_format((float) $amount, 2);

        $quotedItems = collect($quotation['items'])->filter(fn ($item) => $item['unit_price'] !== null);
        $quotedCount = $quotedItems->count();

        $subtotal = $quotedItems->sum(fn ($item) => $item['unit_price'] * $item['requested']);
        $delivery = 0.0;
        $total = $subtotal + $delivery;

        $initials = collect(explode(' ', $quotation['supplier']))
            ->take(2)
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');
    @endphp

    <div class="flex h-full min-h-0 flex-col">

        {{-- Back --}}
        <a href="{{ route('quotation.show', ['id' => $quotation['id']]) }}"
           class="mb-5 inline-flex shrink-0 items-center gap-2 text-sm font-medium text-gray-500 transition-colors hover:text-[#2a7a94]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to quotation {{ $quotation['reference_no'] }}
        </a>

        {{-- Heading --}}
        <div class="mb-6 flex shrink-0 flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-bold text-gray-900">Prepare purchase order</h2>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">Draft</span>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    From quotation
                    <a href="{{ route('quotation.show', ['id' => $quotation['id']]) }}"
                       class="font-medium text-[#2a7a94] hover:underline">{{ $quotation['reference_no'] }}</a>
                    · {{ $quotation['prep_list'] }}
                </p>
            </div>
        </div>

        {{-- Demo confirmation (no persistence yet) — floats at the former banner spot --}}
        <div id="poCreatedBanner" class="flash-success-overlay hidden" role="status">
            <div class="flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 px-5 py-4 shadow-sm">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-green-800">Draft purchase order prepared</p>
                    <p id="poCreatedSummary" class="mt-0.5 text-xs text-green-700"></p>
                    <p class="mt-1 text-xs text-green-700">
                        Nothing was saved yet — wiring this to the PurchaseOrder controller is still a @TODO.
                    </p>
                </div>
            </div>
        </div>

        <form id="poForm" novalidate class="flex min-h-0 flex-1 flex-col">
            <div class="grid min-h-0 flex-1 grid-cols-1 gap-6 xl:grid-cols-3">

                {{-- Left column: items + delivery --}}
                <div class="flex min-h-0 flex-col space-y-6 overflow-y-auto xl:col-span-2">

                    {{-- Items to order --}}
                    <div class="flex flex-col rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
                            <div>
                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Items to order</h3>
                                <p class="mt-0.5 text-xs text-gray-500">
                                    Uncheck items you do not need. Purchase quantity defaults to the amount requested internally.
                                    Shelf life is the minimum months that must remain at delivery.
                                </p>
                            </div>
                            <span id="poSelectedCount"
                                  class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                {{ $quotedCount }} selected
                            </span>
                        </div>

                        @if ($quotedCount === 0)
                            <div class="flex items-start gap-3 border-b border-amber-100 bg-amber-50 px-6 py-3">
                                <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
                                </svg>
                                <p class="text-xs text-amber-700">
                                    This quotation has no priced items yet, so there is nothing to order.
                                </p>
                            </div>
                        @endif

                        <div class="max-h-[320px] min-h-[140px] overflow-auto">
                            <table class="w-full min-w-[1040px] text-sm">
                                <thead class="sticky top-0 z-10">
                                    <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        <th scope="col" class="w-12 px-4 py-3 text-center">
                                            <input type="checkbox" id="poIncludeAll"
                                                   class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300"
                                                   aria-label="Include all quoted items"
                                                   {{ $quotedCount === 0 ? 'disabled' : 'checked' }}>
                                        </th>
                                        <th scope="col" class="px-4 py-3">Item</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-center">Requested</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-center">Shelf life req.</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Unit price</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-center">Purchase qty</th>
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($quotation['items'] as $index => $item)
                                        @php
                                            $isQuoted = $item['unit_price'] !== null;
                                            $lineAmount = $isQuoted ? $item['unit_price'] * $item['requested'] : 0;
                                        @endphp
                                        <tr class="po-row transition-colors {{ $isQuoted ? 'hover:bg-gray-50' : 'bg-gray-50/70' }}"
                                            data-po-item
                                            data-index="{{ $index }}"
                                            data-name="{{ $item['name'] }}"
                                            data-price="{{ $isQuoted ? $item['unit_price'] : 0 }}"
                                            data-max="{{ $item['requested'] }}"
                                            data-unit="{{ $item['unit'] }}"
                                            data-offered-shelf-life="{{ $item['offered_shelf_life_months'] ?? '' }}">
                                            <td class="px-4 py-4 text-center">
                                                <input type="checkbox"
                                                       class="po-include h-4 w-4 cursor-pointer rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300 disabled:cursor-not-allowed"
                                                       aria-label="Include {{ $item['name'] }}"
                                                       {{ $isQuoted ? 'checked' : 'disabled' }}>
                                            </td>
                                            <td class="px-4 py-4">
                                                <p class="font-medium {{ $isQuoted ? 'text-gray-900' : 'text-gray-500' }}">{{ $item['name'] }}</p>
                                                <p class="mt-0.5 text-xs text-gray-500">
                                                    {{ $item['unit'] }}
                                                    <span class="mx-1 text-gray-300">&middot;</span>
                                                    {{ implode(' · ', $item['allocations']) }}
                                                </p>
                                                @if ($item['offered_shelf_life_months'] !== null)
                                                    <p class="mt-1 text-xs text-gray-500">
                                                        Supplier offered {{ $item['offered_shelf_life_months'] }} months
                                                    </p>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-4 text-center text-gray-700">
                                                {{ $item['requested'] }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-4 text-center">
                                                @if ($isQuoted)
                                                    <div class="inline-flex items-center gap-1">
                                                        <input type="number"
                                                               class="po-shelf-life w-16 rounded-md border border-gray-300 px-2 py-1 text-center text-sm text-gray-900 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20"
                                                               value="{{ $item['min_remaining_months'] ?? 0 }}"
                                                               min="0"
                                                               step="1"
                                                               inputmode="numeric"
                                                               aria-label="Minimum remaining shelf life in months for {{ $item['name'] }}">
                                                        <span class="text-[11px] text-gray-400">mo</span>
                                                    </div>
                                                    <p class="mt-1 text-[11px] text-gray-400" data-min-expiry>&mdash;</p>
                                                    <p class="mt-1 hidden text-[11px] font-medium text-amber-600" data-shelf-warning></p>
                                                @else
                                                    <span class="text-xs text-gray-400">&mdash;</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-4 text-right {{ $isQuoted ? 'text-gray-700' : 'text-gray-400' }}">
                                                {{ $isQuoted ? $peso($item['unit_price']) : '—' }}
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-4 text-center">
                                                @if ($isQuoted)
                                                    <input type="number"
                                                           class="po-qty w-20 rounded-md border border-gray-300 px-2 py-1 text-center text-sm text-gray-900 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20"
                                                           value="{{ $item['requested'] }}"
                                                           min="1"
                                                           max="{{ $item['requested'] }}"
                                                           step="1"
                                                           inputmode="numeric"
                                                           aria-label="Purchase quantity for {{ $item['name'] }}">
                                                @else
                                                    <span class="text-xs text-gray-400">No quote</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-4 text-right font-semibold {{ $isQuoted ? 'text-gray-900' : 'text-gray-400' }}"
                                                data-line-amount>
                                                {{ $isQuoted ? $peso($lineAmount) : '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 px-6 py-3">
                            <p class="text-xs text-gray-500">
                                Quantities are internal until the PO is issued. The shelf-life requirement is a quality condition, not a quantity, and is also shown on the RFQ.
                            </p>
                            <p class="text-sm font-semibold text-gray-900">
                                Subtotal <span id="poTableSubtotal">{{ $peso($subtotal) }}</span>
                            </p>
                        </div>
                    </div>

                    {{-- Delivery details --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Delivery details</h3>
                            <p class="mt-0.5 text-xs text-gray-500">These appear on the purchase order sent to the supplier.</p>
                        </div>
                        <div class="grid grid-cols-1 gap-4 px-6 py-5 md:grid-cols-2">
                            <div>
                                <label for="poExpected" class="mb-1 block text-xs font-medium text-gray-600">Expected delivery</label>
                                <input type="date" id="poExpected" value="{{ $quotation['expected'] }}"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                            </div>

                            <div>
                                <label for="poTerms" class="mb-1 block text-xs font-medium text-gray-600">Payment terms</label>
                                <select id="poTerms"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                                    @foreach (['30 days from delivery', '15 days from delivery', 'Cash on delivery', '50% down, 50% on delivery'] as $term)
                                        <option value="{{ $term }}" {{ $quotation['payment_terms'] === $term ? 'selected' : '' }}>{{ $term }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label for="poDeliverTo" class="mb-1 block text-xs font-medium text-gray-600">Deliver to</label>
                                <input type="text" id="poDeliverTo" value="{{ $quotation['deliver_to'] }}"
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">
                            </div>

                            <div class="md:col-span-2">
                                <label for="poNotes" class="mb-1 block text-xs font-medium text-gray-600">Notes for the supplier</label>
                                <textarea id="poNotes" rows="3"
                                          class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 focus:border-[#2a7a94] focus:outline-none focus:ring-2 focus:ring-[#2a7a94]/20">Deliver in a single shipment. Include the PO number on all packing slips and invoices.</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right column: supplier + summary --}}
                <aside class="min-h-0 space-y-6 overflow-y-auto xl:sticky xl:top-6">

                    {{-- Supplier --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Supplier</h3>
                        </div>
                        <div class="px-6 py-5">
                            <div class="flex items-center gap-3">
                                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-[#2a7a94]/10 text-sm font-bold text-[#2a7a94]">
                                    {{ $initials }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900">{{ $quotation['supplier'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $quotation['contact'] }}</p>
                                </div>
                            </div>
                            <dl class="mt-4 space-y-2 text-sm">
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-xs text-gray-500">Email</dt>
                                    <dd class="text-right text-gray-700">{{ $quotation['email'] }}</dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-xs text-gray-500">Quotation</dt>
                                    <dd class="text-right">
                                        <a href="{{ route('quotation.show', ['id' => $quotation['id']]) }}"
                                           class="font-medium text-[#2a7a94] hover:underline">{{ $quotation['reference_no'] }}</a>
                                    </dd>
                                </div>
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-xs text-gray-500">Prep list</dt>
                                    <dd class="text-right text-gray-700">{{ $quotation['prep_list'] }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    {{-- Summary --}}
                    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Order summary</h3>
                        </div>
                        <div class="px-6 py-5">
                            <dl class="space-y-2 text-sm">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Items selected</dt>
                                    <dd class="font-medium text-gray-900" id="poSummaryItems">{{ $quotedCount }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Subtotal</dt>
                                    <dd class="text-gray-700" id="poSummarySubtotal">{{ $peso($subtotal) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-gray-500">Delivery</dt>
                                    <dd class="text-gray-700">{{ $peso($delivery) }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4 border-t border-gray-100 pt-3">
                                    <dt class="text-sm font-bold text-gray-900">Total</dt>
                                    <dd class="text-base font-bold text-gray-900" id="poSummaryTotal">{{ $peso($total) }}</dd>
                                </div>
                            </dl>

                            <button type="submit" id="poCreateBtn"
                                    class="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#236b80] disabled:cursor-not-allowed disabled:bg-gray-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                                Generate purchase order
                            </button>

                            <a href="{{ route('quotation.show', ['id' => $quotation['id']]) }}"
                               class="mt-2 flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                                Cancel
                            </a>

                            <p class="mt-3 text-center text-[11px] text-gray-400">
                                Creates a draft order. You can review it before sending it to the supplier.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>

    <script>
        (function () {
            'use strict';

            const form = document.getElementById('poForm');
            const rows = Array.from(document.querySelectorAll('[data-po-item]'));
            const includeAll = document.getElementById('poIncludeAll');
            const selectedCount = document.getElementById('poSelectedCount');
            const summaryItems = document.getElementById('poSummaryItems');
            const summarySubtotal = document.getElementById('poSummarySubtotal');
            const summaryTotal = document.getElementById('poSummaryTotal');
            const tableSubtotal = document.getElementById('poTableSubtotal');
            const createBtn = document.getElementById('poCreateBtn');
            const banner = document.getElementById('poCreatedBanner');
            const bannerSummary = document.getElementById('poCreatedSummary');
            let bannerTimer = null;
            const expectedInput = document.getElementById('poExpected');

            function peso(amount) {
                return '₱' + amount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            function qtyInput(row) {
                return row.querySelector('.po-qty');
            }

            // ------------------------------------------------------------------
            // Shelf life: the PO line carries a minimum months-remaining
            // requirement. The date below it is only advisory (expected
            // delivery + required months); the binding check happens at
            // receiving against the actual receipt date.
            // ------------------------------------------------------------------
            function shelfLifeInput(row) {
                return row.querySelector('.po-shelf-life');
            }

            function readRequiredMonths(row) {
                const input = shelfLifeInput(row);
                if (!input) return 0;

                let value = parseInt(input.value, 10);
                if (isNaN(value) || value < 0) value = 0;
                return value;
            }

            function offeredMonths(row) {
                const raw = row.dataset.offeredShelfLife;
                if (raw === undefined || raw === '') return null;

                const value = parseInt(raw, 10);
                return isNaN(value) ? null : value;
            }

            function parseIsoDate(value) {
                if (!value) return null;

                const parts = value.split('-').map(Number);
                if (parts.length !== 3 || parts.some(isNaN)) return null;

                return new Date(parts[0], parts[1] - 1, parts[2]);
            }

            function addMonths(date, months) {
                const day = date.getDate();
                const target = new Date(date.getFullYear(), date.getMonth() + months, 1);
                const lastDay = new Date(target.getFullYear(), target.getMonth() + 1, 0).getDate();
                target.setDate(Math.min(day, lastDay));
                return target;
            }

            function formatDate(date) {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
            }

            function clampShelfLife(input) {
                let value = parseInt(input.value, 10);
                if (isNaN(value) || value < 0) value = 0;
                input.value = value;
                return value;
            }

            function updateShelfRow(row) {
                const input = shelfLifeInput(row);
                if (!input) return;

                const required = readRequiredMonths(row);
                const expiryEl = row.querySelector('[data-min-expiry]');
                const warningEl = row.querySelector('[data-shelf-warning]');
                const expected = parseIsoDate(expectedInput ? expectedInput.value : '');

                if (expiryEl) {
                    if (required === 0) {
                        expiryEl.textContent = 'No requirement';
                    } else if (expected) {
                        expiryEl.textContent = 'Min expiry ' + formatDate(addMonths(expected, required));
                    } else {
                        expiryEl.textContent = 'Set delivery date';
                    }
                }

                if (warningEl) {
                    const offered = offeredMonths(row);
                    const shortDated = offered !== null && required > 0 && offered < required;

                    warningEl.classList.toggle('hidden', !shortDated);
                    warningEl.textContent = shortDated
                        ? 'Supplier offered ' + offered + ' months \u2014 below requirement'
                        : '';
                }
            }

            function updateShelfDates() {
                rows.forEach(updateShelfRow);
            }

            function clampQty(input) {
                const max = parseInt(input.max, 10) || 1;
                let value = parseInt(input.value, 10);

                if (isNaN(value) || value < 1) value = 1;
                if (value > max) value = max;

                input.value = value;
                return value;
            }

            function rowAmount(row) {
                const include = row.querySelector('.po-include');
                const input = qtyInput(row);

                if (!include || !include.checked || !input) return 0;

                return (parseFloat(row.dataset.price) || 0) * clampQty(input);
            }

            function recalc() {
                let subtotal = 0;
                let count = 0;
                let shortDated = 0;

                rows.forEach(function (row) {
                    const include = row.querySelector('.po-include');
                    const input = qtyInput(row);
                    const amountCell = row.querySelector('[data-line-amount]');
                    const isIncluded = include && include.checked && !include.disabled;

                    if (input) {
                        input.disabled = !isIncluded;
                    }

                    row.classList.toggle('opacity-60', !isIncluded && !include.disabled);

                    if (isIncluded) {
                        const amount = rowAmount(row);
                        subtotal += amount;
                        count++;

                        const required = readRequiredMonths(row);
                        const offered = offeredMonths(row);
                        if (offered !== null && required > 0 && offered < required) {
                            shortDated++;
                        }

                        if (amountCell) amountCell.textContent = peso(amount);
                    } else if (amountCell && include && !include.disabled) {
                        amountCell.textContent = '—';
                    }
                });

                // Keep the "select all" box in sync (ignore the disabled
                // no-quote rows).
                const selectable = rows.filter(function (row) {
                    const include = row.querySelector('.po-include');
                    return include && !include.disabled;
                });
                const selected = selectable.filter(function (row) {
                    return row.querySelector('.po-include').checked;
                });

                if (includeAll) {
                    includeAll.checked = selectable.length > 0 && selected.length === selectable.length;
                    includeAll.indeterminate = selected.length > 0 && selected.length < selectable.length;
                }

                selectedCount.textContent = count + ' selected';
                summaryItems.textContent = count;
                summarySubtotal.textContent = peso(subtotal);
                summaryTotal.textContent = peso(subtotal);
                tableSubtotal.textContent = peso(subtotal);
                createBtn.disabled = count === 0;

                return { subtotal: subtotal, count: count, shortDated: shortDated };
            }

            rows.forEach(function (row) {
                const include = row.querySelector('.po-include');
                const input = qtyInput(row);
                const shelf = shelfLifeInput(row);

                if (include) {
                    include.addEventListener('change', recalc);
                }

                if (input) {
                    input.addEventListener('input', recalc);
                    input.addEventListener('change', function () {
                        clampQty(input);
                        recalc();
                    });
                    input.addEventListener('blur', function () {
                        clampQty(input);
                        recalc();
                    });
                }

                if (shelf) {
                    shelf.addEventListener('input', function () {
                        updateShelfRow(row);
                    });
                    shelf.addEventListener('change', function () {
                        clampShelfLife(shelf);
                        updateShelfRow(row);
                    });
                    shelf.addEventListener('blur', function () {
                        clampShelfLife(shelf);
                        updateShelfRow(row);
                    });
                }
            });

            if (expectedInput) {
                expectedInput.addEventListener('input', updateShelfDates);
                expectedInput.addEventListener('change', updateShelfDates);
            }

            if (includeAll) {
                includeAll.addEventListener('change', function () {
                    rows.forEach(function (row) {
                        const include = row.querySelector('.po-include');
                        if (!include || include.disabled) return;
                        include.checked = includeAll.checked;
                    });
                    recalc();
                });
            }

            // @TODO: replace this stub with a POST to the PurchaseOrder controller.
            // The payload below is everything the backend needs to create the PO.
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const totals = recalc();
                if (totals.count === 0) return;

                const items = rows
                    .filter(function (row) {
                        const include = row.querySelector('.po-include');
                        return include && include.checked && !include.disabled;
                    })
                    .map(function (row) {
                        const input = qtyInput(row);
                        return {
                            item: row.dataset.name,
                            unit: row.dataset.unit,
                            qty: parseInt(input.value, 10),
                            unit_price: parseFloat(row.dataset.price),
                            min_remaining_months: readRequiredMonths(row),
                            offered_shelf_life_months: offeredMonths(row),
                        };
                    });

                const payload = {
                    rfq_id: {{ $quotation['id'] }},
                    supplier: @json($quotation['supplier']),
                    items: items,
                    expected_delivery: document.getElementById('poExpected').value,
                    payment_terms: document.getElementById('poTerms').value,
                    deliver_to: document.getElementById('poDeliverTo').value,
                    notes: document.getElementById('poNotes').value,
                    subtotal: totals.subtotal,
                    delivery: 0,
                    total: totals.subtotal,
                };

                console.log('Generate purchase order — payload (TODO: POST to controller)', payload);

                let summary = totals.count + ' item' + (totals.count === 1 ? '' : 's')
                    + ' · ' + peso(totals.subtotal) + ' · ' + payload.supplier;
                if (totals.shortDated > 0) {
                    summary += ' · ' + totals.shortDated + ' short-dated line' + (totals.shortDated === 1 ? '' : 's');
                }

                bannerSummary.textContent = summary;
                banner.classList.remove('hidden', 'is-hiding');

                // Informational only: hide it again after a few seconds.
                clearTimeout(bannerTimer);
                bannerTimer = setTimeout(function () {
                    banner.classList.add('is-hiding');
                    setTimeout(function () {
                        banner.classList.add('hidden');
                        banner.classList.remove('is-hiding');
                    }, 350);
                }, 5000);
            });

            updateShelfDates();
            recalc();
        })();
    </script>
@endsection
