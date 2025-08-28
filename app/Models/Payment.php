<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Payment Management for Agricultural Product Sales
 *
 * Represents individual payment transactions for agricultural microgreens orders,
 * tracking payment methods, amounts, statuses, and processing details. Essential
 * for financial management and revenue tracking in agricultural business operations.
 *
 * @property int $id Primary key identifier
 * @property int $order_id Associated order being paid for
 * @property float $amount Payment amount in base currency
 * @property int $payment_method_id Payment processing method used
 * @property int $status_id Current payment status
 * @property string|null $transaction_id External payment processor transaction ID
 * @property \DateTime|null $paid_at Timestamp when payment was completed
 * @property string|null $notes Additional payment notes or processing details
 *
 * @relationship order BelongsTo Order being paid for
 * @relationship paymentMethod BelongsTo Payment processing method and configuration
 * @relationship paymentStatus BelongsTo Current payment status and workflow state
 *
 * @business_rule Payment amounts must match order totals for complete payment
 * @business_rule Transaction IDs required for online payment processing
 * @business_rule Status transitions follow payment workflow validation
 * @business_rule Activity logging tracks all payment modifications for auditing
 *
 * @agricultural_context Payments fund agricultural production and operations:
 * - Completed payments trigger agricultural production planning and resource allocation
 * - Payment timing affects agricultural workflow scheduling and delivery planning
 * - Payment methods determine processing workflows for different customer types
 * - Revenue tracking enables agricultural business profitability analysis
 *
 * Each payment directly impacts agricultural production cash flow and resource
 * allocation for microgreens cultivation and customer fulfillment.
 *
 * @usage_example
 * // Create payment for order
 * $payment = Payment::create([
 *     'order_id' => $order->id,
 *     'amount' => 45.99,
 *     'payment_method_id' => PaymentMethod::findByCode('stripe')->id,
 *     'status_id' => PaymentStatus::findByCode('pending')->id,
 *     'transaction_id' => 'pi_stripe_transaction_id'
 * ]);
 *
 * // Process payment completion
 * if ($payment->isStripePayment()) {
 *     $payment->markAsCompleted();
 * }
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class Payment extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'amount',
        'payment_method_id',
        'status_id',
        'transaction_id',
        'paid_at',
        'notes',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];
    
    /**
     * Get the agricultural order being paid for.
     *
     * Relationship to the order that this payment settles. Essential for
     * linking agricultural production planning to financial transactions
     * and enabling cash flow management for microgreens operations.
     *
     * @return BelongsTo<Order> Order being paid for
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the payment processing method and configuration.
     *
     * Relationship to payment method that defines processing requirements,
     * fees, and workflow for this transaction. Used for agricultural
     * business payment processing and customer payment options.
     *
     * @return BelongsTo<PaymentMethod> Payment processing method
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the current payment status and workflow state.
     *
     * Relationship to payment status that controls workflow progression
     * and business rule enforcement. Essential for agricultural business
     * financial operations and payment processing automation.
     *
     * @return BelongsTo<PaymentStatus> Current payment status
     */
    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class, 'status_id');
    }
    
    /**
     * Check if payment has been successfully completed.
     *
     * Determines if payment has been processed and funds received,
     * enabling agricultural production to proceed and resources to
     * be allocated for microgreens cultivation and delivery.
     *
     * @return bool True if payment is completed
     */
    public function isCompleted(): bool
    {
        return $this->paymentStatus?->isCompleted() ?? false;
    }
    
    /**
     * Check if payment is pending processing.
     *
     * Determines if payment is awaiting processing, requiring agricultural
     * production to wait for payment confirmation before proceeding with
     * resource allocation and cultivation scheduling.
     *
     * @return bool True if payment is pending
     */
    public function isPending(): bool
    {
        return $this->paymentStatus?->isPending() ?? false;
    }
    
    /**
     * Check if payment processing has failed.
     *
     * Determines if payment has failed, requiring agricultural production
     * to be paused and customer notification for alternative payment
     * arrangement before proceeding with cultivation.
     *
     * @return bool True if payment has failed
     */
    public function hasFailed(): bool
    {
        return $this->paymentStatus?->isFailed() ?? false;
    }
    
    /**
     * Check if the payment has been refunded.
     */
    public function isRefunded(): bool
    {
        return $this->paymentStatus?->isRefunded() ?? false;
    }

    /**
     * Check if this payment uses Stripe.
     */
    public function isStripePayment(): bool
    {
        return $this->paymentMethod?->isStripe() ?? false;
    }

    /**
     * Check if this payment requires online processing.
     */
    public function requiresOnlineProcessing(): bool
    {
        return $this->paymentMethod?->requiresOnlineProcessing() ?? false;
    }
    
    /**
     * Mark payment as completed and trigger agricultural production.
     *
     * Updates payment status to completed and records payment timestamp,
     * enabling agricultural production planning and resource allocation
     * for microgreens cultivation to proceed.
     *
     * @return void
     */
    public function markAsCompleted(): void
    {
        $this->status_id = PaymentStatus::findByCode('completed')?->id;
        $this->paid_at = now();
        $this->save();
    }
    
    /**
     * Mark the payment as failed.
     */
    public function markAsFailed(): void
    {
        $this->status_id = PaymentStatus::findByCode('failed')?->id;
        $this->save();
    }
    
    /**
     * Mark the payment as refunded.
     */
    public function markAsRefunded(): void
    {
        $this->status_id = PaymentStatus::findByCode('refunded')?->id;
        $this->save();
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'amount', 'payment_method_id', 'status_id', 'transaction_id', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
