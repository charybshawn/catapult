<?php

namespace App\Filament\Widgets;

use App\Models\CropPlan;
use App\Models\Order;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Guava\Calendar\Filament\CalendarWidget;

class CropPlanCalendarWidget extends CalendarWidget
{
    protected static ?string $maxHeight = '500px';

    protected static ?string $pollingInterval = null;

    public function getEvents(FetchInfo $info): array
    {
        $events = [];

        // Get crop plans within the date range
        $cropPlans = CropPlan::with(['order.customer', 'recipe.masterCultivar', 'recipe.masterSeedCatalog'])
            ->when($info->start, function ($query) use ($info) {
                return $query->where('plant_by_date', '>=', $info->start);
            })
            ->when($info->end, function ($query) use ($info) {
                return $query->where('plant_by_date', '<=', $info->end);
            })
            ->get();

        foreach ($cropPlans as $cropPlan) {
            $customerName = $cropPlan->order->customer->name ?? 'Unknown Customer';
            $varietyName = $cropPlan->recipe->masterSeedCatalog->common_name ?? 
                          $cropPlan->recipe->masterCultivar->cultivar_name ?? 
                          'Unknown Variety';
            
            $title = "{$varietyName} - {$customerName}";
            
            // Color coding based on status
            $color = match($cropPlan->status->code ?? 'draft') {
                'approved' => '#22c55e', // green
                'planted' => '#3b82f6',  // blue
                'harvested' => '#8b5cf6', // purple
                'cancelled' => '#ef4444', // red
                default => '#f59e0b',     // amber (draft/pending)
            };

            $events[] = CalendarEvent::make()
                ->id($cropPlan->id)
                ->title($title)
                ->start($cropPlan->plant_by_date)
                ->color($color);
        }

        // Also show orders without crop plans as potential events
        $ordersWithoutPlans = Order::with(['customer', 'status'])
            ->whereDoesntHave('cropPlans')
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'pending', 'confirmed']);
            })
            ->when($info->start, function ($query) use ($info) {
                return $query->where('harvest_date', '>=', $info->start);
            })
            ->when($info->end, function ($query) use ($info) {
                return $query->where('harvest_date', '<=', $info->end);
            })
            ->get();

        foreach ($ordersWithoutPlans as $order) {
            $customerName = $order->customer->name ?? 'Unknown Customer';
            $title = "📅 Order #{$order->id} - {$customerName}";
            
            $events[] = CalendarEvent::make()
                ->id("order-{$order->id}")
                ->title($title)
                ->start($order->harvest_date)
                ->color('#6b7280'); // gray for unplanned orders
        }

        return $events;
    }

}