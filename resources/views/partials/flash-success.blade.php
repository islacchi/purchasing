{{--
    Transparent success overlay.
    Floats above the page at the exact placement the old inline banner used
    to occupy (top of the content area), so it is visible on every module
    without pushing the page content down. Auto-dismissed by the layout JS.
--}}
@if (session('status'))
    <div class="flash-success-overlay" aria-live="polite">
        <div class="flash-message flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 shadow-sm" role="status">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 13 4 4L19 7"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    </div>
@endif
