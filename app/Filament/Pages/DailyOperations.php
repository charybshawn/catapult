<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Crop;
use App\Models\Order;
use App\Models\Consumable;
use App\Models\CropAlert;
use App\Models\CropBatchListView;
use Carbon\Carbon;

/**
 * Daily Operations Center Page
 * 
 * Central operational dashboard for microgreens farm daily activities.
 * Provides quick access to critical farm tasks, production metrics, and
 * workflow navigation for efficient daily operations management.
 * 
 * @filament_page Central operations dashboard for farm management
 * @agricultural_workflow Supports daily growing, harvesting, inventory tasks
 * @business_operations Tracks pending orders, alerts, stock levels
 * @ui_organization Grouped quick actions by operational category
 * 
 * @package App\Filament\Pages
 * @author Catapult Development Team
 * @version 1.0.0
 */
class DailyOperations extends Page
{
    /**
     * Navigation icon for operations dashboard
     * 
     * @var string Clipboard icon representing daily task management
     */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    /**
     * Navigation label for operations center
     * 
     * @var string Display name in navigation menu
     */
    protected static ?string $navigationLabel = 'Daily Operations';
    
    /**
     * Page title for operations dashboard
     * 
     * @var string Display title for page header
     */
    protected static ?string $title = 'Daily Operations Center';
    
    /**
     * Navigation registration control
     * 
     * @var bool False to exclude from main navigation
     * @performance Reduces navigation queries for operational dashboard
     */
    protected static bool $shouldRegisterNavigation = false;
    
    /**
     * Blade view template for operations page
     * 
     * @var string Path to daily operations template
     */
    protected string $view = 'filament.pages.daily-operations';
    
    /**
     * Get view data for operations dashboard template
     * 
     * Compiles operational statistics and quick action configurations for
     * the daily operations dashboard template display.
     * 
     * @template_data Primary data provider for daily-operations blade template
     * @dashboard_integration Combines stats and quick actions for unified interface
     * @agricultural_operations Provides farm-specific operational data
     * 
     * @return array Template data with statistics and quick action configurations
     */
    public function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'quickActions' => $this->getQuickActions(),
        ];
    }
    
    /**
     * Get operational statistics for dashboard display
     * 
     * Calculates key operational metrics for daily farm management including
     * crop advancement needs, alerts, inventory status, and order backlogs.
     * Uses optimized queries with aggregation for performance.
     * 
     * @agricultural_metrics Core production and inventory statistics
     * @business_intelligence Daily operational KPIs for farm management
     * @performance Uses view-based queries and aggregation for efficiency
     * 
     * @return array Associative array of operational statistics
     */
    protected function getStats(): array
    {
        $today = Carbon::today();
        
        return [
            'crops_to_advance' => CropBatchListView::where('current_stage_code', '!=', 'harvested')
                ->whereRaw("time_to_next_stage_minutes <= 0")
                ->sum('crop_count'),
            'todays_alerts' => CropAlert::whereDate('next_run_at', $today)
                ->where('is_active', true)
                ->count(),
            'low_stock_items' => Consumable::whereRaw('(initial_stock - consumed_quantity) <= restock_threshold')
                ->count(),
            'pending_orders' => Order::join('order_statuses', 'orders.status_id', '=', 'order_statuses.id')
                ->where('order_statuses.code', '=', 'pending')
                ->count(),
            'crops_ready_to_harvest' => CropBatchListView::where('current_stage_code', '=', 'light')
                ->whereRaw("time_to_next_stage_minutes <= 0")
                ->sum('crop_count'),
        ];
    }
    
    /**
     * Get quick action configurations for operations dashboard
     * 
     * Defines grouped quick action categories for daily farm operations including
     * growing, harvesting, inventory management, analytics, and configuration.
     * Each action group contains themed actions with routing and badge integration.
     * 
     * @agricultural_workflow Organizes actions by operational workflow categories
     * @ui_organization Groups related actions with consistent theming and navigation
     * @badge_integration Dynamic badge counts from operational statistics
     * @route_integration Direct links to relevant Filament resource pages
     * 
     * @return array Multi-dimensional array of action group configurations
     */
    protected function getQuickActions(): array
    {
        return [
            'growing' => [
                'title' => 'Growing Operations',
                'icon' => 'heroicon-o-beaker',
                'color' => 'success',
                'actions' => [
                    [
                        'label' => 'Start New Batch',
                        'icon' => 'heroicon-o-plus-circle',
                        'url' => route('filament.admin.resources.crops.create'),
                        'description' => 'Seed new trays and start growing',
                        'color' => 'primary',
                    ],
                    [
                        'label' => 'View All Crops',
                        'icon' => 'heroicon-o-rectangle-stack',
                        'url' => route('filament.admin.resources.crops.index'),
                        'description' => 'Monitor all active crops',
                        'color' => 'info',
                    ],
                    [
                        'label' => 'Crop Alerts',
                        'icon' => 'heroicon-o-bell-alert',
                        'url' => route('filament.admin.resources.crop-alerts.index'),
                        'description' => 'Check stage advancement alerts',
                        'color' => 'warning',
                        'badge' => $this->getStats()['todays_alerts'],
                    ],
                    [
                        'label' => 'Weekly Planning',
                        'icon' => 'heroicon-o-calendar-days',
                        'url' => route('filament.admin.pages.weekly-planning'),
                        'description' => 'Plan your week ahead',
                        'color' => 'secondary',
                    ],
                ],
            ],
            'harvesting' => [
                'title' => 'Harvesting & Packaging',
                'icon' => 'heroicon-o-scissors',
                'color' => 'warning',
                'actions' => [
                    [
                        'label' => 'Harvest Crops',
                        'icon' => 'heroicon-o-scissors',
                        'url' => route('filament.admin.resources.crops.index', ['tableFilters[current_stage][value]' => 'light']),
                        'description' => 'View crops ready for harvest',
                        'color' => 'success',
                        'badge' => $this->getStats()['crops_ready_to_harvest'],
                    ],
                    [
                        'label' => 'Create Order',
                        'icon' => 'heroicon-o-shopping-cart',
                        'url' => route('filament.admin.resources.orders.create'),
                        'description' => 'Record new customer orders',
                        'color' => 'primary',
                    ],
                    [
                        'label' => 'Pending Orders',
                        'icon' => 'heroicon-o-clock',
                        'url' => route('filament.admin.resources.orders.index', ['tableFilters[status][value]' => 'pending']),
                        'description' => 'Process pending orders',
                        'color' => 'warning',
                        'badge' => $this->getStats()['pending_orders'],
                    ],
                    [
                        'label' => 'Generate Invoice',
                        'icon' => 'heroicon-o-document-text',
                        'url' => route('filament.admin.resources.invoices.create'),
                        'description' => 'Create customer invoices',
                        'color' => 'info',
                    ],
                ],
            ],
            'inventory' => [
                'title' => 'Inventory Management',
                'icon' => 'heroicon-o-archive-box',
                'color' => 'primary',
                'actions' => [
                    [
                        'label' => 'Seed Inventory',
                        'icon' => 'heroicon-o-circle-stack',
                        'url' => route('filament.admin.resources.consumables.index', ['tableFilters[type][value]' => 'seed']),
                        'description' => 'Manage seed stock levels',
                        'color' => 'success',
                    ],
                    [
                        'label' => 'Low Stock Alert',
                        'icon' => 'heroicon-o-exclamation-triangle',
                        'url' => route('filament.admin.resources.consumables.index', ['tableFilters[low_stock][value]' => '1']),
                        'description' => 'Items needing reorder',
                        'color' => 'danger',
                        'badge' => $this->getStats()['low_stock_items'],
                    ],
                    [
                        'label' => 'Add Inventory',
                        'icon' => 'heroicon-o-plus',
                        'url' => route('filament.admin.resources.consumables.create'),
                        'description' => 'Record new stock arrivals',
                        'color' => 'primary',
                    ],
                    [
                        'label' => 'Product Inventory',
                        'icon' => 'heroicon-o-cube',
                        'url' => route('filament.admin.resources.product-inventories.index'),
                        'description' => 'Track finished products',
                        'color' => 'info',
                    ],
                ],
            ],
            'data_insights' => [
                'title' => 'Data & Insights',
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'info',
                'actions' => [
                    [
                        'label' => 'Farm Dashboard',
                        'icon' => 'heroicon-o-presentation-chart-bar',
                        'url' => route('filament.admin.pages.dashboard'),
                        'description' => 'Overall farm analytics',
                        'color' => 'primary',
                    ],
                    [
                        'label' => 'Seed Price Trends',
                        'icon' => 'heroicon-o-arrow-trending-up',
                        'url' => route('filament.admin.pages.seed-price-trends'),
                        'description' => 'Monitor seed pricing',
                        'color' => 'warning',
                    ],
                    [
                        'label' => 'Reorder Advisor',
                        'icon' => 'heroicon-o-light-bulb',
                        'url' => route('filament.admin.pages.seed-reorder-advisor'),
                        'description' => 'Smart reordering suggestions',
                        'color' => 'success',
                    ],
                    [
                        'label' => 'Upload Seed Data',
                        'icon' => 'heroicon-o-arrow-up-tray',
                        'url' => route('filament.admin.pages.seed-scrape-uploader'),
                        'description' => 'Import supplier catalogs',
                        'color' => 'secondary',
                    ],
                ],
            ],
            'configuration' => [
                'title' => 'Setup & Configuration',
                'icon' => 'heroicon-o-cog-6-tooth',
                'color' => 'secondary',
                'actions' => [
                    [
                        'label' => 'Recipes',
                        'icon' => 'heroicon-o-book-open',
                        'url' => route('filament.admin.resources.recipes.index'),
                        'description' => 'Growing recipes and methods',
                        'color' => 'primary',
                    ],
                    [
                        'label' => 'Products',
                        'icon' => 'heroicon-o-shopping-bag',
                        'url' => route('filament.admin.resources.products.index'),
                        'description' => 'Product catalog & pricing',
                        'color' => 'info',
                    ],
                    [
                        'label' => 'Suppliers',
                        'icon' => 'heroicon-o-truck',
                        'url' => route('filament.admin.resources.suppliers.index'),
                        'description' => 'Manage supplier information',
                        'color' => 'warning',
                    ],
                    [
                        'label' => 'Settings',
                        'icon' => 'heroicon-o-adjustments-horizontal',
                        'url' => route('filament.admin.resources.settings.index'),
                        'description' => 'System configuration',
                        'color' => 'secondary',
                    ],
                ],
            ],
        ];
    }
}