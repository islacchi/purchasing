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
        $response->assertSessionHasErrors('login');
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
