<?php

namespace App\Actions\CropAlert;

use App\Models\CropAlert;
use Carbon\Carbon;

class RescheduleCropAlert
{
    /**
     * Reschedule a crop alert to a new time
     */
    public function execute(CropAlert $record, string $newTime): CropAlert
    {
        $record->update([
            'next_run_at' => Carbon::parse($newTime),
        ]);

        return $record->fresh();
    }
}