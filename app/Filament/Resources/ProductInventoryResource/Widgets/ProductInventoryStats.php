<?php

namespace App\Filament\Resources\ProductInventoryResource\Widgets;

use App\Models\ProductInventory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class ProductInventoryStats extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        // Get total inventory value
        $totalValue = ProductInventory::where('status', 'active')
            ->selectRaw('SUM(quantity * cost_per_unit) as total')
            ->value('total') ?? 0;
            
        // Get inventory by packaging type
        $packagingStats = DB::table('product_inventories')
            ->join('product_price_variations', 'product_inventories.price_variation_id', '=', 'product_price_variations.id')
            ->leftJoin('packaging_types', 'product_price_variations.packaging_type_id', '=', 'packaging_types.id')
            ->where('product_inventories.status', 'active')
            ->select(
                DB::raw('COALESCE(packaging_types.name, "No Packaging") as packaging'),
                DB::raw('COUNT(DISTINCT product_inventories.id) as count'),
                DB::raw('SUM(product_inventories.quantity) as total_quantity')
            )
            ->groupBy('packaging_types.name')
            ->orderByDesc('total_quantity')
            ->limit(3)
            ->get();
            
        $stats = [
            Stat::make('Total Inventory Value', '$' . number_format($totalValue, 2))
                ->description('Active inventory value')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
        
        // Add top 3 packaging types
        foreach ($packagingStats as $packagingStat) {
            $stats[] = Stat::make($packagingStat->packaging, number_format($packagingStat->total_quantity, 0))
                ->description($packagingStat->count . ' inventory ' . str()->plural('batch', $packagingStat->count))
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary');
        }
        
        // Add low stock alert
        $lowStockCount = ProductInventory::where('status', 'active')
            ->where('available_quantity', '>', 0)
            ->where('available_quantity', '<=', 10)
            ->count();
            
        if ($lowStockCount > 0) {
            $stats[] = Stat::make('Low Stock Alert', $lowStockCount)
                ->description('Items below threshold')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning');
        }
        
        return $stats;
    }
}