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

class PriceIndexTest extends TestCase
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
        return Project::create(array_merge([
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Pharma supplies Q3',
            'reference_no' => 'RF-2026-'.(Project::query()->count() + 1),
            'mode_of_procurement' => 'small_value',
        ], $overrides));
    }

    private function item(array $overrides = []): Item
    {
        $categoryName = $overrides['category'] ?? 'drugs and medicines';
        unset($overrides['category']);

        $category = ItemCategory::firstOrCreate(['name' => $categoryName]);

        return Item::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Amoxicillin 500mg',
            'unit' => 'box of 100',
            'default_min_shelf_life_months' => 0,
        ], $overrides));
    }

    private function projectItem(Project $project, Item $item, int $quantity, array $overrides = []): ProjectItem
    {
        return ProjectItem::create(array_merge([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => $quantity,
            'in_stock' => 0,
            'unit_cost' => 300.00,
            'awarded_unit_price' => 300.00,
            'fulfillment_status' => 'pending',
        ], $overrides));
    }

    private function preplist(User $user, array $overrides = []): Preplist
    {
        $sequence = Preplist::query()->count() + 1;

        return Preplist::create(array_merge([
            'created_by' => $user->id,
            'name' => 'Preplist '.$sequence,
            'reference_no' => 'PL-2026-'.$sequence,
        ], $overrides));
    }

    private function preplistItem(Preplist $preplist, Item $item): PreplistItem
    {
        return PreplistItem::create([
            'preplist_id' => $preplist->id,
            'item_id' => $item->id,
            'min_shelf_life_months' => 0,
        ]);
    }

    private function supplier(string $name): Supplier
    {
        return Supplier::create(['name' => $name]);
    }

    private function quotation(User $user, Preplist $preplist, Supplier $supplier, array $overrides = []): Quotation
    {
        return Quotation::create(array_merge([
            'preplist_id' => $preplist->id,
            'supplier_id' => $supplier->id,
            'created_by' => $user->id,
            'rfq_number' => 'RFQ-2026-'.str_pad((string) (Quotation::query()->count() + 1), 4, '0', STR_PAD_LEFT),
            'generated_at' => now(),
        ], $overrides));
    }

    private function quote(
        Quotation $quotation,
        PreplistItem $preplistItem,
        ?float $unitPrice,
        string $quotedAt,
        array $overrides = [],
    ): QuotationItem {
        return QuotationItem::create(array_merge([
            'quotation_id' => $quotation->id,
            'preplist_item_id' => $preplistItem->id,
            'unit_price' => $unitPrice,
            'quoted_at' => Carbon::parse($quotedAt),
        ], $overrides));
    }

    public function test_price_index_uses_latest_quote_per_supplier_and_computes_savings(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $item = $this->item();

        $projectA = $this->project($user, $entity, ['title' => 'Pharma supplies Q3']);
        $projectB = $this->project($user, $entity, ['title' => 'Laboratory reagents']);
        $this->projectItem($projectA, $item, 100);
        $this->projectItem($projectB, $item, 50);

        $preplist = $this->preplist($user);
        $preplistItem = $this->preplistItem($preplist, $item);

        $greenCore = $this->supplier('GreenCore Pharmaceuticals');
        $medSource = $this->supplier('MedSource Inc.');

        $greenCoreOld = $this->quotation($user, $preplist, $greenCore);
        $this->quote($greenCoreOld, $preplistItem, 320.00, '2026-01-10 08:00:00');

        $greenExpiry = now()->addYear()->toDateString();
        $greenCoreNew = $this->quotation($user, $preplist, $greenCore);
        $this->quote($greenCoreNew, $preplistItem, 300.00, '2026-02-10 08:00:00', [
            'brand' => 'MediCore',
            'expiry' => $greenExpiry,
        ]);

        $medExpiry = now()->addDays(60)->toDateString();
        $medSourceQuotation = $this->quotation($user, $preplist, $medSource);
        $this->quote($medSourceQuotation, $preplistItem, 360.00, '2026-02-05 08:00:00', [
            'brand' => 'PharmaCorp',
            'expiry' => $medExpiry,
        ]);

        $response = $this->actingAs($user)->get(route('priceindex'))->assertOk();

        $row = collect($response->viewData('items'))->firstWhere('id', $item->id);

        $this->assertNotNull($row);
        $this->assertSame(150, $row['requested']);
        $this->assertSame(['Laboratory reagents', 'Pharma supplies Q3'], $row['projects']);
        $this->assertEqualsCanonicalizing([$projectA->id, $projectB->id], $row['project_ids']);

        $projectOptions = $response->viewData('projectOptions');
        $this->assertSame('Pharma supplies Q3 ('.$projectA->reference_no.')', $projectOptions[$projectA->id]);
        $this->assertSame('Laboratory reagents ('.$projectB->reference_no.')', $projectOptions[$projectB->id]);

        $this->assertSame(2, $row['stats']['count']);
        $this->assertEqualsWithDelta(300.00, $row['stats']['best'], 0.001);
        $this->assertEqualsWithDelta(330.00, $row['stats']['avg'], 0.001);
        $this->assertEqualsWithDelta(20.0, $row['stats']['spread'], 0.001);
        $this->assertEqualsWithDelta(4500.00, $row['stats']['savings'], 0.001);
        $this->assertSame($greenCore->id, $row['stats']['best_supplier']);

        $this->assertCount(2, $row['quotes']);

        $greenQuote = collect($row['quotes'])->firstWhere('supplier', $greenCore->id);
        $this->assertEqualsWithDelta(300.00, $greenQuote['price'], 0.001);
        $this->assertSame('Feb 10, 2026', $greenQuote['quoted_at']);
        $this->assertSame('MediCore', $greenQuote['brand']);
        $this->assertSame(Carbon::parse($greenExpiry)->format('M j, Y'), $greenQuote['expiry']);
        $this->assertNull($greenQuote['expiry_state']);
        $this->assertArrayNotHasKey('coverage', $greenQuote);

        $medQuote = collect($row['quotes'])->firstWhere('supplier', $medSource->id);
        $this->assertEqualsWithDelta(360.00, $medQuote['price'], 0.001);
        $this->assertSame('PharmaCorp', $medQuote['brand']);
        $this->assertSame(Carbon::parse($medExpiry)->format('M j, Y'), $medQuote['expiry']);
        $this->assertNull($medQuote['expiry_state']);
        $this->assertArrayNotHasKey('coverage', $medQuote);

        // History keeps every priced line, including the superseded GreenCore quote.
        $this->assertCount(3, $row['history']);
        $this->assertCount(2, collect($row['history'])->where('supplier', $greenCore->id));
        $this->assertNotNull($row['sparkline']);

        $scorecard = $response->viewData('scorecard');
        $this->assertSame(1, $scorecard[$greenCore->id]['quotes']);
        $this->assertSame(1, $scorecard[$greenCore->id]['compared']);
        $this->assertSame(1, $scorecard[$greenCore->id]['wins']);
        $this->assertEqualsWithDelta(0.0, $scorecard[$greenCore->id]['premium_sum'], 0.001);

        $this->assertSame(1, $scorecard[$medSource->id]['quotes']);
        $this->assertSame(1, $scorecard[$medSource->id]['compared']);
        $this->assertSame(0, $scorecard[$medSource->id]['wins']);
        $this->assertEqualsWithDelta(20.0, $scorecard[$medSource->id]['premium_sum'], 0.001);

        $suppliers = $response->viewData('suppliers');
        $this->assertSame('GreenCore Pharmaceuticals', $suppliers[$greenCore->id]['name']);
        $this->assertNotSame($suppliers[$greenCore->id]['color'], $suppliers[$medSource->id]['color']);

        $response->assertSee('Live quotation data')
            ->assertDontSee('Dummy data')
            ->assertSee('id="pricePeriod"', false)
            ->assertSee('aria-label="Price trend period"', false)
            ->assertSee('Amoxicillin 500mg')
            ->assertSee('GreenCore Pharmaceuticals')
            ->assertSee('MedSource Inc.')
            ->assertSee('Brand / expiry')
            ->assertSee('current quotes across the catalogue')
            ->assertSee('Ties count as a win for every tied supplier.');
    }

    public function test_price_index_project_filter_uses_ids_for_duplicate_and_pipe_titles(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $item = $this->item(['name' => 'Filterable item', 'unit' => 'Box']);

        $first = $this->project($user, $entity, [
            'title' => 'Same title | with pipe',
            'reference_no' => 'RF-DUP-1',
        ]);
        $second = $this->project($user, $entity, [
            'title' => 'Same title | with pipe',
            'reference_no' => 'RF-DUP-2',
        ]);
        $this->projectItem($first, $item, 10);
        $this->projectItem($second, $item, 20);

        $response = $this->actingAs($user)->get(route('priceindex'))->assertOk();

        $row = collect($response->viewData('items'))->firstWhere('id', $item->id);
        $options = $response->viewData('projectOptions');

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $row['project_ids']);
        $this->assertSame(['Same title | with pipe'], $row['projects']);
        $this->assertCount(2, $options);
        $this->assertSame('Same title | with pipe (RF-DUP-1)', $options[$first->id]);
        $this->assertSame('Same title | with pipe (RF-DUP-2)', $options[$second->id]);

        $response->assertSee('data-project-ids="'.$first->id.','.$second->id.'"', false)
            ->assertDontSee('data-projects=', false)
            ->assertSee('Same title | with pipe (RF-DUP-1)');
    }

    public function test_price_index_ignores_zero_and_negative_unit_prices(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $item = $this->item(['name' => 'Insulin vials', 'unit' => 'box of 10']);

        $project = $this->project($user, $entity, ['title' => 'Pharma supplies Q3']);
        $this->projectItem($project, $item, 100);

        $preplist = $this->preplist($user);
        $preplistItem = $this->preplistItem($preplist, $item);

        $valid = $this->supplier('GreenCore Pharmaceuticals');
        $secondValid = $this->supplier('MedSource Inc.');
        $zero = $this->supplier('Zero Price Supplies');
        $negative = $this->supplier('Negative Price Supplies');

        $this->quote($this->quotation($user, $preplist, $valid), $preplistItem, 300.00, '2026-02-10 08:00:00');
        $this->quote($this->quotation($user, $preplist, $secondValid), $preplistItem, 350.00, '2026-02-11 08:00:00');
        $this->quote($this->quotation($user, $preplist, $zero), $preplistItem, 0.00, '2026-02-12 08:00:00');
        $this->quote($this->quotation($user, $preplist, $negative), $preplistItem, -50.00, '2026-02-13 08:00:00');

        $response = $this->actingAs($user)->get(route('priceindex'))->assertOk();

        $row = collect($response->viewData('items'))->firstWhere('id', $item->id);

        $this->assertNotNull($row);
        $this->assertSame(2, $row['stats']['count']);
        $this->assertEqualsWithDelta(300.00, $row['stats']['best'], 0.001);
        $this->assertEqualsWithDelta(325.00, $row['stats']['avg'], 0.001);
        $this->assertEqualsWithDelta(2500.00, $row['stats']['savings'], 0.001);
        $this->assertSame($valid->id, $row['stats']['best_supplier']);
        $this->assertCount(2, $row['quotes']);
        $this->assertCount(2, $row['history']);

        $suppliers = $response->viewData('suppliers');
        $this->assertArrayNotHasKey($zero->id, $suppliers);
        $this->assertArrayNotHasKey($negative->id, $suppliers);
    }

    public function test_price_index_flags_expired_and_soon_expiring_quotes(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $item = $this->item(['name' => 'Ceftriaxone 1g', 'unit' => 'vial']);

        $project = $this->project($user, $entity, ['title' => 'Pharma supplies Q3']);
        $this->projectItem($project, $item, 100);

        $preplist = $this->preplist($user);
        $preplistItem = $this->preplistItem($preplist, $item);

        $expired = $this->supplier('Expired Batch Supplies');
        $soon = $this->supplier('Near Expiry Supplies');
        $good = $this->supplier('Fresh Batch Supplies');

        $this->quote($this->quotation($user, $preplist, $expired), $preplistItem, 100.00, '2026-02-01 08:00:00', [
            'brand' => 'OldBatch',
            'expiry' => now()->subDay()->toDateString(),
        ]);
        $this->quote($this->quotation($user, $preplist, $soon), $preplistItem, 110.00, '2026-02-02 08:00:00', [
            'brand' => 'NearBatch',
            'expiry' => now()->addDays(10)->toDateString(),
        ]);
        $this->quote($this->quotation($user, $preplist, $good), $preplistItem, 120.00, '2026-02-03 08:00:00', [
            'brand' => 'FreshBatch',
            'expiry' => now()->addYear()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('priceindex'))->assertOk();
        $row = collect($response->viewData('items'))->firstWhere('id', $item->id);

        $expiredQuote = collect($row['quotes'])->firstWhere('supplier', $expired->id);
        $soonQuote = collect($row['quotes'])->firstWhere('supplier', $soon->id);
        $goodQuote = collect($row['quotes'])->firstWhere('supplier', $good->id);

        $this->assertSame('OldBatch', $expiredQuote['brand']);
        $this->assertSame('expired', $expiredQuote['expiry_state']);
        $this->assertSame('NearBatch', $soonQuote['brand']);
        $this->assertSame('soon', $soonQuote['expiry_state']);
        $this->assertSame('FreshBatch', $goodQuote['brand']);
        $this->assertNull($goodQuote['expiry_state']);
    }

    public function test_price_index_shows_requested_items_without_quotes(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $item = $this->item([
            'name' => 'Surgical gloves',
            'unit' => 'box of 50',
            'category' => 'supplies and equipments',
        ]);

        $project = $this->project($user, $entity, ['title' => 'Pharma supplies Q3']);
        $this->projectItem($project, $item, 40);

        $response = $this->actingAs($user)->get(route('priceindex'))->assertOk();

        $row = collect($response->viewData('items'))->firstWhere('id', $item->id);

        $this->assertNotNull($row);
        $this->assertSame(40, $row['requested']);
        $this->assertSame(['Pharma supplies Q3'], $row['projects']);
        $this->assertSame(0, $row['stats']['count']);
        $this->assertNull($row['stats']['best']);
        $this->assertSame([], $row['quotes']);
        $this->assertNull($row['sparkline']);

        $this->assertSame(0, $response->viewData('itemsWithQuotes'));
        $response->assertSee('Surgical gloves');
    }

    public function test_price_index_ignores_unpriced_lines_and_soft_deleted_projects(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $item = $this->item(['name' => 'Sodium chloride 0.9% 500mL', 'unit' => 'box of 24']);
        $ghostItem = $this->item(['name' => 'Ghost item', 'unit' => 'Box']);

        $activeProject = $this->project($user, $entity, ['title' => 'Active project']);
        $this->projectItem($activeProject, $item, 20);

        $deletedProject = $this->project($user, $entity, ['title' => 'Deleted project']);
        $this->projectItem($deletedProject, $item, 999);
        $this->projectItem($deletedProject, $ghostItem, 10);
        $deletedProject->delete();

        $preplist = $this->preplist($user);
        $preplistItem = $this->preplistItem($preplist, $item);
        $supplier = $this->supplier('GreenCore Pharmaceuticals');

        // A later unpriced line must not replace the supplier's priced line.
        $unpriced = $this->quotation($user, $preplist, $supplier);
        $this->quote($unpriced, $preplistItem, null, '2026-02-10 08:00:00');

        $priced = $this->quotation($user, $preplist, $supplier);
        $this->quote($priced, $preplistItem, 850.00, '2026-01-10 08:00:00');

        $response = $this->actingAs($user)->get(route('priceindex'))->assertOk();
        $items = collect($response->viewData('items'));

        $row = $items->firstWhere('id', $item->id);

        $this->assertNotNull($row);
        $this->assertSame(20, $row['requested']);
        $this->assertSame(['Active project'], $row['projects']);
        $this->assertSame(1, $row['stats']['count']);
        $this->assertEqualsWithDelta(850.00, $row['stats']['best'], 0.001);
        $this->assertCount(1, $row['history']);

        // The deleted project's item has no active request and no quote.
        $this->assertNull($items->firstWhere('id', $ghostItem->id));
    }

    public function test_price_index_renders_empty_state_when_no_data_exists(): void
    {
        $user = $this->admin();

        $response = $this->actingAs($user)->get(route('priceindex'))->assertOk();

        $this->assertSame([], $response->viewData('items'));
        $this->assertSame(0, $response->viewData('itemsWithQuotes'));
        $this->assertSame(0, $response->viewData('activeSuppliers'));

        $response->assertSee('Live quotation data')
            ->assertSee('No items with quotation activity yet.')
            ->assertSee('No suppliers have quoted yet.')
            ->assertSee('No items to compare yet.')
            ->assertSee('No comparable savings yet.');
    }
}
