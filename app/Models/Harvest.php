<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents a simplified agricultural harvest record for microgreens production, tracking
 * yield data per cultivar with streamlined data collection. This model is central
 * to production analytics and supports agricultural planning and optimization.
 *
 * @business_domain Agricultural Production & Yield Management
 * @workflow_context Used in production tracking, harvest scheduling, and yield analytics
 * @agricultural_process Records actual harvest results for cultivars to analyze production efficiency
 *
 * Database Table: harvests
 * @property int $id Primary identifier for harvest record
 * @property int $master_cultivar_id Reference to cultivar being harvested
 * @property int $user_id Worker/user who performed the harvest
 * @property float $total_weight_grams Total yield weight in grams for this cultivar harvest
 * @property Carbon $harvest_date Date when harvest was completed
 * @property string|null $notes Harvest notes and observations
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @relationship masterCultivar BelongsTo relationship to MasterCultivar for variety data
 * @relationship user BelongsTo relationship to User who performed harvest
 *
 * @business_rule Each harvest record represents one cultivar-weight-date combination
 * @business_rule Multiple records per session enable multi-cultivar harvest tracking
 * @business_rule Weekly harvest periods run Wednesday to Tuesday for consistent reporting
 * @business_rule Cumulative harvests achieved through multiple records with same cultivar/date
 *
 * @agricultural_metric Total weight per cultivar tracks production volume for order fulfillment
 * @agricultural_metric Harvest dates enable production scheduling and planning
 * @agricultural_metric Cultivar-specific yields support variety performance analysis
 */
class Harvest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'master_cultivar_id',
        'user_id',
        'total_weight_grams',
        'harvest_date',
        'notes',
    ];

    protected $casts = [
        'total_weight_grams' => 'decimal:2',
        'harvest_date' => 'date',
    ];

    /**
     * Get the master cultivar (seed variety) for this harvest.
     * Provides agricultural context for yield analysis and planning.
     *
     * @return BelongsTo MasterCultivar relationship
     * @agricultural_context Links harvest to specific seed variety for performance tracking
     * @business_usage Used in yield analytics and variety comparison reports
     */
    public function masterCultivar(): BelongsTo
    {
        return $this->belongsTo(MasterCultivar::class);
    }

    /**
     * Get the user who performed this harvest operation.
     * Tracks agricultural labor and productivity metrics.
     *
     * @return BelongsTo User relationship
     * @workflow_context Enables labor tracking and harvest performance analysis
     * @business_usage Used in productivity reports and labor cost allocation
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Get the start date of the agricultural week containing this harvest.
     * Agricultural weeks run Wednesday to Tuesday for consistent reporting.
     *
     * @return Carbon Week start date (Wednesday)
     * @agricultural_standard Agricultural week starts Wednesday for harvest planning
     * @business_usage Used in weekly production reports and scheduling
     */
    public function getWeekStartDateAttribute(): Carbon
    {
        return $this->harvest_date->copy()->startOfWeek(Carbon::WEDNESDAY);
    }

    /**
     * Get the end date of the agricultural week containing this harvest.
     * Agricultural weeks run Wednesday to Tuesday for consistent reporting.
     *
     * @return Carbon Week end date (Tuesday)
     * @agricultural_standard Agricultural week ends Tuesday for harvest planning
     * @business_usage Used in weekly production reports and scheduling
     */
    public function getWeekEndDateAttribute(): Carbon
    {
        return $this->harvest_date->copy()->endOfWeek(Carbon::TUESDAY);
    }


    /**
     * Get the full variety name for display purposes.
     * Provides user-friendly variety identification for agricultural records.
     *
     * @return string Full variety name or fallback text
     * @agricultural_context Displays complete cultivar name for identification
     * @business_usage Used in harvest reports and production documentation
     * @fallback_handling Returns 'Unknown Variety' if cultivar relationship missing
     */
    public function getVarietyNameAttribute(): string
    {
        return $this->masterCultivar ? $this->masterCultivar->full_name : 'Unknown Variety';
    }

    /**
     * Configure activity logging for harvest record changes.
     * Tracks modifications to critical agricultural production data.
     *
     * @return LogOptions Activity logging configuration
     * @audit_purpose Maintains history of harvest data changes for agricultural compliance
     * @logged_fields Tracks all core harvest metrics and relationships
     * @business_usage Used for production auditing and data integrity verification
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'master_cultivar_id',
                'user_id',
                'total_weight_grams',
                'harvest_date',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
