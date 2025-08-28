<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillRecurringOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:backfill-recurring 
                            {--dry-run : Show what would be generated without making changes}
                            {--order-id= : Only process specific order template ID}
                            {--from-date= : Override start date (YYYY-MM-DD)}
                            {--to-date= : End date for backfill (YYYY-MM-DD, defaults to today)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill missing recurring orders from past dates to present';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $orderId = $this->option('order-id');
        $fromDate = $this->option('from-date');
        $toDate = $this->option('to-date') ? Carbon::parse($this->option('to-date')) : now();
        
        $this->info('Scanning for recurring order templates that need backfilling...');
        
        // Find active recurring templates
        $query = Order::where('is_recurring', true)
            ->where('is_recurring_active', true)
            ->whereNull('parent_recurring_order_id') // Only templates
            ->where('recurring_start_date', '<=', $toDate); // Started before our end date
            
        if ($orderId) {
            $query->where('id', $orderId);
        }
        
        $templates = $query->with(['user', 'orderItems', 'packagingTypes'])->get();
        
        if ($templates->isEmpty()) {
            $this->info('No recurring order templates found that need backfilling.');
            return 0;
        }
        
        $this->info("Found {$templates->count()} recurring template(s) to process:");
        
        foreach ($templates as $template) {
            $userName = $template->user->name ?? 'Unknown';
            $this->line("  - Order #{$template->id}: {$userName} ({$template->recurring_frequency})");
        }
        
        $this->newLine();
        
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }
        
        $totalGenerated = 0;
        $errors = 0;
        
        foreach ($templates as $template) {
            try {
                $generated = $this->backfillOrdersForTemplate($template, $fromDate, $toDate, $isDryRun);
                $totalGenerated += $generated;
                
                if ($generated > 0) {
                    $userName = $template->user->name ?? 'Unknown';
                    $this->line("✓ Generated {$generated} orders for {$userName}");
                } else {
                    $userName = $template->user->name ?? 'Unknown';
                    $this->line("- No missing orders for {$userName}");
                }
                
            } catch (Exception $e) {
                $this->error("✗ Failed to process Order #{$template->id}: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->newLine();
        $this->info("Backfill complete:");
        $this->line("  - Generated: {$totalGenerated} orders");
        if ($errors > 0) {
            $this->line("  - Errors: {$errors} templates");
        }
        
        if ($isDryRun) {
            $this->warn('Run without --dry-run to apply changes');
        }
        
        return 0;
    }
    
    /**
     * Backfill orders for a specific template
     */
    private function backfillOrdersForTemplate(Order $template, ?string $fromDateStr, Carbon $toDate, bool $isDryRun): int
    {
        // Use provided from date or template's recurring start date
        $fromDate = $fromDateStr ? Carbon::parse($fromDateStr) : Carbon::parse($template->recurring_start_date);
        
        // Don't generate beyond the end date if set
        if ($template->recurring_end_date && $toDate->gt($template->recurring_end_date)) {
            $toDate = Carbon::parse($template->recurring_end_date);
        }
        
        // Get existing generated orders to avoid duplicates
        $existingDeliveryDates = $template->generatedOrders()
            ->pluck('delivery_date')
            ->map(fn($date) => Carbon::parse($date)->format('Y-m-d'))
            ->toArray();
        
        $currentDate = $fromDate->copy();
        $generated = 0;
        
        while ($currentDate->lte($toDate)) {
            $deliveryDate = $this->calculateDeliveryDate($currentDate, $template);
            $deliveryDateStr = $deliveryDate->format('Y-m-d');
            
            // Skip if we already have an order for this delivery date
            if (!in_array($deliveryDateStr, $existingDeliveryDates)) {
                if ($isDryRun) {
                    $status = $this->calculateInitialStatus($deliveryDate, $template);
                    $this->line("    Would generate order for delivery: {$deliveryDateStr} (status: {$status})");
                } else {
                    $this->generateOrderForDate($template, $currentDate->copy(), $deliveryDate);
                }
                $generated++;
            }
            
            // Move to next occurrence based on frequency
            $currentDate = $this->getNextOccurrence($currentDate, $template);
        }
        
        // Update template's last generated and next generation dates
        if (!$isDryRun && $generated > 0) {
            $nextGeneration = $this->getNextOccurrence($toDate, $template);
            $template->update([
                'last_generated_at' => now(),
                'next_generation_date' => $nextGeneration->isFuture() ? $nextGeneration : null
            ]);
        }
        
        return $generated;
    }
    
    /**
     * Calculate delivery date based on harvest date
     */
    private function calculateDeliveryDate(Carbon $harvestDate, Order $template): Carbon
    {
        // For farmer's markets, delivery is typically the same day
        // For other types, might be next day
        return match($template->order_type) {
            'farmers_market', 'farmers_market_recurring' => $harvestDate->copy(),
            default => $harvestDate->copy()->addDay()
        };
    }
    
    /**
     * Get the next occurrence based on recurring frequency
     */
    private function getNextOccurrence(Carbon $currentDate, Order $template): Carbon
    {
        return match($template->recurring_frequency) {
            'weekly' => $currentDate->addWeek(),
            'biweekly' => $currentDate->addWeeks($template->recurring_interval ?? 2),
            'monthly' => $currentDate->addMonth(),
            default => $currentDate->addWeek()
        };
    }
    
    /**
     * Generate a single order for a specific date
     */
    private function generateOrderForDate(Order $template, Carbon $harvestDate, Carbon $deliveryDate): Order
    {
        // Create new order based on template
        $newOrder = $template->replicate([
            'is_recurring',
            'recurring_frequency',
            'recurring_start_date', 
            'recurring_end_date',
            'recurring_days_of_week',
            'recurring_interval',
            'last_generated_at',
            'next_generation_date'
        ]);
        
        $newOrder->parent_recurring_order_id = $template->id;
        $newOrder->harvest_date = $harvestDate;
        $newOrder->delivery_date = $deliveryDate;
        $newOrder->is_recurring = false;
        
        // Set appropriate status based on delivery date
        $newOrder->status = $this->calculateInitialStatus($deliveryDate, $template);
        
        // Set billing period based on delivery date
        $newOrder->billing_period = $this->calculateBillingPeriod($deliveryDate, $template);
        
        $newOrder->save();
        
        // Copy order items
        foreach ($template->orderItems as $item) {
            $newOrder->orderItems()->create([
                'product_id' => $item->product_id,
                'price_variation_id' => $item->price_variation_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ]);
        }
        
        // Copy packaging
        foreach ($template->packagingTypes as $packaging) {
            $newOrder->packagingTypes()->attach($packaging->id, [
                'quantity' => $packaging->pivot->quantity,
                'notes' => $packaging->pivot->notes,
            ]);
        }
        
        return $newOrder;
    }
    
    /**
     * Calculate appropriate initial status based on delivery date
     */
    private function calculateInitialStatus(Carbon $deliveryDate, Order $template): string
    {
        $today = now();
        $daysDiff = $today->diffInDays($deliveryDate, false); // Negative if delivery is in past
        
        // For orders with delivery dates in the past
        if ($deliveryDate->lt($today)) {
            $daysAgo = abs($daysDiff); // Convert to positive days ago
            // If delivery was more than 7 days ago, mark as completed
            if ($daysAgo > 7) {
                return 'completed';
            }
            // If delivery was 1-7 days ago, mark as delivered
            elseif ($daysAgo > 0) {
                return 'delivered';
            }
            // If delivery was today, mark as pending (might still need delivery)
            else {
                return 'pending';
            }
        }
        
        // For future orders, always start as pending
        return 'pending';
    }
    
    /**
     * Calculate billing period for the order
     */
    private function calculateBillingPeriod(Carbon $deliveryDate, Order $template): string
    {
        return match($template->order_type) {
            'b2b' => $deliveryDate->format('Y-m'),
            'farmers_market', 'farmers_market_recurring' => $deliveryDate->format('Y-\WW'),
            'csa_recurring' => $deliveryDate->format('Y-m'),
            'weekly_box_recurring' => $deliveryDate->format('Y-\WW'),
            default => $deliveryDate->format('Y-m'),
        };
    }
}