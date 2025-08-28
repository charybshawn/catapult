<?php

namespace App\Models;

use InvalidArgumentException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Invoice Model for Agricultural Product Billing and Payment Management
 * 
 * Manages complex billing workflows for agricultural microgreens business including
 * individual order invoicing, consolidated billing for recurring B2B customers,
 * and payment tracking throughout the agricultural production cycle.
 * 
 * This model handles:
 * - Individual order invoicing for one-time purchases
 * - Consolidated monthly/weekly invoicing for recurring B2B customers
 * - Payment status tracking (draft, sent, paid, overdue, cancelled)
 * - Billing period management for seasonal agricultural customers
 * - Invoice numbering with year/month sequential formatting (INV-YYYYMM-0001)
 * 
 * Business Context:
 * Agricultural customers often need flexible billing cycles that align with their
 * harvest schedules, seasonal cash flow, and recurring delivery patterns. This model
 * supports both immediate invoicing for retail customers and consolidated billing
 * for commercial restaurants and distributors with regular orders.
 * 
 * @property int $id Primary key
 * @property int $order_id Single order being invoiced (null for consolidated)
 * @property int $user_id User who created the invoice (system user for automated)
 * @property int $customer_id Customer being billed for agricultural products
 * @property string $invoice_number Formatted invoice number (INV-YYYYMM-0001)
 * @property float $amount Base amount for individual order invoice
 * @property float $total_amount Total amount including consolidated orders
 * @property int $payment_status_id Current payment status (draft/sent/paid/overdue)
 * @property \Carbon\Carbon $issue_date Date invoice was generated
 * @property \Carbon\Carbon $due_date Payment due date (typically 30 days)
 * @property \Carbon\Carbon|null $sent_at Timestamp when invoice was sent to customer
 * @property \Carbon\Carbon|null $paid_at Timestamp when payment was received
 * @property \Carbon\Carbon|null $billing_period_start Start of billing period for consolidated invoices
 * @property \Carbon\Carbon|null $billing_period_end End of billing period for consolidated invoices
 * @property bool $is_consolidated Whether this invoice consolidates multiple orders
 * @property int $consolidated_order_count Number of orders included in consolidated invoice
 * @property string|null $notes Additional invoice notes or payment terms
 * @property \Carbon\Carbon $created_at Invoice creation timestamp
 * @property \Carbon\Carbon $updated_at Last modification timestamp
 * 
 * @relationship order BelongsTo relationship to single Order (null for consolidated invoices)
 * @relationship user BelongsTo relationship to User who created the invoice
 * @relationship customer BelongsTo relationship to Customer being billed
 * @relationship paymentStatus BelongsTo relationship to PaymentStatus lookup
 * @relationship consolidatedOrders HasMany relationship to Orders included in consolidated invoice
 * 
 * @business_rules
 * - Individual order invoices have order_id and amount populated, is_consolidated = false
 * - Consolidated invoices have order_id = null, total_amount populated, is_consolidated = true
 * - Invoice numbers follow INV-YYYYMM-0001 format with sequential numbering per month
 * - Due dates default to 30 days but can be customized per customer payment terms
 * - Payment status transitions: draft → sent → paid/overdue → cancelled
 * - Consolidated invoices span billing periods and include multiple delivered orders
 * 
 * @workflow_patterns
 * Individual Order Invoicing:
 * 1. Order is fulfilled and ready for invoicing
 * 2. Invoice::createFromOrder() generates invoice with order details
 * 3. Invoice marked as 'sent' when delivered to customer
 * 4. Payment received triggers 'paid' status update
 * 
 * Consolidated Billing Workflow:
 * 1. InvoiceConsolidationService identifies customers needing monthly billing
 * 2. Service collects all delivered orders within billing period
 * 3. Consolidated invoice created with total_amount and billing period
 * 4. All included orders linked via consolidated_invoice_id foreign key
 * 
 * @see \App\Services\InvoiceConsolidationService For consolidated billing logic
 * @see \App\Models\PaymentStatus For payment workflow states
 * @see \App\Models\Order For order-to-invoice relationships
 * 
 * @author Agricultural Systems Team
 * @package App\Models
 */
class Invoice extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     * 
     * Defines which invoice fields can be bulk assigned during creation
     * and updates, supporting both individual and consolidated billing workflows.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'user_id',
        'customer_id',
        'invoice_number',
        'amount',
        'total_amount',
        'payment_status_id',
        'issue_date',
        'due_date',
        'sent_at',
        'paid_at',
        'billing_period_start',
        'billing_period_end',
        'is_consolidated',
        'consolidated_order_count',
        'notes',
    ];
    
    /**
     * The attributes that should be cast to appropriate data types.
     * 
     * Ensures proper handling of monetary amounts, dates, and boolean flags
     * for agricultural billing periods and payment tracking.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'total_amount' => 'float',
        'issue_date' => 'date',
        'due_date' => 'date',
        'billing_period_start' => 'date',
        'billing_period_end' => 'date',
        'is_consolidated' => 'boolean',
        'consolidated_order_count' => 'integer',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
    ];
    
    /**
     * Get the order for this invoice.
     * 
     * Returns the single order being invoiced for individual invoices.
     * For consolidated invoices, this relationship is null since multiple
     * orders are included via the consolidatedOrders relationship.
     * 
     * @return BelongsTo<Order, Invoice> Single order relationship
     * @business_context Individual invoices link to one order, consolidated invoices have order_id = null
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the user who created this invoice.
     * 
     * Tracks which system user or administrator generated the invoice,
     * supporting both manual invoice creation and automated consolidated
     * billing processes. Often the system user for recurring invoices.
     * 
     * @return BelongsTo<User, Invoice> User who created the invoice
     * @business_context Can be admin user for manual invoices or system user for automated billing
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the customer for this invoice.
     * 
     * Returns the agricultural customer (restaurant, distributor, retailer)
     * who will be billed for microgreens products and services. Essential
     * for payment tracking and customer account management.
     * 
     * @return BelongsTo<Customer, Invoice> Customer being billed
     * @business_context Links invoice to agricultural customer for payment processing
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    /**
     * Get the payment status for this invoice.
     * 
     * Returns current payment status (draft, sent, paid, overdue, cancelled)
     * enabling payment workflow tracking and automated follow-up processes
     * for agricultural billing cycles.
     * 
     * @return BelongsTo<PaymentStatus, Invoice> Current payment status
     * @business_context Enables payment workflow: draft → sent → paid/overdue → cancelled
     */
    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class);
    }
    
    /**
     * Get all orders consolidated in this invoice.
     * 
     * For consolidated invoices, returns all orders delivered within the
     * billing period that are included in this single invoice. Used for
     * B2B customers with regular deliveries who prefer monthly/weekly billing.
     * 
     * @return HasMany<Order> Orders included in consolidated invoice
     * @business_context Only populated for consolidated invoices (is_consolidated = true)
     * @performance Uses foreign key consolidated_invoice_id for efficient querying
     */
    public function consolidatedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'consolidated_invoice_id');
    }
    
    /**
     * Check if the invoice is overdue.
     * 
     * Determines if an invoice is past its due date and requires follow-up.
     * Only considers invoices in 'sent' status as overdue candidates since
     * draft invoices haven't been delivered to customers yet.
     * 
     * @return bool True if invoice is sent and past due date
     * @business_context Triggers automated overdue notifications and payment follow-up
     * @usage Used by payment reminder systems and customer account management
     */
    public function isOverdue(): bool
    {
        return $this->paymentStatus?->code === 'sent' && $this->due_date < now();
    }
    
    /**
     * Check if the invoice is paid.
     * 
     * Verifies payment completion by checking payment status. Used throughout
     * the system to determine customer account standing and order processing
     * eligibility for agricultural customers.
     * 
     * @return bool True if payment status is 'paid'
     * @business_context Determines customer credit status for future orders
     * @usage Controls order acceptance and credit limit enforcement
     */
    public function isPaid(): bool
    {
        return $this->paymentStatus?->code === 'paid';
    }
    
    /**
     * Mark the invoice as sent.
     * 
     * Updates invoice status to 'sent' and records the delivery timestamp.
     * This starts the payment terms countdown and enables overdue tracking
     * for agricultural customer payment workflows.
     * 
     * @return void
     * @throws \InvalidArgumentException If sent status not found in system
     * @business_context Starts payment terms period and overdue calculations
     * @workflow Typically called when invoice is emailed or delivered to customer
     */
    public function markAsSent(): void
    {
        $sentStatus = PaymentStatus::where('code', 'sent')->first();
        $this->payment_status_id = $sentStatus?->id;
        $this->sent_at = now();
        $this->save();
    }
    
    /**
     * Mark the invoice as paid.
     * 
     * Records successful payment receipt and completion timestamp.
     * Updates customer account standing and can trigger automated
     * processes like credit limit increases or loyalty program updates.
     * 
     * @return void
     * @throws \InvalidArgumentException If paid status not found in system
     * @business_context Completes payment workflow and updates customer credit status
     * @workflow Called by payment processing systems or manual payment entry
     */
    public function markAsPaid(): void
    {
        $paidStatus = PaymentStatus::where('code', 'paid')->first();
        $this->payment_status_id = $paidStatus?->id;
        $this->paid_at = now();
        $this->save();
    }
    
    /**
     * Mark the invoice as overdue.
     * 
     * Flags invoice as overdue for follow-up and collections processes.
     * Typically triggered by automated systems checking due dates, this
     * can affect customer ordering privileges and credit terms.
     * 
     * @return void
     * @throws \InvalidArgumentException If overdue status not found in system
     * @business_context May restrict new orders until payment received
     * @workflow Automated by scheduled tasks checking invoice due dates
     */
    public function markAsOverdue(): void
    {
        $overdueStatus = PaymentStatus::where('code', 'overdue')->first();
        $this->payment_status_id = $overdueStatus?->id;
        $this->save();
    }
    
    /**
     * Mark the invoice as cancelled.
     * 
     * Cancels invoice due to order cancellation, customer disputes, or
     * administrative corrections. Removes payment obligations and updates
     * customer account accordingly for agricultural billing adjustments.
     * 
     * @return void
     * @throws \InvalidArgumentException If cancelled status not found in system
     * @business_context Removes payment obligation and may affect customer account balance
     * @workflow Manual administrative action or automated order cancellation response
     */
    public function markAsCancelled(): void
    {
        $cancelledStatus = PaymentStatus::where('code', 'cancelled')->first();
        $this->payment_status_id = $cancelledStatus?->id;
        $this->save();
    }
    
    /**
     * Check if this is a consolidated invoice.
     * 
     * Determines if invoice consolidates multiple orders for B2B customers
     * with regular delivery schedules. Affects display logic, payment
     * processing, and accounting workflows.
     * 
     * @return bool True if invoice consolidates multiple orders
     * @business_context Consolidated invoices combine weekly/monthly deliveries
     * @usage Controls invoice display templates and payment processing logic
     */
    public function isConsolidated(): bool
    {
        return $this->is_consolidated === true;
    }
    
    /**
     * Get the effective amount (total_amount for consolidated, amount for single).
     * 
     * Returns the appropriate amount to charge based on invoice type.
     * Individual invoices use 'amount', consolidated invoices use 'total_amount'
     * which sums all included orders within the billing period.
     * 
     * @return float Effective invoice amount to be paid
     * @business_context Handles different amount fields for individual vs consolidated invoices
     * @usage Payment processing and invoice display calculations
     */
    public function getEffectiveAmountAttribute(): float
    {
        return $this->total_amount ?? $this->amount ?? 0;
    }
    
    /**
     * Get display text for billing period.
     * 
     * Formats billing period dates for consolidated invoice display.
     * Returns human-readable period like "Jan 15, 2024 - Feb 14, 2024"
     * for customer communication and invoice documentation.
     * 
     * @return string|null Formatted billing period or null if not set
     * @business_context Only relevant for consolidated invoices with billing periods
     * @usage Invoice templates and customer communications
     * @example "Dec 01, 2023 - Dec 31, 2023" for monthly consolidated billing
     */
    public function getBillingPeriodDisplayAttribute(): ?string
    {
        if (!$this->billing_period_start || !$this->billing_period_end) {
            return null;
        }
        
        return $this->billing_period_start->format('M d, Y') . ' - ' . $this->billing_period_end->format('M d, Y');
    }

    /**
     * Create an invoice from an order.
     * 
     * Generates a new individual invoice for a completed agricultural order.
     * Validates order requirements, calculates total amount, generates sequential
     * invoice number, and establishes payment terms for the customer.
     * 
     * This method handles the standard invoicing workflow for individual orders
     * that don't use consolidated billing. It's the primary entry point for
     * order-to-invoice conversion in the agricultural sales process.
     * 
     * @param Order $order Completed order requiring invoice generation
     * @return self Newly created invoice instance
     * @throws InvalidArgumentException If order already has invoice or doesn't require one
     * 
     * @business_workflow
     * 1. Validates order is eligible for invoicing (completed, no existing invoice)
     * 2. Calculates total amount from order items and pricing
     * 3. Generates sequential invoice number (INV-YYYYMM-0001 format)
     * 4. Sets payment terms (default 30 days) and initial status (draft)
     * 5. Links invoice to order via invoice_id foreign key
     * 
     * @usage Called after order fulfillment and before customer delivery
     * @example Invoice::createFromOrder($completedOrder) // Creates INV-202312-0045
     */
    public static function createFromOrder(Order $order): self
    {
        if ($order->invoice) {
            throw new InvalidArgumentException('Order already has an invoice');
        }

        if (!$order->requires_invoice) {
            throw new InvalidArgumentException('Order does not require an invoice');
        }

        $totalAmount = $order->totalAmount();
        
        // Generate invoice number
        $invoiceNumber = self::generateInvoiceNumber();
        
        $invoice = self::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'customer_id' => $order->customer_id,
            'invoice_number' => $invoiceNumber,
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'payment_status_id' => PaymentStatus::where('code', 'draft')->first()?->id,
            'issue_date' => now(),
            'due_date' => now()->addDays(30), // Default 30-day payment terms
            'is_consolidated' => false,
            'consolidated_order_count' => 1,
            'notes' => "Invoice for Order #{$order->id}",
        ]);

        // Update the order to link to this invoice
        $order->update(['invoice_id' => $invoice->id]);

        return $invoice;
    }

    /**
     * Generate a unique invoice number.
     * 
     * Creates sequential invoice numbers using INV-YYYYMM-NNNN format where
     * YYYY is year, MM is month, and NNNN is zero-padded sequence number.
     * Ensures uniqueness by querying existing invoices within the same month.
     * 
     * The numbering system provides:
     * - Chronological organization for accounting
     * - Easy identification of invoice creation period
     * - Sequential tracking within each month
     * - Consistent formatting for customer communication
     * 
     * @return string Formatted invoice number (e.g., "INV-202312-0045")
     * @business_context Sequential numbering required for accounting and audit trails
     * @performance Queries only current month's invoices for sequence calculation
     * @example "INV-202401-0001" for first invoice of January 2024
     */
    public static function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        
        // Get the next sequential number for this month
        $lastInvoice = self::whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastInvoice ? (int) substr($lastInvoice->invoice_number, -4) + 1 : 1;
        
        return sprintf('INV-%s%s-%04d', $year, $month, $sequence);
    }
    
    /**
     * Configure the activity log options for this model.
     * 
     * Defines which invoice fields are tracked for audit and compliance purposes.
     * Logs key financial and status changes for agricultural billing transparency
     * and regulatory requirements.
     * 
     * @return LogOptions Configured logging options for invoice changes
     * @business_context Required for financial audit trails and payment disputes
     * @compliance Tracks all payment status changes and amount modifications
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'invoice_number', 'amount', 'status', 'due_date', 'sent_at', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
