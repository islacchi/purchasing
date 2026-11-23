<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Preplist;
use App\Models\PreplistItem;
use App\Models\PreplistItemSource;
use App\Models\Project;
use App\Models\ProjectItem;
use App\Models\ProcuringEntity;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RichSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = User::where('email', 'admin@example.com')->firstOrFail();

            // Clear sample data: purchase orders (+ lines / receipts / allocations),
            // quotations (+ items), prep lists (+ sources / items) and projects (+ items).
            \App\Models\PurchaseOrderReceipt::query()->delete();
            \App\Models\PurchaseOrderItemAllocation::query()->delete();
            \App\Models\PurchaseOrderItem::query()->delete();
            \App\Models\PurchaseOrder::query()->delete();

            \App\Models\QuotationItem::query()->delete();
            \App\Models\Quotation::query()->delete();

            \App\Models\PreplistItemSource::query()->delete();
            \App\Models\PreplistItem::query()->delete();
            \App\Models\Preplist::query()->delete();

            \App\Models\ProjectItem::query()->delete();
            \App\Models\Project::query()->delete();

            $drugs = ItemCategory::firstOrCreate(['name' => 'drugs and medicines']);
            $supplies = ItemCategory::firstOrCreate(['name' => 'supplies and equipments']);

            $entities = collect([
                ['name' => 'DOH Region VII - Central Visayas', 'type' => 'Regional Office', 'address' => 'Maasin City, Southern Leyte', 'contact_person' => 'Maria Santos', 'contact_no' => '(+63) 53-555-1010', 'email' => 'reg7@doh.gov.ph'],
                ['name' => 'Municipality of Dauis', 'type' => 'Local Government Unit', 'address' => 'Dauis, Bohol', 'contact_person' => 'Hon. Aurora Reyes', 'contact_no' => '(+63) 38-555-2020', 'email' => 'dauis@bohol.gov.ph'],
            ])->map(fn ($e) => ProcuringEntity::firstOrCreate(['name' => $e['name']], $e));

            $suppliers = collect([
                ['name' => 'Nine Lives Corporation', 'address' => 'Banilkan, Tagbilaran City, Bohol', 'contact_person' => 'Ana Villanueva', 'contact_no' => '(+63) 38-555-3111', 'email' => 'sales@ninelives.com.ph'],
                ['name' => 'GreenCore Pharmaceuticals', 'address' => 'Jacinto Street, Cebu City, Cebu', 'contact_person' => 'Miguel Ortega', 'contact_no' => '(+63) 32-555-4222', 'email' => 'orders@greencore.ph'],
                ['name' => 'Ritelink Medical Supplies', 'address' => 'JTL Road, Baguio City, Benguet', 'contact_person' => 'Karen Dela Cruz', 'contact_no' => '(+63) 74-555-5333', 'email' => 'info@ritelink.com.ph'],
                ['name' => 'Triple Tact Trading', 'address' => 'M. Paredes Street, Manila, NCR', 'contact_person' => 'Jerome Villamor', 'contact_no' => '(+63) 2-555-6444', 'email' => 'sales@tripletacth.ph'],
            ])->map(fn ($s) => Supplier::firstOrCreate(['name' => $s['name']], $s));

            $items = collect([
                ['name' => 'Amoxicillin 500mg capsule',      'category' => $drugs,    'unit' => 'capsule', 'shelf' => 30, 'box' => 100],
                ['name' => 'Azithromycin 500mg tablet',      'category' => $drugs,    'unit' => 'tablet',  'shelf' => 18, 'box' => 144],
                ['name' => 'Anti-rabies vaccine equine 200 IU/ml','category' => $drugs, 'unit' => 'vial',    'shelf' => 24, 'box' => 50],
                ['name' => 'Salbutamol 100mcg inhaler',      'category' => $drugs,    'unit' => 'inhaler', 'shelf' => 12, 'box' => 24],
                ['name' => 'ORS oral rehydration salts',     'category' => $supplies, 'unit' => 'sachet',  'shelf' => 24, 'box' => 240],
                ['name' => 'IV cannula 22G',                 'category' => $supplies, 'unit' => 'piece',   'shelf' => 36, 'box' => 500],
                ['name' => 'Syringe 3mL disposable',         'category' => $supplies, 'unit' => 'piece',   'shelf' => 24, 'box' => 1000],
                ['name' => 'Gauze pads 4x4 cm sterile',      'category' => $supplies, 'unit' => 'pack',    'shelf' => 24, 'box' => 100],
                ['name' => 'Surgical gloves latex medium',    'category' => $supplies, 'unit' => 'box',     'shelf' => 24, 'box' => 100],
                ['name' => 'Paracetamol 500mg tablet',       'category' => $drugs,    'unit' => 'tablet',  'shelf' => 30, 'box' => 1000],
                ['name' => 'Mefenamic Acid 500mg capsule',   'category' => $drugs,    'unit' => 'capsule', 'shelf' => 24, 'box' => 500],
                ['name' => 'Hydrogen Peroxide Solution 3%', 'category' => $supplies, 'unit' => 'bottle',  'shelf' => 12, 'box' => 24],
            ])->map(fn ($i) => Item::firstOrCreate([
                'name' => $i['name'],
                'category_id' => $i['category']->id,
                'unit' => $i['unit'],
            ], [
                'default_min_shelf_life_months' => $i['shelf'],
                'box_size' => $i['box'],
            ]));

            $projects = [
                [
                    'entity' => $entities[0],
                    'title' => 'Community Health Program 2026 - Bohol Province',
                    'reference_no' => 'CHP-2026-0001',
                    'mode_of_procurement' => 'small_value',
                    'amount_awarded' => 1_850_000.00,
                    'date_awarded' => '2026-06-15',
                    'delivery_days' => 45,
                    'delivery_due_date' => '2026-12-30',
                    'notes' => 'Quarterly replenishment of medicines and medical supplies for the Integrated Provincial Health Office.',
                    'lines' => [
                        // [item index, quantity, in_stock, unit_cost, awarded_unit_price]
                        [0, 10, 4, 120.00, 98.00],
                        [1, 12, 0, 385.00, 320.00],
                        [2, 15, 0, 175.00, 148.00],
                        [3, 20, 3, 540.00, 460.00],
                        [4, 8, 2, 95.00, 78.00],
                        [5, 50, 10, 38.00, 32.00],
                        [6, 40, 15, 15.00, 12.00],
                        [7, 25, 0, 78.00, 65.00],
                        [8, 30, 5, 220.00, 185.00],
                        [9, 60, 0, 18.00, 14.00],
                    ],
                ],
                [
                    'entity' => $entities[1],
                    'title' => 'Municipality of Dauis - School Health Program 2026',
                    'reference_no' => 'MHP-2026-0002',
                    'mode_of_procurement' => 'small_value',
                    'amount_awarded' => 645_000.00,
                    'date_awarded' => '2026-07-20',
                    'delivery_days' => 30,
                    'delivery_due_date' => '2026-08-19',
                    'notes' => 'School-based medicines and first-responder supplies for the Municipality of Dauis.',
                    'lines' => [
                        [1, 18, 0, 385.00, 320.00],
                        [3, 12, 2, 540.00, 460.00],
                        [4, 24, 6, 95.00, 78.00],
                        [5, 30, 5, 38.00, 32.00],
                        [6, 45, 9, 15.00, 12.00],
                        [7, 20, 3, 78.00, 65.00],
                        [8, 28, 7, 220.00, 185.00],
                        [9, 52, 10, 18.00, 14.00],
                        [0, 14, 2, 120.00, 98.00],
                        [10, 38, 5, 195.00, 168.00],
                    ],
                ],
            ];

            foreach ($projects as $projectSpec) {
                $project = Project::create([
                    'procuring_entity_id' => $projectSpec['entity']->id,
                    'created_by' => $user->id,
                    'title' => $projectSpec['title'],
                    'reference_no' => $projectSpec['reference_no'],
                    'mode_of_procurement' => $projectSpec['mode_of_procurement'],
                    'amount_awarded' => $projectSpec['amount_awarded'],
                    'date_awarded' => $projectSpec['date_awarded'],
                    'delivery_days' => $projectSpec['delivery_days'],
                    'delivery_due_date' => $projectSpec['delivery_due_date'],
                    'notes' => $projectSpec['notes'],
                ]);

                foreach ($projectSpec['lines'] as $line) {
                    [$itemIndex, $quantity, $inStock, $unitCost, $awardedUnitPrice] = $line;
                    $item = $items[$itemIndex];

                    ProjectItem::create([
                        'project_id' => $project->id,
                        'item_id' => $item->id,
                        'quantity' => $quantity,
                        'in_stock' => $inStock,
                        'unit_cost' => $unitCost,
                        'awarded_unit_price' => $awardedUnitPrice,
                        'fulfillment_status' => 'pending',
                    ]);
                }
            }
        });

        $this->command?->info('Rich sample data created.');
    }
}
