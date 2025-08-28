<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Agricultural product category management model for organizing microgreens and related products.
 * 
 * Categories provide hierarchical organization of agricultural products, enabling efficient
 * product discovery, reporting, and inventory management across different product lines.
 * 
 * @property int $id Primary key identifier
 * @property string $name Category name for agricultural product grouping
 * @property string|null $description Detailed category description for clarification
 * @property bool $is_active Category availability status for product organization
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * 
 * @agricultural_context Used for organizing microgreens varieties, seed mixes, and related products
 * @business_rules Categories can be deactivated but not deleted if they contain products
 * @usage_pattern Commonly used in product filtering, reporting, and inventory organization
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class Category extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all agricultural products associated with this category.
     * 
     * Retrieves microgreens, seed mixes, and other agricultural products organized
     * under this category for inventory management and product discovery.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Product>
     * @agricultural_context Returns products like microgreens varieties, seed mixes, packaging products
     * @business_usage Used for product filtering, category reporting, and inventory organization
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the items for the category.
     * 
     * @deprecated Use products() instead - legacy method maintained for backward compatibility
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Product>
     */
    public function items(): HasMany
    {
        return $this->products();
    }

    /**
     * Configure the activity log options for this model.
     * 
     * Defines which attributes should be logged when categories are created, updated,
     * or modified, providing audit trail for agricultural product organization changes.
     * 
     * @return \Spatie\Activitylog\LogOptions Activity logging configuration
     * @audit_trail Tracks changes to category name, description, and active status
     * @business_importance Critical for maintaining product organization audit trail
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'description',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
