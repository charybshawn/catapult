<?php

namespace App\Filament\Resources\ActivityResource\Pages;

use App\Filament\Resources\ActivityResource;
use App\Filament\Widgets\ActivityStatsWidget;
use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\UserActivityHeatmapWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Activity;

class ListActivities extends ListRecords
{
    protected static string $resource = ActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('purge')
                ->label('Purge Old Logs')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Purge Old Activity Logs')
                ->modalDescription('This will permanently delete activity logs older than the retention period. Are you sure?')
                ->modalSubmitActionLabel('Yes, purge logs')
                ->action(fn () => $this->purgeLogs())
                ->visible(fn () => auth()->user()->can('purge', Activity::class)),
        ];
    }
    
    protected function purgeLogs(): void
    {
        \Artisan::call('activitylog:purge', ['--force' => true]);
        
        $this->notify('success', 'Old activity logs have been purged.');
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            ActivityStatsWidget::class,
        ];
    }
    
    protected function getFooterWidgets(): array
    {
        return [
            RecentActivityWidget::class,
            UserActivityHeatmapWidget::class,
        ];
    }
} 