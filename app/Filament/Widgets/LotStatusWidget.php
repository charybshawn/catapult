<?php

namespace App\Filament\Widgets;

use App\Services\InventoryManagementService;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class LotStatusWidget extends Widget
{
    protected static string $view = 'filament.widgets.lot-status-widget';
    
    // Set the default widget positioning
    protected static ?int $sort = 3;
    
    // Refresh interval in seconds (30 minutes)
    protected static ?string $pollingInterval = '1800s';
    
    // Widget height
    protected int | string | array $columnSpan = 'full';

    /**
     * Get lot status data for the widget.
     * 
     * @return array
     */
    public function getLotStatusData(): array
    {
        $inventoryService = app(InventoryManagementService::class);
        
        // Get overall lot statistics
        $lotStatus = $inventoryService->checkAllLots();
        
        // Get critical alerts
        $criticalAlerts = $inventoryService->getCriticalAlerts();
        
        // Get low stock lots with details
        $lowStockLots = $inventoryService->getLowStockLots(15.0);
        
        return [
            'statistics' => [
                'total_lots' => $lotStatus['total_lots'],
                'active_lots' => $lotStatus['active_lots'],
                'depleted_lots' => $lotStatus['depleted_lots'],
                'low_stock_lots' => $lotStatus['low_stock_lots'],
            ],
            'critical_alerts' => collect($criticalAlerts)->take(5)->toArray(),
            'low_stock_lots' => $lowStockLots->take(10)->toArray(),
            'last_updated' => Carbon::now(),
        ];
    }
    
    /**
     * Get the widget data.
     * 
     * @return array
     */
    protected function getViewData(): array
    {
        return $this->getLotStatusData();
    }
}