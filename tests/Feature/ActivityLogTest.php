<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Preplist;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    public function test_preplist_create_writes_activity_log_row(): void
    {
        $user = $this->admin();

        $entity = ProcuringEntity::create([
            'name' => 'DOH Region VII',
            'type' => 'Regional Office',
        ]);

        $project = Project::create([
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Purchase of pharma supplies',
            'reference_no' => 'RF-2026-0042',
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 482000.00,
            'date_awarded' => '2026-07-03',
            'delivery_days' => 30,
            'notes' => 'Quarterly procurement of pharmaceutical supplies.',
        ]);

        $category = ItemCategory::create(['name' => 'drugs and medicines']);

        $item = Item::create([
            'category_id' => $category->id,
            'name' => 'Mefenamic Capsule - 500mg',
            'unit' => 'Box',
            'default_min_shelf_life_months' => 0,
        ]);

        $projectItem = ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => 50,
            'unit_cost' => 320.00,
            'awarded_unit_price' => 305.00,
            'fulfillment_status' => 'pending',
        ]);

        $this->actingAs($user)->post(route('preplist.store'), [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ])->assertSessionHasNoErrors();

        $preplist = Preplist::firstOrFail();

        $this->assertDatabaseCount('activity_logs', 1);

        $log = ActivityLog::firstOrFail();

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('admin', $log->user_name);
        $this->assertSame('Administrator', $log->role_name);
        $this->assertSame('Created', $log->action);
        $this->assertSame('Preplist', $log->module);
        $this->assertSame('July Region VII batch', $log->record_label);
        $this->assertSame(Preplist::class, $log->subject_type);
        $this->assertSame($preplist->id, $log->subject_id);
        $this->assertNotNull($log->created_at);
    }

    public function test_supplier_create_writes_activity_log_row(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('entities.store', ['type' => 'supplier']), [
            'name' => 'Triple Tact',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('activity_logs', 1);

        $log = ActivityLog::firstOrFail();

        $this->assertSame('Created', $log->action);
        $this->assertSame('Entities', $log->module);
        $this->assertSame('Triple Tact', $log->record_label);
        $this->assertSame(Supplier::class, $log->subject_type);
        $this->assertSame($user->id, $log->user_id);
    }

    public function test_failed_preplist_create_writes_no_activity_log_row(): void
    {
        $user = $this->admin();

        $this->actingAs($user)->post(route('preplist.store'), [
            'name' => 'Missing sources',
        ])->assertSessionHasErrors('sources');

        $this->assertDatabaseCount('activity_logs', 0);
    }
}
