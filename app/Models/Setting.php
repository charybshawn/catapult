<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Setting extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    
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
     * Get a setting value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
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
     * Set a setting value by key.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @param string|null $group
     * @return void
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
     * Cast the value based on type.
     *
     * @param string $value
     * @param string $type
     * @return mixed
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
     * Determine the type of a value.
     *
     * @param mixed $value
     * @return string
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
     * Convert a value to string for storage.
     *
     * @param mixed $value
     * @return string
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
     * Configure the activity log options for this model.
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'value', 'type', 'group'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
