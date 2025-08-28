<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use App\Traits\Logging\ExtendedLogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

/**
 * User Authentication and Role Management Model for Catapult Agricultural System
 *
 * Represents system users including staff members, customer portal users, and
 * customer-only records for agricultural business operations. Supports role-based
 * access control, Filament admin panel access, customer portal functionality,
 * and agricultural business customer classifications.
 *
 * @property int $id Primary key identifier
 * @property string $name Full name of the user
 * @property string $email Email address (unique across system)
 * @property string|null $password Hashed password (null for customer-only records)
 * @property Carbon|null $email_verified_at Email verification timestamp
 * @property string|null $remember_token Authentication remember token
 * @property string|null $phone Contact phone number
 * @property int|null $customer_type_id Customer classification for agricultural business
 * @property float|null $wholesale_discount_percentage Individual wholesale discount rate
 * @property string|null $company_name Business name for B2B customers
 * @property string|null $address Street address
 * @property string|null $city City for delivery/contact
 * @property string|null $state State/province
 * @property string|null $zip Postal/ZIP code
 *
 * @relationship orders HasMany Orders created or managed by this user
 * @relationship timeCards HasMany Time tracking records for staff
 * @relationship customer HasOne Customer profile for portal users
 * @relationship customerType BelongsTo Customer classification and pricing rules
 *
 * @business_rule Users can be staff (admin, employee) or customers with portal access
 * @business_rule Customer-only records exist without passwords for order tracking
 * @business_rule Customer portal access requires password and appropriate permissions
 * @business_rule Filament admin panel restricted to staff roles with proper permissions
 * @business_rule Individual wholesale discounts override product defaults
 *
 * @agricultural_context Users manage the entire agricultural business workflow.
 * Staff users handle crop planning, production management, and order fulfillment.
 * Customer portal users track their agricultural orders, view growing progress,
 * and manage delivery preferences. Customer-only records enable order tracking
 * without requiring account creation.
 *
 * @usage_example
 * // Create staff user
 * $staff = User::create([
 *     'name' => 'Farm Manager',
 *     'email' => 'manager@farm.com',
 *     'password' => Hash::make('secure_password')
 * ]);
 * $staff->assignRole('employee');
 *
 * // Create customer with portal access
 * $customer = User::create([
 *     'name' => 'Restaurant Owner',
 *     'email' => 'orders@restaurant.com',
 *     'password' => Hash::make('customer_password'),
 *     'customer_type_id' => CustomerType::findByCode('wholesale')->id,
 *     'wholesale_discount_percentage' => 15.00
 * ]);
 * $customer->assignRole('customer');
 *
 * @package App\Models
 * @author Catapult Development Team
 * @version 2.0.0
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, ExtendedLogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'customer_type_id',
        'wholesale_discount_percentage',
        'company_name',
        'address',
        'city',
        'state',
        'zip',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'wholesale_discount_percentage' => 'decimal:2',
    ];

    /**
     * Get the orders created or managed by this user.
     *
     * Relationship to orders that this user has created (for staff) or
     * placed (for customer portal users). Essential for order tracking,
     * customer relationship management, and agricultural workflow coordination.
     *
     * @return HasMany<Order> Orders created or managed by this user
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the time tracking records for this user.
     *
     * Relationship to staff time cards for agricultural labor tracking,
     * payroll management, and operational cost analysis. Used for
     * staff productivity monitoring and agricultural project costing.
     *
     * @return HasMany<TimeCard> Time tracking records for staff
     */
    public function timeCards(): HasMany
    {
        return $this->hasMany(TimeCard::class);
    }

    /**
     * Get the customer profile associated with this user.
     *
     * Relationship to detailed customer profile for portal users who
     * require comprehensive agricultural customer management including
     * delivery addresses, billing preferences, and agricultural preferences.
     *
     * @return HasOne<Customer> Customer profile for portal users
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Get the customer type classification for this user.
     *
     * Relationship to customer type determining agricultural business
     * workflows, pricing structures, and delivery preferences.
     * Essential for applying appropriate agricultural business processes.
     *
     * @return BelongsTo<CustomerType> Customer classification and pricing rules
     */
    public function customerType(): BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    /**
     * Configure the activity log options for this model.
     *
     * Defines user activity logging for security audit trails and
     * agricultural business compliance. Tracks changes to core user
     * information while protecting sensitive authentication data.
     *
     * @return LogOptions Configured activity logging options
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Determine if the user can access the Filament admin panel.
     *
     * Implements agricultural business access control with strict role-based
     * restrictions. Only staff members (admin, employee) can access the
     * agricultural management system. Customer portal users are restricted
     * to their own separate interface.
     *
     * Requirements:
     * - Must have password (excludes customer-only records)
     * - Must not be customer-only role
     * - Must have 'access filament' permission
     *
     * @param Panel $panel Filament panel instance
     * @return bool True if user can access admin panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Must have a password to access any panel
        if (!$this->password) {
            return false;
        }
        
        // Only non-customer roles can access the admin panel
        if ($this->hasRole('customer') && !$this->hasAnyRole(['admin', 'employee'])) {
            return false;
        }
        
        return $this->hasPermissionTo('access filament');
    }

    /**
     * Check if this user can login (has password and appropriate permissions).
     *
     * Determines if user has authentication credentials for system access.
     * Customer-only records without passwords cannot log in but can still
     * be used for order tracking and agricultural business relationships.
     *
     * @return bool True if user has password for authentication
     */
    public function canLogin(): bool
    {
        return !is_null($this->password);
    }

    /**
     * Check if this is a customer-only account (no login access).
     *
     * Determines if user record exists solely for agricultural business
     * tracking without authentication capabilities. These records enable
     * order management and customer communication without requiring
     * customer portal registration.
     *
     * @return bool True if customer role with no password
     */
    public function isCustomerOnly(): bool
    {
        return $this->hasRole('customer') && is_null($this->password);
    }

    /**
     * Upgrade a customer-only account to have login access.
     *
     * Converts customer-only record to full customer portal user when
     * existing agricultural business customers decide to create portal
     * accounts. Maintains order history and customer relationships.
     *
     * Used when customers sign up with existing email addresses from
     * previous agricultural orders or business relationships.
     *
     * @param string $password Plain text password to hash and store
     * @return bool True if upgrade successful
     */
    public function enableLogin(string $password): bool
    {
        if ($this->password) {
            // Already has login access
            return false;
        }

        $this->password = Hash::make($password);
        $this->email_verified_at = now(); // Mark as verified since they're actively registering
        
        return $this->save();
    }

    /**
     * Find an existing customer record by email for potential upgrade.
     *
     * Searches for customer-only records that could be upgraded to
     * full portal access when customers register. Maintains agricultural
     * business continuity by linking new portal accounts to existing
     * order history and customer relationships.
     *
     * @param string $email Email address to search for
     * @return User|null Existing customer record or null if not found
     */
    public static function findCustomerByEmail(string $email): ?self
    {
        return static::where('email', $email)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'customer');
            })
            ->first();
    }

    /**
     * Get the effective wholesale discount percentage for this user.
     *
     * Calculates applicable wholesale discount with hierarchical logic:
     * 1. User-specific wholesale discount percentage (individual rates)
     * 2. Product default wholesale discount (if user qualifies for wholesale)
     * 3. Zero discount (retail pricing)
     *
     * Essential for agricultural business pricing calculations across
     * different customer relationships and volume commitments.
     *
     * @param Product|null $product Product for default discount fallback
     * @return float Discount percentage (0-100), capped at 100%
     */
    public function getWholesaleDiscountPercentage(?Product $product = null): float
    {
        // If user has a specific wholesale discount, use it (capped at 100%)
        if ($this->wholesale_discount_percentage !== null && $this->wholesale_discount_percentage > 0) {
            return min($this->wholesale_discount_percentage, 100);
        }
        
        // If user is wholesale or farmers market type but no specific discount, use product default (capped at 100%)
        if ($this->customerType?->qualifiesForWholesalePricing() && $product && $product->wholesale_discount_percentage > 0) {
            return min($product->wholesale_discount_percentage, 100);
        }
        
        return 0;
    }

    /**
     * Check if this user should receive wholesale pricing.
     *
     * Determines wholesale pricing eligibility based on customer type
     * classification or individual discount percentage. Used throughout
     * agricultural pricing calculations and order processing workflows.
     *
     * @return bool True if user qualifies for wholesale pricing
     */
    public function isWholesaleCustomer(): bool
    {
        return $this->customerType?->isWholesale() || 
               ($this->wholesale_discount_percentage !== null && $this->wholesale_discount_percentage > 0);
    }

    /**
     * Check if this user is a farmers market customer.
     *
     * Determines if user represents direct agricultural market sales
     * requiring simplified processing, cash payment workflows, and
     * minimal administrative overhead typical of farmers market operations.
     *
     * @return bool True if user is classified as farmers market
     */
    public function isFarmersMarketCustomer(): bool
    {
        return $this->customerType?->isFarmersMarket() ?? false;
    }

    /**
     * Check if this user is a retail customer.
     *
     * Determines if user requires retail agricultural customer workflows
     * including standard pricing, individual order processing, and
     * consumer-focused communications. Defaults to true if unclassified.
     *
     * @return bool True if user is classified as retail (or unclassified)
     */
    public function isRetailCustomer(): bool
    {
        return $this->customerType?->isRetail() ?? true;
    }

    /**
     * Check if this user should receive discounted pricing (wholesale or farmers market).
     *
     * Determines if user qualifies for any form of agricultural business
     * discount pricing based on customer type classification. Used for
     * pricing display and order processing decision logic.
     *
     * @return bool True if user qualifies for discount pricing
     */
    public function receivesDiscountPricing(): bool
    {
        return $this->customerType?->qualifiesForWholesalePricing() ?? false;
    }

}
