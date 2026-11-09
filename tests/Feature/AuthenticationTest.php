<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_with_username_and_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->post('/login', [
            'login' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertRedirect('/projects');
        $this->assertAuthenticated();
    }

    public function test_admin_can_login_with_email_and_password(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->post('/login', [
            'login' => 'admin@example.com',
            'password' => 'admin',
        ]);

        $response->assertRedirect('/projects');
        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->from('/login')->post('/login', [
            'login' => 'admin',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_login_with_unknown_account_displays_a_clear_message(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->from('/login')->post('/login', [
            'login' => 'missing-user',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['login' => 'No account exists for that username or email.']);
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login_and_is_told_to_contact_an_administrator(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('name', 'admin')->firstOrFail();
        $admin->update(['is_active' => false]);

        $response = $this->from('/login')->post('/login', [
            'login' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'login' => 'This account is inactive or disabled. Please contact an administrator to activate your account.',
        ]);
        $this->assertGuest();
    }

    public function test_active_account_with_wrong_password_displays_a_clear_message(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->from('/login')->post('/login', [
            'login' => 'admin',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['password' => 'The password you entered is incorrect.']);
        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/projects')->assertRedirect('/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Purchasing Login');
    }

    public function test_first_load_always_shows_the_login_view_even_when_authenticated(): void
    {
        $this->seed(AdminUserSeeder::class);

        $user = User::where('name', 'admin')->firstOrFail();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/login');

        $this->actingAs($user)
            ->get('/login')
            ->assertOk()
            ->assertSee('Purchasing Login');

        $this->assertAuthenticated();
    }

    public function test_recently_active_session_can_access_authenticated_routes(): void
    {
        $this->seed(AdminUserSeeder::class);

        $user = User::where('name', 'admin')->firstOrFail();
        $now = now()->timestamp;

        $this->actingAs($user)
            ->withSession([
                'authenticated_at' => $now,
                'last_activity_at' => $now,
            ])
            ->get('/projects')
            ->assertOk();

        $this->assertAuthenticated();
    }

    public function test_session_expires_after_inactivity_timeout(): void
    {
        $this->seed(AdminUserSeeder::class);

        $user = User::where('name', 'admin')->firstOrFail();
        $idleSince = now()->subMinutes(21)->timestamp;

        $response = $this->actingAs($user)
            ->withSession([
                'authenticated_at' => $idleSince,
                'last_activity_at' => $idleSince,
            ])
            ->get('/projects');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'login' => 'Your session expired due to inactivity. Please sign in again.',
        ]);
        $this->assertGuest();
    }

    public function test_session_expires_after_absolute_lifetime(): void
    {
        $this->seed(AdminUserSeeder::class);

        $user = User::where('name', 'admin')->firstOrFail();
        $signedInAt = now()->subMinutes(481)->timestamp;

        $response = $this->actingAs($user)
            ->withSession([
                'authenticated_at' => $signedInAt,
                'last_activity_at' => now()->timestamp,
            ])
            ->get('/projects');

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'login' => 'Your session reached its maximum lifetime. Please sign in again.',
        ]);
        $this->assertGuest();
    }

    public function test_authenticated_admin_can_access_projects_page(): void
    {
        $this->seed(AdminUserSeeder::class);

        $user = User::where('name', 'admin')->firstOrFail();

        $this->actingAs($user)
            ->get('/projects')
            ->assertOk()
            ->assertSee('admin')
            ->assertSee('Administrator');
    }

    public function test_procurement_officer_can_login(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->post('/login', [
            'login' => 'user1',
            'password' => 'user1',
        ]);

        $response->assertRedirect('/projects');
        $this->assertAuthenticated();
    }

    public function test_viewer_can_login(): void
    {
        $this->seed(AdminUserSeeder::class);

        $response = $this->post('/login', [
            'login' => 'user2',
            'password' => 'user2',
        ]);

        $response->assertRedirect('/projects');
        $this->assertAuthenticated();
    }

    public function test_settings_link_is_visible_to_administrator_only(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('name', 'admin')->firstOrFail();
        $officer = User::where('name', 'user1')->firstOrFail();
        $viewer = User::where('name', 'user2')->firstOrFail();

        $this->actingAs($admin)->get('/projects')->assertSee(route('settings'));
        $this->actingAs($officer)->get('/projects')->assertDontSee(route('settings'));
        $this->actingAs($viewer)->get('/projects')->assertDontSee(route('settings'));
    }

    public function test_only_administrator_can_access_settings(): void
    {
        $this->seed(AdminUserSeeder::class);

        $admin = User::where('name', 'admin')->firstOrFail();
        $officer = User::where('name', 'user1')->firstOrFail();
        $viewer = User::where('name', 'user2')->firstOrFail();

        $this->actingAs($admin)->get('/settings')->assertOk();
        $this->actingAs($officer)->get('/settings')->assertForbidden();
        $this->actingAs($viewer)->get('/settings')->assertForbidden();
    }
}
