<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Services\RecurringOrderService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Handle generation of next recurring order from template
 */
class RecurringOrderGenerationAction
{
    public function __construct(
        private RecurringOrderService $recurringOrderService
    ) {}

    /**
     * Generate the next order in a recurring series
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
        } catch (\Exception $e) {
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
                \Filament\Notifications\Actions\Action::make('view')
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