<?php

namespace App\Services;

use App\Models\CropPlan;
use App\Models\Order;
use App\Models\CropPlanStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for handling crop planning dashboard business logic.
 * 
 * This service separates complex query and business logic from the Filament dashboard page,
 * making it more testable and reusable across different parts of the application.
 */
class CropPlanDashboardService
{
    /**
     * Get crop plans that need urgent attention (next 7 days).
     * 
     * Returns crop plans that are approved/active and need to be planted
     * within the next 7 days, grouped by plant date.
     *
     * @return Collection<string, Collection<CropPlan>>
     */
    public function getUrgentCrops(): Collection
    {
        $activeStatus = CropPlanStatus::findByCode('active');
        
        if (!$activeStatus) {
            return collect();
        }

        return CropPlan::with(['recipe.seedEntry', 'order.customer', 'status'])
            ->where('status_id', $activeStatus->id)
            ->where('plant_by_date', '<=', now()->addDays(7))
            ->where('plant_by_date', '>=', now())
            ->orderBy('plant_by_date', 'asc')
            ->get()
            ->groupBy(function ($plan) {
                return $plan->plant_by_date->format('Y-m-d');
            });
    }

    /**
     * Get overdue crop plans.
     * 
     * Returns crop plans that are approved/active but should have been
     * planted already (plant_by_date is in the past).
     *
     * @return Collection<CropPlan>
     */
    public function getOverdueCrops(): Collection
    {
        $activeStatus = CropPlanStatus::findByCode('active');
        
        if (!$activeStatus) {
            return collect();
        }

        return CropPlan::with(['recipe.seedEntry', 'order.customer', 'status'])
            ->where('status_id', $activeStatus->id)
            ->where('plant_by_date', '<', now())
            ->orderBy('plant_by_date', 'asc')
            ->get();
    }

    /**
     * Get upcoming orders that need crop plans.
     * 
     * Returns orders for the next 14 days that don't have crop plans yet
     * and are in a status that requires crop planning.
     *
     * @return Collection<Order>
     */
    public function getUpcomingOrders(): Collection
    {
        return Order::with(['customer', 'orderItems.product'])
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->where('delivery_date', '>=', now())
            ->where('delivery_date', '<=', now()->addDays(14))
            ->orderBy('delivery_date', 'asc')
            ->get()
            ->filter(function ($order) {
                // Only include orders that don't have crop plans yet
                return $order->cropPlans->isEmpty();
            });
    }

    /**
     * Get dashboard statistics summary.
     * 
     * Returns counts for urgent crops, overdue crops, and upcoming orders
     * that need attention on the dashboard.
     *
     * @return array{urgent_crops_count: int, overdue_crops_count: int, upcoming_orders_count: int}
     */
    public function getDashboardStats(): array
    {
        return [
            'urgent_crops_count' => $this->getUrgentCrops()->flatten()->count(),
            'overdue_crops_count' => $this->getOverdueCrops()->count(),
            'upcoming_orders_count' => $this->getUpcomingOrders()->count(),
        ];
    }

    /**
     * Get upcoming crop plans by date range.
     * 
     * Useful for calendar views and planning reports.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return Collection<CropPlan>
     */
    public function getCropPlansByDateRange(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now()->addDays(60);

        return CropPlan::with(['recipe.seedEntry', 'order', 'status'])
            ->where('plant_by_date', '>=', $startDate)
            ->where('plant_by_date', '<=', $endDate)
            ->orderBy('plant_by_date', 'asc')
            ->get();
    }

    /**
     * Get orders by delivery date range.
     * 
     * Useful for calendar views and delivery planning.
     *
     * @param Carbon|null $startDate
     * @param Carbon|null $endDate
     * @return Collection<Order>
     */
    public function getOrdersByDeliveryDateRange(?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? now()->subDays(30);
        $endDate = $endDate ?? now()->addDays(60);

        return Order::with(['customer'])
            ->whereIn('status', ['pending', 'confirmed', 'processing'])
            ->where('delivery_date', '>=', $startDate)
            ->where('delivery_date', '<=', $endDate)
            ->whereNotNull('delivery_date')
            ->orderBy('delivery_date', 'asc')
            ->get();
    }
}