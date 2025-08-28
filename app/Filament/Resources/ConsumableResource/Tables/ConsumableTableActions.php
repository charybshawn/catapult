<?php

namespace App\Filament\Resources\ConsumableResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Tables;

class ConsumableTableActions
{
    /**
     * Get table actions for ConsumableResource
     */
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->tooltip('View record'),
                EditAction::make()->tooltip('Edit record'),
                DeleteAction::make()->tooltip('Delete record'),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Get header actions for ConsumableResource table
     */
    public static function headerActions(): array
    {
        return [
            // CSV export action will be added by the main resource
        ];
    }

    /**
     * Get bulk actions for ConsumableResource
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                ...static::getStandardBulkActions(),
                ...static::getInventoryBulkActions(),
            ]),
        ];
    }

    /**
     * Get standard bulk actions
     */
    protected static function getStandardBulkActions(): array
    {
        return [
            DeleteBulkAction::make(),
            BulkAction::make('activate')
                ->label('Activate Selected')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function ($records) {
                    $records->each(function ($record) {
                        $record->update(['is_active' => true]);
                    });
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('deactivate')
                ->label('Deactivate Selected')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->action(function ($records) {
                    $records->each(function ($record) {
                        $record->update(['is_active' => false]);
                    });
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }

    /**
     * Get inventory-related bulk actions
     */
    protected static function getInventoryBulkActions(): array
    {
        return [
            BulkAction::make('mark_low_stock')
                ->label('Mark as Low Stock')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->action(function ($records) {
                    $records->each(function ($record) {
                        // Set consumed quantity to force low stock status
                        $newConsumed = max(0, $record->initial_stock - ($record->restock_threshold ?? 1));
                        $record->update(['consumed_quantity' => $newConsumed]);
                    });
                })
                ->requiresConfirmation()
                ->modalHeading('Mark as Low Stock')
                ->modalDescription('This will update the consumed quantity to trigger low stock alerts for selected items.')
                ->deselectRecordsAfterCompletion(),
        ];
    }
}