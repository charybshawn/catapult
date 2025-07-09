<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderPlanningService;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

class OrderObserver
{
    protected OrderPlanningService $orderPlanningService;

    public function __construct(OrderPlanningService $orderPlanningService)
    {
        $this->orderPlanningService = $orderPlanningService;
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Only generate plans for new orders (not templates or recurring)
        $statusCode = $order->status?->code;
        if (in_array($statusCode, ['draft', 'pending', 'confirmed', 'new'])) {
            if (!$order->is_recurring && $order->requiresCropProduction()) {
                try {
                    $result = $this->orderPlanningService->generatePlansForOrder($order);
                    
                    if ($result['success']) {
                        Log::info('Auto-generated crop plans for new order', [
                            'order_id' => $order->id,
                            'plans_count' => $result['plans']->count()
                        ]);
                    } else {
                        Log::warning('Failed to auto-generate crop plans for new order', [
                            'order_id' => $order->id,
                            'issues' => $result['issues']
                        ]);
                        
                        // Show Filament notification to the user
                        if (auth()->check()) {
                            $issueMessages = [];
                            foreach ($result['issues'] as $issue) {
                                if (is_array($issue)) {
                                    $recipe = $issue['recipe'] ?? 'Unknown variety';
                                    $problem = $issue['issue'] ?? 'Unknown issue';
                                    $issueMessages[] = "{$recipe}: {$problem}";
                                } else {
                                    $issueMessages[] = $issue;
                                }
                            }
                            
                            Notification::make()
                                ->title('Order Cannot Be Fulfilled')
                                ->body('This order cannot be fulfilled by the delivery date. Issues: ' . implode(', ', $issueMessages))
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error auto-generating crop plans for order', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Skip if order doesn't require crop production
        if (!$order->requiresCropProduction()) {
            return;
        }

        // Check if significant fields have changed
        $significantChanges = $order->isDirty(['delivery_date', 'harvest_date', 'unified_status_id']);
        
        if ($significantChanges) {
            $this->handleOrderChanges($order);
        }
    }
    
    /**
     * Handle changes to an order that might affect crop plans
     */
    protected function handleOrderChanges(Order $order): void
    {
        // Get existing crop plans
        $existingPlans = $order->cropPlans()
            ->with('status')
            ->get();
            
        if ($existingPlans->isEmpty()) {
            // No plans exist yet, generate if needed
            if ($order->shouldHaveCropPlans()) {
                $this->orderPlanningService->generatePlansForOrder($order);
            }
            return;
        }
        
        // Check plan statuses
        $draftPlans = $existingPlans->filter(fn($p) => $p->status->code === 'draft');
        $activePlans = $existingPlans->filter(fn($p) => $p->status->code === 'active');
        $completedPlans = $existingPlans->filter(fn($p) => $p->status->code === 'completed');
        
        // If order was cancelled, cancel draft plans
        if ($order->isInFinalState() || $order->status?->code === 'cancelled') {
            foreach ($draftPlans as $plan) {
                $plan->cancel();
                $plan->update(['admin_notes' => 'Cancelled due to order cancellation']);
            }
            
            // Remove from aggregation
            $aggregationService = app(\App\Services\AggregatedCropPlanService::class);
            foreach ($existingPlans as $plan) {
                $aggregationService->removeFromAggregation($plan);
            }
            
            Log::info('Cancelled crop plans due to order cancellation', [
                'order_id' => $order->id,
                'cancelled_plans' => $draftPlans->count()
            ]);
            return;
        }
        
        // If only draft plans exist and dates changed significantly
        if ($draftPlans->isNotEmpty() && $activePlans->isEmpty() && $completedPlans->isEmpty()) {
            if ($order->isDirty(['delivery_date', 'harvest_date'])) {
                // Update draft plans with new dates
                $result = $this->orderPlanningService->updatePlansForOrder($order);
                
                Log::info('Updated crop plans due to order date changes', [
                    'order_id' => $order->id,
                    'result' => $result
                ]);
            }
        } else if ($activePlans->isNotEmpty() || $completedPlans->isNotEmpty()) {
            // Log warning for manual review
            Log::warning('Order with active/completed crop plans was modified', [
                'order_id' => $order->id,
                'delivery_date_changed' => $order->isDirty('delivery_date'),
                'harvest_date_changed' => $order->isDirty('harvest_date'),
                'active_plans' => $activePlans->count(),
                'completed_plans' => $completedPlans->count()
            ]);
            
            // Send notification to manager
            $this->notifyManagerOfOrderChange($order, $existingPlans);
        }
    }
    
    /**
     * Notify manager when an order with active plans is modified
     */
    protected function notifyManagerOfOrderChange(Order $order, $plans): void
    {
        $managers = \App\Models\User::role(['admin', 'manager'])->get();
        
        foreach ($managers as $manager) {
            $manager->notify(new \App\Notifications\OrderWithActivePlansModified($order, $plans));
        }
    }

    /**
     * Handle the Order "deleting" event.
     */
    public function deleting(Order $order): bool
    {
        // Check if order has any active crop plans
        $activePlans = $order->cropPlans()
            ->whereHas('status', function ($query) {
                $query->whereIn('code', ['active']);
            })
            ->exists();

        if ($activePlans) {
            Log::warning('Attempted to delete order with active crop plans', [
                'order_id' => $order->id
            ]);
            // Prevent deletion if there are active plans
            return false;
        }

        // Check if order has any crops
        if ($order->crops()->exists()) {
            Log::warning('Attempted to delete order with existing crops', [
                'order_id' => $order->id
            ]);
            // Prevent deletion if there are crops
            return false;
        }

        return true;
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        // Cancel any draft crop plans
        $order->cropPlans()
            ->whereHas('status', function ($query) {
                $query->where('code', 'draft');
            })
            ->each(function ($plan) {
                $plan->cancel();
                $plan->update(['notes' => 'Cancelled due to order deletion']);
            });
    }
}