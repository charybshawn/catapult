<?php

namespace App\Actions\RecurringOrder;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Bulk Generate Orders Action
 * Extracted from RecurringOrderResource bulk actions
 * Pure business logic - no Filament dependencies
 * Max 100 lines as per requirements
 */
class BulkGenerateOrdersAction
{
    /**
     * Execute bulk generation for all active templates
     */
    public function executeForAllActive(): array
    {
        $templates = Order::with(['customer', 'orderType', 'orderItems.product', 'orderItems.priceVariation', 'packagingTypes'])
            ->where('is_recurring', true)
            ->where('is_recurring_active', true)
            ->get();

        return $this->processTemplates($templates);
    }

    /**
     * Execute bulk generation for selected records
     */
    public function executeForRecords(Collection $records): array
    {
        return $this->processTemplates($records);
    }

    /**
     * Process a collection of templates and generate orders
     */
    protected function processTemplates(Collection $templates): array
    {
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $generatedOrders = [];

        foreach ($templates as $template) {
            try {
                $newOrders = $template->generateRecurringOrdersCatchUp();
                
                if (!empty($newOrders)) {
                    foreach ($newOrders as $newOrder) {
                        $successCount++;
                        $generatedOrders[] = [
                            'order_id' => $newOrder->id,
                            'customer' => $template->customer->contact_name ?? 'Unknown',
                            'delivery_date' => $newOrder->delivery_date->format('M d, Y')
                        ];
                    }
                } else {
                    $errorCount++;
                    $customerName = $template->customer->contact_name ?? 'Unknown';
                    $errors[] = "Template #{$template->id} ({$customerName}): Generation returned null";
                }
            } catch (\Exception $e) {
                $errorCount++;
                $customerName = $template->customer->contact_name ?? 'Unknown';
                $errors[] = "Template #{$template->id} ({$customerName}): {$e->getMessage()}";
                
                Log::error('Bulk order generation failed for template', [
                    'template_id' => $template->id,
                    'customer' => $customerName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $this->formatResult($templates, $successCount, $errorCount, $errors, $generatedOrders);
    }

    /**
     * Format the result for display
     */
    protected function formatResult(Collection $templates, int $successCount, int $errorCount, array $errors, array $generatedOrders): array
    {
        $title = "Recurring Order Generation Complete";
        $body = "Processed {$templates->count()} templates:\n";
        $body .= "✅ Successfully generated: {$successCount} orders\n";
        $body .= "❌ Errors: {$errorCount} templates";

        if (count($generatedOrders) > 0) {
            $body .= "\n\nGenerated Orders:\n";
            foreach ($generatedOrders as $order) {
                $body .= "• Order #{$order['order_id']} - {$order['customer']} (Delivery: {$order['delivery_date']})\n";
            }
        }

        if (count($errors) > 0) {
            $body .= "\n\nErrors:\n";
            foreach ($errors as $error) {
                $body .= "• {$error}\n";
            }
        }

        $type = $errorCount > 0 ? 'warning' : ($successCount > 0 ? 'success' : 'info');

        return [
            'title' => $title,
            'message' => $body,
            'type' => $type,
            'success_count' => $successCount,
            'error_count' => $errorCount,
        ];
    }
}