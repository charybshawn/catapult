<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeCardTask extends Model
{
    protected $fillable = [
        'time_card_id',
        'task_name',
        'task_type_id',
        'is_custom',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    public function timeCard(): BelongsTo
    {
        return $this->belongsTo(TimeCard::class);
    }

    public function taskType(): BelongsTo
    {
        return $this->belongsTo(TaskType::class);
    }
}
