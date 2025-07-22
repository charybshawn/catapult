<?php

namespace App\Actions\Order;

use App\Models\Order;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Handle wholesale price recalculation for orders
 */
class PriceRecalculationAction
{
    /**
     * Recalculate all item prices using current wholesale discount
     */
    public function execute(Order $order): array
    {
        if (!$this->isEligibleForRecalculation($order)) {
            return ['success' => false, 'message' => 'Order is not eligible for price recalculation'];
        }

        try {
            $result = $this->performPriceRecalculation($order);
            $this->logAndNotify($order, $result);
            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to recalculate order prices', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            $this->sendErrorNotification($e->getMessage());
            return ['success' => false, 'message' => 'Failed to recalculate prices: ' . $e->getMessage()];
        }
    }
    
    protected function performPriceRecalculation(Order $order): array
    {
        $oldTotal = $order->totalAmount();
        $updatedItems = 0;
        
        foreach ($order->orderItems as $item) {
            if (!$item->product || !$item->price_variation_id) continue;
            
            $currentPrice = $item->product->getPriceForSpecificCustomer($order->customer, $item->price_variation_id);
            
            if (abs($currentPrice - $item->price) > 0.001) {
                $item->update(['price' => $currentPrice]);
                $updatedItems++;
            }
        }
        
        $newTotal = $order->fresh()->totalAmount();
        return [
            'success' => true,
            'items_updated' => $updatedItems,
            'old_total' => $oldTotal,
            'new_total' => $newTotal,
            'savings' => $oldTotal - $newTotal
        ];
    }
    
    protected function logAndNotify(Order $order, array $result): void
    {
        if ($result['items_updated'] > 0) {
            $this->sendSuccessNotification($result['items_updated'], $result['new_total'], $result['savings']);
            Log::info('Order prices recalculated successfully', ['order_id' => $order->id] + $result);
        } else {
            $this->sendNoChangesNotification();
        }
    }

    /**
     * Check if order is eligible for price recalculation
     */
    protected function isEligibleForRecalculation(Order $order): bool
    {
        return $order->status?->code !== 'template' && 
               $order->status?->code !== 'cancelled' &&
               !$order->status?->is_final &&
               $order->customer->isWholesaleCustomer() &&
               $order->orderItems->isNotEmpty();
    }

    protected function sendSuccessNotification(int $updatedItems, float $newTotal, float $savings): void
    {
        Notification::make()
            ->title('Prices Recalculated')
            ->body("Updated {$updatedItems} items. New total: $" . number_format($newTotal, 2) . " (saved $" . number_format($savings, 2) . ")")
            ->success()
            ->send();
    }

    protected function sendNoChangesNotification(): void
    {
        Notification::make()
            ->title('No Changes Needed')
            ->body('All prices are already up to date.')
            ->info()
            ->send();
    }

    protected function sendErrorNotification(string $message): void
    {
        Notification::make()
            ->title('Error Recalculating Prices')
            ->body('Failed to recalculate prices: ' . $message)
            ->danger()
            ->send();
    }
}