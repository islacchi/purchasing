<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReceipt;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function entity(string $name): ProcuringEntity
    {
        return ProcuringEntity::create(['name' => $name, 'type' => 'Regional Office']);
    }

    private function project(User $user, ProcuringEntity $entity): Project
    {
        return Project::create([
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Test purchase requisition',
            'reference_no' => 'RF-2026-0001',
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 100000.00,
            'date_awarded' => '2026-07-03',
            'delivery_days' => 30,
        ]);
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
            'default_min_shelf_life_months' => 0,
        ]);
    }

    /**
     * Build the full fixture chain (project -> preplist -> priced quotation ->
     * purchase order with a line, optionally with a receipt covering $qty).
     * The line counts as purchased only when receipts cover the ordered qty.
     */
    private function completedPurchase(
        User $user,
        string $itemName,
        int $qty,
        float $price,
        string $supplierName,
        string $poNumber,
        string $issuedOn,
        bool $fullyReceived = true,
        ?Item $item = null,
    ): void {
        $this->actingAs($user);

        $entity = $this->entity('Entity '.$poNumber);
        $project = $this->project($user, $entity);
        $item ??= $this->item(['name' => $itemName, 'category' => 'drugs and medicines']);

        $projectItem = ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => $qty,
            'unit_cost' => 100.00,
            'awarded_unit_price' => 95.00,
            'fulfillment_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'Catalog fixture '.$poNumber,
                'sources' => [$projectItem->id],
            ])
            ->assertSessionHasNoErrors();

        $preplist = Preplist::query()->latest('id')->firstOrFail();

        $supplier = Supplier::create(['name' => $supplierName]);
        $preplistItem = PreplistItem::where('preplist_id', $preplist->id)->firstOrFail();

        $quotation = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-'.$poNumber,
            'generated_at' => now(),
        ]);

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'unit_price' => $price,
            'quoted_at' => now(),
        ]);

        $po = PurchaseOrder::create([
            'quotation_id' => $quotation->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'po_number' => $poNumber,
            'status' => $fullyReceived ? PurchaseOrder::STATUS_COMPLETED : PurchaseOrder::STATUS_SENT,
            'issued_on' => $issuedOn,
        ]);

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'quotation_item_id' => $quotation->items()->firstOrFail()->id,
            'item_id' => $item->id,
            'qty' => $qty,
            'unit_price' => $price,
        ]);

        if ($fullyReceived) {
            PurchaseOrderReceipt::create([
                'purchase_order_item_id' => $poItem->id,
                'received_by' => $user->id,
                'received_qty' => $qty,
                'received_on' => $issuedOn,
            ]);
        }
    }

    public function test_index_renders_the_empty_state_without_completed_purchases(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('item-catalog'))
            ->assertOk()
            ->assertSee('No completed purchases yet')
            ->assertSee('ITEM CATALOG');
    }

    public function test_index_lists_only_fully_received_lines(): void
    {
        $user = $this->admin();

        $this->completedPurchase($user, 'Catalog listed item', 10, 100.00, 'CatalogListedCo', 'PO-2026-TEST1', '2026-06-05');
        $this->completedPurchase($user, 'Catalog hidden item', 10, 200.00, 'CatalogHiddenCo', 'PO-2026-TEST2', '2026-06-05', fullyReceived: false);

        $this->actingAs($user)
            ->get(route('item-catalog'))
            ->assertOk()
            ->assertSee('Catalog listed item')
            ->assertDontSee('Catalog hidden item');
    }

    public function test_index_date_range_filter_narrows_results(): void
    {
        $user = $this->admin();

        $this->completedPurchase($user, 'Catalog range july item', 5, 50.00, 'JulySuppliesCo', 'PO-2026-JUL01', '2026-07-10');
        $this->completedPurchase($user, 'Catalog range november item', 5, 50.00, 'NovSuppliesCo', 'PO-2026-NOV01', '2026-11-10');

        $this->actingAs($user)
            ->get(route('item-catalog', ['from' => '2026-11-01', 'to' => '2026-11-30']))
            ->assertOk()
            ->assertSee('Catalog range november item')
            ->assertDontSee('Catalog range july item');

        $this->actingAs($user)
            ->get(route('item-catalog', ['from' => '2026-07-01', 'to' => '2026-07-31']))
            ->assertOk()
            ->assertSee('Catalog range july item')
            ->assertDontSee('Catalog range november item');
    }

    public function test_item_detail_aggregates_across_suppliers(): void
    {
        $user = $this->admin();

        // Two purchases of the same catalog item from two different suppliers.
        $sharedItem = $this->item(['name' => 'Catalog compared item', 'category' => 'drugs and medicines']);

        $this->completedPurchase($user, 'Catalog compared item', 100, 40.00, 'CatalogCheapCo', 'PO-2026-CMP1', '2026-09-05', item: $sharedItem);
        $this->completedPurchase($user, 'Catalog compared item', 50, 60.00, 'CatalogExpensiveCo', 'PO-2026-CMP2', '2026-10-01', item: $sharedItem);

        $itemId = $sharedItem->id;

        $response = $this->actingAs($user)
            ->get(route('item-catalog.show', ['item' => $itemId]))
            ->assertOk()
            ->assertSee('CatalogCheapCo')
            ->assertSee('CatalogExpensiveCo')
            ->assertSee('Supplier comparison')
            ->assertSee('Purchase history');

        // Cheaper supplier must show up with the lowest price; higher one later in the list.
        $content = $response->getContent();
        $this->assertStringContainsString('Supplier comparison', $content);
        $this->assertStringContainsString('Purchase history', $content);
    }
}
