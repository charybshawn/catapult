<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'invoice_number',
        'amount',
        'status', // draft, sent, paid, overdue, cancelled
        'due_date',
        'sent_at',
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
        'due_date' => 'date',
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
