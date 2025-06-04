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
        'company_name',
        'address',
        'city',
        'state',
        'zip',
        'preferences',
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
        'preferences' => 'array',
    ];

    /**
     * Get the orders for this user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
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
     * Get navigation preferences for the user.
     */
    public function getNavigationPreferences(): array
    {
        return $this->preferences['navigation'] ?? [];
    }

    /**
     * Update navigation preferences for the user.
     */
    public function updateNavigationPreferences(array $navigationPrefs): void
    {
        $preferences = $this->preferences ?? [];
        $preferences['navigation'] = $navigationPrefs;
        $this->update(['preferences' => $preferences]);
    }

    /**
     * Check if a navigation group is collapsed.
     */
    public function isNavigationGroupCollapsed(string $group): bool
    {
        $navPrefs = $this->getNavigationPreferences();
        return $navPrefs['collapsed_groups'][$group] ?? false;
    }

    /**
     * Set navigation group collapsed state.
     */
    public function setNavigationGroupCollapsed(string $group, bool $collapsed): void
    {
        $navPrefs = $this->getNavigationPreferences();
        $navPrefs['collapsed_groups'][$group] = $collapsed;
        $this->updateNavigationPreferences($navPrefs);
    }
}
