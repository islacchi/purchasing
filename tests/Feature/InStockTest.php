<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\PreplistItemSource;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderItemAllocation;
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

    public function test_fulfilled_items_are_hidden_on_the_compile_page(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $coveredItem = $this->item(['name' => 'CoveredItemCompileHide']);
        $openItem = $this->item(['name' => 'OpenItemCompileShow', 'category' => 'supplies and equipments']);

        // Project A is fully covered — it must vanish from the compile page entirely.
        $projectA = $this->project($user, $entity, [
            'title' => 'All Covered Project',
            'reference_no' => 'RF-2026-A1',
        ]);
        $this->projectItem($projectA, $coveredItem, ['quantity' => 30, 'in_stock' => 30]);

        // Project B is mixed — only the open item stays selectable.
        $projectB = $this->project($user, $entity, [
            'title' => 'Mixed Project',
            'reference_no' => 'RF-2026-B2',
        ]);
        $this->projectItem($projectB, $coveredItem, ['quantity' => 30, 'in_stock' => 30]);
        $this->projectItem($projectB, $openItem, ['quantity' => 15, 'in_stock' => 0]);

        $this->actingAs($user)
            ->get(route('preplist.create'))
            ->assertOk()
            ->assertSee('Mixed Project')
            ->assertSee('OpenItemCompileShow')
            ->assertDontSee('CoveredItemCompileHide')
            ->assertDontSee('All Covered Project');
    }

    public function test_fulfilled_source_is_rejected_on_compile(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $coveredItem = $this->item(['name' => 'CoveredItemRejectMe']);
        $coveredSource = $this->projectItem($project, $coveredItem, ['quantity' => 30, 'in_stock' => 30]);

        $this->actingAs($user)->post(route('preplist.store'), [
            'name' => 'Rejected compile',
            'sources' => [$coveredSource->id],
        ])->assertSessionHasErrors('sources');

        $this->assertDatabaseCount('preplists', 0);
        $this->assertDatabaseCount('preplist_items', 0);
    }

    public function test_item_fulfilled_after_being_added_stays_visible_on_edit_and_is_not_dropped(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $sourceItem = $this->item(['name' => 'OpenItemThenCovered']);
        $source = $this->projectItem($project, $sourceItem, ['quantity' => 20, 'in_stock' => 0]);

        $this->actingAs($user)->post(route('preplist.store'), [
            'name' => 'Keep me honest',
            'sources' => [$source->id],
        ])->assertSessionHasNoErrors();

        $preplist = Preplist::query()->firstOrFail();

        // The line becomes fully covered AFTER it was added to the prep list.
        $source->update(['in_stock' => 20]);

        $this->actingAs($user)
            ->get(route('preplist.edit', ['preplist' => $preplist->id]))
            ->assertOk()
            ->assertSee('OpenItemThenCovered')
            ->assertSee('Fulfilled')
            ->assertSeeHtml('value="'.$source->id.'"');

        // Saving the edit without touching the line keeps it sourced.
        $this->actingAs($user)->put(route('preplist.update', ['preplist' => $preplist->id]), [
            'name' => 'Keep me honest',
            'sources' => [$source->id],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('preplists', 1);
        $this->assertSame(1, PreplistItemSource::query()->where('project_item_id', $source->id)->count());
    }

    public function test_po_uses_remaining_quantity_and_allocates_by_remaining(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $projectA = $this->project($user, $entity, [
            'title' => 'Alpha project',
            'reference_no' => 'RF-2026-A1',
        ]);
        $projectB = $this->project($user, $entity, [
            'title' => 'Beta project',
            'reference_no' => 'RF-2026-B2',
        ]);
        $item = $this->item(['name' => 'SharedItemQtyTest']);

        // Both partly/never stocked — both compilable, both needed.
        $sourceA = $this->projectItem($projectA, $item, ['quantity' => 40, 'in_stock' => 30]); // remaining 10
        $sourceB = $this->projectItem($projectB, $item, ['quantity' => 20, 'in_stock' => 0]);  // remaining 20

        $this->actingAs($user)->post(route('preplist.store'), [
            'name' => 'Remaining quantity check',
            'sources' => [$sourceA->id, $sourceB->id],
            'shelf_life' => [$sourceA->id => 6, $sourceB->id => 6],
        ])->assertSessionHasNoErrors();

        $preplist = Preplist::query()->firstOrFail();
        $supplier = Supplier::firstOrCreate(['name' => 'Triple Tact']);

        $quotation = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'rfq_number' => sprintf('RFQ-%d-%04d', (int) now()->year, 9998),
            'generated_at' => now(),
        ]);

        $preplistItem = PreplistItem::query()->firstOrFail();

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 6,
            'unit_price' => 305.00,
            'quoted_at' => now(),
        ]);

        // The prep page reflects the sum of what is still missing.
        $this->actingAs($user)
            ->get(route('purchase-order.create', ['rfq' => $quotation->id]))
            ->assertOk()
            ->assertSee('30');

        $quotationItem = $quotation->items->firstOrFail();

        $this->actingAs($user)->post(route('purchase-order.store'), [
            'quotation_id' => $quotation->id,
            'expected_delivery' => '2026-10-15',
            'payment_terms' => '30 days from delivery',
            'deliver_to' => 'DOH Region VII Warehouse, Cebu City',
            'notes' => 'Single shipment.',
            'items' => [
                $quotationItem->id => [
                    'quotation_item_id' => (string) $quotationItem->id,
                    'included' => '1',
                    'qty' => '15',
                    'min_remaining_months' => '6',
                ],
            ],
        ])->assertSessionHasNoErrors();

        $poLine = PurchaseOrderItem::query()->firstOrFail();

        $allocations = PurchaseOrderItemAllocation::query()
            ->where('purchase_order_item_id', $poLine->id)
            ->pluck('qty', 'project_item_id');

        // Proportional to what each source still needs: 15 x (10/30) = 5 for A, remainder 10 for B.
        $this->assertSame(5, $allocations[$sourceA->id] ?? null);
        $this->assertSame(10, $allocations[$sourceB->id] ?? null);
    }

    public function test_stock_covered_item_is_not_listed_on_the_po_prep_page(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $coveredItem = $this->item(['name' => 'AlphaItemCovered']);
        $openItem = $this->item(['name' => 'BetaItemOpen', 'category' => 'supplies and equipments']);

        // Compile first (in_stock = 0); coverage happens afterwards, the
        // way stock-on-hand typically materialises after a prep list is
        // built. Fulfilled lines can no longer enter a compile POST.
        $coveredSource = $this->projectItem($project, $coveredItem, ['quantity' => 30, 'in_stock' => 0]);
        $openSource = $this->projectItem($project, $openItem, ['quantity' => 20, 'in_stock' => 0]);

        $supplier = Supplier::firstOrCreate(['name' => 'Triple Tact']);

        $this->actingAs($user)->post(route('preplist.store'), [
            'name' => 'July Region VII batch',
            'sources' => [$coveredSource->id, $openSource->id],
            'shelf_life' => [$coveredSource->id => 9, $openSource->id => 3],
        ])->assertSessionHasNoErrors();

        $preplist = Preplist::query()->firstOrFail();

        // Stock shows up AFTER the prep list was compiled — the line is now
        // fulfilled and must not appear on the PO preparation screen.
        $coveredSource->update(['in_stock' => 30]);

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
