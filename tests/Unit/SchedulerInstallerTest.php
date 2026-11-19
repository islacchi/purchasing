<?php

namespace Tests\Unit;

use App\Support\SchedulerInstaller;
use Tests\TestCase;

class SchedulerInstallerTest extends TestCase
{
    public function test_task_command_runs_the_scheduler_without_flashing_a_console(): void
    {
        $command = SchedulerInstaller::taskCommand();

        $this->assertStringContainsString('schedule:run', $command);
        $this->assertStringContainsString('powershell.exe', $command);
        $this->assertStringContainsString('-WindowStyle', $command);
        $this->assertStringContainsString('Hidden', $command);
        $this->assertStringNotContainsString('"cmd"', $command);
    }

    public function test_task_command_invokes_php_with_the_artisan_path(): void
    {
        $command = SchedulerInstaller::taskCommand();

        $this->assertStringContainsString(SchedulerInstaller::artisanPath(), $command);
        $this->assertStringContainsString('schedule:run', $command);
    }
}
