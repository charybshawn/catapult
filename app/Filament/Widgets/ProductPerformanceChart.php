<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;

/**
 * Agricultural product performance analytics widget for revenue tracking.
 *
 * Displays top-performing microgreens products by revenue over the past 30 days
 * through interactive doughnut chart visualization. Analyzes sent invoices to
 * provide accurate revenue attribution and identify best-selling varieties
 * for strategic agricultural planning and production optimization.
 *
 * @filament_widget Chart widget for product revenue analytics
 * @business_domain Agricultural product performance and revenue tracking
 * @analytics_context 30-day revenue analysis from completed invoices only
 * @dashboard_position Sort order 2, full width for prominent revenue display
 * @ui_visualization Doughnut chart with color-coded product revenue segments
 */
class ProductPerformanceChart extends ChartWidget
{
    /** @var string Widget heading describing the analytics time period */
    protected ?string $heading = 'Top Products (Last 30 Days)';
    
    /** @var int Widget sort order for dashboard positioning */
    protected static ?int $sort = 2;
    
    /** @var string Widget column span for full-width revenue display */
    protected int | string | array $columnSpan = 'full';

    /**
     * Generate product performance chart data for agricultural revenue analysis.
     *
     * Retrieves and processes revenue data from order items associated with
     * sent invoices to ensure accurate financial reporting. Groups by product
     * and calculates total revenue contribution for top-performing varieties
     * over the past 30 days.
     *
     * @return array Chart.js compatible dataset with product labels and revenues
     * @business_logic Only includes revenue from sent invoices for accuracy
     * @agricultural_analytics Identifies best-selling microgreens varieties
     * @revenue_tracking Supports strategic production planning decisions
     */
    protected function getData(): array
    {
        $data = $this->getProductData();
        
        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $data['revenues'],
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(59, 130, 246, 0.8)', 
                        'rgba(249, 115, 22, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                    ],
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    /**
     * Get chart type for product performance visualization.
     *
     * @return string Chart.js chart type identifier for doughnut chart display
     */
    protected function getType(): string
    {
        return 'doughnut';
    }
    
    /**
     * Extract and process product revenue data for chart visualization.
     *
     * Performs complex query joining order items, orders, products, and invoices
     * to calculate accurate revenue attribution. Filters for sent invoices only
     * and limits to top 5 products by revenue for focused analytics display.
     *
     * @return array Processed data with product labels and revenue values
     * @database_query Joins multiple tables for accurate revenue calculation
     * @business_filter 30-day period with sent invoice requirement
     * @data_processing Limits to top 5 products and converts to float values
     */
    private function getProductData(): array
    {
        $topProducts = OrderItem::join('orders', 'order_products.order_id', '=', 'orders.id')
            ->join('products', 'order_products.product_id', '=', 'products.id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at') // Only count sent invoices
            ->selectRaw('products.name, SUM(order_products.quantity * order_products.price) as total_revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $labels = $topProducts->pluck('name')->toArray();
        $revenues = $topProducts->pluck('total_revenue')->map(fn($value) => (float) $value)->toArray();
        
        return [
            'labels' => $labels,
            'revenues' => $revenues,
        ];
    }
}