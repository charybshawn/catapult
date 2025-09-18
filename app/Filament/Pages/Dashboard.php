<?php

namespace App\Filament\Pages;

use App\Models\Consumable;
use App\Models\Crop;
use App\Models\CropPlan;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\TaskSchedule;
use App\Models\TimeCard;
use App\Services\InventoryManagementService;
use App\Services\RecipeVarietyService;
use Carbon\Carbon;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Dashboard extends BaseDashboard
{
    protected static ?string $slug = 'dashboard';

    protected InventoryManagementService $inventoryService;

    protected RecipeVarietyService $varietyService;

    public function __construct()
    {
        $this->inventoryService = app(InventoryManagementService::class);
        $this->varietyService = app(RecipeVarietyService::class);
    }

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Farm Dashboard';

    protected static ?int $navigationSort = -1000; // Ensure Dashboard is always first

    protected static ?string $navigationGroup = null; // Keep Dashboard ungrouped at top level

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
        return Crop::whereHas('currentStage', function ($query) {
            $query->where('code', '!=', 'harvested');
        })->count();
    }

    protected function getActiveTraysCount(): int
    {
        return Crop::whereHas('currentStage', function ($query) {
            $query->where('code', '!=', 'harvested');
        })->distinct('tray_number')->count();
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
        return $this->inventoryService->getLowStockCount();
    }

    protected function getCropsNeedingHarvest()
    {
        return Crop::whereHas('currentStage', function ($query) {
            $query->where('code', 'light');
        })
            ->where('light_at', '<', now()->subDays(7)) // Example logic
            ->with(['recipe.masterSeedCatalog'])
            ->take(5)
            ->get();
    }

    protected function getRecentlySowedCrops()
    {
        return Crop::whereHas('currentStage', function ($query) {
            $query->where('code', 'planting');
        })
            ->orderBy('germination_at', 'desc')
            ->with(['recipe.masterSeedCatalog'])
            ->take(5)
            ->get();
    }

    protected function getLowStockItems()
    {
        return $this->inventoryService->getLowStockItems(10);
    }

    /**
     * Get crop alerts scheduled for today, sorted by next occurrence time
     */
    protected function getTodaysAlerts()
    {
        $now = now();
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();

        $alerts = TaskSchedule::where('resource_type', 'crops')
            ->where('is_active', true)
            ->where('next_run_at', '>=', $now) // Only get alerts from now onwards (not overdue)
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

            return 'Advance to '.ucfirst($stage);
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
                $overdue .= $days.'d ';
            }
            if ($hours > 0 || $days > 0) {
                $overdue .= $hours.'h ';
            }
            $overdue .= $minutes.'m';

            return 'Overdue by '.trim($overdue);
        }

        $diff = $now->diff($nextRunAt);
        $days = $diff->d;
        $hours = $diff->h;
        $minutes = $diff->i;

        $timeUntil = '';
        if ($days > 0) {
            $timeUntil .= $days.'d ';
        }
        if ($hours > 0 || $days > 0) {
            $timeUntil .= $hours.'h ';
        }
        $timeUntil .= $minutes.'m';

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
                    ->where('germination_at', $crop->germination_at)
                    ->where('current_stage_id', $crop->current_stage_id)
                    ->count();

                return "{$batchCount} trays";
            }
        }

        return $alert->conditions['tray_number'] ? 'Single tray' : 'Unknown';
    }

    /**
     * Group alerts by batch (variety + germination_at + current_stage + target_stage)
     */
    protected function groupAlertsByBatch($alerts, $isOverdue = false)
    {
        $groupedAlerts = [];

        // Pre-load all crops to avoid N+1 queries
        $cropIds = $alerts->map(fn ($alert) => $alert->conditions['crop_id'] ?? null)->filter()->unique();
        $crops = Crop::with(['recipe.masterSeedCatalog'])->whereIn('id', $cropIds)->get()->keyBy('id');

        // Pre-load all batch crops to avoid N+1 queries
        $allCrops = Crop::with(['recipe.masterSeedCatalog', 'currentStage'])->get()->groupBy(function ($crop) {
            $plantedAt = $crop->germination_at ? $crop->germination_at->format('Y-m-d') : 'unknown';
            $stageCode = 'unknown';

            // Safely get the stage code
            if ($crop->relationLoaded('currentStage') && $crop->currentStage && is_object($crop->currentStage)) {
                $stageCode = $crop->currentStage->code;
            } elseif ($crop->current_stage_id) {
                // Fallback: load the stage directly
                $stage = \App\Models\CropStage::find($crop->current_stage_id);
                $stageCode = $stage?->code ?? 'unknown';
            }

            return "{$crop->recipe_id}|{$plantedAt}|{$stageCode}";
        });

        foreach ($alerts as $alert) {
            $cropId = $alert->conditions['crop_id'] ?? null;
            $crop = $cropId ? $crops->get($cropId) : null;

            if (! $crop) {
                continue;
            }

            // Create batch key: variety + planted date + current stage + target stage + task

            // Try to get variety name from multiple sources in order of preference
            $variety = $this->varietyService->getFullVarietyName($crop->recipe)
                ?: ($alert->conditions['variety']
                ?? $crop->recipe?->name
                ?? 'Unknown');
            $plantedAt = $crop->germination_at ? $crop->germination_at->format('Y-m-d') : 'unknown';
            // Safely get current stage
            $currentStage = 'unknown';
            if ($crop->relationLoaded('currentStage') && $crop->currentStage && is_object($crop->currentStage)) {
                $currentStage = $crop->currentStage->code;
            } elseif ($crop->current_stage_id) {
                $stage = \App\Models\CropStage::find($crop->current_stage_id);
                $currentStage = $stage?->code ?? 'unknown';
            }

            $targetStage = $alert->conditions['target_stage'] ?? 'unknown';
            $taskName = $alert->task_name;

            $batchKey = "{$variety}|{$plantedAt}|{$currentStage}|{$targetStage}|{$taskName}";

            if (! isset($groupedAlerts[$batchKey])) {
                // Get all crops in this batch from pre-loaded data
                $batchKey2 = "{$crop->recipe_id}|{$plantedAt}|{$currentStage}";
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
                    'germination_at' => $crop->germination_at,
                    'recipe_name' => $crop->recipe->name ?? 'Unknown Recipe',
                    'tray_count' => count($trayNumbers),
                    'tray_numbers' => $trayNumbers,
                    'tray_info' => count($trayNumbers).' trays',
                    'batch_key' => $batchKey,
                    'conditions' => $earliestAlert->conditions,
                    'stage_timings' => $this->getStageTimings($crop),
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
        $crops = Crop::whereHas('currentStage', function ($query) {
            $query->where('code', '!=', 'harvested');
        })
            ->with(['recipe', 'currentStage'])
            ->get()
            ->groupBy(function ($crop) {
                if ($crop->relationLoaded('currentStage') && $crop->currentStage && is_object($crop->currentStage)) {
                    return $crop->currentStage->code;
                } elseif ($crop->current_stage_id) {
                    $stage = \App\Models\CropStage::find($crop->current_stage_id);

                    return $stage?->code ?? 'unknown';
                }

                return 'unknown';
            });

        $stageData = [];
        foreach (['planting', 'germination', 'blackout', 'light'] as $stage) {
            $stageCrops = $crops->get($stage, collect());
            $stageData[$stage] = [
                'count' => $stageCrops->count(),
                'crops' => $stageCrops,
                'overdue_count' => $stageCrops->filter(function ($crop) {
                    return str_contains($crop->timeToNextStage() ?? '', 'Ready to advance');
                })->count(),
            ];
        }

        return $stageData;
    }

    /**
     * Get seed inventory alerts
     */
    protected function getSeedInventoryAlerts()
    {
        return Consumable::whereHas('consumableType', function ($query) {
            $query->where('code', 'seed');
        })
            ->where(function ($query) {
                $query->whereRaw('total_quantity <= restock_threshold')
                    ->orWhereRaw('(initial_stock - consumed_quantity) <= restock_threshold');
            })
            ->with(['masterSeedCatalog'])
            ->orderBy('total_quantity', 'asc')
            ->take(8)
            ->get();
    }

    /**
     * Get packaging inventory alerts
     */
    protected function getPackagingAlerts()
    {
        return Consumable::whereHas('consumableType', function ($query) {
            $query->where('code', 'packaging');
        })
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

        return Crop::whereHas('currentStage', function ($query) {
            $query->where('code', 'light');
        })
            ->with(['recipe.masterSeedCatalog', 'order'])
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
        $cropsByVariety = Crop::whereHas('currentStage', function ($query) {
            $query->where('code', '!=', 'harvested');
        })
            ->with(['recipe.masterSeedCatalog'])
            ->get()
            ->groupBy(function ($crop) {
                return $crop->recipe->cultivar_name ?? 'Unknown';
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
                $stageCode = 'unknown';
                if ($crop->relationLoaded('currentStage') && $crop->currentStage && is_object($crop->currentStage)) {
                    $stageCode = $crop->currentStage->code;
                } elseif ($crop->current_stage_id) {
                    $stage = \App\Models\CropStage::find($crop->current_stage_id);
                    $stageCode = $stage?->code ?? 'unknown';
                }

                return $stageCode === 'light' &&
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
                'recipe_estimate' => $firstCrop->recipe->expected_yield_grams ?? 0,
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
        $harvestedCrops = Crop::whereHas('currentStage', function ($query) {
            $query->where('code', 'harvested');
        })
            ->whereHas('recipe', function ($query) use ($varietyName) {
                $query->where('cultivar_name', $varietyName);
            })
            ->with(['recipe.masterSeedCatalog'])
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
            'recent_harvest_count' => $recentHarvests->count(),
        ];
    }

    /**
     * Calculate yield confidence level based on historical data
     */
    protected function calculateConfidenceLevel(?array $historicalData): string
    {
        if (! $historicalData) {
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
            $harvests = Crop::whereHas('currentStage', function ($query) {
                $query->where('code', 'light');
            })
                ->with(['recipe.masterSeedCatalog'])
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
                    return $this->varietyService->getCultivarName($crop->recipe) ?? 'Unknown';
                })->map->count(),
            ];
        }

        return $schedule;
    }

    /**
     * Get 2 recommendations based on upcoming orders
     */
    protected function getPlantingRecommendations(): array
    {
        $recommendations = [];

        // Get orders for next 2 weeks
        $upcomingOrders = Order::whereBetween('harvest_date', [
            now()->addDays(7),
            now()->addDays(21),
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

                if (! isset($recommendations[$varietyName])) {
                    $recommendations[$varietyName] = [
                        'variety' => $varietyName,
                        'total_needed' => 0,
                        'estimated_trays' => 0,
                        'plant_by_date' => $order->harvest_date->subDays(10), // Estimate 10 days growth
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
            'status' => $utilizationPercent > 90 ? 'critical' : ($utilizationPercent > 75 ? 'warning' : 'good'),
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
            'employees_clocked_in' => TimeCard::active()->count(),
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
        $activeTimeCards = TimeCard::active()
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
            if ($card->clock_in && ! $card->clock_out) {
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
        $completedCards = TimeCard::whereHas('timeCardStatus', function ($q) {
            $q->whereIn('code', ['approved', 'paid']);
        })
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
        return CropPlan::with(['recipe.masterSeedCatalog', 'order.customer', 'status'])
            ->whereHas('status', function ($query) {
                $query->where('code', 'active');
            })
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
        return CropPlan::with(['recipe.masterSeedCatalog', 'order.customer', 'status'])
            ->whereHas('status', function ($query) {
                $query->where('code', 'active');
            })
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
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['pending', 'confirmed', 'processing']);
            })
            ->whereDoesntHave('status', function ($query) {
                $query->where('code', 'template');
            })
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
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['pending', 'confirmed', 'processing']);
            })
            ->where('delivery_date', '>=', now()->subDays(30))
            ->where('delivery_date', '<=', now()->addDays(60))
            ->get();

        foreach ($orders as $order) {
            if ($order->delivery_date) {
                $events[] = [
                    'id' => 'order-'.$order->id,
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
        $cropPlans = CropPlan::with(['recipe.masterSeedCatalog', 'order', 'status'])
            ->where('plant_by_date', '>=', now()->subDays(30))
            ->where('plant_by_date', '<=', now()->addDays(60))
            ->get();

        foreach ($cropPlans as $plan) {
            $color = match ($plan->status?->code) {
                'draft' => '#6b7280', // gray
                'active' => '#3b82f6', // blue
                'completed' => '#10b981', // green
                'overdue' => '#ef4444', // red
                default => '#6b7280',
            };

            // Get variety name - handle missing recipes
            $varietyName = 'Unknown';
            if ($plan->recipe) {
                $varietyName = $this->varietyService->getCommonName($plan->recipe);
            } elseif ($plan->variety_id) {
                // Try to get name from master seed catalog if no recipe
                $masterSeedCatalog = \App\Models\MasterSeedCatalog::find($plan->variety_id);
                if ($masterSeedCatalog) {
                    $varietyName = $masterSeedCatalog->common_name;
                }
            }

            $events[] = [
                'id' => 'plant-'.$plan->id,
                'title' => "Plant: {$varietyName}",
                'start' => $plan->plant_by_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'planting',
                    'planId' => $plan->id,
                    'variety' => $varietyName,
                    'trays' => $plan->trays_needed,
                    'status' => $plan->status?->code,
                ],
            ];
        }

        return $events;
    }

    /**
     * Advance crops to next stage from alert
     */
    public function advanceCropsFromAlert(Request $request): JsonResponse
    {
        $alertIds = $request->input('alert_ids', []);

        if (empty($alertIds)) {
            return response()->json(['success' => false, 'message' => 'No alerts provided']);
        }

        try {
            // Get all task schedules for these alerts
            $alerts = TaskSchedule::whereIn('id', $alertIds)->get();

            $processedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($alerts as $alert) {
                $cropId = $alert->conditions['crop_id'] ?? null;

                if (! $cropId) {
                    $failedCount++;

                    continue;
                }

                $crop = Crop::find($cropId);

                if (! $crop) {
                    $failedCount++;

                    continue;
                }

                try {
                    // Advance the crop stage using the service
                    $taskManagementService = app(\App\Services\CropTaskManagementService::class);
                    $taskManagementService->advanceStage($crop);

                    // Mark the alert as completed
                    $alert->update([
                        'is_active' => false,
                        'completed_at' => now(),
                    ]);

                    $processedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Crop #{$crop->id}: ".$e->getMessage();
                }
            }

            $message = "Advanced {$processedCount} crops successfully.";
            if ($failedCount > 0) {
                $message .= " {$failedCount} failed.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'processed' => $processedCount,
                'failed' => $failedCount,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to advance crops: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Rollback crop stage from alert
     */
    public function rollbackCropFromAlert(Request $request): JsonResponse
    {
        $alertIds = $request->input('alert_ids', []);

        if (empty($alertIds)) {
            return response()->json(['success' => false, 'message' => 'No alerts provided']);
        }

        try {
            // Get all task schedules for these alerts
            $alerts = TaskSchedule::whereIn('id', $alertIds)->get();

            $processedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($alerts as $alert) {
                $cropId = $alert->conditions['crop_id'] ?? null;

                if (! $cropId) {
                    $failedCount++;

                    continue;
                }

                $crop = Crop::find($cropId);

                if (! $crop) {
                    $failedCount++;

                    continue;
                }

                try {
                    // Load current stage if not already loaded
                    if (! $crop->relationLoaded('currentStage')) {
                        $crop->load('currentStage');
                    }

                    // Get current stage code
                    $currentStageCode = $crop->currentStage?->code;
                    if (! $currentStageCode) {
                        throw new \Exception('Cannot determine current stage');
                    }

                    // Rollback the crop stage
                    $previousStage = $this->getPreviousStage($currentStageCode);

                    if (! $previousStage) {
                        throw new \Exception('Cannot rollback from current stage');
                    }

                    // Update crop stage and timestamp
                    $previousStageRecord = \App\Models\CropStage::where('code', $previousStage)->first();
                    if ($previousStageRecord) {
                        $crop->update([
                            'current_stage_id' => $previousStageRecord->id,
                            "{$previousStage}_at" => now(),
                        ]);
                    } else {
                        throw new \Exception('Previous stage not found');
                    }

                    // Reschedule the alert
                    $alert->update([
                        'next_run_at' => now()->addDays(1), // Reschedule for tomorrow
                    ]);

                    $processedCount++;
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Crop #{$crop->id}: ".$e->getMessage();
                }
            }

            $message = "Rolled back {$processedCount} crops successfully.";
            if ($failedCount > 0) {
                $message .= " {$failedCount} failed.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'processed' => $processedCount,
                'failed' => $failedCount,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to rollback crops: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Get the previous stage for a given stage
     */
    protected function getPreviousStage(string $currentStage): ?string
    {
        $stages = [
            'planting' => null,
            'germination' => 'planting',
            'blackout' => 'germination',
            'light' => 'blackout',
            'harvested' => 'light',
        ];

        return $stages[$currentStage] ?? null;
    }

    /**
     * Get stage timing information for a crop
     */
    protected function getStageTimings(Crop $crop): array
    {
        $timings = [];

        // Load current stage if not loaded
        if (! $crop->relationLoaded('currentStage')) {
            $crop->load('currentStage');
        }

        $currentStageCode = $crop->getRelationValue('currentStage')?->code ?? 'unknown';

        // Define stage progression with proper fallback logic for skipped stages
        $stageData = [
            'germination' => [
                'start_field' => 'germination_at',
                'end_field' => 'germination_at',
                'next_stage_start' => 'germination_at',
            ],
            'blackout' => [
                'start_field' => 'germination_at',
                'end_field' => 'blackout_at',
                'next_stage_start' => 'blackout_at',
            ],
            'light' => [
                'start_field' => null, // Will be determined dynamically
                'end_field' => 'light_at',
                'next_stage_start' => 'light_at',
            ],
            'harvested' => [
                'start_field' => 'light_at',
                'end_field' => 'harvested_at',
                'next_stage_start' => null,
            ],
        ];

        // Handle germination stage
        if ($crop->germination_at) {
            $startTime = \Carbon\Carbon::parse($crop->germination_at);
            if ($currentStageCode === 'germination') {
                // Currently in germination stage - show time since planting
                $timings['germination'] = [
                    'status' => 'current',
                    'duration' => $this->formatDuration($startTime->diff(\Carbon\Carbon::now())),
                    'start_date' => $startTime->format('M j, Y g:i A'),
                    'end_date' => null,
                ];
            } elseif ($crop->germination_at && $currentStageCode !== 'germination') {
                // Germination stage completed
                $endTime = \Carbon\Carbon::parse($crop->germination_at);

                // Only show completed germination if it actually took some time
                $duration = $startTime->diff($endTime);
                if ($duration->d > 0 || $duration->h > 0 || $duration->i > 0 || $duration->s > 0) {
                    $timings['germination'] = [
                        'status' => 'completed',
                        'duration' => $this->formatDuration($duration),
                        'start_date' => $startTime->format('M j, Y g:i A'),
                        'end_date' => $endTime->format('M j, Y g:i A'),
                    ];
                }
            }
        }

        // Handle blackout stage (may be skipped)
        if ($crop->germination_at && $crop->blackout_at) {
            // Blackout stage was not skipped
            $startTime = \Carbon\Carbon::parse($crop->germination_at);
            $endTime = \Carbon\Carbon::parse($crop->blackout_at);

            $timings['blackout'] = [
                'status' => 'completed',
                'duration' => $this->formatDuration($startTime->diff($endTime)),
                'start_date' => $startTime->format('M j, Y g:i A'),
                'end_date' => $endTime->format('M j, Y g:i A'),
            ];
        } elseif ($currentStageCode === 'blackout' && $crop->germination_at) {
            // Currently in blackout stage
            $startTime = \Carbon\Carbon::parse($crop->germination_at);
            $timings['blackout'] = [
                'status' => 'current',
                'duration' => $this->formatDuration($startTime->diff(\Carbon\Carbon::now())),
                'start_date' => $startTime->format('M j, Y g:i A'),
                'end_date' => null,
            ];
        }

        // Handle light stage
        if ($currentStageCode === 'light') {
            // Currently in light stage - calculate from when light stage actually started
            $lightStartTime = $crop->blackout_at ?
                \Carbon\Carbon::parse($crop->blackout_at) :
                \Carbon\Carbon::parse($crop->germination_at);

            $timings['light'] = [
                'status' => 'current',
                'duration' => $this->formatDuration($lightStartTime->diff(\Carbon\Carbon::now())),
                'start_date' => $lightStartTime->format('M j, Y g:i A'),
                'end_date' => null,
            ];
        } elseif ($crop->light_at && $crop->harvested_at) {
            // Light stage completed (moved to harvested)
            $lightStartTime = $crop->blackout_at ?
                \Carbon\Carbon::parse($crop->blackout_at) :
                \Carbon\Carbon::parse($crop->germination_at);
            $lightEndTime = \Carbon\Carbon::parse($crop->light_at);

            $timings['light'] = [
                'status' => 'completed',
                'duration' => $this->formatDuration($lightStartTime->diff($lightEndTime)),
                'start_date' => $lightStartTime->format('M j, Y g:i A'),
                'end_date' => $lightEndTime->format('M j, Y g:i A'),
            ];
        }

        // Handle harvested stage
        if ($crop->harvested_at && $crop->light_at) {
            $startTime = \Carbon\Carbon::parse($crop->light_at);
            $endTime = \Carbon\Carbon::parse($crop->harvested_at);

            $timings['harvested'] = [
                'status' => 'completed',
                'duration' => $this->formatDuration($startTime->diff($endTime)),
                'start_date' => $startTime->format('M j, Y g:i A'),
                'end_date' => $endTime->format('M j, Y g:i A'),
            ];
        } elseif ($currentStageCode === 'harvested' && $crop->light_at) {
            $startTime = \Carbon\Carbon::parse($crop->light_at);
            $timings['harvested'] = [
                'status' => 'current',
                'duration' => $this->formatDuration($startTime->diff(\Carbon\Carbon::now())),
                'start_date' => $startTime->format('M j, Y g:i A'),
                'end_date' => null,
            ];
        }

        return $timings;
    }

    /**
     * Format a DateInterval into a human-readable string
     */
    protected function formatDuration(\DateInterval $duration): string
    {
        $parts = [];

        // Check if it's exactly 0 duration (instant transition)
        if ($duration->d === 0 && $duration->h === 0 && $duration->i === 0 && $duration->s === 0) {
            return 'Instant';
        }

        if ($duration->d > 0) {
            $parts[] = $duration->d.' day'.($duration->d > 1 ? 's' : '');
        }

        if ($duration->h > 0) {
            $parts[] = $duration->h.' hour'.($duration->h > 1 ? 's' : '');
        }

        if ($duration->i > 0 && count($parts) < 2) {
            $parts[] = $duration->i.' minute'.($duration->i > 1 ? 's' : '');
        }

        if (empty($parts)) {
            return 'Less than 1 minute';
        }

        return implode(' ', $parts);
    }
}
