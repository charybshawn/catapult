<?php

namespace App\Actions\Order;

use App\Models\Order;
use App\Models\Invoice;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Handle invoice creation from orders
 */
class InvoiceCreationAction
{
    /**
     * Create an invoice from an order
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
        } catch (\Exception $e) {
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
                \Filament\Notifications\Actions\Action::make('view')
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