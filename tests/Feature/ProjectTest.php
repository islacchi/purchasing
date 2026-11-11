<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function entity(array $overrides = []): ProcuringEntity
    {
        return ProcuringEntity::create(array_merge([
            'name' => 'DOH Region VII',
            'type' => 'Regional Office',
        ], $overrides));
    }

    private function project(User $user, ProcuringEntity $entity, array $overrides = []): Project
    {
        return Project::create(array_merge([
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Purchase of pharma supplies',
            'reference_no' => 'RF-2026-0042',
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 482000.00,
            'date_awarded' => '2026-07-03',
            'delivery_days' => 30,
            'notes' => 'Quarterly procurement of pharmaceutical supplies.',
        ], $overrides));
    }

    private function addItem(Project $project): ProjectItem
    {
        $category = ItemCategory::firstOrCreate(['name' => 'drugs and medicines']);
        $item = Item::create([
            'category_id' => $category->id,
            'name' => 'Mefenamic Capsule - 500mg',
            'unit' => 'Box',
        ]);

        return ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => 50,
            'unit_cost' => 320.00,
            'awarded_unit_price' => 305.00,
            'fulfillment_status' => 'pending',
        ]);
    }

    public function test_projects_module_lists_real_database_records(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $this->actingAs($user)
            ->get('/projects')
            ->assertOk()
            ->assertSee($project->title)
            ->assertSee($project->reference_no)
            ->assertSee($entity->name);
    }

    public function test_projects_can_be_searched_by_title_or_entity(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $otherEntity = $this->entity(['name' => 'PGH Manila', 'type' => 'Government Hospital']);

        $this->project($user, $entity, ['title' => 'Regional medicine purchase']);
        $this->project($user, $otherEntity, ['title' => 'Hospital equipment purchase', 'reference_no' => 'RF-2026-0099']);

        $this->actingAs($user)
            ->get('/projects?q=medicine')
            ->assertOk()
            ->assertSee('Regional medicine purchase')
            ->assertDontSee('Hospital equipment purchase');
    }

    public function test_project_create_form_has_procuring_entity_dropdown(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $this->actingAs($user)
            ->get(route('projects.create'))
            ->assertOk()
            ->assertSee('name="procuring_entity_id"', false)
            ->assertSee($entity->name)
            ->assertSee('value="'.$entity->id.'"', false);
    }

    public function test_project_can_be_created_with_database_record_and_items(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $response = $this->actingAs($user)
            ->post(route('projects.store'), [
                'procuring_entity_id' => $entity->id,
                'title' => 'Purchase of pharma supplies',
                'reference_no' => 'RF-2026-0042',
                'mode_of_procurement' => 'small_value',
                'amount_awarded' => 482000.00,
                'date_awarded' => '2026-07-03',
                'delivery_days' => 30,
                'notes' => 'Quarterly procurement of pharmaceutical supplies.',
                'items' => [
                    [
                        'description' => 'Mefenamic Capsule - 500mg',
                        'category' => 'drugs and medicines',
                        'unit' => 'Box',
                        'quantity' => 50,
                        'unit_cost' => 320.00,
                        'quoted_price' => 305.00,
                    ],
                ],
            ]);

        $project = Project::firstOrFail();

        $response->assertRedirect(route('projects.show', ['project' => $project->id]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Purchase of pharma supplies',
            'delivery_days' => 30,
        ]);

        $this->assertSame('2026-08-02', $project->fresh()->delivery_due_date->toDateString());

        $this->assertDatabaseHas('items', [
            'name' => 'Mefenamic Capsule - 500mg',
            'unit' => 'Box',
        ]);

        $this->assertDatabaseHas('project_items', [
            'project_id' => $project->id,
            'quantity' => 50,
            'unit_cost' => 320.00,
            'awarded_unit_price' => 305.00,
        ]);
    }

    public function test_project_edit_form_shows_original_data(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $this->addItem($project);

        $this->actingAs($user)
            ->get(route('projects.edit', ['project' => $project->id]))
            ->assertOk()
            ->assertSee('value="'.$project->title.'"', false)
            ->assertSee('value="'.$project->reference_no.'"', false)
            ->assertSee('value="'.$project->amount_awarded.'"', false)
            ->assertSee('value="'.$entity->id.'" selected', false)
            ->assertSee($entity->name)
            ->assertSee('value="Mefenamic Capsule - 500mg"', false);
    }

    public function test_project_can_be_updated(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $otherEntity = $this->entity(['name' => 'PGH Manila', 'type' => 'Government Hospital']);
        $project = $this->project($user, $entity);

        $response = $this->actingAs($user)
            ->put(route('projects.update', ['project' => $project->id]), [
                'procuring_entity_id' => $otherEntity->id,
                'title' => 'Purchase of pharma supplies updated',
                'reference_no' => 'RF-2026-0100',
                'mode_of_procurement' => 'competitive_bidding',
                'amount_awarded' => 500000.00,
                'date_awarded' => '2026-07-10',
                'delivery_days' => 15,
                'notes' => 'Updated project notes.',
                'items' => [],
            ]);

        $response->assertRedirect(route('projects.show', ['project' => $project->id]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'procuring_entity_id' => $otherEntity->id,
            'title' => 'Purchase of pharma supplies updated',
            'reference_no' => 'RF-2026-0100',
            'mode_of_procurement' => 'competitive_bidding',
            'delivery_days' => 15,
        ]);

        $this->assertSame('2026-07-25', $project->fresh()->delivery_due_date->toDateString());
    }

    public function test_project_show_displays_real_database_record(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $this->addItem($project);

        $this->actingAs($user)
            ->get(route('projects.show', ['project' => $project->id]))
            ->assertOk()
            ->assertSee($project->title)
            ->assertSee($project->reference_no)
            ->assertSee($entity->name)
            ->assertSee('Mefenamic Capsule - 500mg');
    }

    public function test_project_can_be_deleted(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $this->actingAs($user)
            ->delete(route('projects.destroy', ['project' => $project->id]))
            ->assertRedirect(route('projects'));

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_project_cannot_be_deleted_when_a_prep_list_sources_it(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $projectItem = $this->addItem($project);

        // The prep list compiles the project's item, so the project is in use.
        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'July Region VII batch',
                'sources' => [$projectItem->id],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->delete(route('projects.destroy', ['project' => $project->id]))
            ->assertSessionHasErrors('project');

        $this->assertNull(Project::withTrashed()->find($project->id)->deleted_at);

        $this->actingAs($user)
            ->get(route('projects.show', ['project' => $project->id]))
            ->assertOk()
            ->assertSee('cannot be deleted')
            ->assertDontSee('id="projectDeleteBtn"', false);
    }

    public function test_project_requires_an_existing_procuring_entity(): void
    {
        $user = $this->admin();
        $this->entity();

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'procuring_entity_id' => 99999,
                'title' => 'Purchase of pharma supplies',
                'reference_no' => 'RF-2026-0042',
                'mode_of_procurement' => 'small_value',
            ])
            ->assertSessionHasErrors('procuring_entity_id');

        $this->assertDatabaseCount('projects', 0);
    }
}
