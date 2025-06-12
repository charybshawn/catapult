<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

class SlidingNavigationBuilder
{
    public static function build(): array
    {
        $staticNavigation = self::getStaticNavigation();
        $dynamicNavigation = self::getDynamicNavigation();
        
        // Merge dynamic items into static structure
        $navigation = $staticNavigation;
        foreach ($dynamicNavigation as $groupKey => $items) {
            if (isset($navigation['submenus'][$groupKey])) {
                $navigation['submenus'][$groupKey]['items'] = array_merge(
                    $navigation['submenus'][$groupKey]['items'] ?? [],
                    $items
                );
            }
        }
        
        return $navigation;
    }

    private static function getStaticNavigation(): array
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
                'products' => [
                    'label' => 'Products',
                    'icon' => 'heroicon-o-shopping-bag',
                    'description' => 'Products and pricing',
                    'badge' => null,
                ],
                'inventory' => [
                    'label' => 'Inventory',
                    'icon' => 'heroicon-o-cube',
                    'description' => 'Seeds, consumables, and supplies',
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
                            'label' => 'Daily Operations',
                            'url' => '/admin/daily-operations',
                            'icon' => 'heroicon-o-clipboard-document-check',
                            'active' => request()->routeIs('filament.admin.pages.daily-operations'),
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
                        // Custom items with badges
                        [
                            'label' => 'Alerts & Tasks',
                            'url' => '/admin/crop-alerts',
                            'icon' => 'heroicon-o-bell-alert',
                            'active' => request()->routeIs('filament.admin.resources.crop-alerts.*'),
                            'badge' => self::getAlertsBadge(),
                        ],
                        // Dynamic resources will be auto-added via getDynamicNavigation()
                    ],
                ],
                
                'products' => [
                    'items' => [
                        // Custom items with badges  
                        [
                            'label' => 'Product Inventory',
                            'url' => '/admin/product-inventories',
                            'icon' => 'heroicon-o-cube',
                            'active' => request()->routeIs('filament.admin.resources.product-inventories.*'),
                            'badge' => self::getProductInventoryBadge(),
                        ],
                        // Dynamic resources will be auto-added via getDynamicNavigation()
                    ],
                ],
                
                'inventory' => [
                    'items' => [
                        // Master Seed Catalog (no dropdown needed)
                        [
                            'label' => 'Master Seed Catalog',
                            'url' => '/admin/master-seed-catalogs',
                            'icon' => 'heroicon-o-clipboard-document-list',
                            'active' => request()->routeIs('filament.admin.resources.master-seed-catalogs.*'),
                        ],
                        // Sub-sub menu: Online Seed Pricing
                        [
                            'label' => 'Online Seed Pricing',
                            'icon' => 'heroicon-o-chart-bar',
                            'type' => 'group', // This indicates it's a dropdown group
                            'children' => [
                                [
                                    'label' => 'Seed Entries',
                                    'url' => '/admin/seed-entries',
                                    'icon' => 'heroicon-o-identification',
                                    'active' => request()->routeIs('filament.admin.resources.seed-entries.*'),
                                ],
                                [
                                    'label' => 'Seed Data Uploads',
                                    'url' => '/admin/seed-data-uploads',
                                    'icon' => 'heroicon-o-arrow-up-tray',
                                    'active' => request()->routeIs('filament.admin.pages.seed-data-uploads'),
                                ],
                                [
                                    'label' => 'Seed Price Trends',
                                    'url' => '/admin/seed-price-trends',
                                    'icon' => 'heroicon-o-chart-bar',
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
                        // Dynamic resources will be auto-added via getDynamicNavigation()
                    ],
                ],
                
                'orders' => [
                    'items' => [
                        // Dynamic resources will be auto-added via getDynamicNavigation()
                    ],
                ],
                
                'system' => [
                    'items' => [
                        // Dynamic resources will be auto-added via getDynamicNavigation()
                    ],
                ],
            ],
        ];
    }

    private static function getDynamicNavigation(): array
    {
        try {
            $panel = Filament::getCurrentPanel();
            if (!$panel) {
                return [];
            }

            $groupedItems = [];

            // Get resources
            $resources = $panel->getResources();
            foreach ($resources as $resource) {
                $navigationGroup = $resource::getNavigationGroup();
                $slidingGroup = self::mapNavigationGroupToSlidingGroup($navigationGroup, $resource);
                
                if ($slidingGroup && !self::isStaticallyDefined($resource)) {
                    $groupedItems[$slidingGroup][] = [
                        'label' => $resource::getNavigationLabel(),
                        'url' => $resource::getUrl('index'),
                        'icon' => $resource::getNavigationIcon() ?? 'heroicon-o-document',
                        'active' => request()->routeIs($resource::getRouteBaseName() . '.*'),
                    ];
                }
            }

            // Get pages
            $pages = $panel->getPages();
            foreach ($pages as $page) {
                $navigationGroup = $page::getNavigationGroup();
                $slidingGroup = self::mapNavigationGroupToSlidingGroup($navigationGroup);
                
                if ($slidingGroup && !self::isStaticallyDefined($page)) {
                    $groupedItems[$slidingGroup][] = [
                        'label' => $page::getNavigationLabel(),
                        'url' => $page::getUrl(),
                        'icon' => $page::getNavigationIcon() ?? 'heroicon-o-document',
                        'active' => request()->routeIs($page::getRouteName()),
                    ];
                }
            }

            // Sort each group's items alphabetically
            foreach ($groupedItems as $group => $items) {
                usort($groupedItems[$group], function ($a, $b) {
                    return strcmp($a['label'], $b['label']);
                });
            }

            return $groupedItems;
        } catch (\Exception $e) {
            // Silently fail if there's an issue with resource discovery
            return [];
        }
    }

    private static function mapNavigationGroupToSlidingGroup(?string $navigationGroup, $resource = null): ?string
    {
        // Handle special case: "Products & Inventory" needs to be split dynamically
        if ($navigationGroup === 'Products & Inventory' && $resource) {
            $resourceName = class_basename($resource);
            
            // Use dynamic logic to determine if it's inventory or products
            // Inventory resources typically manage: supplies, consumables, seeds, packaging
            $inventoryKeywords = ['consumable', 'supplier', 'packaging', 'seed', 'catalog', 'cultivar', 'inventory'];
            $productKeywords = ['product', 'category', 'mix', 'entry'];
            
            $lowerResourceName = strtolower($resourceName);
            
            // Check if resource name contains inventory-related keywords
            foreach ($inventoryKeywords as $keyword) {
                if (str_contains($lowerResourceName, $keyword)) {
                    return 'inventory';
                }
            }
            
            // Check if resource name contains product-related keywords
            foreach ($productKeywords as $keyword) {
                if (str_contains($lowerResourceName, $keyword)) {
                    return 'products';
                }
            }
            
            // Default to inventory for "Products & Inventory" group if unclear
            return 'inventory';
        }
        
        return match ($navigationGroup) {
            // Direct group mappings
            'Seeds' => 'inventory',  // Seeds are inventory items
            'Products' => 'products', // Product catalog items
            'Production' => 'production', 
            'Orders & Sales' => 'orders',
            'System' => 'system',
            default => null,
        };
    }

    private static function isStaticallyDefined($resource): bool
    {
        // Resources that are manually defined in static navigation with special handling
        $staticResources = [
            'CropAlertResource', // Has badge in production menu
            'ProductInventoryResource', // Has badge in products menu
            'SeedEntryResource', // In Online Seed Pricing dropdown
            'MasterSeedCatalogResource', // In inventory menu
            'MasterCultivarResource', // Hidden - managed within Master Seed Catalog
        ];

        $resourceName = class_basename($resource);
        return in_array($resourceName, $staticResources);
    }
    
    private static function getProductionBadge(): ?array
    {
        try {
            // Get overdue task schedules count (using existing TaskSchedule model)
            $overdueCount = \App\Models\TaskSchedule::where('resource_type', 'crops')
                ->where('is_active', true)
                ->where('next_run_at', '<', now())
                ->count();
            
            if ($overdueCount > 0) {
                return [
                    'count' => $overdueCount,
                    'color' => 'danger',
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if there's an issue
        }
        
        return null;
    }
    
    private static function getOrdersBadge(): ?array
    {
        try {
            // Get pending orders count
            $pendingCount = \App\Models\Order::where('status', 'pending')->count();
            
            if ($pendingCount > 0) {
                return [
                    'count' => $pendingCount,
                    'color' => 'warning',
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if there's an issue
        }
        
        return null;
    }
    
    private static function getAlertsBadge(): ?array
    {
        try {
            // Get today's task schedules
            $todayCount = \App\Models\TaskSchedule::where('resource_type', 'crops')
                ->where('is_active', true)
                ->whereDate('next_run_at', today())
                ->count();
            
            if ($todayCount > 0) {
                return [
                    'count' => $todayCount,
                    'color' => 'primary',
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if there's an issue
        }
        
        return null;
    }
    
    private static function getProductInventoryBadge(): ?array
    {
        try {
            // Get count of low stock items
            $lowStockCount = \App\Models\ProductInventory::active()
                ->where('available_quantity', '>', 0)
                ->where('available_quantity', '<=', 10)
                ->count();
            
            if ($lowStockCount > 0) {
                return [
                    'count' => $lowStockCount,
                    'color' => 'warning',
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if there's an issue
        }
        
        return null;
    }
}