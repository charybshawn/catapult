<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Services\OrderPlanningService;
use App\Services\CropPlanAggregateService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 * Handle business logic when order items change through Filament
 * Extracted from OrderItemObserver to work WITH Filament patterns
 */
class HandleOrderItemUpdatesAction
{
    public function __construct(
        private OrderPlanningService $orderPlanningService,
        private CropPlanAggregateService $aggregatedCropPlanService
    ) {}

    public function execute(Order $order, ?EditRecord $page = null): void
    {
        // Skip if order doesn't require crop production
        if (!$order->requiresCropProduction()) {
            return;
        }
        
        // Skip if order is in final state or is a template
        if ($order->isInFinalState() || $order->status?->code === 'template') {
            return;
        }

        $this->handleCropPlanUpdates($order, $page);
    }

    private function handleCropPlanUpdates(Order $order, ?EditRecord $page): void
    {
        // Get existing crop plans for this order
        $existingPlans = $order->cropPlans()->with('status')->get();
        
        if ($existingPlans->isEmpty()) {
            // No plans exist yet, generate if needed
            if ($order->shouldHaveCropPlans()) {
                $this->generateNewPlans($order, $page);
            }
            return;
        }
        
        // Check if only draft plans exist
        $onlyDraftPlans = $existingPlans->every(fn($p) => $p->status && $p->status->code === 'draft');
        
        if ($onlyDraftPlans) {
            $this->updateDraftPlans($order, $page);
        } else {
            $this->handleActivePlanChanges($order, $existingPlans, $page);
        }
    }

    private function generateNewPlans(Order $order, ?EditRecord $page): void
    {
        Log::info('Order items changed, generating crop plans', [
            'order_id' => $order->id,
            'user_id' => auth()->id()
        ]);
        
        $result = $this->orderPlanningService->generatePlansForOrder($order);
        
        if ($page) {
            if ($result['success']) {
                Notification::make()
                    ->title('Crop Plans Generated')
                    ->body("Generated {$result['plans']->count()} crop plans for updated order items")
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Crop Plan Generation Issues')
                    ->body('Some crop plans could not be generated for the updated items')
                    ->warning()
                    ->send();
            }
        }
    }

    private function updateDraftPlans(Order $order, ?EditRecord $page): void
    {
        Log::info('Order items changed, updating draft crop plans', [
            'order_id' => $order->id,
            'user_id' => auth()->id()
        ]);
        
        // Update the plans
        $this->orderPlanningService->updatePlansForOrder($order);
        
        // Recalculate aggregations
        $affectedPlans = $order->cropPlans()->with(['aggregatedCropPlan', 'status'])->get();
        foreach ($affectedPlans as $plan) {
            if ($plan->aggregatedCropPlan) {
                $this->aggregatedCropPlanService->recalculateAggregation($plan->aggregatedCropPlan);
            }
        }
        
        if ($page) {
            Notification::make()
                ->title('Crop Plans Updated')
                ->body('Draft crop plans have been updated to match the new order items')
                ->success()
                ->send();
        }
    }

    private function handleActivePlanChanges(Order $order, $existingPlans, ?EditRecord $page): void
    {
        Log::warning('Order items changed for order with active/completed crop plans', [
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'active_plans' => $existingPlans->filter(fn($p) => $p->status && $p->status->code === 'active')->count(),
            'completed_plans' => $existingPlans->filter(fn($p) => $p->status && $p->status->code === 'completed')->count()
        ]);
        
        if ($page) {
            Notification::make()
                ->title('Active Crop Plans Detected')
                ->body('Order items were changed but active crop plans exist. Manual review may be required.')
                ->warning()
                ->persistent()
                ->send();
        }
        
        // Notify managers about the change
        $this->notifyManagersOfActiveOrderChanges($order);
    }

    private function notifyManagersOfActiveOrderChanges(Order $order): void
    {
        try {
            $managers = \App\Models\User::role(['admin', 'manager'])->get();
            
            foreach ($managers as $manager) {
                // Only send notification if the notification class exists
                if (class_exists(\App\Notifications\OrderWithActivePlansModified::class)) {
                    $manager->notify(new \App\Notifications\OrderWithActivePlansModified($order));
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to notify managers of active order changes', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}