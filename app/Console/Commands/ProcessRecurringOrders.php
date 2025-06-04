<?php

namespace App\Console\Commands;

use App\Services\RecurringOrderService;
use Illuminate\Console\Command;

class ProcessRecurringOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:process-recurring
                            {--dry-run : Show what would be processed without making changes}
                            {--force : Force processing even if already run today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process all active recurring orders and generate new orders as needed';

    protected RecurringOrderService $recurringOrderService;

    public function __construct(RecurringOrderService $recurringOrderService)
    {
        parent::__construct();
        $this->recurringOrderService = $recurringOrderService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing recurring orders...');
        
        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No changes will be made');
            return $this->dryRun();
        }

        $results = $this->recurringOrderService->processRecurringOrders();
        
        $this->displayResults($results);
        
        if ($results['generated'] > 0 || $results['deactivated'] > 0) {
            $this->info('✅ Recurring order processing completed successfully');
            return Command::SUCCESS;
        }
        
        $this->info('ℹ️  No recurring orders needed processing');
        return Command::SUCCESS;
    }

    /**
     * Show what would be processed in dry-run mode.
     */
    protected function dryRun(): int
    {
        $upcomingOrders = $this->recurringOrderService->getUpcomingRecurringOrders(1);
        $stats = $this->recurringOrderService->getRecurringOrderStats();
        
        $this->table(['Statistic', 'Count'], [
            ['Active Templates', $stats['active_templates']],
            ['Paused Templates', $stats['paused_templates']],
            ['Total Generated Orders', $stats['total_generated']],
            ['Due for Processing Today', $upcomingOrders->count()],
        ]);
        
        if ($upcomingOrders->count() > 0) {
            $this->info("\nOrders that would be generated:");
            $this->table(
                ['Customer', 'Frequency', 'Next Generation', 'Items'],
                $upcomingOrders->map(function ($order) {
                    return [
                        $order->user->name ?? 'Unknown',
                        $order->recurring_frequency_display,
                        $order->next_generation_date?->format('Y-m-d H:i'),
                        $order->orderItems->count() . ' items'
                    ];
                })->toArray()
            );
        }
        
        return Command::SUCCESS;
    }

    /**
     * Display the processing results.
     */
    protected function displayResults(array $results): void
    {
        $this->table(['Metric', 'Count'], [
            ['Templates Processed', $results['processed']],
            ['New Orders Generated', $results['generated']],
            ['Templates Deactivated', $results['deactivated']],
            ['Errors Encountered', count($results['errors'])],
        ]);
        
        if (!empty($results['errors'])) {
            $this->error("\n❌ Errors encountered:");
            foreach ($results['errors'] as $error) {
                $this->error("Order ID {$error['order_id']}: {$error['error']}");
            }
        }
        
        if ($results['generated'] > 0) {
            $this->info("\n🎉 Successfully generated {$results['generated']} new orders!");
        }
    }
}