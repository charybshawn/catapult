<?php

namespace App\Filament\Support;

use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

class SlidingNavigationBuilder
{
    public static function build(): array
    {
        return [
            // Main menu groups (what user sees initially)
            'main' => [
                'dashboard' => [
                    'label' => 'Dashboard & Overview',
                    'icon' => 'heroicon-o-home',
                    'description' => 'Farm overview and planning',
                    'badge' => null,
                ],
                'production' => [
                    'label' => 'Production',
                    'icon' => 'heroicon-o-beaker',
                    'description' => 'Crops, recipes, and alerts',
                    'badge' => self::getProductionBadge(),
                ],
                'inventory' => [
                    'label' => 'Products & Inventory',
                    'icon' => 'heroicon-o-cube',
                    'description' => 'Seeds, products, and supplies',
                    'badge' => null,
                ],
                'orders' => [
                    'label' => 'Orders & Sales',
                    'icon' => 'heroicon-o-shopping-cart',
                    'description' => 'Customer orders and invoices',
                    'badge' => self::getOrdersBadge(),
                ],
                'system' => [
                    'label' => 'System',
                    'icon' => 'heroicon-o-cog-6-tooth',
                    'description' => 'Settings and administration',
                    'badge' => null,
                ],
            ],
            
            // Submenu items for each group
            'submenus' => [
                'dashboard' => [
                    'items' => [
                        [
                            'label' => 'Main Dashboard',
                            'url' => '/admin/dashboard',
                            'icon' => 'heroicon-o-home',
                            'active' => request()->routeIs('filament.admin.pages.dashboard'),
                        ],
                        [
                            'label' => 'Weekly Planning',
                            'url' => '/admin/weekly-planning',
                            'icon' => 'heroicon-o-calendar',
                            'active' => request()->routeIs('filament.admin.pages.weekly-planning'),
                        ],
                        [
                            'label' => 'Analytics',
                            'url' => '/admin/analytics',
                            'icon' => 'heroicon-o-chart-bar',
                            'active' => request()->routeIs('filament.admin.pages.analytics'),
                        ],
                    ],
                ],
                
                'production' => [
                    'items' => [
                        [
                            'label' => 'Crops',
                            'url' => '/admin/crops',
                            'icon' => 'heroicon-o-beaker',
                            'active' => request()->routeIs('filament.admin.resources.crops.*'),
                        ],
                        [
                            'label' => 'Recipes',
                            'url' => '/admin/recipes',
                            'icon' => 'heroicon-o-document-text',
                            'active' => request()->routeIs('filament.admin.resources.recipes.*'),
                        ],
                        [
                            'label' => 'Crop Plans',
                            'url' => '/admin/crop-plans',
                            'icon' => 'heroicon-o-calendar-days',
                            'active' => request()->routeIs('filament.admin.resources.crop-plans.*'),
                        ],
                        [
                            'label' => 'Alerts & Tasks',
                            'url' => '/admin/crop-alerts',
                            'icon' => 'heroicon-o-bell-alert',
                            'active' => request()->routeIs('filament.admin.resources.crop-alerts.*'),
                            'badge' => self::getAlertsBadge(),
                        ],
                        [
                            'label' => 'Tasks',
                            'url' => '/admin/tasks',
                            'icon' => 'heroicon-o-check-circle',
                            'active' => request()->routeIs('filament.admin.resources.tasks.*'),
                        ],
                    ],
                ],
                
                'inventory' => [
                    'items' => [
                        [
                            'label' => 'Seeds',
                            'url' => '/admin/seed-entries',
                            'icon' => 'heroicon-o-identification',
                            'active' => request()->routeIs('filament.admin.resources.seed-entries.*'),
                        ],
                        [
                            'label' => 'Products',
                            'url' => '/admin/products',
                            'icon' => 'heroicon-o-shopping-bag',
                            'active' => request()->routeIs('filament.admin.resources.products.*'),
                        ],
                        [
                            'label' => 'Consumables',
                            'url' => '/admin/consumables',
                            'icon' => 'heroicon-o-archive-box',
                            'active' => request()->routeIs('filament.admin.resources.consumables.*'),
                        ],
                        [
                            'label' => 'Categories',
                            'url' => '/admin/categories',
                            'icon' => 'heroicon-o-tag',
                            'active' => request()->routeIs('filament.admin.resources.categories.*'),
                        ],
                        [
                            'label' => 'Suppliers',
                            'url' => '/admin/suppliers',
                            'icon' => 'heroicon-o-building-office',
                            'active' => request()->routeIs('filament.admin.resources.suppliers.*'),
                        ],
                        [
                            'label' => 'Packaging Types',
                            'url' => '/admin/packaging-types',
                            'icon' => 'heroicon-o-cube',
                            'active' => request()->routeIs('filament.admin.resources.packaging-types.*'),
                        ],
                        [
                            'label' => 'Product Mixes',
                            'url' => '/admin/product-mixes',
                            'icon' => 'heroicon-o-puzzle-piece',
                            'active' => request()->routeIs('filament.admin.resources.product-mixes.*'),
                        ],
                        [
                            'label' => 'Seed Price Trends',
                            'url' => '/admin/seed-price-trends',
                            'icon' => 'heroicon-o-chart-line',
                            'active' => request()->routeIs('filament.admin.pages.seed-price-trends'),
                        ],
                        [
                            'label' => 'Reorder Advisor',
                            'url' => '/admin/seed-reorder-advisor',
                            'icon' => 'heroicon-o-bell-alert',
                            'active' => request()->routeIs('filament.admin.pages.seed-reorder-advisor'),
                        ],
                    ],
                ],
                
                'orders' => [
                    'items' => [
                        [
                            'label' => 'Orders',
                            'url' => '/admin/orders',
                            'icon' => 'heroicon-o-shopping-cart',
                            'active' => request()->routeIs('filament.admin.resources.orders.*'),
                        ],
                        [
                            'label' => 'Recurring Orders',
                            'url' => '/admin/recurring-orders',
                            'icon' => 'heroicon-o-arrow-path',
                            'active' => request()->routeIs('filament.admin.resources.recurring-orders.*'),
                        ],
                        [
                            'label' => 'Invoices',
                            'url' => '/admin/invoices',
                            'icon' => 'heroicon-o-document-text',
                            'active' => request()->routeIs('filament.admin.resources.invoices.*'),
                        ],
                        [
                            'label' => 'Customers',
                            'url' => '/admin/users',
                            'icon' => 'heroicon-o-users',
                            'active' => request()->routeIs('filament.admin.resources.users.*'),
                        ],
                    ],
                ],
                
                'system' => [
                    'items' => [
                        [
                            'label' => 'Settings',
                            'url' => '/admin/settings',
                            'icon' => 'heroicon-o-cog-6-tooth',
                            'active' => request()->routeIs('filament.admin.resources.settings.*'),
                        ],
                        [
                            'label' => 'Scheduled Tasks',
                            'url' => '/admin/scheduled-tasks',
                            'icon' => 'heroicon-o-clock',
                            'active' => request()->routeIs('filament.admin.resources.scheduled-tasks.*'),
                        ],
                    ],
                ],
            ],
        ];
    }
    
    private static function getProductionBadge(): ?array
    {
        // Get overdue crop alerts count
        $overdueCount = \App\Models\CropAlert::where('alert_date', '<', now())->count();
        
        if ($overdueCount > 0) {
            return [
                'count' => $overdueCount,
                'color' => 'danger',
            ];
        }
        
        return null;
    }
    
    private static function getOrdersBadge(): ?array
    {
        // Get pending orders count
        $pendingCount = \App\Models\Order::where('status', 'pending')->count();
        
        if ($pendingCount > 0) {
            return [
                'count' => $pendingCount,
                'color' => 'warning',
            ];
        }
        
        return null;
    }
    
    private static function getAlertsBadge(): ?array
    {
        // Get today's alerts
        $todayCount = \App\Models\CropAlert::whereDate('alert_date', today())->count();
        
        if ($todayCount > 0) {
            return [
                'count' => $todayCount,
                'color' => 'primary',
            ];
        }
        
        return null;
    }
}