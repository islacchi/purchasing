<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogPruneTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function logRow(User $admin, int $ageInDays, string $label): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'role_name' => 'Administrator',
            'action' => 'Created',
            'module' => 'Tests',
            'record_label' => $label,
            'created_at' => now()->subDays($ageInDays),
        ]);
    }

    public function test_prune_deletes_only_entries_older_than_the_window(): void
    {
        $admin = $this->admin();
        $this->logRow($admin, 400, 'Old entry');
        $this->logRow($admin, 10, 'Recent entry');

        $this->artisan('activity:prune', ['--days' => 365])->assertSuccessful();

        $this->assertDatabaseMissing('activity_logs', ['record_label' => 'Old entry']);
        $this->assertDatabaseHas('activity_logs', ['record_label' => 'Recent entry']);
    }

    public function test_dry_run_deletes_nothing(): void
    {
        $admin = $this->admin();
        $this->logRow($admin, 400, 'Old entry');
        $this->logRow($admin, 10, 'Recent entry');

        $this->artisan('activity:prune', ['--days' => 365, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('activity_logs', ['record_label' => 'Old entry']);
        $this->assertDatabaseHas('activity_logs', ['record_label' => 'Recent entry']);
    }

    public function test_days_must_be_at_least_one(): void
    {
        $admin = $this->admin();
        $this->logRow($admin, 1, 'Recent entry');

        $this->artisan('activity:prune', ['--days' => 0])->assertFailed();

        $this->assertDatabaseHas('activity_logs', ['record_label' => 'Recent entry']);
    }

    public function test_prune_accepts_a_custom_window(): void
    {
        $admin = $this->admin();
        $this->logRow($admin, 40, 'Fortieth day entry');
        $this->logRow($admin, 20, 'Twentieth day entry');

        $this->artisan('activity:prune', ['--days' => 30])->assertSuccessful();

        $this->assertDatabaseMissing('activity_logs', ['record_label' => 'Fortieth day entry']);
        $this->assertDatabaseHas('activity_logs', ['record_label' => 'Twentieth day entry']);
    }
}
