<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Recipe;
use App\Models\User;
use App\Notifications\ResourceActionRequired;
use App\Services\InventoryManagementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Lot depletion monitoring command for agricultural inventory management.
 * Monitors seed lot inventory levels, identifies depleted or low-stock conditions,
 * and provides automated notifications to ensure continuous microgreens production
 * without interruption due to insufficient raw materials.
 *
 * @business_domain Agricultural inventory management and seed lot monitoring
 * @monitoring_scope Seed lot depletion, low stock alerts, automatic status updates
 * @scheduling_context Runs daily at 7 AM and every 4 hours for continuous monitoring
 * @notification_system Automated alerts to farm managers and inventory personnel
 * @production_impact Prevents production delays by proactive inventory monitoring
 */
class CheckLotDepletion extends Command
{
    /**
     * The name and signature of the console command for lot depletion monitoring.
     * Supports notification sending and automatic depletion marking options.
     *
     * @var string
     */
    protected $signature = 'app:check-lot-depletion {--notify : Send notifications about depleted lots} {--auto-mark : Automatically mark depleted lots}';

    /**
     * The console command description for agricultural lot depletion monitoring.
     *
     * @var string
     */
    protected $description = 'Check all active recipes for lot depletion and send notifications';

    /**
     * Execute comprehensive lot depletion monitoring for agricultural inventory.
     * Analyzes seed lot levels, identifies depletion conditions, sends notifications,
     * and optionally marks depleted lots to maintain continuous production capacity.
     *
     * @param InventoryManagementService $depletionService Service for inventory and depletion management
     * @agricultural_monitoring Comprehensive seed lot status analysis and reporting
     * @notification_logic Conditional notification sending based on depletion and low stock conditions
     * @automatic_marking Optional automatic status updates for depleted lots
     * @production_continuity Ensures uninterrupted microgreens production through proactive monitoring
     * @return int Command exit status
     */
    public function handle(InventoryManagementService $depletionService)
    {
        $this->info('Starting lot depletion check...');
        
        $shouldNotify = $this->option('notify');
        $shouldAutoMark = $this->option('auto-mark');
        
        try {
            // Get comprehensive lot status
            $lotStatus = $depletionService->checkAllLots();
            
            $this->info("Lot Status Summary:");
            $this->info("- Total lots: {$lotStatus['total_lots']}");
            $this->info("- Active lots: {$lotStatus['active_lots']}");
            $this->info("- Depleted lots: {$lotStatus['depleted_lots']}");
            $this->info("- Low stock lots: {$lotStatus['low_stock_lots']}");
            
            // Display depleted recipes
            $depletedRecipes = $depletionService->getDepletedRecipes();
            
            if ($depletedRecipes->count() > 0) {
                $this->warn("Found {$depletedRecipes->count()} recipes with depleted lots:");
                
                foreach ($depletedRecipes as $recipe) {
                    $this->warn("- {$recipe->name} (Lot: {$recipe->lot_number})");
                }
                
                // Send notifications if requested
                if ($shouldNotify) {
                    $this->info('Sending depletion notifications...');
                    $depletionService->sendDepletionAlerts();
                    $this->info('Notifications sent successfully.');
                }
            } else {
                $this->info('No depleted lots found.');
            }
            
            // Auto-mark depleted lots if requested
            if ($shouldAutoMark) {
                $this->info('Checking for lots to automatically mark as depleted...');
                $markedCount = $depletionService->markAutomaticDepletion();
                $this->info("Automatically marked {$markedCount} lots as depleted.");
            }
            
            // Display low stock warnings
            if ($lotStatus['low_stock_lots'] > 0) {
                $this->warn("Warning: {$lotStatus['low_stock_lots']} lots are running low on stock.");
                
                if ($shouldNotify) {
                    $this->info('Sending low stock notifications...');
                    $depletionService->sendLowStockAlerts();
                    $this->info('Low stock notifications sent successfully.');
                }
            }
            
            // Log the operation
            Log::info('Lot depletion check completed', [
                'total_lots' => $lotStatus['total_lots'],
                'depleted_lots' => $lotStatus['depleted_lots'],
                'low_stock_lots' => $lotStatus['low_stock_lots'],
                'notifications_sent' => $shouldNotify,
                'auto_marked' => $shouldAutoMark,
            ]);
            
            $this->info('Lot depletion check completed successfully.');
            
        } catch (Exception $e) {
            $this->error("Error during lot depletion check: {$e->getMessage()}");
            Log::error('Lot depletion check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}