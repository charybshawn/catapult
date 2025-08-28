<?php

namespace App\Actions\Order;

use Exception;
use Filament\Actions\Action;
use App\Models\Order;
use App\Models\Invoice;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Creates invoices from agricultural customer orders with validation and feedback.
 * 
 * Manages single order to invoice conversion workflow including eligibility validation,
 * invoice generation through Order model methods, comprehensive error handling,
 * and user notification management. Ensures proper billing workflow for
 * agricultural product deliveries.
 * 
 * @business_domain Agricultural Customer Billing and Invoice Management
 * @invoice_creation Single order to invoice conversion with validation
 * @billing_workflow Integrated invoice generation for agricultural sales
 * 
 * @author Catapult System
 * @since 1.0.0
 */
class InvoiceCreationAction
{
    /**
     * Execute invoice creation from agricultural order with comprehensive validation.
     * 
     * Validates order eligibility for invoicing, creates invoice through Order model
     * factory methods, and provides comprehensive user feedback. Handles all
     * invoice creation scenarios including success, validation failures, and
     * system errors with appropriate logging and notifications.
     * 
     * @business_process Single Order Invoice Creation Workflow
     * @agricultural_context Invoice generation for microgreens and agricultural product orders
     * @validation_comprehensive Eligibility checks with detailed error feedback
     * 
     * @param Order $order The agricultural order to create invoice from
     * @return Invoice|null Created invoice instance or null if creation fails
     * 
     * @throws Exception From invoice creation process with error handling
     * 
     * @eligibility_validation:
     *   - Order must require invoice (requires_invoice = true)
     *   - Order cannot already have existing invoice
     *   - Order must be in appropriate status for invoicing
     * 
     * @workflow_steps:
     *   1. Validate order invoice eligibility
     *   2. Create invoice using Invoice::createFromOrder method
     *   3. Send success notification with invoice details
     *   4. Log successful invoice creation
     *   5. Return created invoice instance
     * 
     * @error_handling Comprehensive exception catching with user notifications
     * @audit_logging Detailed success and failure logging for billing operations
     * 
     * @usage Called from order management interfaces and billing workflows
     * @notification_integration Filament notification system for user feedback
     */
    public function execute(Order $order): ?Invoice
    {
        // Validate order can have an invoice created
        if (!$this->canCreateInvoice($order)) {
            Log::warning('Attempted to create invoice for ineligible order', [
                'order_id' => $order->id,
                'status_code' => $order->status?->code,
                'requires_invoice' => $order->requires_invoice,
                'has_existing_invoice' => !is_null($order->invoice)
            ]);
            
            $this->sendErrorNotification('Order is not eligible for invoice creation.');
            return null;
        }

        try {
            $invoice = Invoice::createFromOrder($order);
            
            $this->sendSuccessNotification($order, $invoice);
            
            Log::info('Invoice created successfully from order', [
                'order_id' => $order->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number ?? $invoice->id,
                'total_amount' => $order->totalAmount()
            ]);
            
            return $invoice;
        } catch (Exception $e) {
            Log::error('Failed to create invoice from order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->sendErrorNotification('Failed to create invoice: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if an invoice can be created for this order
     */
    protected function canCreateInvoice(Order $order): bool
    {
        return $order->status?->code !== 'template' && 
               $order->requires_invoice &&
               !$order->invoice; // No existing invoice
    }

    protected function sendSuccessNotification(Order $order, Invoice $invoice): void
    {
        Notification::make()
            ->title('Invoice Created')
            ->body("Invoice #{$invoice->id} has been created successfully.")
            ->success()
            ->actions([
                Action::make('view')
                    ->label('View Invoice')
                    ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id]))
            ])
            ->send();
    }

    protected function sendErrorNotification(string $message): void
    {
        Notification::make()
            ->title('Error Creating Invoice')
            ->body($message)
            ->danger()
            ->send();
    }
}