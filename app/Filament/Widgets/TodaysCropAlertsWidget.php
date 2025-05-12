<?php

namespace App\Filament\Widgets;

use App\Models\CropAlert;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class TodaysCropAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.todays-crop-alerts-widget';
    
    // Set the default widget positioning
    protected static ?int $sort = 2;
    
    // Refresh interval in seconds (15 minutes)
    protected static ?string $pollingInterval = '900s';

    public function getTodaysAlerts()
    {
        return CropAlert::query()
            ->whereDate('next_run_at', Carbon::today())
            ->orderBy('next_run_at')
            ->get();
    }
} 