<?php

namespace App\Filament\Resources\ActivityResource\Tables;

use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use App\Filament\Resources\ActivityResource;
use Filament\Actions\BulkAction;
use App\Actions\Activity\ExportActivityLogsAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Tables;

class ActivityTableActions
{
    /**
     * Get view action for activity records
     */
    public static function getViewAction(): ViewAction
    {
        return ViewAction::make()
            ->tooltip('View activity details');
    }

    /**
     * Get statistics header action
     */
    public static function getStatisticsAction(): Action
    {
        return Action::make('stats')
            ->label('View Statistics')
            ->icon('heroicon-o-chart-bar')
            ->url(fn () => ActivityResource::getUrl('stats'))
            ->color('gray');
    }

    /**
     * Get timeline header action
     */
    public static function getTimelineAction(): Action
    {
        return Action::make('timeline')
            ->label('Timeline View')
            ->icon('heroicon-o-clock')
            ->url(fn () => ActivityResource::getUrl('timeline'))
            ->color('gray');
    }

    /**
     * Get export bulk action
     */
    public static function getExportBulkAction(): BulkAction
    {
        return BulkAction::make('export')
            ->label('Export Selected')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function ($records) {
                return app(ExportActivityLogsAction::class)->execute($records);
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * Get action group for table row actions
     */
    public static function getActionGroup(): ActionGroup
    {
        return ActionGroup::make([
            static::getViewAction(),
        ])
        ->label('Actions')
        ->icon('heroicon-m-ellipsis-vertical')
        ->size('sm')
        ->color('gray')
        ->button();
    }

    /**
     * Get bulk action group
     */
    public static function getBulkActionGroup(): BulkActionGroup
    {
        return BulkActionGroup::make([
            static::getExportBulkAction(),
        ]);
    }

    /**
     * Get all header actions as array
     */
    public static function getHeaderActions(): array
    {
        return [
            static::getStatisticsAction(),
            static::getTimelineAction(),
        ];
    }
}