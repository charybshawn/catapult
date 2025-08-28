<?php

namespace App\Filament\Resources\RecurringOrderResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Actions\RecurringOrder\BulkGenerateOrdersAction;
use App\Actions\RecurringOrder\GenerateRecurringOrdersAction;
use App\Models\Order;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

/**
 * Recurring Order Table Actions - Extracted from RecurringOrderResource
 * Originally lines 389-603 in main resource (actions, headerActions, bulkActions)
 * Organized according to Filament Resource Architecture Guide
 * Business logic delegated to Action classes
 */
class RecurringOrderTableActions
{
    /**
     * Get row actions for recurring order table
     */
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->tooltip('View recurring order template'),
                EditAction::make()
                    ->tooltip('Edit recurring order template'),
                static::getGenerateNextAction(),
                static::getPauseAction(),
                static::getResumeAction(),
                DeleteAction::make()
                    ->tooltip('Delete recurring order template'),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Generate next order action
     */
    protected static function getGenerateNextAction(): Action
    {
        return Action::make('generate_next')
            ->label('Generate Orders')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->action(function (?Order $record): void {
                if (!$record) return;
                
                $result = app(GenerateRecurringOrdersAction::class)->execute($record);
                
                if ($result['success']) {
                    Notification::make()
                        ->title($result['title'])
                        ->body($result['message'])
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title($result['title'])
                        ->body($result['message'])
                        ->warning()
                        ->send();
                }
            })
            ->visible(fn (?Order $record): bool => $record?->is_recurring_active ?? false);
    }

    /**
     * Pause recurring order action
     */
    protected static function getPauseAction(): Action
    {
        return Action::make('pause')
            ->label('Pause')
            ->icon('heroicon-o-pause')
            ->color('warning')
            ->action(fn (?Order $record) => $record?->update(['is_recurring_active' => false]))
            ->requiresConfirmation()
            ->visible(fn (?Order $record): bool => $record?->is_recurring_active ?? false);
    }

    /**
     * Resume recurring order action
     */
    protected static function getResumeAction(): Action
    {
        return Action::make('resume')
            ->label('Resume')
            ->icon('heroicon-o-play')
            ->color('success')
            ->action(fn (?Order $record) => $record?->update(['is_recurring_active' => true]))
            ->visible(fn (?Order $record): bool => !($record?->is_recurring_active ?? true));
    }

    /**
     * Get header actions for recurring order table
     */
    public static function headerActions(): array
    {
        return [
            static::getGenerateAllDueAction(),
        ];
    }

    /**
     * Generate all due orders action
     */
    protected static function getGenerateAllDueAction(): Action
    {
        return Action::make('generate_all_due')
            ->label('Generate All Due Orders')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->action(function () {
                $result = app(BulkGenerateOrdersAction::class)->executeForAllActive();
                
                Notification::make()
                    ->title($result['title'])
                    ->body($result['message'])
                    ->{$result['type']}()
                    ->persistent()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading('Generate All Due Orders')
            ->modalDescription('This will generate orders for all active recurring templates that are due for generation.')
            ->modalSubmitActionLabel('Generate Orders');
    }

    /**
     * Get bulk actions for recurring order table
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                static::getGenerateSelectedAction(),
                static::getPauseAllAction(),
                static::getResumeAllAction(),
            ]),
        ];
    }

    /**
     * Generate orders from selected templates
     */
    protected static function getGenerateSelectedAction(): BulkAction
    {
        return BulkAction::make('generate_selected')
            ->label('Generate Orders from Selected')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->action(function (Collection $records) {
                $result = app(BulkGenerateOrdersAction::class)->executeForRecords($records);
                
                Notification::make()
                    ->title($result['title'])
                    ->body($result['message'])
                    ->{$result['type']}()
                    ->persistent()
                    ->send();
            })
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion();
    }

    /**
     * Pause all selected templates
     */
    protected static function getPauseAllAction(): BulkAction
    {
        return BulkAction::make('pause_all')
            ->label('Pause All')
            ->icon('heroicon-o-pause')
            ->color('warning')
            ->action(fn (Collection $records) => $records->each->update(['is_recurring_active' => false]))
            ->deselectRecordsAfterCompletion()
            ->requiresConfirmation();
    }

    /**
     * Resume all selected templates
     */
    protected static function getResumeAllAction(): BulkAction
    {
        return BulkAction::make('resume_all')
            ->label('Resume All')
            ->icon('heroicon-o-play')
            ->color('success')
            ->action(fn (Collection $records) => $records->each->update(['is_recurring_active' => true]))
            ->deselectRecordsAfterCompletion();
    }
}