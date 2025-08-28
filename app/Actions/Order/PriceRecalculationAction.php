<?php

namespace App\Actions\Order;

use Exception;
use App\Models\Order;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Recalculates order item prices using current agricultural product pricing.
 * 
 * Manages wholesale price updates for agricultural orders when pricing changes
 * or customer discount levels are modified. Compares current item prices with
 * latest pricing data and updates order totals while maintaining audit trails
 * and providing comprehensive user feedback.
 * 
 * @business_domain Agricultural Product Pricing and Order Management
 * @price_synchronization Order item price updates with current pricing data
 * @wholesale_management Customer-specific pricing with discount integration
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class PriceRecalculationAction
{
    /**
     * Execute comprehensive price recalculation for agricultural order items.
     * 
     * Updates all order item prices based on current product pricing and
     * customer-specific discount rates. Compares existing prices with current
     * rates and updates items where price differences are detected, providing
     * detailed results and user feedback for pricing changes.
     * 
     * @business_process Order Price Recalculation Workflow
     * @agricultural_context Current microgreens pricing with customer discounts
     * @price_accuracy Ensures order reflects current agricultural product rates
     * 
     * @param Order $order The agricultural order requiring price recalculation
     * @return array Structured result with success status, counts, and totals
     * 
     * @throws Exception From price calculation failures with error handling
     * 
     * @eligibility_checks Validates order is appropriate for price recalculation
     * @price_comparison Compares current vs stored prices with precision tolerance
     * @batch_updates Efficiently updates multiple order items requiring changes
     * 
     * @result_structure Returns:
     *   - success: Boolean operation status
     *   - message: User-friendly result description
     *   - updated_items: Count of items with price changes
     *   - old_total: Previous order total amount
     *   - new_total: Updated order total amount
     *   - savings: Total amount saved (if applicable)
     * 
     * @customer_pricing Uses Product::getPriceForSpecificCustomer for accuracy
     * @audit_logging Detailed logging of price changes and calculation results
     * 
     * @usage Called from order management interfaces for price synchronization
     * @notification_system Provides user feedback on recalculation results
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
        } catch (Exception $e) {
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