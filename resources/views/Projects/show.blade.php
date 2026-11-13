@extends('layouts.app')

@section('page-title', 'PROJECTS')
@section('title', 'Project Details - Purchasing')

@section('header-actions')
@endsection

@section('content')
    {{-- Print styles: hide the app chrome so only the project summary prints. --}}
    <style>
        /* Anti-delete warning overlay — floats above the content area. */
        #antiDeleteBanner {
            position: fixed;
            top: 9rem;
            left: calc(5rem + 2rem);
            right: 2rem;
            z-index: 60;
            pointer-events: none;
            opacity: 1;
            transform: translateY(0);
            transition: opacity 300ms ease, transform 300ms ease;
        }
        html.sidebar-expanded #antiDeleteBanner { left: calc(16rem + 2rem); }
        #antiDeleteBanner.is-hiding { opacity: 0; transform: translateY(-4px); }

        /* Print sheet is hidden on screen; visible only for printing. */
        .print-sheet { display: none; }

        @media print {
            /* Officio (8.5 × 13 in), landscape — 13 in wide, 8.5 in tall */
            @page { size: 13in 8.5in; margin: 10mm; }
            #sidebar,
            header,
            .flash-success-overlay,
            #signoutModal,
            #userMenu,
            .no-print,
            .screen-only,
            #printPreviewModal,
            #projectDeleteModal { display: none !important; }
            .print-sheet { display: block !important; width: 100% !important; padding: 0 !important; }
            html, body { overflow: visible !important; height: auto !important; background: #fff !important; }
            main { overflow: visible !important; background: #fff !important; padding: 0 !important; }
            .rounded-lg { border-radius: 0 !important; }
            table thead { display: table-header-group; }
            table tr { page-break-inside: avoid; }
        }
    </style>

    <div class="screen-only flex h-full min-h-0 flex-col">
    <div class="flex min-h-0 flex-1 flex-col">

    @php
        $totalItems = $project->totalItemCount();
        $fulfilledItems = $project->fulfilledItemCount();
        $status = $project->statusLabel();
        $statusColor = $project->statusColor();

        $statusPillClasses = [
            'gray' => 'bg-gray-100 text-gray-700',
            'blue' => 'bg-blue-100 text-blue-700',
            'green' => 'bg-green-100 text-green-700',
        ];

        $modeLabels = [
            'shopping' => 'Shopping',
            'small_value' => 'Small value',
            'competitive_bidding' => 'Competitive bidding',
            'direct_contracting' => 'Direct contracting',
        ];

        $modeLabel = $modeLabels[$project->mode_of_procurement]
            ?? ucfirst(str_replace('_', ' ', $project->mode_of_procurement));

        $deliveryPeriod = $project->delivery_days !== null
            ? $project->delivery_days.' days'
            : '—';

        if ($project->delivery_due_date !== null) {
            $deliveryPeriod .= ' (due '.$project->delivery_due_date->format('M j, Y').')';
        }

        $progressLabel = $totalItems > 0
            ? $fulfilledItems.' / '.$totalItems.' items fulfilled'
            : 'No items recorded yet';
    @endphp

    {{-- Project Header --}}
    <div class="flex shrink-0 items-start justify-between mb-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ $project->title }}</h2>
            <p class="text-sm text-gray-500 mt-1">{{ $progressLabel }}</p>
        </div>
        <div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusPillClasses[$statusColor] }}">
                {{ $status }}
            </span>
        </div>
    </div>

    {{-- Success messages are rendered once, globally, by layouts.app (partials.flash-success). --}}

    @if ($errors->any())
        <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @php
        $preplistSources = $preplistSources ?? collect();
    @endphp

    {{-- Anti-delete warning: floats above the view (same pattern as the flash-success
         overlay) so fading it out does not shift the layout underneath. --}}
    @if ($preplistSources->isNotEmpty())
        <div id="antiDeleteBanner" class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-lg" role="alert">
            <p class="font-semibold">This project cannot be deleted.</p>
            <p class="mt-0.5 text-xs text-amber-700">
                It is used by the prep list(s)
                <span class="font-medium text-amber-900">{{ $preplistSources->map(fn ($item) => $item->reference_no.' — '.$item->name)->implode(', ') }}</span>.
                Remove its items from those prep lists first.
            </p>
        </div>
    @endif

    {{-- Main Content --}}
    <div class="grid min-h-0 flex-1 auto-rows-[minmax(0,1fr)] grid-cols-1 gap-6 lg:grid-cols-4">
        {{-- Project Details (Left Column) — static --}}
        <div class="min-h-0 lg:col-span-1">
            <div class="bg-white rounded-lg border border-gray-200 h-full">
                <div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Entity</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project->procuringEntity?->name ?? '—' }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Reference no.</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project->reference_no }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mode of procurement</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $modeLabel }}</p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Amount awarded</label>
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $project->amount_awarded !== null ? '₱ ' . number_format((float) $project->amount_awarded, 2) : '—' }}
                        </p>
                    </div>
                    <div class="p-4 border-b border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Date awarded</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $project->date_awarded?->format('F j, Y') ?? '—' }}</p>
                    </div>
                    <div class="p-4">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Delivery period</label>
                        <p class="text-sm font-semibold text-gray-900">{{ $deliveryPeriod }}</p>
                    </div>
                    <div class="p-4 border-t border-gray-200">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Notes</label>
                        <p class="text-sm text-gray-900">{{ $project->notes ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items Table (Right Column) — scrolls vertically --}}
        <div class="min-h-0 lg:col-span-3">
            <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white">
                <div class="min-h-[240px] flex-1 overflow-y-auto overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 border-b border-gray-300 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Item description</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700">To procure</th>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-700">Unit</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700">Unit cost</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700">Quoted price</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-700">Total</th>
                                <th class="px-4 py-2 text-center text-xs font-semibold text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($project->items as $projectItem)
                                @php
                                    $unitPrice = $projectItem->awarded_unit_price ?? $projectItem->unit_cost;
                                    $remaining = $projectItem->remainingQuantity();
                                    $lineTotal = $remaining * (float) $unitPrice;
                                    $fulfillmentStatus = $projectItem->isFulfilled() ? 'fulfilled' : ($projectItem->fulfillment_status ?: 'pending');

                                    $itemStatus = ucfirst(str_replace('_', ' ', $fulfillmentStatus));
                                    $itemStatusClasses = match ($fulfillmentStatus) {
                                        'fulfilled' => 'bg-green-100 text-green-700',
                                        'waived' => 'bg-orange-100 text-orange-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $projectItem->item?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $remaining }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $projectItem->item?->unit ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">₱ {{ number_format((float) $projectItem->unit_cost, 2) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">
                                        {{ $projectItem->awarded_unit_price !== null ? '₱ ' . number_format((float) $projectItem->awarded_unit_price, 2) : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 text-right">₱ {{ number_format($lineTotal, 2) }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $itemStatusClasses }}">
                                            {{ $itemStatus }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-sm text-gray-500">
                                        No items recorded for this project yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="no-print mt-4 flex shrink-0 items-center justify-between gap-3">
        <a href="{{ route('projects') }}"
           class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
            </svg>
            Back to list
        </a>
        <div class="flex items-center gap-3">
        @if ($preplistSources->isNotEmpty())
            <button type="button" disabled
                    class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-200 text-sm font-medium text-gray-400 cursor-not-allowed"
                    title="Used by a prep list and cannot be deleted">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete project
            </button>
        @elseif (auth()->user()?->canModule('projects', 'manage'))
            <form id="projectDeleteForm" method="POST" action="{{ route('projects.destroy', ['project' => $project->id]) }}">
                @csrf
                @method('DELETE')
                <button type="button" id="projectDeleteBtn" aria-haspopup="dialog"
                        class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete project
                </button>
            </form>
        @else
            {{-- View-only role: never shows the delete confirmation, opens the
                 restricted dialog instead (mirrors the quotation Remove flow). --}}
            <button type="button" data-access-restricted-modal-toggle
                    title="Your role does not have access to that module."
                    class="flex cursor-not-allowed items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Delete project
            </button>
        @endif
        @if (auth()->user()?->canModule('projects', 'manage'))
            <button type="button" id="printProjectBtn"
                    class="no-print flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17m-5 0a2 2 0 104 0 2 2 0 10-4 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V4a1 1 0 011-1h10a1 1 0 011 1v5M6 7h12"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 13H4a2 2 0 01-2-2v-2a2 2 0 012-2h16a2 2 0 012 2v2a2 2 0 01-2 2h-2m-12 0v6a1 1 0 001 1h10a1 1 0 001-1v-6"/>
                </svg>
                Print Project
            </button>
        @else
            <span class="no-print flex cursor-not-allowed items-center gap-2 px-4 py-2 rounded-md border border-gray-200 text-sm font-medium text-gray-300"
                  title="Your role does not have access to that module.">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17m-5 0a2 2 0 104 0 2 2 0 10-4 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V4a1 1 0 011-1h10a1 1 0 011 1v5M6 7h12"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 13H4a2 2 0 01-2-2v-2a2 2 0 012-2h16a2 2 0 012 2v2a2 2 0 01-2 2h-2m-12 0v6a1 1 0 001 1h10a1 1 0 001-1v-6"/>
                </svg>
                Print Project
            </span>
        @endif
        @if (auth()->user()?->canModule('projects', 'manage'))
            <a href="{{ route('projects.edit', ['project' => $project->id]) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-md border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit project
            </a>
        @else
            <span class="flex cursor-not-allowed items-center gap-2 px-4 py-2 rounded-md border border-gray-200 text-sm font-medium text-gray-400"
                  title="Your role does not have access to that module.">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit project
            </span>
        @endif
        </div>
    </div>

    </div><!-- /flex column -->
    </div>{{-- /screen-only --}}

    {{-- Print sheet: hidden on screen; what window.print() sends to the printer (landscape). --}}
    <div id="projectPrintSheet" class="print-sheet">
        @include('Projects.print-sheet')
    </div>

    {{-- Print preview modal --}}
    <div id="printPreviewModal" class="screen-only hidden fixed inset-0 z-[70] items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="printPreviewTitle">
        <div id="printPreviewOverlay" class="absolute inset-0 bg-gray-900/60"></div>
        <div class="relative flex h-[85vh] w-full max-w-6xl flex-col overflow-hidden rounded-xl bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                <div>
                    <h3 id="printPreviewTitle" class="text-sm font-semibold text-gray-900">Print preview</h3>
                    <p class="mt-0.5 text-[11px] text-gray-500">8.5 × 13 in officio (landscape) — generated from the current project data.</p>
                </div>
                <button type="button" data-close-print-modal aria-label="Close print preview"
                        class="rounded-md p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-auto bg-gray-100 p-4 sm:p-6">
                <div class="mx-auto w-full max-w-[1200px] rounded-md bg-white p-6 shadow-md">
                    @include('Projects.print-sheet')
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-5 py-3">
                <button type="button" data-close-print-modal
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Close
                </button>
                <button type="button" id="printPreviewPrintBtn"
                        class="flex items-center gap-2 rounded-md bg-[#2a7a94] px-4 py-2 text-sm font-medium text-white hover:bg-[#236b80] transition-colors"
                        title="Opens the print dialog — pick your printer or 'Save as PDF'">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 9V4a1 1 0 011-1h10a1 1 0 011 1v5M6 17h12M6 13H4a2 2 0 01-2-2v-3a2 2 0 012-2h16a2 2 0 012 2v3a2 2 0 01-2 2h-2m-12 0v6a1 1 0 001 1h10a1 1 0 001-1v-6"/>
                    </svg>
                    Print / Save as PDF…
                </button>
            </div>
        </div>
    </div>

    {{-- Delete project confirmation modal --}}
    <div id="projectDeleteModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="projectDeleteTitle">
        <div id="projectDeleteOverlay" class="absolute inset-0 bg-gray-900/50"></div>

        <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
            <div class="flex items-start gap-3 px-5 py-4">
                <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-red-50">
                    <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 id="projectDeleteTitle" class="text-base font-bold text-gray-900">Delete project?</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        <span class="font-medium text-gray-700">{{ $project->title }}</span>
                        and all of its items will be permanently deleted. This action cannot be undone.
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
                <button type="button" id="projectDeleteCancel"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="button" id="projectDeleteConfirm"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition-colors cursor-pointer">
                    Delete
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Print preview → click Print Project opens a modal with the print sheet;
        // the merged "Print / Save as PDF…" button sends it to the browser dialog.
        (function () {
            var openBtn = document.getElementById('printProjectBtn');
            var closeButtons = document.querySelectorAll('[data-close-print-modal]');
            var printBtn = document.getElementById('printPreviewPrintBtn');
            var modal = document.getElementById('printPreviewModal');
            var overlay = document.getElementById('printPreviewOverlay');

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                printBtn.focus();
            }
            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (openBtn) openBtn.focus();
            }

            function sanitizeFilename(name) {
                // Keep the suggested "Save as PDF" filename simple and OS-safe.
                return String(name || 'project')
                    .replace(/[^a-z0-9 _\-\.]/gi, '')
                    .trim()
                    .replace(/\s+/g, '_');
            }

            if (openBtn) openBtn.addEventListener('click', openModal);
            closeButtons.forEach(function (btn) { btn.addEventListener('click', closeModal); });
            if (overlay) overlay.addEventListener('click', closeModal);

            // The print dialog doubles as "Save as PDF"; rename the document
            // first so the suggested PDF filename matches the project's
            // reference number, then restore it once the dialog closes.
            if (printBtn) {
                printBtn.addEventListener('click', function () {
                    var originalTitle = document.title;
                    document.title = sanitizeFilename(@json($project->reference_no . '_Project'));
                    window.print();
                    window.addEventListener('afterprint', function restoreTitle() {
                        document.title = originalTitle;
                        window.removeEventListener('afterprint', restoreTitle);
                    });
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        })();
    </script>
    @endpush

    @push('scripts')
    <script>
        // Delete project confirmation modal
        (function () {
            const btn = document.getElementById('projectDeleteBtn');
            const modal = document.getElementById('projectDeleteModal');
            const overlay = document.getElementById('projectDeleteOverlay');
            const cancelBtn = document.getElementById('projectDeleteCancel');
            const confirmBtn = document.getElementById('projectDeleteConfirm');
            const form = document.getElementById('projectDeleteForm');

            function openModal() {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                confirmBtn.focus();
            }
            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                if (btn) btn.focus();
            }

            if (btn) {
                btn.addEventListener('click', openModal);
            }
            cancelBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', closeModal);
            confirmBtn.addEventListener('click', function () { form.submit(); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        })();
    </script>
    @endpush

    @push('scripts')
    <script>
        // Anti-delete warning: auto-dismiss after a few seconds like the flash success message.
        (function () {
            var banner = document.getElementById('antiDeleteBanner');
            if (!banner) return;

            setTimeout(function () {
                banner.classList.add('is-hiding');
            }, 4000);
            setTimeout(function () {
                banner.classList.add('hidden');
            }, 4350);
        })();
    </script>
    @endpush

@endsection
