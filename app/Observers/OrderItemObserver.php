<?php

namespace App\Observers;

use App\Models\OrderItem;
use App\Services\OrderPlanningService;
use App\Services\CropPlanAggregateService;
use Illuminate\Support\Facades\Log;

class OrderItemObserver
{
    protected OrderPlanningService $orderPlanningService;
    protected CropPlanAggregateService $aggregatedCropPlanService;

    public function __construct(
        OrderPlanningService $orderPlanningService,
        CropPlanAggregateService $aggregatedCropPlanService
    ) {
        $this->orderPlanningService = $orderPlanningService;
        $this->aggregatedCropPlanService = $aggregatedCropPlanService;
    }

    /**
     * Handle the OrderItem "created" event.
     */
    public function created(OrderItem $orderItem): void
    {
        $this->handleOrderItemChange($orderItem, 'created');
    }

    /**
     * Handle the OrderItem "updated" event.
     */
    public function updated(OrderItem $orderItem): void
    {
        // Check if quantity changed significantly
        if ($orderItem->isDirty('quantity') || $orderItem->isDirty('product_id')) {
            $this->handleOrderItemChange($orderItem, 'updated');
        }
    }

    /**
     * Handle the OrderItem "deleted" event.
     */
    public function deleted(OrderItem $orderItem): void
    {
        $this->handleOrderItemChange($orderItem, 'deleted');
    }

    /**
     * Handle changes to order items
     */
    protected function handleOrderItemChange(OrderItem $orderItem, string $action): void
    {
        $order = $orderItem->order;
        
        // Skip if order doesn't require crop production
        if (!$order || !$order->requiresCropProduction()) {
            return;
        }
        
        // Skip if order is in final state or is a template
        if ($order->isInFinalState() || $order->status?->code === 'template') {
            return;
        }
        
        // Get existing crop plans for this order
        $existingPlans = $order->cropPlans()
            ->with('status')
            ->get();
            
        if ($existingPlans->isEmpty()) {
            // No plans exist yet, might need to generate
            if ($action !== 'deleted' && $order->shouldHaveCropPlans()) {
                Log::info('Order item changed, generating crop plans', [
                    'order_id' => $order->id,
                    'item_id' => $orderItem->id,
                    'action' => $action
                ]);
                
                $this->orderPlanningService->generatePlansForOrder($order);
            }
            return;
        }
        
        // Check if only draft plans exist
        $onlyDraftPlans = $existingPlans->every(fn($p) => $p->status->code === 'draft');
        
        if ($onlyDraftPlans) {
            // For draft plans, we can update them
            Log::info('Order item changed, updating draft crop plans', [
                'order_id' => $order->id,
                'item_id' => $orderItem->id,
                'action' => $action,
                'old_quantity' => $action === 'updated' ? $orderItem->getOriginal('quantity') : null,
                'new_quantity' => $orderItem->quantity
            ]);
            
            // Update the plans
            $this->orderPlanningService->updatePlansForOrder($order);
            
            // Recalculate aggregations
            $affectedPlans = $order->cropPlans()->with('aggregatedCropPlan')->get();
            foreach ($affectedPlans as $plan) {
                if ($plan->aggregatedCropPlan) {
                    $this->aggregatedCropPlanService->recalculateAggregation($plan->aggregatedCropPlan);
                }
            }
        } else {
            // Active or completed plans exist, log for manual review
            Log::warning('Order items changed for order with active/completed crop plans', [
                'order_id' => $order->id,
                'item_id' => $orderItem->id,
                'action' => $action,
                'active_plans' => $existingPlans->filter(fn($p) => $p->status->code === 'active')->count(),
                'completed_plans' => $existingPlans->filter(fn($p) => $p->status->code === 'completed')->count()
            ]);
            
            // Notify managers
            $this->notifyManagersOfItemChange($order, $orderItem, $action, $existingPlans);
        }
    }
    
    /**
     * Notify managers about order item changes
     */
    protected function notifyManagersOfItemChange($order, $orderItem, $action, $plans): void
    {
        $managers = \App\Models\User::role(['admin', 'manager'])->get();
        
        $message = match($action) {
            'created' => "New item added: {$orderItem->product->name} ({$orderItem->quantity}g)",
            'updated' => "Item quantity changed: {$orderItem->product->name}",
            'deleted' => "Item removed: {$orderItem->product->name}",
            default => "Item changed"
        };
        
        foreach ($managers as $manager) {
            $manager->notify(new \App\Notifications\OrderItemsChangedWithActivePlans($order, $message, $plans));
        }
    }
}