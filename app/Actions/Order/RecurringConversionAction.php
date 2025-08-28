<?php

namespace App\Actions\Order;

use Exception;
use Filament\Actions\Action;
use App\Models\Order;
use App\Services\RecurringOrderService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Converts regular agricultural orders into recurring delivery templates.
 * 
 * Manages conversion of standard customer orders into recurring order templates
 * for automated delivery schedules. Handles validation, template creation,
 * and comprehensive user feedback for establishing ongoing agricultural
 * product delivery relationships with customers.
 * 
 * @business_domain Agricultural Customer Relationship and Recurring Sales Management
 * @order_conversion Regular to recurring order template transformation
 * @delivery_automation Template creation for scheduled agricultural deliveries
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class RecurringConversionAction
{
    /**
     * Initialize RecurringConversionAction with recurring order service dependency.
     * 
     * @param RecurringOrderService $recurringOrderService Service for recurring order management
     */
    public function __construct(
        private RecurringOrderService $recurringOrderService
    ) {}

    /**
     * Execute order conversion to recurring template with comprehensive validation.
     * 
     * Converts standard agricultural order into recurring delivery template with
     * specified schedule and recurrence settings. Validates order eligibility,
     * creates template through RecurringOrderService, and provides detailed
     * user feedback on conversion results.
     * 
     * @business_process Regular to Recurring Order Conversion Workflow
     * @agricultural_context Establishing ongoing delivery schedules for agricultural products
     * @template_creation Automated delivery schedule template generation
     * 
     * @param Order $order The standard order to convert to recurring template
     * @param array $recurringData Recurrence configuration including schedule and settings
     * @return Order|null Created recurring template order or null if conversion fails
     * 
     * @throws Exception From conversion process with comprehensive error handling
     * 
     * @eligibility_validation:
     *   - Order must not already be recurring
     *   - Order must have customer assigned
     *   - Order must have items for template creation
     *   - Order cannot be child of existing recurring template
     *   - Order must be in appropriate status for conversion
     * 
     * @conversion_workflow:
     *   1. Validate order conversion eligibility
     *   2. Convert via RecurringOrderService.convertToRecurringTemplate
     *   3. Send success notification with template details
     *   4. Log successful conversion operation
     *   5. Return created recurring template
     * 
     * @template_features Created template supports automated delivery scheduling
     * @audit_logging Detailed logging of conversion operations and results
     * 
     * @usage Called from order management interfaces for recurring conversion
     * @notification_system Comprehensive user feedback on conversion success/failure
     */
    public function execute(Order $order, array $recurringData): ?Order
    {
        // Validate order can be converted to recurring
        if (!$this->canConvertToRecurring($order)) {
            Log::warning('Attempted to convert ineligible order to recurring', [
                'order_id' => $order->id,
                'is_recurring' => $order->is_recurring,
                'status_code' => $order->status?->code,
                'has_parent_recurring' => !is_null($order->parent_recurring_order_id),
                'has_customer' => !is_null($order->customer),
                'has_items' => $order->orderItems()->count() > 0
            ]);
            
            $this->sendErrorNotification('Order is not eligible for conversion to recurring.');
            return null;
        }

        try {
            $convertedOrder = $this->recurringOrderService->convertToRecurringTemplate(
                $order, 
                $recurringData
            );
            
            $this->sendSuccessNotification($order, $convertedOrder);
            
            Log::info('Order converted to recurring template successfully', [
                'original_order_id' => $order->id,
                'converted_order_id' => $convertedOrder->id,
                'frequency' => $recurringData['frequency'] ?? 'unknown',
                'start_date' => $recurringData['start_date'] ?? null,
                'end_date' => $recurringData['end_date'] ?? null
            ]);
            
            return $convertedOrder;
        } catch (Exception $e) {
            Log::error('Failed to convert order to recurring template', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'recurring_data' => $recurringData
            ]);
            
            $this->sendErrorNotification('Failed to convert order to recurring: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if order can be converted to recurring
     */
    protected function canConvertToRecurring(Order $order): bool
    {
        return !$order->is_recurring && 
               $order->status?->code !== 'template' &&
               $order->parent_recurring_order_id === null && // Not generated from recurring
               $order->customer &&
               $order->orderItems()->count() > 0;
    }

    protected function sendSuccessNotification(Order $originalOrder, Order $convertedOrder): void
    {
        Notification::make()
            ->title('Order Converted Successfully')
            ->body("Order #{$originalOrder->id} has been converted to a recurring template.")
            ->success()
            ->actions([
                Action::make('view')
                    ->label('View Template')
                    ->url(route('filament.admin.resources.recurring-orders.edit', ['record' => $convertedOrder->id]))
            ])
            ->send();
    }

    protected function sendErrorNotification(string $message): void
    {
        Notification::make()
            ->title('Conversion Failed')
            ->body($message)
            ->danger()
            ->send();
    }
}