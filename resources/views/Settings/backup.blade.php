@extends('layouts.app')

@section('page-title', 'BACK-UP')
@section('title', 'Back-up - Purchasing')

@section('header-actions')
@endsection

@section('content')
    @php
        $formatBytes = function (int $bytes): string {
            if ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2).' MB';
            }

            return number_format($bytes / 1024, 2).' KB';
        };
    @endphp

    @if ($errors->any())
        <div class="mb-4 shrink-0 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Page header --}}
    <div class="mb-4 flex shrink-0 flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Back-up</h2>
            <p class="mt-1 text-xs text-gray-500">A complete snapshot of the database — structure and contents — kept on this machine.</p>
        </div>
        @unless ($dbInfo['is_mysql'])
            <p class="max-w-xs rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[11px] leading-snug text-amber-800">
                Back-up and restore only support MySQL. The current default connection is not MySQL, so the create button is disabled.
            </p>
        @endunless
    </div>

    <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1fr)_460px]">

        {{-- LEFT — Current database --}}
        <div class="flex flex-col gap-4">

            <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Current database</p>
                <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <p class="text-[9px] font-extrabold uppercase tracking-[0.1em] text-gray-500">Driver</p>
                        <p class="mt-1 text-sm font-bold text-gray-900">{{ $dbInfo['driver'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <p class="text-[9px] font-extrabold uppercase tracking-[0.1em] text-gray-500">Database</p>
                        <p class="mt-1 truncate text-sm font-bold text-gray-900" title="{{ $dbInfo['database'] }}">{{ $dbInfo['database'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <p class="text-[9px] font-extrabold uppercase tracking-[0.1em] text-gray-500">Host</p>
                        <p class="mt-1 text-sm font-bold text-gray-900">{{ $dbInfo['host'] }}<small class="font-medium text-gray-500">:{{ $dbInfo['port'] }}</small></p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                        <p class="text-[9px] font-extrabold uppercase tracking-[0.1em] text-gray-500">Latest back-up</p>
                        <p class="mt-1 text-sm font-bold text-gray-900">{{ $latest['created'] ?? 'Not yet taken' }}</p>
                    </div>
                </div>
            </div>

            {{-- Restore guide (for whoever administers the system). --}}
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
                <details id="restoreGuide" class="group transition-colors open:bg-gray-50">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 select-none">
                        <span class="min-w-0">
                            <span class="block text-xs font-semibold text-gray-900">Restore guide (for non-technical readers)</span>
                            <span class="mt-0.5 block text-[10px] text-gray-500">Step-by-step instructions to bring a back-up back, or use “Restore” on the right.</span>
                        </span>
                        <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 ease-out group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 6 6 6-6 6"/>
                        </svg>
                    </summary>

                    <div class="max-h-[560px] space-y-4 overflow-y-auto border-t border-gray-100 px-4 py-4 text-xs leading-relaxed text-gray-600">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400">Close with Esc.</p>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">When you would use this</p>
                            <ul class="mt-1 list-disc space-y-1 pl-5">
                                <li>You open the system and a section looks empty or broken (projects, prep lists, quotations, purchase orders, items).</li>
                                <li>You want to move the system to a new computer exactly as it was.</li>
                                <li>You did not see any error, but the data is wrong in a way you cannot fix by re-entering it.</li>
                            </ul>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Fastest path — restore in-app</p>
                            <ol class="mt-1 list-decimal space-y-1 pl-5">
                                <li>In the back-up history on the right, find the back-up you want and click <span class="font-semibold text-gray-800">Restore</span> on that row.</li>
                                <li>Type <span class="font-mono text-[11px] text-gray-800">RESTORE</span> when prompted, then confirm. A safety back-up of the current state is created automatically before anything changes.</li>
                                <li>Sessions (who is logged in right now) are never touched — you will not be logged out.</li>
                            </ol>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Step 1 — take a back-up of what you have now (if you prefer the command line)</p>
                            <p class="mt-1">This is the single most important step: it makes the restore reversible.</p>
                            <ol class="mt-1 list-decimal space-y-1 pl-5">
                                <li>Click the <span class="font-semibold text-gray-800">Create back-up</span> button on the right side of this page.</li>
                                <li>Wait for the green banner at the top that says <span class="font-semibold text-gray-800">“Database back-up created.”</span></li>
                                <li>A new file name appears at the top of the back-up history list (it looks like <span class="font-mono text-[11px] text-gray-800">purchasing-YYYY-MM-DD-HHMMSS.sql</span>).</li>
                            </ol>
                            <p class="mt-1">If that step failed with a red error message, stop here and contact your IT support. Do not try the restore.</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Step 2 — free the system</p>
                            <p class="mt-1">Tell everyone else using the system to stay out (this is a maintenance window). Then, from the project folder:</p>
                            <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-3 py-2 text-[11px] text-gray-100">php artisan down</pre>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Step 3 — restore (command line)</p>
                            <ol class="mt-1 list-decimal space-y-1 pl-5">
                                <li>Open the Command Prompt: press the <span class="font-semibold text-gray-800">Windows key</span>, type <span class="font-semibold text-gray-800">cmd</span>, press <span class="font-semibold text-gray-800">Enter</span>.</li>
                                <li>Paste the following line and press <span class="font-semibold text-gray-800">Enter</span>. Replace the file name with the one you noted in Step 1:</li>
                            </ol>
                            <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-3 py-2 text-[11px] text-gray-100">cmd /c "type {{ str_replace('/', '\\', storage_path('app/private/backups')) }}\purchasing-YYYY-MM-DD-HHMMSS.sql | mysql -h {{ $dbInfo['host'] }} -P {{ $dbInfo['port'] }} -u root -p purchasing_db"</pre>
                            <ol class="mt-1 list-decimal space-y-1 pl-5" start="3">
                                <li>It now asks for the MySQL root password. Type it (nothing will appear on the screen — this is normal) and press <span class="font-semibold text-gray-800">Enter</span>.</li>
                                <li>Wait for the prompt to come back (a few seconds). Then bring the system back up:</li>
                            </ol>
                            <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-3 py-2 text-[11px] text-gray-100">php artisan up</pre>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">What this restores — and what it doesn't</p>
                            <ul class="mt-1 list-disc space-y-1 pl-5">
                                <li><span class="font-semibold text-gray-800">Restored:</span> everything kept in the database — users and roles (and the privilege matrix), projects, items, prep lists, quotations, purchase orders, receiving history, and activity logs.</li>
                                <li><span class="font-semibold text-gray-800">Not restored:</span> the Excel files uploaded in the Quotation tracker. Those live in the `storage/app/private` folder, not the database.</li>
                                <li><span class="font-semibold text-gray-800">Command-line restore will sign everyone out</span> — the dump replays `DROP TABLE sessions`. The in-app Restore keeps sessions untouched.</li>
                            </ul>
                        </div>
                    </div>
                </details>
            </div>
        </div>

        {{-- RIGHT — Back-ups --}}
        <div class="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm self-start">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-gray-200 px-4 py-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">Back-ups</p>
                    <p class="mt-0.5 text-[10px] text-gray-500">The {{ \App\Support\BackupStorage::KEEP }} newest are kept; older ones are pruned automatically.</p>
                </div>
                <span class="inline-flex items-center whitespace-nowrap rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-bold text-gray-600">KEEP {{ \App\Support\BackupStorage::KEEP }}</span>
            </div>

            {{-- Create zone --}}
            <div class="shrink-0 border-b border-gray-200 bg-[#fbfdfe] px-4 py-3">
                <form method="POST" action="{{ route('settings.backup.store') }}">
                    @csrf
                    <button type="submit" @disabled(! $dbInfo['is_mysql'])
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#2a7a94] px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-[#236b80] disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Create back-up
                    </button>
                </form>
                <p class="mt-1.5 text-center text-[9.5px] text-gray-400">A snapshot of the whole database is written now. Oldest back-ups are pruned to keep the 15 newest.</p>
            </div>

            {{-- Back-up history --}}
            <div class="max-h-[420px] overflow-y-auto">
                <ul class="divide-y divide-gray-100">
                    @forelse ($backups as $backup)
                        <li class="flex items-center gap-3 px-3 py-2.5 transition-colors odd:bg-white even:bg-[#f2f7f8]">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-500">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-mono text-[11px] font-bold text-gray-900" title="{{ $backup['filename'] }}">{{ $backup['filename'] }}</p>
                                <p class="mt-0.5 text-[9.5px] text-gray-500">{{ $backup['created'] }} · {{ $formatBytes($backup['size']) }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1.5">
                                <a href="{{ route('settings.backup.download', ['file' => $backup['filename']]) }}"
                                   class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                   title="Download"
                                   aria-label="Download {{ $backup['filename'] }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                </a>
                                <button type="button"
                                        class="backup-restore-btn inline-flex h-7 items-center gap-1.5 rounded-md border border-[#cde4ec] bg-[#eef7fa] px-2 text-[10px] font-bold text-[#2a7a94] transition-colors hover:bg-[#e0edf2]"
                                        data-file="{{ $backup['filename'] }}"
                                        title="Restore from this back-up">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 14.5v4.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75v-4.25M12 3v12m0 0l-4.5-4.5M12 15l4.5-4.5"/>
                                    </svg>
                                    Restore
                                </button>
                                <form method="POST" action="{{ route('settings.backup.destroy', ['file' => $backup['filename']]) }}"
                                      onsubmit="return confirm('Delete this back-up?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex h-7 w-7 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600" title="Delete" aria-label="Delete {{ $backup['filename'] }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="border border-dashed border-gray-300 bg-gray-50 px-4 py-8 text-center text-xs text-gray-500">
                            No back-ups yet. Take one now — it contains the whole database.
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- Footer strip --}}
            <div class="flex shrink-0 items-center justify-between gap-3 border-t border-gray-200 px-4 py-2 text-[10px] text-gray-500">
                <span>Showing {{ count($backups) }} back-up{{ count($backups) === 1 ? '' : 's' }} · auto-pruned to the {{ \App\Support\BackupStorage::KEEP }} newest</span>
                <span>Restores never delete back-ups</span>
            </div>
        </div>
    </div>

    {{-- Restore confirmation modal --}}
    <div id="backupRestoreModal" class="hidden fixed inset-0 z-[70] items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="backupRestoreTitle">
        <div id="backupRestoreOverlay" class="absolute inset-0 bg-gray-900/60"></div>
        <form method="POST" action="{{ route('settings.backup.restore') }}" class="relative w-full max-w-md overflow-hidden rounded-lg bg-white shadow-xl">
            @csrf
            <input type="hidden" id="restoreFileInput" name="file" value="">
            <div class="flex items-start gap-3 border-b border-gray-100 px-5 py-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-50">
                    <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m0 3.75h.008M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78a1.5 1.5 0 0 0 1.29-2.25L13.71 3.86a1.5 1.5 0 0 0-2.58 0Z"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <h3 id="backupRestoreTitle" class="text-base font-bold text-gray-900">Restore from back-up?</h3>
                    <p class="mt-1 text-xs leading-snug text-gray-500">This overwrites the current database. A safety back-up of the current state is taken first.</p>
                </div>
            </div>
            <div class="space-y-3 px-5 py-4">
                <div class="rounded-md border border-red-100 bg-red-50/70 px-3 py-2 text-[11px] leading-snug text-red-800">
                    <b class="font-bold">Everything since the back-up was taken will be lost</b> — projects, prep lists, quotations, purchase orders, users, roles, and activity logs will be replaced by the contents of this file.
                </div>
                <div class="flex items-center gap-2.5 rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                    <svg class="h-4 w-4 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    <code id="restoreFileName" class="truncate font-mono text-[10px] font-bold text-gray-900"></code>
                </div>
                <div>
                    <label for="restorePhraseInput" class="mb-1 block text-[10px] font-semibold uppercase tracking-wider text-gray-500">Type <span class="font-mono text-red-600">RESTORE</span> to confirm</label>
                    <input id="restorePhraseInput" type="text" name="expected_restore_phrase"
                           placeholder="RESTORE" autocomplete="off" spellcheck="false"
                           class="h-9 w-full rounded-md border border-gray-300 px-3 text-sm font-medium uppercase tracking-wider text-gray-900 focus:outline-none focus:ring-2 focus:ring-red-200">
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 border-t border-gray-100 bg-[#fbfdfe] px-5 py-3">
                <button type="button" id="backupRestoreCancel"
                        class="h-9 rounded-md border border-gray-300 bg-white px-4 text-xs font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="backupRestoreConfirm" disabled
                        class="h-9 rounded-md bg-red-600 px-4 text-xs font-bold text-white transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500">
                    Restore database
                </button>
            </div>
        </form>
    </div>

    <script>
        // Collapse the restore guide on Escape.
        (function () {
            var guide = document.getElementById('restoreGuide');

            if (! guide) {
                return;
            }

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && guide.open) {
                    guide.open = false;
                }
            });
        })();

        // Restore modal: open from any row's Restore button; confirm only after
        // the operator types the exact phrase.
        (function () {
            var modal = document.getElementById('backupRestoreModal');
            var overlay = document.getElementById('backupRestoreOverlay');
            var cancelBtn = document.getElementById('backupRestoreCancel');
            var phraseInput = document.getElementById('restorePhraseInput');
            var confirmBtn = document.getElementById('backupRestoreConfirm');
            var fileInput = document.getElementById('restoreFileInput');
            var fileLabel = document.getElementById('restoreFileName');
            var openButtons = document.querySelectorAll('.backup-restore-btn');

            if (!modal || !fileInput || !phraseInput || !confirmBtn) {
                return;
            }

            function openModal(filename) {
                fileInput.value = filename;
                if (fileLabel) {
                    fileLabel.textContent = filename;
                }
                phraseInput.value = '';
                confirmBtn.disabled = true;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                phraseInput.focus();
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }

            openButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openModal(btn.dataset.file);
                });
            });

            if (overlay) {
                overlay.addEventListener('click', closeModal);
            }
            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeModal);
            }

            phraseInput.addEventListener('input', function () {
                confirmBtn.disabled = phraseInput.value.trim() !== 'RESTORE';
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });
        })();
    </script>
@endsection
