<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_name',
        'business_name',
        'email',
        'cc_email',
        'phone',
        'customer_type_id',
        'wholesale_discount_percentage',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'user_id',
    ];

    protected $casts = [
        'wholesale_discount_percentage' => 'decimal:2',
    ];

    /**
     * Get the user account associated with this customer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer type for this customer.
     */
    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    /**
     * Get the orders for this customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the invoices for this customer.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if customer has a user account for login.
     */
    public function hasUserAccount(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get the customer's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->business_name) {
            return $this->business_name . ' (' . $this->contact_name . ')';
        }
        return $this->contact_name;
    }
    
    /**
     * Format phone number for display (Canadian format).
     */
    public function getFormattedPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }
        
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        
        // Format as (xxx) xxx-xxxx
        if (strlen($phone) === 10) {
            return sprintf('(%s) %s-%s', 
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6)
            );
        }
        
        return $this->phone;
    }

    /**
     * Scope a query to only include retail customers.
     */
    public function scopeRetail(Builder $query): Builder
    {
        return $query->whereHas('customerType', function ($q) {
            $q->where('code', 'retail');
        });
    }

    /**
     * Scope a query to only include wholesale customers.
     */
    public function scopeWholesale(Builder $query): Builder
    {
        return $query->whereHas('customerType', function ($q) {
            $q->where('code', 'wholesale');
        });
    }

    /**
     * Scope a query to only include farmers market customers.
     */
    public function scopeFarmersMarket(Builder $query): Builder
    {
        return $query->whereHas('customerType', function ($q) {
            $q->where('code', 'farmers_market');
        });
    }

    /**
     * Check if this customer is a wholesale customer.
     */
    public function isWholesaleCustomer(): bool
    {
        return $this->customerType?->isWholesale() ?? false;
    }

    /**
     * Check if this customer is a farmers market customer.
     */
    public function isFarmersMarketCustomer(): bool
    {
        return $this->customerType?->isFarmersMarket() ?? false;
    }

    /**
     * Check if this customer is a retail customer.
     */
    public function isRetailCustomer(): bool
    {
        return $this->customerType?->isRetail() ?? true;
    }

    /**
     * Check if this customer qualifies for wholesale pricing.
     */
    public function qualifiesForWholesalePricing(): bool
    {
        return $this->customerType?->qualifiesForWholesalePricing() ?? false;
    }

    /**
     * Get the effective discount percentage for this customer.
     */
    public function getEffectiveDiscountAttribute(): float
    {
        if ($this->qualifiesForWholesalePricing()) {
            return $this->wholesale_discount_percentage ?? 0;
        }
        return 0;
    }
}