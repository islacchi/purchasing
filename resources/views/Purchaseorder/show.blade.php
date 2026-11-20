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
        // Real purchase order loaded by PurchaseOrderController@show.
        $supplier = $purchaseOrder->supplier;
        $quotation = $purchaseOrder->quotation;
        $prepList = $quotation?->preplist;
        $items = $purchaseOrder->items;

        $status = $purchaseOrder->statusLabel();
        $statusStyles = [
            'Draft' => 'bg-gray-100 text-gray-600',
            'Sent to supplier' => 'bg-blue-100 text-blue-700',
            'Partially received' => 'bg-amber-100 text-amber-700',
            'Completed' => 'bg-green-100 text-green-700',
            'Cancelled' => 'bg-red-100 text-red-700',
        ];

        $isLocal = $purchaseOrder->isLocalPurchase();
        $hasTbd = $isLocal && $items->contains(fn ($i) => $i->unit_price === null);

        $stages = $isLocal ? ['Draft', 'Bought', 'Completed'] : \App\Models\PurchaseOrder::stages();
        $currentStage = $isLocal
            ? match ($purchaseOrder->status) {
                \App\Models\PurchaseOrder::STATUS_DRAFT => 0,
                \App\Models\PurchaseOrder::STATUS_PARTIALLY_RECEIVED => 1,
                default => 2, // completed
            }
            : $purchaseOrder->currentStageIndex();
        $isCancelled = $purchaseOrder->status === \App\Models\PurchaseOrder::STATUS_CANCELLED;

        $subtotal = $purchaseOrder->lineTotal();
        $freight = (float) $purchaseOrder->delivery_fee;
        $total = $subtotal + $freight;

        $receivedCount = $items->filter(fn ($item) => $item->isReceived())->count();
        $receivedUnits = $items->sum(fn ($item) => $item->receivedQty());
        $orderedUnits = $items->sum(fn ($item) => (int) $item->qty);
        $canReceive = $purchaseOrder->canReceive();
        $canManagePurchaseOrder = auth()->user()?->canModule('purchase-order', 'manage') ?? false;

        $supplierName = $supplier?->name ?? 'Supplier removed';

        $initials = collect(explode(' ', $supplierName))
            ->take(2)
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->implode('');

        $quotationShow = $quotation
            ? route('quotation.show', ['quotation' => $quotation->id])
            : null;

        $peso = function ($amount) {
            return '₱' . number_format($amount, 2);
        };
    @endphp

    <div class="flex min-h-full flex-col lg:h-full lg:min-h-0">

        {{-- Success messages are rendered once, globally, by layouts.app (partials.flash-success). --}}

        @if ($errors->any())
            <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        @if ($hasTbd)
            <div class="mb-4 shrink-0 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="status">
                <p class="font-semibold">Price to be recorded</p>
                <p class="mt-0.5 text-xs text-amber-700">
                    Some lines have no price yet. After the store purchase, press
                    <span class="font-medium">Mark as bought</span> — the system will ask for the price paid for each line and complete the order.
                </p>
            </div>
        @endif

        {{-- Heading --}}
        <div class="mb-6 flex shrink-0 flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold text-gray-900">{{ $purchaseOrder->po_number }}</h2>
                    <span id="poStatusPill" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusStyles[$status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $status }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    @if ($purchaseOrder->isLocalPurchase())
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-800 align-middle">Local purchase</span>
                        <span class="text-gray-400">no quotation</span>
                    @else
                        Issued from quotation
                        @if ($quotationShow)
                            <a href="{{ $quotationShow }}"
                               class="font-medium text-[#2a7a94] hover:underline">{{ $quotation?->rfq_number }}</a>
                        @else
                            <span class="text-gray-400">Quotation removed</span>
                        @endif
                        &middot; {{ $prepList?->name ?? 'Prep list removed' }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if (! $isLocal)
                <form method="POST" action="{{ route('purchase-order.send', ['purchaseOrder' => $purchaseOrder->id]) }}">
                    @csrf
                    <button type="submit" id="poSendBtn"
                            class="inline-flex items-center gap-2 rounded-lg border border-[#2a7a94] bg-white px-4 py-2 text-sm font-medium text-[#2a7a94] transition-colors hover:bg-teal-50 {{ $purchaseOrder->status === \App\Models\PurchaseOrder::STATUS_DRAFT ? '' : 'hidden' }}">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M22 2 11 13m0 0L2 9v4l9-7 9 7v4L22 2z"/>
                        </svg>
                        Send to supplier
                    </button>
                </form>
                @endif

                <button type="button" id="poCancelBtn"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-600 transition-colors hover:bg-red-50 {{ $purchaseOrder->isCancellable() ? '' : 'hidden' }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    Cancel order
                </button>

                @if ($purchaseOrder->isDeletable() && auth()->user()?->isAdministrator())
                    <button type="button" id="poDeleteBtn"
                            class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2 text-sm font-medium text-red-700 transition-colors hover:bg-red-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                        </svg>
                        Delete order
                    </button>
                @endif

            </div>
        </div>

        {{-- Status progress (hidden once cancelled — the cancellation notice lives in the Order details card) --}}
        @if (! $isCancelled)
            <div class="mb-4 shrink-0 rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                <h3 class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Order progress</h3>
                <ol id="poStatusTracker" class="flex flex-wrap items-center gap-y-3">
                    @foreach ($stages as $index => $stage)
                        @php
                            $isDone = $currentStage !== null && $index < $currentStage;
                            $isCurrent = $currentStage !== null && $index === $currentStage;
                        @endphp
                        <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}" data-stage-index="{{ $index }}">
                            <span data-stage-dot class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold
                                {{ $isDone ? 'bg-green-100 text-green-600' : ($isCurrent ? 'bg-[#2a7a94] text-white' : 'bg-gray-100 text-gray-400') }}">
                                <span data-stage-number class="{{ $isDone ? 'hidden' : '' }}">{{ $index + 1 }}</span>
                                <svg data-stage-check class="h-3.5 w-3.5 {{ $isDone ? '' : 'hidden' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m5 13 4 4L19 7"/>
                                </svg>
                            </span>
                            <span data-stage-label class="ml-2 whitespace-nowrap text-xs font-medium
                                {{ $isCurrent ? 'text-[#2a7a94]' : ($isDone ? 'text-gray-700' : 'text-gray-400') }}">
                                {{ $stage }}
                            </span>
                            @if (! $loop->last)
                                <span data-stage-connector class="mx-3 hidden h-0.5 flex-1 rounded-full sm:block {{ $isDone ? 'bg-green-200' : 'bg-gray-100' }}"></span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        {{-- Two-column layout: rail of labeled cards + items table --}}
        <div class="grid grid-cols-1 items-start gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-[330px_minmax(0,1fr)] lg:grid-rows-[minmax(0,1fr)]">

            {{-- LEFT RAIL --}}
            <div class="flex flex-col gap-4">

                {{-- Supplier --}}
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Supplier</p>
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-full bg-[#2a7a94]/10 text-xs font-bold text-[#2a7a94]">
                            {{ $initials }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900">{{ $supplierName }}</p>
                            <p class="mt-0.5 text-[11px] text-gray-500">{{ $supplier?->contact_person ?? 'No contact person' }}</p>
                        </div>
                    </div>

                    <div class="mt-3 divide-y divide-gray-100">
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Email</span>
                            <span class="text-right font-semibold text-gray-900">{{ $supplier?->email ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Phone</span>
                            <span class="text-right font-semibold text-gray-900">{{ $supplier?->contact_no ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Address</span>
                            <span class="max-w-[70%] truncate text-right font-semibold text-gray-900" title="{{ $supplier?->address }}">{{ $supplier?->address ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Order details --}}
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-500">Order details</p>

                    @if ($isCancelled)
                        <div class="mb-2 flex min-w-0 items-center gap-1.5 rounded-md border border-red-100 bg-red-50/70 px-2 py-1">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
                            </svg>
                            <p class="whitespace-nowrap text-[10px] font-semibold uppercase tracking-wide text-red-600">Purchase order cancelled</p>
                            <span class="hidden max-w-[220px] truncate text-[11px] text-red-500/90 md:inline" title="{{ $purchaseOrder->cancel_reason ?: 'No reason recorded.' }}">&middot; {{ $purchaseOrder->cancel_reason ?: 'No reason recorded.' }}</span>
                        </div>
                    @endif

                    <div class="divide-y divide-gray-100">
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Date issued</span>
                            <span class="text-right font-semibold text-gray-900">{{ $purchaseOrder->issued_on?->format('M j, Y') ?? 'Not issued yet' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Expected delivery</span>
                            <span class="text-right font-semibold text-gray-900">{{ $purchaseOrder->expected_delivery_on?->format('M j, Y') ?? 'Not set' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Payment terms</span>
                            <span class="text-right font-semibold text-gray-900">{{ $purchaseOrder->payment_terms ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Deliver to</span>
                            <span class="text-right font-semibold text-gray-900">{{ $purchaseOrder->deliver_to ?? '—' }}</span>
                        </div>
                        <div class="flex items-baseline justify-between gap-3 py-2 text-xs">
                            <span class="text-gray-500">Items</span>
                            <span class="text-right font-semibold text-gray-900">{{ $items->count() }} line items</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ITEMS ORDERED --}}
            <div class="flex min-h-[240px] flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm lg:h-full lg:min-h-0">
                <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-2.5">
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Items ordered</p>
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="receiptSummary" class="inline-flex items-center rounded-full border border-teal-100 bg-teal-50 px-2.5 py-1 text-xs font-medium text-[#2a7a94]">
                            {{ $receivedUnits }} / {{ $orderedUnits }} {{ $isLocal ? 'bought' : 'received' }} &middot; {{ $receivedCount }}/{{ $items->count() }} lines
                        </span>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                            {{ $items->count() }} items
                        </span>
                    </div>
                </div>

                <div class="lg:min-h-0 lg:flex-1 lg:overflow-auto">
                <table class="w-full table-fixed text-xs">
                    <colgroup>
                        <col class="w-[5%]">
                        <col class="w-[33%]">
                        <col class="w-[9%]">
                        <col class="w-[14%]">
                        <col class="w-[13%]">
                        <col class="w-[13%]">
                        <col class="w-[13%]">
                    </colgroup>
                    <thead class="sticky top-0 z-10 bg-[#0e5266]">
                        <tr class="text-left text-[10px] font-semibold uppercase tracking-wider text-white">
                            <th scope="col" class="px-3 py-2.5 text-center">#</th>
                            <th scope="col" class="px-3 py-2.5 text-left">Item</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5 text-center">Qty</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5 text-center">Shelf life req.</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5 text-right">{{ $purchaseOrder->isLocalPurchase() ? 'Local price' : 'Unit price' }}</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5 text-right">Total unit price</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2.5 text-center">{{ $purchaseOrder->isLocalPurchase() ? 'Bought' : 'Received' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($items as $item)
                            @php
                                $minShelf = $item->min_remaining_months ?? 0;
                                $isReceived = $item->isReceived();
                            @endphp
                            <tr class="odd:bg-white even:bg-[#f2f7f8] transition-colors hover:bg-[#e4edf0]">
                                <td class="px-3 py-2 text-center text-xs text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2">
                                    <p class="font-medium text-gray-900 break-words">{{ $item->item?->name ?? 'Item removed' }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ $item->item?->unit ?? '' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-center text-gray-700">{{ $item->qty }}</td>
                                <td class="whitespace-nowrap px-3 py-2.5 text-center">
                                    @if ($minShelf > 0)
                                        <span class="text-gray-700" title="Minimum {{ $minShelf }} months remaining shelf life at delivery">&ge; {{ $minShelf }} mo</span>
                                    @else
                                        <span class="text-xs text-gray-400" title="Non-perishable">&mdash;</span>
                                    @endif
                                </td>
                                <td class="break-words px-3 py-2 text-right text-gray-700">{{ $item->unit_price === null ? '—' : $peso($item->unit_price) }}</td>
                                <td class="break-words px-3 py-2 text-right font-semibold text-gray-900">{{ $item->unit_price === null ? '—' : $peso($item->extendedTotal()) }}</td>
                                                                <td class="whitespace-nowrap px-3 py-2.5 text-center">
                                    @php($receivedQty = $item->receivedQty())
                                    @if ($receivedQty > 0)
                                        <p class="text-[11px] font-semibold {{ $isReceived ? 'text-green-700' : 'text-amber-700' }}">
                                            {{ $receivedQty }} / {{ $item->qty }}
                                        </p>
                                    @endif

                                    @if (! $isReceived && $receivedQty > 0)
                                        <span class="mt-0.5 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-amber-700">
                                            Partial
                                        </span>
                                    @endif

                                    @if ($isReceived)
                                        <span class="mt-0.5 inline-flex items-center gap-1 text-[10px] font-medium text-green-700">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6 6 9-13.5"/>
                                            </svg>
                                            {{ $isLocal ? 'Fully bought' : 'Fully received' }}
                                        </span>
                                    @elseif ($isLocal && $canManagePurchaseOrder)
                                        <button type="button"
                                                data-po-bought
                                                data-po-line-id="{{ $item->id }}"
                                                data-po-qty="{{ $item->remainingQty() }}"
                                                data-po-label="{{ $item->item?->name ?? 'this line' }}"
                                                class="mt-0.5 inline-flex items-center gap-1 rounded-md border border-amber-400 bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-800 transition-colors hover:bg-amber-100 focus:outline-none focus:ring-2 focus:ring-amber-300"
                                                aria-label="Mark {{ $item->item?->name ?? 'this line' }} as bought">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 10a2 2 0 100 4m8-4a2 2 0 100 4M8 13h8"/>
                                            </svg>
                                            Mark as bought
                                        </button>
                                    @elseif ($canManagePurchaseOrder && $canReceive)
                                        <button type="button"
                                                data-po-receive
                                                data-po-action="{{ route('purchase-order.receive', ['purchaseOrder' => $purchaseOrder->id, 'purchaseOrderItem' => $item->id]) }}"
                                                data-po-label="{{ $item->item?->name ?? 'This line' }}"
                                                data-po-remaining="{{ $item->remainingQty() }}"
                                                data-po-ordered="{{ $item->qty }}"
                                                class="mt-0.5 inline-flex items-center gap-1 rounded-md border border-[#2a7a94] bg-white px-2 py-1 text-[10px] font-semibold text-[#2a7a94] transition-colors hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-300"
                                                aria-label="Record a received quantity for {{ $item->item?->name ?? 'this line' }}">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15"/>
                                            </svg>
                                            Receive
                                        </button>
                                    @elseif ($canManagePurchaseOrder)
                                        @php($title = 'Send the PO to the supplier before recording receipts')
                                        <span class="text-[10px] text-gray-400" title="{{ $title }}">{{ $isLocal ? 'Pending' : 'Not sent' }}</span>
                                    @else
                                        @php($title = 'Your role does not have access to that module.')
                                        <input type="checkbox" data-access-restricted-modal-toggle
                                               class="h-4 w-4 cursor-pointer rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300 disabled:opacity-40"
                                               title="{{ $title }}"
                                               aria-label="{{ $title }}">
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

            {{-- Fixed summary — visible no matter how many rows scroll --}}
            <div class="flex shrink-0 items-center justify-between gap-4 border-t border-gray-100 bg-gray-50/60 px-4 py-2.5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total &middot; {{ $items->count() }} {{ $items->count() === 1 ? 'line item' : 'line items' }}</p>
                <p class="text-sm font-extrabold text-[#0e5266]">{{ $hasTbd ? '—' : $peso($total) }}</p>
            </div>

            <div class="shrink-0 border-t border-gray-100 px-4 py-2.5">
                <p class="text-xs text-gray-500">
                    {{ $purchaseOrder->notes ?: ($isLocal ? 'Purchased locally — record the details with the store.' : 'No notes recorded for the supplier.') }}
                </p>
                <p class="mt-1 text-[11px] text-gray-400">
                    @if ($isLocal)
                        For local purchases, press <span class="font-medium text-gray-500">Mark as bought</span> on the line after each store trip — the system asks for the quantity bought and the price paid. The order completes once every line is fully bought.
                    @else
                        Use <span class="font-medium text-gray-500">Receive</span> to record how many units arrived and passed the shelf-life check. The order completes once every line’s full quantity is received.
                    @endif
                </p>
            </div>
            </div>

        </div>{{-- /grid --}}

        {{-- Footer actions --}}
        <div class="mt-6 flex shrink-0 flex-wrap items-center justify-between gap-3">
            <a href="{{ route('purchase-order') }}"
               class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
                Back to list
            </a>

            @if ($quotationShow)
                <a href="{{ $quotationShow }}"
                   class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3h-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    View source quotation
                </a>
            @endif
        </div>

        {{-- Cancel purchase order confirmation modal --}}
        <div id="poCancelModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="poCancelTitle">
            <div id="poCancelOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <form id="poCancelForm" method="POST" action="{{ route('purchase-order.cancel', ['purchaseOrder' => $purchaseOrder->id]) }}" class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
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
                            <span class="font-medium text-gray-700">{{ $purchaseOrder->po_number }}</span>
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

        {{-- Delete purchase order confirmation modal --}}
        <div id="poDeleteModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="poDeleteTitle">
            <div id="poDeleteOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <form id="poDeleteForm" method="POST" action="{{ route('purchase-order.destroy', ['purchaseOrder' => $purchaseOrder->id]) }}" class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
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
                            <span class="font-medium text-gray-700">{{ $purchaseOrder->po_number }}</span>
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

        {{-- Record receipt modal: asks how many units arrived this time --}}
        <div id="poReceiveModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="poReceiveTitle">
            <div id="poReceiveOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <form id="poReceiveForm" method="POST" class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
                @csrf
                <div class="flex items-start gap-3 px-5 py-4">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-teal-50">
                        <svg class="h-5 w-5 text-[#2a7a94]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 id="poReceiveTitle" class="text-base font-bold text-gray-900">Record a receipt</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            <span id="poReceiveItemName" class="font-medium text-gray-800">This line</span>
                            &mdash; how many units arrived?
                            <span id="poReceiveRemaining" class="text-gray-500"></span>
                        </p>
                    </div>
                </div>

                <div class="px-5 pb-4">
                    <label for="poReceiveQty" class="block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Quantity received</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input id="poReceiveQty" name="received_qty" type="number" min="1" step="1" required
                               class="w-28 rounded-lg border border-gray-300 px-3 py-2 text-right text-sm focus:outline-none focus:ring-2 focus:ring-teal-300"
                               aria-label="Quantity received">
                        <span id="poReceiveMax" class="text-xs text-gray-500"></span>
                    </div>
                    <p class="mt-2 text-[11px] text-gray-400">The order becomes Completed only when every line&rsquo;s full quantity is received. You can record the rest on another delivery.</p>
                </div>

                <input type="hidden" name="expected_received" value="0">
                <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                    <button type="button" id="poReceiveCancel"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                        Keep open
                    </button>
                    <button type="submit"
                            class="rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white hover:bg-[#236b80] transition-colors cursor-pointer">
                        Record receipt
                    </button>
                </div>
            </form>
        </div>

        {{-- Record local purchase - quantity bought + price paid per outstanding line --}}
        @php($poClosed = in_array($purchaseOrder->status, [\App\Models\PurchaseOrder::STATUS_COMPLETED, \App\Models\PurchaseOrder::STATUS_CANCELLED], true))
        @if ($isLocal && ! $poClosed)
        <div id="poBoughtModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="poBoughtTitle">
            <div id="poBoughtOverlay" class="absolute inset-0 bg-gray-900/50"></div>

            <form id="poBoughtForm" method="POST" action="{{ route('purchase-order.bought', ['purchaseOrder' => $purchaseOrder->id]) }}"
                  class="relative flex max-h-[85vh] w-full max-w-md flex-col rounded-lg bg-white shadow-xl">
                @csrf
                <input type="hidden" name="bought_line_id" id="boughtLineId" value="">
                <div class="flex items-start gap-3 border-b border-gray-100 px-5 py-4">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-50">
                        <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.225c-.668 0-1.189-.578-1.119-1.243l1.263-12A1.125 1.125 0 016.029 7.5h11.942a1.125 1.125 0 011.119 1.257z"/>
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h3 id="poBoughtTitle" class="text-base font-bold text-gray-900">Record purchase</h3>
                        <p class="mt-1 text-sm text-gray-500">Enter the quantity bought and the price paid for this line. Use 0 when the store had none. The order becomes <span class="font-medium text-gray-800">Completed</span> only once every line&rsquo;s full quantity is bought.</p>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto divide-y divide-gray-100 px-5 py-2">
                    @foreach ($items as $item)
                        @php($boughtSoFar = $item->receivedQty())
                        @php($lineDone = $item->isReceived())
                        <div id="boughtLineRow-{{ $item->id }}" class="flex items-center justify-between gap-3 py-2">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900" title="{{ $item->item?->name }}">{{ $item->item?->name ?? 'Item removed' }}</p>
                                <p class="text-[10px] text-gray-500">
                                    Ordered {{ $item->qty }}{{ $boughtSoFar > 0 ? ' · bought '.$boughtSoFar : '' }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if ($lineDone)
                                    <span class="w-14 text-[10px] font-semibold uppercase tracking-wide text-amber-700">Bought</span>
                                    <span class="w-16 text-right text-sm text-gray-500">{{ $boughtSoFar }}</span>
                                    <span class="w-24 text-right text-sm text-gray-500">&#8369;{{ number_format((float) $item->unit_price, 2) }}</span>
                                @else
                                    <input type="number" name="quantities[{{ $item->id }}]" data-qty-line="{{ $item->id }}" min="0" max="{{ $item->remainingQty() }}" step="1"
                                           value="{{ $item->remainingQty() }}"
                                           class="w-16 rounded-lg border border-gray-300 px-2 py-1.5 text-right text-sm focus:outline-none focus:ring-2 focus:ring-amber-300"
                                           aria-label="Quantity bought for {{ $item->item?->name ?? 'line' }}">
                                    <div class="flex items-center gap-1">
                                        <span class="text-xs text-gray-400">&#8369;</span>
                                        @if ($item->unit_price !== null)
                                            <input type="number" value="{{ number_format((float) $item->unit_price, 2, '.', '') }}" readonly disabled
                                                   class="w-24 cursor-not-allowed rounded-lg border border-gray-200 bg-gray-50 px-2 py-1.5 text-right text-sm text-gray-500"
                                                   aria-label="Price for {{ $item->item?->name ?? 'line' }} (locked)">
                                        @else
                                            <input type="number" name="prices[{{ $item->id }}]" data-price-line="{{ $item->id }}" min="0" step="0.01"
                                                   placeholder="0.00"
                                                   class="w-24 rounded-lg border border-gray-300 px-2 py-1.5 text-right text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-300"
                                                   aria-label="Price paid for {{ $item->item?->name ?? 'line' }}">
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-gray-100 px-5 py-3">
                    <p class="text-[11px] text-gray-400">Outstanding lines stay open for the next trip.</p>
                    <div class="flex items-center gap-2">
                        <button type="button" id="poBoughtCancel"
                                class="whitespace-nowrap rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                            Close
                        </button>
                        <button type="submit"
                                class="whitespace-nowrap rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 transition-colors cursor-pointer">
                            Record purchase
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @endif

    <script>
        // Record receipt: clicking Receive opens a modal that asks how many
        // units arrived. Receipts accumulate; the line only closes once its
        // full ordered quantity has been covered.
        (function () {
            const receiveModal = document.getElementById('poReceiveModal');
            const receiveForm = document.getElementById('poReceiveForm');
            const receiveOverlay = document.getElementById('poReceiveOverlay');
            const receiveCancel = document.getElementById('poReceiveCancel');
            const receiveItemName = document.getElementById('poReceiveItemName');
            const receiveRemaining = document.getElementById('poReceiveRemaining');
            const receiveQty = document.getElementById('poReceiveQty');
            const receiveMax = document.getElementById('poReceiveMax');

            function openReceiveModal() {
                receiveModal.classList.remove('hidden');
                receiveModal.classList.add('flex');
                if (receiveQty) {
                    receiveQty.focus();
                    receiveQty.select();
                }
            }

            function closeReceiveModal(focusBack) {
                receiveModal.classList.add('hidden');
                receiveModal.classList.remove('flex');
                if (focusBack) focusBack.focus();
            }

            if (receiveModal && receiveForm) {
                document.addEventListener('click', function (event) {
                    const trigger = event.target.closest('[data-po-receive]');
                    if (! trigger) {
                        return;
                    }

                    event.preventDefault();

                    const outstanding = parseInt(trigger.getAttribute('data-po-remaining') || '0', 10);
                    const ordered = trigger.getAttribute('data-po-ordered') || '';

                    receiveForm.setAttribute('action', trigger.getAttribute('data-po-action'));
                    receiveItemName.textContent = trigger.getAttribute('data-po-label') || 'This line';

                    if (receiveRemaining) {
                        receiveRemaining.textContent = ordered !== ''
                            ? outstanding + ' of ' + ordered + ' still outstanding.'
                            : '';
                    }
                    if (receiveQty) {
                        receiveQty.max = String(Math.max(1, outstanding));
                        receiveQty.value = String(Math.max(1, outstanding));
                    }
                    if (receiveMax) {
                        receiveMax.textContent = outstanding > 0 ? 'of ' + outstanding + ' outstanding' : '';
                    }

                    openReceiveModal();
                });

                if (receiveOverlay) {
                    receiveOverlay.addEventListener('click', () => closeReceiveModal());
                }
                if (receiveCancel) {
                    receiveCancel.addEventListener('click', () => closeReceiveModal());
                }

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && receiveModal && ! receiveModal.classList.contains('hidden')) {
                        closeReceiveModal();
                    }
                });
            }
        })();

        // Mark as bought (local purchase): open the price-confirmation modal for
        // the line the user picked from the items table.
        (function () {
            const boughtModal = document.getElementById('poBoughtModal');
            const boughtForm = document.getElementById('poBoughtForm');
            const boughtOverlay = document.getElementById('poBoughtOverlay');
            const boughtCancel = document.getElementById('poBoughtCancel');
            const boughtLineId = document.getElementById('boughtLineId');
            let boughtTrigger = null;

            function openBoughtModal(trigger) {
                if (!boughtModal) return;
                boughtModal.classList.remove('hidden');
                boughtModal.classList.add('flex');
                boughtTrigger = trigger || null;

                const lineId = trigger ? trigger.getAttribute('data-po-line-id') : null;
                boughtModal.querySelectorAll('[id^="boughtLineRow-"]').forEach(function (row) {
                    row.classList.toggle('hidden', row.id !== 'boughtLineRow-' + lineId);
                });

                if (trigger) {
                    if (boughtLineId) boughtLineId.value = lineId || '';

                    const qtyInput = boughtForm ? boughtForm.querySelector('input[data-qty-line="' + lineId + '"]') : null;
                    if (qtyInput) {
                        qtyInput.value = trigger.getAttribute('data-po-qty') || qtyInput.value;
                        qtyInput.max = qtyInput.value;
                    }

                    const priceInput = boughtForm ? boughtForm.querySelector('input[data-price-line="' + lineId + '"]') : null;
                    if (priceInput) priceInput.value = '';
                }

                if (boughtCancel) boughtCancel.focus();
            }
            function closeBoughtModal(focusBack) {
                if (!boughtModal) return;
                boughtModal.classList.add('hidden');
                boughtModal.classList.remove('flex');
                if (focusBack) focusBack.focus();
            }

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('[data-po-bought]');
                if (!trigger) {
                    return;
                }

                event.preventDefault();

                openBoughtModal(trigger);
            });

            if (boughtOverlay) {
                boughtOverlay.addEventListener('click', () => closeBoughtModal());
            }
            if (boughtCancel) {
                boughtCancel.addEventListener('click', () => closeBoughtModal(boughtTrigger));
            }
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && boughtModal && ! boughtModal.classList.contains('hidden')) {
                    closeBoughtModal(boughtTrigger);
                }
            });
        })();

        // Cancel and delete purchase order confirmation modals.
        (function () {
            const cancelBtn = document.getElementById('poCancelBtn');
            const cancelModal = document.getElementById('poCancelModal');
            const cancelOverlay = document.getElementById('poCancelOverlay');
            const cancelDismiss = document.getElementById('poCancelDismiss');

            const deleteBtn = document.getElementById('poDeleteBtn');
            const deleteModal = document.getElementById('poDeleteModal');
            const deleteOverlay = document.getElementById('poDeleteOverlay');
            const deleteDismiss = document.getElementById('poDeleteDismiss');

            function openModal(modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
            function closeModal(modal, focusBack) {
                if (! modal) return;
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (focusBack) focusBack.focus();
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', () => openModal(cancelModal));
            }
            if (cancelOverlay) {
                cancelOverlay.addEventListener('click', () => closeModal(cancelModal, cancelBtn));
            }
            if (cancelDismiss) {
                cancelDismiss.addEventListener('click', () => closeModal(cancelModal, cancelBtn));
            }

            if (deleteBtn) {
                deleteBtn.addEventListener('click', () => openModal(deleteModal));
            }
            if (deleteOverlay) {
                deleteOverlay.addEventListener('click', () => closeModal(deleteModal, deleteBtn));
            }
            if (deleteDismiss) {
                deleteDismiss.addEventListener('click', () => closeModal(deleteModal, deleteBtn));
            }

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                if (cancelModal && ! cancelModal.classList.contains('hidden')) closeModal(cancelModal, cancelBtn);
                if (deleteModal && ! deleteModal.classList.contains('hidden')) closeModal(deleteModal, deleteBtn);
            });
        })();
    </script>
@endsection
