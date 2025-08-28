<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;

/**
 * Simple Navigation Builder
 * 
 * Configuration-driven navigation builder for Filament admin panel.
 * Processes JSON configuration to create agricultural farm management
 * navigation structure with dynamic resource integration.
 * 
 * @filament_support Navigation management utility
 * @agricultural_use Farm management interface navigation organization
 * @configuration Reads from config/navigation.json for menu structure
 * @integration Dynamic Filament resource discovery and menu population
 * 
 * Key features:
 * - JSON-driven navigation configuration
 * - Dynamic resource detection and menu integration
 * - Agricultural workflow organization (production, inventory, orders)
 * - Active state management for current page highlighting
 * 
 * @package App\Filament\Support
 * @author Shawn
 * @since 2024
 */
class SimpleNavigationBuilder
{
    /**
     * Build navigation structure from JSON configuration.
     * 
     * @agricultural_context Creates farm management navigation with production, inventory, orders
     * @return array Complete navigation structure with main menu and submenus
     * @configuration Loads from config/navigation.json file
     * @structure Returns ['main' => [...], 'submenus' => [...]] format
     */
    public static function build(): array
    {
        $config = json_decode(file_get_contents(config_path('navigation.json')), true);
        
        // Main menu items
        $mainMenu = $config['main'];
        
        // Process submenus
        $submenus = [];
        foreach ($config['submenus'] as $groupKey => $items) {
            $submenus[$groupKey] = [
                'items' => self::processMenuItems($items)
            ];
        }
        
        return [
            'main' => $mainMenu,
            'submenus' => $submenus
        ];
    }
    
    /**
     * Process menu items recursively, handling resources and groups.
     * 
     * @agricultural_context Processes agricultural resource menu items (crops, products, orders)
     * @param array $items Menu item configuration array
     * @return array Processed menu items with URLs, icons, and active states
     * @dynamic_resources Converts resource names to full navigation items
     */
    private static function processMenuItems(array $items): array
    {
        $processedItems = [];
        
        foreach ($items as $item) {
            if (isset($item['resource'])) {
                // Dynamic resource item
                $resource = self::getResource($item['resource']);
                if ($resource) {
                    $processedItems[] = [
                        'label' => $resource::getNavigationLabel(),
                        'url' => $resource::getUrl('index'),
                        'icon' => $resource::getNavigationIcon() ?? 'heroicon-o-document',
                        'active' => request()->routeIs($resource::getRouteBaseName() . '.*'),
                    ];
                }
            } elseif (isset($item['type']) && $item['type'] === 'group') {
                // Sub-sub menu group
                $item['children'] = self::processMenuItems($item['children']);
                $processedItems[] = $item;
            } else {
                // Static menu item
                if (isset($item['routes'])) {
                    $item['active'] = self::isRouteActive($item['routes']);
                }
                $processedItems[] = $item;
            }
        }
        
        return $processedItems;
    }
    
    /**
     * Find Filament resource by class name.
     * 
     * @agricultural_context Locates agricultural resources (ProductResource, CropResource, etc.)
     * @param string $resourceName Resource class basename (e.g., 'ProductResource')
     * @return string|null Full resource class name if found
     * @filament_integration Uses Filament panel resource discovery
     */
    private static function getResource(string $resourceName): ?string
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        if (!$panel) return null;
        
        foreach ($panel->getResources() as $resource) {
            if (class_basename($resource) === $resourceName) {
                return $resource;
            }
        }
        
        return null;
    }
    
    private static function isRouteActive(array $routes): bool
    {
        foreach ($routes as $route) {
            if (request()->routeIs($route)) {
                return true;
            }
        }
        return false;
    }
}