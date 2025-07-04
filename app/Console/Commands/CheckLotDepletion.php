<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Models\User;
use App\Notifications\ResourceActionRequired;
use App\Services\LotDepletionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLotDepletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-lot-depletion {--notify : Send notifications about depleted lots} {--auto-mark : Automatically mark depleted lots}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check all active recipes for lot depletion and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(LotDepletionService $depletionService)
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
            
        } catch (\Exception $e) {
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