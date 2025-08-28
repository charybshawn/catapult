<?php

namespace App\Actions\Order;

use Exception;
use Filament\Actions\Action;
use App\Models\Order;
use App\Services\RecurringOrderService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Generates new orders from recurring agricultural delivery templates.
 * 
 * Creates the next scheduled order in a recurring delivery series based on
 * template configuration and schedule settings. Handles template validation,
 * order generation through RecurringOrderService, and comprehensive user
 * feedback for automated agricultural delivery workflows.
 * 
 * @business_domain Agricultural Recurring Sales and Automated Delivery Management
 * @order_generation Next order creation from recurring templates
 * @delivery_automation Scheduled agricultural product delivery execution
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class RecurringOrderGenerationAction
{
    /**
     * Initialize RecurringOrderGenerationAction with recurring order service dependency.
     * 
     * @param RecurringOrderService $recurringOrderService Service for recurring order generation
     */
    public function __construct(
        private RecurringOrderService $recurringOrderService
    ) {}

    /**
     * Execute next order generation from recurring agricultural delivery template.
     * 
     * Creates new order instance from recurring template with current pricing,
     * delivery scheduling, and customer information. Validates template eligibility,
     * generates order through RecurringOrderService, and provides comprehensive
     * user feedback on generation results.
     * 
     * @business_process Recurring Order Generation Workflow
     * @agricultural_context Next scheduled delivery creation for agricultural products
     * @automation_execution Template-based order creation with current data
     * 
     * @param Order $templateOrder The recurring template order for generation
     * @return Order|null Generated order instance or null if generation fails
     * 
     * @throws Exception From order generation process with error handling
     * 
     * @template_validation:
     *   - Order must be marked as recurring (is_recurring = true)
     *   - Order status must be 'template' for active templates
     *   - Template must have valid configuration for generation
     * 
     * @generation_workflow:
     *   1. Validate template order eligibility
     *   2. Generate next order via RecurringOrderService.generateNextOrder
     *   3. Send success notification with new order details
     *   4. Log successful generation operation
     *   5. Return created order instance
     * 
     * @current_data_integration Generated orders use current pricing and inventory
     * @schedule_management Automatic delivery date calculation from template settings
     * 
     * @usage Called from recurring order management interfaces and automated scheduling
     * @notification_system User feedback on successful generation or failures
     */
    public function execute(Order $templateOrder): ?Order
    {
        // Validate this is a recurring template
        if (!$templateOrder->is_recurring || $templateOrder->status?->code !== 'template') {
            Log::warning('Attempted to generate recurring order from non-template', [
                'order_id' => $templateOrder->id,
                'is_recurring' => $templateOrder->is_recurring,
                'status_code' => $templateOrder->status?->code
            ]);
            
            $this->sendErrorNotification('Order is not a valid recurring template.');
            return null;
        }

        try {
            $newOrder = $this->recurringOrderService->generateNextOrder($templateOrder);
            
            if ($newOrder) {
                $this->sendSuccessNotification($templateOrder, $newOrder);
                return $newOrder;
            } else {
                $this->sendWarningNotification();
                return null;
            }
        } catch (Exception $e) {
            Log::error('Failed to generate recurring order', [
                'template_order_id' => $templateOrder->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendErrorNotification('Failed to generate recurring order: ' . $e->getMessage());
            return null;
        }
    }

    protected function sendSuccessNotification(Order $templateOrder, Order $newOrder): void
    {
        Notification::make()
            ->title('Recurring Order Generated')
            ->body("Order #{$newOrder->id} has been created successfully.")
            ->success()
            ->actions([
                Action::make('view')
                    ->label('View Order')
                    ->url(route('filament.admin.resources.orders.edit', ['record' => $newOrder->id]))
            ])
            ->send();
    }

    protected function sendWarningNotification(): void
    {
        Notification::make()
            ->title('No Order Generated')
            ->body('No new order was generated. It may not be time for the next recurring order yet.')
            ->warning()
            ->send();
    }

    protected function sendErrorNotification(string $message): void
    {
        Notification::make()
            ->title('Error Generating Order')
            ->body($message)
            ->danger()
            ->send();
    }
}