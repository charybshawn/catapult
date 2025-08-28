<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Illuminate\Support\Carbon;

/**
 * Agricultural sales revenue analytics widget for financial performance tracking.
 *
 * Displays daily revenue trends over the past 30 days through area chart
 * visualization. Analyzes completed agricultural sales from sent invoices
 * to provide accurate financial performance metrics and identify revenue
 * patterns for business planning and growth analysis.
 *
 * @filament_widget Chart widget for agricultural sales revenue analytics
 * @business_domain Agricultural sales performance and financial tracking
 * @analytics_context 30-day daily revenue analysis from completed invoices
 * @dashboard_position Sort order 1, full width for prominent financial display
 * @ui_visualization Area line chart with filled trend visualization
 */
class SalesRevenueChart extends ChartWidget
{
    /** @var string Widget heading describing the revenue analytics period */
    protected ?string $heading = 'Sales Revenue (Last 30 Days)';
    
    /** @var int Widget sort order for prominent dashboard positioning */
    protected static ?int $sort = 1;
    
    /** @var string Widget column span for full-width revenue visualization */
    protected int | string | array $columnSpan = 'full';

    /**
     * Generate sales revenue chart data for agricultural business analytics.
     *
     * Retrieves and processes daily revenue data from completed agricultural
     * orders over the past 30 days. Only includes revenue from sent invoices
     * to ensure accurate financial reporting and business performance analysis.
     *
     * @return array Chart.js compatible dataset with daily revenue trends
     * @business_logic Only includes revenue from sent invoices for accuracy
     * @agricultural_analytics Daily revenue tracking for microgreens business
     * @financial_reporting Supports business planning and growth analysis
     */
    protected function getData(): array
    {
        $data = $this->getSalesData();
        
        return [
            'datasets' => [
                [
                    'label' => 'Daily Revenue',
                    'data' => $data['revenues'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    /**
     * Get chart type for sales revenue visualization.
     *
     * @return string Chart.js chart type identifier for line chart display
     */
    protected function getType(): string
    {
        return 'line';
    }
    
    /**
     * Extract and process daily sales revenue data for chart visualization.
     *
     * Performs complex query joining orders, order items, and invoices to
     * calculate accurate daily revenue totals. Fills missing days with zero
     * values to maintain consistent 30-day trend visualization and supports
     * comprehensive financial analysis.
     *
     * @return array Processed data with date labels and daily revenue values
     * @database_query Joins orders, order_products, and invoices tables
     * @business_filter 30-day period with sent invoice requirement
     * @data_processing Fills gaps with zero revenue for complete trend display
     */
    private function getSalesData(): array
    {
        $orders = Order::join('order_products', 'orders.id', '=', 'order_products.order_id')
            ->join('invoices', 'orders.id', '=', 'invoices.order_id')
            ->where('orders.created_at', '>=', Carbon::now()->subDays(30))
            ->whereNotNull('invoices.sent_at') // Only count sent invoices
            ->selectRaw('DATE(orders.created_at) as date, SUM(order_products.quantity * order_products.price) as total_revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $revenues = [];
        
        // Fill in missing days with zero revenue
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::now()->subDays($i)->format('M j');
            
            $orderForDay = $orders->firstWhere('date', $date);
            $revenues[] = $orderForDay ? (float) $orderForDay->total_revenue : 0;
        }
        
        return [
            'labels' => $labels,
            'revenues' => $revenues,
        ];
    }
}