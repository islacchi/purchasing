<?php

namespace App\Support;

use Symfony\Component\Process\Process;

/**
 * One-click installer for the Windows Task Scheduler entry that keeps the
 * Laravel scheduler ticking (`php artisan schedule:run` every 5 minutes),
 * which is what lets `notify:deadlines` fire the desktop toast automatically.
 *
 * Modelled on the existing "Laravel Scheduler - pharma-rfq" task on this host.
 * Windows only; on any other OS `install()` fails gracefully with a message
 * and the admin can register the task manually.
 */
class SchedulerInstaller
{
    public const TASK_NAME = 'Laravel Scheduler - purchasing';

    /**
     * Resolve a usable php.exe CLI binary (not the Apache SAPI).
     */
    public static function phpCli(): string
    {
        // `php artisan serve` reports SAPI "cli-server" and PHP_BINARY points at php.exe.
        if (in_array(PHP_SAPI, ['cli', 'cli-server'], true) && is_file(PHP_BINARY)) {
            return PHP_BINARY;
        }

        // `php bin/console` under Apache (mod_php) reports PHP_BINARY as httpd.exe,
        // which cannot be passed to schtasks as an interpreter command.
        $xamppCandidate = 'C:\\xampp\\php\\php.exe';
        if (is_file($xamppCandidate)) {
            return $xamppCandidate;
        }

        // Last resort: rely on PATH (schtasks will fail with a clear message).
        return 'php';
    }

    public static function artisanPath(): string
    {
        return base_path('artisan');
    }

    /**
     * The command string registered with Task Scheduler.
     *
     * Wrapped in a hidden PowerShell so the scheduled run never flashes a
     * console window. The wrapper hides its own console, and the PHP child
     * shares it — the toast still pops up in the interactive session.
     */
    public static function taskCommand(): string
    {
        return '"powershell.exe" -NoProfile -WindowStyle Hidden -Command "& \'' . static::phpCli() . '\' \'' . static::artisanPath() . '\' schedule:run"';
    }

    /**
     * Best-effort detection of the task. Returns null when it cannot tell
     * (e.g. the current web user cannot read the caller's tasks).
     */
    public static function isInstalled(): ?bool
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        try {
            $process = new Process(
                ['schtasks', '/Query', '/TN', static::TASK_NAME],
                null,
                null,
                null,
                5
            );
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Create (or overwrite) the scheduled task. Returns ok + combined output.
     *
     * @return array{ok: bool, output: string}
     */
    public static function install(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return ['ok' => false, 'output' => 'Windows-only: register a task that runs "php artisan schedule:run" every 5 minutes manually.'];
        }

        try {
            $process = new Process(
                [
                    'schtasks', '/Create',
                    '/TN', static::TASK_NAME,
                    '/SC', 'MINUTE',
                    '/MO', '5',
                    '/TR', static::taskCommand(),
                    '/F',
                ],
                null,
                null,
                null,
                15
            );
            $process->run();

            $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());

            return ['ok' => $process->isSuccessful(), 'output' => $output];
        } catch (\Throwable $e) {
            return ['ok' => false, 'output' => $e->getMessage()];
        }
    }
}
