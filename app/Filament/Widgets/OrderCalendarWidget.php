<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\OrderStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Saade\FilamentFullCalendar\Actions\ViewAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class OrderCalendarWidget extends FullCalendarWidget
{
    protected static ?string $heading = 'Orders Calendar';
    
    public Model|string|int|null $record = null;

    /**
     * FullCalendar config options - Set to weekly view by default
     */
    public function config(): array
    {
        return [
            'initialView' => 'timeGridWeek',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,listWeek',
            ],
            'eventDisplay' => 'block',
            'eventTimeFormat' => [
                'hour' => 'numeric',
                'minute' => '2-digit',
                'meridiem' => 'short',
            ],
            'slotMinTime' => '06:00:00',
            'slotMaxTime' => '20:00:00',
            'expandRows' => true,
            'height' => 'auto',
            'allDaySlot' => true,
        ];
    }

    /**
     * Fetch orders and display them based on delivery dates
     */
    public function fetchEvents(array $info): array
    {
        $start = Carbon::parse($info['start']);
        $end = Carbon::parse($info['end']);

        // Fetch orders within the date range based on delivery_date
        $orders = Order::with(['customer', 'status', 'orderItems.product'])
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('delivery_date', [$start, $end])
                    ->orWhereBetween('harvest_date', [$start, $end]);
            })
            ->whereNotIn('status_id', function ($query) {
                $query->select('id')
                    ->from('order_statuses')
                    ->whereIn('code', ['cancelled', 'template']);
            })
            ->get();

        $events = [];

        foreach ($orders as $order) {
            // Determine which date to use for the event
            $eventDate = $order->delivery_date ?? $order->harvest_date;
            
            if (!$eventDate || $eventDate->lt($start) || $eventDate->gt($end)) {
                continue;
            }

            // Calculate order totals
            $totalItems = $order->orderItems->count();
            $totalQuantity = $order->orderItems->sum('quantity');
            
            // Get customer name
            $customerName = $order->customer?->contact_name ?? 'Unknown Customer';
            
            // Check if order is overdue
            $isOverdue = $order->delivery_date && $order->delivery_date->isPast() && 
                        !in_array($order->status?->code, ['delivered', 'completed']);

            // Get status color
            $color = $this->getStatusColor($order->status?->code ?? 'draft', $isOverdue);

            // Build event title
            $title = sprintf(
                "Order #%d\n%s\n%d items (%.1f total)%s",
                $order->id,
                $customerName,
                $totalItems,
                $totalQuantity,
                $isOverdue ? "\n⚠️ OVERDUE" : ""
            );

            $events[] = [
                'id' => 'order_' . $order->id,
                'title' => $title,
                'start' => $eventDate->format('Y-m-d'),
                'allDay' => true,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => $this->getTextColor($color),
                'extendedProps' => [
                    'type' => 'order',
                    'order_id' => $order->id,
                    'customer_name' => $customerName,
                    'customer_type' => $order->customer_type,
                    'total_items' => $totalItems,
                    'total_quantity' => $totalQuantity,
                    'status' => $order->status?->name ?? 'Unknown',
                    'status_code' => $order->status?->code ?? 'draft',
                    'delivery_date' => $order->delivery_date?->format('Y-m-d'),
                    'harvest_date' => $order->harvest_date?->format('Y-m-d'),
                    'is_overdue' => $isOverdue,
                    'order_items' => $order->orderItems->map(function ($item) {
                        return [
                            'product_name' => $item->product?->name ?? 'Unknown Product',
                            'quantity' => $item->quantity,
                            'quantity_unit' => $item->quantity_unit,
                            'price' => $item->price,
                        ];
                    })->toArray(),
                ],
            ];
        }

        return $events;
    }

    /**
     * Get color based on order status
     */
    protected function getStatusColor(string $status, bool $isOverdue = false): string
    {
        if ($isOverdue) {
            return '#ef4444'; // red-500 for overdue
        }

        return match ($status) {
            'draft' => '#eab308',              // yellow-500
            'pending' => '#f97316',            // orange-500
            'confirmed' => '#3b82f6',          // blue-500
            'growing' => '#22c55e',            // green-500
            'ready_to_harvest' => '#10b981',   // emerald-500
            'harvesting' => '#06b6d4',         // cyan-500
            'packing' => '#8b5cf6',            // violet-500
            'ready_for_delivery' => '#6366f1', // indigo-500
            'out_for_delivery' => '#f59e0b',   // amber-500
            'delivered' => '#6b7280',          // gray-500
            'completed' => '#374151',          // gray-700
            'cancelled' => '#ef4444',          // red-500
            default => '#6b7280',              // gray-500
        };
    }

    /**
     * Get appropriate text color based on background
     */
    protected function getTextColor(string $bgColor): string
    {
        $hex = str_replace('#', '', $bgColor);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }

    /**
     * Define view action for order events
     */
    protected function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->modalHeading(function ($arguments) {
                $event = $arguments['event'] ?? [];
                $props = $event['extendedProps'] ?? [];
                $orderId = $props['order_id'] ?? 'Order';
                $customer = $props['customer_name'] ?? 'Unknown Customer';
                return "Order #$orderId - $customer";
            })
            ->modalContent(function ($arguments) {
                $event = $arguments['event'] ?? [];
                $props = $event['extendedProps'] ?? [];
                
                Log::info('Order calendar modal props:', $props);
                
                return view('filament.widgets.order-calendar-modal', [
                    'orderId' => $props['order_id'] ?? 'Unknown',
                    'customerName' => $props['customer_name'] ?? 'Unknown Customer',
                    'customerType' => $props['customer_type'] ?? 'Unknown',
                    'totalItems' => $props['total_items'] ?? 0,
                    'totalQuantity' => $props['total_quantity'] ?? 0,
                    'status' => $props['status'] ?? 'Unknown',
                    'deliveryDate' => $props['delivery_date'] ?? null,
                    'harvestDate' => $props['harvest_date'] ?? null,
                    'isOverdue' => $props['is_overdue'] ?? false,
                    'orderItems' => $props['order_items'] ?? [],
                ]);
            })
            ->modalSubmitActionLabel('Close')
            ->modalCancelActionLabel('View Order')
            ->url(function ($arguments) {
                $event = $arguments['event'] ?? [];
                $props = $event['extendedProps'] ?? [];
                $orderId = $props['order_id'] ?? null;
                
                if ($orderId) {
                    return route('filament.admin.resources.orders.edit', $orderId);
                }
                
                return route('filament.admin.resources.orders.index');
            });
    }

    /**
     * Calendar actions
     */
    protected function getActions(): array
    {
        return [
            $this->viewAction(),
        ];
    }
}