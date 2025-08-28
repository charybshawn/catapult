<?php

namespace App\Services;

use App\Models\CropPlan;
use App\Models\Order;
use App\Models\CropPlanStatus;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Agricultural Crop Planning Dashboard Service for Microgreens Production Management
 * 
 * This service encapsulates complex agricultural business logic for crop planning dashboards,
 * providing farmers and production managers with critical decision-making data. It bridges
 * the gap between raw agricultural data and actionable production insights.
 * 
 * Core Agricultural Functions:
 * - Urgent crop identification: Seeds that must be planted within 7 days to meet delivery commitments
 * - Overdue crop detection: Missed planting windows requiring immediate attention and customer communication
 * - Order workflow integration: Customer delivery requirements driving backward production scheduling
 * - Dashboard statistics: Key performance indicators for agricultural production management
 * - Calendar data preparation: Agricultural event data formatted for visual planning interfaces
 * 
 * Business Context:
 * - Microgreens have short, precise growing cycles (7-21 days) requiring exact timing
 * - Customer delivery commitments are firm deadlines that cannot be missed without revenue loss
 * - Planting schedules are calculated backward from delivery dates using recipe growing times
 * - Production capacity constraints require careful scheduling to avoid overcommitment
 * - Dashboard views enable proactive management vs reactive crisis response
 * 
 * Agricultural Workflow Integration:
 * - Order Management: Customer delivery requirements and production scheduling coordination
 * - Recipe Management: Growing time calculations and variety-specific production parameters
 * - Capacity Planning: Tray and growing space allocation for optimal facility utilization
 * - Quality Control: Status tracking ensures proper execution of agricultural production plans
 * 
 * Key Performance Metrics:
 * - Urgent crops: Immediate action required to prevent delivery failures
 * - Overdue crops: Production delays requiring management intervention and customer communication
 * - Upcoming orders: Future production requirements for proactive capacity planning
 * - Planning pipeline health: Overall agricultural production schedule assessment
 * 
 * @business_domain Agricultural microgreens production planning and dashboard analytics
 * @agricultural_context Short-cycle crop production with precise timing requirements
 * @dashboard_integration Filament dashboard widgets and calendar component data source
 * @performance Optimized queries with strategic eager loading for dashboard responsiveness
 * 
 * @see CalendarEventService For agricultural calendar event generation using this service
 * @see \App\Filament\Pages\CropPlanningDashboard For UI integration and dashboard presentation
 * @see \App\Models\CropPlan For underlying agricultural planning data structure
 * @see \App\Models\Order For customer delivery requirements and production drivers
 */
class CropPlanDashboardService
{
    /**
     * Get critical agricultural crop plans requiring immediate planting attention
     * 
     * Identifies active crop plans that must be planted within the next 7 days to meet
     * customer delivery commitments. This method provides the foundation for urgent
     * action dashboards and daily operational task prioritization in microgreens production.
     * 
     * Agricultural Context:
     * - 7-day window represents critical action threshold for microgreens production
     * - Active status indicates approved plans ready for immediate execution
     * - Plant-by dates are calculated backward from customer delivery requirements
     * - Grouping by date enables daily task planning and resource allocation
     * - Missing these deadlines results in delivery failures and customer dissatisfaction
     * 
     * Business Logic:
     * - Only includes 'active' status plans (approved and committed)
     * - Filters to current date through 7 days ahead (immediate action window)
     * - Orders by plant_by_date for chronological task prioritization
     * - Groups results by planting date for daily operational planning
     * - Includes complete relationship data for production decision-making
     * 
     * Operational Use Cases:
     * - Daily production task assignment for agricultural staff
     * - Resource allocation planning for growing trays and seeding materials
     * - Capacity constraint identification for production scheduling
     * - Priority alerts for farm management dashboards
     * 
     * @return Collection<string, Collection<CropPlan>> Crop plans grouped by plant_by_date (Y-m-d format)
     *         with complete agricultural context including:
     *         - recipe.seedEntry: Variety specifications and growing requirements
     *         - order.customer: Customer context for delivery coordination
     *         - status: Current plan status for workflow tracking
     *         Each date group contains CropPlan objects ready for immediate action
     * 
     * @business_priority Critical agricultural deadline management for revenue protection
     * @operational_context Daily task prioritization and resource allocation
     * @customer_impact Direct correlation to delivery commitments and customer satisfaction
     * 
     * @see getOverdueCrops() For plans that missed planting deadlines
     * @see getDashboardStats() For aggregate urgent crop metrics
     * @see \App\Models\CropPlanStatus::findByCode() For status-based filtering logic
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
     * Get overdue agricultural crop plans requiring immediate management intervention
     * 
     * Identifies active crop plans that have missed their optimal planting windows,
     * representing critical production delays that require immediate management attention.
     * These overdue plans directly threaten customer delivery commitments and may require
     * customer communication, schedule renegotiation, or alternative production strategies.
     * 
     * Agricultural Context:
     * - Overdue plans indicate missed optimal planting windows for microgreens production
     * - Each day of delay compounds delivery risk and may affect crop quality/yield
     * - Active status indicates these are still committed plans requiring resolution
     * - Past plant_by_date means delivery timelines are now at risk
     * - Immediate action required to minimize customer impact and revenue loss
     * 
     * Business Impact:
     * - Direct threat to customer satisfaction and revenue protection
     * - May require customer communication about delivery delays or alternatives
     * - Indicates potential process failures in production planning or execution
     * - Could signal capacity constraints or resource allocation problems
     * - Management intervention needed to prevent cascading schedule failures
     * 
     * Resolution Strategies:
     * - Immediate replanning with adjusted delivery dates if feasible
     * - Customer communication about potential delays or alternatives
     * - Resource reallocation to expedite delayed plantings where possible
     * - Root cause analysis to prevent future overdue situations
     * - Alternative variety selection if faster-growing options available
     * 
     * Operational Use Cases:
     * - Crisis management dashboards for production supervisors
     * - Customer service preparation for delivery delay communications
     * - Management reporting for production performance assessment
     * - Process improvement analysis for planning workflow optimization
     * 
     * @return Collection<CropPlan> Overdue crop plans ordered by plant_by_date (most overdue first)
     *         with complete agricultural and business context including:
     *         - recipe.seedEntry: Variety information for alternative planning decisions
     *         - order.customer: Customer contact information for delay communication
     *         - status: Current plan status confirming active commitment
     *         Each plan represents an urgent management decision point
     * 
     * @business_priority Critical production failure management requiring immediate intervention
     * @customer_impact High risk of delivery delays and customer dissatisfaction
     * @management_alert Requires supervisor attention and corrective action planning
     * 
     * @see getUrgentCrops() For plans approaching deadline but still actionable
     * @see getDashboardStats() For aggregate overdue crop metrics
     * @see \App\Models\CropPlanStatus::findByCode() For status validation logic
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
     * Get upcoming customer orders requiring agricultural crop plan generation
     * 
     * Identifies confirmed customer orders within the next 14 days that lack
     * corresponding crop plans, representing immediate agricultural planning
     * requirements. These orders drive the creation of new production schedules
     * and resource allocation decisions for microgreens production.
     * 
     * Agricultural Context:
     * - 14-day window provides adequate time for crop plan creation and execution
     * - Orders without crop plans represent unscheduled production requirements
     * - Delivery dates create firm deadlines for backward production scheduling
     * - Customer commitments drive capacity allocation and resource planning decisions
     * - Early identification enables proactive planning vs reactive crisis management
     * 
     * Business Logic:
     * - Filters to actionable order statuses (pending, confirmed, processing)
     * - Includes only orders with future delivery dates within planning horizon
     * - Excludes orders that already have crop plans (production scheduled)
     * - Orders chronologically by delivery date for planning prioritization
     * - Includes customer and product context for agricultural planning decisions
     * 
     * Planning Workflow Integration:
     * - Drives crop plan generation workflow for agricultural production scheduling
     * - Enables proactive capacity planning and resource allocation
     * - Supports customer delivery commitment coordination
     * - Identifies orders requiring immediate agricultural planning attention
     * - Feeds into overall production pipeline planning and optimization
     * 
     * Operational Use Cases:
     * - Daily planning review for agricultural production managers
     * - Resource allocation planning for growing space and materials
     * - Customer delivery commitment tracking and coordination
     * - Production pipeline gap identification and resolution
     * - Planning workflow automation triggers and batch processing
     * 
     * @return Collection<Order> Customer orders requiring crop plan creation, ordered by delivery_date
     *         with complete business context including:
     *         - customer: Customer information for communication and delivery coordination
     *         - orderItems.product: Product specifications driving agricultural requirements
     *         Each order represents an unscheduled production requirement needing attention
     * 
     * @business_workflow Order-to-production planning pipeline integration
     * @agricultural_planning Customer-driven production schedule generation requirements
     * @capacity_management Resource allocation and growing space planning drivers
     * 
     * @see getUrgentCrops() For active plans requiring immediate execution
     * @see getDashboardStats() For aggregate upcoming order metrics
     * @see \App\Models\Order For order status definitions and workflow states
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
     * Get comprehensive agricultural dashboard statistics for production management
     * 
     * Provides key performance indicators and action metrics for agricultural production
     * dashboards, enabling rapid assessment of production pipeline health and identifying
     * areas requiring immediate management attention. These statistics drive daily
     * operational decision-making and resource allocation in microgreens production.
     * 
     * Agricultural Metrics:
     * - urgent_crops_count: Active plans requiring planting within 7 days
     * - overdue_crops_count: Missed planting deadlines requiring immediate intervention
     * - upcoming_orders_count: Customer orders needing crop plan generation
     * 
     * Business Context:
     * - Urgent crops represent immediate action requirements for delivery success
     * - Overdue crops indicate production failures requiring management intervention
     * - Upcoming orders show future planning requirements for capacity management
     * - Combined metrics provide overall production pipeline health assessment
     * - Statistics enable proactive vs reactive agricultural production management
     * 
     * Dashboard Integration:
     * - Feeds dashboard widgets with actionable agricultural metrics
     * - Enables quick visual assessment of production schedule health
     * - Supports alert systems for critical agricultural deadline management
     * - Drives daily operational briefings and task prioritization
     * - Provides foundation for management reporting and performance tracking
     * 
     * Operational Use Cases:
     * - Daily production management briefings for agricultural staff
     * - Key performance indicator tracking for production supervisors
     * - Alert thresholds for automated notification systems
     * - Management reporting for production performance assessment
     * - Resource allocation planning based on workload metrics
     * 
     * @return array{urgent_crops_count: int, overdue_crops_count: int, upcoming_orders_count: int}
     *         Agricultural production statistics with the following meanings:
     *         - urgent_crops_count: Number of crop plans requiring planting within 7 days
     *         - overdue_crops_count: Number of crop plans that missed planting deadlines
     *         - upcoming_orders_count: Number of orders needing crop plan generation
     *         All counts represent actionable items requiring management attention
     * 
     * @business_intelligence Agricultural production KPIs for decision-making support
     * @dashboard_widgets Primary data source for Filament dashboard statistics displays
     * @performance Optimized aggregation leveraging existing filtered collections
     * 
     * @see getUrgentCrops() For detailed urgent crop plan analysis
     * @see getOverdueCrops() For detailed overdue crop plan intervention requirements
     * @see getUpcomingOrders() For detailed order planning pipeline analysis
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
     * Get agricultural crop plans within specified date range for calendar and planning views
     * 
     * Retrieves crop plans scheduled for planting within a specified date range, providing
     * the foundation for calendar visualizations, planning reports, and production scheduling
     * interfaces. This method supports flexible date ranges for various agricultural planning
     * horizons and reporting requirements.
     * 
     * Agricultural Context:
     * - Default range covers 30 days history and 60 days future for comprehensive planning context
     * - Historical data provides production pattern analysis and performance tracking
     * - Future data enables proactive capacity planning and resource allocation
     * - Plant-by dates drive all downstream production scheduling and resource coordination
     * - Comprehensive relationship loading supports complex agricultural decision-making
     * 
     * Business Applications:
     * - Calendar widget data source for visual production schedule management
     * - Planning report generation for agricultural production analysis
     * - Capacity planning tools for growing space and resource allocation
     * - Performance analysis comparing planned vs actual production execution
     * - Integration with external calendar systems for facility scheduling
     * 
     * Data Optimization:
     * - Strategic eager loading prevents N+1 queries for dashboard performance
     * - Date range filtering optimizes query performance for large datasets
     * - Chronological ordering supports sequential processing and display
     * - Complete relationship context enables comprehensive agricultural analysis
     * 
     * @param Carbon|null $startDate Start of date range, defaults to 30 days ago for historical context
     * @param Carbon|null $endDate End of date range, defaults to 60 days ahead for planning horizon
     * 
     * @return Collection<CropPlan> Crop plans within date range with complete agricultural context:
     *         - recipe.seedEntry: Variety specifications and growing requirements
     *         - order: Customer delivery requirements driving production scheduling
     *         - status: Current plan status for workflow and progress tracking
     *         Ordered chronologically by plant_by_date for sequential processing
     * 
     * @business_workflow Calendar integration and agricultural planning report generation
     * @performance Optimized with strategic eager loading for dashboard responsiveness
     * @integration CalendarEventService and dashboard widget data source
     * 
     * @see CalendarEventService::getCropPlanningEvents() For calendar event generation
     * @see getOrdersByDeliveryDateRange() For complementary delivery schedule data
     * @see \App\Models\CropPlan For underlying agricultural planning data structure
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
     * Get customer orders within delivery date range for calendar and delivery coordination
     * 
     * Retrieves customer orders with confirmed delivery dates within a specified range,
     * providing the foundation for delivery planning, calendar integration, and customer
     * commitment coordination. This method focuses on actionable orders requiring
     * agricultural production support and delivery logistics coordination.
     * 
     * Agricultural Context:
     * - Default range covers 30 days history and 60 days future for comprehensive delivery context
     * - Historical deliveries provide customer pattern analysis and service quality tracking
     * - Future deliveries drive backward production scheduling and resource allocation
     * - Delivery dates represent firm customer commitments requiring precise fulfillment
     * - Only includes actionable order statuses that require agricultural production support
     * 
     * Business Applications:
     * - Calendar widget data source for customer delivery deadline visualization
     * - Delivery logistics planning and route optimization coordination
     * - Customer service preparation for delivery confirmation and communication
     * - Revenue projection based on confirmed customer delivery commitments
     * - Integration with agricultural production scheduling for harvest coordination
     * 
     * Operational Use Cases:
     * - Delivery driver route planning and customer coordination
     * - Agricultural harvest scheduling to meet customer delivery requirements
     * - Customer service proactive communication about delivery confirmations
     * - Management reporting for revenue pipeline and customer satisfaction
     * - Quality control coordination for delivery-ready product preparation
     * 
     * Data Filtering Logic:
     * - Includes only orders with confirmed delivery dates (excludes open/draft orders)
     * - Filters to actionable statuses requiring production and delivery coordination
     * - Date range enables flexible reporting and planning horizon management
     * - Customer relationship loading supports delivery coordination and communication
     * 
     * @param Carbon|null $startDate Start of delivery date range, defaults to 30 days ago
     * @param Carbon|null $endDate End of delivery date range, defaults to 60 days ahead
     * 
     * @return Collection<Order> Orders within delivery date range with customer context:
     *         - customer: Customer information for delivery coordination and communication
     *         Ordered chronologically by delivery_date for logistics optimization
     *         All orders have confirmed delivery_date and actionable status
     * 
     * @business_workflow Customer delivery deadline coordination and logistics planning
     * @agricultural_integration Harvest scheduling driven by customer delivery requirements
     * @customer_service Delivery confirmation and coordination data source
     * 
     * @see CalendarEventService::getOrderDeliveryEvents() For calendar event generation
     * @see getCropPlansByDateRange() For complementary production schedule data
     * @see \App\Models\Order For order status definitions and delivery workflow
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