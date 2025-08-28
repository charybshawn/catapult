<?php

namespace App\Actions\RecurringOrder;

use Exception;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Generate Recurring Orders Action
 * Extracted from RecurringOrderResource table actions
 * Pure business logic - no Filament dependencies
 * Max 100 lines as per requirements
 */
class GenerateRecurringOrdersAction
{
    /**
     * Execute order generation for a single recurring template
     */
    public function execute(Order $template): array
    {
        try {
            $generatedOrders = $template->generateRecurringOrdersCatchUp();
            
            if (!empty($generatedOrders)) {
                $count = count($generatedOrders);
                $latestOrder = end($generatedOrders);
                
                return [
                    'success' => true,
                    'title' => "Generated {$count} order(s) successfully",
                    'message' => "Latest order #{$latestOrder->id} for {$latestOrder->delivery_date->format('M d, Y')}",
                ];
            }
            
            return [
                'success' => false,
                'title' => 'Unable to generate orders',
                'message' => 'Check template settings and end date',
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to generate recurring orders', [
                'template_id' => $template->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'title' => 'Error generating orders',
                'message' => $e->getMessage(),
            ];
        }
    }
}