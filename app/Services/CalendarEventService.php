<?php

namespace App\Services;

use App\Models\CropPlan;
use App\Models\Order;
use Carbon\Carbon;

/**
 * Service for generating calendar events for various dashboard views.
 * 
 * This service handles the conversion of database models into calendar-friendly
 * event formats, keeping the presentation logic separate from business logic.
 */
class CalendarEventService
{
    protected CropPlanDashboardService $dashboardService;

    public function __construct(CropPlanDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Generate calendar events for crop planning dashboard.
     * 
     * Combines order delivery events and crop planting events into a single
     * array suitable for calendar display components.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array<array{id: string, title: string, start: string, backgroundColor: string, borderColor: string, textColor: string, extendedProps: array}>
     */
    public function getCropPlanningEvents(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now()->addDays(60);

        $events = [];
        
        // Add order delivery events
        $events = array_merge($events, $this->getOrderDeliveryEvents($startDate, $endDate));
        
        // Add crop planting events
        $events = array_merge($events, $this->getCropPlantingEvents($startDate, $endDate));
        
        return $events;
    }

    /**
     * Generate order delivery events for the calendar.
     * 
     * Creates calendar events for order deliveries, helping to visualize
     * when crops need to be ready for harvest.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<array{id: string, title: string, start: string, backgroundColor: string, borderColor: string, textColor: string, extendedProps: array}>
     */
    protected function getOrderDeliveryEvents(Carbon $startDate, Carbon $endDate): array
    {
        $orders = $this->dashboardService->getOrdersByDeliveryDateRange($startDate, $endDate);
        $events = [];

        foreach ($orders as $order) {
            if ($order->delivery_date) {
                $events[] = [
                    'id' => 'order-' . $order->id,
                    'title' => "Delivery: Order #{$order->id}",
                    'start' => $order->delivery_date->format('Y-m-d'),
                    'backgroundColor' => '#10b981', // green
                    'borderColor' => '#059669',
                    'textColor' => '#ffffff',
                    'extendedProps' => [
                        'type' => 'delivery',
                        'orderId' => $order->id,
                        'customer' => $order->customer->contact_name ?? 'Unknown',
                        'status' => $order->status,
                    ],
                ];
            }
        }

        return $events;
    }

    /**
     * Generate crop planting events for the calendar.
     * 
     * Creates calendar events for crop planting dates, with colors indicating
     * the current status of each crop plan.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array<array{id: string, title: string, start: string, backgroundColor: string, borderColor: string, textColor: string, extendedProps: array}>
     */
    protected function getCropPlantingEvents(Carbon $startDate, Carbon $endDate): array
    {
        $cropPlans = $this->dashboardService->getCropPlansByDateRange($startDate, $endDate);
        $events = [];

        foreach ($cropPlans as $plan) {
            $statusCode = $plan->status?->code ?? 'unknown';
            $color = $this->getStatusColor($statusCode);
            
            $events[] = [
                'id' => 'plant-' . $plan->id,
                'title' => "Plant: {$plan->recipe->seedEntry->common_name}",
                'start' => $plan->plant_by_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'planting',
                    'planId' => $plan->id,
                    'variety' => $plan->recipe->seedEntry->common_name,
                    'trays' => $plan->trays_needed,
                    'status' => $statusCode,
                    'statusName' => $plan->status?->name ?? 'Unknown',
                ],
            ];
        }

        return $events;
    }

    /**
     * Get color for crop plan status.
     * 
     * Maps crop plan status codes to appropriate colors for calendar display.
     *
     * @param string $statusCode
     * @return string Hex color code
     */
    protected function getStatusColor(string $statusCode): string
    {
        return match($statusCode) {
            'draft' => '#6b7280',      // gray
            'active' => '#3b82f6',     // blue
            'completed' => '#10b981',   // green
            'cancelled' => '#ef4444',   // red
            default => '#6b7280',       // gray fallback
        };
    }

    /**
     * Generate events for a specific crop plan status.
     * 
     * Useful for filtered calendar views showing only specific status types.
     *
     * @param string $statusCode
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return array
     */
    public function getEventsByStatus(string $statusCode, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now()->addDays(60);

        $cropPlans = CropPlan::with(['recipe.seedEntry', 'order', 'status'])
            ->whereHas('status', function ($query) use ($statusCode) {
                $query->where('code', $statusCode);
            })
            ->where('plant_by_date', '>=', $startDate)
            ->where('plant_by_date', '<=', $endDate)
            ->get();

        $events = [];
        $color = $this->getStatusColor($statusCode);

        foreach ($cropPlans as $plan) {
            $events[] = [
                'id' => 'plant-' . $plan->id,
                'title' => "Plant: {$plan->recipe->seedEntry->common_name}",
                'start' => $plan->plant_by_date->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'type' => 'planting',
                    'planId' => $plan->id,
                    'variety' => $plan->recipe->seedEntry->common_name,
                    'trays' => $plan->trays_needed,
                    'status' => $statusCode,
                    'statusName' => $plan->status?->name ?? 'Unknown',
                ],
            ];
        }

        return $events;
    }
}