<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InStockTest extends TestCase
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

    private function item(array $overrides = []): Item
    {
        $category = ItemCategory::firstOrCreate([
            'name' => $overrides['category'] ?? 'drugs and medicines',
        ]);

        return Item::create([
            'category_id' => $category->id,
            'name' => $overrides['name'] ?? 'Mefenamic Capsule - 500mg',
            'unit' => $overrides['unit'] ?? 'Box',
            'default_min_shelf_life_months' => $overrides['default_min_shelf_life_months'] ?? 0,
        ]);
    }

    private function projectItem(Project $project, Item $item, array $overrides = []): ProjectItem
    {
        return ProjectItem::create(array_merge([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => 50,
            'in_stock' => 0,
            'unit_cost' => 320.00,
            'awarded_unit_price' => 305.00,
            'fulfillment_status' => 'pending',
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
            'delivery_due_date' => Carbon::parse('2026-07-03')->addDays(30)->toDateString(),
            'notes' => 'Quarterly procurement of pharmaceutical supplies.',
        ], $overrides));
    }

    public function test_project_form_saves_in_stock_and_show_page_lists_remaining(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $this->actingAs($user)->post(route('projects.store'), [
            'procuring_entity_id' => $entity->id,
            'title' => 'Purchase of pharma supplies',
            'reference_no' => 'RF-2026-0042',
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 482000,
            'date_awarded' => '2026-07-03',
            'delivery_days' => 30,
            'items' => [
                [
                    'description' => 'Mefenamic Capsule - 500mg',
                    'category' => 'drugs and medicines',
                    'unit' => 'Box',
                    'quantity' => 50,
                    'in_stock' => 20,
                    'unit_cost' => 320,
                    'quoted_price' => 305,
                ],
                [
                    'description' => 'Paracetamol 500mg',
                    'category' => 'drugs and medicines',
                    'unit' => 'Box',
                    'quantity' => 10,
                    'in_stock' => 10,
                    'unit_cost' => 150,
                    'quoted_price' => 140,
                ],
            ],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('project_items', 2);

        $projectItem = ProjectItem::whereHas('item', fn ($query) => $query->where('name', 'Mefenamic Capsule - 500mg'))->firstOrFail();
        $coveredItem = ProjectItem::whereHas('item', fn ($query) => $query->where('name', 'Paracetamol 500mg'))->firstOrFail();

        $this->assertSame(20, $projectItem->in_stock);
        $this->assertSame(30, $projectItem->remainingQuantity());
        $this->assertTrue($coveredItem->isFulfilledByStock());
        $this->assertTrue($coveredItem->isFulfilled());
        $this->assertFalse($projectItem->isFulfilled());

        $response = $this->actingAs($user)->get(route('projects.show', ['project' => $projectItem->project_id]));
        $response->assertOk();
        $response->assertSee('To procure');
        $response->assertSeeHtml('>30<');
        $response->assertSeeHtml('>0<');
        $response->assertSee('Fulfilled');
    }

    public function test_stock_covered_items_count_as_fulfilled_in_project_progress(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $coveredItem = $this->item(['name' => 'CoveredItemABC']);
        $openItem = $this->item(['name' => 'OpenItemXYZ']);

        $this->projectItem($project, $coveredItem, ['quantity' => 20, 'in_stock' => 20]);
        $this->projectItem($project, $openItem, ['quantity' => 50, 'in_stock' => 10]);

        $project = $project->fresh('items');

        $this->assertSame(1, $project->fulfilledItemCount());
        $this->assertSame(2, $project->totalItemCount());
        $this->assertEquals(50.0, $project->progressPercentage());
        $this->assertSame('In progress', $project->statusLabel());

        // A fully covered project counts as complete.
        $project->items->each(fn (ProjectItem $item) => $item->update([
            'in_stock' => $item->quantity,
        ]));
        $project = $project->fresh('items');

        $this->assertSame(2, $project->fulfilledItemCount());
        $this->assertEquals(100.0, $project->progressPercentage());
        $this->assertSame('Completed', $project->statusLabel());
    }

    public function test_stock_covered_item_is_not_listed_on_the_po_prep_page(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $coveredItem = $this->item(['name' => 'AlphaItemCovered']);
        $openItem = $this->item(['name' => 'BetaItemOpen', 'category' => 'supplies and equipments']);

        $coveredSource = $this->projectItem($project, $coveredItem, ['quantity' => 30, 'in_stock' => 30]);
        $openSource = $this->projectItem($project, $openItem, ['quantity' => 20, 'in_stock' => 0]);

        $supplier = Supplier::firstOrCreate(['name' => 'Triple Tact']);

        $this->actingAs($user)->post(route('preplist.store'), [
            'name' => 'July Region VII batch',
            'sources' => [$coveredSource->id, $openSource->id],
            'shelf_life' => [$coveredSource->id => 9, $openSource->id => 3],
        ])->assertSessionHasNoErrors();

        $preplist = Preplist::query()->firstOrFail();

        $quotation = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'rfq_number' => sprintf('RFQ-%d-%04d', (int) now()->year, 9999),
            'generated_at' => now(),
        ]);

        $preplistItems = PreplistItem::query()->get()->keyBy('item_id');

        foreach ([$coveredItem, $openItem] as $index => $item) {
            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'preplist_item_id' => $preplistItems[$item->id]->id,
                'min_remaining_months' => 6,
                'unit_price' => 305.00 - ($index * 55),
                'quoted_at' => now(),
            ]);
        }

        $response = $this->actingAs($user)->get(route('purchase-order.create', ['rfq' => $quotation->id]));
        $response->assertOk();
        $response->assertSee('BetaItemOpen');
        $response->assertDontSee('AlphaItemCovered');

        // Even if the covered line is forced into the POST body, it is skipped.
        $payload = [
            'quotation_id' => $quotation->id,
            'expected_delivery' => '2026-10-15',
            'payment_terms' => '30 days from delivery',
            'deliver_to' => 'DOH Region VII Warehouse, Cebu City',
            'notes' => 'Deliver in a single shipment.',
            'items' => [],
        ];

        foreach ($quotation->items as $quotationItem) {
            $payload['items'][$quotationItem->id] = [
                'quotation_item_id' => (string) $quotationItem->id,
                'included' => '1',
                'qty' => '5',
                'min_remaining_months' => '6',
            ];
        }

        $this->actingAs($user)->post(route('purchase-order.store'), $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('purchase_order_items', 1);
        $this->assertDatabaseHas('purchase_order_items', [
            'item_id' => $openItem->id,
        ]);
        $this->assertDatabaseMissing('purchase_order_items', [
            'item_id' => $coveredItem->id,
        ]);

        // A payload that selects ONLY the covered line is rejected.
        $coveredOnly = $payload;
        foreach ($coveredOnly['items'] as $quotationItemId => $row) {
            $coveredQuotationItem = $quotation->items->firstWhere('id', (int) $quotationItemId);
            $row['included'] = $coveredQuotationItem?->preplistItem?->item_id === $coveredItem->id ? '1' : '0';
            $coveredOnly['items'][$quotationItemId] = $row;
        }

        $this->actingAs($user)->post(route('purchase-order.store'), $coveredOnly)
            ->assertSessionHasErrors('items');
    }
}
