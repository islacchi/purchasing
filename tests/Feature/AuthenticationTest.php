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
}
