<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Preplist;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\PurchaseOrder;
use App\Models\Quotation;
use App\Models\Supplier;
use App\Models\User;
use App\Support\ActivityLogger;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function entity(string $name = 'DOH Region VII'): ProcuringEntity
    {
        return ProcuringEntity::create([
            'name' => $name,
            'type' => 'Regional Office',
        ]);
    }

    private function project(User $user, ProcuringEntity $entity, array $overrides = []): Project
    {
        $attributes = array_merge([
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Pagination fixture',
            'reference_no' => 'RF-PAGE-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT),
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 1000.00,
            'date_awarded' => '2026-07-03',
            'delivery_days' => 30,
            'delivery_due_date' => '2026-08-02',
        ], $overrides);

        return Project::create($attributes);
    }

    private function item(): Item
    {
        $category = ItemCategory::firstOrCreate(['name' => 'drugs and medicines']);

        return Item::create([
            'category_id' => $category->id,
            'name' => 'Mefenamic Capsule - 500mg',
            'unit' => 'Box',
            'default_min_shelf_life_months' => 0,
        ]);
    }

    private function projectItem(Project $project, Item $item): ProjectItem
    {
        return ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => 50,
            'unit_cost' => 320.00,
            'fulfillment_status' => 'pending',
        ]);
    }

    private function compile(User $user, array $overrides = []): Preplist
    {
        $item = $this->item();
        $project = $this->project($user, $this->entity());
        $projectItem = $this->projectItem($project, $item);

        $this->actingAs($user)
            ->post(route('preplist.store'), array_merge([
                'name' => 'Pagination fixture batch',
                'sources' => [$projectItem->id],
            ], $overrides))
            ->assertSessionHasNoErrors();

        return Preplist::query()->latest('id')->firstOrFail();
    }

    public function test_projects_per_page_is_whitelisted_and_persisted_in_page_links(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        for ($i = 1; $i <= 12; $i++) {
            $this->project($user, $entity, ['title' => sprintf('Paginated fixture %02d', $i)]);
        }

        // Invalid per_page falls back to the default of 10.
        $fallback = $this->actingAs($user)->get(route('projects', ['per_page' => 'nonsense']))->assertOk();
        $this->assertSame(10, $fallback->viewData('projects')->count());

        // Non-whitelisted 7 also falls back to 10.
        $fallbackSeven = $this->actingAs($user)->get(route('projects', ['per_page' => 7]))->assertOk();
        $this->assertSame(10, $fallbackSeven->viewData('projects')->count());

        // 25 shows everything; no pagination nav renders.
        $all = $this->actingAs($user)->get(route('projects', ['per_page' => 25]))->assertOk();
        $this->assertSame(12, $all->viewData('projects')->count());
        $all->assertDontSee('aria-label="Pagination Navigation"', false);

        // Persisted into the page links.
        $five = $this->actingAs($user)->get(route('projects', ['per_page' => 5]))->assertOk();
        $this->assertStringContainsString('per_page=5', $five->viewData('projects')->nextPageUrl());
    }

    public function test_preplist_per_page_is_whitelisted(): void
    {
        $user = $this->admin();

        for ($i = 1; $i <= 6; $i++) {
            $this->compile($user, ['name' => sprintf('Pagination fixture %02d', $i)]);
        }

        $fallback = $this->actingAs($user)->get(route('preplist', ['per_page' => '999']))->assertOk();
        $this->assertSame(6, $fallback->viewData('preplists')->count());

        $five = $this->actingAs($user)->get(route('preplist', ['per_page' => 5]))->assertOk();
        $this->assertSame(5, $five->viewData('preplists')->count());

        $all = $this->actingAs($user)->get(route('preplist', ['per_page' => 25]))->assertOk();
        $this->assertSame(6, $all->viewData('preplists')->count());
        $all->assertDontSee('aria-label="Pagination Navigation"', false);
    }

    public function test_quotation_per_page_counts_groups(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Pagination Supplies']);

        for ($i = 1; $i <= 6; $i++) {
            $preplist = $this->compile($user, ['name' => sprintf('Pagination fixture %02d', $i)]);

            Quotation::create([
                'preplist_id' => $preplist->id,
                'supplier_id' => $supplier->id,
                'created_by' => $user->id,
                'rfq_number' => sprintf('RFQ-2026-%03d', $i),
                'generated_at' => now(),
            ]);
        }

        $default = $this->actingAs($user)->get(route('quotation'))->assertOk();
        $this->assertSame(5, $default->viewData('groups')->count());

        $all = $this->actingAs($user)->get(route('quotation', ['per_page' => 100]))->assertOk();
        $this->assertSame(6, $all->viewData('groups')->count());
        $all->assertDontSee('aria-label="Pagination Navigation"', false);
    }

    public function test_purchase_order_per_page_is_whitelisted(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Pagination Supplies']);
        $preplist = $this->compile($user, ['name' => 'Pagination PO batch']);

        $quotation = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-2026-PGPTURETEST',
            'generated_at' => now(),
        ]);

        for ($i = 1; $i <= 12; $i++) {
            PurchaseOrder::create([
                'quotation_id' => $quotation->id,
                'supplier_id' => $supplier->id,
                'created_by' => $user->id,
                'po_number' => sprintf('PO-2026-%04d', 100 + $i),
                'status' => PurchaseOrder::STATUS_DRAFT,
            ]);
        }

        $default = $this->actingAs($user)->get(route('purchase-order'))->assertOk();
        $this->assertSame(10, $default->viewData('purchaseOrders')->count());
        $default->assertSee('page=2', false);

        $fallback = $this->actingAs($user)->get(route('purchase-order', ['per_page' => 'nonsense']))->assertOk();
        $this->assertSame(10, $fallback->viewData('purchaseOrders')->count());

        $all = $this->actingAs($user)->get(route('purchase-order', ['per_page' => 25]))->assertOk();
        $this->assertSame(12, $all->viewData('purchaseOrders')->count());
        $all->assertDontSee('aria-label="Pagination Navigation"', false);
    }

    public function test_settings_logs_per_page_is_whitelisted(): void
    {
        $user = $this->admin();
        $this->actingAs($user);

        for ($i = 1; $i <= 7; $i++) {
            ActivityLogger::log('Created', 'Projects', sprintf('Pagination fixture log %02d', $i));
        }

        $default = $this->actingAs($user)->get(route('settings.logs'))->assertOk();
        $this->assertSame(7, $default->viewData('logs')->count());

        $five = $this->actingAs($user)->get(route('settings.logs', ['per_page' => 5]))->assertOk();
        $this->assertSame(5, $five->viewData('logs')->count());

        $fallback = $this->actingAs($user)->get(route('settings.logs', ['per_page' => 'nonsense']))->assertOk();
        $this->assertSame(7, $fallback->viewData('logs')->count());
    }
}
