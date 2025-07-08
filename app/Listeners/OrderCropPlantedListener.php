<?php

namespace App\Listeners;

use App\Events\OrderCropPlanted;
use App\Services\StatusTransitionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderCropPlantedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The status transition service.
     *
     * @var \App\Services\StatusTransitionService
     */
    protected $statusService;

    /**
     * Create the event listener.
     *
     * @param \App\Services\StatusTransitionService $statusService
     * @return void
     */
    public function __construct(StatusTransitionService $statusService)
    {
        $this->statusService = $statusService;
    }

    /**
     * Handle the event.
     *
     * @param \App\Events\OrderCropPlanted $event
     * @return void
     */
    public function handle(OrderCropPlanted $event)
    {
        $order = $event->order;
        $crop = $event->crop;
        
        // Check if all required crops are now planted
        $requiredCrops = $order->cropPlans()->count();
        $plantedCrops = $order->crops()->whereNotNull('planting_at')->count();
        
        if ($requiredCrops > 0 && $plantedCrops >= $requiredCrops) {
            // All crops are planted, transition to growing status
            $this->statusService->handleBusinessEvent($order, 'crop.planted', [
                'crop_id' => $crop->id,
                'planted_crops' => $plantedCrops,
                'required_crops' => $requiredCrops
            ]);
        }
    }
}