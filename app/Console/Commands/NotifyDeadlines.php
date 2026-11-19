<?php

namespace App\Console\Commands;

use App\Support\DeliveryNotifications;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class NotifyDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:deadlines
                            {--days=14 : Maximum days ahead to consider a project "due"}
                            {--dry-run : Print what would be sent, do not fire a toast}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fire a Windows toast for projects with approaching or overdue delivery deadlines (respects the same criteria as the bell)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->error('--days must be 1 or greater.');

            return self::FAILURE;
        }

        $notifications = DeliveryNotifications::projects();

        if ($notifications->isEmpty()) {
            $this->info('No projects with approaching delivery deadlines.');

            return self::SUCCESS;
        }

        $red = $notifications->where('severity', 'red')->count();
        $amber = $notifications->where('severity', 'amber')->count();

        $lines = $notifications->take(4)
            ->map(fn ($n) => '• '.$n['title'].' — '.$n['label'].' ('.$n['due'].')')
            ->join("\n");

        $body = $red > 0
            ? $red.' overdue/near-delivery'.($red === 1 ? '' : 's').', '.$amber.' coming soon.'
            : $amber.' coming soon.';

        $message = $body."\n".$lines;

        if ($this->option('dry-run')) {
            $this->line($message);

            return self::SUCCESS;
        }

        return $this->toast('Purchasing · Delivery deadlines', $message) ? self::SUCCESS : self::FAILURE;
    }

    private function toast(string $title, string $message): bool
    {
        // Title and message are passed via environment variables so any text
        // (punctuation, newlines) goes in without quoting surprises.
        $path = tempnam(sys_get_temp_dir(), 'notify_toast_').'.ps1';
        $script = <<<PS
[Windows.UI.Notifications.ToastNotificationManager,Windows.UI.Notifications,ContentType=WindowsRuntime] | Out-Null
[Windows.UI.Notifications.ToastNotification,Windows.UI.Notifications,ContentType=WindowsRuntime] | Out-Null
[Windows.Data.Xml.Dom.XmlDocument,Windows.Data.Xml.Dom,ContentType=WindowsRuntime] | Out-Null
\$template = [Windows.UI.Notifications.ToastNotificationManager]::GetTemplateContent([Windows.UI.Notifications.ToastTemplateType]::ToastText02)
\$xml = New-Object -TypeName Windows.Data.Xml.Dom.XmlDocument
\$xml.LoadXml(\$template.GetXml())
\$texts = \$xml.GetElementsByTagName('text')
\$texts.Item(0).AppendChild(\$xml.CreateTextNode(\$env:TOAST_TITLE)) | Out-Null
\$texts.Item(1).AppendChild(\$xml.CreateTextNode(\$env:TOAST_MESSAGE)) | Out-Null
\$toast = New-Object -TypeName Windows.UI.Notifications.ToastNotification -ArgumentList \$xml
[Windows.UI.Notifications.ToastNotificationManager]::CreateToastNotifier('Purchasing').Show(\$toast)
PS;
        file_put_contents($path, $script);

        $env = array_merge($_SERVER, [
            'TOAST_TITLE' => $title,
            'TOAST_MESSAGE' => $message,
        ]);

        // Launch PowerShell through the windowless VBS runner so this can
        // never flash a console — even though the scheduler itself already
        // runs windowless, a windowless parent still triggers a fresh
        // console for a console-subsystem child.
        $process = new Process([
            (getenv('SystemRoot') ?: 'C:\\Windows') . '\\System32\\wscript.exe',
            '//nologo',
            base_path('resources/scripts/hidden.vbs'),
            'powershell.exe',
            '-NoProfile',
            '-ExecutionPolicy', 'Bypass',
            '-File', $path,
        ], null, $env, null, 15);
        $process->run();
        @unlink($path);

        if (! $process->isSuccessful()) {
            $this->error('Toast failed:');
            $this->line(trim($process->getErrorOutput() ?: $process->getOutput()));

            return false;
        }

        return true;
    }
}
