<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ConsumableNameAccessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a seed consumable type
        $this->seedType = ConsumableType::create([
            'name' => 'Seed',
            'code' => 'seed',
            'is_active' => true
        ]);
        
        // Create a non-seed consumable type
        $this->soilType = ConsumableType::create([
            'name' => 'Soil',
            'code' => 'soil',
            'is_active' => true
        ]);
    }

    public function test_seed_consumable_name_computed_from_catalog_and_cultivar(): void
    {
        // Create master catalog and cultivar
        $catalog = MasterSeedCatalog::create([
            'common_name' => 'Basil',
            'is_active' => true
        ]);
        
        $cultivar = MasterCultivar::create([
            'master_seed_catalog_id' => $catalog->id,
            'cultivar_name' => 'Genovese',
            'is_active' => true
        ]);
        
        // Create seed consumable
        $consumable = Consumable::create([
            'consumable_type_id' => $this->seedType->id,
            'master_seed_catalog_id' => $catalog->id,
            'master_cultivar_id' => $cultivar->id,
            'name' => null, // Should be computed
            'initial_stock' => 1,
            'consumed_quantity' => 0,
            'is_active' => true
        ]);
        
        // Refresh with relationships loaded
        $consumable = $consumable->fresh(['masterSeedCatalog', 'masterCultivar', 'consumableType']);
        
        // Test computed name
        $this->assertEquals('Basil (Genovese)', $consumable->name);
    }
    
    public function test_seed_consumable_name_fallback_to_cultivar_field(): void
    {
        // Create master catalog only
        $catalog = MasterSeedCatalog::create([
            'common_name' => 'Tomato',
            'is_active' => true
        ]);
        
        // Create seed consumable and directly set cultivar field after creation (since it's not fillable)
        $consumable = Consumable::create([
            'consumable_type_id' => $this->seedType->id,
            'master_seed_catalog_id' => $catalog->id,
            'name' => null,
            'initial_stock' => 1,
            'consumed_quantity' => 0,
            'is_active' => true
        ]);
        
        // Set cultivar field directly (bypassing fillable restriction)
        $consumable->cultivar = 'Cherry';
        $consumable->save();
        
        // Refresh with relationships loaded
        $consumable = $consumable->fresh(['masterSeedCatalog', 'masterCultivar', 'consumableType']);
        
        // Test computed name using cultivar field
        $this->assertEquals('Tomato (Cherry)', $consumable->name);
    }
    
    public function test_non_seed_consumable_uses_stored_name(): void
    {
        // Create non-seed consumable
        $consumable = Consumable::create([
            'consumable_type_id' => $this->soilType->id,
            'name' => 'Premium Potting Mix',
            'initial_stock' => 5,
            'consumed_quantity' => 0,
            'is_active' => true
        ]);
        
        // Test stored name is used
        $this->assertEquals('Premium Potting Mix', $consumable->name);
    }
    
    public function test_seed_consumable_incomplete_data_fallback(): void
    {
        // Create seed consumable with minimal data
        $consumable = Consumable::create([
            'consumable_type_id' => $this->seedType->id,
            'name' => 'Original Name',
            'initial_stock' => 1,
            'consumed_quantity' => 0,
            'is_active' => true
        ]);
        
        // Should fall back to stored name when relationships are missing
        $this->assertEquals('Original Name', $consumable->name);
    }
    
    public function test_relationship_integrity_checks(): void
    {
        // Create master catalog and cultivar
        $catalog = MasterSeedCatalog::create([
            'common_name' => 'Lettuce',
            'is_active' => true
        ]);
        
        $cultivar = MasterCultivar::create([
            'master_seed_catalog_id' => $catalog->id,
            'cultivar_name' => 'Buttercrunch',
            'is_active' => true
        ]);
        
        // Valid seed consumable
        $validConsumable = Consumable::create([
            'consumable_type_id' => $this->seedType->id,
            'master_seed_catalog_id' => $catalog->id,
            'master_cultivar_id' => $cultivar->id,
            'initial_stock' => 1,
            'consumed_quantity' => 0,
            'is_active' => true
        ]);
        
        // Invalid seed consumable (missing relationships)
        $invalidConsumable = Consumable::create([
            'consumable_type_id' => $this->seedType->id,
            'initial_stock' => 1,
            'consumed_quantity' => 0,
            'is_active' => true
        ]);
        
        // Non-seed consumable (doesn't need relationships)
        $nonSeedConsumable = Consumable::create([
            'consumable_type_id' => $this->soilType->id,
            'name' => 'Test Soil',
            'initial_stock' => 1,
            'consumed_quantity' => 0,
            'is_active' => true
        ]);
        
        // Test relationship checks
        $this->assertTrue($validConsumable->hasValidSeedRelationships());
        $this->assertFalse($invalidConsumable->hasValidSeedRelationships());
        $this->assertTrue($nonSeedConsumable->hasValidSeedRelationships());
        
        // Test error reporting
        $this->assertEmpty($validConsumable->getSeedRelationshipErrors());
        $this->assertNotEmpty($invalidConsumable->getSeedRelationshipErrors());
        $this->assertEmpty($nonSeedConsumable->getSeedRelationshipErrors());
    }
}
