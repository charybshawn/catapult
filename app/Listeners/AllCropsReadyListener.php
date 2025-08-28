<?php

namespace App\Listeners;

use App\Events\AllCropsReady;
use App\Services\StatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Agricultural crop readiness event listener for harvest workflow automation.
 * 
 * Handles AllCropsReady events to trigger automatic order status transitions
 * when all crops associated with an order have reached harvest readiness.
 * Critical component in agricultural microgreens production workflow that
 * automates the transition from growing phase to harvest-ready phase.
 * 
 * @business_domain Agricultural crop lifecycle and harvest workflow management
 * @agricultural_process Crop maturation monitoring and harvest readiness detection  
 * @workflow_automation Automatic status transitions for order fulfillment
 * @queue_processing Runs asynchronously to avoid blocking crop readiness checks
 */
class AllCropsReadyListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Service for managing agricultural order status transitions.
     * 
     * Handles business logic for transitioning orders through agricultural
     * workflow states based on crop readiness and harvest timing. Manages
     * the complex state transitions in microgreens production cycle.
     *
     * @var StatusTransitionService Service managing order lifecycle transitions
     * @business_context Agricultural order status management for crop production
     */
    protected $statusService;

    /**
     * Create the event listener with status transition service dependency.
     * 
     * Initializes the listener with required service for managing agricultural
     * order status transitions. Essential for automated harvest workflow
     * management in microgreens production operations.
     *
     * @param StatusTransitionService $statusService Service for order status management
     * @return void
     * 
     * @dependency_injection StatusTransitionService handles agricultural workflow logic
     */
    public function __construct(StatusTransitionService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle agricultural crop readiness event for harvest workflow automation.
     * 
     * Processes AllCropsReady events by triggering order status transition to
     * 'ready to harvest' when all associated crops have reached maturity.
     * Includes crop count metrics for monitoring and verification purposes.
     * 
     * @param AllCropsReady $event Event containing order with ready crops
     * @return void
     * 
     * @business_process Agricultural harvest readiness workflow automation
     * @agricultural_logic Transitions order when all crops reach harvest maturity
     * @metrics_tracking Captures total crops vs ready crops for monitoring
     * @queue_execution Runs asynchronously to avoid blocking main workflow
     */
    public function handle(AllCropsReady $event)
    {
        $order = $event->order;
        
        // Transition order to ready-to-harvest status with crop readiness metrics
        $this->statusService->handleBusinessEvent($order, 'crops.ready', [
            'total_crops' => $order->crops()->count(),
            'ready_crops' => $order->crops()->where('is_ready_to_harvest', true)->count()
        ]);
    }
}