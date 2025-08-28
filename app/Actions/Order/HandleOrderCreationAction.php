<?php

namespace App\Actions\Order;

use Exception;
use App\Models\Order;
use App\Services\OrderPlanningService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

/**
 * Handles post-creation workflows for agricultural customer orders.
 * 
 * Manages automated crop plan generation and user feedback when new orders
 * are created through Filament interfaces. Integrates with production planning
 * services to automatically generate cultivation schedules and provides
 * comprehensive user notifications about planning results.
 * 
 * @business_domain Agricultural Order Processing and Production Planning
 * @order_lifecycle Post-creation workflow automation for agricultural orders
 * @production_integration Automatic crop plan generation for order fulfillment
 * 
 * @architecture Extracted from OrderObserver to work WITH Filament patterns
 * @filament_integration Designed for Filament CreateRecord page integration
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class HandleOrderCreationAction
{
    /**
     * Initialize HandleOrderCreationAction with order planning service dependency.
     * 
     * @param OrderPlanningService $orderPlanningService Service for automated crop plan generation
     */
    public function __construct(
        private OrderPlanningService $orderPlanningService
    ) {}

    /**
     * Execute post-creation workflow for newly created agricultural orders.
     * 
     * Determines if automatic crop plan generation is appropriate for the new order
     * and executes planning workflow with comprehensive user feedback. Handles
     * planning success, partial success, and failure scenarios with detailed
     * notifications and audit logging.
     * 
     * @business_process Order Post-Creation Workflow
     * @agricultural_context Automated production planning for microgreens orders
     * @filament_context Integrates with Filament CreateRecord page for user feedback
     * 
     * @param Order $order Newly created order requiring processing
     * @param CreateRecord $page Filament page context for user notifications
     * 
     * @workflow_logic Generates plans only for eligible orders requiring crop production
     * @feedback_system Comprehensive success/warning/error notifications
     * @audit_logging Detailed logging for planning operations and failures
     * 
     * @usage Called from OrderResource CreateRecord hooks after order creation
     * @error_handling Graceful error handling with user-friendly notifications
     */
    public function execute(Order $order, CreateRecord $page): void
    {
        if ($this->shouldGeneratePlans($order)) {
            $this->generatePlansWithFeedback($order, $page);
        }
    }

    private function shouldGeneratePlans(Order $order): bool
    {
        $statusCode = $order->status?->code;
        return in_array($statusCode, ['draft', 'pending', 'confirmed', 'new'])
            && !$order->is_recurring 
            && $order->requiresCropProduction();
    }

    private function generatePlansWithFeedback(Order $order, CreateRecord $page): void
    {
        try {
            $result = $this->orderPlanningService->generatePlansForOrder($order);
            
            if ($result['success']) {
                $this->handlePlanningSuccess($result, $order);
            } else {
                $this->handlePlanningFailure($result, $order);
            }
        } catch (Exception $e) {
            Log::error('Failed to generate crop plans for new order', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            
            Notification::make()
                ->title('Crop Plan Generation Error')
                ->body('An error occurred while generating crop plans. Please try again or contact support.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    private function handlePlanningSuccess(array $result, Order $order): void
    {
        Log::info('Auto-generated crop plans for new order', [
            'order_id' => $order->id,
            'plans_count' => $result['plans']->count()
        ]);

        Notification::make()
            ->title('Order Created Successfully')
            ->body("Generated {$result['plans']->count()} crop plans for this order")
            ->success()
            ->send();
    }

    private function handlePlanningFailure(array $result, Order $order): void
    {
        Log::warning('Failed to auto-generate crop plans for new order', [
            'order_id' => $order->id,
            'issues' => $result['issues']
        ]);

        // Format issues for user display
        $issueMessages = [];
        foreach ($result['issues'] as $issue) {
            if (is_array($issue)) {
                $recipe = $issue['recipe'] ?? 'Unknown variety';
                $problem = $issue['issue'] ?? 'Unknown issue';
                $issueMessages[] = "{$recipe}: {$problem}";
            } else {
                $issueMessages[] = (string) $issue;
            }
        }

        $issueText = implode('<br>', array_slice($issueMessages, 0, 5));
        if (count($issueMessages) > 5) {
            $issueText .= '<br>...and ' . (count($issueMessages) - 5) . ' more issues';
        }

        Notification::make()
            ->title('Crop Plan Generation Issues')
            ->body('Order created but some crop plans could not be generated:<br>' . $issueText)
            ->warning()
            ->persistent()
            ->send();
    }
}