<?php

namespace App\Filament\Traits;

use Filament\Tables;
use Filament\Actions;
use Filament\Notifications\Notification;

trait HasStandardActions
{
    /**
     * Get standard table actions
     */
    public static function getStandardTableActions(): array
    {
        return [
            static::getViewTableAction(),
            static::getEditTableAction(),
            static::getDeleteTableAction(),
        ];
    }
    
    /**
     * Get view table action
     */
    public static function getViewTableAction(): Tables\Actions\ViewAction
    {
        return Tables\Actions\ViewAction::make()
            ->tooltip('View record')
            ->icon('heroicon-m-eye');
    }
    
    /**
     * Get edit table action
     */
    public static function getEditTableAction(): Tables\Actions\EditAction
    {
        return Tables\Actions\EditAction::make()
            ->tooltip('Edit record')
            ->icon('heroicon-m-pencil-square');
    }
    
    /**
     * Get delete table action
     */
    public static function getDeleteTableAction(): Tables\Actions\DeleteAction
    {
        return Tables\Actions\DeleteAction::make()
            ->tooltip('Delete record')
            ->icon('heroicon-m-trash')
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Record deleted')
                    ->body('The record has been deleted successfully.')
            );
    }
    
    /**
     * Get standard bulk actions
     */
    public static function getStandardBulkActions(): array
    {
        return [
            static::getDeleteBulkAction(),
        ];
    }
    
    /**
     * Get delete bulk action
     */
    public static function getDeleteBulkAction(): Tables\Actions\DeleteBulkAction
    {
        return Tables\Actions\DeleteBulkAction::make()
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Records deleted')
                    ->body('The selected records have been deleted successfully.')
            );
    }
    
    /**
     * Get export bulk action
     */
    public static function getExportBulkAction(string $filename = 'export'): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('export')
            ->label('Export Selected')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function ($records) use ($filename) {
                // Basic CSV export functionality
                $headers = array_keys($records->first()->toArray());
                $csv = implode(',', $headers) . "\n";
                
                foreach ($records as $record) {
                    $csv .= implode(',', array_map(function ($value) {
                        return '"' . str_replace('"', '""', $value ?? '') . '"';
                    }, $record->toArray())) . "\n";
                }
                
                return response()->streamDownload(function () use ($csv) {
                    echo $csv;
                }, $filename . '_' . date('Y-m-d_H-i-s') . '.csv', [
                    'Content-Type' => 'text/csv',
                ]);
            })
            ->color('gray')
            ->deselectRecordsAfterCompletion();
    }
    
    /**
     * Get restore action for soft deletes
     */
    public static function getRestoreAction(): Tables\Actions\RestoreAction
    {
        return Tables\Actions\RestoreAction::make()
            ->tooltip('Restore record')
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Record restored')
                    ->body('The record has been restored successfully.')
            );
    }
    
    /**
     * Get force delete action for soft deletes
     */
    public static function getForceDeleteAction(): Tables\Actions\ForceDeleteAction
    {
        return Tables\Actions\ForceDeleteAction::make()
            ->tooltip('Permanently delete record')
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Record permanently deleted')
                    ->body('The record has been permanently deleted.')
            );
    }
    
    /**
     * Get restore bulk action for soft deletes
     */
    public static function getRestoreBulkAction(): Tables\Actions\RestoreBulkAction
    {
        return Tables\Actions\RestoreBulkAction::make()
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Records restored')
                    ->body('The selected records have been restored successfully.')
            );
    }
    
    /**
     * Get force delete bulk action for soft deletes
     */
    public static function getForceDeleteBulkAction(): Tables\Actions\ForceDeleteBulkAction
    {
        return Tables\Actions\ForceDeleteBulkAction::make()
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Records permanently deleted')
                    ->body('The selected records have been permanently deleted.')
            );
    }
}