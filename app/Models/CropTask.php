<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropTask extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'crop_id',
        'recipe_id',
        'crop_task_type_id',
        'crop_task_status_id',
        'details',
        'scheduled_at',
        'triggered_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'details' => 'json',
        'scheduled_at' => 'datetime',
        'triggered_at' => 'datetime',
    ];

    /**
     * Get the crop associated with the task.
     */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    /**
     * Get the recipe associated with the task.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the task type for this crop task.
     */
    public function cropTaskType(): BelongsTo
    {
        return $this->belongsTo(CropTaskType::class);
    }

    /**
     * Get the status for this crop task.
     */
    public function cropTaskStatus(): BelongsTo
    {
        return $this->belongsTo(CropTaskStatus::class);
    }
}
