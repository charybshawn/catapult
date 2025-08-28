<?php

namespace App\Listeners;

use App\Events\OrderCropPlanted;
use App\Services\StatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Agricultural crop planting event listener for order status workflow automation.
 * 
 * Handles OrderCropPlanted events to manage order status transitions when crops
 * are planted for agricultural orders. Monitors crop planting progress and automatically
 * transitions orders to 'growing' status when all required crops are planted.
 * Essential component in microgreens production workflow automation.
 * 
 * @business_domain Agricultural crop planting workflow and order status management
 * @agricultural_process Crop planting milestone tracking for order fulfillment
 * @workflow_automation Automatic order status progression based on crop planting
 * @queue_processing Asynchronous processing to avoid blocking planting operations
 */
class OrderCropPlantedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Service for managing agricultural order status transitions based on crop events.
     * 
     * Handles complex business logic for transitioning orders through agricultural
     * workflow states based on crop planting milestones and production progress.
     *
     * @var StatusTransitionService Service managing order lifecycle in agricultural context
     */
    protected $statusService;

    /**
     * Create the crop planting event listener with status transition service.
     * 
     * Initializes the listener with required service for managing agricultural
     * order status transitions based on crop planting progress.
     *
     * @param StatusTransitionService $statusService Service for order workflow management
     * @return void
     */
    public function __construct(StatusTransitionService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle crop planting event and evaluate order status transition eligibility.
     * 
     * Processes crop planting events by checking if all required crops for an order
     * are now planted, and triggers order status transition to 'growing' when
     * planting phase is complete. Critical for agricultural workflow automation.
     * 
     * @param OrderCropPlanted $event Event containing order and newly planted crop
     * @return void
     * 
     * @business_process Agricultural order progression based on crop planting milestones
     * @workflow_logic Evaluates crop planting completion against order requirements
     * @agricultural_automation Moves orders to growing phase when all crops planted
     */
    public function handle(OrderCropPlanted $event)
    {
        $order = $event->order;
        $crop = $event->crop;
        
        // Evaluate crop planting completion against order requirements
        $requiredCrops = $order->cropPlans()->count();
        $plantedCrops = $order->crops()->whereNotNull('planting_at')->count();
        
        // Transition order to growing status when all required crops are planted
        if ($requiredCrops > 0 && $plantedCrops >= $requiredCrops) {
            $this->statusService->handleBusinessEvent($order, 'crop.planted', [
                'crop_id' => $crop->id,
                'planted_crops' => $plantedCrops,
                'required_crops' => $requiredCrops
            ]);
        }
    }
}