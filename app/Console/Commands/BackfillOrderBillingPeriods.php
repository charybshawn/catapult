<?php

namespace App\Console\Commands;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillOrderBillingPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:backfill-billing-periods 
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill billing periods for existing B2B recurring orders that are missing them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->line('');
        }
        
        // Find B2B recurring orders without billing periods
        $ordersNeedingBillingPeriods = Order::where('order_type', 'b2b_recurring')
            ->where('billing_frequency', '!=', 'immediate')
            ->where(function ($query) {
                $query->whereNull('billing_period_start')
                      ->orWhereNull('billing_period_end');
            })
            ->whereNotNull('delivery_date')
            ->get();
            
        if ($ordersNeedingBillingPeriods->isEmpty()) {
            $this->info('No orders found that need billing periods set.');
            return Command::SUCCESS;
        }
        
        $this->info("Found {$ordersNeedingBillingPeriods->count()} orders that need billing periods:");
        $this->line('');
        
        $updated = 0;
        
        foreach ($ordersNeedingBillingPeriods as $order) {
            $deliveryDate = Carbon::parse($order->delivery_date);
            
            switch ($order->billing_frequency) {
                case 'weekly':
                    $periodStart = $deliveryDate->copy()->startOfWeek()->toDateString();
                    $periodEnd = $deliveryDate->copy()->endOfWeek()->toDateString();
                    break;
                    
                case 'monthly':
                    $periodStart = $deliveryDate->copy()->startOfMonth()->toDateString();
                    $periodEnd = $deliveryDate->copy()->endOfMonth()->toDateString();
                    break;
                    
                case 'quarterly':
                    $periodStart = $deliveryDate->copy()->startOfQuarter()->toDateString();
                    $periodEnd = $deliveryDate->copy()->endOfQuarter()->toDateString();
                    break;
                    
                default:
                    $this->warn("Unknown billing frequency for order {$order->id}: {$order->billing_frequency}");
                    continue 2;
            }
            
            $customerName = $order->user ? $order->user->name : 'Unknown';
            $this->line("Order #{$order->id}:");
            $this->line("  Customer: {$customerName}");
            $this->line("  Delivery Date: {$order->delivery_date}");
            $this->line("  Billing Frequency: {$order->billing_frequency}");
            $this->line("  Period: {$periodStart} to {$periodEnd}");
            $this->line('');
            
            if (!$dryRun) {
                $order->update([
                    'billing_period_start' => $periodStart,
                    'billing_period_end' => $periodEnd,
                ]);
            }
            
            $updated++;
        }
        
        if ($dryRun) {
            $this->info("Would update {$updated} orders with billing periods.");
            $this->line('Run without --dry-run to apply changes.');
        } else {
            $this->info("Successfully updated {$updated} orders with billing periods.");
        }
        
        return Command::SUCCESS;
    }
}