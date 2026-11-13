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
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Current database card --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden lg:col-span-2">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Current Database</h2>
                <p class="mt-0.5 text-xs text-gray-500">Back-ups contain the entire database — structure and contents.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Driver</p>
                        <p class="text-sm text-gray-900">{{ $dbInfo['driver'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Database</p>
                        <p class="text-sm text-gray-900">{{ $dbInfo['database'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Host</p>
                        <p class="text-sm text-gray-900">{{ $dbInfo['host'] }}:{{ $dbInfo['port'] }}</p>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Latest back-up</p>
                        <p class="text-sm text-gray-900">{{ $latest['created'] ?? 'Not yet taken' }}</p>
                    </div>
                </div>

                @unless ($dbInfo['is_mysql'])
                    <p class="mt-4 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                        Back-up only supports MySQL. The current default connection is not MySQL, so “Download Database Back-up” will be disabled.
                    </p>
                @endunless

                {{-- Restore guide (for whoever administers the system). The card grows to fit it. --}}
                <div class="mt-6 border-t border-gray-100 pt-6">
                    <details id="restoreGuide" class="group rounded-lg border border-gray-200 bg-white transition-colors open:bg-gray-50">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-gray-900">Restore guide (for non-technical readers)</span>
                                <span class="mt-0.5 block text-xs text-gray-500">Step-by-step instructions to bring this back-up back, readable by anyone</span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform duration-200 ease-out group-open:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 6 6 6-6 6"/>
                            </svg>
                        </summary>

                        <div class="max-h-[560px] overflow-y-auto border-t border-gray-100 px-4 py-4 text-xs leading-relaxed text-gray-600">
                        <p class="mb-3 text-[11px] uppercase tracking-wider text-gray-400">Close with Esc.</p>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">When you would use this</p>
                                <ul class="mt-1 list-disc space-y-1 pl-5">
                                    <li>You open the system and a section looks empty or broken (projects, prep lists, quotations, purchase orders, items).</li>
                                    <li>You want to move the system to a new computer exactly as it was.</li>
                                    <li>You did not see any error, but the data is wrong in a way you cannot fix by re-entering it.</li>
                                </ul>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Step 1 — take a back-up of what you have now</p>
                                <p class="mt-1">This is the single most important step: it makes the restore reversible.</p>
                                <ol class="mt-1 list-decimal space-y-1 pl-5">
                                    <li>Click the big <span class="font-semibold text-gray-800">“Download Database Back-up”</span> button on the right side of this page.</li>
                                    <li>Wait for the green banner at the top that says <span class="font-semibold text-gray-800">“Database back-up created.”</span></li>
                                    <li>A new file name appears at the top of the back-up history list. Note down the file name (it looks like <span class="font-mono text-[11px] text-gray-800">purchasing-YYYY-MM-DD-HHMMSS.sql</span>).</li>
                                </ol>
                                <p class="mt-1">If that step failed with a red error message, stop here and contact your IT support. Do not try the restore.</p>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Step 2 — free the system</p>
                                <p class="mt-1">Tell everyone else using the system to stay out (this is a maintenance window). Then, from the project folder:</p>
                                <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-3 py-2 text-[11px] text-gray-100">php artisan down</pre>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Step 3 — restore</p>
                                <ol class="mt-1 list-decimal space-y-1 pl-5">
                                    <li>Open the Command Prompt: press the <span class="font-semibold text-gray-800">Windows key</span>, type <span class="font-semibold text-gray-800">cmd</span>, press <span class="font-semibold text-gray-800">Enter</span>.</li>
                                    <li>Paste the following line and press <span class="font-semibold text-gray-800">Enter</span>. Replace the file name with the one you noted in Step 1:</li>
                                </ol>
                                <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-3 py-2 text-[11px] text-gray-100">cmd /c "type {{ str_replace('/', '\\', storage_path('app/private/backups')) }}\purchasing-YYYY-MM-DD-HHMMSS.sql | mysql -h {{ $dbInfo['host'] }} -P {{ $dbInfo['port'] }} -u root -p purchasing_db"</pre>
                                <p class="mt-1">For example, if your newest back-up is <span class="font-mono text-[11px] text-gray-800">purchasing-2026-11-13-063811.sql</span>:</p>
                                <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-3 py-2 text-[11px] text-gray-100">cmd /c "type {{ str_replace('/', '\\', storage_path('app/private/backups')) }}\purchasing-2026-11-13-063811.sql | mysql -h {{ $dbInfo['host'] }} -P {{ $dbInfo['port'] }} -u root -p purchasing_db"</pre>
                                <ol class="mt-1 list-decimal space-y-1 pl-5" start="3">
                                    <li>It now asks for the MySQL root password. Type it (nothing will appear on the screen — this is normal) and press <span class="font-semibold text-gray-800">Enter</span>. If you don’t know the password, contact your IT support.</li>
                                    <li>Wait for the prompt to come back (a few seconds). A backup replaces the whole database; it worked if there is no error message.</li>
                                    <li>Bring the system back up:</li>
                                </ol>
                                <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-900 px-3 py-2 text-[11px] text-gray-100">php artisan up</pre>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Step 4 — check the restore worked</p>
                                <ol class="mt-1 list-decimal space-y-1 pl-5">
                                    <li>Log in as admin.</li>
                                    <li>Open <span class="font-semibold text-gray-800">Projects</span> — your old projects must be listed again (everything for this seed database, such as “Drugs for me”, must be there).</li>
                                    <li>Open <span class="font-semibold text-gray-800">Prep Lists</span>, <span class="font-semibold text-gray-800">Quotation</span>, <span class="font-semibold text-gray-800">Purchase Order</span>, and <span class="font-semibold text-gray-800">Price Index</span> — the old content must be back.</li>
                                    <li>If something is still missing, the very first backup you took in Step 1 is your safety net. Don’t delete anything; call your IT support with the error and the file names involved.</li>
                                </ol>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">What this restores — and what it doesn't</p>
                                <ul class="mt-1 list-disc space-y-1 pl-5">
                                    <li><span class="font-semibold text-gray-800">Restored:</span> everything kept in the database — users and roles (and the privilege matrix), projects, items, prep lists, quotations, purchase orders, receiving history, and activity logs.</li>
                                    <li><span class="font-semibold text-gray-800">Not restored:</span> the Excel files uploaded in the Quotation tracker (suppliers' quotation files and your generated RFQs). Those live in the `storage/app/private` folder, not the database; if you need them back, ask IT for a copy of that folder — the database cannot bring them back.</li>
                                </ul>
                                <p class="mt-2">Leaving the file will not log you out — the database is dropped/recreated, and the system will simply see the restored sessions.</p>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        {{-- Actions and history --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Back-ups</h2>
                <p class="mt-0.5 text-xs text-gray-500">The last {{ count($backups) }} back-up{{ count($backups) === 1 ? '' : 's' }}; only the 15 newest are kept.</p>
            </div>
            <div class="p-6">
                <form method="POST" action="{{ route('settings.backup.store') }}">
                    @csrf
                    <button type="submit" @disabled(! $dbInfo['is_mysql'])
                            class="flex w-full items-center justify-center gap-2 rounded-md border border-gray-300 bg-[#2a7a94] px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#236b80] disabled:cursor-not-allowed disabled:border-gray-300 disabled:bg-gray-100 disabled:text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Download Database Back-up
                    </button>
                </form>

                <div class="pt-4 border-t border-gray-100 mt-4">
                    <p class="mb-3 text-xs font-medium uppercase tracking-wider text-gray-500">Back-up history</p>
                    <ul class="space-y-2">
                        @forelse ($backups as $backup)
                            <li class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 p-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm text-gray-900" title="{{ $backup['filename'] }}">{{ $backup['filename'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $backup['created'] }} &bull; {{ $formatBytes($backup['size']) }}</p>
                                </div>
                                <div class="flex flex-shrink-0 items-center gap-2">
                                    <a href="{{ route('settings.backup.download', ['file' => $backup['filename']]) }}"
                                       class="text-xs font-medium text-[#2a7a94] hover:text-[#236b80]">
                                        Download
                                    </a>
                                    <form method="POST" action="{{ route('settings.backup.destroy', ['file' => $backup['filename']]) }}"
                                          onsubmit="return confirm('Delete this back-up?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="rounded-lg border border-dashed border-gray-300 px-3 py-6 text-center text-xs text-gray-500">
                                No back-ups yet. Take one now — it contains the whole database.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
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
    </script>
@endsection
