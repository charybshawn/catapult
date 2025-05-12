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
        'task_type',
        'details',
        'scheduled_at',
        'triggered_at',
        'status',
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
}
