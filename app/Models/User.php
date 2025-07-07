<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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
     * Get the orders for this user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the time cards for this user.
     */
    public function timeCards(): HasMany
    {
        return $this->hasMany(TimeCard::class);
    }

    /**
     * Get the customer profile associated with this user.
     */
    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class);
    }

    /**
     * Get the customer type for this user.
     */
    public function customerType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CustomerType::class);
    }

    /**
     * Configure the activity log options for this model.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'phone'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Determine if the user can access the Filament panel.
     * Only users with admin/employee roles can access the admin panel.
     * Customers with passwords access a separate customer portal (not implemented yet).
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
     * Check if this user can login (has password and appropriate permissions)
     */
    public function canLogin(): bool
    {
        return !is_null($this->password);
    }

    /**
     * Check if this is a customer-only account (no login access)
     */
    public function isCustomerOnly(): bool
    {
        return $this->hasRole('customer') && is_null($this->password);
    }

    /**
     * Upgrade a customer-only account to have login access
     * This would be used when a customer signs up with an existing email
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
     * Find an existing customer record by email for potential upgrade
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
     * Prioritizes user-specific discount, then falls back to product default.
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
     */
    public function isWholesaleCustomer(): bool
    {
        return $this->customerType?->isWholesale() || 
               ($this->wholesale_discount_percentage !== null && $this->wholesale_discount_percentage > 0);
    }

    /**
     * Check if this user is a farmers market customer.
     */
    public function isFarmersMarketCustomer(): bool
    {
        return $this->customerType?->isFarmersMarket() ?? false;
    }

    /**
     * Check if this user is a retail customer.
     */
    public function isRetailCustomer(): bool
    {
        return $this->customerType?->isRetail() ?? true;
    }

    /**
     * Check if this user should receive discounted pricing (wholesale or farmers market).
     */
    public function receivesDiscountPricing(): bool
    {
        return $this->customerType?->qualifiesForWholesalePricing() ?? false;
    }

}
