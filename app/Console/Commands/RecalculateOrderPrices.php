<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateOrderPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:recalculate-prices 
                            {--order= : Specific order ID to recalculate}
                            {--customer= : Recalculate all orders for a specific customer ID}
                            {--wholesale : Recalculate only wholesale customer orders}
                            {--from= : Start date for order filtering (Y-m-d)}
                            {--to= : End date for order filtering (Y-m-d)}
                            {--dry-run : Show what would be changed without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate order prices based on current customer wholesale discounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting order price recalculation...');
        
        $query = Order::with(['user', 'orderItems.product.priceVariations']);
        
        // Apply filters
        if ($orderId = $this->option('order')) {
            $query->where('id', $orderId);
        }
        
        if ($customerId = $this->option('customer')) {
            $query->where('user_id', $customerId);
        }
        
        if ($this->option('wholesale')) {
            $query->whereHas('user', function ($q) {
                $q->where('customer_type', 'wholesale');
            });
        }
        
        if ($from = $this->option('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        
        if ($to = $this->option('to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        
        // Exclude cancelled orders and templates
        $query->whereNotIn('status', ['cancelled', 'template']);
        
        $orders = $query->get();
        $this->info("Found {$orders->count()} orders to process.");
        
        $updated = 0;
        $totalSavings = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($orders as $order) {
                $changes = $this->recalculateOrderPrices($order);
                
                if ($changes['hasChanges']) {
                    $updated++;
                    $totalSavings += $changes['totalDifference'];
                    
                    $this->line("Order #{$order->id} - Customer: {$order->user->name}");
                    $this->line("  Old Total: $" . number_format($changes['oldTotal'], 2));
                    $this->line("  New Total: $" . number_format($changes['newTotal'], 2));
                    $this->line("  Difference: $" . number_format($changes['totalDifference'], 2));
                    
                    if ($this->option('dry-run')) {
                        $this->info("  [DRY RUN] Would update {$changes['itemsUpdated']} items");
                    } else {
                        $this->info("  Updated {$changes['itemsUpdated']} items");
                    }
                    $this->line("");
                }
            }
            
            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn("DRY RUN COMPLETE - No changes were saved.");
            } else {
                DB::commit();
                $this->info("Successfully updated {$updated} orders.");
            }
            
            if ($updated > 0) {
                $this->info("Total customer savings: $" . number_format($totalSavings, 2));
            } else {
                $this->info("No orders needed price updates.");
            }
            
        } catch (Exception $e) {
            DB::rollBack();
            $this->error("Error updating orders: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Recalculate prices for a single order.
     */
    protected function recalculateOrderPrices(Order $order): array
    {
        $oldTotal = $order->totalAmount();
        $newTotal = 0;
        $itemsUpdated = 0;
        $hasChanges = false;
        
        foreach ($order->orderItems as $item) {
            if (!$item->product || !$item->price_variation_id) {
                continue;
            }
            
            // Get current price for this customer
            $currentPrice = $item->product->getPriceForSpecificCustomer(
                $order->user,
                $item->price_variation_id
            );
            
            // Check if price has changed
            if (abs($currentPrice - $item->price) > 0.001) {
                if (!$this->option('dry-run')) {
                    $item->price = $currentPrice;
                    $item->save();
                }
                $itemsUpdated++;
                $hasChanges = true;
            }
            
            $newTotal += $currentPrice * $item->quantity;
        }
        
        return [
            'hasChanges' => $hasChanges,
            'oldTotal' => $oldTotal,
            'newTotal' => $newTotal,
            'totalDifference' => $oldTotal - $newTotal,
            'itemsUpdated' => $itemsUpdated,
        ];
    }
}