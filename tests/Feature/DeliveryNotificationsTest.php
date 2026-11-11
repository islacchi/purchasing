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

class DeliveryNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function entity(): ProcuringEntity
    {
        return ProcuringEntity::create([
            'name' => 'DOH Region VII',
            'type' => 'Regional Office',
        ]);
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
            'date_awarded' => '2026-11-01',
            'delivery_days' => 30,
            'delivery_due_date' => now()->addDays(5)->toDateString(),
        ], $overrides));
    }

    private function projectItem(Project $project, string $fulfillmentStatus = 'pending'): ProjectItem
    {
        $category = ItemCategory::firstOrCreate(['name' => 'drugs and medicines']);
        $item = Item::firstOrCreate(
            ['name' => 'Mefenamic Capsule - 500mg'],
            [
                'category_id' => $category->id,
                'name' => 'Mefenamic Capsule - 500mg',
                'unit' => 'Box',
            ],
        );

        return ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => 50,
            'unit_cost' => 320.00,
            'awarded_unit_price' => 305.00,
            'fulfillment_status' => $fulfillmentStatus,
        ]);
    }

    public function test_bell_lists_projects_due_within_two_days_in_red(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity, [
            'title' => 'Urgent pharma restock',
            'delivery_due_date' => now()->addDays(2)->toDateString(),
        ]);
        $this->projectItem($project);

        $this->actingAs($user)
            ->get(route('projects'))
            ->assertOk()
            ->assertSee('Delivery deadlines approaching')
            ->assertSee('Urgent pharma restock')
            ->assertSee('Delivery due in 2 days')
            ->assertSee('text-red-600');
    }

    public function test_bell_lists_projects_overdue_in_red(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity, [
            'title' => 'Late laboratory reagents',
            'delivery_due_date' => now()->subDays(4)->toDateString(),
        ]);
        $this->projectItem($project);

        $this->actingAs($user)
            ->get(route('projects'))
            ->assertOk()
            ->assertSee('Late laboratory reagents')
            ->assertSee('Delivery overdue by 4 days')
            ->assertSee('text-red-600');
    }

    public function test_bell_lists_projects_due_within_a_week_in_amber(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity, [
            'title' => 'Steady pharma batch',
            'delivery_due_date' => now()->addDays(6)->toDateString(),
        ]);
        $this->projectItem($project);

        $this->actingAs($user)
            ->get(route('projects'))
            ->assertOk()
            ->assertSee('Steady pharma batch')
            ->assertSee('Delivery due in 6 days')
            ->assertSee('text-amber-600')
            ->assertDontSee('text-red-600');
    }

    public function test_bell_ignores_completed_projects(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity, [
            'title' => 'Fully delivered order',
            'delivery_due_date' => now()->addDays(1)->toDateString(),
        ]);
        $this->projectItem($project, 'fulfilled');

        $this->actingAs($user)
            ->get(route('projects'))
            ->assertOk()
            ->assertDontSee('Delivery due in 1 day')
            ->assertSee('No delivery deadlines approaching');
    }

    public function test_bell_flags_never_started_projects_due_within_two_weeks(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity, [
            'title' => 'Blank slate pharma order',
            'delivery_due_date' => now()->addDays(12)->toDateString(),
        ]);
        $this->projectItem($project); // pending, nothing fulfilled yet

        $this->actingAs($user)
            ->get(route('projects'))
            ->assertOk()
            ->assertSee('Blank slate pharma order')
            ->assertSee('text-amber-600')
            ->assertDontSee('text-red-600');
    }

    public function test_bell_shows_all_caught_up_when_nothing_is_due(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('projects'))
            ->assertOk()
            ->assertSee('No delivery deadlines approaching');
    }
}
