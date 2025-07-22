<?php

namespace App\Actions\Order;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Handle creation of consolidated invoices from multiple orders
 */
class ConsolidatedInvoiceAction
{
    /**
     * Create a consolidated invoice from multiple orders
     */
    public function execute(Collection $orders, array $invoiceData): ?Invoice
    {
        $validationErrors = $this->validateOrdersForConsolidation($orders);
        if (!empty($validationErrors)) {
            $this->sendValidationErrorNotification($validationErrors);
            return null;
        }

        try {
            $invoice = $this->createConsolidatedInvoice($orders, $invoiceData);
            $this->sendSuccessNotification($orders, $invoice);
            Log::info('Consolidated invoice created successfully', [
                'invoice_id' => $invoice->id, 'order_count' => $orders->count(), 'total_amount' => $invoice->total_amount
            ]);
            return $invoice;
        } catch (\Exception $e) {
            Log::error('Failed to create consolidated invoice', ['error' => $e->getMessage(), 'order_count' => $orders->count()]);
            $this->sendErrorNotification($e->getMessage());
            return null;
        }
    }

    /**
     * Validate that orders can be consolidated into a single invoice
     */
    protected function validateOrdersForConsolidation(Collection $orders): array
    {
        $errors = [];
        
        if ($orders->count() < 2) $errors[] = 'At least 2 orders are required for consolidated invoicing.';
        if ($orders->filter(fn($o) => $o->status?->code === 'template')->isNotEmpty()) $errors[] = 'Cannot create invoices for template orders.';
        if ($orders->where('requires_invoice', false)->isNotEmpty()) $errors[] = 'Some selected orders do not require invoices.';
        if ($orders->whereNotNull('invoice_id')->isNotEmpty()) $errors[] = 'Some orders already have invoices.';
        if ($orders->pluck('user_id')->unique()->count() > 1) $errors[] = 'All orders must belong to the same customer for consolidated invoicing.';
        
        return $errors;
    }

    /**
     * Create the consolidated invoice
     */
    protected function createConsolidatedInvoice(Collection $orders, array $data): Invoice
    {
        $totalAmount = $orders->sum(fn($order) => $order->totalAmount());
        $deliveryDates = $orders->pluck('delivery_date')->map(fn($date) => Carbon::parse($date))->sort();
        
        $invoice = Invoice::create([
            'user_id' => $orders->first()->user_id,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'billing_period_start' => $deliveryDates->first()->startOfMonth(),
            'billing_period_end' => $deliveryDates->last()->endOfMonth(),
            'is_consolidated' => true,
            'consolidated_order_count' => $orders->count(),
            'notes' => $data['notes'] ?? "Consolidated invoice for {$orders->count()} orders: " . $orders->pluck('id')->implode(', '),
        ]);

        $orders->each(fn($order) => $order->update(['consolidated_invoice_id' => $invoice->id]));
        return $invoice;
    }

    protected function sendSuccessNotification(Collection $orders, Invoice $invoice): void
    {
        Notification::make()
            ->title('Consolidated Invoice Created')
            ->body("Invoice #{$invoice->invoice_number} created for {$orders->count()} orders totaling $" . number_format($invoice->total_amount, 2) . ".")
            ->success()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('View Invoice')
                    ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id]))
            ])
            ->send();
    }

    protected function sendValidationErrorNotification(array $errors): void
    {
        Notification::make()
            ->title('Cannot Create Consolidated Invoice')
            ->body(implode(' ', $errors))
            ->danger()
            ->persistent()
            ->send();
    }

    protected function sendErrorNotification(string $message): void
    {
        Notification::make()
            ->title('Error Creating Invoice')
            ->body('Failed to create consolidated invoice: ' . $message)
            ->danger()
            ->send();
    }
}