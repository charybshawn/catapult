<?php

namespace App\Console\Commands;

use Exception;
use App\Models\User;
use App\Services\InvoiceConsolidationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateConsolidatedInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate-consolidated 
                            {--date= : Date to generate invoices for (YYYY-MM-DD format)}
                            {--dry-run : Show what would be generated without actually creating invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate consolidated invoices for B2B customers based on their billing frequency';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceConsolidationService $consolidationService): int
    {
        $dateOption = $this->option('date');
        $dryRun = $this->option('dry-run');
        
        try {
            $forDate = $dateOption ? Carbon::parse($dateOption) : now();
        } catch (Exception $e) {
            $this->error('Invalid date format. Please use YYYY-MM-DD format.');
            return Command::FAILURE;
        }
        
        $this->info("Generating consolidated invoices for: {$forDate->toDateString()}");
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No invoices will be created');
            $this->line('');
        }
        
        try {
            if ($dryRun) {
                $this->performDryRun($consolidationService, $forDate);
            } else {
                $generatedInvoices = $consolidationService->generateConsolidatedInvoices($forDate);
                
                if ($generatedInvoices->isEmpty()) {
                    $this->info('No consolidated invoices needed for the specified date.');
                } else {
                    $this->info("Successfully generated {$generatedInvoices->count()} consolidated invoices:");
                    
                    foreach ($generatedInvoices as $invoice) {
                        $this->line("- Invoice #{$invoice->invoice_number} for {$invoice->user->name} - {$invoice->effective_amount}");
                    }
                }
            }
            
            return Command::SUCCESS;
            
        } catch (Exception $e) {
            $this->error('Error generating consolidated invoices: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Perform a dry run to show what would be generated.
     */
    protected function performDryRun(InvoiceConsolidationService $consolidationService, Carbon $forDate): void
    {
        // Get customers who would have consolidated invoices generated
        $customersNeedingInvoices = $this->getCustomersNeedingConsolidatedInvoices($forDate);
        
        if ($customersNeedingInvoices->isEmpty()) {
            $this->info('No customers need consolidated invoices for the specified date.');
            return;
        }
        
        $this->info("Found {$customersNeedingInvoices->count()} customers who would receive consolidated invoices:");
        $this->line('');
        
        foreach ($customersNeedingInvoices as $customer) {
            $orders = $this->getOrdersToConsolidate($customer, $forDate);
            $totalAmount = $orders->sum(function ($order) {
                return $order->totalAmount();
            });
            
            $this->line("Customer: {$customer->name}");
            $this->line("  Orders: {$orders->count()}");
            $this->line("  Total Amount: \${$totalAmount}");
            $this->line("  Billing Periods: {$orders->min('billing_period_start')} to {$orders->max('billing_period_end')}");
            $this->line('');
        }
    }
    
    /**
     * Get customers who need consolidated invoices (duplicate of service method for dry-run).
     */
    protected function getCustomersNeedingConsolidatedInvoices(Carbon $forDate)
    {
        return User::whereHas('orders', function ($query) use ($forDate) {
            $query->where('order_type', 'b2b')
                ->where('billing_frequency', '<>', 'immediate')
                ->where('requires_invoice', true)
                ->whereNull('consolidated_invoice_id')
                ->where('status', '<>', 'cancelled')
                ->where(function ($q) use ($forDate) {
                    $q->where(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'weekly')
                            ->where('billing_period_end', '<=', $forDate);
                    })->orWhere(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'monthly')
                            ->where('billing_period_end', '<=', $forDate);
                    })->orWhere(function ($periodQuery) use ($forDate) {
                        $periodQuery->where('billing_frequency', 'quarterly')
                            ->where('billing_period_end', '<=', $forDate);
                    });
                });
        })->get();
    }
    
    /**
     * Get orders to consolidate for a customer (duplicate of service method for dry-run).
     */
    protected function getOrdersToConsolidate(User $customer, Carbon $forDate)
    {
        return $customer->orders()
            ->where('order_type', 'b2b')
            ->where('billing_frequency', '<>', 'immediate')
            ->where('requires_invoice', true)
            ->whereNull('consolidated_invoice_id')
            ->where('status', '<>', 'cancelled')
            ->where('billing_period_end', '<=', $forDate)
            ->with(['orderItems', 'user'])
            ->get();
    }
}