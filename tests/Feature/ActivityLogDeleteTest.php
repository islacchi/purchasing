<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function logRow(string $label): ActivityLog
    {
        $admin = $this->admin();

        return ActivityLog::create([
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'role_name' => 'Administrator',
            'action' => 'Created',
            'module' => 'Tests',
            'record_label' => $label,
            'created_at' => now(),
        ]);
    }

    public function test_reason_is_required_when_deleting(): void
    {
        $admin = $this->admin();
        $log = $this->logRow('Doomed entry');

        $this->actingAs($admin)
            ->post(route('settings.logs.delete.prepare'), ['logs' => [$log->id]])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    }

    public function test_a_log_selection_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('settings.logs.delete.prepare'), ['reason' => 'Just testing'])
            ->assertSessionHasErrors('logs');
    }

    public function test_deletion_requires_print_confirmation_before_it_happens(): void
    {
        $admin = $this->admin();
        $log = $this->logRow('Deletable entry');

        $this->actingAs($admin)
            ->post(route('settings.logs.delete.prepare'), [
                'logs' => [$log->id],
                'reason' => 'Stale duplicate',
            ])
            ->assertRedirect(route('settings.logs.delete.confirm'))
            ->assertSessionHas('logs_delete_batch');

        // The log is still there: nothing is deleted until the final confirm.
        $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);

        $this->actingAs($admin)
            ->get(route('settings.logs.delete.confirm'))
            ->assertOk()
            ->assertSee('Deletable entry')
            ->assertSee('Stale duplicate');

        $this->actingAs($admin)
            ->post(route('settings.logs.delete.execute'))
            ->assertRedirect(route('settings.logs'))
            ->assertSessionHas('status', 'Deleted 1 activity log');

        $this->assertDatabaseMissing('activity_logs', ['id' => $log->id]);
    }

    public function test_confirm_page_without_a_pending_batch_redirects_back(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.logs.delete.confirm'))
            ->assertRedirect(route('settings.logs'));
    }
}
