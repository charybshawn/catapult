<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;

class SimpleNavigationBuilder
{
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
    
    private static function getResource(string $resourceName): ?string
    {
        $panel = Filament::getCurrentPanel();
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