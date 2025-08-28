<?php

namespace App\Listeners;

use App\Events\OrderHarvested;
use App\Services\StatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Agricultural harvest completion event listener for order fulfillment workflow.
 * 
 * Handles OrderHarvested events to automatically transition orders from growing
 * phase to packing phase when crop harvesting is completed. Critical component
 * in microgreens production workflow that bridges growing operations with
 * order fulfillment and packaging processes.
 * 
 * @business_domain Agricultural harvest workflow and order fulfillment automation
 * @agricultural_process Harvest completion milestone for order progression
 * @workflow_automation Automatic transition from growing to packing phase
 * @queue_processing Asynchronous processing to avoid blocking harvest operations
 */
class OrderHarvestedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Service for managing agricultural order status transitions based on harvest events.
     * 
     * Handles business logic for transitioning orders from growing phase to
     * packaging phase based on harvest completion milestones in microgreens production.
     *
     * @var StatusTransitionService Service managing order workflow in agricultural context
     */
    protected $statusService;

    /**
     * Create the harvest completion event listener with status transition service.
     * 
     * Initializes the listener with required service for managing agricultural
     * order status transitions based on harvest completion events.
     *
     * @param StatusTransitionService $statusService Service for order workflow management
     * @return void
     */
    public function __construct(StatusTransitionService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle crop harvest completion event and trigger order status transition.
     * 
     * Processes harvest completion events by automatically transitioning orders
     * from growing phase to packing phase. Includes harvest metrics for monitoring
     * production efficiency and crop yield tracking.
     * 
     * @param OrderHarvested $event Event containing order with completed harvest
     * @return void
     * 
     * @business_process Agricultural order progression from growing to packing phase
     * @harvest_metrics Tracks harvested crop counts for production monitoring
     * @workflow_automation Automatic transition based on harvest completion
     */
    public function handle(OrderHarvested $event)
    {
        $order = $event->order;
        
        // Transition order to packing status with harvest completion metrics
        $this->statusService->handleBusinessEvent($order, 'harvest.completed', [
            'harvested_crops' => $order->crops()->where('current_stage', 'harvested')->count(),
            'total_crops' => $order->crops()->count()
        ]);
    }
}