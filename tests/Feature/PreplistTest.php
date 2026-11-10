<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\NumberSequence;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\PreplistItemSource;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreplistTest extends TestCase
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

    /**
     * Create a prep list through the store endpoint and return the record.
     */
    private function compile(User $user, array $payload): Preplist
    {
        $this->actingAs($user)
            ->post(route('preplist.store'), $payload)
            ->assertSessionHasNoErrors();

        return Preplist::query()->latest('id')->firstOrFail();
    }

    public function test_preplist_index_lists_real_database_records(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $this->actingAs($user)
            ->get(route('preplist'))
            ->assertOk()
            ->assertSee($preplist->name)
            ->assertSee(route('preplist.show', ['preplist' => $preplist->id]), false)
            ->assertSee($user->name)
            ->assertSee($project->title);
    }

    public function test_preplist_index_can_be_searched_by_name_or_reference(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();

        $matched = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$this->projectItem($project, $item)->id],
        ]);

        $this->compile($user, [
            'name' => 'August laboratory batch',
            'sources' => [$this->projectItem($project, $item)->id],
        ]);

        $this->actingAs($user)
            ->get(route('preplist', ['q' => 'July']))
            ->assertOk()
            ->assertSee($matched->name)
            ->assertDontSee('August laboratory batch');

        $this->actingAs($user)
            ->get(route('preplist', ['q' => $matched->reference_no]))
            ->assertOk()
            ->assertSee($matched->name)
            ->assertDontSee('August laboratory batch');
    }

    public function test_preplist_create_form_lists_real_projects_and_items(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $this->actingAs($user)
            ->get(route('preplist.create'))
            ->assertOk()
            ->assertSee($project->title)
            ->assertSee($item->name)
            ->assertSee('value="'.$projectItem->id.'"', false)
            ->assertDontSee('PPE bulk order')
            ->assertDontSee('Acetaminophen 500mg');
    }

    public function test_preplist_compiles_grouped_items_with_source_quantities(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $firstProject = $this->project($user, $entity);
        $secondProject = $this->project($user, $entity, [
            'title' => 'Laboratory reagents',
            'reference_no' => 'RF-2026-0043',
        ]);

        $item = $this->item();

        $firstSource = $this->projectItem($firstProject, $item, ['quantity' => 30]);
        $secondSource = $this->projectItem($secondProject, $item, ['quantity' => 50]);

        $response = $this->actingAs($user)->post(route('preplist.store'), [
            'name' => 'July Region VII batch',
            'notes' => 'Batch compiled from two projects.',
            'sources' => [$firstSource->id, $secondSource->id],
            'shelf_life' => [
                $firstSource->id => 6,
                $secondSource->id => 12,
            ],
        ]);

        $preplist = Preplist::firstOrFail();

        $response->assertRedirect(route('preplist.show', ['preplist' => $preplist->id]));
        $response->assertSessionHas('status');

        $this->assertSame('PL-'.now()->year.'-0001', $preplist->reference_no);
        $this->assertSame($user->id, $preplist->created_by);

        $this->assertDatabaseCount('preplist_items', 1);
        $this->assertDatabaseCount('preplist_item_sources', 2);

        $preplistItem = PreplistItem::firstOrFail();

        $this->assertSame($item->id, $preplistItem->item_id);
        $this->assertSame(12, $preplistItem->min_shelf_life_months);
        $this->assertSame(80, $preplistItem->totalQuantity());

        $this->assertDatabaseHas('preplist_item_sources', [
            'preplist_item_id' => $preplistItem->id,
            'project_item_id' => $firstSource->id,
            'qty' => 30,
        ]);
        $this->assertDatabaseHas('preplist_item_sources', [
            'preplist_item_id' => $preplistItem->id,
            'project_item_id' => $secondSource->id,
            'qty' => 50,
        ]);

        $this->assertDatabaseHas('number_sequences', [
            'key' => 'preplist',
            'year' => now()->year,
            'last_number' => 1,
        ]);
    }

    public function test_preplist_reference_numbers_increment_for_the_year(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $first = $this->compile($user, [
            'name' => 'First prep list',
            'sources' => [$projectItem->id],
        ]);
        $second = $this->compile($user, [
            'name' => 'Second prep list',
            'sources' => [$projectItem->id],
        ]);

        $this->assertSame('PL-'.now()->year.'-0001', $first->reference_no);
        $this->assertSame('PL-'.now()->year.'-0002', $second->reference_no);

        $this->assertSame(1, NumberSequence::where('key', 'preplist')->count());
    }

    public function test_preplist_edit_form_prefills_existing_sources(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 9],
        ]);

        $this->actingAs($user)
            ->get(route('preplist.edit', ['preplist' => $preplist->id]))
            ->assertOk()
            ->assertSee('value="'.$preplist->name.'"', false)
            ->assertSee('value="'.$projectItem->id.'"', false)
            ->assertSee('value="9"', false)
            ->assertSee($preplist->reference_no);
    }

    public function test_preplist_can_be_updated_and_sources_replaced(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $firstItem = $this->item();
        $secondItem = $this->item(['name' => 'Surgical gloves', 'unit' => 'Pair']);

        $firstSource = $this->projectItem($project, $firstItem);
        $secondSource = $this->projectItem($project, $secondItem);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$firstSource->id],
        ]);

        $response = $this->actingAs($user)->put(
            route('preplist.update', ['preplist' => $preplist->id]),
            [
                'name' => 'July Region VII batch updated',
                'notes' => 'Updated notes.',
                'sources' => [$secondSource->id],
                'shelf_life' => [$secondSource->id => 3],
            ],
        );

        $response->assertRedirect(route('preplist.show', ['preplist' => $preplist->id]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('preplists', [
            'id' => $preplist->id,
            'name' => 'July Region VII batch updated',
            'notes' => 'Updated notes.',
        ]);

        $this->assertDatabaseCount('preplist_items', 1);
        $this->assertDatabaseHas('preplist_items', [
            'preplist_id' => $preplist->id,
            'item_id' => $secondItem->id,
            'min_shelf_life_months' => 3,
        ]);
        $this->assertDatabaseMissing('preplist_items', [
            'preplist_id' => $preplist->id,
            'item_id' => $firstItem->id,
        ]);
        $this->assertDatabaseHas('preplist_item_sources', [
            'project_item_id' => $secondSource->id,
            'qty' => 50,
        ]);
        $this->assertDatabaseMissing('preplist_item_sources', [
            'project_item_id' => $firstSource->id,
        ]);
    }

    public function test_preplist_show_displays_grouped_items_and_source_projects(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $this->actingAs($user)
            ->get(route('preplist.show', ['preplist' => $preplist->id]))
            ->assertOk()
            ->assertSee($preplist->name)
            ->assertSee($preplist->reference_no)
            ->assertSee($item->name)
            ->assertSee($project->title)
            ->assertSee('50 Box total');
    }

    public function test_preplist_notes_can_be_updated_from_the_detail_page(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $this->actingAs($user)
            ->patch(route('preplist.notes', ['preplist' => $preplist->id]), [
                'notes' => 'Delivery to the regional warehouse.',
            ])
            ->assertRedirect(route('preplist.show', ['preplist' => $preplist->id]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('preplists', [
            'id' => $preplist->id,
            'notes' => 'Delivery to the regional warehouse.',
        ]);
    }

    public function test_preplist_can_be_deleted(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $this->actingAs($user)
            ->delete(route('preplist.destroy', ['preplist' => $preplist->id]))
            ->assertRedirect(route('preplist'))
            ->assertSessionHas('status');

        $this->assertSoftDeleted('preplists', ['id' => $preplist->id]);
    }

    public function test_preplist_requires_at_least_one_project_item(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'Empty prep list',
                'sources' => [],
            ])
            ->assertSessionHasErrors('sources');

        $this->assertDatabaseCount('preplists', 0);
        $this->assertDatabaseCount('preplist_items', 0);
        $this->assertDatabaseCount('number_sequences', 0);
    }

    public function test_preplist_rejects_unknown_project_items(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'Invalid prep list',
                'sources' => [99999],
                'shelf_life' => [99999 => 12],
            ])
            ->assertSessionHasErrors('sources.0');

        $this->assertDatabaseCount('preplists', 0);
    }

    public function test_generate_rfq_uses_real_preplist_items_and_suppliers(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $supplier = Supplier::create(['name' => 'Triple Tact']);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
            'shelf_life' => [$projectItem->id => 12],
        ]);

        $this->actingAs($user)
            ->get(route('preplist.generate-rfq', ['preplist' => $preplist->id]))
            ->assertOk()
            ->assertSee('Generate quotation - '.$preplist->name)
            ->assertSee($item->name)
            ->assertSee('50 Box total')
            ->assertSee('&ge; 12 months remaining at delivery', false)
            ->assertSee($supplier->name)
            ->assertSee(route('preplist.show', ['preplist' => $preplist->id]), false)
            ->assertDontSee('Amoxicillin 500mg')
            ->assertDontSee('MedSource Inc.');
    }

    public function test_generate_rfq_handles_a_preplist_without_items(): void
    {
        $user = $this->admin();

        $preplist = Preplist::create([
            'created_by' => $user->id,
            'name' => 'Empty prep list',
            'reference_no' => 'PL-'.now()->year.'-0001',
        ]);

        $this->actingAs($user)
            ->get(route('preplist.generate-rfq', ['preplist' => $preplist->id]))
            ->assertOk()
            ->assertSee('No items to request');
    }

    public function test_unknown_preplist_returns_not_found(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->get('/preplist/99999')
            ->assertNotFound();
    }

    public function test_preplist_models_are_not_using_phantom_timestamps(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $item = $this->item();
        $projectItem = $this->projectItem($project, $item);

        $preplist = $this->compile($user, [
            'name' => 'July Region VII batch',
            'sources' => [$projectItem->id],
        ]);

        $preplistItem = PreplistItem::firstOrFail();
        $source = PreplistItemSource::firstOrFail();

        $this->assertFalse($preplist->usesTimestamps());
        $this->assertFalse($preplistItem->usesTimestamps());
        $this->assertFalse($source->usesTimestamps());
    }
}
