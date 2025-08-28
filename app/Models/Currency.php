<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Multi-currency support model for agricultural market operations.
 * 
 * Provides currency management for agricultural businesses operating in multiple
 * markets, enabling proper pricing, financial reporting, and international
 * sales coordination across different currency regions.
 * 
 * @property int $id Primary key identifier
 * @property string $code ISO currency code (USD, EUR, CAD, etc.)
 * @property string $name Full currency name for display
 * @property string $symbol Currency symbol for formatting ($, €, £, etc.)
 * @property string|null $description Additional currency information
 * @property bool $is_active Currency availability for operational use
 * @property int $sort_order Display ordering for consistent UI presentation
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * @property-read string $display Formatted display string with code and name
 * 
 * @agricultural_context Enables multi-market agricultural sales and pricing
 * @business_rules Inactive currencies hidden from selection but preserved for historical data
 * @usage_pattern Used for pricing, financial reporting, and customer billing
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get active currencies for dropdown selection.
     * 
     * Returns formatted array of active currencies suitable for form dropdowns
     * and UI selection components, using currency codes as both keys and values.
     * 
     * @return array<string, string> Array with currency codes as keys and values
     * @agricultural_context Provides currency options for multi-market agricultural sales
     * @ui_usage Used in Filament forms for pricing and financial configuration
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('code', 'code')
            ->toArray();
    }

    /**
     * Find currency by ISO currency code.
     * 
     * Locates specific currency using standard ISO code for financial
     * operations and international market integration.
     * 
     * @param string $code ISO currency code (USD, EUR, CAD, etc.)
     * @return static|null Currency instance or null if not found
     * @agricultural_context Enables currency identification for multi-market operations
     * @usage_pattern Used for pricing calculations, financial reporting, and market integration
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Scope to get only active currencies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get formatted display string for UI presentation.
     * 
     * Returns currency code and name in user-friendly format for
     * display in forms, reports, and user interfaces.
     * 
     * @return string Formatted display string (e.g., "USD - US Dollar")
     * @agricultural_context Provides clear currency identification in multi-market operations
     * @ui_usage Used for currency selection displays and financial reports
     */
    public function getDisplayAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Format monetary amount with currency symbol and proper formatting.
     * 
     * Formats numerical amount with currency symbol and locale-appropriate
     * number formatting for financial display and reporting.
     * 
     * @param float $amount Monetary amount to format
     * @param int $decimals Number of decimal places (default 2)
     * @return string Formatted amount with currency symbol
     * @agricultural_context Used for pricing displays, invoices, and financial reports
     * @business_usage Ensures consistent currency formatting across agricultural operations
     */
    public function formatAmount(float $amount, int $decimals = 2): string
    {
        return $this->symbol . number_format($amount, $decimals);
    }
}