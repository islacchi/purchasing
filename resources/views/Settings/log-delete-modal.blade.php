{{-- Activity log delete modal — the deletion button only unlocks after printing --}}
@if ($logDeleteLogs->isNotEmpty())
    <div id="logDeleteModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30" data-auto-open>
        <div role="dialog" aria-modal="true" aria-labelledby="logDeleteModalTitle"
             class="mx-4 w-full max-w-2xl rounded-lg border border-gray-200 bg-white p-5 shadow-xl">
            <style>
                @media print {
                    #logDeleteModal { display: block !important; position: absolute !important; inset: 0; background: #fff !important; }
                    #logDeleteModal * { background: #fff !important; color: #000 !important; border-color: #000 !important; box-shadow: none !important; }
                    #logDeleteModal [data-role=print] { display: none !important; }
                    #logDeleteModal form { display: none !important; }
                }
            </style>

            <div class="flex items-start justify-between">
                <div>
                    <h3 id="logDeleteModalTitle" class="text-lg font-bold text-gray-900">Delete activity logs</h3>
                    <p class="mt-1 text-xs text-gray-500">Print this modal — the deletion button unlocks after the print.</p>
                </div>
                <button type="button" data-role="close" aria-label="Close dialog"
                        class="text-gray-400 transition-colors hover:text-gray-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-width="1.8" d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mt-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Reason for deletion</p>
                <p class="mt-1 text-sm text-gray-900">{{ $logDeleteBatch['reason'] ?? '(none recorded)' }}</p>

                <p class="mt-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Log entries to delete ({{ $logDeleteLogs->count() }})</p>
                <table class="mt-2 w-full border-collapse text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border border-gray-200 p-2 text-left font-semibold text-gray-600">Time</th>
                            <th class="border border-gray-200 p-2 text-left font-semibold text-gray-600">User</th>
                            <th class="border border-gray-200 p-2 text-left font-semibold text-gray-600">Action</th>
                            <th class="border border-gray-200 p-2 text-left font-semibold text-gray-600">Module</th>
                            <th class="border border-gray-200 p-2 text-left font-semibold text-gray-600">Record</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logDeleteLogs as $log)
                            <tr class="border-t border-gray-100">
                                <td class="border border-gray-200 p-2 text-gray-700">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                                <td class="border border-gray-200 p-2 text-gray-700">{{ $log->user_name }}</td>
                                <td class="border border-gray-200 p-2 text-gray-700">{{ $log->action }}</td>
                                <td class="border border-gray-200 p-2 text-gray-700">{{ $log->module }}</td>
                                <td class="border border-gray-200 p-2 text-gray-700">{{ $log->record_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-5 flex items-center justify-end gap-3">
                <button type="button" data-role="print"
                        class="h-9 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Print for the record
                </button>
                <form method="POST" action="{{ route('settings.logs.delete.execute') }}">
                    @csrf
                    <button type="submit" data-role="delete" disabled
                            class="h-9 rounded-lg bg-red-600 px-3 text-sm font-medium text-white disabled:cursor-not-allowed disabled:bg-red-400">
                        Delete these logs
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('logDeleteModal');
            if (!modal) {
                return;
            }

            var delBtn = modal.querySelector('[data-role=delete]');
            var printBtn = modal.querySelector('[data-role=print]');
            var closeBtn = modal.querySelector('[data-role=close]');

            function closeModal() {
                modal.classList.add('hidden');
            }

            printBtn.addEventListener('click', function () {
                window.print();
            });

            // The deletion button unlocks only after the print.
            window.addEventListener('afterprint', function () {
                delBtn.disabled = false;
            });

            closeBtn.addEventListener('click', closeModal);

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            // Auto-open when a batch is pending, so the print can happen now.
            modal.classList.remove('hidden');
        })();
    </script>
@endif
