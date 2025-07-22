<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Services\RecurringOrderService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Handle conversion of regular orders to recurring templates
 */
class RecurringConversionAction
{
    public function __construct(
        private RecurringOrderService $recurringOrderService
    ) {}

    /**
     * Convert an order to a recurring template
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
        } catch (\Exception $e) {
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
                \Filament\Notifications\Actions\Action::make('view')
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