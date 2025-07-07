<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLogQuery extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'activity_log_queries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'activity_log_id',
        'sql',
        'bindings',
        'execution_time_ms',
        'connection_name',
        'query_type',
        'table_name',
        'rows_affected',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'bindings' => 'array',
        'execution_time_ms' => 'float',
        'rows_affected' => 'integer',
    ];

    /**
     * Get the activity log that owns the query.
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_log_id');
    }

    /**
     * Scope to filter slow queries.
     */
    public function scopeSlow($query, float $thresholdMs = 100)
    {
        return $query->where('execution_time_ms', '>', $thresholdMs);
    }

    /**
     * Scope to filter by query type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('query_type', $type);
    }

    /**
     * Scope to filter by table name.
     */
    public function scopeForTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    /**
     * Get the formatted SQL with bindings replaced.
     */
    public function getFormattedSqlAttribute(): string
    {
        $sql = $this->sql;
        $bindings = $this->bindings ?? [];

        foreach ($bindings as $binding) {
            $value = is_numeric($binding) ? $binding : "'{$binding}'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        return $sql;
    }

    /**
     * Check if this is a write query.
     */
    public function isWriteQuery(): bool
    {
        return in_array($this->query_type, ['insert', 'update', 'delete', 'create', 'drop', 'alter']);
    }

    /**
     * Check if this is a read query.
     */
    public function isReadQuery(): bool
    {
        return $this->query_type === 'select';
    }
}