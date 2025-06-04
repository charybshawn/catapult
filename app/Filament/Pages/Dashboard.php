<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GroupedCropAlertsWidget;
use App\Models\Crop;
use App\Models\Consumable;
use App\Models\TaskSchedule;
use App\Models\Order;
use App\Models\Recipe;
use App\Models\SeedVariety;
use App\Services\InventoryService;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Panel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class Dashboard extends BaseDashboard
{
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
    
    // Override the default view
    public function getHeader(): ?View
    {
        return view('filament.custom-dashboard-header', $this->getDashboardData());
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
            ->with(['recipe.seedVariety'])
            ->take(5)
            ->get();
    }
    
    protected function getRecentlySowedCrops()
    {
        return Crop::where('current_stage', 'planting')
            ->orderBy('planted_at', 'desc')
            ->with(['recipe.seedVariety'])
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
        
        return TaskSchedule::where('resource_type', 'crops')
            ->where('is_active', true)
            ->where('next_run_at', '>=', $today)
            ->where('next_run_at', '<', $tomorrow)
            ->orderBy('next_run_at', 'asc')
            ->get();
    }
    
    /**
     * Get crops grouped by stage for dashboard overview
     */
    protected function getCropsByStage(): array
    {
        $crops = Crop::whereNotIn('current_stage', ['harvested'])
            ->with(['recipe.seedVariety'])
            ->get()
            ->groupBy('current_stage');
            
        $stageData = [];
        foreach (['germination', 'blackout', 'light'] as $stage) {
            $stageCrops = $crops->get($stage, collect());
            $stageData[$stage] = [
                'count' => $stageCrops->count(),
                'crops' => $stageCrops->take(5),
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
            ->with(['seedVariety'])
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
            ->with(['recipe.seedVariety', 'order'])
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
            ->with(['recipe.seedVariety'])
            ->get()
            ->groupBy(function ($crop) {
                return $crop->recipe->seedVariety->name ?? 'Unknown';
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
            ->whereHas('recipe.seedVariety', function ($query) use ($varietyName) {
                $query->where('name', $varietyName);
            })
            ->with(['recipe.seedVariety'])
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
                ->with(['recipe.seedVariety'])
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
                    return $crop->recipe->seedVariety->name ?? 'Unknown';
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
} 