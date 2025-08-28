<?php

namespace App\Filament\Support;

use Exception;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;

/**
 * Sliding Navigation Builder
 * 
 * Advanced navigation builder for Filament admin panel implementing sliding
 * navigation patterns optimized for agricultural farm management workflows.
 * Combines static menu structure with dynamic resource discovery.
 * 
 * @filament_support Advanced navigation management with sliding UI patterns
 * @agricultural_use Farm management navigation with production, inventory, orders organization
 * @ui_pattern Sliding navigation with grouped resources and contextual organization
 * @dynamic_resources Automatic discovery and categorization of Filament resources
 * 
 * Key features:
 * - Sliding navigation UI for efficient agricultural workflow navigation
 * - Dynamic resource categorization (production vs inventory vs orders)
 * - Agricultural-specific grouping and organization patterns
 * - Smart resource detection and menu placement
 * 
 * @package App\Filament\Support
 * @author Shawn
 * @since 2024
 */
class SlidingNavigationBuilder
{
    /**
     * Build complete sliding navigation structure.
     * 
     * @agricultural_context Builds farm management navigation with production, inventory, orders
     * @return array Complete navigation structure merging static and dynamic elements
     * @methodology Combines predefined agricultural workflows with discovered resources
     */
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
                ],
                'production' => [
                    'label' => 'Production',
                    'icon' => 'heroicon-o-beaker',
                    'description' => 'Crops, recipes, and alerts',
                ],
                'products' => [
                    'label' => 'Products',
                    'icon' => 'heroicon-o-shopping-bag',
                    'description' => 'Products and pricing',
                ],
                'inventory' => [
                    'label' => 'Inventory',
                    'icon' => 'heroicon-o-cube',
                    'description' => 'Seeds, consumables, and supplies',
                ],
                'orders' => [
                    'label' => 'Orders & Sales',
                    'icon' => 'heroicon-o-shopping-cart',
                    'description' => 'Customer orders and invoices',
                ],
                'system' => [
                    'label' => 'System',
                    'icon' => 'heroicon-o-cog-6-tooth',
                    'description' => 'Settings and administration',
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
            $panel = Filament::getCurrentOrDefaultPanel();
            if (!$panel) {
                return [];
            }

            $groupedItems = [];

            // Get resources
            $resources = $panel->getResources();
            foreach ($resources as $resource) {
                // Skip resources that don't want to be in navigation
                if (method_exists($resource, 'shouldRegisterNavigation') && !$resource::shouldRegisterNavigation()) {
                    continue;
                }
                
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
        } catch (Exception $e) {
            // Silently fail if there's an issue with resource discovery
            return [];
        }
    }

    /**
     * Map Filament navigation groups to sliding navigation groups.
     * 
     * @agricultural_context Maps agricultural resource groups to workflow-based categories
     * @param string|null $navigationGroup Original Filament navigation group
     * @param mixed $resource Resource instance for intelligent categorization
     * @return string|null Sliding navigation group ('production', 'inventory', 'products', 'orders')
     * @intelligent_routing Dynamically categorizes "Products & Inventory" based on resource type
     */
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
            'Customers' => 'customers',
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
}