<?php

namespace App\Filament\Support;

use Filament\Facades\Filament;

class SimpleNavigationBuilder
{
    public static function build(): array
    {
        $config = json_decode(file_get_contents(config_path('navigation.json')), true);
        
        // Add badges to main menu items
        $mainMenu = $config['main'];
        $mainMenu['production']['badge'] = self::getProductionBadge();
        $mainMenu['orders']['badge'] = self::getOrdersBadge();
        
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
                if (isset($item['badge'])) {
                    $item['badge'] = self::getBadge($item['badge']);
                }
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
    
    private static function getBadge(string $badgeType): ?array
    {
        return match ($badgeType) {
            'alerts' => self::getAlertsBadge(),
            'product_inventory' => self::getProductInventoryBadge(),
            default => null,
        };
    }
    
    private static function getProductionBadge(): ?array
    {
        try {
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
            // Silently fail
        }
        
        return null;
    }
    
    private static function getOrdersBadge(): ?array
    {
        try {
            $pendingCount = \App\Models\Order::where('status', 'pending')->count();
            
            if ($pendingCount > 0) {
                return [
                    'count' => $pendingCount,
                    'color' => 'warning',
                ];
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        
        return null;
    }
    
    private static function getAlertsBadge(): ?array
    {
        try {
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
            // Silently fail
        }
        
        return null;
    }
    
    private static function getProductInventoryBadge(): ?array
    {
        try {
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
            // Silently fail
        }
        
        return null;
    }
}