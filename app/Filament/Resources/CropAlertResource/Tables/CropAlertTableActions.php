<?php

namespace App\Filament\Resources\CropAlertResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Actions\BulkAction;
use App\Actions\CropAlert\DebugCropAlert;
use App\Actions\CropAlert\ExecuteCropAlert;
use App\Actions\CropAlert\RescheduleCropAlert;
use App\Models\CropAlert;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;

class CropAlertTableActions
{
    /**
     * Get table actions for CropAlertResource
     */
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->tooltip('View record'),
                EditAction::make()
                    ->tooltip('Edit record'),
                static::getDebugAction(),
                static::getExecuteNowAction(),
                static::getRescheduleAction(),
                DeleteAction::make()
                    ->tooltip('Delete record')
                    ->modalDescription('Are you sure you want to delete this alert? This will stop the automatic stage transition alerts for this crop.'),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Get bulk actions for CropAlertResource
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                static::getExecuteSelectedBulkAction(),
                DeleteBulkAction::make()
                    ->modalDescription('Are you sure you want to delete these alerts? This will stop the automatic stage transition alerts for these crops.'),
            ]),
        ];
    }

    /**
     * Debug action for troubleshooting alerts
     */
    protected static function getDebugAction(): Action
    {
        return Action::make('debug')
            ->label('Debug Info')
            ->icon('heroicon-o-code-bracket')
            ->tooltip('Debug Info')
            ->action(function (CropAlert $record) {
                $debugInfo = app(DebugCropAlert::class)->execute($record);
                
                Notification::make()
                    ->title('Debug Information')
                    ->body($debugInfo['html'])
                    ->persistent()
                    ->actions([
                        Action::make('close')
                            ->label('Close')
                            ->color('gray')
                    ])
                    ->send();
            });
    }

    /**
     * Execute now action for immediate processing
     */
    protected static function getExecuteNowAction(): Action
    {
        return Action::make('execute_now')
            ->label('Execute Now')
            ->icon('heroicon-o-bolt')
            ->action(function (CropAlert $record) {
                $result = app(ExecuteCropAlert::class)->execute($record);
                
                if ($result['success']) {
                    Notification::make()
                        ->title('Alert executed successfully')
                        ->body($result['message'])
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Failed to execute alert')
                        ->body($result['message'])
                        ->danger()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Execute Alert Now')
            ->modalDescription('Are you sure you want to execute this alert now? This will advance the crop to the next stage immediately.');
    }

    /**
     * Reschedule action for changing alert timing
     */
    protected static function getRescheduleAction(): Action
    {
        return Action::make('reschedule')
            ->label('Reschedule')
            ->icon('heroicon-o-calendar-days')
            ->schema([
                DateTimePicker::make('new_time')
                    ->label('New time')
                    ->required()
                    ->default(function (CropAlert $record) {
                        return $record->next_run_at;
                    }),
            ])
            ->action(function (CropAlert $record, array $data) {
                app(RescheduleCropAlert::class)->execute($record, $data['new_time']);
                
                Notification::make()
                    ->title('Alert rescheduled')
                    ->success()
                    ->send();
            })
            ->modalHeading('Reschedule Alert');
    }

    /**
     * Bulk action for executing multiple alerts
     */
    protected static function getExecuteSelectedBulkAction(): BulkAction
    {
        return BulkAction::make('execute_selected')
            ->label('Execute Selected')
            ->action(function ($records) {
                $successCount = 0;
                $failCount = 0;
                
                foreach ($records as $record) {
                    $result = app(ExecuteCropAlert::class)->execute($record);
                    
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                }
                
                Notification::make()
                    ->title("Executed {$successCount} alerts")
                    ->body($failCount > 0 ? "{$failCount} alerts failed" : "Successfully advanced crops to their next stages.")
                    ->success()
                    ->send();
            })
            ->requiresConfirmation()
            ->modalHeading('Execute Selected Alerts')
            ->modalDescription('Are you sure you want to execute all selected alerts now? This will advance crops to their next stages immediately.');
    }
}