<?php

namespace App\Filament\Resources\ProductInventoryResource\Widgets;

use App\Models\ProductInventoryStatus;
use App\Models\ProductInventory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * Product inventory analytics widget for agricultural inventory value and distribution tracking.
 *
 * Provides comprehensive overview of agricultural product inventory including total
 * inventory value, packaging type distribution, quantity breakdowns, and low stock
 * alerts. Essential for financial planning and inventory management in microgreens
 * production operations.
 *
 * @filament_widget Stats overview widget for product inventory analytics
 * @business_domain Agricultural product inventory value and distribution analysis
 * @financial_metrics Total inventory value calculation from active stock
 * @packaging_analytics Top packaging types by quantity and batch count
 * @inventory_alerts Low stock threshold monitoring for operational continuity
 */
class ProductInventoryStats extends BaseWidget
{
    /** @var string Polling interval for inventory monitoring updates */
    protected ?string $pollingInterval = '30s';
    
    /**
     * Generate comprehensive product inventory statistics for agricultural operations.
     *
     * Calculates total inventory value from active stock, analyzes packaging
     * type distribution with quantity and batch counts, and monitors low stock
     * conditions. Provides essential financial and operational metrics for
     * agricultural inventory management and planning.
     *
     * @return array Filament Stat components with inventory analytics and alerts
     * @business_logic Only includes active inventory status for accurate valuation
     * @financial_analysis Total value calculation from quantity × cost per unit
     * @operational_alerts Low stock threshold monitoring below 10 units
     */
    protected function getStats(): array
    {
        // Get total inventory value
        $activeStatus = ProductInventoryStatus::where('code', 'active')->first();
        $totalValue = ProductInventory::where('product_inventory_status_id', $activeStatus?->id)
            ->selectRaw('SUM(quantity * cost_per_unit) as total')
            ->value('total') ?? 0;
            
        // Get inventory by packaging type
        $packagingStats = DB::table('product_inventories')
            ->join('product_price_variations', 'product_inventories.price_variation_id', '=', 'product_price_variations.id')
            ->leftJoin('packaging_types', 'product_price_variations.packaging_type_id', '=', 'packaging_types.id')
            ->where('product_inventories.product_inventory_status_id', $activeStatus?->id)
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
        $lowStockCount = ProductInventory::where('product_inventory_status_id', $activeStatus?->id)
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