<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GroupedCropAlertsWidget;
use App\Models\Crop;
use App\Models\Consumable;
use App\Models\TaskSchedule;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Panel;
use Illuminate\Contracts\View\View;

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
        return view('filament.custom-dashboard-header', [
            'activeCropsCount' => $this->getActiveCropsCount(),
            'activeTraysCount' => $this->getActiveTraysCount(),
            'tasksCount' => $this->getTasksCount(),
            'overdueTasksCount' => $this->getOverdueTasksCount(),
            'lowStockCount' => $this->getLowStockCount(),
            'cropsNeedingHarvest' => $this->getCropsNeedingHarvest(),
            'recentlySowedCrops' => $this->getRecentlySowedCrops(),
            'lowStockItems' => $this->getLowStockItems(),
        ]);
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
        return Consumable::whereRaw('(initial_stock - consumed_quantity) <= restock_threshold')->count();
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
        return Consumable::whereRaw('(initial_stock - consumed_quantity) <= restock_threshold')
            ->orderByRaw('(initial_stock - consumed_quantity) / restock_threshold ASC')
            ->take(10)
            ->get();
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
} 