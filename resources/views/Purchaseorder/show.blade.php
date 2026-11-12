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

        $stages = \App\Models\PurchaseOrder::stages();
        $currentStage = $purchaseOrder->currentStageIndex();
        $isCancelled = $purchaseOrder->status === \App\Models\PurchaseOrder::STATUS_CANCELLED;

        $subtotal = $purchaseOrder->lineTotal();
        $freight = (float) $purchaseOrder->delivery_fee;
        $total = $subtotal + $freight;

        $receivedCount = $items->filter(fn ($item) => $item->isReceived())->count();
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

    <div class="flex h-full min-h-0 flex-col">

        {{-- Success messages are rendered once, globally, by layouts.app (partials.flash-success). --}}

        @if ($errors->any())
            <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Heading --}}
        <div class="mb-6 flex shrink-0 flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-bold text-gray-900">{{ $purchaseOrder->po_number }}</h2>
                    <span id="poStatusPill" class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $statusStyles[$status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $status }}
                    </span>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    Issued from quotation
                    @if ($quotationShow)
                        <a href="{{ $quotationShow }}"
                           class="font-medium text-[#2a7a94] hover:underline">{{ $quotation?->rfq_number }}</a>
                    @else
                        <span class="text-gray-400">Quotation removed</span>
                    @endif
                    &middot; {{ $prepList?->name ?? 'Prep list removed' }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
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
            <div class="mb-6 shrink-0 rounded-lg border border-gray-200 bg-white px-4 py-4 shadow-sm">
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

        {{-- Supplier and delivery --}}
        <div class="mb-4 grid shrink-0 grid-cols-1 gap-4 md:grid-cols-2">

            <div class="flex flex-col rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="flex min-h-[48px] shrink-0 items-center justify-between gap-2 border-b border-gray-200 px-4 py-2.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">Supplier</h3>
                </div>
                <div class="flex flex-1 flex-col justify-center px-4 py-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-[#2a7a94]/10 text-xs font-bold text-[#2a7a94]">
                            {{ $initials }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900">{{ $supplierName }}</p>
                            <p class="text-xs text-gray-500">{{ $supplier?->contact_person ?? 'No contact person' }}</p>
                        </div>
                    </div>

                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-gray-500">Email</dt>
                            <dd class="text-right text-xs font-medium text-gray-800">{{ $supplier?->email ?? '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-gray-500">Phone</dt>
                            <dd class="text-right text-xs font-medium text-gray-800">{{ $supplier?->contact_no ?? '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-gray-500">Address</dt>
                            <dd class="max-w-[70%] truncate text-right text-xs font-medium text-gray-800" title="{{ $supplier?->address }}">{{ $supplier?->address ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="flex flex-col rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="flex min-h-[46px] shrink-0 flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-2.5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">Order details</h3>

                    @if ($isCancelled)
                        <div class="flex min-w-0 items-center gap-1.5 rounded-md border border-red-100 bg-red-50/70 px-2 py-1">
                            <svg class="h-3.5 w-3.5 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
                            </svg>
                            <p class="whitespace-nowrap text-[10px] font-semibold uppercase tracking-wide text-red-600">Purchase order cancelled</p>
                            <span class="hidden max-w-[220px] truncate text-[11px] text-red-500/90 md:inline" title="{{ $purchaseOrder->cancel_reason ?: 'No reason recorded.' }}">&middot; {{ $purchaseOrder->cancel_reason ?: 'No reason recorded.' }}</span>
                        </div>
                    @endif
                </div>
                <div class="flex flex-1 flex-col justify-center px-4 py-3">
                    <dl class="space-y-1.5 text-sm">
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-gray-500">Date issued</dt>
                            <dd class="text-right text-xs font-medium text-gray-800">{{ $purchaseOrder->issued_on?->format('M j, Y') ?? 'Not issued yet' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-gray-500">Expected delivery</dt>
                            <dd class="text-right text-xs font-medium text-gray-800">{{ $purchaseOrder->expected_delivery_on?->format('M j, Y') ?? 'Not set' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-gray-500">Payment terms</dt>
                            <dd class="text-right text-xs font-medium text-gray-800">{{ $purchaseOrder->payment_terms ?? '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-gray-500">Deliver to</dt>
                            <dd class="text-right text-xs font-medium text-gray-800">{{ $purchaseOrder->deliver_to ?? '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <dt class="text-xs text-gray-500">Items</dt>
                            <dd class="text-right text-xs font-medium text-gray-800">{{ $items->count() }} line items</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Items ordered --}}
        <div class="flex min-h-[240px] flex-1 flex-col rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-4 py-2.5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-gray-700">Items ordered</h3>
                <div class="flex flex-wrap items-center gap-2">
                    <span id="receiptSummary" class="inline-flex items-center rounded-full border border-teal-100 bg-teal-50 px-2.5 py-1 text-xs font-medium text-[#2a7a94]">
                        {{ $receivedCount }} of {{ $items->count() }} received
                    </span>
                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                        {{ $items->count() }} items
                    </span>
                </div>
            </div>

            <div class="min-h-[120px] flex-1 overflow-auto">
                <table class="w-full text-xs">
                    <thead class="sticky top-0 z-10">
                        <tr class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th scope="col" class="w-12 px-3 py-2 text-center">#</th>
                            <th scope="col" class="px-3 py-2">Item</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2 text-center">Qty</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2 text-center">Shelf life req.</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2 text-center">Received</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2 text-right">Unit price</th>
                            <th scope="col" class="whitespace-nowrap px-3 py-2 text-right">Extended</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($items as $item)
                            @php
                                $minShelf = $item->min_remaining_months ?? 0;
                                $isReceived = $item->isReceived();
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-center text-xs text-gray-400">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2">
                                    <p class="font-medium text-gray-900">{{ $item->item?->name ?? 'Item removed' }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ $item->item?->unit ?? '' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-center text-gray-700">{{ $item->qty }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-center">
                                    @if ($minShelf > 0)
                                        <span class="text-gray-700" title="Minimum {{ $minShelf }} months remaining shelf life at delivery">&ge; {{ $minShelf }} mo</span>
                                    @else
                                        <span class="text-xs text-gray-400" title="Non-perishable">&mdash;</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-center">
                                    <form method="POST" action="{{ route('purchase-order.receive', ['purchaseOrder' => $purchaseOrder->id, 'purchaseOrderItem' => $item->id]) }}">
                                        @csrf
                                        <input type="checkbox"
                                               class="po-receipt h-4 w-4 cursor-pointer rounded border-gray-300 text-[#2a7a94] focus:ring-teal-300 disabled:cursor-not-allowed disabled:opacity-40"
                                               title="{{ $canManagePurchaseOrder
                                                   ? ($canReceive ? 'Tick when the full quantity for this line has arrived and passed the shelf-life check' : 'Send the PO to the supplier before recording receipts')
                                                   : 'Your role does not have access to that module.' }}"
                                               {{ $isReceived ? 'checked' : '' }}
                                               {{ ($canManagePurchaseOrder && ! $canReceive) ? 'disabled' : '' }}
                                               {{ $canManagePurchaseOrder ? ($canReceive ? 'onchange="this.form.submit()"' : '') : 'data-access-restricted-modal-toggle' }}
                                               aria-label="Mark {{ $item->item?->name ?? 'line' }} as received">
                                    </form>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 text-right text-gray-700">{{ $peso($item->unit_price) }}</td>
                                <td class="whitespace-nowrap px-3 py-2 text-right font-semibold text-gray-900">
                                    {{ $peso($item->extendedTotal()) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>

            {{-- Fixed summary — visible no matter how many rows scroll --}}
            <div class="flex shrink-0 items-center justify-between gap-4 border-t border-gray-100 bg-gray-50/60 px-4 py-2.5">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Total &middot; {{ $items->count() }} {{ $items->count() === 1 ? 'line item' : 'line items' }}</p>
                <p class="text-sm font-bold text-gray-900">{{ $peso($total) }}</p>
            </div>

            <div class="shrink-0 border-t border-gray-100 px-4 py-2.5">
                <p class="text-xs text-gray-500">
                    {{ $purchaseOrder->notes ?: 'No notes recorded for the supplier.' }}
                </p>
                <p class="mt-1 text-[11px] text-gray-400">
                    Tick <span class="font-medium text-gray-500">Received</span> when the full quantity for a line has arrived and passed the shelf-life check. Batch/lot expiry is recorded at receiving.
                </p>
            </div>
        </div>

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

    <script>
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
