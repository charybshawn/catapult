<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Represents system configuration settings for agricultural microgreens
 * production management, storing key-value pairs with type safety and
 * grouping for organized application configuration.
 *
 * @business_domain Agricultural System Configuration & Settings Management
 * @workflow_context Used in system setup, operational configuration, and feature control
 * @agricultural_process Configures production parameters, notifications, and operational rules
 *
 * Database Table: settings
 * @property int $id Primary identifier for setting record
 * @property string $key Unique setting key identifier
 * @property string $value Setting value stored as string (cast based on type)
 * @property string|null $description Human-readable setting description
 * @property string $type Value type (string, integer, float, boolean, json)
 * @property string|null $group Setting group for organization (general, notifications, fertilizer)
 * @property Carbon $created_at Record creation timestamp
 * @property Carbon $updated_at Record last update timestamp
 *
 * @business_rule Settings support multiple data types with automatic casting
 * @business_rule Keys are unique identifiers for consistent access
 * @business_rule Groups organize related settings for management interfaces
 *
 * @agricultural_examples Fertilizer ratios, notification thresholds, production parameters
 * @system_configuration Enables dynamic configuration without code changes
 */
class Setting extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'description',
        'type', // string, integer, float, boolean, json
        'group', // general, notifications, fertilizer, etc.
    ];
    
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'string', // Will be cast dynamically based on 'type'
    ];
    
    /**
     * Get a typed setting value by key with fallback default.
     * Retrieves agricultural system configuration with proper type casting.
     *
     * @param string $key Setting key identifier
     * @param mixed $default Default value if setting not found
     * @return mixed Properly typed setting value or default
     * @agricultural_usage Used to retrieve production parameters, notification settings
     * @business_logic Enables dynamic configuration of agricultural workflows
     * @type_safety Automatically casts values based on stored type information
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        
        if (!$setting) {
            return $default;
        }
        
        return self::castValue($setting->value, $setting->type);
    }
    
    /**
     * Set a typed setting value by key with automatic type detection.
     * Stores agricultural configuration with proper type preservation.
     *
     * @param string $key Setting key identifier
     * @param mixed $value Setting value to store
     * @param string|null $description Human-readable setting description
     * @param string|null $group Setting group for organization
     * @return void
     * @agricultural_usage Used to configure production parameters, operational rules
     * @business_logic Enables runtime configuration changes for agricultural systems
     * @type_detection Automatically determines and stores value type for casting
     */
    public static function setValue(string $key, $value, ?string $description = null, ?string $group = null): void
    {
        $type = self::determineType($value);
        $stringValue = self::valueToString($value);
        
        self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'description' => $description,
                'group' => $group,
            ]
        );
    }
    
    /**
     * Cast stored string value to appropriate type based on type metadata.
     * Ensures proper data types for agricultural configuration values.
     *
     * @param string $value Stored string value from database
     * @param string $type Type metadata for casting
     * @return mixed Properly typed value for application use
     * @agricultural_context Maintains data integrity for production parameters
     * @type_system Supports integer, float, boolean, JSON, and string types
     */
    private static function castValue(string $value, string $type)
    {
        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value, // string
        };
    }
    
    /**
     * Automatically determine the appropriate type for a given value.
     * Analyzes value characteristics to assign proper type metadata.
     *
     * @param mixed $value Value to analyze for type determination
     * @return string Type identifier for storage and casting
     * @agricultural_context Ensures configuration values maintain proper types
     * @type_detection Supports common PHP types used in agricultural settings
     */
    private static function determineType($value): string
    {
        return match (true) {
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_bool($value) => 'boolean',
            is_array($value) => 'json',
            default => 'string',
        };
    }
    
    /**
     * Convert any value type to string format for database storage.
     * Serializes values while preserving information for proper reconstruction.
     *
     * @param mixed $value Value to convert to storage format
     * @return string String representation for database storage
     * @agricultural_context Enables storage of complex agricultural configuration data
     * @serialization Handles arrays, booleans, and scalars appropriately
     */
    private static function valueToString($value): string
    {
        return match (true) {
            is_array($value) => json_encode($value),
            is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }
    
    /**
     * Configure activity logging for system setting changes.
     * Tracks modifications to critical agricultural configuration parameters.
     *
     * @return LogOptions Activity logging configuration
     * @audit_purpose Maintains history of system configuration changes
     * @logged_fields Tracks key, value, type, and group for complete context
     * @business_usage Used for system administration and configuration auditing
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'value', 'type', 'group'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
