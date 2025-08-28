<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Customer Management Model for Catapult Agricultural Business System
 *
 * Represents customers in the microgreens agricultural business, managing both
 * individual retail customers and business wholesale relationships. Supports
 * complex pricing structures, multiple customer types, and agricultural business
 * workflows from direct sales to restaurant supply chains.
 *
 * @property int $id Primary key identifier
 * @property string $contact_name Primary contact person name
 * @property string|null $business_name Business or organization name (null for individuals)
 * @property string $email Primary email address for communication
 * @property string|null $cc_email Carbon copy email for business communications
 * @property string|null $phone Contact phone number
 * @property int|null $customer_type_id Customer classification (retail, wholesale, farmers_market)
 * @property float|null $wholesale_discount_percentage Individual wholesale discount rate
 * @property string|null $address Street address for delivery
 * @property string|null $city Delivery city
 * @property string|null $province Province/state for delivery
 * @property string|null $postal_code Postal/ZIP code
 * @property string|null $country Country for international delivery
 * @property int|null $user_id Associated user account for customer portal access
 *
 * @property-read string $display_name Formatted customer name for display
 * @property-read string|null $formatted_phone Formatted phone number display
 * @property-read float $effective_discount Current applicable discount percentage
 *
 * @relationship user BelongsTo User account for customer portal access
 * @relationship customerType BelongsTo Customer classification and pricing rules
 * @relationship orders HasMany Customer orders and agricultural delivery schedules
 * @relationship invoices HasMany Customer invoices and billing records
 *
 * @business_rule Customer types determine pricing structures and agricultural workflows
 * @business_rule Wholesale customers can have individual discount percentages
 * @business_rule Customer portal access is optional via user relationship
 * @business_rule Address information supports both local and international delivery
 *
 * @agricultural_context Customers drive the entire agricultural production cycle.
 * Retail customers typically order small quantities with immediate delivery,
 * wholesale customers (restaurants, retailers) order larger quantities with
 * predictable schedules, and farmers market customers represent direct sales
 * at agricultural markets and events.
 *
 * @usage_example
 * // Create retail customer
 * $customer = Customer::create([
 *     'contact_name' => 'John Smith',
 *     'email' => 'john@example.com',
 *     'customer_type_id' => CustomerType::findByCode('retail')->id
 * ]);
 *
 * // Create wholesale customer with discount
 * $restaurant = Customer::create([
 *     'contact_name' => 'Jane Manager',
 *     'business_name' => 'Fresh Garden Restaurant',
 *     'email' => 'orders@freshgarden.com',
 *     'customer_type_id' => CustomerType::findByCode('wholesale')->id,
 *     'wholesale_discount_percentage' => 15.00
 * ]);
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 1.0.0
 */
class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'wholesale_discount_percentage' => 'decimal:2',
    ];

    /**
     * Get the user account associated with this customer.
     *
     * Relationship to optional user account enabling customer portal access.
     * Allows customers to log in, view order history, manage delivery preferences,
     * and access agricultural product information and growing updates.
     *
     * @return BelongsTo<User> User account for customer portal access
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the customer type classification for this customer.
     *
     * Relationship to customer type that determines pricing structures,
     * agricultural workflows, and business processes. Types include retail
     * (individual consumers), wholesale (restaurants, retailers), and
     * farmers_market (direct agricultural market sales).
     *
     * @return BelongsTo<CustomerType> Customer classification and pricing rules
     */
    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    /**
     * Get the orders for this customer.
     *
     * Relationship to all customer orders including one-time purchases,
     * recurring agricultural subscriptions, and delivery schedules.
     * Essential for customer relationship management and agricultural
     * production planning based on customer demand patterns.
     *
     * @return HasMany<Order> Customer orders and agricultural delivery schedules
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the invoices for this customer.
     *
     * Relationship to customer billing records including individual order
     * invoices and consolidated wholesale billing. Supports agricultural
     * business financial management and customer payment tracking.
     *
     * @return HasMany<Invoice> Customer invoices and billing records
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if customer has a user account for login.
     *
     * Determines if customer has associated user account enabling customer
     * portal access for order history, delivery tracking, and agricultural
     * product information. Used for conditional UI display and access control.
     *
     * @return bool True if customer has user account
     */
    public function hasUserAccount(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get the customer's display name.
     *
     * Formatted name accessor for customer display in agricultural business
     * interfaces. Business customers show "Business Name (Contact Name)"
     * format, individual customers show contact name only.
     *
     * @return string Formatted customer display name
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
     *
     * Formats phone numbers in standard Canadian format (XXX) XXX-XXXX
     * for consistent display across agricultural business communications.
     * Handles 10-digit North American numbers, preserves other formats unchanged.
     *
     * @return string|null Formatted phone number or original format if non-standard
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
     *
     * Query scope for filtering to individual consumer customers who
     * typically purchase smaller quantities directly for personal use.
     * Used for targeted marketing and retail-specific agricultural workflows.
     *
     * @param Builder $query Query builder instance
     * @return Builder Filtered query for retail customers
     */
    public function scopeRetail(Builder $query): Builder
    {
        return $query->whereHas('customerType', function ($q) {
            $q->where('code', 'retail');
        });
    }

    /**
     * Scope a query to only include wholesale customers.
     *
     * Query scope for filtering to business customers (restaurants, retailers)
     * who purchase larger quantities with wholesale pricing and delivery schedules.
     * Essential for B2B agricultural relationship management.
     *
     * @param Builder $query Query builder instance
     * @return Builder Filtered query for wholesale customers
     */
    public function scopeWholesale(Builder $query): Builder
    {
        return $query->whereHas('customerType', function ($q) {
            $q->where('code', 'wholesale');
        });
    }

    /**
     * Scope a query to only include farmers market customers.
     *
     * Query scope for filtering to customers from direct agricultural market
     * sales. These customers typically purchase at farmers markets or
     * agricultural events with cash payments and minimal processing.
     *
     * @param Builder $query Query builder instance
     * @return Builder Filtered query for farmers market customers
     */
    public function scopeFarmersMarket(Builder $query): Builder
    {
        return $query->whereHas('customerType', function ($q) {
            $q->where('code', 'farmers_market');
        });
    }

    /**
     * Check if this customer is a wholesale customer.
     *
     * Determines if customer qualifies for wholesale business processes
     * including volume pricing, consolidated billing, and recurring
     * agricultural delivery schedules typical of restaurant relationships.
     *
     * @return bool True if customer is classified as wholesale
     */
    public function isWholesaleCustomer(): bool
    {
        return $this->customerType?->isWholesale() ?? false;
    }

    /**
     * Check if this customer is a farmers market customer.
     *
     * Determines if customer represents direct agricultural market sales
     * with simplified processing, cash payments, and minimal administrative
     * overhead typical of farmers market transactions.
     *
     * @return bool True if customer is classified as farmers market
     */
    public function isFarmersMarketCustomer(): bool
    {
        return $this->customerType?->isFarmersMarket() ?? false;
    }

    /**
     * Check if this customer is a retail customer.
     *
     * Determines if customer is individual consumer requiring retail pricing,
     * standard delivery processes, and consumer-focused agricultural
     * communications. Defaults to true if customer type not set.
     *
     * @return bool True if customer is classified as retail (or unclassified)
     */
    public function isRetailCustomer(): bool
    {
        return $this->customerType?->isRetail() ?? true;
    }

    /**
     * Check if this customer qualifies for wholesale pricing.
     *
     * Determines if customer is eligible for discounted wholesale rates
     * based on customer type classification. Used throughout agricultural
     * pricing calculations and order processing workflows.
     *
     * @return bool True if customer qualifies for wholesale discount pricing
     */
    public function qualifiesForWholesalePricing(): bool
    {
        return $this->customerType?->qualifiesForWholesalePricing() ?? false;
    }

    /**
     * Get the effective discount percentage for this customer.
     *
     * Calculates the actual discount percentage applicable to this customer
     * based on wholesale qualification status and individual discount rate.
     * Returns customer-specific wholesale discount or 0 for retail customers.
     *
     * Used throughout agricultural pricing calculations and order processing.
     *
     * @return float Effective discount percentage (0-100)
     */
    public function getEffectiveDiscountAttribute(): float
    {
        if ($this->qualifiesForWholesalePricing()) {
            return $this->wholesale_discount_percentage ?? 0;
        }
        return 0;
    }
}