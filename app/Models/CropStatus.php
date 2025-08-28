<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Agricultural crop lifecycle status tracking model for production management.
 * 
 * Manages crop lifecycle status beyond growth stages, providing additional
 * status tracking for quality control, issue management, and production workflow
 * coordination in agricultural operations.
 * 
 * @property int $id Primary key identifier
 * @property string $code Unique status code for programmatic identification
 * @property string $name Human-readable status name for display
 * @property string|null $description Detailed status description and context
 * @property string|null $color UI color code for visual status identification
 * @property bool $is_active Status availability for operational use
 * @property int $sort_order Display ordering for consistent UI presentation
 * @property bool $is_final Whether this status represents completion/termination
 * @property bool $allows_modifications Whether crops in this status can be modified
 * @property \Illuminate\Support\Carbon $created_at Creation timestamp
 * @property \Illuminate\Support\Carbon $updated_at Last update timestamp
 * 
 * @agricultural_context Tracks crop conditions beyond standard growth stages (healthy, damaged, etc.)
 * @business_rules Final statuses prevent further modifications, inactive statuses hidden from UI
 * @workflow_integration Works alongside crop stages for comprehensive status management
 * 
 * @package App\Models
 * @author Catapult Development Team
 * @since 1.0.0
 */
class CropStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'color',
        'is_active',
        'sort_order',
        'is_final',
        'allows_modifications',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_final' => 'boolean',
        'allows_modifications' => 'boolean',
    ];

    /**
     * Get active crop statuses for dropdown selection.
     * 
     * Returns formatted array of active statuses suitable for form dropdowns
     * and UI selection components, ordered by sort priority.
     * 
     * @return array<int, string> Array with status IDs as keys and names as values
     * @agricultural_context Provides status options for crop quality and condition tracking
     * @ui_usage Used in Filament forms for crop status selection
     */
    public static function options(): array
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Find crop status by unique code identifier.
     * 
     * Locates specific status using programmatic code for system integration
     * and consistent status identification across agricultural operations.
     * 
     * @param string $code Unique status code identifier
     * @return static|null Status instance or null if not found
     * @agricultural_context Enables programmatic status identification for workflow automation
     * @usage_pattern Used for status assignment, workflow automation, and system integrations
     */
    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Scope to get only active statuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only final statuses.
     */
    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    /**
     * Check if crops in this status allow modifications.
     * 
     * Determines if crops with this status can be edited, moved, or modified
     * in agricultural operations and management workflows.
     * 
     * @return bool True if modifications are allowed
     * @agricultural_context Prevents changes to crops in final or locked states
     * @business_logic Used for UI control and operation validation
     */
    public function allowsModifications(): bool
    {
        return $this->allows_modifications;
    }

    /**
     * Check if this is a final status in the lifecycle.
     * 
     * Determines if this status represents completion, termination, or
     * end state for crop production workflow.
     * 
     * @return bool True if this is final status
     * @agricultural_context Final statuses indicate completed or terminated production
     * @business_logic Used for workflow completion and reporting
     */
    public function isFinal(): bool
    {
        return $this->is_final;
    }
}