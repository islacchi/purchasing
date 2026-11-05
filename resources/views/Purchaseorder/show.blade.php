@extends('layouts.app')

@section('page-title', 'PURCHASE ORDER')
@section('title', 'Purchase Order - Purchasing')

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
        // Dummy data keyed by the {id} route parameter (no DB integration yet).
        //
        // @TODO: Replace with a real lookup once the PurchaseOrder model and
        // migrations exist, e.g.:
        //   $po = PurchaseOrder::with(['supplier', 'quotation.items', 'items.item'])
        //       ->findOrFail($id);
        // Line totals should be computed from the PO item snapshot, not stored.
        // ------------------------------------------------------------------
        $orders = [
            1 => [
                'number' => 'PO-2026-0117',
                'status' => 'Sent to supplier',
                'supplier' => 'GreenCore Pharmaceuticals',
                'contact' => 'Ana Lim',
                'email' => 'sales@greencore.ph',
                'phone' => '+63 2 8123 4567',
                'address' => '12F One Corporate Centre, Ortigas Center, Pasig City',
                'quotation_id' => 2,
                'quotation_ref' => '#2026-0090',
                'prep_list' => 'July Region VII batch',
                'issued' => 'Jul 18, 2026',
                'expected' => 'Jul 25, 2026',
                'payment_terms' => '30 days from delivery',
                'deliver_to' => 'DOH Region VII Warehouse, Cebu City',
                'notes' => 'Deliver in a single shipment. Include the PO number on all packing slips and invoices.',
                'items' => [
                    ['name' => 'Amoxicillin 500mg', 'unit' => 'box of 100', 'qty' => 100, 'price' => 305.00],
                    ['name' => 'Sterile syringes 5mL', 'unit' => 'box of 100', 'qty' => 30, 'price' => 420.00],
                    ['name' => 'Microscope slides', 'unit' => 'box of 100', 'qty' => 25, 'price' => 250.00],
                ],
            ],
            2 => [
                'number' => 'PO-2026-0116',
                'status' => 'Partially received',
                'supplier' => 'MedSource Inc.',
                'contact' => 'Marco Reyes',
                'email' => 'orders@medsource.ph',
                'phone' => '+63 2 8555 0102',
                'address' => 'Unit 8B, Arcadia Building, Makati City',
                'quotation_id' => 3,
                'quotation_ref' => '#2026-0089',
                'prep_list' => 'Lab reagents – PGH',
                'issued' => 'Jul 15, 2026',
                'expected' => 'Jul 22, 2026',
                'payment_terms' => '15 days from delivery',
                'deliver_to' => 'PGH Manila — Central Supply Room',
                'notes' => 'Partial delivery received on Jul 20. Remaining microscope slides are expected with the next shipment.',
                'items' => [
                    ['name' => 'Surgical gloves', 'unit' => 'box of 50', 'qty' => 40, 'price' => 280.00],
                    ['name' => 'Sodium chloride 0.9% 500mL', 'unit' => 'box of 24', 'qty' => 60, 'price' => 850.00],
                    ['name' => 'Microscope slides', 'unit' => 'box of 100', 'qty' => 25, 'price' => 220.00],
                ],
            ],
            3 => [
                'number' => 'PO-2026-0115',
                'status' => 'Draft',
                'supplier' => 'Triple Tact',
                'contact' => 'Liza Tan',
                'email' => 'sales@tripletact.com',
                'phone' => '+63 2 8333 7788',
                'address' => '3F Trade Center, Quezon City',
                'quotation_id' => 1,
                'quotation_ref' => '#2026-0088',
                'prep_list' => 'Pharma supplies Q3',
                'issued' => 'Jul 14, 2026',
                'expected' => 'Not set',
                'payment_terms' => 'Cash on delivery',
                'deliver_to' => 'DOH Region VII Warehouse, Cebu City',
                'notes' => 'Draft — review quantities and delivery terms before sending to the supplier.',
                'items' => [
                    ['name' => 'Amoxicillin 500mg', 'unit' => 'box of 100', 'qty' => 50, 'price' => 372.00],
                    ['name' => 'Surgical gloves', 'unit' => 'box of 50', 'qty' => 40, 'price' => 301.00],
                ],
            ],
            4 => [
                'number' => 'PO-2026-0114',
                'status' => 'Completed',
                'supplier' => 'MedSource Inc.',
                'contact' => 'Marco Reyes',
                'email' => 'orders@medsource.ph',
                'phone' => '+63 2 8555 0102',
                'address' => 'Unit 8B, Arcadia Building, Makati City',
                'quotation_id' => 3,
                'quotation_ref' => '#2026-0087',
                'prep_list' => 'Surgical supplies',
                'issued' => 'Jul 02, 2026',
                'expected' => 'Jul 10, 2026',
                'payment_terms' => '30 days from delivery',
                'deliver_to' => 'PGH Manila — Central Supply Room',
                'notes' => 'Fully delivered on Jul 09. Invoice received and endorsed to finance.',
                'items' => [
                    ['name' => 'Surgical gloves', 'unit' => 'box of 50', 'qty' => 100, 'price' => 280.00],
                    ['name' => 'Sterile syringes 5mL', 'unit' => 'box of 100', 'qty' => 80, 'price' => 445.00],
                    ['name' => 'Microscope slides', 'unit' => 'box of 100', 'qty' => 50, 'price' => 220.00],
                    ['name' => 'Laboratory gloves', 'unit' => 'box of 50', 'qty' => 40, 'price' => 180.00],
                ],
            ],
            5 => [
                'number' => 'PO-2026-0113',
                'status' => 'Cancelled',
                'supplier' => 'GreenCore Pharmaceuticals',
                'contact' => 'Ana Lim',
                'email' => 'sales@greencore.ph',
                'phone' => '+63 2 8123 4567',
                'address' => '12F One Corporate Centre, Ortigas Center, Pasig City',
                'quotation_id' => 2,
                'quotation_ref' => '#2026-0086',
                'prep_list' => 'Laboratory reagents',
                'issued' => 'Jun 28, 2026',
                'expected' => '—',
                'payment_terms' => '30 days from delivery',
                'deliver_to' => 'PGH Manila — Central Supply Room',
                'notes' => 'Cancelled on Jun 30 — quantities were consolidated into PO-2026-0114.',
                'items' => [
                    ['name' => 'Microscope slides', 'unit' => 'box of 100', 'qty' => 15, 'price' => 250.00],
                    ['name' => 'Sodium chloride 0.9% 500mL', 'unit' => 'box of 24', 'qty' => 20, 'price' => 810.00],
                ],
            ],
            6 => [
                'number' => 'PO-2026-0112',
                'status' => 'Sent to supplier',
                'supplier' => 'Triple Tact',
                'contact' => 'Liza Tan',
                'email' => 'sales@tripletact.com',
                'phone' => '+63 2 8333 7788',
                'address' => '3F Trade Center, Quezon City',
                'quotation_id' => 1,
                'quotation_ref' => '#2026-0085',
                'prep_list' => 'IT hardware refresh',
                'issued' => 'Jun 25, 2026',
                'expected' => 'Jul 30, 2026',
                'payment_terms' => '50% down, 50% on delivery',
                'deliver_to' => 'Head Office — IT Department, Pasig City',
                'notes' => 'Delivery to the 4th floor IT storage room. Coordinate with the IT department 24 hours in advance.',
                'items' => [
                    ['name' => 'External SSD 1TB', 'unit' => 'unit', 'qty' => 20, 'price' => 4850.00],
                    ['name' => 'USB-C docking station', 'unit' => 'unit', 'qty' => 15, 'price' => 3200.00],
                    ['name' => '24-inch monitor', 'unit' => 'unit', 'qty' => 15, 'price' => 7900.00],
                ],
            ],
        ];

        $po = $orders[$id] ?? $orders[1];

        $statusStyles = [
            'Draft' => 'bg-gray-100 text-gray-600',
            'Sent to supplier' => 'bg-blue-100 text-blue-700',
            'Partially received' => 'bg-amber-100 text-amber-700',
            'Completed' => 'bg-green-100 text-green-700',
            'Cancelled' => 'bg-red-100 text-red-700',
        ];

        $stages = ['Draft', 'Sent to supplier', 'Partially received', 'Completed'];
        $currentStage = array_search($po['status'], $stages, true);
        $isCancelled = $po['status'] === 'Cancelled';

        $subtotal = array_sum(array_map(fn ($item) => $item['qty'] * $item['price'], $po['items']));
        $freight = 0.0;
        $total = $subtotal + $freight;

        $peso = function ($amount) {
            return '₱' . number_format($amount, 2);
        };

        $initials = collect(explode(' ', $po['supplier']))
            ->take(2)
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');
    @endphp

    <div class="pb-16">

        {{-- Back --}}
        <a href="{{ route('purchase-order') }}"
           class="mb-5 inline-flex items-center gap-2 text-sm font-medium text-gray-500 transition-colors hover:text-[#2a7a94]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to purchase orders
        </a>

        {{-- Heading --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-bold text-gray-900">{{ $po['number'] }}</h2>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusStyles[$po['status']] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $po['status'] }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Issued from quotation
                    <a href="{{ route('quotation.show', ['id' => $po['quotation_id']]) }}"
                       class="font-medium text-[#2a7a94] hover:underline">{{ $po['quotation_ref'] }}</a>
                    · {{ $po['prep_list'] }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if (! in_array($po['status'], ['Completed', 'Cancelled']))
                    {{-- @TODO: Wire this to the PO cancel endpoint once it exists. --}}
                    <button type="button"
                            class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        Cancel order
                    </button>
                @endif

                {{-- @TODO: Generate the printable PO document. --}}
                <a href="#"
                   class="inline-flex items-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-[#236b80]">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 7.5 12 3m0 0L7.5 7.5M12 3v13.5"/>
                    </svg>
                    Download PDF
                </a>
            </div>
        </div>

        {{-- Status progress / cancelled banner --}}
        @if ($isCancelled)
            <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-5 py-4">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
                </svg>
                <div>
                    <p class="text-sm font-semibold text-red-700">This purchase order was cancelled</p>
                    <p class="mt-0.5 text-xs text-red-600">{{ $po['notes'] }}</p>
                </div>
            </div>
        @else
            <div class="mb-6 rounded-lg border border-gray-200 bg-white px-6 py-5 shadow-sm">
                <ol class="flex flex-wrap items-center gap-y-3">
                    @foreach ($stages as $index => $stage)
                        @php
                            $isDone = $index < $currentStage;
                            $isCurrent = $index === $currentStage;
                        @endphp
                        <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                            <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold
                                {{ $isDone ? 'bg-green-100 text-green-600' : ($isCurrent ? 'bg-[#2a7a94] text-white' : 'bg-gray-100 text-gray-400') }}">
                                @if ($isDone)
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m5 13 4 4L19 7"/>
                                    </svg>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </span>
                            <span class="ml-2 whitespace-nowrap text-xs font-medium
                                {{ $isCurrent ? 'text-[#2a7a94]' : ($isDone ? 'text-gray-700' : 'text-gray-400') }}">
                                {{ $stage }}
                            </span>
                            @if (! $loop->last)
                                <span class="mx-3 hidden h-0.5 flex-1 rounded-full sm:block {{ $isDone ? 'bg-green-200' : 'bg-gray-100' }}"></span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Supplier and delivery --}}
        <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-2">

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
                            <p class="truncate font-semibold text-gray-900">{{ $po['supplier'] }}</p>
                            <p class="text-xs text-gray-500">{{ $po['contact'] }}</p>
                        </div>
                    </div>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-xs text-gray-500">Email</dt>
                            <dd class="text-right text-gray-700">{{ $po['email'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-xs text-gray-500">Phone</dt>
                            <dd class="text-right text-gray-700">{{ $po['phone'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-xs text-gray-500">Address</dt>
                            <dd class="text-right text-gray-700">{{ $po['address'] }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Order details</h3>
                </div>
                <div class="px-6 py-5">
                    <dl class="space-y-2 text-sm">
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-xs text-gray-500">Date issued</dt>
                            <dd class="text-right text-gray-700">{{ $po['issued'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-xs text-gray-500">Expected delivery</dt>
                            <dd class="text-right text-gray-700">{{ $po['expected'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-xs text-gray-500">Payment terms</dt>
                            <dd class="text-right text-gray-700">{{ $po['payment_terms'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-xs text-gray-500">Deliver to</dt>
                            <dd class="text-right text-gray-700">{{ $po['deliver_to'] }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-xs text-gray-500">Items</dt>
                            <dd class="text-right text-gray-700">{{ count($po['items']) }} line items</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Items ordered --}}
        <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700">Items ordered</h3>
                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                    {{ count($po['items']) }} items
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th scope="col" class="w-12 px-6 py-3 text-center">#</th>
                            <th scope="col" class="px-6 py-3">Item</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 text-center">Qty</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Unit price</th>
                            <th scope="col" class="whitespace-nowrap px-6 py-3 text-right">Extended</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($po['items'] as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-center text-xs text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-900">{{ $item['name'] }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ $item['unit'] }}</p>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-center text-gray-700">{{ $item['qty'] }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-gray-700">{{ $peso($item['price']) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-gray-900">
                                    {{ $peso($item['qty'] * $item['price']) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200 bg-gray-50">
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Subtotal</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700">{{ $peso($subtotal) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Delivery</td>
                            <td class="whitespace-nowrap px-6 py-3 text-right text-sm text-gray-700">{{ $peso($freight) }}</td>
                        </tr>
                        <tr class="border-t border-gray-200">
                            <td colspan="4" class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider text-gray-700">Total</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-base font-bold text-gray-900">{{ $peso($total) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="border-t border-gray-100 px-6 py-4">
                <p class="text-xs text-gray-500">
                    {{-- @TODO: Replace with the PO note field once the model exists. --}}
                    {{ $po['notes'] }}
                </p>
            </div>
        </div>

        {{-- Footer actions --}}
        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('purchase-order') }}"
               class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to list
            </a>

            <a href="{{ route('quotation.show', ['id' => $po['quotation_id']]) }}"
               class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3h-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                </svg>
                View source quotation
            </a>
        </div>
    </div>
@endsection
