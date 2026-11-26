<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Preplist;
use App\Models\ProcuringEntity;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
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

    private function addItem(Project $project): ProjectItem
    {
        $category = ItemCategory::firstOrCreate(['name' => 'drugs and medicines']);
        $item = Item::create([
            'category_id' => $category->id,
            'name' => 'Mefenamic Capsule - 500mg',
            'unit' => 'Box',
        ]);

        return ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => 50,
            'unit_cost' => 320.00,
            'awarded_unit_price' => 305.00,
            'fulfillment_status' => 'pending',
        ]);
    }

    public function test_projects_module_lists_real_database_records(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $this->actingAs($user)
            ->get('/projects')
            ->assertOk()
            ->assertSee($project->title)
            ->assertSee($project->reference_no)
            ->assertSee($entity->name)
            ->assertDontSee('bg-gray-200/50')
            ->assertSee('id="view-list"', false)
            ->assertSee('class="min-w-full divide-y divide-gray-200"', false);
    }

    public function test_projects_can_be_searched_by_title_or_entity(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $otherEntity = $this->entity(['name' => 'PGH Manila', 'type' => 'Government Hospital']);

        $this->project($user, $entity, ['title' => 'Regional medicine purchase']);
        $this->project($user, $otherEntity, ['title' => 'Hospital equipment purchase', 'reference_no' => 'RF-2026-0099']);

        $this->actingAs($user)
            ->get('/projects?q=medicine')
            ->assertOk()
            ->assertSee('Regional medicine purchase')
            ->assertDontSee('Hospital equipment purchase')
            ->assertSee('border border-gray-200"', false);
    }

    public function test_project_create_form_has_procuring_entity_dropdown(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $this->actingAs($user)
            ->get(route('projects.create'))
            ->assertOk()
            ->assertSee('name="procuring_entity_id"', false)
            ->assertSee($entity->name)
            ->assertSee('value="'.$entity->id.'"', false)
            // Revamped two-column layout: rail cards + items card toolbar.
            ->assertSee('Project details')
            ->assertSee('Award &amp; delivery', false)
            ->assertSee('id="itemsMeta"', false)
            ->assertSee('id="ghostAddRowBtn"', false);
    }

    public function test_project_form_shows_validation_banner_on_failed_validation(): void
    {
        $user = $this->admin();

        $this->actingAs($user)
            ->withHeaders(['Referer' => route('projects.create')])
            ->followingRedirects()
            ->post(route('projects.store'), [
                'title' => 'Incomplete project',
                'reference_no' => 'RF-INCOMPLETE-1',
            ])
            ->assertOk()
            ->assertSee('flash-success-overlay', false)
            ->assertSee('role="alert"', false)
            ->assertSee('There are issues with your submission.');
    }

    public function test_failed_store_keeps_a_default_empty_item_row(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $this->actingAs($user)
            ->withHeaders(['Referer' => route('projects.create')])
            ->followingRedirects()
            ->post(route('projects.store'), [
                'title' => 'Incomplete project',
                'reference_no' => 'RF-BLANK-1',
                // mode_of_procurement is required; omit it so the store fails and the form re-renders.
                'items' => [
                    ['description' => '', 'category' => '', 'unit' => '', 'quantity' => '', 'unit_cost' => '', 'quoted_price' => '', 'in_stock' => ''],
                ],
            ])
            ->assertOk()
            ->assertSee('class="item-row', false);
    }

    public function test_failed_store_preserves_partial_item_rows(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $this->actingAs($user)
            ->withHeaders(['Referer' => route('projects.create')])
            ->followingRedirects()
            ->post(route('projects.store'), [
                'title' => 'Incomplete project',
                'reference_no' => 'RF-PARTIAL-1',
                'mode_of_procurement' => 'small_value',
                'items' => [
                    [
                        'description' => 'Row with only a description',
                        'category' => '',
                        'unit' => '',
                        'quantity' => '',
                        'unit_cost' => '',
                        'quoted_price' => '',
                        'in_stock' => '',
                    ],
                ],
            ])
            ->assertOk()
            ->assertSee('Row with only a description');
    }

    public function test_project_can_be_created_with_database_record_and_items(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $response = $this->actingAs($user)
            ->post(route('projects.store'), [
                'procuring_entity_id' => $entity->id,
                'title' => 'Purchase of pharma supplies',
                'reference_no' => 'RF-2026-0042',
                'mode_of_procurement' => 'small_value',
                'amount_awarded' => 482000.00,
                'date_awarded' => '2026-07-03',
                'delivery_days' => 30,
                'notes' => 'Quarterly procurement of pharmaceutical supplies.',
                'items' => [
                    [
                        'description' => 'Mefenamic Capsule - 500mg',
                        'category' => 'drugs and medicines',
                        'unit' => 'Box',
                        'quantity' => 50,
                        'unit_cost' => 320.00,
                        'quoted_price' => 305.00,
                    ],
                ],
            ]);

        $project = Project::firstOrFail();

        $response->assertRedirect(route('projects.show', ['project' => $project->id]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'procuring_entity_id' => $entity->id,
            'created_by' => $user->id,
            'title' => 'Purchase of pharma supplies',
            'delivery_days' => 30,
        ]);

        $this->assertSame('2026-08-02', $project->fresh()->delivery_due_date->toDateString());

        $this->assertDatabaseHas('items', [
            'name' => 'Mefenamic Capsule - 500mg',
            'unit' => 'Box',
        ]);

        $this->assertDatabaseHas('project_items', [
            'project_id' => $project->id,
            'quantity' => 50,
            'unit_cost' => 320.00,
            'awarded_unit_price' => 305.00,
        ]);
    }

    public function test_project_can_be_created_with_items_via_the_json_payload(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $response = $this->actingAs($user)
            ->post(route('projects.store'), [
                'procuring_entity_id' => $entity->id,
                'title' => 'Purchase of pharma supplies',
                'reference_no' => 'RF-2026-0077',
                'mode_of_procurement' => 'small_value',
                'items_json' => json_encode([
                    [
                        'description' => 'Mefenamic Capsule - 500mg',
                        'category' => 'drugs and medicines',
                        'unit' => 'Box',
                        'box_size' => null,
                        'quantity' => 50,
                        'unit_cost' => 320.00,
                        'quoted_price' => 305.00,
                        'in_stock' => '',
                    ],
                    [
                        'description' => 'Cetirizine 10mg tablet',
                        'category' => 'supplies and equipments',
                        'unit' => 'pcs',
                        'quantity' => 20,
                        'unit_cost' => 8.50,
                    ],
                ]),
            ]);

        $project = Project::firstOrFail();

        $response->assertRedirect(route('projects.show', ['project' => $project->id]));

        $this->assertDatabaseHas('project_items', [
            'project_id' => $project->id,
            'quantity' => 50,
            'unit_cost' => 320.00,
            'awarded_unit_price' => 305.00,
        ]);
        $this->assertDatabaseHas('project_items', [
            'project_id' => $project->id,
            'quantity' => 20,
            'unit_cost' => 8.50,
        ]);
    }

    public function test_project_accepts_custom_delivery_days_over_the_dropdown(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'procuring_entity_id' => $entity->id,
                'title' => 'Custom delivery test',
                'reference_no' => 'RF-2026-9091',
                'mode_of_procurement' => 'small_value',
                'amount_awarded' => 100.00,
                'date_awarded' => '2026-07-03',
                'delivery_days' => '__custom',
                'delivery_days_custom' => 42,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('projects', [
            'reference_no' => 'RF-2026-9091',
            'delivery_days' => 42,
        ]);
    }

    public function test_project_rejects_negative_item_quantities_and_prices(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'procuring_entity_id' => $entity->id,
                'title' => 'Negative trap test',
                'reference_no' => 'RF-2026-0055',
                'mode_of_procurement' => 'small_value',
                'amount_awarded' => 100.00,
                'date_awarded' => '2026-07-03',
                'delivery_days' => 30,
                'items' => [
                    [
                        'description' => 'Mefenamic Capsule - 500mg',
                        'category' => 'drugs and medicines',
                        'unit' => 'Box',
                        'quantity' => -5,
                        'unit_cost' => -10.00,
                        'quoted_price' => -5.00,
                        'in_stock' => -1,
                    ],
                ],
            ])
            ->assertSessionHasErrors([
                'items.0.quantity',
                'items.0.unit_cost',
                'items.0.quoted_price',
                'items.0.in_stock',
            ]);

        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('project_items', 0);
    }

    public function test_project_edit_form_shows_original_data(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $this->addItem($project);

        $this->actingAs($user)
            ->get(route('projects.edit', ['project' => $project->id]))
            ->assertOk()
            ->assertSee('value="'.$project->title.'"', false)
            ->assertSee('value="'.$project->reference_no.'"', false)
            ->assertSee('value="'.$project->amount_awarded.'"', false)
            ->assertSee('value="'.$entity->id.'" selected', false)
            ->assertSee($entity->name)
            ->assertSee('value="Mefenamic Capsule - 500mg"', false);
    }

    public function test_project_can_be_updated(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $otherEntity = $this->entity(['name' => 'PGH Manila', 'type' => 'Government Hospital']);
        $project = $this->project($user, $entity);

        $response = $this->actingAs($user)
            ->put(route('projects.update', ['project' => $project->id]), [
                'procuring_entity_id' => $otherEntity->id,
                'title' => 'Purchase of pharma supplies updated',
                'reference_no' => 'RF-2026-0100',
                'mode_of_procurement' => 'competitive_bidding',
                'amount_awarded' => 500000.00,
                'date_awarded' => '2026-07-10',
                'delivery_days' => 15,
                'notes' => 'Updated project notes.',
                'items' => [],
            ]);

        $response->assertRedirect(route('projects.show', ['project' => $project->id]));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'procuring_entity_id' => $otherEntity->id,
            'title' => 'Purchase of pharma supplies updated',
            'reference_no' => 'RF-2026-0100',
            'mode_of_procurement' => 'competitive_bidding',
            'delivery_days' => 15,
        ]);

        $this->assertSame('2026-07-25', $project->fresh()->delivery_due_date->toDateString());
    }

    public function test_project_update_keeps_locked_sourced_lines_untouched(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $projectItem = $this->addItem($project);

        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'Locked fixture prep list',
                'sources' => [$projectItem->id],
            ])
            ->assertSessionHasNoErrors();

        // Even when the form submits different values for the locked line,
        // the database must keep the original ones.
        $this->actingAs($user)
            ->put(route('projects.update', ['project' => $project->id]), [
                'procuring_entity_id' => $entity->id,
                'title' => 'Project edited around locked lines',
                'reference_no' => 'RF-2026-EDIT2',
                'mode_of_procurement' => 'small_value',
                'items' => [
                    [
                        'description' => 'Mefenamic Capsule - 500mg',
                        'category' => 'drugs and medicines',
                        'unit' => 'Box',
                        'quantity' => 9999,
                        'unit_cost' => 9999.00,
                        'quoted_price' => 9999.00,
                        'in_stock' => 9999,
                    ],
                ],
            ])
            ->assertSessionHas('status');

        $line = $project->fresh()->items()->firstOrFail();
        $this->assertSame(50, (int) $line->quantity, 'Locked sourced line quantity must not change.');
        $this->assertSame('Box', $line->item?->unit);
        $this->assertSame(320.00, (float) $line->unit_cost);
        $this->assertSame(305.00, (float) $line->awarded_unit_price);
        $this->assertSame(0, (int) $line->in_stock);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'title' => 'Project edited around locked lines',
            'reference_no' => 'RF-2026-EDIT2',
        ]);
    }

    public function test_project_update_refuses_to_remove_a_sourced_line(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $projectItem = $this->addItem($project);

        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'Removal guard prep list',
                'sources' => [$projectItem->id],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->put(route('projects.update', ['project' => $project->id]), [
                'procuring_entity_id' => $entity->id,
                'title' => 'Attempted removal',
                'reference_no' => 'RF-2026-EDIT3',
                'mode_of_procurement' => 'small_value',
                'items' => [],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(1, $project->fresh()->items()->count(), 'The sourced line must remain in the project.');
        $this->assertDatabaseMissing('projects', ['title' => 'Attempted removal']);
    }

    public function test_project_update_edits_and_removes_unsourced_lines(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $first = $this->addItem($project);

        $secondCategory = ItemCategory::firstOrCreate(['name' => 'supplies and equipments']);
        $secondItem = Item::create([
            'category_id' => $secondCategory->id,
            'name' => 'Surgical Gloves - Large',
            'unit' => 'Box',
        ]);
        $second = ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => $secondItem->id,
            'quantity' => 100,
            'unit_cost' => 450.00,
            'awarded_unit_price' => 440.00,
            'fulfillment_status' => 'pending',
        ]);

        $this->actingAs($user)
            ->put(route('projects.update', ['project' => $project->id]), [
                'procuring_entity_id' => $entity->id,
                'title' => $project->title,
                'reference_no' => $project->reference_no,
                'mode_of_procurement' => $project->mode_of_procurement,
                'items' => [
                    [
                        'description' => 'Mefenamic Capsule - 500mg',
                        'category' => 'drugs and medicines',
                        'unit' => 'Box',
                        'quantity' => 25,
                        'unit_cost' => 321.00,
                    ],
                ],
            ])
            ->assertSessionHas('status');

        $this->assertSame(25, (int) $first->fresh()->quantity);
        $this->assertDatabaseMissing('project_items', ['id' => $second->id]);
    }

    public function test_project_edit_form_marks_sourced_lines_as_locked(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $projectItem = $this->addItem($project);

        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'Locked rendering fixture',
                'sources' => [$projectItem->id],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->get(route('projects.edit', ['project' => $project->id]))
            ->assertOk()
            ->assertSee('is-locked', false)
            ->assertSee('readonly', false)
            ->assertSee('locked-category-value', false);
    }

    public function test_project_edit_unlocks_a_line_once_its_prep_list_is_deleted(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $projectItem = $this->addItem($project);

        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'Post-delete unlock fixture',
                'sources' => [$projectItem->id],
            ])
            ->assertSessionHasNoErrors();

        $preplist = Preplist::query()->latest('id')->firstOrFail();
        $this->actingAs($user)
            ->delete(route('preplist.destroy', ['preplist' => $preplist->id]))
            ->assertRedirect(route('preplist'));

        // The sourcing prep list is gone, so the line must no longer be
        // locked and becomes editable again (quantity / awarded price).
        $this->actingAs($user)
            ->get(route('projects.edit', ['project' => $project->id]))
            ->assertOk();

        $this->actingAs($user)
            ->put(route('projects.update', ['project' => $project->id]), [
                'procuring_entity_id' => $entity->id,
                'title' => 'Project edited after source removal',
                'reference_no' => 'RF-2026-EDIT4',
                'mode_of_procurement' => 'small_value',
                'items' => [
                    [
                        'description' => 'Mefenamic Capsule - 500mg',
                        'category' => 'drugs and medicines',
                        'unit' => 'Box',
                        'quantity' => 77,
                        'unit_cost' => 320.00,
                        'quoted_price' => 305.00,
                        'in_stock' => 0,
                    ],
                ],
            ])
            ->assertSessionHas('status');

        $this->assertSame(77, (int) $project->fresh()->items()->firstOrFail()->quantity);
    }

    public function test_project_update_rejects_a_stale_fingerprint(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity, ['title' => 'Original title']);

        $response = $this->actingAs($user)
            ->put(route('projects.update', ['project' => $project->id]), [
                'expected_project_fingerprint' => 'not-the-current-fingerprint',
                'procuring_entity_id' => $entity->id,
                'title' => 'Someone else changed this',
                'reference_no' => 'RF-2026-9999',
                'mode_of_procurement' => 'small_value',
                'items' => [],
            ]);

        $response->assertRedirect(route('projects.edit', ['project' => $project->id]));
        $response->assertSessionHasErrors('project');
        $this->assertSame('Original title', $project->fresh()->title);
    }

    public function test_project_show_displays_real_database_record(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $this->addItem($project);

        $this->actingAs($user)
            ->get(route('projects.show', ['project' => $project->id]))
            ->assertOk()
            ->assertSee($project->title)
            ->assertSee($project->reference_no)
            ->assertSee($entity->name)
            ->assertSee('Mefenamic Capsule - 500mg');
    }

    public function test_sidebar_highlights_the_module_on_its_detail_page(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        // Exactly one sidebar entry stays highlighted while on a sub-page.
        $response = $this->actingAs($user)
            ->get(route('projects.show', ['project' => $project->id]))
            ->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'w-1 bg-teal-300"></span>'));
    }

    public function test_project_can_be_deleted(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);

        $this->actingAs($user)
            ->delete(route('projects.destroy', ['project' => $project->id]))
            ->assertRedirect(route('projects'));

        $this->assertSoftDeleted('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_project_cannot_be_deleted_when_a_prep_list_sources_it(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $projectItem = $this->addItem($project);

        // The prep list compiles the project's item, so the project is in use.
        $this->actingAs($user)
            ->post(route('preplist.store'), [
                'name' => 'July Region VII batch',
                'sources' => [$projectItem->id],
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->delete(route('projects.destroy', ['project' => $project->id]))
            ->assertSessionHasErrors('project');

        $this->assertNull(Project::withTrashed()->find($project->id)->deleted_at);

        $this->actingAs($user)
            ->get(route('projects.show', ['project' => $project->id]))
            ->assertOk()
            ->assertSee('cannot be deleted')
            ->assertDontSee('id="projectDeleteBtn"', false);
    }

    public function test_project_requires_an_existing_procuring_entity(): void
    {
        $user = $this->admin();
        $this->entity();

        $this->actingAs($user)
            ->post(route('projects.store'), [
                'procuring_entity_id' => 99999,
                'title' => 'Purchase of pharma supplies',
                'reference_no' => 'RF-2026-0042',
                'mode_of_procurement' => 'small_value',
            ])
            ->assertSessionHasErrors('procuring_entity_id');

        $this->assertDatabaseCount('projects', 0);
    }

    public function test_projects_module_paginates_and_preserves_the_search(): void
    {
        $user = $this->admin();
        $entity = $this->entity();

        for ($i = 1; $i <= 12; $i++) {
            $this->project($user, $entity, [
                'title' => sprintf('Paginated fixture %02d', $i),
                'reference_no' => sprintf('RF-PG-%02d', $i),
            ]);
        }

        $pageOne = $this->actingAs($user)->get('/projects')->assertOk();
        $pageOne->assertSee('aria-label="Pagination Navigation"', false)
            ->assertSee('page=2', false)
            ->assertSee('results', false);

        $projects = $pageOne->viewData('projects');
        $this->assertSame(10, $projects->count());
        $this->assertSame(12, $projects->total());
        $this->assertSame(1, $projects->currentPage());
        $this->assertNotNull($projects->nextPageUrl());

        $pageTwo = $this->actingAs($user)->get('/projects?page=2')->assertOk();
        $secondPage = $pageTwo->viewData('projects');
        $this->assertSame(2, $secondPage->count());
        $pageTwo->assertSee('Paginated fixture 01')
            ->assertSee('Paginated fixture 02')
            ->assertDontSee('Paginated fixture 12');

        $searchPage = $this->actingAs($user)->get('/projects?q=fixture&page=2')->assertOk();
        $this->assertSame(2, $searchPage->viewData('projects')->count());
        $this->assertStringContainsString('q=fixture', $searchPage->viewData('projects')->previousPageUrl() ?? '');
    }

    public function test_projects_module_filters_by_entity_mode_status(): void
    {
        $user = $this->admin();
        $entityOne = $this->entity(['name' => 'Entities One']);
        $entityTwo = $this->entity(['name' => 'Entities Two']);

        $shopping = $this->project($user, $entityOne, [
            'title' => 'Filter fixture shopping supplies',
            'reference_no' => 'RF-FILTER-SHOP',
            'mode_of_procurement' => 'shopping',
        ]);
        $smallValue = $this->project($user, $entityTwo, [
            'title' => 'Filter fixture small value chemicals',
            'reference_no' => 'RF-FILTER-SV',
            'mode_of_procurement' => 'small_value',
        ]);

        $this->addItemWithStatus($shopping, 'fulfilled');
        $this->addItemWithStatus($smallValue, 'pending');

        $this->actingAs($user)
            ->get('/projects?entity='.$entityOne->id)
            ->assertOk()
            ->assertSee($shopping->title)
            ->assertDontSee($smallValue->title);

        $this->actingAs($user)
            ->get('/projects?mode=shopping')
            ->assertOk()
            ->assertSee($shopping->title)
            ->assertDontSee($smallValue->title);

        $this->actingAs($user)
            ->get('/projects?status=open')
            ->assertOk()
            ->assertSee($smallValue->title)
            ->assertDontSee($shopping->title);

        $this->actingAs($user)
            ->get('/projects?status=completed')
            ->assertOk()
            ->assertSee($shopping->title)
            ->assertDontSee($smallValue->title);

        $this->actingAs($user)
            ->get('/projects?q=Filter+fixture&entity='.$entityOne->id.'&mode=shopping&status=open')
            ->assertOk()
            ->assertSee('No projects match the current filters');
    }

    private function addItemWithStatus(Project $project, string $status): ProjectItem
    {
        $category = ItemCategory::firstOrCreate([
            'name' => 'drugs and medicines',
        ]);
        $item = Item::create([
            'category_id' => $category->id,
            'name' => 'Filter fixture item '.$status.' '.uniqid(),
            'unit' => 'Box',
        ]);

        return ProjectItem::create([
            'project_id' => $project->id,
            'item_id' => $item->id,
            'quantity' => 10,
            'unit_cost' => 100.00,
            'awarded_unit_price' => 100.00,
            'fulfillment_status' => $status,
        ]);
    }

    public function test_project_summary_downloads_an_xlsx_workbook(): void
    {
        $user = $this->admin();
        $entity = $this->entity();
        $project = $this->project($user, $entity);
        $this->addItem($project);

        $response = $this->actingAs($user)->get('/projects/'.$project->id.'/download');

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type')
        );
        $disposition = (string) $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('RF-2026-0042_Project_Summary.xlsx', $disposition);

        // The export is a real .xlsx package: Folio (8.5 × 13 in), landscape,
        // fit-to-width, opened in the normal grid view so it renders in
        // every spreadsheet application.
        $zipPath = tempnam(sys_get_temp_dir(), 'xlsx_').'.xlsx';
        file_put_contents($zipPath, $response->getContent());

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($zipPath) === true);

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $this->assertIsString($sheet);
        $this->assertStringContainsString('paperSize="14"', $sheet);
        $this->assertStringContainsString('orientation="landscape"', $sheet);
        $this->assertStringContainsString('view="normal"', $sheet);
        $this->assertStringContainsString('Mefenamic Capsule - 500mg', $sheet);

        $zip->close();
        unlink($zipPath);
    }
}
