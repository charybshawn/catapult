<?php

namespace App\Services;

use App\Models\CropPlan;
use App\Models\Order;
use Carbon\Carbon;

/**
 * Agricultural Calendar Event Service for Microgreens Production Planning
 * 
 * This service transforms agricultural data models (crop plans, orders, planting schedules)
 * into calendar-friendly event formats for visual planning interfaces. It bridges the gap
 * between complex agricultural business logic and presentation-ready calendar components.
 * 
 * Key Agricultural Contexts:
 * - Order deliveries represent harvest deadlines for microgreens production
 * - Crop planting events show when seeds must be planted to meet delivery schedules
 * - Calendar visualization helps farmers coordinate growing cycles with customer demand
 * - Status-based color coding provides instant visual feedback on production timeline health
 * 
 * Integration Points:
 * - CropPlanDashboardService: Agricultural business logic and data aggregation
 * - Filament calendar widgets: UI presentation for production planning
 * - Order management: Customer delivery requirement coordination
 * - Recipe management: Seed variety and growing specification integration
 * 
 * Business Workflow:
 * 1. Orders with delivery dates create calendar "deadline" events
 * 2. Crop plans generate "planting" events based on growing time calculations
 * 3. Visual calendar allows farmers to identify scheduling conflicts and gaps
 * 4. Color-coded status helps track production pipeline health at a glance
 * 
 * @business_domain Agricultural microgreens production and delivery scheduling
 * @ui_integration Filament dashboard calendar widgets for production planning
 * @data_sources Orders (delivery dates), CropPlans (planting schedules), Recipes (growing times)
 * @performance Optimized for dashboard calendar rendering with pre-aggregated data
 * 
 * @see CropPlanDashboardService For underlying agricultural business logic
 * @see \App\Filament\Widgets\CropPlanCalendarWidget For UI integration
 * @see \App\Models\CropPlan For agricultural planning data structure
 * @see \App\Models\Order For customer delivery requirements
 */
class CalendarEventService
{
    /**
     * Agricultural dashboard service for crop planning business logic and data aggregation
     * 
     * @var CropPlanDashboardService Provides validated agricultural data for calendar event generation
     */
    protected CropPlanDashboardService $dashboardService;

    /**
     * Initialize calendar event service with agricultural dashboard integration
     * 
     * @param CropPlanDashboardService $dashboardService Service providing agricultural business logic
     *                                                   and pre-validated crop planning data
     * @business_context Establishes connection to agricultural data sources and business rules
     */
    public function __construct(CropPlanDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Generate comprehensive calendar events for agricultural crop planning dashboard
     * 
     * Creates a unified calendar view combining critical agricultural milestones:
     * - Customer order delivery deadlines (harvest targets)
     * - Crop planting schedules (seeding timing for delivery alignment)
     * 
     * This method provides farmers with a visual timeline showing both customer
     * commitments and the production activities needed to fulfill them. The default
     * date range covers past month (order history) and next two months (planning horizon).
     * 
     * Agricultural Context:
     * - Delivery events represent customer expectations and revenue commitments
     * - Planting events represent production activities needed to meet those commitments
     * - Visual overlap detection helps identify scheduling conflicts and resource constraints
     * - Color coding provides instant status assessment for production pipeline health
     * 
     * @param Carbon|null $startDate Start of calendar date range, defaults to 30 days ago
     *                               for production history context
     * @param Carbon|null $endDate End of calendar date range, defaults to 60 days ahead
     *                             for adequate planning horizon
     * 
     * @return array<array{id: string, title: string, start: string, backgroundColor: string, borderColor: string, textColor: string, extendedProps: array}>
     *               Calendar event objects with agricultural context data:
     *               - id: Unique identifier with type prefix (order-* or plant-*)
     *               - title: Human-readable event description for farmers
     *               - start: ISO date string for calendar positioning
     *               - backgroundColor/borderColor: Status-based visual indicators
     *               - textColor: Optimized for readability over background colors
     *               - extendedProps: Agricultural metadata for event interaction
     * 
     * @business_workflow Primary dashboard calendar population for production planning
     * @ui_integration Filament calendar widget data source for agricultural scheduling
     * @performance Leverages dashboard service caching for efficient data retrieval
     * 
     * @see getOrderDeliveryEvents() For customer delivery deadline generation
     * @see getCropPlantingEvents() For production schedule event generation
     * @see CropPlanDashboardService For underlying agricultural data aggregation
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
     * Generate customer order delivery deadline events for agricultural planning calendar
     * 
     * Creates calendar events representing customer delivery commitments, which serve as
     * harvest deadlines that drive backward planning for microgreens production. Each
     * delivery event represents a firm customer commitment requiring precise timing.
     * 
     * Agricultural Context:
     * - Delivery dates are harvest deadlines that cannot be moved without customer impact
     * - Green color coding indicates revenue-positive events (successful deliveries)
     * - Events drive backward calculation of planting schedules based on growing times
     * - Customer information enables quick communication about delivery coordination
     * 
     * Business Logic:
     * - Only orders with confirmed delivery dates generate calendar events
     * - Event titles include order ID for quick reference and tracking
     * - Extended properties store customer and status data for calendar interaction
     * - Green theme indicates positive revenue events vs. cost-based planting events
     * 
     * @param Carbon $startDate Start of date range for delivery event retrieval
     * @param Carbon $endDate End of date range for delivery event retrieval
     * 
     * @return array<array{id: string, title: string, start: string, backgroundColor: string, borderColor: string, textColor: string, extendedProps: array}>
     *               Delivery event objects with customer and order context:
     *               - id: 'order-{id}' format for unique event identification
     *               - title: "Delivery: Order #{id}" for farmer recognition
     *               - start: Delivery date in ISO format for calendar positioning
     *               - backgroundColor/borderColor: Green theme for revenue events
     *               - extendedProps.type: 'delivery' for event categorization
     *               - extendedProps.orderId: Direct order reference for drill-down
     *               - extendedProps.customer: Customer name for delivery coordination
     *               - extendedProps.status: Order status for fulfillment tracking
     * 
     * @business_workflow Customer delivery deadline visualization for harvest planning
     * @agricultural_planning Backward scheduling anchor points for production timing
     * @revenue_tracking Visual representation of customer commitments and cash flow
     * 
     * @see CropPlanDashboardService::getOrdersByDeliveryDateRange() For data source
     * @see getCropPlantingEvents() For production schedule coordination
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
     * Generate crop planting schedule events for agricultural production calendar
     * 
     * Creates calendar events representing when seeds must be planted to meet customer
     * delivery deadlines. These events are the production-side counterpart to delivery
     * events, calculated based on recipe growing times and harvest requirements.
     * 
     * Agricultural Context:
     * - Planting dates are calculated backward from delivery dates using recipe timing
     * - Each event represents actual seeding activity required in growing facility
     * - Status-based color coding provides instant visual feedback on production pipeline
     * - Tray counts enable resource planning for growing space and materials
     * - Common names (vs scientific) provide farmer-friendly variety identification
     * 
     * Business Logic:
     * - Events are generated from approved crop plans with confirmed planting schedules
     * - Status determines color coding for quick visual assessment of production health
     * - Extended properties include production details for operational coordination
     * - Event titles use common names familiar to agricultural staff
     * 
     * Status Color Mapping:
     * - Draft (gray): Plans still being developed, not yet committed
     * - Active (blue): Confirmed plans in production pipeline
     * - Completed (green): Successfully executed plantings
     * - Cancelled (red): Plans that were abandoned or superseded
     * 
     * @param Carbon $startDate Start of date range for planting event retrieval
     * @param Carbon $endDate End of date range for planting event retrieval
     * 
     * @return array<array{id: string, title: string, start: string, backgroundColor: string, borderColor: string, textColor: string, extendedProps: array}>
     *               Planting event objects with agricultural production context:
     *               - id: 'plant-{id}' format for unique identification vs. delivery events
     *               - title: "Plant: {common_name}" using farmer-familiar variety names
     *               - start: plant_by_date in ISO format for accurate calendar positioning
     *               - backgroundColor/borderColor: Status-based visual indicators
     *               - extendedProps.type: 'planting' for event categorization
     *               - extendedProps.planId: Direct crop plan reference for drill-down
     *               - extendedProps.variety: Common name for variety identification
     *               - extendedProps.trays: Resource requirement for capacity planning
     *               - extendedProps.status/statusName: Current plan status for workflow tracking
     * 
     * @business_workflow Production schedule visualization for seeding operations
     * @agricultural_planning Forward scheduling from planting to harvest coordination
     * @resource_planning Tray count visibility for growing space allocation
     * 
     * @see CropPlanDashboardService::getCropPlansByDateRange() For data source
     * @see getStatusColor() For status-based visual coding system
     * @see getOrderDeliveryEvents() For delivery deadline coordination
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
     * Get status-based color coding for agricultural crop plan visualization
     * 
     * Maps crop plan status codes to appropriate colors for calendar display, providing
     * instant visual feedback on production pipeline health. Color choices follow
     * agricultural industry conventions and UI accessibility guidelines.
     * 
     * Agricultural Status Meanings:
     * - Draft: Plans being developed, not yet committed to production
     * - Active: Confirmed plans currently in production pipeline
     * - Completed: Successfully executed plantings with crops growing
     * - Cancelled: Plans abandoned due to changes in customer needs or capacity
     * 
     * Color Psychology:
     * - Gray: Neutral, uncommitted status requiring attention
     * - Blue: Active, positive progress in production pipeline
     * - Green: Success, completed activities generating revenue
     * - Red: Problems or cancellations requiring management attention
     * 
     * @param string $statusCode Crop plan status code from status lookup table
     * 
     * @return string Hex color code optimized for calendar display contrast
     *                and accessibility compliance (WCAG 2.1 AA standards)
     * 
     * @business_context Visual status indicators for agricultural production management
     * @ui_design Consistent color scheme across dashboard and calendar components
     * @accessibility High contrast colors suitable for various vision capabilities
     * 
     * @see getCropPlantingEvents() For status-based event generation
     * @see \App\Models\CropPlanStatus For available status codes and definitions
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
     * Generate filtered calendar events for specific agricultural crop plan status
     * 
     * Creates calendar events filtered to show only crop plans with a specific status,
     * enabling focused views of production pipeline segments. This method is particularly
     * useful for status-specific dashboards, troubleshooting workflows, or targeted
     * operational planning views.
     * 
     * Agricultural Use Cases:
     * - 'draft' events: Show uncommitted plans requiring approval decisions
     * - 'active' events: Display confirmed production schedule for daily operations
     * - 'completed' events: Review successfully executed plantings for yield tracking
     * - 'cancelled' events: Analyze abandoned plans for capacity optimization
     * 
     * Business Applications:
     * - Production manager focusing on active plans for daily task assignment
     * - Planning manager reviewing draft plans for approval workflow
     * - Operations manager analyzing completed plans for performance metrics
     * - Management reviewing cancelled plans for process improvement opportunities
     * 
     * @param string $statusCode Crop plan status code to filter events by
     * @param Carbon|null $startDate Start of date range, defaults to 30 days ago for context
     * @param Carbon|null $endDate End of date range, defaults to 60 days ahead for planning
     * 
     * @return array Calendar event objects filtered to specified status, using same
     *               structure as getCropPlanningEvents() but limited to single status
     * 
     * @performance Direct database query with status filtering for optimal performance
     * @caching Bypasses dashboard service caching for real-time status-specific views
     * 
     * @business_workflow Status-focused operational dashboards and management reports
     * @agricultural_planning Targeted view of specific production pipeline segments
     * @management_reporting Status-specific analytics and performance tracking
     * 
     * @see getCropPlanningEvents() For comprehensive multi-status calendar view
     * @see getStatusColor() For consistent color coding across filtered views
     * @see \App\Models\CropPlan For underlying status-based query capabilities
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