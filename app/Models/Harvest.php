<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Harvest extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'master_cultivar_id',
        'user_id',
        'total_weight_grams',
        'tray_count',
        'harvest_date',
        'notes',
    ];

    protected $casts = [
        'total_weight_grams' => 'decimal:2',
        'tray_count' => 'integer',
        'harvest_date' => 'date',
    ];

    public function masterCultivar(): BelongsTo
    {
        return $this->belongsTo(MasterCultivar::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getWeekStartDateAttribute(): Carbon
    {
        return $this->harvest_date->copy()->startOfWeek(Carbon::WEDNESDAY);
    }

    public function getWeekEndDateAttribute(): Carbon
    {
        return $this->harvest_date->copy()->endOfWeek(Carbon::TUESDAY);
    }

    public function getAverageWeightPerTrayAttribute(): float
    {
        return $this->tray_count > 0 ? $this->total_weight_grams / $this->tray_count : 0;
    }

    public function getVarietyNameAttribute(): string
    {
        return $this->masterCultivar ? $this->masterCultivar->full_name : 'Unknown Variety';
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'master_cultivar_id',
                'user_id',
                'total_weight_grams',
                'tray_count',
                'harvest_date',
                'notes',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
