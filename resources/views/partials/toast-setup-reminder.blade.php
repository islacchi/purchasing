{{--
    Admin-only "deadline notifications not set up" reminder.
    One click registers the Windows Task Scheduler entry that keeps the
    Laravel scheduler ticking (schedule:run every 5 minutes), which is what
    makes the `notify:deadlines` desktop toast fire automatically.
    Shown only on requests to the host itself (localhost / 127.0.0.1) — the
    task is always machine-local, so remote LAN browsers never see it.
    Shown until an administrator dismisses it, it gets installed, or it is
    already detected as installed on this host.
--}}
@php
    $requestHost = strtolower(request()->getHost());
    // The installer always registers the task on the machine running PHP, so it only
    // makes sense on requests made to the host itself. Remote LAN browsers never see it.
    $isLocalhost = in_array($requestHost, ['localhost', '127.0.0.1', '::1', '[::1]'], true);

    $taskPresent = $isLocalhost
        ? \Illuminate\Support\Facades\Cache::remember(
            'toast_setup_reminder.task_present',
            300,
            fn () => \App\Support\SchedulerInstaller::isInstalled()
        )
        : true;

    $toastReminderDismissed = auth()->check()
        && \Illuminate\Support\Facades\Cache::get('toast_setup_reminder.dismissed.' . auth()->id());
@endphp

@if (! $toastReminderDismissed && $taskPresent !== true && auth()->user()?->isAdministrator())
    <div class="toast-setup-reminder-overlay" role="status" aria-live="polite">
        <div class="flash-message flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 shadow-sm">
            <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.34 15.84c-.671 1.343.019 2.931 1.405 3.413.832.29 1.762.1 2.382-.457M6.318 6.318A9.44 9.44 0 0 0 3.75 12c0 2.294.848 4.408 2.25 6.057M17.25 6.057A8.956 8.956 0 0 1 20.25 12c0 2.294-.848 4.408-2.25 6.057M12 12v.01"/>
            </svg>
            <div class="min-w-0 flex-1">
                <p class="font-semibold">Windows notifications for delivery deadlines are not set up on this host</p>
                <p class="mt-1 text-xs text-amber-700">
                    The in-app bell works, but automatic desktop alerts need a Task Scheduler entry that runs
                    <code class="rounded bg-amber-100 px-1 py-0.5 font-mono">php artisan schedule:run</code> every 5 minutes.
                    Install it here and it keeps running on its own — it re-creates the entry safely if one already exists.
                </p>

                @error('toast_setup')
                    <p class="mt-2 rounded-md border border-red-200 bg-red-50 px-2 py-1.5 text-[11px] text-red-700">{{ $message }}</p>
                @enderror

                <form method="POST" action="{{ route('settings.toast-reminder.install') }}" class="mt-2">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-400">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v12m0 0 3.75 3.75M6.75 15 3 11.25M17.25 21v-12m0 0-3.75-3.75M17.25 9 21 12.75"/>
                        </svg>
                        Install now
                    </button>
                </form>

                <details class="mt-2 text-[11px] text-amber-700">
                    <summary class="cursor-pointer font-medium hover:text-amber-900">Install manually (if the button does not work)</summary>
                    <div class="mt-1.5">
                        <div class="flex items-start gap-2">
                            <code id="toastSetupCommand" class="block min-w-0 flex-1 overflow-x-auto whitespace-nowrap rounded-md bg-amber-100/70 px-2 py-1.5 font-mono text-[11px] text-amber-900">schtasks /Create /TN "{{ \App\Support\SchedulerInstaller::TASK_NAME }}" /SC MINUTE /MO 5 /TR "{{ \App\Support\SchedulerInstaller::taskCommand() }}" /F</code>
                            <button type="button" id="toastSetupCopy"
                                    class="shrink-0 rounded-md border border-amber-300 bg-amber-50 px-2 py-1.5 text-[11px] font-medium text-amber-800 hover:bg-amber-100 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-300">
                                Copy
                            </button>
                        </div>
                        <p class="mt-1">Adjust the php.exe path if your install differs. The entry runs as the current user at logon, which is why the toast shows while you're signed in.</p>
                    </div>
                </details>
            </div>
            <form method="POST" action="{{ route('settings.toast-reminder.dismiss') }}" class="shrink-0">
                @csrf
                <button type="submit"
                        class="rounded-md border border-amber-300 px-2.5 py-1.5 text-[11px] font-medium text-amber-800 hover:bg-amber-100 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-300"
                        title="Hide this reminder for your account">
                    Dismiss
                </button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var copy = document.getElementById('toastSetupCopy');
            if (!copy) return;
            copy.addEventListener('click', function () {
                var text = document.getElementById('toastSetupCommand').textContent.trim();
                var done = function () {
                    var original = copy.textContent;
                    copy.textContent = 'Copied!';
                    window.setTimeout(function () { copy.textContent = original; }, 1600);
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(done, done);
                    return;
                }
                var range = document.createRange();
                range.selectNodeContents(document.getElementById('toastSetupCommand'));
                var selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
                done();
            });
        })();
    </script>
@endif
