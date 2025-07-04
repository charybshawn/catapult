<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\ConsumableTransaction;
use App\Models\Consumable;
use App\Models\User;
use App\Models\ConsumableType;

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

    public function test_is_inbound_transaction()
    {
        $additionTransaction = ConsumableTransaction::factory()->addition()->make();
        $this->assertTrue($additionTransaction->isInbound());

        $consumptionTransaction = ConsumableTransaction::factory()->consumption()->make();
        $this->assertFalse($consumptionTransaction->isInbound());
    }

    public function test_is_outbound_transaction()
    {
        $consumptionTransaction = ConsumableTransaction::factory()->consumption()->make();
        $this->assertTrue($consumptionTransaction->isOutbound());

        $additionTransaction = ConsumableTransaction::factory()->addition()->make();
        $this->assertFalse($additionTransaction->isOutbound());
    }

    public function test_get_type_label()
    {
        $transaction = ConsumableTransaction::factory()->consumption()->make();
        $this->assertEquals('Used in Production', $transaction->getTypeLabel());

        $transaction = ConsumableTransaction::factory()->addition()->make();
        $this->assertEquals('Stock Added', $transaction->getTypeLabel());
    }

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

    public function test_belongs_to_consumable()
    {
        $consumable = Consumable::factory()->create();
        $transaction = ConsumableTransaction::factory()->create([
            'consumable_id' => $consumable->id
        ]);

        $this->assertInstanceOf(Consumable::class, $transaction->consumable);
        $this->assertEquals($consumable->id, $transaction->consumable->id);
    }

    public function test_belongs_to_user()
    {
        $user = User::factory()->create();
        $transaction = ConsumableTransaction::factory()->create([
            'user_id' => $user->id
        ]);

        $this->assertInstanceOf(User::class, $transaction->user);
        $this->assertEquals($user->id, $transaction->user->id);
    }

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
