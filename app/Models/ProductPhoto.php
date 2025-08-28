<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents product imagery for agricultural microgreens products, supporting
 * visual marketing, product identification, and customer education. Manages
 * photo ordering, default selections, and agricultural product presentation.
 *
 * @business_domain Agricultural Product Marketing & Visual Content
 * @workflow_context Used in product management, marketing, and customer interfaces
 * @agricultural_process Provides visual representation of microgreens varieties
 *
 * Database Table: product_photos
 * @property int $id Primary identifier for product photo
 * @property int $product_id Reference to agricultural product
 * @property string $photo Photo file path or URL
 * @property bool $is_default Whether this is the primary product photo
 * @property int|null $order Display order for multiple product photos
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship product BelongsTo relationship to Product for agricultural context
 *
 * @business_rule Only one photo can be set as default per product
 * @business_rule Photos are ordered for consistent product presentation
 * @business_rule Default photo is used in product listings and quick views
 *
 * @agricultural_context Visual representation helps customers identify varieties
 * @marketing_usage Professional photos support premium agricultural product sales
 */
class ProductPhoto extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'product_photos';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'photo',
        'is_default',
        'order',
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];
    
    /**
     * Get the agricultural product that owns this photo.
     * Links photo to specific microgreens or agricultural product.
     *
     * @return BelongsTo Product relationship
     * @agricultural_context Connects photo to specific variety or product mix
     * @business_usage Used for product photo management and display workflows
     * @visual_context Enables variety-specific imagery for customer identification
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    /**
     * Set this photo as the primary default photo for the product.
     * Ensures only one default photo exists per agricultural product.
     *
     * @return void
     * @business_rule Only one photo can be default per product
     * @agricultural_usage Default photo represents product in listings and catalogs
     * @workflow_impact Automatically clears other default photos for consistency
     * @database_transaction Atomically updates default status across related photos
     */
    public function setAsDefault(): void
    {
        if (!$this->is_default) {
            // Clear any existing default photos
            static::where('product_id', $this->product_id)
                ->where('id', '!=', $this->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
            
            // Set this photo as default
            $this->is_default = true;
            $this->save();
        }
    }
    
    /**
     * Configure activity logging for product photo changes.
     * Tracks modifications to agricultural product visual content.
     *
     * @return LogOptions Activity logging configuration
     * @audit_purpose Maintains history of product photo changes for marketing tracking
     * @logged_fields Tracks product association, photo path, default status, and ordering
     * @business_usage Used for visual content management and change auditing
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'photo', 'is_default', 'order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
} 