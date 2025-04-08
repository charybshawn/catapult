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
        'method',
        'status',
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
     * Check if the payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
    
    /**
     * Check if the payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
    
    /**
     * Check if the payment has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
    
    /**
     * Check if the payment has been refunded.
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }
    
    /**
     * Mark the payment as completed.
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->paid_at = now();
        $this->save();
    }
    
    /**
     * Mark the payment as failed.
     */
    public function markAsFailed(): void
    {
        $this->status = 'failed';
        $this->save();
    }
    
    /**
     * Mark the payment as refunded.
     */
    public function markAsRefunded(): void
    {
        $this->status = 'refunded';
        $this->save();
    }
    
    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'amount', 'method', 'status', 'transaction_id', 'paid_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
