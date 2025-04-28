<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GroupedCropAlertsWidget;
use App\Models\Crop;
use App\Models\Consumable;
use App\Models\TaskSchedule;
use Filament\Pages\Page;
use Filament\Navigation\NavigationItem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = null;
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Farm Dashboard';
    protected static ?int $navigationSort = 1;
    
    protected static string $view = 'filament.pages.dashboard';
    
    public $activeTab = 'active-crops';
    
    public function mount(): void
    {
        $this->activeTab = request()->query('tab', 'active-crops');
    }
    
    // Data for the dashboard
    public function getViewData(): array
    {
        $tasksCount = TaskSchedule::where('resource_type', 'crops')->where('is_active', true)->count();
        $overdueTasksCount = TaskSchedule::where('resource_type', 'crops')
            ->where('is_active', true)
            ->where('next_run_at', '<', now())
            ->count();
        
        // Calculate current_stock as initial_stock - consumed_quantity
        $lowStockCount = Consumable::whereRaw('(initial_stock - consumed_quantity) <= restock_threshold')->count();
        
        return [
            'activeCropsCount' => Crop::whereNotIn('current_stage', ['harvested'])->count(),
            'activeTraysCount' => Crop::whereNotIn('current_stage', ['harvested'])->distinct('tray_number')->count(),
            'tasksCount' => $tasksCount,
            'overdueTasksCount' => $overdueTasksCount,
            'lowStockCount' => $lowStockCount,
            'alerts' => [
                'cropTasks' => $tasksCount,
                'overdueCount' => $overdueTasksCount,
                'lowStockCount' => $lowStockCount,
            ],
            'activeTab' => $this->activeTab,
            'cropsNeedingHarvest' => Crop::where('current_stage', 'light')
                ->where('light_at', '<', now()->subDays(7))
                ->with(['recipe.seedVariety'])
                ->take(5)
                ->get(),
            'recentlySowedCrops' => Crop::where('current_stage', 'planting')
                ->orderBy('planted_at', 'desc')
                ->with(['recipe.seedVariety'])
                ->take(5)
                ->get(),
            'lowStockItems' => Consumable::whereRaw('(initial_stock - consumed_quantity) <= restock_threshold')
                ->orderByRaw('(initial_stock - consumed_quantity) / restock_threshold ASC')
                ->take(10)
                ->get(),
            'totalHarvestedCrops' => Crop::where('current_stage', 'harvested')->count(),
            'totalHarvestedWeight' => Crop::where('current_stage', 'harvested')->sum('harvest_weight_grams') ?? 0,
            'totalHarvestedValue' => Crop::where('current_stage', 'harvested')
                ->sum('harvest_weight_grams') / 1000 ?? 0, // Convert grams to kg
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        // Empty since we're using a custom dashboard layout
        return [];
    }
} 