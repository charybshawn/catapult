<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Carbon;

class ProductPerformanceChart extends ChartWidget
{
    protected static ?string $heading = 'Top Products (Last 30 Days)';
    
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

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

    protected function getType(): string
    {
        return 'doughnut';
    }
    
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