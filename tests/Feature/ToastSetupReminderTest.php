<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToastSetupReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The banner's task detection caches its answer; pin it to "not installed"
        // so the test does not depend on whether this machine happens to have
        // a "Laravel Scheduler - purchasing" entry.
        \Illuminate\Support\Facades\Cache::put('toast_setup_reminder.task_present', false, 300);
    }

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    public function test_admin_sees_reminder_on_localhost_requests(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('http://localhost/projects');

        $response->assertOk();
        $response->assertSee('Install now', false);
    }

    public function test_admin_does_not_see_reminder_on_lan_host_requests(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get('http://192.168.1.50/projects');

        $response->assertOk();
        $response->assertDontSee('Install now', false);
    }

    public function test_non_admin_never_sees_reminder(): void
    {
        $this->seed(AdminUserSeeder::class);
        $user1 = User::where('name', 'user1')->firstOrFail();

        $response = $this->actingAs($user1)->get('http://localhost/projects');

        $response->assertOk();
        $response->assertDontSee('Install now', false);
    }

    public function test_dismissed_reminder_stays_dismissed_for_localhost_admins(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('settings.toast-reminder.dismiss'))
            ->assertRedirect();

        $response = $this->actingAs($admin)->get('http://localhost/projects');

        $response->assertOk();
        $response->assertDontSee('Install now', false);
    }
}
