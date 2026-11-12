<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RoleModuleAccess;
use App\Models\User;
use App\Support\ModuleAccess;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ModuleAccess::flush();
    }

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function officer(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'user1')->firstOrFail();
    }

    private function viewer(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'user2')->firstOrFail();
    }

    public function test_seeded_grants_give_viewer_view_only_and_officer_manage(): void
    {
        $this->seed(AdminUserSeeder::class);

        $this->assertDatabaseHas('role_module_access', [
            'role_id' => Role::where('name', 'Viewer')->value('id'),
            'module' => 'projects',
            'level' => 'view',
        ]);
        $this->assertDatabaseHas('role_module_access', [
            'role_id' => Role::where('name', 'Procurement Officer')->value('id'),
            'module' => 'projects',
            'level' => 'manage',
        ]);
    }

    public function test_viewer_can_read_modules_but_cannot_write(): void
    {
        $user = $this->viewer();

        $this->actingAs($user)->get(route('projects'))->assertOk();
        $this->actingAs($user)->get(route('preplist'))->assertOk();
        $this->actingAs($user)->get(route('quotation'))->assertOk();
        $this->actingAs($user)->get(route('priceindex'))->assertOk();
        $this->actingAs($user)->get(route('purchase-order'))->assertOk();
        $this->actingAs($user)->get(route('entities'))->assertOk();

        $this->actingAs($user)->post(route('projects.store'), [
            'title' => 'Viewer should not create',
            'reference_no' => 'RF-2026-TEST',
            'procuring_entity_id' => 1,
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 100,
        ])->assertForbidden();

        $this->actingAs($user)->post(route('entities.store', ['type' => 'supplier']), [
            'name' => 'Viewer Supplier',
        ])->assertForbidden();

        $this->actingAs($user)->post(route('preplist.store'), [
            'name' => 'Viewer list',
            'sources' => [1],
        ])->assertForbidden();
    }

    public function test_officer_can_write_but_cannot_open_admin_settings(): void
    {
        $user = $this->officer();

        $this->actingAs($user)->get(route('projects'))->assertOk();
        $this->actingAs($user)->post(route('entities.store', ['type' => 'supplier']), [
            'name' => 'Officer Supplier',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->get(route('settings.privileges'))->assertForbidden();
        $this->actingAs($user)->get(route('settings.logs'))->assertForbidden();
        $this->actingAs($user)->post(route('settings.privileges.access.update'), [
            'access' => [],
        ])->assertForbidden();
    }

    public function test_administrator_always_has_full_access_even_without_grants(): void
    {
        $user = $this->admin();

        // Wipe every grant row; the administrator must still pass.
        RoleModuleAccess::query()->delete();

        $this->actingAs($user)->get(route('projects'))->assertOk();
        $this->actingAs($user)->post(route('entities.store', ['type' => 'supplier']), [
            'name' => 'Admin Supplier',
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->get(route('settings.privileges'))->assertOk();
    }

    public function test_role_grant_of_none_blocks_even_read_access(): void
    {
        $this->seed(AdminUserSeeder::class);
        $user = User::where('name', 'user2')->firstOrFail();
        $viewerRole = Role::where('name', 'Viewer')->firstOrFail();

        RoleModuleAccess::query()
            ->where('role_id', $viewerRole->id)
            ->where('module', 'projects')
            ->update(['level' => 'none']);

        $this->actingAs($user)->get(route('projects'))->assertForbidden();
        $this->actingAs($user)->get(route('projects.create'))->assertForbidden();

        // Other modules keep their granted levels.
        $this->actingAs($user)->get(route('entities'))->assertOk();
    }

    public function test_matrix_update_persists_grants_and_logs_the_change(): void
    {
        $admin = $this->admin();
        $officerRole = Role::where('name', 'Procurement Officer')->firstOrFail();

        $access = [];
        foreach (array_keys(ModuleAccess::MODULES) as $module) {
            $access[$officerRole->id][$module] = 'none';
        }
        $access[$officerRole->id]['projects'] = 'view';   // officer can read but not write
        $access[$officerRole->id]['quotation'] = 'manage'; // stays manage

        $this->actingAs($admin)
            ->post(route('settings.privileges.access.update'), ['access' => $access])
            ->assertSessionHas('status', 'Role access updated.');

        $this->assertDatabaseHas('role_module_access', [
            'role_id' => $officerRole->id,
            'module' => 'projects',
            'level' => 'view',
        ]);
        $this->assertDatabaseHas('role_module_access', [
            'role_id' => $officerRole->id,
            'module' => 'quotation',
            'level' => 'manage',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Edited',
            'module' => 'Users',
            'record_label' => 'Role module access matrix',
        ]);

        // Changes apply immediately.
        $officer = User::where('name', 'user1')->firstOrFail();
        $this->actingAs($officer)->get(route('projects'))->assertOk();
        $this->actingAs($officer)->post(route('projects.store'), [
            'title' => 'officer should no longer write',
            'reference_no' => 'RF-2026-X',
            'procuring_entity_id' => 1,
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 100,
        ])->assertForbidden();
        $this->actingAs($officer)->get(route('preplist'))->assertForbidden();
    }

    public function test_administrator_cannot_be_demoted_by_the_matrix(): void
    {
        $admin = $this->admin();
        $adminRole = Role::where('name', 'Administrator')->firstOrFail();

        $access = [];
        foreach (array_keys(ModuleAccess::MODULES) as $module) {
            $access[$adminRole->id][$module] = 'none';
        }

        $this->actingAs($admin)
            ->post(route('settings.privileges.access.update'), ['access' => $access])
            ->assertSessionHas('status', 'Role access updated.');

        // Administrator rows were ignored by the matrix update.
        $this->assertDatabaseCount('role_module_access', 12); // only officer + viewer seeded
        $this->actingAs($admin)->get(route('projects'))->assertOk();
    }

    public function test_restricted_module_renders_the_friendly_403_page(): void
    {
        $this->seed(AdminUserSeeder::class);
        $user = User::where('name', 'user2')->firstOrFail();
        $viewerRole = Role::where('name', 'Viewer')->firstOrFail();

        RoleModuleAccess::query()
            ->where('role_id', $viewerRole->id)
            ->where('module', 'projects')
            ->update(['level' => 'none']);

        $response = $this->actingAs($user)->get(route('projects'));

        $response->assertForbidden();
        $response->assertSee('Access restricted');
        $response->assertSee('Your role does not have access to that module.');
        $response->assertDontSee('Pages available to you');
    }

    public function test_sidebar_hides_modules_the_role_cannot_view(): void
    {
        $this->seed(AdminUserSeeder::class);
        $user = User::where('name', 'user2')->firstOrFail();
        $viewerRole = Role::where('name', 'Viewer')->firstOrFail();

        RoleModuleAccess::query()
            ->where('role_id', $viewerRole->id)
            ->where('module', 'projects')
            ->update(['level' => 'none']);

        // On a page the viewer may open, the hidden sidebar link is gone.
        $this->actingAs($user)
            ->get(route('preplist'))
            ->assertOk()
            ->assertDontSee('href="'.route('projects').'"', false);
    }
}
