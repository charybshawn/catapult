<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderToCropService;
use Illuminate\Console\Command;

class GenerateCropsFromOrders extends Command
{
    protected $signature = 'orders:generate-crops 
                            {--order-id= : Generate crop plans for specific order ID}
                            {--days-ahead=7 : Look ahead this many days for orders}
                            {--dry-run : Show what would be generated without creating crop plans}';

    protected $description = 'Generate crop plans that need to be planted for upcoming orders';

    protected OrderToCropService $orderToCropService;

    public function __construct(OrderToCropService $orderToCropService)
    {
        parent::__construct();
        $this->orderToCropService = $orderToCropService;
    }

    public function handle()
    {
        $orderId = $this->option('order-id');
        $daysAhead = $this->option('days-ahead');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No crops will be created');
            $this->newLine();
        }

        if ($orderId) {
            $this->generateForSpecificOrder($orderId, $isDryRun);
        } else {
            $this->generateForUpcomingOrders($daysAhead, $isDryRun);
        }

        return 0;
    }

    private function generateForSpecificOrder(int $orderId, bool $isDryRun): void
    {
        $order = Order::find($orderId);
        
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return;
        }

        $customerName = $order->user->name ?? 'Unknown';
        $this->info("Generating crop plans for Order #{$order->id} - {$customerName}");
        $this->line("Delivery Date: {$order->delivery_date->format('Y-m-d')}");
        $this->newLine();

        $results = $this->orderToCropService->generateCropPlansForOrder($order, $isDryRun);

        $this->displayResults($results, $isDryRun);
    }

    private function generateForUpcomingOrders(int $daysAhead, bool $isDryRun): void
    {
        $endDate = now()->addDays($daysAhead);
        
        $this->info("Scanning for orders needing crop plans (next {$daysAhead} days)...");
        
        $orders = Order::whereIn('status', ['pending', 'queued', 'preparing'])
            ->where('delivery_date', '<=', $endDate)
            ->whereDoesntHave('cropPlans')
            ->with(['customer', 'orderItems.product'])
            ->orderBy('delivery_date')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('No orders found that need crop plan generation.');
            return;
        }

        $this->info("Found {$orders->count()} orders needing crop plans:");
        foreach ($orders as $order) {
            $customerName = $order->customer->contact_name ?? 'Unknown';
            $this->line("  - Order #{$order->id}: {$customerName} (delivery: {$order->delivery_date->format('Y-m-d')})");
        }
        $this->newLine();

        $totalCropsCreated = 0;
        $totalErrors = 0;

        foreach ($orders as $order) {
            $this->info("Processing Order #{$order->id}...");
            
            $results = $this->orderToCropService->generateCropPlansForOrder($order, $isDryRun);
            
            $totalCropsCreated += $results['plans_created'];
            $totalErrors += count($results['errors']);

            if (!empty($results['errors'])) {
                foreach ($results['errors'] as $error) {
                    $this->error("  ✗ {$error}");
                }
            } else {
                $verb = $isDryRun ? 'Would create' : 'Created';
                $this->line("  ✓ {$verb} {$results['plans_created']} crop plans");
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $verb = $isDryRun ? 'Would create' : 'Created';
        $this->line("  - {$verb}: {$totalCropsCreated} crop plans");
        $this->line("  - Processed: {$orders->count()} orders");
        
        if ($totalErrors > 0) {
            $this->line("  - Errors: {$totalErrors}");
        }

        if ($isDryRun) {
            $this->warn('Run without --dry-run to actually create crop plans');
        }
    }

    private function displayResults(array $results, bool $isDryRun): void
    {
        if (!empty($results['errors'])) {
            $this->error('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->error("  ✗ {$error}");
            }
            return;
        }

        if (empty($results['plans_planned'])) {
            $this->warn('No crop plans needed for this order');
            return;
        }

        $verb = $isDryRun ? 'Would create' : 'Created';
        $this->info("{$verb} {$results['plans_created']} crop plans:");

        $this->table(
            ['Recipe', 'Trays', 'Plant By Date'],
            collect($results['plans_planned'])->map(function ($plan) {
                $recipe = $plan['recipe'] ?? 'Unknown';
                $recipeName = is_object($recipe) ? $recipe->name : $recipe;
                
                return [
                    $recipeName,
                    $plan['quantity'] ?? $plan['trays_needed'] ?? 1,
                    $plan['plant_by_date'] ?? 'Unknown'
                ];
            })->toArray()
        );
    }
}