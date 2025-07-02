<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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
     * Get the order for this payment.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the payment method for this payment.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the payment status for this payment.
     */
    public function paymentStatus(): BelongsTo
    {
        return $this->belongsTo(PaymentStatus::class, 'status_id');
    }
    
    /**
     * Check if the payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->paymentStatus?->isCompleted() ?? false;
    }
    
    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool
    {
        return $this->paymentStatus?->isPending() ?? false;
    }
    
    /**
     * Check if the payment has failed.
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
     * Mark the payment as completed.
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
