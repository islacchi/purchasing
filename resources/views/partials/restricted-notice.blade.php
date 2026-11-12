{{--
    Shared "restricted notice" content: amber icon, title, reason.
    Used by every dialog that blocks an action (the access-restricted
    modal, the quotation removal blocked/denied dialog) and the
    standalone 403 page, so the visual language stays in one place.

    Expects: $title (string), $reason (string), $titleId (unique id
    referenced by the wrapper's aria-labelledby).
    Optional: $as — heading tag, defaults to 'h3' (dialog); 'h1' for
    a standalone page.
--}}
@php($as = $as ?? 'h3')
<span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-50">
    <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
    </svg>
</span>
<div class="min-w-0">
    <{{ $as }} id="{{ $titleId }}" class="text-base font-bold text-gray-900">{{ $title }}</{{ $as }}>
    <p class="mt-1 text-sm text-gray-500">{{ $reason }}</p>
</div>
