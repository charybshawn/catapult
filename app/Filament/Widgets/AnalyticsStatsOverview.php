<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        return [
            Stat::make('Total Revenue (30 days)', $this->getTotalRevenue())
                ->description($this->getRevenueChange())
                ->descriptionIcon($this->getRevenueIcon())
                ->color($this->getRevenueColor())
                ->chart($this->getRevenueChart()),
                
            Stat::make('Orders (30 days)', $this->getTotalOrders())
                ->description($this->getOrdersChange())
                ->descriptionIcon($this->getOrdersIcon())
                ->color($this->getOrdersColor()),
                
            Stat::make('Avg Order Value', $this->getAverageOrderValue())
                ->description($this->getAOVChange())
                ->descriptionIcon($this->getAOVIcon())
                ->color($this->getAOVColor()),
                
            Stat::make('Active Products', $this->getActiveProducts())
                ->description('Products with sales')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success'),
        ];
    }
    
    private function getTotalRevenue(): string
    {
        $revenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at') // Only count invoices that have been sent
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        return '$' . number_format($revenue, 2);
    }
    
    private function getRevenueChange(): string
    {
        $currentRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $previousRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(60))
            ->where('orders.created_at', '<', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
            
        if ($previousRevenue == 0) return 'No previous data';
        
        $change = (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
        return number_format(abs($change), 1) . '% ' . ($change >= 0 ? 'increase' : 'decrease');
    }
    
    private function getRevenueIcon(): string
    {
        $currentRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $previousRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(60))
            ->where('orders.created_at', '<', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
            
        return $currentRevenue >= $previousRevenue ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }
    
    private function getRevenueColor(): string
    {
        $currentRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $previousRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(60))
            ->where('orders.created_at', '<', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
            
        return $currentRevenue >= $previousRevenue ? 'success' : 'danger';
    }
    
    private function getRevenueChart(): array
    {
        return Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(7))
            ->whereNotNull('invoices.sent_at')
            ->selectRaw('DATE(orders.created_at) as date, SUM(order_products.quantity * order_products.price) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->map(fn($value) => (float) $value)
            ->toArray();
    }
    
    private function getTotalOrders(): string
    {
        $orders = Order::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        return number_format($orders);
    }
    
    private function getOrdersChange(): string
    {
        $currentOrders = Order::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $previousOrders = Order::where('created_at', '>=', Carbon::now()->subDays(60))
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->count();
            
        if ($previousOrders == 0) return 'No previous data';
        
        $change = (($currentOrders - $previousOrders) / $previousOrders) * 100;
        return number_format(abs($change), 1) . '% ' . ($change >= 0 ? 'increase' : 'decrease');
    }
    
    private function getOrdersIcon(): string
    {
        $currentOrders = Order::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $previousOrders = Order::where('created_at', '>=', Carbon::now()->subDays(60))
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->count();
            
        return $currentOrders >= $previousOrders ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }
    
    private function getOrdersColor(): string
    {
        $currentOrders = Order::where('created_at', '>=', Carbon::now()->subDays(30))->count();
        $previousOrders = Order::where('created_at', '>=', Carbon::now()->subDays(60))
            ->where('created_at', '<', Carbon::now()->subDays(30))
            ->count();
            
        return $currentOrders >= $previousOrders ? 'success' : 'danger';
    }
    
    private function getAverageOrderValue(): string
    {
        $totalRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $totalOrders = Order::join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->count();
        
        $aov = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        return '$' . number_format($aov, 2);
    }
    
    private function getAOVChange(): string
    {
        // Current AOV
        $currentRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $currentOrders = Order::join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->count();
        $currentAOV = $currentOrders > 0 ? $currentRevenue / $currentOrders : 0;
        
        // Previous AOV
        $previousRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(60))
            ->where('orders.created_at', '<', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $previousOrders = Order::join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(60))
            ->where('orders.created_at', '<', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->count();
        $previousAOV = $previousOrders > 0 ? $previousRevenue / $previousOrders : 0;
        
        if ($previousAOV == 0) return 'No previous data';
        
        $change = (($currentAOV - $previousAOV) / $previousAOV) * 100;
        return number_format(abs($change), 1) . '% ' . ($change >= 0 ? 'increase' : 'decrease');
    }
    
    private function getAOVIcon(): string
    {
        $currentRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $currentOrders = Order::join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->count();
        $currentAOV = $currentOrders > 0 ? $currentRevenue / $currentOrders : 0;
        
        $previousRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(60))
            ->where('orders.created_at', '<', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $previousOrders = Order::join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(60))
            ->where('orders.created_at', '<', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->count();
        $previousAOV = $previousOrders > 0 ? $previousRevenue / $previousOrders : 0;
        
        return $currentAOV >= $previousAOV ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }
    
    private function getAOVColor(): string
    {
        $currentRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $currentOrders = Order::join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->count();
        $currentAOV = $currentOrders > 0 ? $currentRevenue / $currentOrders : 0;
        
        $previousRevenue = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(60))
            ->where('orders.created_at', '<', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->sum(DB::raw('order_products.quantity * order_products.price'));
        $previousOrders = Order::join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(60))
            ->where('orders.created_at', '<', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at')
            ->count();
        $previousAOV = $previousOrders > 0 ? $previousRevenue / $previousOrders : 0;
        
        return $currentAOV >= $previousAOV ? 'success' : 'danger';
    }
    
    private function getActiveProducts(): string
    {
        $activeProducts = OrderItem::join('orders', 'order_products.order_id', '=', 'orders.id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at') // Only count products from sent invoices
            ->distinct('order_products.product_id')
            ->count('order_products.product_id');
            
        return number_format($activeProducts);
    }
}