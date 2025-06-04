<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Order;
use Illuminate\Support\Carbon;

class SalesRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Sales Revenue (Last 30 Days)';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

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

    protected function getType(): string
    {
        return 'line';
    }
    
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