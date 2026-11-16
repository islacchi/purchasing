<?php

namespace Tests\Feature;

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
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
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

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type'),
        );
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment; filename="PO-', $disposition);
        $this->assertStringContainsString('.xlsx', $disposition);
        $this->assertDatabaseCount('po_cart_items', 0);

        $quotationItemId = $item->id;
        /** @var PurchaseOrder $po */
        $po = PurchaseOrder::query()->firstOrFail();

        $this->assertSame('draft', $po->status);
        $this->assertSame($supplier->id, $po->supplier_id);
        $this->assertSame('Central Warehouse', $po->deliver_to);

        $line = PurchaseOrderItem::where('purchase_order_id', $po->id)->firstOrFail();
        $this->assertSame($quotationItemId, $line->quotation_item_id);
        $this->assertSame(50, $line->qty);
        $this->assertSame('305.00', (string) $line->unit_price);
    }

    public function test_bulk_checkout_creates_multiple_purchase_orders_and_downloads_a_zip(): void
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
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('.zip', $disposition);

        $zipPath = tempnam(sys_get_temp_dir(), 'po_zip_').'.zip';
        file_put_contents($zipPath, $response->getContent());

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($zipPath) === true);
        $this->assertSame(2, $zip->numFiles);
        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $zip->close();
        unlink($zipPath);

        $this->assertEqualsCanonicalizing(['PO-2026-0001.xlsx', 'PO-2026-0002.xlsx'], $names);
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
}
