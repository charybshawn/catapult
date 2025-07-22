<?php

namespace App\Filament\Resources\ActivityResource\Tables;

use Filament\Tables;

class ActivityTableActions
{
    /**
     * Get view action for activity records
     */
    public static function getViewAction(): Tables\Actions\ViewAction
    {
        return Tables\Actions\ViewAction::make()
            ->tooltip('View activity details');
    }

    /**
     * Get statistics header action
     */
    public static function getStatisticsAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('stats')
            ->label('View Statistics')
            ->icon('heroicon-o-chart-bar')
            ->url(fn () => \App\Filament\Resources\ActivityResource::getUrl('stats'))
            ->color('gray');
    }

    /**
     * Get timeline header action
     */
    public static function getTimelineAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('timeline')
            ->label('Timeline View')
            ->icon('heroicon-o-clock')
            ->url(fn () => \App\Filament\Resources\ActivityResource::getUrl('timeline'))
            ->color('gray');
    }

    /**
     * Get export bulk action
     */
    public static function getExportBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('export')
            ->label('Export Selected')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function ($records) {
                return app(\App\Actions\Activity\ExportActivityLogsAction::class)->execute($records);
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * Get action group for table row actions
     */
    public static function getActionGroup(): Tables\Actions\ActionGroup
    {
        return Tables\Actions\ActionGroup::make([
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
    public static function getBulkActionGroup(): Tables\Actions\BulkActionGroup
    {
        return Tables\Actions\BulkActionGroup::make([
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