<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPrivilegeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function roleId(string $name): int
    {
        return Role::where('name', $name)->value('id');
    }

    public function test_administrator_sees_real_users_and_roles(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/settings/privileges')
            ->assertOk()
            ->assertSee('admin@example.com')
            ->assertSee('user1@example.com')
            ->assertSee('user2@example.com')
            ->assertSee('Procurement Officer')
            ->assertSee('Viewer')
            ->assertSee('Available Roles')
            ->assertDontSee('j.santos@company.com')
            ->assertDontSee('dummy data');
    }

    public function test_privilege_list_can_be_searched(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/settings/privileges?search=user1')
            ->assertOk()
            ->assertSee('user1@example.com')
            ->assertDontSee('user2@example.com');
    }

    public function test_live_privilege_search_returns_matching_rows_only(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->get(route('settings.privileges.search', ['search' => 'user1']));

        $response->assertOk()
            ->assertSee('user1@example.com')
            ->assertSee('Procurement Officer')
            ->assertDontSee('user2@example.com')
            ->assertDontSee('Viewer');
    }

    public function test_live_privilege_search_returns_empty_state_when_nothing_matches(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('settings.privileges.search', ['search' => 'missing-user']))
            ->assertOk()
            ->assertSee('No users match your search.');
    }

    public function test_administrator_can_open_the_create_and_edit_forms(): void
    {
        $admin = $this->admin();
        $user = User::where('name', 'user2')->firstOrFail();

        $this->actingAs($admin)
            ->get('/settings/privileges/create')
            ->assertOk()
            ->assertSee('ADD USER')
            ->assertSee('Viewer');

        $this->actingAs($admin)
            ->get(route('settings.privileges.edit', $user->id))
            ->assertOk()
            ->assertSee('EDIT USER')
            ->assertSee('user2@example.com')
            ->assertSee('Viewer');
    }

    public function test_administrator_can_create_a_user(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/settings/privileges', [
            'name' => 'newuser',
            'email' => 'newuser@example.com',
            'role_id' => $this->roleId('Viewer'),
            'status' => 'Active',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('settings.privileges'));
        $response->assertSessionHas('status');

        $user = User::where('name', 'newuser')->firstOrFail();

        $this->assertSame('newuser@example.com', $user->email);
        $this->assertSame('Viewer', $user->role->name);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('secret123', $user->password));
    }

    public function test_create_user_validates_unique_email_and_password(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post('/settings/privileges', [
            'name' => 'anotheruser',
            'email' => 'user1@example.com',
            'role_id' => $this->roleId('Viewer'),
            'status' => 'Active',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseMissing('users', ['name' => 'anotheruser']);
    }

    public function test_administrator_can_update_a_user_without_changing_the_password(): void
    {
        $admin = $this->admin();
        $user = User::where('name', 'user2')->firstOrFail();
        $originalPassword = $user->password;

        $response = $this->actingAs($admin)->put(route('settings.privileges.update', $user->id), [
            'name' => 'user2-updated',
            'email' => 'user2-updated@example.com',
            'role_id' => $this->roleId('Procurement Officer'),
            'status' => 'Inactive',
        ]);

        $response->assertRedirect(route('settings.privileges'));
        $response->assertSessionHas('status');

        $user->refresh();

        $this->assertSame('user2-updated', $user->name);
        $this->assertSame('user2-updated@example.com', $user->email);
        $this->assertSame('Procurement Officer', $user->role->name);
        $this->assertFalse($user->is_active);
        $this->assertSame($originalPassword, $user->password);
    }

    public function test_administrator_can_update_a_user_password(): void
    {
        $admin = $this->admin();
        $user = User::where('name', 'user1')->firstOrFail();

        $this->actingAs($admin)->put(route('settings.privileges.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'status' => 'Active',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertRedirect(route('settings.privileges'));

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_administrator_can_toggle_a_user_status(): void
    {
        $admin = $this->admin();
        $user = User::where('name', 'user2')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('settings.privileges.toggle-status', $user->id))
            ->assertRedirect(route('settings.privileges'));

        $this->assertFalse($user->fresh()->is_active);

        $this->actingAs($admin)
            ->post(route('settings.privileges.toggle-status', $user->id))
            ->assertRedirect(route('settings.privileges'));

        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_administrator_can_delete_a_user(): void
    {
        $admin = $this->admin();
        $user = User::where('name', 'user2')->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('settings.privileges.destroy', $user->id))
            ->assertRedirect(route('settings.privileges'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_administrator_cannot_deactivate_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('settings.privileges.toggle-status', $admin->id))
            ->assertRedirect(route('settings.privileges'))
            ->assertSessionHas('error');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_administrator_cannot_demote_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('settings.privileges.update', $admin->id), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role_id' => $this->roleId('Viewer'),
            'status' => 'Active',
        ])->assertSessionHas('error');

        $this->assertSame('Administrator', $admin->fresh()->role->name);
    }

    public function test_administrator_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete(route('settings.privileges.destroy', $admin->id))
            ->assertRedirect(route('settings.privileges'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_non_administrators_cannot_manage_users(): void
    {
        $this->admin();
        $officer = User::where('name', 'user1')->firstOrFail();

        $this->actingAs($officer)->get('/settings/privileges')->assertForbidden();
        $this->actingAs($officer)->get(route('settings.privileges.search'))->assertForbidden();
        $this->actingAs($officer)->post('/settings/privileges', [
            'name' => 'intruder',
            'email' => 'intruder@example.com',
            'role_id' => $this->roleId('Viewer'),
            'status' => 'Active',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['name' => 'intruder']);
    }
}
