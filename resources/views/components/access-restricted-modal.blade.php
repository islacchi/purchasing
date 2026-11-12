{{-- Shared "Access restricted" dialog, opened by any element carrying
     data-access-restricted-modal-toggle. Include once per page. --}}

<div id="accessRestrictedModal"
     class="hidden fixed inset-0 z-[70] items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="accessRestrictedTitle">
    <div class="absolute inset-0 bg-gray-900/50" data-access-restricted-overlay></div>

    <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
        <div class="flex items-start gap-3 px-5 py-4">
            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-50">
                <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
                </svg>
            </span>
            <div class="min-w-0">
                <h3 id="accessRestrictedTitle" class="text-base font-bold text-gray-900">Access restricted</h3>
                <p class="mt-1 text-sm text-gray-500">Your role does not have access to that module.</p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-5 py-3">
            <button type="button" id="accessRestrictedOk"
                    class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors cursor-pointer">
                Okay
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Opened by any element carrying data-access-restricted-modal-toggle.
    (function () {
        if (window.__accessRestrictedModalInit) {
            return;
        }
        window.__accessRestrictedModalInit = true;

        var modal = document.getElementById('accessRestrictedModal');
        var overlay = modal ? modal.querySelector('[data-access-restricted-overlay]') : null;
        var ok = document.getElementById('accessRestrictedOk');

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            if (ok) ok.focus();
        }
        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('click', function (e) {
            var trigger = e.target.closest('[data-access-restricted-modal-toggle]');

            if (trigger) {
                e.preventDefault();
                e.stopPropagation();
                openModal();
                return;
            }

            if (overlay && e.target === overlay) {
                closeModal();
            }
        });

        if (ok) ok.addEventListener('click', closeModal);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && ! modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
@endpush
