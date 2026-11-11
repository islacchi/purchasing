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
        return Project::create(array_merge([
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Purchase of pharma supplies',
            'reference_no' => 'RF-2026-0042',
            'mode_of_procurement' => 'small_value',
            'amount_awarded' => 482000.00,
            'date_awarded' => '2026-07-03',
            'delivery_days' => 30,
            'notes' => 'Quarterly procurement of pharmaceutical supplies.',
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

    public function test_quotation_show_displays_quoted_items_and_short_dated_warning(): void
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
            'offered_shelf_life_months' => 6,
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
            ->assertSee('1 item below the required shelf life')
            ->assertSee('Short-dated')
            ->assertSee('Offered: 6 months')
            ->assertSee('Remove quotation')
            ->assertSee(route('quotation.destroy-document', ['quotation' => $quotation->id]), false)
            ->assertSee(route('purchase-order.create', ['rfq' => $quotation->id]), false);
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
            ->assertSee('₱ 305.50 / Box');
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

        $quotation = Quotation::query()->firstOrFail();

        $this->upload($user, $quotation, $first)->assertSessionHas('status');

        $second = $this->generatedWorkbook($user, $preplist, $supplier, [$preplistItem->id]);
        $second = $this->fillUnitPrices($second, [18 => 250]);

        $this->upload($user, $quotation->fresh(), $second, 'updated.xlsx')->assertSessionHas('status');

        $this->assertDatabaseCount('quotation_items', 1);
        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'unit_price' => 250.00,
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

    public function test_unknown_quotation_returns_not_found(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get('quotation/99999')
            ->assertNotFound();
    }
}
