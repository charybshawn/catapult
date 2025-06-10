<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceConsolidationService
{
    /**
     * Generate consolidated invoices for B2B customers based on their billing frequency.
     */
    public function generateConsolidatedInvoices(Carbon $forDate = null): Collection
    {
        $forDate = $forDate ?? now();
        $generatedInvoices = collect();
        
        Log::info('Starting consolidated invoice generation', ['date' => $forDate->toDateString()]);
        
        // Get all B2B customers with recurring orders that need consolidation
        $customersNeedingInvoices = $this->getCustomersNeedingConsolidatedInvoices($forDate);
        
        foreach ($customersNeedingInvoices as $customer) {
            try {
                $invoice = $this->generateConsolidatedInvoiceForCustomer($customer, $forDate);
                if ($invoice) {
                    $generatedInvoices->push($invoice);
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate consolidated invoice for customer', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Completed consolidated invoice generation', [
            'generated_count' => $generatedInvoices->count()
        ]);
        
        return $generatedInvoices;
    }
    
    /**
     * Get customers who need consolidated invoices generated for the given date.
     */
    protected function getCustomersNeedingConsolidatedInvoices(Carbon $forDate): Collection
    {
        return User::whereHas('orders', function ($query) use ($forDate) {
            $query->where('order_type', 'b2b_recurring')
                ->where('billing_frequency', '!=', 'immediate')
                ->where('requires_invoice', true)
                ->whereNull('consolidated_invoice_id')
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($forDate) {
                    // Orders that fall within billing periods ending on or before the target date
                    $q->where(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'weekly')
                            ->where('billing_period_end', '<=', $forDate->toDateString());
                    })->orWhere(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'monthly')
                            ->where('billing_period_end', '<=', $forDate->toDateString());
                    })->orWhere(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'quarterly')
                            ->where('billing_period_end', '<=', $forDate->toDateString());
                    });
                });
        })->get();
    }
    
    /**
     * Generate a consolidated invoice for a specific customer.
     */
    public function generateConsolidatedInvoiceForCustomer(User $customer, Carbon $forDate = null): ?Invoice
    {
        $forDate = $forDate ?? now();
        
        // Get all unbilled orders for this customer that should be consolidated
        $ordersToConsolidate = $this->getOrdersToConsolidate($customer, $forDate);
        
        if ($ordersToConsolidate->isEmpty()) {
            return null;
        }
        
        return DB::transaction(function () use ($customer, $ordersToConsolidate, $forDate) {
            // Create the consolidated invoice
            $invoice = $this->createConsolidatedInvoice($customer, $ordersToConsolidate, $forDate);
            
            // Link all orders to this consolidated invoice
            $this->linkOrdersToConsolidatedInvoice($ordersToConsolidate, $invoice);
            
            Log::info('Generated consolidated invoice', [
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'order_count' => $ordersToConsolidate->count(),
                'total_amount' => $invoice->total_amount
            ]);
            
            return $invoice;
        });
    }
    
    /**
     * Get orders that should be consolidated for a customer.
     */
    protected function getOrdersToConsolidate(User $customer, Carbon $forDate): Collection
    {
        return $customer->orders()
            ->where('order_type', 'b2b_recurring')
            ->where('billing_frequency', '!=', 'immediate')
            ->where('requires_invoice', true)
            ->whereNull('consolidated_invoice_id')
            ->where('status', '!=', 'cancelled')
            ->where('billing_period_end', '<=', $forDate->toDateString())
            ->with(['orderItems', 'user'])
            ->get();
    }
    
    /**
     * Create the consolidated invoice record.
     */
    protected function createConsolidatedInvoice(User $customer, Collection $orders, Carbon $forDate): Invoice
    {
        $totalAmount = $orders->sum(function ($order) {
            return $order->totalAmount();
        });
        
        $earliestBillingStart = $orders->min('billing_period_start');
        $latestBillingEnd = $orders->max('billing_period_end');
        
        $invoice = Invoice::create([
            'user_id' => $customer->id,
            'invoice_number' => $this->generateConsolidatedInvoiceNumber($customer, $forDate),
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'issue_date' => $forDate->toDateString(),
            'due_date' => $forDate->copy()->addDays(30)->toDateString(), // 30 days payment terms
            'billing_period_start' => $earliestBillingStart,
            'billing_period_end' => $latestBillingEnd,
            'is_consolidated' => true,
            'consolidated_order_count' => $orders->count(),
            'notes' => "Consolidated invoice for {$orders->count()} orders from {$earliestBillingStart} to {$latestBillingEnd}"
        ]);
        
        return $invoice;
    }
    
    /**
     * Link orders to the consolidated invoice.
     */
    protected function linkOrdersToConsolidatedInvoice(Collection $orders, Invoice $invoice): void
    {
        $orders->each(function ($order) use ($invoice) {
            $order->update(['consolidated_invoice_id' => $invoice->id]);
        });
    }
    
    /**
     * Generate a unique invoice number for consolidated invoices.
     */
    protected function generateConsolidatedInvoiceNumber(User $customer, Carbon $forDate): string
    {
        $prefix = 'CONS';
        $customerCode = strtoupper(substr($customer->name, 0, 3));
        $dateCode = $forDate->format('Ymd');
        $sequence = Invoice::where('invoice_number', 'like', "{$prefix}-{$customerCode}-{$dateCode}-%")
            ->count() + 1;
            
        return sprintf('%s-%s-%s-%03d', $prefix, $customerCode, $dateCode, $sequence);
    }
    
    /**
     * Set billing periods for orders based on their billing frequency.
     */
    public function setBillingPeriodForOrder(Order $order): void
    {
        if ($order->order_type !== 'b2b_recurring' || $order->billing_frequency === 'immediate') {
            return;
        }
        
        $deliveryDate = $order->delivery_date ?? now();
        
        switch ($order->billing_frequency) {
            case 'weekly':
                $periodStart = $deliveryDate->copy()->startOfWeek();
                $periodEnd = $deliveryDate->copy()->endOfWeek();
                break;
                
            case 'monthly':
                $periodStart = $deliveryDate->copy()->startOfMonth();
                $periodEnd = $deliveryDate->copy()->endOfMonth();
                break;
                
            case 'quarterly':
                $periodStart = $deliveryDate->copy()->startOfQuarter();
                $periodEnd = $deliveryDate->copy()->endOfQuarter();
                break;
                
            default:
                return;
        }
        
        $order->update([
            'billing_period_start' => $periodStart->toDateString(),
            'billing_period_end' => $periodEnd->toDateString()
        ]);
    }
    
    /**
     * Process immediate invoicing for website orders.
     */
    public function processImmediateInvoicing(Order $order): ?Invoice
    {
        if (!$order->requiresImmediateInvoicing() || !$order->requires_invoice) {
            return null;
        }
        
        // Check if invoice already exists
        if ($order->invoice) {
            return $order->invoice;
        }
        
        return DB::transaction(function () use ($order) {
            $invoice = Invoice::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'invoice_number' => $this->generateImmediateInvoiceNumber($order),
                'total_amount' => $order->totalAmount(),
                'status' => 'pending',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(7)->toDateString(), // 7 days for immediate orders
                'is_consolidated' => false,
                'notes' => "Invoice for order #{$order->id}"
            ]);
            
            Log::info('Generated immediate invoice', [
                'invoice_id' => $invoice->id,
                'order_id' => $order->id,
                'total_amount' => $invoice->total_amount
            ]);
            
            return $invoice;
        });
    }
    
    /**
     * Generate invoice number for immediate orders.
     */
    protected function generateImmediateInvoiceNumber(Order $order): string
    {
        $prefix = 'INV';
        $dateCode = now()->format('Ymd');
        $sequence = Invoice::where('invoice_number', 'like', "{$prefix}-{$dateCode}-%")
            ->count() + 1;
            
        return sprintf('%s-%s-%04d', $prefix, $dateCode, $sequence);
    }
}