<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GroupedCropAlertsWidget;
use App\Models\Crop;
use App\Models\Consumable;
use App\Models\TaskSchedule;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\SeedEntry;
use App\Models\TimeCard;
use App\Models\User;
use App\Models\CropPlan;
use App\Services\InventoryService;
use App\Services\CropPlanCalculatorService;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Panel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Dashboard extends BaseDashboard
{
    protected static ?string $slug = 'dashboard';
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Farm Dashboard';
    protected static ?int $navigationSort = 1;
    
    // Make dashboard use full width
    protected static bool $isWidgetFullWidth = true;
    protected static bool $isWidgetColumnSpanFull = true;
    protected static bool $shouldSeeFullWidthContent = true;
    
    public function getWidgets(): array
    {
        return [
            // Built-in widgets would go here
        ];
    }
    
    // Use custom view instead of header
    protected static string $view = 'filament.custom-dashboard-header';
    
    /**
     * Get view data for the custom template
     */
    protected function getViewData(): array
    {
        return $this->getDashboardData();
    }
    
    /**
     * AJAX endpoint for dashboard data updates
     */
    public function getDashboardDataAjax(): JsonResponse
    {
        return response()->json($this->getDashboardData());
    }
    
    /**
     * Get all dashboard data in a centralized method
     */
    protected function getDashboardData(): array
    {
        return [
            // Operations Dashboard Data
            'activeCropsCount' => $this->getActiveCropsCount(),
            'activeTraysCount' => $this->getActiveTraysCount(),
            'tasksCount' => $this->getTasksCount(),
            'overdueTasksCount' => $this->getOverdueTasksCount(),
            'cropsNeedingHarvest' => $this->getCropsNeedingHarvest(),
            'recentlySowedCrops' => $this->getRecentlySowedCrops(),
            'cropsByStage' => $this->getCropsByStage(),
            
            // Alerts Data
            'todaysAlerts' => $this->getTodaysAlerts(),
            'upcomingAlerts' => $this->getUpcomingAlerts(),
            'overdueAlerts' => $this->getOverdueAlerts(),
            'alertsSummary' => $this->getAlertsSummary(),
            
            // Inventory & Alerts Data
            'lowStockCount' => $this->getLowStockCount(),
            'lowStockItems' => $this->getLowStockItems(),
            'seedInventoryAlerts' => $this->getSeedInventoryAlerts(),
            'packagingAlerts' => $this->getPackagingAlerts(),
            
            // Harvest & Yield Data
            'upcomingHarvests' => $this->getUpcomingHarvests(),
            'yieldEstimates' => $this->getYieldEstimates(),
            'weeklyHarvestSchedule' => $this->getWeeklyHarvestSchedule(),
            
            // Planning Data
            'plantingRecommendations' => $this->getPlantingRecommendations(),
            'trayUtilization' => $this->getTrayUtilization(),
            
            // Time Management Data
            'timeCardsSummary' => $this->getTimeCardsSummary(),
            'activeEmployees' => $this->getActiveEmployees(),
            'flaggedTimeCards' => $this->getFlaggedTimeCards(),
            
            // Crop Planning Data
            'urgentCropPlans' => $this->getUrgentCropPlans(),
            'overdueCropPlans' => $this->getOverdueCropPlans(),
            'upcomingOrdersNeedingPlans' => $this->getUpcomingOrdersNeedingPlans(),
            'cropPlanningCalendarEvents' => $this->getCropPlanningCalendarEvents(),
        ];
    }
    
    protected function getActiveCropsCount(): int
    {
        return Crop::whereNotIn('current_stage', ['harvested'])->count();
    }
    
    protected function getActiveTraysCount(): int
    {
        return Crop::whereNotIn('current_stage', ['harvested'])->distinct('tray_number')->count();
    }
    
    protected function getTasksCount(): int
    {
        return TaskSchedule::where('resource_type', 'crops')->where('is_active', true)->count();
    }
    
    protected function getOverdueTasksCount(): int
    {
        return TaskSchedule::where('resource_type', 'crops')
            ->where('is_active', true)
            ->where('next_run_at', '<', now())
            ->count();
    }
    
    protected function getLowStockCount(): int
    {
        return app(InventoryService::class)->getLowStockCount();
    }
    
    protected function getCropsNeedingHarvest()
    {
        return Crop::where('current_stage', 'light')
            ->where('light_at', '<', now()->subDays(7)) // Example logic
            ->with(['recipe.seedEntry'])
            ->take(5)
            ->get();
    }
    
    protected function getRecentlySowedCrops()
    {
        return Crop::where('current_stage', 'planting')
            ->orderBy('planted_at', 'desc')
            ->with(['recipe.seedEntry'])
            ->take(5)
            ->get();
    }
    
    protected function getLowStockItems()
    {
        return app(InventoryService::class)->getLowStockItems(10);
    }
    
    /**
     * Get crop alerts scheduled for today, sorted by next occurrence time
     */
    protected function getTodaysAlerts()
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        
        $alerts = TaskSchedule::where('resource_type', 'crops')
            ->where('is_active', true)
            ->where('next_run_at', '>=', $today)
            ->where('next_run_at', '<', $tomorrow)
            ->orderBy('next_run_at', 'asc')
            ->get();
            
        return $this->groupAlertsByBatch($alerts);
    }
    
    /**
     * Get upcoming alerts for the next 7 days
     */
    protected function getUpcomingAlerts()
    {
        $now = now();
        $nextWeek = now()->addDays(7);
        
        $alerts = TaskSchedule::where('resource_type', 'crops')
            ->where('is_active', true)
            ->where('next_run_at', '>=', $now)
            ->where('next_run_at', '<=', $nextWeek)
            ->orderBy('next_run_at', 'asc')
            ->get();
            
        return $this->groupAlertsByBatch($alerts);
    }
    
    /**
     * Get overdue alerts
     */
    protected function getOverdueAlerts()
    {
        $now = now();
        
        $alerts = TaskSchedule::where('resource_type', 'crops')
            ->where('is_active', true)
            ->where('next_run_at', '<', $now)
            ->orderBy('next_run_at', 'asc')
            ->get();
            
        return $this->groupAlertsByBatch($alerts, true);
    }
    
    /**
     * Get readable alert type from task name
     */
    protected function getAlertType(string $taskName): string
    {
        if (str_starts_with($taskName, 'advance_to_')) {
            $stage = str_replace('advance_to_', '', $taskName);
            return 'Advance to ' . ucfirst($stage);
        }
        
        if ($taskName === 'suspend_watering') {
            return 'Suspend Watering';
        }
        
        return ucfirst(str_replace('_', ' ', $taskName));
    }
    
    /**
     * Get time until alert in human readable format
     */
    protected function getTimeUntil(Carbon $nextRunAt): string
    {
        $now = Carbon::now();
        
        if ($nextRunAt->isPast()) {
            $diff = $now->diff($nextRunAt);
            $days = $diff->d;
            $hours = $diff->h;
            $minutes = $diff->i;
            
            $overdue = '';
            if ($days > 0) {
                $overdue .= $days . 'd ';
            }
            if ($hours > 0 || $days > 0) {
                $overdue .= $hours . 'h ';
            }
            $overdue .= $minutes . 'm';
            
            return 'Overdue by ' . trim($overdue);
        }
        
        $diff = $now->diff($nextRunAt);
        $days = $diff->d;
        $hours = $diff->h;
        $minutes = $diff->i;
        
        $timeUntil = '';
        if ($days > 0) {
            $timeUntil .= $days . 'd ';
        }
        if ($hours > 0 || $days > 0) {
            $timeUntil .= $hours . 'h ';
        }
        $timeUntil .= $minutes . 'm';
        
        return trim($timeUntil);
    }
    
    /**
     * Get alert priority based on timing and type
     */
    protected function getAlertPriority($alert): string
    {
        if ($alert->next_run_at->isPast()) {
            return 'critical';
        }
        
        if ($alert->next_run_at->isToday()) {
            return 'high';
        }
        
        if ($alert->next_run_at->isTomorrow()) {
            return 'medium';
        }
        
        return 'low';
    }
    
    /**
     * Get tray information from alert conditions
     */
    protected function getTrayInfo($alert): string
    {
        if (isset($alert->conditions['tray_numbers']) && is_array($alert->conditions['tray_numbers'])) {
            $count = count($alert->conditions['tray_numbers']);
            return "{$count} trays";
        }
        
        if (isset($alert->conditions['crop_id'])) {
            $cropId = $alert->conditions['crop_id'];
            $crop = Crop::find($cropId);
            
            if ($crop) {
                $batchCount = Crop::where('recipe_id', $crop->recipe_id)
                    ->where('planted_at', $crop->planted_at)
                    ->where('current_stage', $crop->current_stage)
                    ->count();
                    
                return "{$batchCount} trays";
            }
        }
        
        return $alert->conditions['tray_number'] ? 'Single tray' : 'Unknown';
    }
    
    /**
     * Group alerts by batch (variety + planted_at + current_stage + target_stage)
     */
    protected function groupAlertsByBatch($alerts, $isOverdue = false)
    {
        $groupedAlerts = [];
        
        // Pre-load all crops to avoid N+1 queries
        $cropIds = $alerts->map(fn($alert) => $alert->conditions['crop_id'] ?? null)->filter()->unique();
        $crops = Crop::with(['recipe.seedEntry'])->whereIn('id', $cropIds)->get()->keyBy('id');
        
        // Pre-load all batch crops to avoid N+1 queries
        $allCrops = Crop::with(['recipe.seedEntry'])->get()->groupBy(function($crop) {
            $plantedAt = $crop->planted_at ? $crop->planted_at->format('Y-m-d') : 'unknown';
            return "{$crop->recipe_id}|{$plantedAt}|{$crop->current_stage}";
        });
        
        foreach ($alerts as $alert) {
            $cropId = $alert->conditions['crop_id'] ?? null;
            $crop = $cropId ? $crops->get($cropId) : null;
            
            if (!$crop) continue;
            
            // Create batch key: variety + planted date + current stage + target stage + task
            
            // Try to get variety name from multiple sources in order of preference
            $variety = $crop->recipe?->seedEntry?->cultivar_name 
                ?? $alert->conditions['variety'] 
                ?? $crop->recipe?->name 
                ?? 'Unknown';
            $plantedAt = $crop->planted_at ? $crop->planted_at->format('Y-m-d') : 'unknown';
            $currentStage = $crop->current_stage;
            $targetStage = $alert->conditions['target_stage'] ?? 'unknown';
            $taskName = $alert->task_name;
            
            $batchKey = "{$variety}|{$plantedAt}|{$currentStage}|{$targetStage}|{$taskName}";
            
            if (!isset($groupedAlerts[$batchKey])) {
                // Get all crops in this batch from pre-loaded data
                $batchKey2 = "{$crop->recipe_id}|{$plantedAt}|{$crop->current_stage}";
                $batchCrops = $allCrops->get($batchKey2, collect());
                
                // Get all tray numbers for this batch
                $trayNumbers = $batchCrops->pluck('tray_number')->sort()->values()->toArray();
                
                // Use the current alert as the representative for this batch
                $earliestAlert = $alert;
                
                $groupedAlerts[$batchKey] = (object) [
                    'id' => $earliestAlert->id, // Use earliest alert ID for actions
                    'alert_ids' => [], // Will collect all alert IDs in this batch
                    'task_name' => $earliestAlert->task_name,
                    'alert_type' => $this->getAlertType($earliestAlert->task_name),
                    'next_run_at' => $earliestAlert->next_run_at,
                    'time_until' => $this->getTimeUntil($earliestAlert->next_run_at),
                    'is_overdue' => $isOverdue,
                    'is_today' => $earliestAlert->next_run_at->isToday(),
                    'priority' => $isOverdue ? 'critical' : $this->getAlertPriority($earliestAlert),
                    'variety' => $variety,
                    'target_stage' => $targetStage,
                    'current_stage' => $currentStage,
                    'planted_at' => $crop->planted_at,
                    'recipe_name' => $crop->recipe->name ?? 'Unknown Recipe',
                    'tray_count' => count($trayNumbers),
                    'tray_numbers' => $trayNumbers,
                    'tray_info' => count($trayNumbers) . ' trays',
                    'batch_key' => $batchKey,
                    'conditions' => $earliestAlert->conditions,
                ];
            }
            
            // Add this alert ID to the batch
            $groupedAlerts[$batchKey]->alert_ids[] = $alert->id;
        }
        
        return collect(array_values($groupedAlerts));
    }
    
    /**
     * Get alerts summary statistics
     */
    protected function getAlertsSummary(): array
    {
        $now = now();
        $overdueAlerts = $this->getOverdueAlerts();
        $todaysAlerts = $this->getTodaysAlerts();
        $upcomingAlerts = $this->getUpcomingAlerts();
        
        return [
            'total' => $upcomingAlerts->count(),
            'overdue' => $overdueAlerts->count(),
            'today' => $todaysAlerts->count(),
            'this_week' => $upcomingAlerts->filter(function ($alert) {
                return $alert->next_run_at->isCurrentWeek();
            })->count(),
            'critical' => $upcomingAlerts->filter(function ($alert) {
                return $alert->priority === 'critical';
            })->count(),
            'high' => $upcomingAlerts->filter(function ($alert) {
                return $alert->priority === 'high';
            })->count(),
        ];
    }
    
    /**
     * Get crops grouped by stage for dashboard overview
     */
    protected function getCropsByStage(): array
    {
        $crops = Crop::whereNotIn('current_stage', ['harvested'])
            ->with(['recipe.seedEntry'])
            ->get()
            ->groupBy('current_stage');
            
        $stageData = [];
        foreach (['planting', 'germination', 'blackout', 'light'] as $stage) {
            $stageCrops = $crops->get($stage, collect());
            $stageData[$stage] = [
                'count' => $stageCrops->count(),
                'crops' => $stageCrops,
                'overdue_count' => $stageCrops->filter(function ($crop) {
                    return str_contains($crop->timeToNextStage() ?? '', 'Ready to advance');
                })->count()
            ];
        }
        
        return $stageData;
    }
    
    /**
     * Get seed inventory alerts
     */
    protected function getSeedInventoryAlerts()
    {
        return Consumable::where('type', 'seed')
            ->where(function ($query) {
                $query->whereRaw('total_quantity <= restock_threshold')
                      ->orWhereRaw('(initial_stock - consumed_quantity) <= restock_threshold');
            })
            ->with(['seedEntry'])
            ->orderBy('total_quantity', 'asc')
            ->take(8)
            ->get();
    }
    
    /**
     * Get packaging inventory alerts
     */
    protected function getPackagingAlerts()
    {
        return Consumable::where('type', 'packaging')
            ->whereRaw('(initial_stock - consumed_quantity) <= restock_threshold')
            ->with(['packagingType'])
            ->orderByRaw('(initial_stock - consumed_quantity) ASC')
            ->take(5)
            ->get();
    }
    
    /**
     * Get upcoming harvests for the next 7 days
     */
    protected function getUpcomingHarvests()
    {
        $nextWeek = now()->addDays(7);
        
        return Crop::where('current_stage', 'light')
            ->with(['recipe.seedEntry', 'order'])
            ->get()
            ->filter(function ($crop) use ($nextWeek) {
                $expectedHarvest = $crop->expectedHarvestDate();
                return $expectedHarvest && $expectedHarvest->lte($nextWeek);
            })
            ->sortBy(function ($crop) {
                return $crop->expectedHarvestDate();
            })
            ->take(10);
    }
    
    /**
     * Get yield estimates by variety using historical data
     */
    protected function getYieldEstimates(): array
    {
        $estimates = [];
        
        // Get active crops grouped by variety
        $cropsByVariety = Crop::whereNotIn('current_stage', ['harvested'])
            ->with(['recipe.seedEntry'])
            ->get()
            ->groupBy(function ($crop) {
                return $crop->recipe->seedEntry->cultivar_name ?? 'Unknown';
            });
            
        foreach ($cropsByVariety as $varietyName => $crops) {
            $totalTrays = $crops->count();
            $firstCrop = $crops->first();
            
            // Get historical yield data for this variety
            $historicalYield = $this->getHistoricalYieldForVariety($varietyName);
            
            // Use historical data if available, otherwise fall back to recipe estimate
            $avgYieldPerTray = $historicalYield['avg_yield_per_tray'] ?? ($firstCrop->recipe->expected_yield_grams ?? 0);
            $totalEstimatedYield = $totalTrays * $avgYieldPerTray;
            
            $readyToHarvestCount = $crops->filter(function ($crop) {
                return $crop->current_stage === 'light' && 
                       str_contains($crop->timeToNextStage() ?? '', 'Ready to advance');
            })->count();
            
            $estimates[] = [
                'variety' => $varietyName,
                'trays' => $totalTrays,
                'estimated_yield_grams' => $totalEstimatedYield,
                'estimated_yield_kg' => round($totalEstimatedYield / 1000, 2),
                'ready_to_harvest' => $readyToHarvestCount,
                'historical_data_available' => $historicalYield !== null,
                'confidence_level' => $this->calculateConfidenceLevel($historicalYield),
                'yield_variance' => $historicalYield['variance'] ?? null,
                'last_30_days_avg' => $historicalYield['last_30_days_avg'] ?? null,
                'recipe_estimate' => $firstCrop->recipe->expected_yield_grams ?? 0
            ];
        }
        
        return collect($estimates)->sortByDesc('estimated_yield_grams')->take(8)->toArray();
    }
    
    /**
     * Get historical yield data for a specific variety
     */
    protected function getHistoricalYieldForVariety(string $varietyName): ?array
    {
        // Get harvested crops for this variety with yield data
        $harvestedCrops = Crop::where('current_stage', 'harvested')
            ->whereNotNull('harvest_weight_grams')
            ->where('harvest_weight_grams', '>', 0)
            ->whereHas('recipe.seedEntry', function ($query) use ($varietyName) {
                $query->where('cultivar_name', $varietyName);
            })
            ->with(['recipe.seedEntry'])
            ->get();
            
        if ($harvestedCrops->isEmpty()) {
            return null;
        }
        
        // Calculate statistics
        $yields = $harvestedCrops->pluck('harvest_weight_grams');
        $avgYield = $yields->avg();
        $variance = $this->calculateVariance($yields->toArray());
        
        // Get recent harvests (last 30 days)
        $recentHarvests = $harvestedCrops->filter(function ($crop) {
            return $crop->harvested_at && $crop->harvested_at->gte(now()->subDays(30));
        });
        
        $last30DaysAvg = $recentHarvests->isEmpty() ? null : $recentHarvests->avg('harvest_weight_grams');
        
        return [
            'avg_yield_per_tray' => round($avgYield, 2),
            'total_harvests' => $harvestedCrops->count(),
            'variance' => round($variance, 2),
            'std_deviation' => round(sqrt($variance), 2),
            'min_yield' => $yields->min(),
            'max_yield' => $yields->max(),
            'last_30_days_avg' => $last30DaysAvg ? round($last30DaysAvg, 2) : null,
            'recent_harvest_count' => $recentHarvests->count()
        ];
    }
    
    /**
     * Calculate yield confidence level based on historical data
     */
    protected function calculateConfidenceLevel(?array $historicalData): string
    {
        if (!$historicalData) {
            return 'low'; // No historical data
        }
        
        $harvestCount = $historicalData['total_harvests'];
        $variance = $historicalData['variance'];
        $avgYield = $historicalData['avg_yield_per_tray'];
        
        // Calculate coefficient of variation (lower is better)
        $coefficientOfVariation = $avgYield > 0 ? sqrt($variance) / $avgYield : 1;
        
        // Determine confidence based on sample size and consistency
        if ($harvestCount >= 10 && $coefficientOfVariation < 0.2) {
            return 'high';
        } elseif ($harvestCount >= 5 && $coefficientOfVariation < 0.3) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    /**
     * Calculate variance for an array of values
     */
    protected function calculateVariance(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / count($values);
        $sumSquares = array_sum(array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values));
        
        return $sumSquares / (count($values) - 1); // Sample variance
    }
    
    /**
     * Get weekly harvest schedule
     */
    protected function getWeeklyHarvestSchedule(): array
    {
        $schedule = [];
        
        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i);
            $harvests = Crop::where('current_stage', 'light')
                ->with(['recipe.seedEntry'])
                ->get()
                ->filter(function ($crop) use ($date) {
                    $expectedHarvest = $crop->expectedHarvestDate();
                    return $expectedHarvest && $expectedHarvest->isSameDay($date);
                });
                
            $schedule[] = [
                'date' => $date,
                'day_name' => $date->format('l'),
                'harvest_count' => $harvests->count(),
                'varieties' => $harvests->groupBy(function ($crop) {
                    return $crop->recipe->seedEntry->cultivar_name ?? 'Unknown';
                })->map->count()
            ];
        }
        
        return $schedule;
    }
    
    /**
     * Get planting recommendations based on upcoming orders
     */
    protected function getPlantingRecommendations(): array
    {
        $recommendations = [];
        
        // Get orders for next 2 weeks
        $upcomingOrders = Order::whereBetween('harvest_date', [
            now()->addDays(7),
            now()->addDays(21)
        ])->with(['orderItems'])->get();
        
        if ($upcomingOrders->isEmpty()) {
            return $recommendations;
        }
        
        // Group by variety and calculate needed trays
        foreach ($upcomingOrders as $order) {
            foreach ($order->orderItems as $item) {
                // This would need proper product-to-variety mapping
                // For now, simplified logic
                $varietyName = $item->name ?? 'Unknown';
                $quantity = $item->quantity;
                
                if (!isset($recommendations[$varietyName])) {
                    $recommendations[$varietyName] = [
                        'variety' => $varietyName,
                        'total_needed' => 0,
                        'estimated_trays' => 0,
                        'plant_by_date' => $order->harvest_date->subDays(10) // Estimate 10 days growth
                    ];
                }
                
                $recommendations[$varietyName]['total_needed'] += $quantity;
                $recommendations[$varietyName]['estimated_trays'] += ceil($quantity / 50); // Assume 50g per tray
            }
        }
        
        return array_values($recommendations);
    }
    
    /**
     * Get tray utilization statistics
     */
    protected function getTrayUtilization(): array
    {
        $totalTrays = 100; // This should come from configuration
        $activeTrays = $this->getActiveTraysCount();
        $utilizationPercent = round(($activeTrays / $totalTrays) * 100, 1);
        
        return [
            'total_trays' => $totalTrays,
            'active_trays' => $activeTrays,
            'available_trays' => $totalTrays - $activeTrays,
            'utilization_percent' => $utilizationPercent,
            'status' => $utilizationPercent > 90 ? 'critical' : ($utilizationPercent > 75 ? 'warning' : 'good')
        ];
    }
    
    /**
     * Get time cards summary for dashboard
     */
    protected function getTimeCardsSummary(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();
        
        return [
            'employees_clocked_in' => TimeCard::where('status', 'active')->count(),
            'flagged_time_cards' => TimeCard::where('requires_review', true)->count(),
            'total_hours_today' => $this->getTotalHoursForPeriod($today, now()),
            'total_hours_this_week' => $this->getTotalHoursForPeriod($thisWeek, now()),
            'total_hours_this_month' => $this->getTotalHoursForPeriod($thisMonth, now()),
            'average_daily_hours' => $this->getAverageDailyHours(),
        ];
    }
    
    /**
     * Get currently active employees
     */
    protected function getActiveEmployees(): array
    {
        $activeTimeCards = TimeCard::where('status', 'active')
            ->with('user')
            ->orderBy('clock_in', 'asc')
            ->get();
            
        return $activeTimeCards->map(function ($timeCard) {
            return [
                'id' => $timeCard->id,
                'user_name' => $timeCard->user->name,
                'clock_in' => $timeCard->clock_in,
                'elapsed_time' => $timeCard->elapsed_time,
                'hours_worked' => $timeCard->clock_in->diffInHours(now()),
                'is_flagged' => $timeCard->requires_review,
                'needs_attention' => $timeCard->clock_in->diffInHours(now()) >= 7, // Near 8-hour limit
            ];
        })->toArray();
    }
    
    /**
     * Get time cards that are flagged for review
     */
    protected function getFlaggedTimeCards(): array
    {
        $flaggedCards = TimeCard::where('requires_review', true)
            ->with('user')
            ->orderBy('max_shift_exceeded_at', 'desc')
            ->take(10)
            ->get();
            
        return $flaggedCards->map(function ($timeCard) {
            return [
                'id' => $timeCard->id,
                'user_name' => $timeCard->user->name,
                'clock_in' => $timeCard->clock_in,
                'elapsed_time' => $timeCard->elapsed_time,
                'exceeded_at' => $timeCard->max_shift_exceeded_at,
                'flags' => $timeCard->flags ?? [],
                'work_date' => $timeCard->work_date,
            ];
        })->toArray();
    }
    
    /**
     * Calculate total hours worked for a given period
     */
    protected function getTotalHoursForPeriod(Carbon $start, Carbon $end): float
    {
        $timeCards = TimeCard::whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get();
            
        $totalMinutes = 0;
        
        foreach ($timeCards as $card) {
            if ($card->status === 'active') {
                $totalMinutes += $card->clock_in->diffInMinutes(now());
            } else {
                $totalMinutes += $card->duration_minutes ?? 0;
            }
        }
        
        return round($totalMinutes / 60, 1);
    }
    
    /**
     * Get average daily hours worked over the last 30 days
     */
    protected function getAverageDailyHours(): float
    {
        $thirtyDaysAgo = now()->subDays(30)->startOfDay();
        $completedCards = TimeCard::where('status', 'completed')
            ->where('work_date', '>=', $thirtyDaysAgo->toDateString())
            ->get();
            
        if ($completedCards->isEmpty()) {
            return 0;
        }
        
        $totalMinutes = $completedCards->sum('duration_minutes');
        $totalDays = $completedCards->groupBy('work_date')->count();
        
        if ($totalDays === 0) {
            return 0;
        }
        
        return round(($totalMinutes / 60) / $totalDays, 1);
    }
    
    /**
     * Get urgent crop plans that need to be planted soon (next 7 days)
     */
    protected function getUrgentCropPlans()
    {
        return CropPlan::with(['recipe.seedEntry', 'order.customer'])
            ->where('status', 'approved')
            ->where('plant_by_date', '<=', now()->addDays(7))
            ->where('plant_by_date', '>=', now())
            ->orderBy('plant_by_date', 'asc')
            ->get()
            ->groupBy(function ($plan) {
                return $plan->plant_by_date->format('Y-m-d');
            });
    }

    /**
     * Get crop plans that should have been planted already (overdue)
     */
    protected function getOverdueCropPlans()
    {
        return CropPlan::with(['recipe.seedEntry', 'order.customer'])
            ->where('status', 'approved')
            ->where('plant_by_date', '<', now())
            ->orderBy('plant_by_date', 'asc')
            ->get();
    }

    /**
     * Get orders for the next 14 days that might need crop plans
     */
    protected function getUpcomingOrdersNeedingPlans()
    {
        return Order::with(['customer', 'orderItems.product', 'cropPlans'])
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->where('status', '!=', 'template') // Exclude recurring order templates
            ->whereNotNull('delivery_date') // Ensure there's a delivery date
            ->where('delivery_date', '>=', now())
            ->where('delivery_date', '<=', now()->addDays(14))
            ->orderBy('delivery_date', 'asc')
            ->get()
            ->filter(function ($order) {
                // Only include orders that don't have crop plans yet
                return $order->cropPlans->isEmpty();
            });
    }

    /**
     * Get calendar events for crop planning (deliveries and plantings)
     */
    protected function getCropPlanningCalendarEvents(): array
    {
        $events = [];
        
        // Add order delivery dates
        $orders = Order::with(['customer'])
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->where('delivery_date', '>=', now()->subDays(30))
            ->where('delivery_date', '<=', now()->addDays(60))
            ->get();
            
        foreach ($orders as $order) {
            if ($order->delivery_date) {
                $events[] = [
                    'id' => 'order-' . $order->id,
                    'title' => "Delivery: Order #{$order->id}",
                    'start' => $order->delivery_date->format('Y-m-d'),
                    'backgroundColor' => '#10b981', // green
                    'borderColor' => '#059669',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'delivery',
                        'orderId' => $order->id,
                        'customer' => $order->customer->contact_name ?? 'Unknown',
                    ],
                ];
            }
        }
        
        // Add crop planting dates
        $cropPlans = CropPlan::with(['recipe.seedEntry', 'order'])
            ->where('plant_by_date', '>=', now()->subDays(30))
            ->where('plant_by_date', '<=', now()->addDays(60))
            ->get();
            
        foreach ($cropPlans as $plan) {
            $color = match($plan->status) {
                'draft' => '#6b7280', // gray
                'approved' => '#3b82f6', // blue
                'completed' => '#10b981', // green
                'overdue' => '#ef4444', // red
                default => '#6b7280',
            };
            
            $events[] = [
                'id' => 'plant-' . $plan->id,
                'title' => "Plant: {$plan->recipe->seedEntry->common_name}",
                'start' => $plan->plant_by_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'planting',
                    'planId' => $plan->id,
                    'variety' => $plan->recipe->seedEntry->common_name,
                    'trays' => $plan->trays_needed,
                    'status' => $plan->status,
                ],
            ];
        }
        
        return $events;
    }
} 