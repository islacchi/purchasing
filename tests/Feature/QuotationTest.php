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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QuotationTest extends TestCase
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
     * POST to the preplist Generate File endpoint and return the workbook as-is
     * (identical to what a supplier would receive).
     */
    private function generatedWorkbook(User $user, Preplist $preplist, Supplier $supplier, array $itemIds): string
    {
        $response = $this->actingAs($user)->postJson(
            route('preplist.generate-rfq.excel', ['preplist' => $preplist->id]),
            [
                'files' => [
                    ['supplier' => $supplier->name, 'items' => $itemIds],
                ],
            ],
        );
        $response->assertOk();

        return $response->getContent();
    }

    /**
     * Patch the sheet XML inside an .xlsx workbook and return the new binary.
     *
     * @param  callable(string): string  $patch
     */
    private function patchWorksheet(string $binary, callable $patch): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'rfq_patched_');
        file_put_contents($temporaryPath, $binary);

        $zip = new \ZipArchive;
        $zip->open($temporaryPath);

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sheet = $patch($sheet);

        $zip->deleteName('xl/worksheets/sheet1.xml');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();

        $contents = file_get_contents($temporaryPath);
        unlink($temporaryPath);

        return $contents;
    }

    /**
     * Patch the "Unit Price" cells inside the workbook's sheet XML.
     *
     * @param  array<int, string|int|float>  $prices  Row number => unit price
     */
    private function fillUnitPrices(string $binary, array $prices): string
    {
        return $this->patchWorksheet($binary, function (string $sheet) use ($prices) {
            foreach ($prices as $row => $price) {
                $sheet = str_replace(
                    '<c r="F'.$row.'" s="4"/>',
                    '<c r="F'.$row.'" s="4"><v>'.$price.'</v></c>',
                    $sheet,
                );
            }

            return $sheet;
        });
    }

    /**
     * Patch the supplier-entered Brand (E) and Expiry (G) cells.
     */
    private function fillItemDetails(string $binary, int $row, ?string $brand = null, ?string $expiry = null): string
    {
        return $this->patchWorksheet($binary, function (string $sheet) use ($row, $brand, $expiry) {
            foreach (['E' => $brand, 'G' => $expiry] as $column => $value) {
                if ($value === null) {
                    continue;
                }

                $sheet = str_replace(
                    '<c r="'.$column.$row.'" s="4"/>',
                    '<c r="'.$column.$row.'" s="4" t="inlineStr"><is><t>'.$value.'</t></is></c>',
                    $sheet,
                );
            }

            return $sheet;
        });
    }

    private function replaceInSheet(string $binary, string $search, string $replace): string
    {
        return $this->patchWorksheet(
            $binary,
            fn (string $sheet) => str_replace($search, $replace, $sheet),
        );
    }

    private function upload(User $user, Quotation $quotation, string $binary, string $name = 'returned.xlsx')
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'rfq_filled_').'.xlsx';
        file_put_contents($temporaryPath, $binary);

        $file = new UploadedFile(
            $temporaryPath,
            $name,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        return $this->actingAs($user)
            ->post(route('quotation.store-document', ['quotation' => $quotation->id]), ['document' => $file]);
    }

    public function test_quotation_index_lists_real_database_records(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 9],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);

        $this->actingAs($user)
            ->get(route('quotation'))
            ->assertOk()
            ->assertSee('July Region VII batch')
            ->assertSee($preplist->reference_no)
            ->assertSee($supplier->name)
            ->assertSee('0 / 1')
            ->assertSee('Awaiting quote')
            ->assertSee(route('quotation.show', ['quotation' => $quotation->id]), false);
    }

    public function test_quotation_index_shows_empty_state_when_none_exist(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get(route('quotation'))
            ->assertOk()
            ->assertSee('No quotations yet');
    }

    public function test_quotation_index_groups_quotations_by_prep_list(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplierOne = Supplier::create(['name' => 'Triple Tact']);
        $supplierTwo = Supplier::create(['name' => 'Acme Supplies']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 9],
        ]);

        $this->createQuotation($user, $preplist, $supplierOne);
        $this->createQuotation($user, $preplist, $supplierTwo);

        $this->actingAs($user)
            ->get(route('quotation'))
            ->assertOk()
            ->assertSee($preplist->name)
            ->assertSee($preplist->reference_no)
            ->assertSee($supplierOne->name)
            ->assertSee($supplierTwo->name)
            ->assertSee('Awaiting quote');
    }

    public function test_quotation_module_paginates_prep_list_groups(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        for ($i = 1; $i <= 12; $i++) {
            $preplist = $this->compile($user, [
                'name' => sprintf('Group fixture %02d', $i),
                'sources' => [$projectItem->id],
                'shelf_life' => [$projectItem->id => 9],
            ]);
            $this->createQuotation($user, $preplist, $supplier, [
                'rfq_number' => sprintf('RFQ-2026-%04d', $i),
            ]);
        }

        $pageOne = $this->actingAs($user)->get(route('quotation'))->assertOk();
        $groups = $pageOne->viewData('groups');
        $this->assertSame(5, $groups->count());
        $this->assertSame(12, $groups->total());
        $this->assertSame(1, $groups->currentPage());
        $this->assertNotNull($groups->nextPageUrl());

        $pageOne->assertSee('aria-label="Pagination Navigation"', false)
            ->assertSee('Group fixture 12')
            ->assertSee('Group fixture 08')
            ->assertDontSee('Group fixture 07');

        $pageThree = $this->actingAs($user)->get(route('quotation', ['page' => 3]))->assertOk();
        $this->assertSame(2, $pageThree->viewData('groups')->count());
        $pageThree->assertSee('Group fixture 02')
            ->assertSee('Group fixture 01')
            ->assertDontSee('Group fixture 03');
    }

    public function test_quotation_document_upload_rejects_a_stale_fingerprint(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Stale Fingerprint Supplies']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 9],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'stale_').'.xlsx';
        file_put_contents($temporaryPath, 'anything at all');

        $file = new UploadedFile(
            $temporaryPath,
            'returned.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $response = $this->actingAs($user)
            ->post(route('quotation.store-document', ['quotation' => $quotation->id]), [
                'expected_document_fingerprint' => 'not-the-current-fingerprint',
                'document' => $file,
            ]);

        $response->assertSessionHasErrors('document');

        @unlink($temporaryPath);
    }

    public function test_quotation_show_displays_pending_items_and_document(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Amoxicillin 500mg']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 30]);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 12],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);

        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee($supplier->name)
            ->assertSee('July Region VII batch')
            ->assertSee('Amoxicillin 500mg')
            ->assertSee('30 Box requested')
            ->assertSee('0 / 1')
            ->assertSee('Awaiting quote')
            ->assertSee('Pending')
            ->assertSee('quotation-'.$supplier->id.'.xlsx')
            ->assertSee(route('quotation.download', ['quotation' => $quotation->id]), false)
            ->assertSee($project->title)
            ->assertSee($project->reference_no);
    }

    public function test_viewer_sees_the_submit_quotation_button_disabled(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Amoxicillin 500mg']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 30]);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 12],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);

        $viewer = User::where('name', 'user2')->firstOrFail();

        $this->actingAs($viewer)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('Your role does not have access to upload files.')
            ->assertDontSee('for="supplierQuotationFile"', false)
            ->assertDontSee('supplierQuotationForm', false);

        // An administrator still sees the real upload label.
        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('for="supplierQuotationFile"', false)
            ->assertSee('supplierQuotationForm', false);
    }

    public function test_quotation_show_displays_quoted_items_and_flags_the_expiry(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Amoxicillin 500mg']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 40]);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 12],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::firstOrFail();

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 12,
            'unit_price' => 305.00,
            'brand' => 'MediCore',
            'expiry' => '2028-01-01',
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('1 / 1')
            ->assertSee('Quoted')
            ->assertSee('₱ 305.00 / Box')
            ->assertSee('₱ 12,200.00 subtotal')
            ->assertSee('₱ 12,200.00')
            ->assertSee('data-expiry-status="good"', false)
            ->assertDontSee('data-expiry-status="short"', false)
            ->assertDontSee('data-expiry-status="close"', false)
            ->assertSee('Remove quotation')
            ->assertSee(route('quotation.destroy-document', ['quotation' => $quotation->id]), false)
            ->assertSee(route('purchase-order.create', ['rfq' => $quotation->id]), false);
    }

    public function test_quotation_show_flags_an_expiry_that_does_not_cover_the_delivery(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Amoxicillin 500mg']);
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::firstOrFail();

        // The project's delivery is due 2026-08-02, so this expiry is too short.
        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 305.00,
            'brand' => 'MediCore',
            'expiry' => '2026-07-20',
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('data-expiry-status="short"', false)
            ->assertSee('text-red-600', false)
            ->assertSee('Jul 20, 2026');
    }

    public function test_quotation_show_rates_expiry_per_project(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();

        // Both projects draw from the same item, but each has its own delivery
        // period and therefore its own minimum expiry date.
        $projectOne = $this->project($user, $entity, [
            'title' => 'Project One',
            'reference_no' => 'RF-2026-0101',
            'date_awarded' => '2026-11-13',
            'delivery_days' => 45,
            'delivery_due_date' => null,
        ]);
        $projectTwo = $this->project($user, $entity, [
            'title' => 'Project Two',
            'reference_no' => 'RF-2026-0102',
            'date_awarded' => '2026-11-13',
            'delivery_days' => 30,
            'delivery_due_date' => null,
        ]);

        $item = $this->item(['name' => 'Amoxicillin 500mg']);
        $projectItemOne = $this->projectItem($projectOne, $item, ['quantity' => 30]);
        $projectItemTwo = $this->projectItem($projectTwo, $item, ['quantity' => 20]);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'November Region VII batch',
            'sources' => [$projectItemOne->id, $projectItemTwo->id],
            'shelf_life' => [$projectItemOne->id => 1, $projectItemTwo->id => 1],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::firstOrFail();

        $this->assertSame(1, $preplistItem->min_shelf_life_months);

        // Project One: due 2026-12-28 + 1 month = 2027-01-28 — Jan 17 is short.
        // Project Two: due 2026-12-13 + 1 month = 2027-01-13 — Jan 17 is close.
        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 1,
            'unit_price' => 305.00,
            'brand' => 'MediCore',
            'expiry' => '2027-01-17',
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('Project One')
            ->assertSee('Project Two')
            ->assertSee('data-expiry-status="short"', false)
            ->assertSee('data-expiry-status="close"', false)
            ->assertSee('Jan 28, 2027')
            ->assertSee('Jan 13, 2027')
            ->assertSee('text-red-600', false)
            ->assertSee('text-yellow-600', false);
    }

    public function test_quotation_show_treats_month_only_expiry_as_the_end_of_its_month(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity, [
            'date_awarded' => '2026-12-01',
            'delivery_days' => 45,
            'delivery_due_date' => null,
        ]);
        $item = $this->item(['name' => 'Amoxicillin 500mg']);
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::firstOrFail();

        // Delivery is due 2027-01-15; Dec 31, 2026 is too early.
        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 305.00,
            'brand' => 'MediCore',
            'expiry' => '2026-12-31',
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('Dec 31, 2026')
            ->assertSee('data-expiry-status="short"', false);
    }

    public function test_quotation_document_can_be_downloaded(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);

        $response = $this->actingAs($user)
            ->get(route('quotation.download', ['quotation' => $quotation->id]))
            ->assertOk();

        $this->assertStringContainsString(
            'attachment',
            $response->headers->get('Content-Disposition'),
        );
        $this->assertStringContainsString(
            'quotation-'.$supplier->id.'.xlsx',
            $response->headers->get('Content-Disposition'),
        );

        $this->assertSame(
            'dummy-content-'.$supplier->id,
            Storage::disk('local')->get('quotations/test-'.$supplier->id.'.xlsx'),
        );
    }

    public function test_quotation_can_be_deleted(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);

        $this->actingAs($user)
            ->delete(route('quotation.destroy', ['quotation' => $quotation->id]))
            ->assertRedirect(route('quotation'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('quotations', ['id' => $quotation->id]);
        Storage::disk('local')->assertMissing('quotations/test-'.$supplier->id.'.xlsx');
    }

    public function test_quoted_quotation_cannot_be_deleted(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::firstOrFail();

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 305.00,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('quotation.destroy', ['quotation' => $quotation->id]))
            ->assertRedirect(route('quotation'))
            ->assertSessionHasErrors('quotation');

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);
        Storage::disk('local')->assertExists('quotations/test-'.$supplier->id.'.xlsx');
    }

    public function test_quotation_document_can_be_removed_without_deleting_the_entry(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);
        $preplistItem = PreplistItem::firstOrFail();

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'min_remaining_months' => 0,
            'unit_price' => 305.00,
            'quoted_at' => now(),
        ]);

        $this->actingAs($user)
            ->delete(route('quotation.destroy-document', ['quotation' => $quotation->id]))
            ->assertRedirect(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertSessionHas('status');

        $quotation->refresh();

        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);
        $this->assertDatabaseMissing('quotation_items', ['quotation_id' => $quotation->id]);
        $this->assertNull($quotation->document_path);
        $this->assertNull($quotation->document_name);
        Storage::disk('local')->assertMissing('quotations/test-'.$supplier->id.'.xlsx');

        // The entry now waits for a fresh supplier response.
        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('0 / 1')
            ->assertSee('Awaiting quote')
            ->assertSee('No supplier quotation submitted yet');
    }

    public function test_quotation_document_cannot_be_removed_when_a_purchase_order_exists(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

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
            'unit_price' => 305.00,
            'quoted_at' => now(),
        ]);

        // The FK that blocks the delete comes from a purchase order whose
        // lines reference these quoted prices.
        $purchaseOrder = PurchaseOrder::create([
            'quotation_id' => $quotation->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'po_number' => 'PO-'.now()->year.'-9999',
            'status' => PurchaseOrder::STATUS_DRAFT,
            'delivery_fee' => 0,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $purchaseOrder->id,
            'quotation_item_id' => $quotationItem->id,
            'item_id' => $item->id,
            'qty' => 5,
            'unit_price' => 305.00,
            'min_remaining_months' => 0,
        ]);

        // Removing the document is refused with a reason, not a 500.
        $this->actingAs($user)
            ->delete(route('quotation.destroy-document', ['quotation' => $quotation->id]))
            ->assertRedirect(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertSessionHas('documentRemoveBlocked');

        $quotation->refresh();

        $this->assertDatabaseHas('quotation_items', ['id' => $quotationItem->id]);
        $this->assertNotNull($quotation->document_path);
        Storage::disk('local')->assertExists('quotations/test-'.$supplier->id.'.xlsx');

        // The show page renders the blocked explanation instead of the
        // confirmation dialog, and mentions the blocking PO.
        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('This quotation cannot be removed')
            ->assertSee($purchaseOrder->po_number)
            ->assertDontSee('id="quotationRemoveConfirm"', false);
    }

    public function test_viewer_sees_the_access_restricted_modal_before_the_confirmation(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);

        $viewer = User::where('name', 'user2')->firstOrFail();

        // A view-only user sees the access-restricted dialog instead of the
        // destructive confirmation — not even the hidden upload form.
        $this->actingAs($viewer)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('Access restricted')
            ->assertSee('Your role does not have access to that module.')
            ->assertDontSee('id="quotationRemoveConfirm"', false)
            ->assertDontSee('supplierQuotationForm', false);
    }

    public function test_quotation_upload_parses_unit_prices_and_marks_items_quoted(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'AlphaItemAlpha']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 30]);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 9],
        ]);

        $preplistItem = PreplistItem::firstOrFail();

        $binary = $this->generatedWorkbook($user, $preplist, $supplier, [$preplistItem->id]);
        $binary = $this->fillUnitPrices($binary, [18 => '305.50']);
        $binary = $this->fillItemDetails($binary, 18, 'MediCore', 'December 2027');

        // Generating the RFQ creates the quotation record to upload against.
        $quotation = Quotation::query()->firstOrFail();

        $this->upload($user, $quotation, $binary)
            ->assertRedirect(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'unit_price' => 305.50,
            'min_remaining_months' => 9,
            'brand' => 'MediCore',
            'expiry' => '2027-12-31',
        ]);

        $this->assertSame('305.50', QuotationItem::firstOrFail()->unit_price);

        $quotation->refresh();

        $this->assertSame('quotations/'.$quotation->rfq_number.'.xlsx', $quotation->document_path);
        $this->assertSame('returned.xlsx', $quotation->document_name);
        Storage::disk('local')->assertExists($quotation->document_path);

        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('1 / 1')
            ->assertSee('Quoted')
            ->assertSee('MediCore')
            ->assertSee('Dec 31, 2027')
            ->assertSee('₱ 305.50 / Box');
    }

    public function test_quotation_upload_rejects_non_positive_unit_prices(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'AlphaItemAlpha']);
        $projectItem = $this->projectItem($project, $item, ['quantity' => 30]);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 9],
        ]);

        $preplistItem = PreplistItem::firstOrFail();

        $binary = $this->generatedWorkbook($user, $preplist, $supplier, [$preplistItem->id]);
        $quotation = Quotation::query()->firstOrFail();

        foreach (['0', '-12.50'] as $price) {
            $patched = $this->fillUnitPrices($binary, [18 => $price]);

            $this->upload($user, $quotation, $patched)
                ->assertSessionHasErrors([
                    'document' => 'The unit price for "AlphaItemAlpha" must be greater than zero.',
                ]);
        }

        $this->assertDatabaseCount('quotation_items', 0);
        $this->assertNull($quotation->fresh()->document_path);
    }

    public function test_quotation_upload_normalizes_month_only_and_keeps_na(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $firstItem = $this->item(['name' => 'AlphaItemAlpha']);
        $secondItem = $this->item(['name' => 'BetaItemBeta', 'category' => 'supplies and equipments']);

        $firstSource = $this->projectItem($project, $firstItem);
        $secondSource = $this->projectItem($project, $secondItem);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$firstSource->id, $secondSource->id],
            'shelf_life' => [$firstSource->id => 9, $secondSource->id => 12],
        ]);

        $preplistItems = PreplistItem::query()->orderBy('item_id')->get()->keyBy('item_id');
        $firstPreplistItem = $preplistItems[$firstItem->id];
        $secondPreplistItem = $preplistItems[$secondItem->id];

        $binary = $this->generatedWorkbook(
            $user,
            $preplist,
            $supplier,
            [$firstPreplistItem->id, $secondPreplistItem->id],
        );
        $binary = $this->fillUnitPrices($binary, [18 => 250, 19 => 305]);
        $binary = $this->fillItemDetails($binary, 18, 'MediCore', 'December 2027');
        $binary = $this->fillItemDetails($binary, 19, 'DashCore', 'N/A');

        $quotation = Quotation::query()->firstOrFail();

        $this->upload($user, $quotation, $binary)
            ->assertRedirect(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $firstPreplistItem->id,
            'brand' => 'MediCore',
            'expiry' => '2027-12-31',
        ]);

        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $secondPreplistItem->id,
            'brand' => 'DashCore',
            'expiry' => null,
        ]);
    }

    public function test_quotation_upload_rejects_an_ambiguous_expiry_with_row_and_description(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'AlphaItemAlpha']);
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $preplistItem = PreplistItem::firstOrFail();

        $binary = $this->generatedWorkbook($user, $preplist, $supplier, [$preplistItem->id]);
        $binary = $this->fillUnitPrices($binary, [18 => '305.50']);
        $binary = $this->fillItemDetails($binary, 18, 'MediCore', '03/04/2027');

        $quotation = Quotation::query()->firstOrFail();

        $this->upload($user, $quotation, $binary)
            ->assertRedirect()
            ->assertSessionHasErrors('document');

        $this->assertDatabaseMissing('quotation_items', ['quotation_id' => $quotation->id]);
        $this->assertNull($quotation->fresh()->document_path);

        // The validation message names the row and the item description.
        $errors = session()->get('errors');
        $this->assertStringContainsString('Row 18', $errors->first('document'));
        $this->assertStringContainsString('AlphaItemAlpha', $errors->first('document'));
    }

    public function test_quotation_upload_keeps_unquoted_items_pending(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $firstItem = $this->item(['name' => 'AlphaItemAlpha']);
        $secondItem = $this->item(['name' => 'BetaItemBeta', 'category' => 'supplies and equipments']);

        $firstSource = $this->projectItem($project, $firstItem);
        $secondSource = $this->projectItem($project, $secondItem);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$firstSource->id, $secondSource->id],
            'shelf_life' => [$firstSource->id => 6, $secondSource->id => 12],
        ]);

        $preplistItems = PreplistItem::query()->orderBy('item_id')->get()->keyBy('item_id');
        $firstPreplistItem = $preplistItems[$firstItem->id];
        $secondPreplistItem = $preplistItems[$secondItem->id];

        $binary = $this->generatedWorkbook(
            $user,
            $preplist,
            $supplier,
            [$firstPreplistItem->id, $secondPreplistItem->id],
        );
        $binary = $this->fillUnitPrices($binary, [18 => 250]);

        $quotation = Quotation::query()->firstOrFail();

        $this->upload($user, $quotation, $binary)
            ->assertSessionHas('status');

        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $firstPreplistItem->id,
            'unit_price' => 250.00,
        ]);
        $this->assertDatabaseMissing('quotation_items', [
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $secondPreplistItem->id,
        ]);

        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('1 / 2');
    }

    public function test_quotation_upload_replaces_existing_prices(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 9],
        ]);

        $preplistItem = PreplistItem::firstOrFail();

        $quotation = Quotation::query()->create([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-'.now()->year.'-0001',
            'generated_at' => now(),
        ]);

        $first = $this->generatedWorkbook($user, $preplist, $supplier, [$preplistItem->id]);
        $first = $this->fillUnitPrices($first, [18 => 305]);
        $first = $this->fillItemDetails($first, 18, 'OldBrand', '2027-12-31');

        $quotation = Quotation::query()->firstOrFail();

        $this->upload($user, $quotation, $first)->assertSessionHas('status');

        $second = $this->generatedWorkbook($user, $preplist, $supplier, [$preplistItem->id]);
        $second = $this->fillUnitPrices($second, [18 => 250]);
        $second = $this->fillItemDetails($second, 18, 'NewBrand', '2028-06-30');

        $this->upload($user, $quotation->fresh(), $second, 'updated.xlsx')->assertSessionHas('status');

        $this->assertDatabaseCount('quotation_items', 1);
        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'unit_price' => 250.00,
            'brand' => 'NewBrand',
            'expiry' => '2028-06-30',
        ]);
    }

    public function test_quotation_upload_rejects_a_random_xlsx(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $preplistItem = PreplistItem::firstOrFail();

        $binary = $this->generatedWorkbook($user, $preplist, $supplier, [$preplistItem->id]);
        $binary = $this->replaceInSheet($binary, 'Item Description', 'Not The Template');
        $quotation = Quotation::query()->firstOrFail();

        $this->upload($user, $quotation, $binary)->assertSessionHasErrors('document');

        $this->assertDatabaseMissing('quotation_items', ['quotation_id' => $quotation->id]);
        $this->assertNull($quotation->fresh()->document_path);
    }

    public function test_quotation_upload_rejects_mismatched_item_names(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'CorrectItemName']);
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $preplistItem = PreplistItem::firstOrFail();

        $binary = $this->generatedWorkbook($user, $preplist, $supplier, [$preplistItem->id]);
        $binary = $this->replaceInSheet($binary, 'CorrectItemName', 'CompletelyDifferentItem');
        $binary = $this->fillUnitPrices($binary, [18 => 305]);
        $quotation = Quotation::query()->firstOrFail();

        $this->upload($user, $quotation, $binary)->assertSessionHasErrors('document');

        $this->assertDatabaseMissing('quotation_items', ['quotation_id' => $quotation->id]);
        $this->assertNull($quotation->fresh()->document_path);
    }

    public function test_quotation_pages_survive_a_missing_prep_list(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Amoxicillin 500mg']);
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $quotation = $this->createQuotation($user, $preplist, $supplier);

        $preplist->delete();

        $this->actingAs($user)
            ->get(route('quotation.show', ['quotation' => $quotation->id]))
            ->assertOk()
            ->assertSee('Prep list removed')
            ->assertSee('prep list has been deleted');

        $this->actingAs($user)
            ->get(route('quotation'))
            ->assertOk()
            ->assertSee('Prep list removed');
    }

    public function test_quotation_upload_is_rejected_when_the_prep_list_is_gone(): void
    {
        Storage::fake('local');

        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item(['name' => 'Amoxicillin 500mg']);
        $projectItem = $this->projectItem($project, $item);
        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $preplistItem = PreplistItem::firstOrFail();
        $binary = $this->generatedWorkbook($user, $preplist, $supplier, [$preplistItem->id]);

        $quotation = Quotation::query()->firstOrFail();

        $preplist->delete();

        $this->upload($user, $quotation, $binary)
            ->assertSessionHasErrors('document');
    }

    public function test_unknown_quotation_returns_not_found(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get('quotation/99999')
            ->assertNotFound();
    }
}
