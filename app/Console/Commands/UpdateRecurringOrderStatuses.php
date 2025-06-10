<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateRecurringOrderStatuses extends Command
{
    protected $signature = 'orders:update-recurring-statuses {--dry-run : Show what would be updated}';
    protected $description = 'Update statuses of existing generated recurring orders based on delivery dates (queued → preparing → growing → harvested → delivered → completed)';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $orders = Order::whereNotNull('parent_recurring_order_id')->get();
        $today = now();
        $updated = 0;
        
        $this->info('Updating statuses for generated recurring orders...');
        
        foreach ($orders as $order) {
            $deliveryDate = $order->delivery_date;
            $daysDiff = $today->diffInDays($deliveryDate, false); // Negative if delivery is in past
            
            $newStatus = 'pending';
            if ($deliveryDate->lt($today)) {
                $daysAgo = abs($daysDiff); // Convert to positive days ago
                if ($daysAgo > 7) {
                    $newStatus = 'completed';
                } elseif ($daysAgo > 0) {
                    $newStatus = 'delivered';
                } else {
                    $newStatus = 'pending';
                }
            }
            
            $currentStatus = $order->status;
            
            // Debug output
            $timeDesc = $daysDiff < 0 ? abs($daysDiff) . ' days ago' : 'in ' . $daysDiff . ' days';
            $this->line("Order #{$order->id}: Delivery {$deliveryDate->format('Y-m-d')} ({$timeDesc}), Current: {$currentStatus}, Suggested: {$newStatus}");
            
            if ($currentStatus !== $newStatus) {
                if ($isDryRun) {
                    $this->line("    → Would update from '{$currentStatus}' to '{$newStatus}'");
                } else {
                    $order->update(['status' => $newStatus]);
                    $this->line("    ✓ Updated from '{$currentStatus}' to '{$newStatus}'");
                }
                $updated++;
            }
        }
        
        $this->info("Would update {$updated} orders with more appropriate statuses.");
        
        return 0;
    }
}