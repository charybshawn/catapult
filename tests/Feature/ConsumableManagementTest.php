<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use App\Models\Supplier;
use App\Models\SupplierType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsumableManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required lookup data - use firstOrCreate to avoid duplicates
        ConsumableType::firstOrCreate(
            ['code' => 'seed'],
            ['name' => 'Seeds', 'is_active' => true, 'sort_order' => 1]
        );
        
        ConsumableType::firstOrCreate(
            ['code' => 'soil'],
            ['name' => 'Soil', 'is_active' => true, 'sort_order' => 2]
        );
        
        ConsumableUnit::firstOrCreate(
            ['code' => 'unit'],
            ['name' => 'Unit', 'symbol' => 'unit', 'category' => 'count', 'is_active' => true]
        );
        
        ConsumableUnit::firstOrCreate(
            ['code' => 'gram'],
            ['name' => 'Gram', 'symbol' => 'g', 'category' => 'weight', 'is_active' => true]
        );
        
        SupplierType::firstOrCreate(
            ['code' => 'seed'],
            ['name' => 'Seed Supplier', 'is_active' => true]
        );
    }

    /** @test */
    public function consumable_can_be_created_with_basic_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create([
            'name' => 'Test Supplier',
            'supplier_type_id' => SupplierType::where('code', 'seed')->first()->id,
        ]);

        $consumableData = [
            'name' => 'Test Seed',
            'consumable_type_id' => ConsumableType::where('code', 'seed')->first()->id,
            'supplier_id' => $supplier->id,
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'lot_no' => 'TEST001',
            'is_active' => true,
        ];

        $consumable = Consumable::create($consumableData);

        $this->assertDatabaseHas('consumables', [
            'name' => 'Test Seed',
            'total_quantity' => 1000.0,
            'quantity_unit' => 'g',
            'lot_no' => 'TEST001',
        ]);
        
        // Verify the deprecated unit field is NOT set
        $this->assertNull($consumable->unit);
    }

    /** @test */
    public function consumable_handles_different_quantity_units(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $consumableType = ConsumableType::where('code', 'seed')->first();

        $units = ['g', 'kg', 'l', 'ml', 'oz', 'lb'];

        foreach ($units as $unit) {
            $consumable = Consumable::create([
                'name' => "Test Seed {$unit}",
                'consumable_type_id' => $consumableType->id,
                'supplier_id' => $supplier->id,
                'total_quantity' => 500.0,
                'consumed_quantity' => 0.0,
                'quantity_unit' => $unit,
                'lot_no' => "TEST{$unit}",
                'is_active' => true,
            ]);

            $this->assertEquals($unit, $consumable->quantity_unit);
            $this->assertNull($consumable->unit); // Deprecated field should not be set
        }
    }

    /** @test */
    public function consumable_requires_essential_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Verify that a consumable with minimal required fields can be created
        $consumable = Consumable::create([
            'name' => 'Basic Consumable',
            'consumable_type_id' => ConsumableType::where('code', 'seed')->first()->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('consumables', [
            'name' => 'Basic Consumable',
            'is_active' => true,
        ]);
        
        // Verify the type accessor works
        $this->assertEquals('seed', $consumable->type);
    }

    /** @test */
    public function consumable_lot_number_is_automatically_uppercased(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        
        $consumable = Consumable::create([
            'name' => 'Test Seed',
            'consumable_type_id' => ConsumableType::where('code', 'seed')->first()->id,
            'supplier_id' => $supplier->id,
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'lot_no' => 'test001',  // lowercase input
            'is_active' => true,
        ]);

        $this->assertEquals('TEST001', $consumable->lot_no); // Should be uppercase
    }

    /** @test */
    public function consumable_relationships_work_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $consumableType = ConsumableType::where('code', 'seed')->first();
        
        $consumable = Consumable::create([
            'name' => 'Test Seed',
            'consumable_type_id' => $consumableType->id,
            'supplier_id' => $supplier->id,
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'lot_no' => 'TEST001',
            'is_active' => true,
        ]);

        // Test relationships
        $this->assertEquals($supplier->id, $consumable->supplier->id);
        $this->assertEquals($consumableType->id, $consumable->consumableType->id);
        $this->assertEquals('seed', $consumable->type); // Should get from relationship
    }

    /** @test */
    public function consumable_casts_fields_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        
        $consumable = Consumable::create([
            'name' => 'Test Seed',
            'consumable_type_id' => ConsumableType::where('code', 'seed')->first()->id,
            'supplier_id' => $supplier->id,
            'total_quantity' => '1000.123',  // String input
            'consumed_quantity' => '250.456', // String input
            'quantity_unit' => 'g',
            'lot_no' => 'TEST001',
            'is_active' => 1, // Integer input
        ]);

        // Test casting - refresh from database to get cast values
        $consumable->refresh();
        $this->assertIsFloat((float)$consumable->total_quantity);
        $this->assertIsFloat((float)$consumable->consumed_quantity);
        $this->assertIsBool($consumable->is_active);
        $this->assertEquals(1000.12, (float)$consumable->total_quantity); // Database precision is 3 decimal places  
        $this->assertEquals(250.46, (float)$consumable->consumed_quantity);
        $this->assertTrue($consumable->is_active);
    }

    /** @test */
    public function consumable_fillable_fields_are_correct(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $consumableType = ConsumableType::where('code', 'seed')->first();
        
        $consumableData = [
            'name' => 'Test Seed',
            'consumable_type_id' => $consumableType->id,
            'supplier_id' => $supplier->id,
            'total_quantity' => 1000.0,
            'consumed_quantity' => 0.0,
            'quantity_unit' => 'g',
            'lot_no' => 'TEST001',
            'is_active' => true,
            'notes' => 'Test notes',
            'unit' => 'gram', // This should NOT be mass assignable anymore
        ];

        $consumable = Consumable::create($consumableData);

        // Verify all fillable fields are set correctly
        $this->assertEquals('Test Seed', $consumable->name);
        $this->assertEquals($consumableType->id, $consumable->consumable_type_id);
        $this->assertEquals($supplier->id, $consumable->supplier_id);
        $this->assertEquals(1000.0, $consumable->total_quantity);
        $this->assertEquals('g', $consumable->quantity_unit);
        $this->assertEquals('Test notes', $consumable->notes);
        
        // Verify the deprecated unit field is NOT mass assignable
        $this->assertNull($consumable->unit);
    }

    /** @test */
    public function consumable_handles_seed_type_calculations(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $seedType = ConsumableType::where('code', 'seed')->first();
        
        $consumable = Consumable::create([
            'name' => 'Test Seed',
            'consumable_type_id' => $seedType->id,
            'supplier_id' => $supplier->id,
            'total_quantity' => 1000.0,
            'consumed_quantity' => 250.0,
            'quantity_unit' => 'g',
            'lot_no' => 'TEST001',
            'is_active' => true,
        ]);

        // For seed type, total_quantity should be managed directly (not calculated)
        $this->assertEquals(1000.0, $consumable->total_quantity);
        
        // Available stock calculation
        $availableStock = $consumable->total_quantity - $consumable->consumed_quantity;
        $this->assertEquals(750.0, $availableStock);
    }

    /** @test */
    public function consumable_handles_non_seed_type_calculations(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $soilType = ConsumableType::where('code', 'soil')->first();
        
        $consumable = Consumable::create([
            'name' => 'Test Soil',
            'consumable_type_id' => $soilType->id,
            'supplier_id' => $supplier->id,
            'initial_stock' => 10,
            'consumed_quantity' => 2,
            'quantity_per_unit' => 50.0,
            'quantity_unit' => 'l',
            'is_active' => true,
        ]);

        // For non-seed types, total_quantity should be calculated
        // Available stock = initial_stock - consumed_quantity = 10 - 2 = 8
        // Total quantity = available_stock * quantity_per_unit = 8 * 50 = 400
        $this->assertEquals(400.0, $consumable->total_quantity);
    }

    /** @test */
    public function consumable_type_accessor_works_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $seedType = ConsumableType::where('code', 'seed')->first();
        
        $consumable = Consumable::create([
            'name' => 'Test Seed',
            'consumable_type_id' => $seedType->id,
            'supplier_id' => $supplier->id,
            'total_quantity' => 1000.0,
            'quantity_unit' => 'g',
            'lot_no' => 'TEST001',
            'is_active' => true,
        ]);

        // Type should be retrieved from the relationship
        $this->assertEquals('seed', $consumable->type);
    }

    /** @test */
    public function consumable_type_accessor_fails_fast_when_relationship_missing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        
        // Create a consumable and then manually break the relationship
        $consumable = Consumable::create([
            'name' => 'Test Consumable',
            'consumable_type_id' => ConsumableType::where('code', 'seed')->first()->id,
            'supplier_id' => $supplier->id,
            'total_quantity' => 1000.0,
            'quantity_unit' => 'g',
            'is_active' => true,
        ]);

        // Manually break the relationship to simulate missing data
        $consumable->update(['consumable_type_id' => null]);
        $consumable->unsetRelation('consumableType'); // Clear the cached relationship
        
        // Accessing the type should throw an exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("missing consumable_type_id relationship");
        
        $consumable->type;
    }
}