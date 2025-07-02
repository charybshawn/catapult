<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'requires_processing',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_processing' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the payments for this payment method.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get options for select fields (active methods only).
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Get all active payment methods.
     */
    public static function active()
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Find payment method by code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if this is Stripe payment method.
     */
    public function isStripe(): bool
    {
        return $this->code === 'stripe';
    }

    /**
     * Check if this is e-transfer payment method.
     */
    public function isETransfer(): bool
    {
        return $this->code === 'e-transfer';
    }

    /**
     * Check if this is cash payment method.
     */
    public function isCash(): bool
    {
        return $this->code === 'cash';
    }

    /**
     * Check if this is invoice payment method.
     */
    public function isInvoice(): bool
    {
        return $this->code === 'invoice';
    }

    /**
     * Check if this payment method requires online processing.
     */
    public function requiresOnlineProcessing(): bool
    {
        return $this->requires_processing;
    }

    /**
     * Check if this is an offline payment method.
     */
    public function isOfflineMethod(): bool
    {
        return !$this->requires_processing;
    }
}