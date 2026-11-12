{{-- Shared "Access restricted" dialog, opened by any element carrying
     data-access-restricted-modal-toggle. Include once per page. --}}

<div id="accessRestrictedModal"
     class="hidden fixed inset-0 z-[70] items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="accessRestrictedTitle">
    <div class="absolute inset-0 bg-gray-900/50" data-access-restricted-overlay></div>

    <div class="relative w-full max-w-sm rounded-lg bg-white shadow-xl">
        <div class="flex items-start gap-3 px-5 py-4">
            @include('partials.restricted-notice', [
                'title' => 'Access restricted',
                'reason' => 'Your role does not have access to that module.',
                'titleId' => 'accessRestrictedTitle',
            ])
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
