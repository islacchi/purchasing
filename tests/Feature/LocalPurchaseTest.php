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
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    private function viewer(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'user2')->firstOrFail();
    }

    private function entity(array $overrides = []): ProcuringEntity
    {
        return ProcuringEntity::create(array_merge([
            'name' => 'DOH Region VII',
            'type' => 'Regional Office',
        ], $overrides));
    }

    private function project(User $user, ProcuringEntity $entity): Project
    {
        return Project::create([
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Purchase of pharma supplies',
            'reference_no' => 'RF-2026-0042',
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 482000.00,
            'date_awarded' => '2026-07-03',
            'delivery_days' => 30,
        ]);
    }

    private function item(array $overrides = []): Item
    {
        $category = ItemCategory::firstOrCreate(['name' => 'drugs and medicines']);

        return Item::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Mefenamic Capsule - 500mg',
            'unit' => 'Box',
            'default_min_shelf_life_months' => 0,
        ], $overrides));
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

    private function fixture(User $user): array
    {
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'July Region VII batch',
                'sources' => [$projectItem->id],
                'shelf_life' => [$projectItem->id => 0],
            ])
            ->assertSessionHasNoErrors();

        $preplist = Preplist::query()->latest('id')->firstOrFail();
        $preplistItem = PreplistItem::where('preplist_id', $preplist->id)->firstOrFail();

        return [
            'entity' => $entity,
            'project' => $project,
            'item' => $item,
            'projectItem' => $projectItem,
            'preplist' => $preplist,
            'preplistItem' => $preplistItem,
        ];
    }

    public function test_admin_can_raise_a_local_purchase_order(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'expected_delivery' => '2026-12-01',
                'payment_terms' => 'Cash on pickup',
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '305.00']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();

        $this->assertSame('local', $po->source);
        $this->assertNull($po->quotation_id);
        $this->assertSame($supplier->id, (int) $po->supplier_id);
        $this->assertSame('draft', $po->status);

        $poItem = $po->items()->firstOrFail();

        $this->assertNull($poItem->quotation_item_id);
        $this->assertSame(10, (int) $poItem->qty);
        $this->assertEquals(305.00, (float) $poItem->unit_price);
        $this->assertSame(10, (int) $poItem->allocations()->sum('qty'));
    }

    public function test_quantity_above_remaining_is_rejected(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 51, 'unit_price' => '305.00']],
            ])
            ->assertSessionHasErrors('local_purchase');

        $this->assertSame(0, PurchaseOrder::query()->count());
    }

    public function test_at_least_one_line_must_be_selected(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [],
                'lines' => [],
            ])
            ->assertSessionHasErrors('selected');

        $this->assertSame(0, PurchaseOrder::query()->count());
    }

    public function test_admin_can_raise_a_local_purchase_order_without_prices(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $this->assertSame('local', $po->source);
        $this->assertSame('draft', $po->status);

        $poItem = $po->items()->firstOrFail();
        $this->assertNull($poItem->unit_price);
        $this->assertSame(10, (int) $poItem->qty);
        $this->assertSame(10, (int) $poItem->allocations()->sum('qty'));
    }

    public function test_bought_records_prices_receipts_and_completes_the_order(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $poItem = $po->items()->firstOrFail();

        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'prices' => [$poItem->id => '305.00'],
            ])
            ->assertSessionHasNoErrors();

        $po->refresh();
        $poItem->refresh();

        $this->assertSame('completed', $po->status);
        $this->assertTrue($poItem->isReceived());
        $this->assertEquals(305.00, (float) $poItem->unit_price);
        $this->assertSame(1, $poItem->receipts()->count());
        $this->assertSame(10, $poItem->qty); // quantity not touched
    }

    public function test_bought_scopes_the_purchase_to_the_requested_line(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $poItem = $po->items()->firstOrFail();

        // First trip on the specific line only.
        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'bought_line_id' => $poItem->id,
                'quantities' => [$poItem->id => 4],
                'prices' => [$poItem->id => '305.00'],
            ])
            ->assertSessionHasNoErrors();

        $poItem->refresh();

        $this->assertFalse($poItem->isReceived());
        $this->assertSame('partially_received', $po->refresh()->status);
        $this->assertSame(4, (int) $poItem->receipts()->sum('received_qty'));

        // Second trip on the same line covers the remainder and completes the order.
        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'bought_line_id' => $poItem->id,
                'quantities' => [$poItem->id => 6],
            ])
            ->assertSessionHasNoErrors();

        $poItem->refresh();

        $this->assertTrue($poItem->isReceived());
        $this->assertSame('completed', $po->refresh()->status);
    }

    public function test_bought_is_rejected_for_a_completed_local_purchase(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $poItem = $po->items()->firstOrFail();

        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'bought_line_id' => $poItem->id,
                'quantities' => [$poItem->id => 10],
                'prices' => [$poItem->id => '305.00'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('completed', $po->refresh()->status);

        // The order is closed; buying through the line is rejected.
        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'bought_line_id' => $poItem->id,
                'quantities' => [$poItem->id => 2],
            ])
            ->assertSessionHasErrors('bought');
    }

    public function test_bought_requires_the_price_for_every_tbd_line(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();

        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'prices' => [],
            ])
            ->assertSessionHasErrors('prices');

        $po->refresh();
        $this->assertSame('draft', $po->status);
        $this->assertNull($po->items()->first()->unit_price);
        $this->assertSame(0, \App\Models\PurchaseOrderReceipt::query()->count());
    }

    public function test_local_purchase_order_cannot_be_sent_to_supplier(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 5, 'unit_price' => '100']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();

        $this->actingAs($user)
            ->post(route('purchase-order.send', ['purchaseOrder' => $po->id]))
            ->assertSessionHasErrors('purchase_order');

        $po->refresh();
        $this->assertSame('draft', $po->status);
    }

    public function test_local_purchase_creates_a_draft_order_without_receipts(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '305.00']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $this->assertSame('draft', $po->status);

        $poItem = $po->items()->firstOrFail();
        $this->assertSame(10, (int) $poItem->qty);
        $this->assertEquals(305.00, (float) $poItem->unit_price);
        $this->assertSame(0, \App\Models\PurchaseOrderReceipt::query()->count());
        $this->assertSame(0, (int) $f['projectItem']->fresh()->in_stock);
    }

    public function test_partial_bought_quantity_keeps_the_line_open_until_fully_covered(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '305.00']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $poItem = $po->items()->firstOrFail();

        // First trip: 4 of the 10 ordered units.
        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'quantities' => [$poItem->id => 4],
            ])
            ->assertSessionHasNoErrors();

        $poItem->refresh();

        $this->assertSame(10, (int) $poItem->qty); // ordered qty is untouched
        $this->assertSame(10, (int) $poItem->allocations()->sum('qty'));
        $this->assertSame(4, (int) $poItem->receipts()->sum('received_qty'));
        $this->assertFalse($poItem->isReceived());
        $this->assertSame(6, $poItem->remainingQty());
        $this->assertSame('partially_received', $po->refresh()->status);

        $projectItem = $f['projectItem']->fresh();
        $this->assertSame(4, (int) $projectItem->in_stock);
        $this->assertSame(46, $projectItem->remainingQuantity());

        // Second trip: the remaining 6 completes the line and the order.
        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'quantities' => [$poItem->id => 6],
            ])
            ->assertSessionHasNoErrors();

        $poItem->refresh();

        $this->assertSame(10, (int) $poItem->receipts()->sum('received_qty'));
        $this->assertTrue($poItem->isReceived());
        $this->assertSame('completed', $po->refresh()->status);
        $this->assertSame(10, (int) $f['projectItem']->fresh()->in_stock);
    }

    public function test_bought_quantity_cannot_exceed_the_outstanding_quantity(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '305.00']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $poItem = $po->items()->firstOrFail();

        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'quantities' => [$poItem->id => 11],
            ])
            ->assertSessionHasErrors('quantities');

        $this->assertSame(0, (int) $poItem->receipts()->sum('received_qty'));
        $this->assertSame('draft', $po->refresh()->status);
    }

    public function test_bought_can_skip_a_line_with_zero_quantity(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '']],
            ])
            ->assertSessionHasNoErrors();

        $po = PurchaseOrder::query()->latest('id')->firstOrFail();
        $poItem = $po->items()->firstOrFail();

        // Nothing bought on this trip: no price is demanded and no receipt is written.
        $this->actingAs($user)
            ->post(route('purchase-order.bought', ['purchaseOrder' => $po->id]), [
                'quantities' => [$poItem->id => 0],
            ])
            ->assertSessionHasErrors('quantities'); // all zero => nothing recorded

        $this->assertSame(0, \App\Models\PurchaseOrderReceipt::query()->count());
        $this->assertSame('draft', $po->refresh()->status);
        $this->assertNull($poItem->refresh()->unit_price);
    }

    public function test_stock_bump_is_split_across_allocations_for_partial_receipts(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();

        $first = $this->projectItem($project, $item, ['quantity' => 50, 'in_stock' => 0]);
        $second = $this->projectItem($project, $item, ['quantity' => 30, 'in_stock' => 0]);

        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $purchaseOrder = PurchaseOrder::create([
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'po_number' => 'PO-ALLOC-TEST',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'source' => PurchaseOrder::SOURCE_LOCAL,
        ]);

        $line = \App\Models\PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'item_id' => $item->id,
            'qty' => 80,
            'unit_price' => 100,
        ]);

        \App\Models\PurchaseOrderItemAllocation::create([
            'purchase_order_item_id' => $line->id,
            'project_item_id' => $first->id,
            'qty' => 50,
        ]);
        \App\Models\PurchaseOrderItemAllocation::create([
            'purchase_order_item_id' => $line->id,
            'project_item_id' => $second->id,
            'qty' => 30,
        ]);

        \App\Support\PurchaseReceipts::finalize($line, $user->id, 30);
        $this->assertSame(18, (int) $first->fresh()->in_stock);
        $this->assertSame(12, (int) $second->fresh()->in_stock);

        \App\Support\PurchaseReceipts::finalize($line, $user->id, 50);
        $this->assertSame(50, (int) $first->fresh()->in_stock);
        $this->assertSame(30, (int) $second->fresh()->in_stock);
        $this->assertSame('fulfilled', $first->fresh()->fulfillment_status);
    }

    public function test_local_purchase_excel_leaves_amounts_blank(): void
    {
        $user = $this->admin();
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $f = $this->fixture($user);
        $preplistItem = $f['preplistItem'];

        $response = $this->actingAs($user)
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 10, 'unit_price' => '']],
            ]);

        // Creating the draft local purchase order also returns the Excel document.
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $temporaryPath = tempnam(sys_get_temp_dir(), 'po_workbook_local_');
        file_put_contents($temporaryPath, $response->getContent());

        $zip = new \ZipArchive;
        $zip->open($temporaryPath);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($temporaryPath);

        $this->assertIsString($sheetXml);
        $this->assertStringContainsString('Mefenamic Capsule - 500mg', $sheetXml);
        $this->assertStringContainsString('PURCHASE ORDER', $sheetXml);

        // Local purchases keep the unit price and amount columns blank — the
        // price is only recorded once the purchase is marked as bought. The
        // quoted price (from the project award) is still surfaced as a column.
        $this->assertStringContainsString('<c r="F17" s="4"/>', $sheetXml);
        $this->assertStringContainsString('<c r="G17" s="4"/>', $sheetXml);
        $this->assertStringContainsString('<c r="I17" s="4" t="inlineStr"><is><t xml:space="preserve">₱ 305.00</t></is></c>', $sheetXml);
        $this->assertStringContainsString('<c r="G18" s="10"/>', $sheetXml);
        $this->assertStringContainsString('<c r="G20" s="12"/>', $sheetXml);
    }

    public function test_viewer_cannot_create_a_local_purchase_order(): void
    {
        $admin = $this->admin();
        $f = $this->fixture($admin);
        $supplier = Supplier::create(['name' => 'Bohol Cash Store']);
        $preplistItem = $f['preplistItem'];

        $this->actingAs($this->viewer())
            ->post(route('preplist.local-purchase.store', ['preplist' => $f['preplist']->id]), [
                'supplier_id' => $supplier->id,
                'selected' => [$preplistItem->id],
                'lines' => [$preplistItem->id => ['qty' => 5, 'unit_price' => '305.00']],
            ])
            ->assertForbidden();

        $this->assertSame(0, PurchaseOrder::query()->count());
    }
}
