<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Consumable;
use App\Models\ConsumableTransaction;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Populate initial transactions for existing consumables
        $consumables = Consumable::where('is_active', true)->get();
        
        foreach ($consumables as $consumable) {
            // Skip if transactions already exist for this consumable
            if (ConsumableTransaction::where('consumable_id', $consumable->id)->exists()) {
                continue;
            }

            // Calculate current stock using legacy method
            $currentStock = $this->calculateLegacyStock($consumable);
            
            if ($currentStock <= 0) {
                continue; // Skip consumables with no stock
            }

            // Create initial stock transaction
            ConsumableTransaction::create([
                'consumable_id' => $consumable->id,
                'type' => ConsumableTransaction::TYPE_INITIAL,
                'quantity' => $currentStock,
                'balance_after' => $currentStock,
                'user_id' => null, // System migration
                'notes' => 'Initial stock migrated from legacy system',
                'metadata' => [
                    'initial_stock' => $consumable->initial_stock,
                    'consumed_quantity' => $consumable->consumed_quantity,
                    'total_quantity' => $consumable->total_quantity,
                    'migrated_at' => now()->toDateTimeString(),
                ],
                'created_at' => $consumable->created_at ?? now(),
                'updated_at' => $consumable->created_at ?? now(),
            ]);

            // If there's consumed quantity, create a consumption transaction
            if ($consumable->consumed_quantity > 0) {
                $balanceAfterConsumption = max(0, $currentStock - $consumable->consumed_quantity);
                
                ConsumableTransaction::create([
                    'consumable_id' => $consumable->id,
                    'type' => ConsumableTransaction::TYPE_CONSUMPTION,
                    'quantity' => -$consumable->consumed_quantity, // Negative for consumption
                    'balance_after' => $balanceAfterConsumption,
                    'user_id' => null, // System migration
                    'notes' => 'Historical consumption migrated from legacy system',
                    'metadata' => [
                        'consumed_quantity' => $consumable->consumed_quantity,
                        'migrated_at' => now()->toDateTimeString(),
                    ],
                    'created_at' => $consumable->updated_at ?? now(),
                    'updated_at' => $consumable->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all transactions that were created during migration
        ConsumableTransaction::where('notes', 'like', '%migrated from legacy system%')->delete();
    }

    /**
     * Calculate current stock using legacy method.
     */
    private function calculateLegacyStock(Consumable $consumable): float
    {
        // For seeds, use total_quantity directly
        if ($consumable->consumableType && $consumable->consumableType->code === 'seed') {
            return $consumable->total_quantity ?? 0;
        }
        
        // For other consumables, use initial_stock - consumed_quantity
        return max(0, ($consumable->initial_stock ?? 0) - ($consumable->consumed_quantity ?? 0));
    }
};
