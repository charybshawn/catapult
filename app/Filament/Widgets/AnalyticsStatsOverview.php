<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $analytics = Cache::remember('analytics_stats_overview', now()->addMinutes(15), function () {
            return $this->calculateAnalyticsData();
        });

        return [
            Stat::make('Total Revenue (30 days)', '$' . number_format($analytics['current_revenue'], 2))
                ->description($analytics['revenue_change'])
                ->descriptionIcon($analytics['revenue_icon'])
                ->color($analytics['revenue_color'])
                ->chart($analytics['revenue_chart']),
                
            Stat::make('Orders (30 days)', number_format($analytics['current_orders']))
                ->description($analytics['orders_change'])
                ->descriptionIcon($analytics['orders_icon'])
                ->color($analytics['orders_color']),
                
            Stat::make('Avg Order Value', '$' . number_format($analytics['current_aov'], 2))
                ->description($analytics['aov_change'])
                ->descriptionIcon($analytics['aov_icon'])
                ->color($analytics['aov_color']),
                
            Stat::make('Active Products', number_format($analytics['active_products']))
                ->description('Products with sales')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),
        ];
    }
    
    private function calculateAnalyticsData(): array
    {
        $currentPeriodStart = Carbon::now()->subDays(30);
        $previousPeriodStart = Carbon::now()->subDays(60);
        $previousPeriodEnd = Carbon::now()->subDays(30);
        
        // Single optimized query to get all metrics for both periods
        $revenueData = DB::select("
            SELECT 
                CASE 
                    WHEN orders.created_at >= ? THEN 'current'
                    ELSE 'previous'
                END AS period,
                COUNT(DISTINCT orders.id) as order_count,
                SUM(order_products.quantity * order_products.price) as revenue,
                COUNT(DISTINCT order_products.product_id) as active_products_count
            FROM orders
            JOIN order_products ON orders.id = order_products.order_id
            JOIN invoices ON orders.id = invoices.order_id
            WHERE orders.created_at >= ?
                AND orders.created_at < NOW()
                AND invoices.sent_at IS NOT NULL
            GROUP BY period
        ", [$currentPeriodStart, $previousPeriodStart]);
        
        // Process results
        $current = collect($revenueData)->firstWhere('period', 'current');
        $previous = collect($revenueData)->firstWhere('period', 'previous');
        
        $currentRevenue = $current->revenue ?? 0;
        $previousRevenue = $previous->revenue ?? 0;
        $currentOrders = $current->order_count ?? 0;
        $previousOrders = $previous->order_count ?? 0;
        $currentAOV = $currentOrders > 0 ? $currentRevenue / $currentOrders : 0;
        $previousAOV = $previousOrders > 0 ? $previousRevenue / $previousOrders : 0;
        $activeProducts = $current->active_products_count ?? 0;
        
        // Get chart data for last 7 days
        $chartData = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(7))
            ->whereNotNull('invoices.sent_at')
            ->selectRaw('DATE(orders.created_at) as date, SUM(order_products.quantity * order_products.price) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->map(fn($value) => (float) $value)
            ->toArray();
        
        return [
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'current_orders' => $currentOrders,
            'previous_orders' => $previousOrders,
            'current_aov' => $currentAOV,
            'previous_aov' => $previousAOV,
            'active_products' => $activeProducts,
            'revenue_chart' => $chartData,
            
            // Calculate change indicators
            'revenue_change' => $this->calculateChange($currentRevenue, $previousRevenue),
            'revenue_icon' => $currentRevenue >= $previousRevenue ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
            'revenue_color' => $currentRevenue >= $previousRevenue ? 'success' : 'danger',
            
            'orders_change' => $this->calculateChange($currentOrders, $previousOrders),
            'orders_icon' => $currentOrders >= $previousOrders ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
            'orders_color' => $currentOrders >= $previousOrders ? 'success' : 'danger',
            
            'aov_change' => $this->calculateChange($currentAOV, $previousAOV),
            'aov_icon' => $currentAOV >= $previousAOV ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down',
            'aov_color' => $currentAOV >= $previousAOV ? 'success' : 'danger',
        ];
    }
    
    private function calculateChange(float $current, float $previous): string
    {
        if ($previous == 0) {
            return 'No previous data';
        }
        
        $change = (($current - $previous) / $previous) * 100;
        return number_format(abs($change), 1) . '% ' . ($change >= 0 ? 'increase' : 'decrease');
    }
}