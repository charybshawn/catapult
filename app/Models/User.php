<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, LogsActivity;

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
        'customer_type',
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
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasPermissionTo('access filament');
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
        
        // If user is wholesale type but no specific discount, use product default (capped at 100%)
        if ($this->customer_type === 'wholesale' && $product && $product->wholesale_discount_percentage > 0) {
            return min($product->wholesale_discount_percentage, 100);
        }
        
        return 0;
    }

    /**
     * Check if this user should receive wholesale pricing.
     */
    public function isWholesaleCustomer(): bool
    {
        return $this->customer_type === 'wholesale' || 
               ($this->wholesale_discount_percentage !== null && $this->wholesale_discount_percentage > 0);
    }

}
