<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CropBatch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'crop_batches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipe_id',
    ];

    /**
     * Get the recipe for this batch.
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the crops in this batch.
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class, 'crop_batch_id');
    }

    /**
     * Get the count of crops in this batch.
     */
    public function getCropCountAttribute(): int
    {
        return $this->crops()->count();
    }

    /**
     * Get the current stage of the batch (from first crop).
     */
    public function getCurrentStageAttribute(): ?string
    {
        $firstCrop = $this->crops()->with('currentStage')->first();
        return $firstCrop?->currentStage?->name;
    }

    /**
     * Check if this batch is in soaking stage.
     */
    public function isInSoaking(): bool
    {
        $firstCrop = $this->crops()->with('currentStage')->first();
        return $firstCrop?->currentStage?->code === 'soaking';
    }
}