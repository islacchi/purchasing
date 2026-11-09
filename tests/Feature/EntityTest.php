<?php

namespace Tests\Feature;

use App\Models\ProcuringEntity;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(AdminUserSeeder::class);

        return User::where('name', 'admin')->firstOrFail();
    }

    public function test_entity_module_lists_real_database_records(): void
    {
        $entity = ProcuringEntity::create([
            'name' => 'DOH Region VII',
            'type' => 'Regional Office',
            'address' => 'Osmeña Blvd, Cebu City',
            'contact_person' => 'Dr. R. Villanueva',
            'contact_no' => '032-123-4567',
            'email' => 'doh.region7@gov.ph',
        ]);

        $supplier = Supplier::create([
            'name' => 'Triple Tact',
            'address' => 'Mandaue City, Cebu',
            'contact_person' => 'E. Lim',
            'contact_no' => '032-345-6789',
            'email' => 'sales@tripletact.com',
        ]);

        $this->actingAs($this->admin())
            ->get('/entities')
            ->assertOk()
            ->assertSee('DOH Region VII')
            ->assertSee('Triple Tact');
    }

    public function test_suppliers_tab_can_be_loaded_directly(): void
    {
        $this->actingAs($this->admin())
            ->get('/entities?tab=suppliers')
            ->assertOk()
            ->assertSee('data-active-tab="suppliers"', false);
    }

    public function test_procuring_entity_can_be_created_from_real_form(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('entities.store', ['type' => 'procuring_entity']), [
                'name' => 'PGH Manila',
                'type' => 'Government Hospital',
                'address' => 'Taft Ave, Ermita, Manila',
                'contact_person' => 'N. Aquino',
                'contact_no' => '02-8554-8400',
                'email' => 'procurement@pgh.gov.ph',
            ]);

        $response->assertRedirect(route('entities', ['tab' => 'entities']));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('procuring_entities', [
            'name' => 'PGH Manila',
            'type' => 'Government Hospital',
        ]);
    }

    public function test_supplier_can_be_created_from_real_form(): void
    {
        $response = $this->actingAs($this->admin())
            ->post(route('entities.store', ['type' => 'supplier']), [
                'name' => 'GreenCore Pharmaceuticals',
                'address' => 'Quezon City, Metro Manila',
                'contact_person' => 'P. Domingo',
                'contact_no' => '02-8123-4455',
                'email' => 'orders@greencorepharma.ph',
            ]);

        $response->assertRedirect(route('entities', ['tab' => 'suppliers']));
        $response->assertSessionHas('status');
        $this->assertDatabaseHas('suppliers', [
            'name' => 'GreenCore Pharmaceuticals',
        ]);
    }

    public function test_procuring_entity_edit_form_shows_original_data(): void
    {
        $entity = ProcuringEntity::create([
            'name' => 'DOH Region VII',
            'type' => 'Regional Office',
            'address' => 'Osmeña Blvd, Cebu City',
            'contact_person' => 'Dr. R. Villanueva',
            'contact_no' => '032-123-4567',
            'email' => 'doh.region7@gov.ph',
        ]);

        $this->actingAs($this->admin())
            ->get(route('entities.edit', ['type' => 'procuring_entity', 'id' => $entity->id]))
            ->assertOk()
            ->assertSee('value="'.$entity->name.'"', false)
            ->assertSee($entity->address)
            ->assertSee('value="'.$entity->email.'"', false);
    }

    public function test_supplier_edit_form_shows_original_data(): void
    {
        $supplier = Supplier::create([
            'name' => 'GreenCore Pharmaceuticals',
            'address' => 'Quezon City, Metro Manila',
            'contact_person' => 'P. Domingo',
            'contact_no' => '02-8123-4455',
            'email' => 'orders@greencorepharma.ph',
        ]);

        $this->actingAs($this->admin())
            ->get(route('entities.edit', ['type' => 'supplier', 'id' => $supplier->id]))
            ->assertOk()
            ->assertSee('value="'.$supplier->name.'"', false)
            ->assertSee('value="'.$supplier->email.'"', false)
            ->assertSee(route('entities', ['tab' => 'suppliers']), false);
    }

    public function test_procuring_entity_can_be_updated(): void
    {
        $entity = ProcuringEntity::create([
            'name' => 'DOH Region VII',
            'type' => 'Regional Office',
        ]);

        $this->actingAs($this->admin())
            ->put(route('entities.update', ['type' => 'procuring_entity', 'id' => $entity->id]), [
                'name' => 'DOH Region VII Updated',
                'type' => 'Regional Office',
                'address' => 'Osmeña Blvd, Cebu City',
                'contact_person' => 'Dr. R. Villanueva',
                'contact_no' => '032-123-4567',
                'email' => 'doh.region7@gov.ph',
            ])
            ->assertRedirect(route('entities', ['tab' => 'entities']));

        $this->assertDatabaseHas('procuring_entities', [
            'id' => $entity->id,
            'name' => 'DOH Region VII Updated',
        ]);
    }

    public function test_procuring_entity_can_be_deleted(): void
    {
        $entity = ProcuringEntity::create([
            'name' => 'DOH Region VII',
            'type' => 'Regional Office',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('entities.destroy', ['type' => 'procuring_entity', 'id' => $entity->id]))
            ->assertRedirect(route('entities', ['tab' => 'entities']));

        $this->assertSoftDeleted('procuring_entities', [
            'id' => $entity->id,
        ]);
    }

    public function test_supplier_can_be_updated(): void
    {
        $supplier = Supplier::create([
            'name' => 'GreenCore Pharmaceuticals',
            'address' => 'Quezon City, Metro Manila',
        ]);

        $this->actingAs($this->admin())
            ->put(route('entities.update', ['type' => 'supplier', 'id' => $supplier->id]), [
                'name' => 'GreenCore Pharmaceuticals Updated',
                'address' => 'Makati City',
                'contact_person' => 'P. Domingo',
                'contact_no' => '02-8123-4455',
                'email' => 'orders@greencorepharma.ph',
            ])
            ->assertRedirect(route('entities', ['tab' => 'suppliers']));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'GreenCore Pharmaceuticals Updated',
        ]);
    }

    public function test_supplier_can_be_deleted(): void
    {
        $supplier = Supplier::create([
            'name' => 'GreenCore Pharmaceuticals',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('entities.destroy', ['type' => 'supplier', 'id' => $supplier->id]))
            ->assertRedirect(route('entities', ['tab' => 'suppliers']));

        $this->assertSoftDeleted('suppliers', [
            'id' => $supplier->id,
        ]);
    }
}
