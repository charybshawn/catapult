<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Crop;
use App\Models\Order;
use App\Models\Consumable;
use App\Models\CropAlert;
use App\Models\CropBatchListView;
use Carbon\Carbon;

class DailyOperations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Daily Operations';
    protected static ?string $title = 'Daily Operations Center';
    protected static bool $shouldRegisterNavigation = false;
    
    protected static string $view = 'filament.pages.daily-operations';
    
    public function getViewData(): array
    {
        return [
            'stats' => $this->getStats(),
            'quickActions' => $this->getQuickActions(),
        ];
    }
    
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