<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\User;
use App\Notifications\ResourceActionRequired;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing lot depletion alerts and validation.
 * 
 * This service handles comprehensive lot status monitoring including:
 * - Checking all lots for depletion status
 * - Sending notifications about depleted and low stock lots
 * - Automatically marking lots as depleted when appropriate
 * - Providing summary reports of lot status
 */
class LotDepletionService
{
    /**
     * The LotInventoryService instance.
     */
    protected LotInventoryService $lotInventoryService;

    /**
     * Low stock threshold percentage.
     */
    protected float $lowStockThreshold = 15.0;

    /**
     * Create a new service instance.
     */
    public function __construct(LotInventoryService $lotInventoryService)
    {
        $this->lotInventoryService = $lotInventoryService;
    }

    /**
     * Check all lots and return comprehensive status summary.
     * 
     * @return array Summary with keys: total_lots, active_lots, depleted_lots, low_stock_lots, lot_details
     */
    public function checkAllLots(): array
    {
        $allLots = $this->lotInventoryService->getAllLotNumbers();
        $lotDetails = [];
        $depletedCount = 0;
        $lowStockCount = 0;
        $activeCount = 0;
        
        foreach ($allLots as $lotNumber) {
            $summary = $this->lotInventoryService->getLotSummary($lotNumber);
            $isDepletedByQuantity = $summary['available'] <= 0;
            $isLowStock = false;
            
            if ($summary['total'] > 0) {
                $availablePercentage = ($summary['available'] / $summary['total']) * 100;
                $isLowStock = $availablePercentage <= $this->lowStockThreshold && $availablePercentage > 0;
            }
            
            // Check if any recipes are manually marked as depleted for this lot
            $recipesForLot = Recipe::where('lot_number', $lotNumber)
                ->where('is_active', true)
                ->get();
            
            $manuallyMarkedDepleted = $recipesForLot->where('lot_depleted_at', '!=', null)->count() > 0;
            
            $isDepleted = $isDepletedByQuantity || $manuallyMarkedDepleted;
            
            if ($isDepleted) {
                $depletedCount++;
            } elseif ($isLowStock) {
                $lowStockCount++;
            } else {
                $activeCount++;
            }
            
            $lotDetails[] = [
                'lot_number' => $lotNumber,
                'total_quantity' => $summary['total'],
                'available_quantity' => $summary['available'],
                'consumed_quantity' => $summary['consumed'],
                'entry_count' => $summary['entry_count'],
                'is_depleted' => $isDepleted,
                'is_low_stock' => $isLowStock,
                'available_percentage' => $summary['total'] > 0 ? ($summary['available'] / $summary['total']) * 100 : 0,
                'manually_marked_depleted' => $manuallyMarkedDepleted,
                'depleted_by_quantity' => $isDepletedByQuantity,
                'oldest_entry_date' => $summary['oldest_entry_date'],
                'newest_entry_date' => $summary['newest_entry_date'],
                'recipe_count' => $recipesForLot->count(),
            ];
        }
        
        return [
            'total_lots' => $allLots->count(),
            'active_lots' => $activeCount,
            'depleted_lots' => $depletedCount,
            'low_stock_lots' => $lowStockCount,
            'lot_details' => $lotDetails,
        ];
    }

    /**
     * Get all recipes that have depleted lots.
     * 
     * @return Collection
     */
    public function getDepletedRecipes(): Collection
    {
        return Recipe::where('is_active', true)
            ->whereNotNull('lot_number')
            ->get()
            ->filter(function ($recipe) {
                return $recipe->isLotDepleted();
            });
    }

    /**
     * Get all recipes that have low stock lots.
     * 
     * @return Collection
     */
    public function getLowStockRecipes(): Collection
    {
        return Recipe::where('is_active', true)
            ->whereNotNull('lot_number')
            ->get()
            ->filter(function ($recipe) {
                if ($recipe->isLotDepleted()) {
                    return false; // Skip depleted lots
                }
                
                $lotQuantity = $recipe->getLotQuantity();
                if ($lotQuantity <= 0) {
                    return false;
                }
                
                $summary = $this->lotInventoryService->getLotSummary($recipe->lot_number);
                if ($summary['total'] <= 0) {
                    return false;
                }
                
                $availablePercentage = ($summary['available'] / $summary['total']) * 100;
                return $availablePercentage <= $this->lowStockThreshold;
            });
    }

    /**
     * Send notifications about depleted lots to admin users.
     * 
     * @return void
     */
    public function sendDepletionAlerts(): void
    {
        $depletedRecipes = $this->getDepletedRecipes();
        
        if ($depletedRecipes->isEmpty()) {
            return;
        }
        
        $adminUsers = User::where('is_admin', true)->get();
        
        if ($adminUsers->isEmpty()) {
            Log::warning('No admin users found to send lot depletion alerts to');
            return;
        }
        
        $lotDetails = [];
        foreach ($depletedRecipes as $recipe) {
            $lotNumber = $recipe->lot_number;
            if (!isset($lotDetails[$lotNumber])) {
                $summary = $this->lotInventoryService->getLotSummary($lotNumber);
                $lotDetails[$lotNumber] = [
                    'lot_number' => $lotNumber,
                    'recipes' => [],
                    'summary' => $summary,
                ];
            }
            $lotDetails[$lotNumber]['recipes'][] = $recipe->name;
        }
        
        $subject = 'Critical Alert: Seed Lot Depletion Detected';
        $body = $this->buildDepletionNotificationBody($lotDetails);
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new ResourceActionRequired(
                $subject,
                $body,
                url('/admin/recipes'),
                'View Recipes'
            ));
        }
        
        Log::info('Lot depletion alerts sent', [
            'depleted_lots' => count($lotDetails),
            'affected_recipes' => $depletedRecipes->count(),
            'notified_users' => $adminUsers->count(),
        ]);
    }

    /**
     * Send notifications about low stock lots to admin users.
     * 
     * @return void
     */
    public function sendLowStockAlerts(): void
    {
        $lowStockRecipes = $this->getLowStockRecipes();
        
        if ($lowStockRecipes->isEmpty()) {
            return;
        }
        
        $adminUsers = User::where('is_admin', true)->get();
        
        if ($adminUsers->isEmpty()) {
            Log::warning('No admin users found to send low stock alerts to');
            return;
        }
        
        $lotDetails = [];
        foreach ($lowStockRecipes as $recipe) {
            $lotNumber = $recipe->lot_number;
            if (!isset($lotDetails[$lotNumber])) {
                $summary = $this->lotInventoryService->getLotSummary($lotNumber);
                $availablePercentage = $summary['total'] > 0 ? ($summary['available'] / $summary['total']) * 100 : 0;
                $lotDetails[$lotNumber] = [
                    'lot_number' => $lotNumber,
                    'recipes' => [],
                    'summary' => $summary,
                    'available_percentage' => $availablePercentage,
                ];
            }
            $lotDetails[$lotNumber]['recipes'][] = $recipe->name;
        }
        
        $subject = 'Warning: Low Stock Alert for Seed Lots';
        $body = $this->buildLowStockNotificationBody($lotDetails);
        
        foreach ($adminUsers as $admin) {
            $admin->notify(new ResourceActionRequired(
                $subject,
                $body,
                url('/admin/recipes'),
                'View Recipes'
            ));
        }
        
        Log::info('Low stock alerts sent', [
            'low_stock_lots' => count($lotDetails),
            'affected_recipes' => $lowStockRecipes->count(),
            'notified_users' => $adminUsers->count(),
        ]);
    }

    /**
     * Automatically mark lots as depleted when they have zero available quantity.
     * 
     * @return int Number of recipes marked as depleted
     */
    public function markAutomaticDepletion(): int
    {
        $activeRecipes = Recipe::where('is_active', true)
            ->whereNotNull('lot_number')
            ->whereNull('lot_depleted_at')
            ->get();
        
        $markedCount = 0;
        
        foreach ($activeRecipes as $recipe) {
            $lotQuantity = $recipe->getLotQuantity();
            
            if ($lotQuantity <= 0) {
                $recipe->markLotDepleted();
                $markedCount++;
                
                Log::info('Automatically marked lot as depleted', [
                    'recipe_id' => $recipe->id,
                    'recipe_name' => $recipe->name,
                    'lot_number' => $recipe->lot_number,
                    'available_quantity' => $lotQuantity,
                ]);
            }
        }
        
        return $markedCount;
    }

    /**
     * Get critical lot alerts for dashboard display.
     * 
     * @return array
     */
    public function getCriticalAlerts(): array
    {
        $depletedRecipes = $this->getDepletedRecipes();
        $lowStockRecipes = $this->getLowStockRecipes();
        
        $alerts = [];
        
        // Add depleted lot alerts
        foreach ($depletedRecipes as $recipe) {
            $alerts[] = [
                'type' => 'critical',
                'title' => 'Lot Depleted',
                'message' => "Recipe '{$recipe->name}' has a depleted lot ({$recipe->lot_number})",
                'recipe_id' => $recipe->id,
                'lot_number' => $recipe->lot_number,
                'created_at' => $recipe->lot_depleted_at ?? now(),
            ];
        }
        
        // Add low stock alerts
        foreach ($lowStockRecipes as $recipe) {
            $summary = $this->lotInventoryService->getLotSummary($recipe->lot_number);
            $availablePercentage = $summary['total'] > 0 ? ($summary['available'] / $summary['total']) * 100 : 0;
            
            $alerts[] = [
                'type' => 'warning',
                'title' => 'Low Stock',
                'message' => "Recipe '{$recipe->name}' lot ({$recipe->lot_number}) is running low (" . number_format($availablePercentage, 1) . "% remaining)",
                'recipe_id' => $recipe->id,
                'lot_number' => $recipe->lot_number,
                'available_percentage' => $availablePercentage,
                'available_quantity' => $summary['available'],
                'created_at' => now(),
            ];
        }
        
        // Sort by severity (critical first) and then by date
        usort($alerts, function ($a, $b) {
            if ($a['type'] === 'critical' && $b['type'] === 'warning') {
                return -1;
            } elseif ($a['type'] === 'warning' && $b['type'] === 'critical') {
                return 1;
            }
            return $b['created_at'] <=> $a['created_at'];
        });
        
        return $alerts;
    }

    /**
     * Build the notification body for depletion alerts.
     * 
     * @param array $lotDetails
     * @return string
     */
    protected function buildDepletionNotificationBody(array $lotDetails): string
    {
        $body = "The following seed lots have been depleted and require immediate attention:\n\n";
        
        foreach ($lotDetails as $details) {
            $body .= "**Lot {$details['lot_number']}**\n";
            $body .= "- Total Quantity: {$details['summary']['total']}g\n";
            $body .= "- Available Quantity: {$details['summary']['available']}g\n";
            $body .= "- Consumed Quantity: {$details['summary']['consumed']}g\n";
            $body .= "- Affected Recipes: " . implode(', ', $details['recipes']) . "\n";
            $body .= "- Inventory Entries: {$details['summary']['entry_count']}\n\n";
        }
        
        $body .= "**Action Required:**\n";
        $body .= "- Review and update recipe lot assignments\n";
        $body .= "- Order new seed stock for affected varieties\n";
        $body .= "- Consider suspending production for affected recipes\n\n";
        
        $body .= "Please address these issues promptly to maintain production schedules.";
        
        return $body;
    }

    /**
     * Build the notification body for low stock alerts.
     * 
     * @param array $lotDetails
     * @return string
     */
    protected function buildLowStockNotificationBody(array $lotDetails): string
    {
        $body = "The following seed lots are running low on stock and may need attention:\n\n";
        
        foreach ($lotDetails as $details) {
            $body .= "**Lot {$details['lot_number']}** (" . number_format($details['available_percentage'], 1) . "% remaining)\n";
            $body .= "- Total Quantity: {$details['summary']['total']}g\n";
            $body .= "- Available Quantity: {$details['summary']['available']}g\n";
            $body .= "- Consumed Quantity: {$details['summary']['consumed']}g\n";
            $body .= "- Affected Recipes: " . implode(', ', $details['recipes']) . "\n\n";
        }
        
        $body .= "**Recommended Actions:**\n";
        $body .= "- Monitor these lots closely\n";
        $body .= "- Consider placing orders for replacement seed stock\n";
        $body .= "- Review upcoming production schedules\n\n";
        
        $body .= "Early planning helps prevent production disruptions.";
        
        return $body;
    }

    /**
     * Set the low stock threshold percentage.
     * 
     * @param float $threshold
     * @return void
     */
    public function setLowStockThreshold(float $threshold): void
    {
        $this->lowStockThreshold = max(0, min(100, $threshold));
    }

    /**
     * Get the current low stock threshold percentage.
     * 
     * @return float
     */
    public function getLowStockThreshold(): float
    {
        return $this->lowStockThreshold;
    }
}