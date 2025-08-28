<?php

namespace App\Actions\CropPlan;

use Exception;
use App\Services\CropPlanningService;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * Generates agricultural crop production plans for order fulfillment workflows.
 * 
 * Orchestrates automated crop plan generation for microgreens production based on
 * confirmed customer orders within planning timeframes. Integrates with crop planning
 * service to create detailed production schedules, resource allocation plans,
 * and variety-specific cultivation timelines for agricultural facility operations.
 * 
 * @business_domain Agricultural Production Planning and Order Fulfillment
 * @crop_planning Automated production plan generation for microgreens cultivation
 * @order_integration Links customer orders to agricultural production schedules
 * 
 * @architecture Pure business logic class - NOT a Filament component
 * @usage Called FROM Filament resource actions and planning workflows
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class GenerateCropPlansAction
{
    /**
     * Injected crop planning service for agricultural production plan generation.
     * 
     * @var CropPlanningService
     */
    protected CropPlanningService $cropPlanningService;

    /**
     * Initialize GenerateCropPlansAction with crop planning service dependency.
     * 
     * @param CropPlanningService $cropPlanningService Service for agricultural production planning
     */
    public function __construct(CropPlanningService $cropPlanningService)
    {
        $this->cropPlanningService = $cropPlanningService;
    }

    /**
     * Execute automated crop plan generation for upcoming order fulfillment.
     * 
     * Generates comprehensive agricultural production plans for confirmed customer
     * orders within a 30-day planning window. Creates detailed cultivation schedules,
     * resource allocation plans, and variety-specific production timelines to ensure
     * timely harvest and delivery for agricultural facility operations.
     * 
     * @business_process Automated Production Planning Generation Workflow
     * @agricultural_context 30-day rolling production planning for microgreens orders
     * @order_fulfillment Links customer delivery dates to cultivation start dates
     * 
     * @return array Comprehensive generation results including:
     *   - success: Boolean operation status
     *   - start_date: Planning period start date
     *   - end_date: Planning period end date
     *   - order_count: Number of orders processed
     *   - plan_count: Number of crop plans generated
     *   - orders: Collection of processed orders
     *   - plans: Collection of generated crop plans
     *   - plans_by_order: Plans grouped by order for organization
     *   - variety_breakdown: Count of plans by variety type
     * 
     * @throws Exception Caught and returned in error result structure
     * 
     * @planning_window 30 days from current date for production scheduling
     * @order_eligibility Non-recurring orders in draft through in_production status
     * @result_organization Groups plans by order and variety for facility management
     * 
     * @usage Called from production planning interfaces and scheduled automation
     * @performance_impact Processes multiple orders with batch plan generation
     */
    public function execute(): array
    {
        try {
            $startDate = now()->toDateString();
            $endDate = now()->addDays(30)->toDateString();
            
            // Get orders available for crop plan generation
            $orders = $this->getOrdersForGeneration($startDate, $endDate);
            
            // Generate crop plans for orders in the date range
            $cropPlans = $this->cropPlanningService->generateIndividualPlansForAllOrders($startDate, $endDate);
            
            return $this->buildSuccessResult($startDate, $endDate, $orders, $cropPlans);
            
        } catch (Exception $e) {
            return $this->buildErrorResult($e);
        }
    }

    /**
     * Retrieve customer orders eligible for agricultural crop plan generation.
     * 
     * Queries database for non-recurring customer orders within specified date range
     * that require production planning. Filters by order status to include only
     * orders that are confirmed or in active production phases, excluding completed
     * or cancelled orders from planning workflow.
     * 
     * @business_logic Order Eligibility Filter for Production Planning
     * @agricultural_context Filters orders requiring microgreens cultivation planning
     * 
     * @param string $startDate Planning period start date (Y-m-d format)
     * @param string $endDate Planning period end date (Y-m-d format)
     * @return Collection Orders eligible for crop plan generation with customer and status data
     * 
     * @query_optimization Eager loads customer and status relationships
     * @date_filtering Matches orders by harvest_date within planning window
     * @recurring_exclusion Excludes recurring orders (handled separately)
     * @status_filtering Includes: draft, pending, confirmed, in_production status codes
     * 
     * @database_relations Loads customer and order status for planning context
     * @performance Uses whereHas for efficient status code filtering
     */
    protected function getOrdersForGeneration(string $startDate, string $endDate): Collection
    {
        return Order::with(['customer', 'status'])
            ->where('harvest_date', '>=', $startDate)
            ->where('harvest_date', '<=', $endDate)
            ->where('is_recurring', false)
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['draft', 'pending', 'confirmed', 'in_production']);
            })
            ->get();
    }

    /**
     * Build comprehensive success result structure for crop plan generation.
     * 
     * Creates detailed result array containing planning period information, order
     * and plan counts, raw data collections, and organized breakdowns for UI display
     * and session storage. Provides multiple data views for different presentation
     * needs in agricultural production planning interfaces.
     * 
     * @business_reporting Comprehensive production planning results for UI display
     * @data_organization Multiple collection views for different interface needs
     * 
     * @param string $startDate Planning period start date for reference
     * @param string $endDate Planning period end date for reference
     * @param Collection $orders Processed orders collection for context
     * @param Collection $cropPlans Generated crop plans collection
     * @return array Structured success result with comprehensive planning data
     * 
     * @result_organization Groups plans by order_id and variety for facility management
     * @variety_breakdown Counts plans by variety common_name for resource planning
     * @session_storage Formatted for modal display and temporary data storage
     */
    protected function buildSuccessResult(string $startDate, string $endDate, Collection $orders, Collection $cropPlans): array
    {
        return [
            'success' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'order_count' => $orders->count(),
            'plan_count' => $cropPlans->count(),
            'orders' => $orders,
            'plans' => $cropPlans,
            'plans_by_order' => $cropPlans->groupBy('order_id'),
            'variety_breakdown' => $cropPlans->groupBy('variety.common_name')->map->count(),
        ];
    }

    /**
     * Build standardized error result structure for failed plan generation.
     * 
     * Creates consistent error response format for crop plan generation failures.
     * Captures exception details for troubleshooting while providing standardized
     * structure for UI error handling and user notification systems.
     * 
     * @param Exception $e Caught exception from plan generation process
     * @return array Standardized error result structure
     * 
     * @error_handling Consistent format for UI integration and user notification
     * @troubleshooting Preserves exception message for debugging and support
     */
    protected function buildErrorResult(Exception $e): array
    {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}