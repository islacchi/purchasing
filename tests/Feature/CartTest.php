<?php

namespace Tests\Feature;

use App\Http\Controllers\PoCartController;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\PoCartItem;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderItemAllocation;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CartTest extends TestCase
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
        ], $overrides));
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

    private function compile(User $user, array $payload): Preplist
    {
        $this->actingAs($user)
            ->post(route('preplist.store'), $payload)
            ->assertSessionHasNoErrors();

        return Preplist::query()->latest('id')->firstOrFail();
    }

    private function createQuotation(User $user, Preplist $preplist, Supplier $supplier, array $overrides = []): Quotation
    {
        Storage::disk('local')->put('quotations/test-'.$supplier->id.'.xlsx', 'dummy-content-'.$supplier->id);

        return Quotation::create(array_merge([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-'.now()->year.'-000'.$supplier->id,
            'generated_at' => now(),
            'document_path' => 'quotations/test-'.$supplier->id.'.xlsx',
            'document_name' => 'quotation-'.$supplier->id.'.xlsx',
            'document_size' => strlen('dummy-content-'.$supplier->id),
        ], $overrides));
    }

    /**
     * Seed a priced quotation line with enough remaining need to test the cart.
     */
    private function seedPricedLine(User $user, ProcuringEntity $entity, Supplier $supplier, string $itemName, float $price): QuotationItem
    {
        $project = $this->project($user, $entity, ['title' => 'Purchase of '.strtolower($itemName), 'reference_no' => 'RF-2026-0043']);
        $item = $this->item(['name' => $itemName]);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 50]);
        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 0],
        ]);
        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::where('preplist_id', $preplist->id)->firstOrFail();

        return QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => $price,
            'quoted_at' => now(),
        ]);
    }

    public function test_cart_page_starts_empty(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('po-cart'))
            ->assertOk()
            ->assertSee('Your cart is empty');
    }

    public function test_add_entire_quotation_to_cart(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $item = $this->seedPricedLine($user, $entity, $supplier, 'Mefenamic Capsule - 500mg', 305.00);

        $this->actingAs($user)
            ->post(route('po-cart.add'), ['quotation_id' => $item->quotation_id])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('po_cart_items', 1);

        $this->actingAs($user)
            ->get(route('po-cart'))
            ->assertOk()
            ->assertSee('Triple Tact')
            ->assertSee('Mefenamic Capsule - 500mg')
            ->assertSee('Order summary');
    }

    public function test_checkout_accepts_submitted_qty_overrides(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $item = $this->seedPricedLine($user, $entity, $supplier, 'Amoxicillin 500mg', 305.00);

        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $item->quotation_id]);

        $cartItem = PoCartItem::firstOrFail();

        // Submit a higher quantity through the checkout form payload.
        $this->actingAs($user)
            ->post(route('po-cart.checkout'), [
                'qty' => [(string) $cartItem->id => 30],
            ]);

        $this->assertDatabaseCount('purchase_orders', 1);
        $po = \App\Models\PurchaseOrder::firstOrFail();
        $this->assertSame(30, (int) $po->items()->firstOrFail()->qty);
    }

    public function test_checkout_accepts_the_json_qty_payload(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $item = $this->seedPricedLine($user, $entity, $supplier, 'Amoxicillin 500mg', 305.00);

        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $item->quotation_id]);

        $cartItem = PoCartItem::firstOrFail();

        $this->actingAs($user)
            ->post(route('po-cart.checkout'), [
                'qty_json' => json_encode([(string) $cartItem->id => 40]),
            ]);

        $this->assertDatabaseCount('purchase_orders', 1);
        $po = \App\Models\PurchaseOrder::firstOrFail();
        $this->assertSame(40, (int) $po->items()->firstOrFail()->qty);
    }

    public function test_adding_same_line_is_idempotent(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Triple Tact']);
        $item = $this->seedPricedLine($user, $entity, $supplier, 'Mefenamic Capsule - 500mg', 305.00);

        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $item->quotation_id]);
        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $item->quotation_id]);

        $this->assertDatabaseCount('po_cart_items', 1);
    }

    public function test_line_without_a_price_cannot_be_added(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Mefenamic Capsule - 500mg']);
        $projectItem = $this->projectItem($project, $item);
        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);
        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::firstOrFail();
        $quotationItem = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => null,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('po-cart.add'), ['quotation_item_id' => $quotationItem->id])
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('po_cart_items', 0);
    }

    public function test_line_fully_covered_by_stock_cannot_be_added(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Mefenamic Capsule - 500mg']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 50, 'in_stock' => 30]);
        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 0],
        ]);
        // After compiling, stock fully covers the line; remaining is now zero.
        $projectItem->update(['in_stock' => 50]);
        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::where('preplist_id', $preplist->id)->firstOrFail();
        $quotationItem = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 305.00,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('po-cart.add'), ['quotation_item_id' => $quotationItem->id])
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('po_cart_items', 0);
    }

    public function test_checkout_creates_a_draft_po_and_clears_the_cart(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $item = $this->seedPricedLine($user, $entity, $supplier, 'Mefenamic Capsule - 500mg', 305.00);

        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $item->quotation_id]);
        $this->assertDatabaseCount('po_cart_items', 1);

        $response = $this->actingAs($user)
            ->post(route('po-cart.checkout'), [
                'expected_delivery' => '2026-12-15',
                'payment_terms' => '30 days from delivery',
                'deliver_to' => 'Central Warehouse',
            ]);

        $this->assertDatabaseCount('po_cart_items', 0);

        $quotationItemId = $item->id;
        /** @var PurchaseOrder $po */
        $po = PurchaseOrder::query()->firstOrFail();

        // Clicking "Create draft POs" generates the Excel workbook of the
        // created draft PO as an attachment.
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString($po->po_number, (string) $response->headers->get('Content-Disposition'));

        $this->assertSame('draft', $po->status);
        $this->assertSame($supplier->id, $po->supplier_id);
        $this->assertSame('Central Warehouse', $po->deliver_to);

        $line = PurchaseOrderItem::where('purchase_order_id', $po->id)->firstOrFail();
        $this->assertSame($quotationItemId, $line->quotation_item_id);
        $this->assertSame(50, $line->qty);
        $this->assertSame('305.00', (string) $line->unit_price);
    }

    public function test_bulk_checkout_creates_multiple_purchase_orders_and_returns_the_workbook(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $project = $this->project($user, $entity, ['title' => 'Purchase of pharma supplies', 'reference_no' => 'RF-2026-0044']);
        $itemA = $this->item(['name' => 'Mefenamic Capsule - 500mg']);
        $itemB = $this->item(['name' => 'Cetirizine 10mg tablet']);
        $piA = $this->projectItem($project, $itemA);
        $piB = $this->projectItem($project, $itemB);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$piA->id, $piB->id],
            'shelf_life' => [$piA->id => 0, $piB->id => 0],
        ]);

        $smartPharma = Supplier::create(['name' => 'Smart Pharma']);
        $abcMedicals = Supplier::create(['name' => 'ABC Medicals']);
        $quotationSmart = $this->createQuotation($user, $preplist, $smartPharma);
        $quotationAbc = $this->createQuotation($user, $preplist, $abcMedicals);
        $lines = PreplistItem::where('preplist_id', $preplist->id)->get()->keyBy('item_id');

        QuotationItem::create([
            'quotation_id' => $quotationSmart->id,
            'preplist_item_id' => $lines[$itemA->id]->id,
            'min_remaining_months' => 0,
            'unit_price' => 250.00,
            'quoted_at' => now(),
        ]);
        QuotationItem::create([
            'quotation_id' => $quotationSmart->id,
            'preplist_item_id' => $lines[$itemB->id]->id,
            'min_remaining_months' => 0,
            'unit_price' => 150.00,
            'quoted_at' => now(),
        ]);
        QuotationItem::create([
            'quotation_id' => $quotationAbc->id,
            'preplist_item_id' => $lines[$itemA->id]->id,
            'min_remaining_months' => 0,
            'unit_price' => 240.00,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $quotationSmart->id]);
        // Adding ABC Medicals replaces Smart's line for itemA (one line per item).
        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $quotationAbc->id]);

        $response = $this->actingAs($user)->post(route('po-cart.checkout'));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', (string) $response->headers->get('Content-Type'));

        $this->assertDatabaseCount('purchase_orders', 2);
        $this->assertDatabaseCount('po_cart_items', 0);
    }

    public function test_bulk_add_best_prices_picks_cheapest_and_includes_single_quotes(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $project = $this->project($user, $entity, ['title' => 'Purchase of pharma supplies', 'reference_no' => 'RF-2026-0044']);
        $itemA = $this->item(['name' => 'Mefenamic Capsule - 500mg']);
        $itemB = $this->item(['name' => 'Cetirizine 10mg tablet']);
        $piA = $this->projectItem($project, $itemA);
        $piB = $this->projectItem($project, $itemB);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$piA->id, $piB->id],
            'shelf_life' => [$piA->id => 0, $piB->id => 0],
        ]);

        $smartPharma = Supplier::create(['name' => 'Smart Pharma']);
        $abcMedicals = Supplier::create(['name' => 'ABC Medicals']);
        $quotationSmart = $this->createQuotation($user, $preplist, $smartPharma);
        $quotationAbc = $this->createQuotation($user, $preplist, $abcMedicals);
        $lines = PreplistItem::where('preplist_id', $preplist->id)->get()->keyBy('item_id');

        QuotationItem::create([
            'quotation_id' => $quotationSmart->id,
            'preplist_item_id' => $lines[$itemA->id]->id,
            'min_remaining_months' => 0,
            'unit_price' => 320.00,
            'quoted_at' => now(),
        ]);
        QuotationItem::create([
            'quotation_id' => $quotationSmart->id,
            'preplist_item_id' => $lines[$itemB->id]->id,
            'min_remaining_months' => 0,
            'unit_price' => 150.00,
            'quoted_at' => now(),
        ]);
        QuotationItem::create([
            'quotation_id' => $quotationAbc->id,
            'preplist_item_id' => $lines[$itemA->id]->id,
            'min_remaining_months' => 0,
            'unit_price' => 250.00,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('po-cart.bulk-add'))
            ->assertRedirect(route('po-cart'))
            ->assertSessionHasNoErrors();

        $cart = PoCartItem::with('quotationItem')->get();

        $this->assertCount(2, $cart);

        $prices = $cart->pluck('quotationItem.unit_price')->map(fn ($price) => (float) $price)->all();
        $this->assertContainsEquals(250.0, $prices);
        $this->assertContainsEquals(150.0, $prices);
    }

    public function test_cart_items_remove_endpoint_accepts_post_with_delete_method(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $item = $this->seedPricedLine($user, $entity, $supplier, 'Mefenamic Capsule - 500mg', 305.00);
        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $item->quotation_id]);

        $cartItem = PoCartItem::firstOrFail();

        $this->actingAs($user)
            ->post(route('po-cart.remove', ['cartItem' => $cartItem->id]), ['_method' => 'DELETE'])
            ->assertRedirect();

        $this->assertDatabaseCount('po_cart_items', 0);
    }

    public function test_adding_a_second_quotation_displaces_the_first(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Displacement item']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 50]);
        $preplist = $this->compile($user, [
            'name' => 'Displacement batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 0],
        ]);
        $preplistItem = PreplistItem::firstOrFail();

        $supplierA = Supplier::create(['name' => 'Supplier A']);
        $supplierB = Supplier::create(['name' => 'Supplier B']);

        $quotationA = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplierA->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-'.now()->year.'-DISP-AA',
            'generated_at' => now(),
            'document_path' => 'quotations/a.xlsx',
            'document_name' => 'quotation-a.xlsx',
            'document_size' => 8,
        ]);
        $quotationB = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplierB->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-'.now()->year.'-DISP-BB',
            'generated_at' => now(),
            'document_path' => 'quotations/b.xlsx',
            'document_name' => 'quotation-b.xlsx',
            'document_size' => 8,
        ]);

        $pricedItemA = QuotationItem::create([
            'quotation_id' => $quotationA->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 40.00,
            'quoted_at' => now(),
        ]);
        QuotationItem::create([
            'quotation_id' => $quotationB->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 35.00,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $quotationA->id]);
        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $quotationB->id]);

        $this->assertDatabaseCount('po_cart_items', 1);
        $this->assertDatabaseHas('po_cart_items', [
            'user_id' => $user->id,
            'quotation_item_id' => QuotationItem::where('quotation_id', $quotationB->id)->value('id'),
        ]);
        $this->assertDatabaseMissing('po_cart_items', [
            'user_id' => $user->id,
            'quotation_item_id' => $pricedItemA->id,
        ]);
    }

    public function test_checkout_refuses_duplicate_preplist_lines_in_the_cart(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Duplicated checkout item']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 50]);
        $preplist = $this->compile($user, [
            'name' => 'Duplicate-cart batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 0],
        ]);
        $preplistItem = PreplistItem::firstOrFail();

        $supplierA = Supplier::create(['name' => 'Supplier A']);
        $supplierB = Supplier::create(['name' => 'Supplier B']);

        $quotationA = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplierA->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-'.now()->year.'-DUP-AA',
            'generated_at' => now(),
            'document_path' => 'quotations/a.xlsx',
            'document_name' => 'quotation-a.xlsx',
            'document_size' => 8,
        ]);
        $quotationB = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplierB->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-'.now()->year.'-DUP-BB',
            'generated_at' => now(),
            'document_path' => 'quotations/b.xlsx',
            'document_name' => 'quotation-b.xlsx',
            'document_size' => 8,
        ]);

        QuotationItem::create([
            'quotation_id' => $quotationA->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 40.00,
            'quoted_at' => now(),
        ]);
        QuotationItem::create([
            'quotation_id' => $quotationB->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 35.00,
            'quoted_at' => now(),
        ]);

        // Bypass the add-path displacement and seed both lines into the cart
        // directly, simulating drift (a DB hiccup or hand-inserted rows).
        PoCartItem::create([
            'user_id' => $user->id,
            'quotation_item_id' => QuotationItem::where('quotation_id', $quotationA->id)->value('id'),
            'qty' => 50,
        ]);
        PoCartItem::create([
            'user_id' => $user->id,
            'quotation_item_id' => QuotationItem::where('quotation_id', $quotationB->id)->value('id'),
            'qty' => 50,
        ]);

        // The cart can no longer be forced to hold both lines — add(-1)
        // displaces existing rows. Our checkout guard is a back-stop for
        // drifted state; craft it through direct insert above instead.
        $this->actingAs($user)
            ->post(route('po-cart.checkout'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_duplicate_awarded_line_exception_is_recognized(): void
    {
        $controller = new PoCartController;
        $method = new \ReflectionMethod($controller, 'isDuplicateAwardedLineViolation');
        $method->setAccessible(true);

        $matching = $this->makeQueryException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '7' for key 'purchase_order_items_quotation_item_id_unique'", 23000);
        $this->assertTrue($method->invoke($controller, $matching));

        // A composite-key duplicate (a different constraint) should NOT be re-mapped — we want it to bubble up.
        $compositeOnly = $this->makeQueryException("SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '7-9' for key 'purchase_order_items.purchase_order_id_quotation_item_id_unique'", 23000);
        $this->assertFalse($method->invoke($controller, $compositeOnly));

        // A syntax error is not a duplicate of this kind.
        $syntax = $this->makeQueryException("SQLSTATE[42000]: Syntax error near 'test'", 42000);
        $this->assertFalse($method->invoke($controller, $syntax));
    }

    private function makeQueryException(string $message, string $sqlState): QueryException
    {
        return new QueryException(
            'testing',
            'select 1',
            [],
            new \PDOException($message, (int) $sqlState),
        );
    }

    public function test_checkout_revalidates_quantities(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $item = $this->seedPricedLine($user, $entity, $supplier, 'Mefenamic Capsule - 500mg', 305.00);
        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_id' => $item->quotation_id]);

        // Reduce the project item's needed quantity to 10 so the cart's 50 no longer fits.
        $project = Project::query()->latest('id')->firstOrFail();
        $projectItem = ProjectItem::query()->where('project_id', $project->id)->firstOrFail();
        $projectItem->update(['quantity' => 10]);

        $this->actingAs($user)
            ->post(route('po-cart.checkout'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('purchase_orders', 0);
        $this->assertDatabaseCount('po_cart_items', 1);
    }

    public function test_cart_shows_the_effective_price_when_the_supplier_quotes_a_box_for_a_boxless_item(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Tablet Box Supplier']);

        $project = $this->project($user, $entity);
        // The item has no box requirement; the supplier sells boxes of 50
        // tablets at ₱ 231 each, so the quote is per box.
        $item = $this->item(['name' => 'TabletItemX']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 100]);

        $preplist = $this->compile($user, [
            'name' => 'Boxless batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 0],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::query()->firstOrFail();

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'unit_price' => 231.00,
            'supplier_box_size' => 50,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('po-cart.add'), ['quotation_item_id' => QuotationItem::firstOrFail()->id])
            ->assertSessionHasNoErrors();

        // 231 / 50 = 4.62 per unit — the cart displays and totals from it.
        $response = $this->actingAs($user)->get(route('po-cart'));
        $line = collect($response->viewData('grouped'))
            ->flatMap(fn ($group) => $group['items'])
            ->first();

        $this->assertEqualsWithDelta(4.62, $line['unit_price'], 0.01);
        $this->assertEqualsWithDelta(462.00, $line['line_total'], 0.01);

        // The pack message explains the boxes needed for the current qty.
        $this->assertStringContainsString('Supplier quotes in boxes of 50', $response->getContent());
        $this->assertStringContainsString('you will be buying 2 boxes (100 tablets)', $response->getContent());
    }

    public function test_checkout_creates_the_po_in_the_supplier_units_when_the_quote_is_per_box(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Per-Box Supplier']);

        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'BoxlessSlab']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 200]);

        $preplist = $this->compile($user, [
            'name' => 'Boxless batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 0],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::query()->firstOrFail();

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'unit_price' => 100.00,
            'supplier_box_size' => 100,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('po-cart.add'), ['quotation_item_id' => QuotationItem::firstOrFail()->id]);

        // The cart buys the requirement (200 pcs) at the effective price
        // (₱1.00/unit against the ₱100-per-box quotation).
        $cartItem = PoCartItem::firstOrFail();
        $this->assertSame(200, (int) $cartItem->qty);

        $response = $this->actingAs($user)->post(route('po-cart.checkout'));
        $response->assertOk();

        $line = PurchaseOrderItem::where('quotation_item_id', $quotation->items()->first()->id)->firstOrFail();

        // The generated PO is in the supplier's units: 2 boxes at ₱100
        // each — matching the cart total of ₱200.
        $this->assertSame(2, (int) $line->qty);
        $this->assertSame('100.00', (string) $line->unit_price);
        $this->assertNull($line->box_size);
        $this->assertEqualsWithDelta(200.00, $line->extendedTotal(), 0.01);
    }

    public function test_po_show_displays_the_quoted_price_per_piece_for_boxless_lines(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Per-Box Supplier']);

        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'BoxlessSlab']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 200]);

        $preplist = $this->compile($user, [
            'name' => 'Boxless batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 0],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::query()->firstOrFail();

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'unit_price' => 100.00,
            'supplier_box_size' => 100,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('po-cart.add'), ['quotation_item_id' => QuotationItem::firstOrFail()->id]);

        $this->actingAs($user)->post(route('po-cart.checkout'));

        $po = PurchaseOrder::query()->firstOrFail();
        $po->load(['items.item', 'items.quotationItem']);

        $this->actingAs($user)
            ->get(route('purchase-order.show', ['purchaseOrder' => $po->id]))
            ->assertOk()
            ->assertSee('BoxlessSlab')
            // Unit price column: the quoted price per piece (100 / 100 = 1.00).
            ->assertSee('1.00')
            // Quoted price per box column: the quotation's unit price (₱100.00).
            ->assertSee('100.00')
            // Total unit price column: 2 boxes at ₱100.00 = ₱200.00.
            ->assertSee('200.00');
    }

    public function test_supplier_pack_mismatch_stays_piece_accurate_through_partial_delivery(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $supplier = Supplier::create(['name' => 'Different Pack Supplier']);

        $project = $this->project($user, $entity, ['title' => 'Boxed project', 'reference_no' => 'RF-2026-0071']);
        // 12 boxes of 50 are required; 7 boxes (350 pieces) are on hand.
        $item = $this->item(['name' => 'Boxed DeviceK', 'box_size' => 50]);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 12, 'in_stock' => 350]);

        $preplist = $this->compile($user, [
            'name' => 'Boxed Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 0],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier, [
            'rfq_number' => 'RFQ-'.now()->year.'-0071A',
        ]);
        $preplistItem = PreplistItem::query()->firstOrFail();

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 305.00,
            'supplier_box_size' => 30, // packed in 30s instead of the 50s required
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)->post(route('po-cart.add'), ['quotation_item_id' => QuotationItem::firstOrFail()->id]);

        $cartItem = PoCartItem::query()->firstOrFail();

        // The stock still lacks 5 of our boxes (250 pieces). The supplier
        // covers it with 9 of their boxes (270 pieces) — order the ceiling.
        $this->actingAs($user)
            ->post(route('po-cart.checkout'), [
                'qty' => [(string) $cartItem->id => 9],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('purchase_orders', 1);
        $po = PurchaseOrder::query()->firstOrFail();
        $line = PurchaseOrderItem::query()->firstOrFail();

        // The line snapshots the supplier's pack and is reconciled in pieces.
        $this->assertSame(9, (int) $line->qty);
        $this->assertSame(30, (int) $line->box_size);
        $this->assertTrue($line->trackedInPieces());
        $this->assertSame(270, $line->orderedPieces());

        $this->actingAs($user)
            ->post(route('purchase-order.send', ['purchaseOrder' => $po->id]))
            ->assertRedirect(route('purchase-order.show', ['purchaseOrder' => $po->id]));

        // First delivery: 5 of their boxes arrive — 150 pieces, not a full
        // requirement box. The project must NOT complete on this.
        $this->actingAs($user)
            ->post(route('purchase-order.receive', ['purchaseOrder' => $po->id, 'purchaseOrderItem' => $line->id]), [
                'received_qty' => 150,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(150, (int) $line->fresh()->receipts()->sum('received_qty'));
        $this->assertFalse($line->fresh()->isReceived());
        $this->assertSame(PurchaseOrder::STATUS_PARTIALLY_RECEIVED, $po->fresh()->status);

        $projectItem->refresh();
        $this->assertSame(500, (int) $projectItem->in_stock); // 350 + 150 actually arrived
        $this->assertSame(2, $projectItem->remainingQuantity()); // 100 pieces = 2 of our boxes
        $this->assertSame('partial', $projectItem->fulfillment_status);

        // The rest arrives (4 more boxes = 120 pieces): the order closes but
        // only 620 of 600 needed are on hand — over-delivery, not a magic
        // requirement completion.
        $this->actingAs($user)
            ->post(route('purchase-order.receive', ['purchaseOrder' => $po->id, 'purchaseOrderItem' => $line->id]), [
                'received_qty' => 120,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(PurchaseOrder::STATUS_COMPLETED, $po->fresh()->status);
        $this->assertTrue($line->fresh()->isReceived());

        $projectItem->refresh();
        $this->assertSame(620, (int) $projectItem->in_stock);
        $this->assertSame('fulfilled', $projectItem->fulfillment_status);
        // The single source absorbs the whole allocated order quantity.
        $this->assertSame(270, (int) PurchaseOrderItemAllocation::query()
            ->where('purchase_order_item_id', $line->id)->sum('qty'));
    }
}
