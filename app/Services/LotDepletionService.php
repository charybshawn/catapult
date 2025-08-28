<?php

namespace App\Services;

/**
 * @deprecated Use InventoryManagementService instead. This service will be phased out.
 * @migration_path Functionality moved to InventoryManagementService for better architectural alignment
 * @removal_timeline Scheduled for removal in next major version
 */

use App\Models\Recipe;
use App\Models\User;
use App\Notifications\ResourceActionRequired;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Agricultural seed lot depletion monitoring and alert management service.
 * 
 * Monitors seed lot inventory levels across agricultural production operations,
 * providing automated depletion detection, low stock alerts, and critical
 * inventory notifications. Essential for maintaining seed supply continuity
 * and preventing agricultural production disruptions.
 * 
 * @deprecated Superseded by InventoryManagementService for unified inventory handling
 * @business_domain Agricultural seed lot inventory monitoring and supply chain management
 * @agricultural_alerts Automated notifications for seed stock depletion and low levels
 * @production_continuity Prevents agricultural disruptions through proactive inventory management
 * @lot_tracking Comprehensive seed lot lifecycle and consumption monitoring
 * 
 * @features
 * - Real-time seed lot depletion detection
 * - Configurable low stock threshold alerting
 * - Automated admin notifications for critical inventory levels
 * - Comprehensive lot status reporting and analytics
 * - Automatic lot depletion marking based on consumption
 * - Dashboard integration for critical inventory alerts
 * 
 * @example
 * $depletionService = new LotDepletionService($lotInventoryService);
 * $alerts = $depletionService->getCriticalAlerts();
 * $depletionService->sendDepletionAlerts();
 * 
 * @migration_note New implementations should use InventoryManagementService
 * @see InventoryManagementService For unified inventory management
 * @see LotInventoryService For lot quantity calculations
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
    protected float $lowStockThreshold;

    /**
     * Create a new service instance.
     */
    public function __construct(LotInventoryService $lotInventoryService)
    {
        $this->lotInventoryService = $lotInventoryService;
        $this->lowStockThreshold = config('inventory.low_stock_threshold', 15.0);
    }

    /**
     * Analyze all agricultural seed lots for comprehensive inventory status assessment.
     * 
     * Performs system-wide analysis of seed lot inventory levels, categorizing lots
     * by status (active, depleted, low stock) and providing detailed metrics for
     * agricultural supply chain management and production planning decisions.
     * 
     * @inventory_analysis Complete system-wide seed lot status evaluation
     * @agricultural_planning Supports production scheduling and resource allocation
     * @supply_chain_monitoring Identifies critical inventory issues across all lots
     * @deprecation_notice Use InventoryManagementService::analyzeLotStatus() for new code
     * 
     * @return array Comprehensive lot analysis with detailed status breakdown
     * 
     * @response_structure
     * [
     *   'total_lots' => int,           // Total seed lots in system
     *   'active_lots' => int,          // Lots with adequate inventory
     *   'depleted_lots' => int,        // Lots with zero or marked depleted
     *   'low_stock_lots' => int,       // Lots below threshold but not depleted
     *   'lot_details' => array         // Per-lot detailed analysis
     * ]
     * 
     * @lot_detail_structure
     * [
     *   'lot_number' => string,
     *   'total_quantity' => float,
     *   'available_quantity' => float,
     *   'consumed_quantity' => float,
     *   'is_depleted' => bool,
     *   'is_low_stock' => bool,
     *   'available_percentage' => float,
     *   'recipe_count' => int
     * ]
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
     * Retrieve all agricultural recipes with depleted seed lot inventory.
     * 
     * Identifies recipes whose assigned seed lots have been completely consumed
     * or manually marked as depleted, requiring immediate attention to prevent
     * agricultural production disruptions and crop planning conflicts.
     * 
     * @production_impact Identifies recipes that cannot be produced due to seed depletion
     * @agricultural_planning Critical for crop scheduling and order fulfillment
     * @inventory_management Supports seed procurement and lot reassignment decisions
     * @deprecation_notice Use InventoryManagementService for new implementations
     * 
     * @return Collection<Recipe> Recipes with depleted seed lot assignments
     * 
     * @example
     * $depletedRecipes = $this->getDepletedRecipes();
     * foreach ($depletedRecipes as $recipe) {
     *     // Alert production team about unavailable varieties
     *     Log::warning("Recipe {$recipe->name} has depleted lot {$recipe->lot_number}");
     * }
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
     * Retrieve agricultural recipes with seed lots approaching depletion threshold.
     * 
     * Identifies recipes whose seed lot inventory has fallen below the configured
     * low stock threshold but is not yet depleted, enabling proactive seed
     * procurement and production planning to prevent supply disruptions.
     * 
     * @early_warning Identifies lots approaching depletion before critical shortage
     * @proactive_management Enables preventive seed ordering and lot management
     * @production_continuity Supports uninterrupted agricultural operations
     * @threshold_based Uses configurable percentage threshold for low stock detection
     * 
     * @return Collection<Recipe> Recipes with low stock seed lot assignments
     * 
     * @example
     * $lowStockRecipes = $this->getLowStockRecipes();
     * foreach ($lowStockRecipes as $recipe) {
     *     $percentage = $this->calculateAvailablePercentage($recipe->lot_number);
     *     Log::info("Recipe {$recipe->name} lot at {$percentage}% - consider reordering");
     * }
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
     * Dispatch critical agricultural inventory alerts to administrative users.
     * 
     * Sends immediate notifications to all admin users about seed lots that have
     * reached complete depletion, requiring urgent action to maintain agricultural
     * production capabilities and prevent order fulfillment failures.
     * 
     * @critical_alerts High-priority notifications for depleted seed inventory
     * @admin_notification Targets administrative users for immediate action
     * @production_protection Prevents agricultural production disruptions
     * @automated_monitoring Reduces manual inventory monitoring overhead
     * 
     * @return void Notifications sent asynchronously to admin users
     * 
     * @notification_content
     * - Affected lot numbers and quantities
     * - Impacted recipe names and production capabilities
     * - Recommended actions for inventory restoration
     * - Links to relevant management interfaces
     * 
     * @example
     * // Typically called from scheduled job
     * $this->sendDepletionAlerts();
     * // Admins receive immediate email/app notifications
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
     * Dispatch proactive agricultural inventory warnings to administrative users.
     * 
     * Sends early warning notifications to admin users about seed lots approaching
     * depletion threshold, enabling proactive inventory management and preventing
     * critical shortages in agricultural production operations.
     * 
     * @early_warning Non-critical notifications for proactive inventory management
     * @preventive_alerts Enables action before critical shortage occurs
     * @admin_guidance Provides percentage remaining and trend information
     * @production_planning Supports strategic seed procurement decisions
     * 
     * @return void Warning notifications sent to administrative users
     * 
     * @notification_content
     * - Lot numbers with current inventory percentages
     * - Available quantities and consumption trends
     * - Affected recipes and production impact estimates
     * - Recommended procurement timeline and quantities
     * 
     * @example
     * // Called from daily inventory monitoring job
     * $this->sendLowStockAlerts();
     * // Admins receive planning notifications for proactive ordering
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
     * Automatically flag seed lots as depleted based on zero inventory availability.
     * 
     * Scans all active recipes with seed lot assignments and automatically marks
     * lots as depleted when available quantity reaches zero, maintaining accurate
     * inventory status and preventing production planning with unavailable seeds.
     * 
     * @automated_flagging Reduces manual inventory status maintenance
     * @inventory_accuracy Maintains precise lot depletion status
     * @production_safety Prevents planning with unavailable seed lots
     * @system_integrity Ensures consistent inventory state across agricultural operations
     * 
     * @return int Number of recipe lot assignments marked as depleted
     * 
     * @marking_criteria
     * - Recipe is active and has lot assignment
     * - Lot quantity is zero or negative
     * - Not previously marked as depleted
     * 
     * @example
     * $markedCount = $this->markAutomaticDepletion();
     * Log::info("Automatically marked {$markedCount} lots as depleted");
     * 
     * @audit_logging Each automatic marking is logged for inventory traceability
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
     * Generate critical agricultural inventory alerts for dashboard display.
     * 
     * Compiles prioritized list of seed lot inventory issues for real-time
     * dashboard display, providing agricultural managers with immediate visibility
     * into critical and warning-level inventory situations requiring attention.
     * 
     * @dashboard_integration Provides real-time inventory status for management interfaces
     * @prioritized_alerts Orders by severity (critical depleted lots first)
     * @actionable_information Includes specific lot numbers, recipes, and impact details
     * @agricultural_oversight Enables proactive inventory management and decision making
     * 
     * @return array Prioritized alert list sorted by severity and recency
     * 
     * @alert_structure
     * [
     *   'type' => string,              // 'critical' or 'warning'
     *   'title' => string,             // Alert category
     *   'message' => string,           // Detailed alert description
     *   'recipe_id' => int,            // Affected recipe identifier
     *   'lot_number' => string,        // Seed lot identifier
     *   'available_percentage' => float, // For low stock alerts
     *   'created_at' => Carbon         // Alert timestamp
     * ]
     * 
     * @example
     * $alerts = $this->getCriticalAlerts();
     * foreach ($alerts as $alert) {
     *     if ($alert['type'] === 'critical') {
     *         // Display high-priority alert in red
     *     }
     * }
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
     * Construct detailed notification content for critical seed lot depletion alerts.
     * 
     * Formats comprehensive depletion notification including affected lot details,
     * inventory quantities, impacted recipes, and recommended actions for immediate
     * agricultural inventory management response.
     * 
     * @notification_formatting Creates structured, actionable alert content
     * @agricultural_context Provides seed lot and recipe impact details
     * @action_guidance Includes specific steps for inventory restoration
     * @internal Utility method for alert notification construction
     * 
     * @param array $lotDetails Collected lot depletion information with summaries
     * @return string Formatted notification body for admin alerts
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
     * Construct detailed notification content for proactive low stock warnings.
     * 
     * Formats early warning notification including current inventory levels,
     * percentage remaining, affected recipes, and recommended proactive actions
     * for agricultural seed lot management before critical depletion occurs.
     * 
     * @warning_formatting Creates structured, preventive alert content
     * @inventory_trends Provides current levels and consumption patterns
     * @proactive_guidance Includes recommendations for preventive action
     * @internal Utility method for low stock notification construction
     * 
     * @param array $lotDetails Collected low stock lot information with percentages
     * @return string Formatted warning body for proactive admin alerts
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
     * Configure agricultural seed lot low stock alert threshold percentage.
     * 
     * Sets the inventory percentage below which seed lots are considered low stock
     * and trigger proactive warning notifications. Threshold affects agricultural
     * inventory monitoring sensitivity and procurement planning timing.
     * 
     * @threshold_configuration Adjusts sensitivity of low stock detection
     * @agricultural_planning Influences procurement timing and inventory management
     * @alert_tuning Customizes warning levels for operational requirements
     * @bounds_checking Ensures threshold remains within valid 0-100% range
     * 
     * @param float $threshold Percentage (0-100) below which lots trigger low stock alerts
     * @return void Threshold updated for future inventory evaluations
     * 
     * @example
     * // Set alerts for lots with less than 20% remaining
     * $this->setLowStockThreshold(20.0);
     */
    public function setLowStockThreshold(float $threshold): void
    {
        $this->lowStockThreshold = max(0, min(100, $threshold));
    }

    /**
     * Retrieve current agricultural seed lot low stock alert threshold.
     * 
     * Returns the configured percentage below which seed lots trigger low stock
     * warnings, used for inventory monitoring sensitivity and alert configuration
     * display in agricultural management interfaces.
     * 
     * @threshold_inquiry Returns current low stock detection sensitivity
     * @configuration_display Supports admin interface threshold display
     * @monitoring_reference Used in inventory analysis and alert generation
     * 
     * @return float Current threshold percentage (0-100) for low stock alerts
     * 
     * @example
     * $currentThreshold = $this->getLowStockThreshold();
     * echo "Low stock alerts trigger at {$currentThreshold}% remaining";
     */
    public function getLowStockThreshold(): float
    {
        return $this->lowStockThreshold;
    }
}