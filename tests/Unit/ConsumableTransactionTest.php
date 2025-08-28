<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ConsumableTransaction;
use App\Models\Consumable;
use App\Models\User;
use App\Models\ConsumableType;

/**
 * Unit tests for ConsumableTransaction model in agricultural inventory management.
 * 
 * Tests comprehensive consumable transaction functionality for agricultural supply tracking
 * including seeds, soil, packaging, and other production materials. Validates transaction
 * types, quantity tracking, inventory balance calculations, and metadata management
 * for microgreens production workflows.
 *
 * @covers \App\Models\ConsumableTransaction
 * @group unit
 * @group consumables
 * @group inventory
 * @group agricultural-testing
 * 
 * @business_context Agricultural consumable inventory tracking and transaction logging
 * @test_category Unit tests for consumable transaction model functionality
 * @agricultural_workflow Inventory management for seeds, soil, packaging materials
 */
class ConsumableTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a seed consumable type for testing
        ConsumableType::factory()->create([
            'code' => 'seed',
            'name' => 'Seeds',
            'is_active' => true,
        ]);
    }

    /**
     * Test consumable transaction creation for agricultural inventory tracking.
     * 
     * Validates that consumable transactions can be created with proper agricultural
     * context including consumption tracking, balance calculations, and user attribution
     * for microgreens production supply management.
     *
     * @test
     * @return void
     * @agricultural_scenario Seed consumption during crop seeding process
     * @business_validation Ensures transactions properly track inventory changes
     */
    public function test_consumable_transaction_can_be_created()
    {
        $consumable = Consumable::factory()->create();
        $user = User::factory()->create();

        $transaction = ConsumableTransaction::create([
            'consumable_id' => $consumable->id,
            'type' => ConsumableTransaction::TYPE_CONSUMPTION,
            'quantity' => -50.5,
            'balance_after' => 449.5,
            'user_id' => $user->id,
            'notes' => 'Test consumption',
        ]);

        $this->assertDatabaseHas('consumable_transactions', [
            'consumable_id' => $consumable->id,
            'type' => 'consumption',
            'quantity' => -50.5,
            'balance_after' => 449.5,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test validation of consumable transaction types for agricultural workflows.
     * 
     * Validates that all required transaction types are available for agricultural
     * inventory management including consumption, additions, waste tracking, and
     * transfers between production stages or locations.
     *
     * @test
     * @return void
     * @agricultural_scenario Complete inventory transaction type coverage
     * @business_logic Ensures all agricultural inventory scenarios are supported
     */
    public function test_transaction_types_are_valid()
    {
        $validTypes = ConsumableTransaction::getValidTypes();
        
        $expectedTypes = [
            'consumption',
            'addition',
            'adjustment',
            'waste',
            'expiration',
            'transfer_out',
            'transfer_in',
            'initial',
        ];

        $this->assertEquals($expectedTypes, $validTypes);
    }

    /**
     * Test inbound transaction identification for agricultural supply management.
     * 
     * Validates that transactions adding inventory (additions, transfers in) are
     * properly identified as inbound for agricultural supply chain tracking and
     * inventory balance calculations in microgreens production.
     *
     * @test
     * @return void
     * @agricultural_scenario New seed shipment arrival increasing inventory
     * @business_logic Inbound transactions increase available supply levels
     */
    public function test_is_inbound_transaction()
    {
        $additionTransaction = ConsumableTransaction::factory()->addition()->make();
        $this->assertTrue($additionTransaction->isInbound());

        $consumptionTransaction = ConsumableTransaction::factory()->consumption()->make();
        $this->assertFalse($consumptionTransaction->isInbound());
    }

    /**
     * Test outbound transaction identification for agricultural consumption tracking.
     * 
     * Validates that transactions removing inventory (consumption, waste, transfers out)
     * are properly identified as outbound for agricultural production tracking and
     * cost calculation in microgreens farming operations.
     *
     * @test
     * @return void
     * @agricultural_scenario Seed usage during crop planting reducing inventory
     * @business_logic Outbound transactions decrease available supply levels
     */
    public function test_is_outbound_transaction()
    {
        $consumptionTransaction = ConsumableTransaction::factory()->consumption()->make();
        $this->assertTrue($consumptionTransaction->isOutbound());

        $additionTransaction = ConsumableTransaction::factory()->addition()->make();
        $this->assertFalse($additionTransaction->isOutbound());
    }

    /**
     * Test transaction type label generation for agricultural UI display.
     * 
     * Validates that transaction types have proper human-readable labels for
     * agricultural inventory management interfaces, providing clear context
     * for farming operations and supply tracking workflows.
     *
     * @test
     * @return void
     * @agricultural_scenario User interface displaying transaction history
     * @business_logic Labels provide clear context for agricultural operations
     */
    public function test_get_type_label()
    {
        $transaction = ConsumableTransaction::factory()->consumption()->make();
        $this->assertEquals('Used in Production', $transaction->getTypeLabel());

        $transaction = ConsumableTransaction::factory()->addition()->make();
        $this->assertEquals('Stock Added', $transaction->getTypeLabel());
    }

    /**
     * Test transaction impact display formatting for agricultural inventory reporting.
     * 
     * Validates that transaction quantity impacts are properly formatted with
     * appropriate signs (+/-) for agricultural inventory reports and dashboards,
     * ensuring clear visibility of inventory changes.
     *
     * @test
     * @return void
     * @agricultural_scenario Inventory report showing quantity changes
     * @business_logic Formatted display helps track inventory movements
     */
    public function test_get_impact_display()
    {
        $consumptionTransaction = ConsumableTransaction::factory()->make([
            'quantity' => -25.750
        ]);
        $this->assertEquals('-25.750', $consumptionTransaction->getImpact());

        $additionTransaction = ConsumableTransaction::factory()->make([
            'quantity' => 100.000
        ]);
        $this->assertEquals('+100.000', $additionTransaction->getImpact());
    }

    /**
     * Test consumption transaction creation for agricultural production tracking.
     * 
     * Validates that consumption transactions can be created with complete
     * agricultural context including crop references, lot tracking, and metadata
     * for seed usage during microgreens production processes.
     *
     * @test
     * @return void
     * @agricultural_scenario Seed consumption during basil crop seeding
     * @business_validation Tracks material usage with complete traceability
     */
    public function test_create_consumption_transaction()
    {
        $consumable = Consumable::factory()->create();
        $user = User::factory()->create();

        $transaction = ConsumableTransaction::createConsumption(
            $consumable,
            50.5,
            449.5,
            $user,
            'crop',
            123,
            'Used for basil seeding',
            ['lot_number' => 'ABC123']
        );

        $this->assertEquals('consumption', $transaction->type);
        $this->assertEquals(-50.5, $transaction->quantity);
        $this->assertEquals(449.5, $transaction->balance_after);
        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals('crop', $transaction->reference_type);
        $this->assertEquals(123, $transaction->reference_id);
        $this->assertEquals('Used for basil seeding', $transaction->notes);
        $this->assertEquals(['lot_number' => 'ABC123'], $transaction->metadata);
    }

    /**
     * Test addition transaction creation for agricultural supply management.
     * 
     * Validates that addition transactions can be created with complete
     * agricultural context including supplier information, lot tracking, and
     * metadata for new inventory arrivals in microgreens production.
     *
     * @test
     * @return void
     * @agricultural_scenario New seed shipment arrival with supplier lot tracking
     * @business_validation Tracks supply additions with complete traceability
     */
    public function test_create_addition_transaction()
    {
        $consumable = Consumable::factory()->create();
        $user = User::factory()->create();

        $transaction = ConsumableTransaction::createAddition(
            $consumable,
            1000.0,
            1449.5,
            $user,
            'order',
            456,
            'New shipment arrived',
            ['supplier_lot' => 'XYZ789']
        );

        $this->assertEquals('addition', $transaction->type);
        $this->assertEquals(1000.0, $transaction->quantity);
        $this->assertEquals(1449.5, $transaction->balance_after);
        $this->assertEquals($user->id, $transaction->user_id);
        $this->assertEquals('order', $transaction->reference_type);
        $this->assertEquals(456, $transaction->reference_id);
        $this->assertEquals('New shipment arrived', $transaction->notes);
        $this->assertEquals(['supplier_lot' => 'XYZ789'], $transaction->metadata);
    }

    /**
     * Test consumable relationship for agricultural inventory tracking.
     * 
     * Validates that transactions are properly linked to consumable items
     * for agricultural inventory management, ensuring proper relationship
     * integrity for seeds, soil, packaging, and other production materials.
     *
     * @test
     * @return void
     * @agricultural_scenario Transaction linked to specific seed variety
     * @relationship_validation Ensures transaction-consumable data integrity
     */
    public function test_belongs_to_consumable()
    {
        $consumable = Consumable::factory()->create();
        $transaction = ConsumableTransaction::factory()->create([
            'consumable_id' => $consumable->id
        ]);

        $this->assertInstanceOf(Consumable::class, $transaction->consumable);
        $this->assertEquals($consumable->id, $transaction->consumable->id);
    }

    /**
     * Test user relationship for agricultural transaction accountability.
     * 
     * Validates that transactions are properly attributed to users for
     * agricultural operation accountability and audit trails, tracking
     * who performed inventory actions in microgreens production.
     *
     * @test
     * @return void
     * @agricultural_scenario Farm worker performing inventory transaction
     * @relationship_validation Ensures transaction-user accountability
     */
    public function test_belongs_to_user()
    {
        $user = User::factory()->create();
        $transaction = ConsumableTransaction::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertInstanceOf(User::class, $transaction->user);
        $this->assertEquals($user->id, $transaction->user->id);
    }

    /**
     * Test metadata casting for agricultural transaction context storage.
     * 
     * Validates that transaction metadata is properly cast to arrays for
     * storing additional agricultural context like lot numbers, supplier
     * information, and production details in microgreens farming operations.
     *
     * @test
     * @return void
     * @agricultural_scenario Lot number and supplier metadata storage
     * @data_validation Ensures metadata maintains proper data structure
     */
    public function test_metadata_is_cast_to_array()
    {
        $transaction = ConsumableTransaction::factory()->create([
            'metadata' => ['key' => 'value', 'number' => 123]
        ]);

        $this->assertIsArray($transaction->metadata);
        $this->assertEquals('value', $transaction->metadata['key']);
        $this->assertEquals(123, $transaction->metadata['number']);
    }
}
