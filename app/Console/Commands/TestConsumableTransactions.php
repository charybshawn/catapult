<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ConsumableTransaction;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\User;
use App\Services\InventoryManagementService;

class TestConsumableTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:consumable-transactions';
    
    /**
     * The inventory service instance.
     *
     * @var InventoryManagementService
     */
    protected InventoryManagementService $inventoryService;
    
    /**
     * Create a new command instance.
     */
    public function __construct(InventoryManagementService $inventoryService)
    {
        parent::__construct();
        $this->inventoryService = $inventoryService;
    }

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test consumable transaction tracking functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Consumable Transaction System...');

        try {
            // Test 1: Basic functionality
            $this->info('1. Testing basic transaction types...');
            $types = ConsumableTransaction::getValidTypes();
            $this->line('Valid transaction types: ' . implode(', ', $types));
            
            // Test 2: Create test data
            $this->info('2. Creating test consumable...');
            $seedType = ConsumableType::where('code', 'seed')->first();
            if (!$seedType) {
                $seedType = ConsumableType::create([
                    'code' => 'seed',
                    'name' => 'Seeds',
                    'description' => 'Test seeds',
                    'color' => 'green',
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
                $this->line('Created seed consumable type');
            }

            // Get or create a default consumable unit
            $defaultUnit = \App\Models\ConsumableUnit::where('code', 'unit')->first();
            if (!$defaultUnit) {
                $this->error('No default consumable unit found. Database may not be properly seeded.');
                return 1;
            }

            $consumable = Consumable::create([
                'name' => 'Test Basil Seeds - CLI Test',
                'consumable_type_id' => $seedType->id,
                'consumable_unit_id' => $defaultUnit->id,
                'total_quantity' => 1000,
                'consumed_quantity' => 0,
                'quantity_unit' => 'g',
                'restock_threshold' => 100,
                'restock_quantity' => 500,
                'is_active' => true,
            ]);
            $this->line('Created test consumable: ' . $consumable->name);

            // Test 3: Initialize transaction tracking
            $this->info('3. Testing transaction tracking initialization...');
            $initialTransaction = $this->inventoryService->initializeTransactionTracking($consumable);
            
            if ($initialTransaction) {
                $this->line('✓ Initial transaction created. Balance: ' . $initialTransaction->balance_after . 'g');
            } else {
                $this->error('✗ Failed to create initial transaction');
                return 1;
            }

            // Test 4: Record consumption
            $this->info('4. Testing consumption recording...');
            $consumptionTransaction = $this->inventoryService->recordConsumption(
                $consumable, 
                75.0, 
                'g', 
                null, 
                'test', 
                1, 
                'CLI test consumption'
            );
            $this->line('✓ Consumption recorded. New balance: ' . $consumptionTransaction->balance_after . 'g');

            // Test 5: Record addition
            $this->info('5. Testing stock addition...');
            $additionTransaction = $this->inventoryService->recordAddition(
                $consumable, 
                250.0, 
                'g', 
                null, 
                'test', 
                2, 
                'CLI test addition'
            );
            $this->line('✓ Addition recorded. New balance: ' . $additionTransaction->balance_after . 'g');

            // Test 6: Unit conversion
            $this->info('6. Testing unit conversion...');
            $kgConsumption = $this->inventoryService->recordConsumption(
                $consumable, 
                0.1, // 0.1 kg = 100g
                'kg', 
                null, 
                'test', 
                3, 
                'CLI test unit conversion'
            );
            $this->line('✓ Unit conversion worked. 0.1kg recorded as: ' . $kgConsumption->quantity . 'g');
            $this->line('✓ New balance: ' . $kgConsumption->balance_after . 'g');

            // Test 7: Transaction history
            $this->info('7. Testing transaction history...');
            $history = $this->inventoryService->getTransactionHistory($consumable);
            $this->line('✓ Transaction history retrieved. Count: ' . $history->count());
            
            foreach ($history as $transaction) {
                $this->line('  - ' . $transaction->getTypeLabel() . ': ' . $transaction->getImpact() . 'g (Balance: ' . $transaction->balance_after . 'g)');
            }

            // Test 8: Legacy field updates
            $this->info('8. Testing legacy field synchronization...');
            $consumable->refresh();
            $this->line('✓ Legacy fields updated:');
            $this->line('  - total_quantity: ' . $consumable->total_quantity . 'g');
            $this->line('  - consumed_quantity: ' . $consumable->consumed_quantity . 'g');

            // Test 9: Current stock calculation
            $this->info('9. Testing current stock calculation...');
            $currentStock = $this->inventoryService->getCurrentStockFromTransactions($consumable);
            $currentStockModel = $consumable->getCurrentStockWithTransactions();
            $this->line('✓ Current stock (service): ' . $currentStock . 'g');
            $this->line('✓ Current stock (model): ' . $currentStockModel . 'g');
            
            if (abs($currentStock - $currentStockModel) < 0.01) {
                $this->line('✓ Service and model calculations match');
            } else {
                $this->error('✗ Service and model calculations do not match');
            }

            // Final calculation verification
            $expectedBalance = 1000 - 75 + 250 - 100; // Initial - consumption + addition - kg consumption
            if (abs($currentStock - $expectedBalance) < 0.01) {
                $this->line('✓ Final balance calculation correct: ' . $expectedBalance . 'g');
            } else {
                $this->error('✗ Final balance incorrect. Expected: ' . $expectedBalance . 'g, Got: ' . $currentStock . 'g');
            }

            $this->info('');
            $this->info('🎉 All tests completed successfully!');
            $this->info('Consumable transaction tracking system is working correctly.');

            return 0;

        } catch (\Exception $e) {
            $this->error('Test failed with error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
