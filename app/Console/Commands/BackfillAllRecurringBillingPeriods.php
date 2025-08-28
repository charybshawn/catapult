<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillAllRecurringBillingPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:backfill-all-recurring-billing-periods 
                            {--dry-run : Show what would be updated without making changes}
                            {--order-type= : Only process specific order type (b2b, farmers_market_recurring, etc.)}
                            {--start-date= : Only process orders from this date onwards (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill billing periods for all types of recurring orders that started in the past';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $orderType = $this->option('order-type');
        $startDate = $this->option('start-date');
        
        $this->info('Scanning for recurring orders missing billing periods...');
        
        // Build query for recurring orders missing billing periods
        // Include both templates and generated orders
        $query = Order::where('is_recurring', true)
            ->whereNull('billing_period') // Only orders missing billing periods
            ->where(function($q) {
                // Include templates that need retroactive processing
                $q->where('status', 'template')
                  ->where('recurring_start_date', '<=', now())
                  // OR include generated orders from templates
                  ->orWhere(function($subQ) {
                      $subQ->where('status', '!=', 'template')
                           ->whereNotNull('parent_recurring_order_id');
                  });
            });
            
        // Filter by order type if specified
        if ($orderType) {
            $query->where('order_type', $orderType);
        }
        
        // Filter by start date if specified
        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate));
        }
        
        $ordersNeedingBillingPeriods = $query->get();
        
        if ($ordersNeedingBillingPeriods->isEmpty()) {
            $this->info('No recurring orders found that need billing period backfill.');
            return 0;
        }
        
        $this->info("Found {$ordersNeedingBillingPeriods->count()} recurring orders needing billing periods:");
        
        $groupedByType = $ordersNeedingBillingPeriods->groupBy('order_type');
        foreach ($groupedByType as $type => $orders) {
            $this->line("  - {$type}: {$orders->count()} orders");
        }
        
        $this->newLine();
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }
        
        $processed = 0;
        $errors = 0;
        
        foreach ($ordersNeedingBillingPeriods as $order) {
            try {
                $billingPeriod = $this->calculateBillingPeriod($order);
                
                if ($isDryRun) {
                    $this->line("Would set Order #{$order->id} ({$order->order_type}) billing period to: {$billingPeriod}");
                } else {
                    $order->update(['billing_period' => $billingPeriod]);
                    $this->line("✓ Set Order #{$order->id} ({$order->order_type}) billing period to: {$billingPeriod}");
                }
                
                $processed++;
                
            } catch (Exception $e) {
                $this->error("✗ Failed to process Order #{$order->id}: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("Processing complete:");
        $this->line("  - Processed: {$processed} orders");
        if ($errors > 0) {
            $this->line("  - Errors: {$errors} orders");
        }
        
        if ($isDryRun) {
            $this->warn('Run without --dry-run to apply changes');
        }
        
        return 0;
    }
    
    /**
     * Calculate the appropriate billing period for an order
     */
    private function calculateBillingPeriod(Order $order): string
    {
        // For templates, use the recurring start date; for generated orders, use delivery/created date
        if ($order->status === 'template') {
            $orderDate = Carbon::parse($order->recurring_start_date ?? $order->created_at);
        } else {
            $orderDate = Carbon::parse($order->delivery_date ?? $order->created_at);
        }
        
        // Different billing period logic based on order type
        return match($order->order_type) {
            'b2b' => $this->calculateB2BBillingPeriod($orderDate, $order),
            'farmers_market_recurring', 'farmers_market' => $this->calculateFarmersMarketBillingPeriod($orderDate, $order),
            'csa_recurring' => $this->calculateCSABillingPeriod($orderDate, $order),
            'weekly_box_recurring' => $this->calculateWeeklyBoxBillingPeriod($orderDate, $order),
            default => $this->calculateDefaultBillingPeriod($orderDate, $order),
        };
    }
    
    /**
     * B2B orders typically use monthly billing cycles
     */
    private function calculateB2BBillingPeriod(Carbon $orderDate, Order $order): string
    {
        // For B2B, use monthly billing periods
        return $orderDate->format('Y-m');
    }
    
    /**
     * Farmer's market orders might be weekly or by market date
     */
    private function calculateFarmersMarketBillingPeriod(Carbon $orderDate, Order $order): string
    {
        // For farmer's markets, could be weekly or by specific market dates
        // Using ISO week format (e.g., "2024-W15")
        return $orderDate->format('Y-\WW');
    }
    
    /**
     * CSA orders typically follow seasonal or monthly billing
     */
    private function calculateCSABillingPeriod(Carbon $orderDate, Order $order): string
    {
        // For CSA, use monthly billing
        return $orderDate->format('Y-m');
    }
    
    /**
     * Weekly box subscriptions typically bill weekly
     */
    private function calculateWeeklyBoxBillingPeriod(Carbon $orderDate, Order $order): string
    {
        // Weekly billing periods using ISO week
        return $orderDate->format('Y-\WW');
    }
    
    /**
     * Default billing period calculation
     */
    private function calculateDefaultBillingPeriod(Carbon $orderDate, Order $order): string
    {
        // Default to monthly if we can't determine the type
        return $orderDate->format('Y-m');
    }
}