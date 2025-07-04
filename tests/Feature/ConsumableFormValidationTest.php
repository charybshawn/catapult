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
use Livewire\Livewire;
use App\Filament\Resources\ConsumableResource\Pages\CreateConsumable;

class ConsumableFormValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create required lookup data
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
    public function consumable_form_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(CreateConsumable::class)
            ->fillForm([
                'name' => '', // Empty required field
                'consumable_type_id' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'consumable_type_id']);
    }

    /** @test */
    public function consumable_form_accepts_valid_seed_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create([
            'supplier_type_id' => SupplierType::where('code', 'seed')->first()->id,
        ]);

        $seedType = ConsumableType::where('code', 'seed')->first();

        Livewire::test(CreateConsumable::class)
            ->fillForm([
                'name' => 'Test Seed Variety',
                'consumable_type_id' => $seedType->id,
                'supplier_id' => $supplier->id,
                'total_quantity' => 1000,
                'consumed_quantity' => 0,
                'quantity_unit' => 'g',
                'lot_no' => 'TEST001',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('consumables', [
            'name' => 'Test Seed Variety',
            'total_quantity' => 1000,
            'quantity_unit' => 'g',
            'lot_no' => 'TEST001',
        ]);
    }

    /** @test */
    public function consumable_form_handles_different_quantity_units(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $seedType = ConsumableType::where('code', 'seed')->first();

        $validUnits = ['g', 'kg', 'l', 'ml', 'oz', 'lb', 'cm', 'm'];

        foreach ($validUnits as $unit) {
            Livewire::test(CreateConsumable::class)
                ->fillForm([
                    'name' => "Test Item {$unit}",
                    'consumable_type_id' => $seedType->id,
                    'supplier_id' => $supplier->id,
                    'total_quantity' => 500,
                    'consumed_quantity' => 0,
                    'quantity_unit' => $unit,
                    'lot_no' => "TEST{$unit}",
                    'is_active' => true,
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $this->assertDatabaseHas('consumables', [
                'name' => "Test Item {$unit}",
                'quantity_unit' => $unit,
            ]);
        }
    }

    /** @test */
    public function consumable_form_validates_numeric_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $seedType = ConsumableType::where('code', 'seed')->first();

        Livewire::test(CreateConsumable::class)
            ->fillForm([
                'name' => 'Test Seed',
                'consumable_type_id' => $seedType->id,
                'supplier_id' => $supplier->id,
                'total_quantity' => 'not-a-number',
                'consumed_quantity' => 'invalid',
                'quantity_unit' => 'g',
                'lot_no' => 'TEST001',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['total_quantity', 'consumed_quantity']);
    }

    /** @test */
    public function consumable_form_validates_positive_quantities(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $seedType = ConsumableType::where('code', 'seed')->first();

        Livewire::test(CreateConsumable::class)
            ->fillForm([
                'name' => 'Test Seed',
                'consumable_type_id' => $seedType->id,
                'supplier_id' => $supplier->id,
                'total_quantity' => -100,
                'consumed_quantity' => -50,
                'quantity_unit' => 'g',
                'lot_no' => 'TEST001',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['total_quantity', 'consumed_quantity']);
    }

    /** @test */
    public function consumable_form_does_not_accept_deprecated_unit_field(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $seedType = ConsumableType::where('code', 'seed')->first();

        // Try to create a consumable with both quantity_unit and the deprecated unit field
        $consumable = Consumable::create([
            'name' => 'Test Seed',
            'consumable_type_id' => $seedType->id,
            'supplier_id' => $supplier->id,
            'total_quantity' => 1000,
            'consumed_quantity' => 0,
            'quantity_unit' => 'g',
            'unit' => 'gram', // This should be ignored due to fillable restriction
            'lot_no' => 'TEST001',
            'is_active' => true,
        ]);

        // Verify the deprecated unit field is NOT set
        $this->assertNull($consumable->unit);
        // But quantity_unit should be set correctly
        $this->assertEquals('g', $consumable->quantity_unit);
    }

    /** @test */
    public function consumable_form_handles_lot_number_formatting(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $seedType = ConsumableType::where('code', 'seed')->first();

        Livewire::test(CreateConsumable::class)
            ->fillForm([
                'name' => 'Test Seed',
                'consumable_type_id' => $seedType->id,
                'supplier_id' => $supplier->id,
                'total_quantity' => 1000,
                'consumed_quantity' => 0,
                'quantity_unit' => 'g',
                'lot_no' => 'test-001-lower', // lowercase input
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Lot number should be automatically converted to uppercase
        $this->assertDatabaseHas('consumables', [
            'lot_no' => 'TEST-001-LOWER',
        ]);
    }

    /** @test */
    public function consumable_form_validates_relationship_constraints(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $seedType = ConsumableType::where('code', 'seed')->first();

        Livewire::test(CreateConsumable::class)
            ->fillForm([
                'name' => 'Test Seed',
                'consumable_type_id' => $seedType->id,
                'supplier_id' => 99999, // Non-existent supplier
                'total_quantity' => 1000,
                'consumed_quantity' => 0,
                'quantity_unit' => 'g',
                'lot_no' => 'TEST001',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasFormErrors(['supplier_id']);
    }

    /** @test */
    public function consumable_form_handles_soil_type_correctly(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $soilType = ConsumableType::where('code', 'soil')->first();

        Livewire::test(CreateConsumable::class)
            ->fillForm([
                'name' => 'Premium Potting Mix',
                'consumable_type_id' => $soilType->id,
                'supplier_id' => $supplier->id,
                'initial_stock' => 10,
                'consumed_quantity' => 0,
                'quantity_per_unit' => 50.0,
                'quantity_unit' => 'l',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('consumables', [
            'name' => 'Premium Potting Mix',
            'initial_stock' => 10,
            'quantity_per_unit' => 50.0,
            'quantity_unit' => 'l',
        ]);
    }

    /** @test */
    public function consumable_form_validates_boolean_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $seedType = ConsumableType::where('code', 'seed')->first();

        // Test with valid boolean values
        foreach ([true, false, 1, 0, '1', '0'] as $boolValue) {
            Livewire::test(CreateConsumable::class)
                ->fillForm([
                    'name' => 'Test Seed ' . $boolValue,
                    'consumable_type_id' => $seedType->id,
                    'supplier_id' => $supplier->id,
                    'total_quantity' => 1000,
                    'consumed_quantity' => 0,
                    'quantity_unit' => 'g',
                    'lot_no' => 'TEST' . $boolValue,
                    'is_active' => $boolValue,
                ])
                ->call('create')
                ->assertHasNoFormErrors();
        }
    }

    /** @test */
    public function consumable_form_handles_optional_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::factory()->create();
        $seedType = ConsumableType::where('code', 'seed')->first();

        Livewire::test(CreateConsumable::class)
            ->fillForm([
                'name' => 'Test Seed',
                'consumable_type_id' => $seedType->id,
                'supplier_id' => $supplier->id,
                'total_quantity' => 1000,
                'consumed_quantity' => 0,
                'quantity_unit' => 'g',
                'lot_no' => 'TEST001',
                'is_active' => true,
                'notes' => 'Optional test notes',
                'cost_per_unit' => 2.50,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('consumables', [
            'name' => 'Test Seed',
            'notes' => 'Optional test notes',
            'cost_per_unit' => 2.50,
        ]);
    }
}