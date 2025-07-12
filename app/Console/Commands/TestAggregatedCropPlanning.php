<?php

namespace App\Console\Commands;

use App\Services\CropPlanningService;
use Illuminate\Console\Command;
use App\Models\Order;

class TestAggregatedCropPlanning extends Command
{
    protected $signature = 'test:aggregated-crop-planning 
                            {--start-date= : Start date for order range}
                            {--end-date= : End date for order range}
                            {--dry-run : Show what would be generated without creating crop plans}';

    protected $description = 'Test the aggregated crop planning functionality';

    public function handle()
    {
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No crop plans will be created');
            $this->newLine();
        }

        $cropPlanningService = app(CropPlanningService::class);

        // Show current actual orders (not recurring templates) in date range
        $this->info('Current actual orders in date range:');
        $orders = Order::with(['orderItems.product', 'orderItems.priceVariation', 'status'])
            ->where('harvest_date', '>=', $startDate ?: now())
            ->where('harvest_date', '<=', $endDate ?: now()->addDays(30))
            ->where('is_recurring', false) // Exclude recurring order templates
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'pending', 'confirmed', 'in_production']);
            })
            ->get();

        if ($orders->isEmpty()) {
            $this->warn('No orders found in the specified date range.');
            return 0;
        }

        $this->table(
            ['Order ID', 'Harvest Date', 'Status', 'Items Count'],
            $orders->map(function ($order) {
                return [
                    $order->id,
                    $order->harvest_date->format('Y-m-d'),
                    $order->status->name ?? 'Unknown',
                    $order->orderItems->count()
                ];
            })
        );

        $this->newLine();
        $this->info('Generating aggregated crop plans...');

        try {
            $cropPlans = $cropPlanningService->generateIndividualPlansForAllOrders($startDate, $endDate);

            if ($cropPlans->isEmpty()) {
                $this->warn('No crop plans were generated.');
                return 0;
            }

            $this->info("Generated {$cropPlans->count()} aggregated crop plans:");
            $this->newLine();

            $this->table(
                ['Variety ID', 'Cultivar', 'Harvest Date', 'Total Grams', 'Trays Needed'],
                $cropPlans->map(function ($plan) {
                    return [
                        $plan->variety_id ?? 'N/A',
                        $plan->cultivar ?? 'N/A',
                        $plan->expected_harvest_date->format('Y-m-d'),
                        number_format($plan->grams_needed, 2),
                        $plan->trays_needed
                    ];
                })
            );

            if (!$isDryRun) {
                $this->info('Crop plans have been created in the database.');
            }

        } catch (\Exception $e) {
            $this->error('Error generating aggregated crop plans: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}