<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agricultural product delivery status tracking model for order fulfillment management.
 * 
 * Manages delivery status progression from order placement through final delivery
 * for agricultural products, enabling customer communication and logistics coordination.
 * 
 * @property int $id Primary key identifier
 * @property string $code Unique status code for programmatic identification (pending, scheduled, in_transit, delivered, failed)
 * @property string $name Human-readable status name for display
 * @property string|null $description Detailed status description and context
 * @property string|null $color UI color code for visual status identification
 * @property bool $is_active Status availability for operational use
 * @property int $sort_order Status progression ordering for workflow sequence
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * 
 * @agricultural_context Tracks fresh product delivery timing for quality assurance
 * @business_rules Status progression follows delivery workflow sequence
 * @customer_communication Used for delivery notifications and tracking updates
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class DeliveryStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get all orders with this delivery status.
     * 
     * Retrieves agricultural product orders that are currently at this
     * delivery status for logistics management and customer tracking.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Order>
     * @agricultural_context Returns orders requiring delivery status-specific handling
     * @business_usage Used for delivery coordination and customer communication
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get active delivery statuses for dropdown selection.
     * 
     * Returns formatted array of active statuses suitable for form dropdowns
     * and UI selection components, ordered by delivery workflow sequence.
     * 
     * @return array<int, string> Array with status IDs as keys and names as values
     * @agricultural_context Provides delivery status options for order management
     * @ui_usage Used in Filament forms for delivery status updates
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
     * Find delivery status by unique code identifier.
     * 
     * Locates specific status using programmatic code for workflow automation
     * and consistent status identification across delivery operations.
     * 
     * @param string $code Unique status code (pending, scheduled, in_transit, delivered, failed)
     * @return static|null Status instance or null if not found
     * @agricultural_context Enables programmatic status identification for delivery tracking
     * @usage_pattern Used for status transitions, automated notifications, and integrations
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Check if delivery is pending scheduling.
     * 
     * @return bool True if delivery is pending
     * @agricultural_context Order ready but delivery not yet scheduled
     */
    public function isPending(): bool { return $this->code === 'pending'; }
    
    /**
     * Check if delivery is scheduled for future date.
     * 
     * @return bool True if delivery is scheduled
     * @agricultural_context Delivery date/time confirmed and scheduled
     */
    public function isScheduled(): bool { return $this->code === 'scheduled'; }
    
    /**
     * Check if delivery is currently in transit.
     * 
     * @return bool True if delivery is in transit
     * @agricultural_context Fresh products en route to customer
     */
    public function isInTransit(): bool { return $this->code === 'in_transit'; }
    
    /**
     * Check if delivery has been completed.
     * 
     * @return bool True if delivery is completed
     * @agricultural_context Products successfully delivered to customer
     */
    public function isDelivered(): bool { return $this->code === 'delivered'; }
    
    /**
     * Check if delivery attempt failed.
     * 
     * @return bool True if delivery failed
     * @agricultural_context Delivery unsuccessful, requires rescheduling or resolution
     */
    public function isFailed(): bool { return $this->code === 'failed'; }
}