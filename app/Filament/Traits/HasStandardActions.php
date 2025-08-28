<?php

namespace App\Filament\Traits;

use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Tables;
use Filament\Actions;
use Filament\Notifications\Notification;

/**
 * Has Standard Actions Trait
 * 
 * Standardized action patterns for agricultural Filament resources providing
 * consistent CRUD operations, export functionality, and soft delete management.
 * Ensures uniform action behavior across all agricultural resource tables.
 * 
 * @filament_trait Standard action patterns for agricultural resource management
 * @agricultural_use Consistent CRUD actions across agricultural entities (products, crops, orders, inventory)
 * @action_consistency Uniform table and bulk actions for agricultural resource management
 * @soft_delete_support Restore and force delete actions for agricultural entity lifecycle
 * 
 * Key features:
 * - Standard table actions (view, edit, delete) with agricultural tooltips
 * - Bulk operations for agricultural entity management
 * - CSV export functionality for agricultural data analysis
 * - Soft delete support with restore capabilities
 * - Consistent notification patterns for agricultural workflows
 * 
 * @package App\Filament\Traits
 * @author Shawn
 * @since 2024
 */
trait HasStandardActions
{
    /**
     * Get standard table actions for agricultural resources.
     * 
     * @agricultural_context Standard view, edit, delete actions for agricultural entities
     * @return array Standard table actions with agricultural-appropriate tooltips
     * @consistency Provides uniform action patterns across agricultural resources
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
    public static function getViewTableAction(): ViewAction
    {
        return ViewAction::make()
            ->tooltip('View record')
            ->icon('heroicon-m-eye');
    }
    
    /**
     * Get edit table action
     */
    public static function getEditTableAction(): EditAction
    {
        return EditAction::make()
            ->tooltip('Edit record')
            ->icon('heroicon-m-pencil-square');
    }
    
    /**
     * Get delete table action
     */
    public static function getDeleteTableAction(): DeleteAction
    {
        return DeleteAction::make()
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
    public static function getDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Records deleted')
                    ->body('The selected records have been deleted successfully.')
            );
    }
    
    /**
     * Get basic CSV export bulk action for agricultural data.
     * 
     * @agricultural_context Basic CSV export for agricultural data analysis and reporting
     * @param string $filename Base filename for export (timestamp will be appended)
     * @return BulkAction Bulk action for exporting selected agricultural records to CSV
     * @note Consider using CsvExportAction trait for more advanced export features
     */
    public static function getExportBulkAction(string $filename = 'export'): BulkAction
    {
        return BulkAction::make('export')
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
     * Get restore action for soft-deleted agricultural entities.
     * 
     * @agricultural_context Restore capability for accidentally deleted agricultural entities
     * @return RestoreAction Restore action for recovering soft-deleted agricultural records
     * @soft_delete_support Allows recovery of agricultural entities without data loss
     */
    public static function getRestoreAction(): RestoreAction
    {
        return RestoreAction::make()
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
    public static function getForceDeleteAction(): ForceDeleteAction
    {
        return ForceDeleteAction::make()
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
    public static function getRestoreBulkAction(): RestoreBulkAction
    {
        return RestoreBulkAction::make()
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
    public static function getForceDeleteBulkAction(): ForceDeleteBulkAction
    {
        return ForceDeleteBulkAction::make()
            ->successNotification(
                Notification::make()
                    ->success()
                    ->title('Records permanently deleted')
                    ->body('The selected records have been permanently deleted.')
            );
    }
}