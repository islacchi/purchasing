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
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
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

        return User::where('email', 'user2@example.com')->firstOrFail();
    }

    private function procurementOfficer(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('email', 'user1@example.com')->firstOrFail();
    }

    private static int $poSequence = 9000;

    private function makePurchaseOrder(User $user, string $status = PurchaseOrder::STATUS_DRAFT): PurchaseOrder
    {
        $quoted = $this->quotedQuotation($user);
        $quotation = $quoted['quotation'];

        $purchaseOrder = PurchaseOrder::create([
            'quotation_id' => $quotation->id,
            'supplier_id' => $quoted['supplier']->id,
            'created_by' => $user->id,
            'po_number' => sprintf('PO-%d-%04d', now()->year, ++self::$poSequence),
            'status' => $status,
            'issued_on' => $status === PurchaseOrder::STATUS_DRAFT ? null : today(),
        ]);

        foreach ($quoted['items'] as $item) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $purchaseOrder->id,
                'quotation_item_id' => $item['quotation_item']->id,
                'item_id' => $item['preplist_item']->item_id,
                'qty' => 10,
                'unit_price' => $item['quotation_item']->unit_price,
            ]);
        }

        return $purchaseOrder->fresh(['items']);
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
        $attributes = array_merge([
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Purchase of pharma supplies',
            'reference_no' => 'RF-2026-0042',
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 482000.00,
            'date_awarded' => '2026-07-03',
            'delivery_days' => 30,
            'notes' => 'Quarterly procurement of pharmaceutical supplies.',
        ], $overrides);

        if (
            empty($attributes['delivery_due_date'])
            && ! empty($attributes['date_awarded'])
            && ! empty($attributes['delivery_days'])
        ) {
            $attributes['delivery_due_date'] = Carbon::parse($attributes['date_awarded'])
                ->addDays((int) $attributes['delivery_days'])
                ->toDateString();
        }

        return Project::create($attributes);
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

    private static int $rfqSequence = 4200;

    /**
     * Build a quoted quotation with two items and return its context.
     *
     * @return array{quotation: Quotation, supplier: Supplier, preplist: Preplist, items: array<int, array{preplist_item: PreplistItem, quotation_item: QuotationItem}>}
     */
    private function quotedQuotation(User $user): array
    {
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $firstItem = $this->item(['name' => 'AlphaItemAlpha']);
        $secondItem = $this->item(['name' => 'BetaItemBeta', 'category' => 'supplies and equipments']);
        $firstSource = $this->projectItem($project, $firstItem, ['quantity' => 30]);
        $secondSource = $this->projectItem($project, $secondItem, ['quantity' => 20]);
        $supplier = Supplier::firstOrCreate(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$firstSource->id, $secondSource->id],
            'shelf_life' => [$firstSource->id => 9, $secondSource->id => 3],
        ]);

        $preplistItems = PreplistItem::query()->orderBy('item_id')->get()->keyBy('item_id');
        $preplistItemOne = $preplistItems[$firstItem->id];
        $preplistItemTwo = $preplistItems[$secondItem->id];

        $quotation = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'rfq_number' => sprintf('RFQ-%d-%04d', (int) now()->year, ++self::$rfqSequence),
            'generated_at' => now(),
        ]);

        $quotationItemOne = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItemOne->id,
            'min_remaining_months' => 9,
            'unit_price' => 305.00,
            'offered_shelf_life_months' => 12,
            'quoted_at' => now(),
        ]);

        $quotationItemTwo = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItemTwo->id,
            'min_remaining_months' => 3,
            'unit_price' => 250.00,
            'offered_shelf_life_months' => null,
            'quoted_at' => now(),
        ]);

        return [
            'quotation' => $quotation,
            'supplier' => $supplier,
            'preplist' => $preplist,
            'items' => [
                ['preplist_item' => $preplistItemOne, 'quotation_item' => $quotationItemOne],
                ['preplist_item' => $preplistItemTwo, 'quotation_item' => $quotationItemTwo],
            ],
        ];
    }

    private function storePayload(Quotation $quotation): array
    {
        $items = [];

        foreach ($quotation->items as $quotationItem) {
            $items[$quotationItem->id] = [
                'quotation_item_id' => (string) $quotationItem->id,
                'included' => '1',
                'qty' => '10',
                'min_remaining_months' => '9',
            ];
        }

        return [
            'quotation_id' => $quotation->id,
            'expected_delivery' => '2026-10-15',
            'payment_terms' => '30 days from delivery',
            'deliver_to' => 'DOH Region VII Warehouse, Cebu City',
            'notes' => 'Deliver in a single shipment.',
            'items' => $items,
        ];
    }

    public function test_purchase_order_index_shows_empty_state_when_none_exist(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('purchase-order'))
            ->assertOk()
            ->assertSee('No purchase orders yet');
    }

    public function test_create_page_shows_the_real_quotation(): void
    {
        $user = $this->admin();
        $quoted = $this->quotedQuotation($user);
        $quotation = $quoted['quotation'];

        $this->actingAs($user)
            ->get(route('purchase-order.create', ['rfq' => $quotation->id]))
            ->assertOk()
            ->assertSee($quoted['supplier']->name)
            ->assertSee('AlphaItemAlpha')
            ->assertSee('BetaItemBeta')
            ->assertSee('₱305.00')
            ->assertSee(route('purchase-order.store'), false);
    }

    public function test_posting_the_create_form_persists_the_draft_purchase_order(): void
    {
        $user = $this->admin();
        $quoted = $this->quotedQuotation($user);
        $quotation = $quoted['quotation'];

        $response = $this->actingAs($user)
            ->post(route('purchase-order.store'), $this->storePayload($quotation));

        $purchaseOrder = PurchaseOrder::query()->firstOrFail();

        $response->assertRedirect(route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id]));

        $this->assertMatchesRegularExpression('/^PO-'.now()->year.'-\d{4}$/', $purchaseOrder->po_number);
        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $purchaseOrder->status);
        $this->assertSame($quotation->id, $purchaseOrder->quotation_id);
        $this->assertSame($quoted['supplier']->id, $purchaseOrder->supplier_id);
        $this->assertNotNull($purchaseOrder->expected_delivery_on);

        $this->assertDatabaseCount('purchase_order_items', 2);

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $purchaseOrder->id,
            'qty' => 10,
            'unit_price' => 305.00,
        ]);

        $this->actingAs($user)
            ->get(route('purchase-order'))
            ->assertOk()
            ->assertSee($purchaseOrder->po_number)
            ->assertSee($quoted['supplier']->name)
            ->assertSee('Draft')
            ->assertSee('July Region VII batch');
    }

    public function test_posting_the_form_allocates_quantities_across_the_project_sources(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $projectOne = $this->project($user, $entity, ['title' => 'Project One', 'reference_no' => 'RF-2026-0101']);
        $projectTwo = $this->project($user, $entity, ['title' => 'Project Two', 'reference_no' => 'RF-2026-0102']);

        $item = $this->item(['name' => 'AlphaItemAlpha']);
        $projectItemOne = $this->projectItem($projectOne, $item, ['quantity' => 30]);
        $projectItemTwo = $this->projectItem($projectTwo, $item, ['quantity' => 20]);
        $supplier = Supplier::firstOrCreate(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'November batch',
            'sources' => [$projectItemOne->id, $projectItemTwo->id],
            'shelf_life' => [$projectItemOne->id => 1, $projectItemTwo->id => 1],
        ]);

        $preplistItem = PreplistItem::firstOrFail();

        $quotation = Quotation::create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-'.now()->year.'-0099',
            'generated_at' => now(),
        ]);

        $quotationItem = QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 1,
            'unit_price' => 100.00,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('purchase-order.store'), [
                'quotation_id' => $quotation->id,
                'expected_delivery' => '2026-10-15',
                'items' => [
                    $quotationItem->id => [
                        'quotation_item_id' => (string) $quotationItem->id,
                        'included' => '1',
                        'qty' => '25',
                        'min_remaining_months' => '1',
                    ],
                ],
            ])
            ->assertRedirect();

        $purchaseOrderItem = PurchaseOrderItem::firstOrFail();

        $this->assertDatabaseHas('purchase_order_item_allocations', [
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'project_item_id' => $projectItemOne->id,
            'qty' => 15,
        ]);
        $this->assertDatabaseHas('purchase_order_item_allocations', [
            'purchase_order_item_id' => $purchaseOrderItem->id,
            'project_item_id' => $projectItemTwo->id,
            'qty' => 10,
        ]);
    }

    public function test_posting_more_than_requested_is_rejected(): void
    {
        $user = $this->admin();
        $quoted = $this->quotedQuotation($user);
        $quotation = $quoted['quotation'];

        $payload = $this->storePayload($quotation);
        $payload['items'][$quoted['items'][0]['quotation_item']->id]['qty'] = '999';

        $this->actingAs($user)
            ->post(route('purchase-order.store'), $payload)
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_posting_with_no_selected_items_is_rejected(): void
    {
        $user = $this->admin();
        $quoted = $this->quotedQuotation($user);
        $quotation = $quoted['quotation'];

        $payload = $this->storePayload($quotation);

        foreach ($payload['items'] as $rowId => $row) {
            $payload['items'][$rowId]['included'] = '0';
        }

        $this->actingAs($user)
            ->post(route('purchase-order.store'), $payload)
            ->assertSessionHasErrors('items');

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_send_receive_and_cancel_workflow(): void
    {
        $user = $this->admin();
        $quoted = $this->quotedQuotation($user);
        $quotation = $quoted['quotation'];

        $purchaseOrder = PurchaseOrder::create([
            'quotation_id' => $quotation->id,
            'supplier_id' => $quoted['supplier']->id,
            'created_by' => $user->id,
            'po_number' => 'PO-'.now()->year.'-0001',
            'status' => PurchaseOrder::STATUS_DRAFT,
        ]);

        // Send requires a draft order.
        $this->actingAs($user)
            ->post(route('purchase-order.send', ['purchaseOrder' => $purchaseOrder->id]))
            ->assertRedirect(route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id]));

        $this->assertSame(PurchaseOrder::STATUS_SENT, $purchaseOrder->fresh()->status);

        $items = QuotationItem::with('preplistItem')->get();

        // The purchase order has no lines yet, so create them from the quotation.
        foreach ($items as $quotationItem) {
            PurchaseOrderItem::create([
                'purchase_order_id' => $purchaseOrder->id,
                'quotation_item_id' => $quotationItem->id,
                'item_id' => $quotationItem->preplistItem->item_id,
                'qty' => (int) $quotationItem->preplistItem->sources->sum('qty'),
                'unit_price' => $quotationItem->unit_price,
            ]);
        }

        $lines = PurchaseOrderItem::query()->orderBy('id')->get();
        $firstLine = $lines->first();
        $secondLine = $lines->last();

        // Receive the first line.
        $this->actingAs($user)
            ->post(route('purchase-order.receive', ['purchaseOrder' => $purchaseOrder->id, 'purchaseOrderItem' => $firstLine->id]))
            ->assertRedirect(route('purchase-order.show', ['purchaseOrder' => $purchaseOrder->id]));

        $this->assertSame(PurchaseOrder::STATUS_PARTIALLY_RECEIVED, $purchaseOrder->fresh()->status);
        $this->assertDatabaseCount('purchase_order_receipts', 1);

        // Receive the second line.
        $this->actingAs($user)
            ->post(route('purchase-order.receive', ['purchaseOrder' => $purchaseOrder->id, 'purchaseOrderItem' => $secondLine->id]));

        $this->assertSame(PurchaseOrder::STATUS_COMPLETED, $purchaseOrder->fresh()->status);

        // A completed order cannot be cancelled.
        $this->actingAs($user)
            ->post(route('purchase-order.cancel', ['purchaseOrder' => $purchaseOrder->id]), ['reason' => 'Too late'])
            ->assertSessionHasErrors('purchase_order');

        $this->assertSame(PurchaseOrder::STATUS_COMPLETED, $purchaseOrder->fresh()->status);
    }

    public function test_unreceiving_a_line_reverts_the_status(): void
    {
        $user = $this->admin();
        $quoted = $this->quotedQuotation($user);
        $quotation = $quoted['quotation'];

        $purchaseOrder = PurchaseOrder::create([
            'quotation_id' => $quotation->id,
            'supplier_id' => $quoted['supplier']->id,
            'created_by' => $user->id,
            'po_number' => 'PO-'.now()->year.'-0002',
            'status' => PurchaseOrder::STATUS_SENT,
            'issued_on' => today(),
        ]);

        $quotationItem = $quotation->items->first();

        $line = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'quotation_item_id' => $quotationItem->id,
            'item_id' => $quotationItem->preplistItem->item_id,
            'qty' => 10,
            'unit_price' => 305.00,
        ]);

        $this->actingAs($user)
            ->post(route('purchase-order.receive', ['purchaseOrder' => $purchaseOrder->id, 'purchaseOrderItem' => $line->id]));
        $this->assertSame(PurchaseOrder::STATUS_COMPLETED, $purchaseOrder->fresh()->status);

        $this->actingAs($user)
            ->post(route('purchase-order.receive', ['purchaseOrder' => $purchaseOrder->id, 'purchaseOrderItem' => $line->id]));

        $this->assertSame(PurchaseOrder::STATUS_SENT, $purchaseOrder->fresh()->status);
        $this->assertDatabaseCount('purchase_order_receipts', 0);
    }

    public function test_cancelled_order_cannot_be_sent_or_received(): void
    {
        $user = $this->admin();
        $quoted = $this->quotedQuotation($user);
        $quotation = $quoted['quotation'];

        $purchaseOrder = PurchaseOrder::create([
            'quotation_id' => $quotation->id,
            'supplier_id' => $quoted['supplier']->id,
            'created_by' => $user->id,
            'po_number' => 'PO-'.now()->year.'-0003',
            'status' => PurchaseOrder::STATUS_DRAFT,
        ]);

        $this->actingAs($user)
            ->post(route('purchase-order.cancel', ['purchaseOrder' => $purchaseOrder->id]), ['reason' => 'Changed supplier'])
            ->assertSessionHas('status');

        $this->assertSame(PurchaseOrder::STATUS_CANCELLED, $purchaseOrder->fresh()->status);

        $this->actingAs($user)
            ->post(route('purchase-order.send', ['purchaseOrder' => $purchaseOrder->id]))
            ->assertSessionHasErrors('purchase_order');

        $quotationItem = $quotation->items->first();

        $line = PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'quotation_item_id' => $quotationItem->id,
            'item_id' => $quotationItem->preplistItem->item_id,
            'qty' => 10,
            'unit_price' => 305.00,
        ]);

        $this->actingAs($user)
            ->post(route('purchase-order.receive', ['purchaseOrder' => $purchaseOrder->id, 'purchaseOrderItem' => $line->id]))
            ->assertSessionHasErrors('purchase_order');

        $this->assertDatabaseCount('purchase_order_receipts', 0);
    }

    public function test_administrator_can_delete_a_draft_purchase_order_and_its_rows(): void
    {
        $admin = $this->admin();
        $purchaseOrder = $this->makePurchaseOrder($admin, PurchaseOrder::STATUS_DRAFT);

        $this->actingAs($admin)
            ->delete(route('purchase-order.destroy', ['purchaseOrder' => $purchaseOrder->id]))
            ->assertRedirect(route('purchase-order'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('purchase_orders', ['id' => $purchaseOrder->id]);
        $this->assertDatabaseCount('purchase_order_items', 0);
    }

    public function test_administrator_can_delete_a_cancelled_purchase_order(): void
    {
        $admin = $this->admin();
        $purchaseOrder = $this->makePurchaseOrder($admin, PurchaseOrder::STATUS_CANCELLED);

        $this->actingAs($admin)
            ->delete(route('purchase-order.destroy', ['purchaseOrder' => $purchaseOrder->id]))
            ->assertRedirect(route('purchase-order'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('purchase_orders', ['id' => $purchaseOrder->id]);
    }

    public function test_administrator_cannot_delete_a_sent_purchase_order(): void
    {
        $admin = $this->admin();
        $purchaseOrder = $this->makePurchaseOrder($admin, PurchaseOrder::STATUS_SENT);

        $this->actingAs($admin)
            ->delete(route('purchase-order.destroy', ['purchaseOrder' => $purchaseOrder->id]))
            ->assertRedirect(route('purchase-order'))
            ->assertSessionHasErrors('purchase_order');

        $this->assertDatabaseHas('purchase_orders', ['id' => $purchaseOrder->id]);
    }

    public function test_administrator_cannot_delete_a_completed_purchase_order(): void
    {
        $admin = $this->admin();
        $purchaseOrder = $this->makePurchaseOrder($admin, PurchaseOrder::STATUS_COMPLETED);

        $this->actingAs($admin)
            ->delete(route('purchase-order.destroy', ['purchaseOrder' => $purchaseOrder->id]))
            ->assertRedirect(route('purchase-order'))
            ->assertSessionHasErrors('purchase_order');

        $this->assertDatabaseHas('purchase_orders', ['id' => $purchaseOrder->id]);
    }

    public function test_a_viewer_cannot_delete_a_draft_purchase_order(): void
    {
        $viewer = $this->viewer();
        $purchaseOrder = $this->makePurchaseOrder($viewer, PurchaseOrder::STATUS_DRAFT);

        $this->actingAs($viewer)
            ->delete(route('purchase-order.destroy', ['purchaseOrder' => $purchaseOrder->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('purchase_orders', ['id' => $purchaseOrder->id]);
    }

    public function test_a_procurement_officer_cannot_delete_a_draft_purchase_order(): void
    {
        $officer = $this->procurementOfficer();
        $purchaseOrder = $this->makePurchaseOrder($officer, PurchaseOrder::STATUS_DRAFT);

        $this->actingAs($officer)
            ->delete(route('purchase-order.destroy', ['purchaseOrder' => $purchaseOrder->id]))
            ->assertForbidden();

        $this->assertDatabaseHas('purchase_orders', ['id' => $purchaseOrder->id]);
    }

    public function test_delete_action_is_only_shown_to_administrators_for_draft_or_cancelled_orders(): void
    {
        $admin = $this->admin();
        $draft = $this->makePurchaseOrder($admin, PurchaseOrder::STATUS_DRAFT);

        $this->actingAs($admin)
            ->get(route('purchase-order.show', ['purchaseOrder' => $draft->id]))
            ->assertOk()
            ->assertSee('id="poDeleteBtn"', false)
            ->assertSee('Delete order');

        $this->actingAs($this->viewer())
            ->get(route('purchase-order.show', ['purchaseOrder' => $draft->id]))
            ->assertOk()
            ->assertDontSee('id="poDeleteBtn"', false);

        $sent = $this->makePurchaseOrder($admin, PurchaseOrder::STATUS_SENT);

        $this->actingAs($admin)
            ->get(route('purchase-order.show', ['purchaseOrder' => $sent->id]))
            ->assertOk()
            ->assertDontSee('id="poDeleteBtn"', false);

        // The destroy endpoint URL is identical to the GET show route, so the
        // index only carries the delete button when the order is deletable.
        $content = $this->actingAs($admin)
            ->get(route('purchase-order'))
            ->assertOk()
            ->getContent();

        // The delete endpoint URL matches the show route, so count the
        // destructive forms instead: exactly the draft one should render.
        $draftForm = 'action="'.route('purchase-order.destroy', ['purchaseOrder' => $draft->id]).'"';
        $sentForm = 'action="'.route('purchase-order.destroy', ['purchaseOrder' => $sent->id]).'"';

        $this->assertSame(1, substr_count($content, $draftForm));
        $this->assertSame(0, substr_count($content, $sentForm));
    }

    public function test_unknown_quotation_and_purchase_order_return_not_found(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get('purchase-order/create/99999')
            ->assertNotFound();

        $this->actingAs($user)
            ->get('purchase-order/99999')
            ->assertNotFound();
    }
}
