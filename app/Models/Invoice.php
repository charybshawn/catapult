<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Invoice extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
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
        'status', // draft, sent, paid, overdue, cancelled, pending
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
     * The attributes that should be cast.
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
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
    
    /**
     * Get the user (customer) for this invoice.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the customer for this invoice.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    
    /**
     * Get all orders consolidated in this invoice.
     */
    public function consolidatedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'consolidated_invoice_id');
    }
    
    /**
     * Check if the invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'sent' && $this->due_date < now();
    }
    
    /**
     * Check if the invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
    
    /**
     * Mark the invoice as sent.
     */
    public function markAsSent(): void
    {
        $this->status = 'sent';
        $this->sent_at = now();
        $this->save();
    }
    
    /**
     * Mark the invoice as paid.
     */
    public function markAsPaid(): void
    {
        $this->status = 'paid';
        $this->paid_at = now();
        $this->save();
    }
    
    /**
     * Mark the invoice as overdue.
     */
    public function markAsOverdue(): void
    {
        $this->status = 'overdue';
        $this->save();
    }
    
    /**
     * Mark the invoice as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->status = 'cancelled';
        $this->save();
    }
    
    /**
     * Check if this is a consolidated invoice.
     */
    public function isConsolidated(): bool
    {
        return $this->is_consolidated === true;
    }
    
    /**
     * Get the effective amount (total_amount for consolidated, amount for single).
     */
    public function getEffectiveAmountAttribute(): float
    {
        return $this->total_amount ?? $this->amount ?? 0;
    }
    
    /**
     * Get display text for billing period.
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
     */
    public static function createFromOrder(Order $order): self
    {
        if ($order->invoice) {
            throw new \InvalidArgumentException('Order already has an invoice');
        }

        if (!$order->requires_invoice) {
            throw new \InvalidArgumentException('Order does not require an invoice');
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
            'status' => 'draft',
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
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'invoice_number', 'amount', 'status', 'due_date', 'sent_at', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
