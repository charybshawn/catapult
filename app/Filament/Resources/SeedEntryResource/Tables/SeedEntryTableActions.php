<?php

namespace App\Filament\Resources\SeedEntryResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use App\Actions\SeedEntry\ImportToMasterCatalogAction;
use App\Actions\SeedEntry\ToggleSeedEntryStatusAction;
use App\Actions\SeedEntry\ValidateSeedEntryDeletionAction;
use App\Models\SeedEntry;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

class SeedEntryTableActions
{
    /**
     * Returns Filament table actions for SeedEntry
     */
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->tooltip('View record'),
                EditAction::make()->tooltip('Edit record'),
                static::getDeleteAction(),
                static::getDeactivateAction(),
                static::getActivateAction(),
                static::getVisitUrlAction(),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Returns Filament table bulk actions for SeedEntry
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                static::getImportToMasterCatalogBulkAction(),
                static::getDeleteBulkAction(),
            ]),
        ];
    }

    protected static function getDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->tooltip('Delete record')
            ->requiresConfirmation()
            ->modalHeading('Delete Seed Entry')
            ->modalDescription('Are you sure you want to delete this seed entry?')
            ->before(function (DeleteAction $action, SeedEntry $record) {
                // Check for active relationships that would prevent deletion
                $issues = app(ValidateSeedEntryDeletionAction::class)->execute($record);
                
                if (!empty($issues)) {
                    // Cancel the action and show the issues
                    $action->cancel();
                    
                    Notification::make()
                        ->title('Cannot Delete Seed Entry')
                        ->body(
                            'This seed entry cannot be deleted because it is actively being used:' . 
                            '<br><br><strong>' . implode('</strong><br><strong>', $issues) . '</strong>' .
                            '<br><br>Please remove these dependencies first, or consider deactivating the seed entry instead.'
                        )
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }

    protected static function getDeactivateAction(): Action
    {
        return Action::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-eye-slash')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Deactivate Seed Entry')
            ->modalDescription('This will deactivate the seed entry, making it unavailable for new uses while preserving existing data.')
            ->action(function (SeedEntry $record) {
                app(ToggleSeedEntryStatusAction::class)->deactivate($record);
                
                Notification::make()
                    ->title('Seed Entry Deactivated')
                    ->body("'{$record->common_name} - {$record->cultivar_name}' has been deactivated.")
                    ->success()
                    ->send();
            })
            ->visible(fn (SeedEntry $record) => $record->is_active ?? true);
    }

    protected static function getActivateAction(): Action
    {
        return Action::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-eye')
            ->color('success')
            ->action(function (SeedEntry $record) {
                app(ToggleSeedEntryStatusAction::class)->activate($record);
                
                Notification::make()
                    ->title('Seed Entry Activated')
                    ->body("'{$record->common_name} - {$record->cultivar_name}' has been activated.")
                    ->success()
                    ->send();
            })
            ->visible(fn (SeedEntry $record) => !($record->is_active ?? true));
    }

    protected static function getVisitUrlAction(): Action
    {
        return Action::make('visit_url')
            ->label('Visit URL')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->url(fn (SeedEntry $record) => $record->url)
            ->openUrlInNewTab()
            ->visible(fn (SeedEntry $record) => !empty($record->url));
    }

    protected static function getImportToMasterCatalogBulkAction(): BulkAction
    {
        return BulkAction::make('import_to_master_catalog')
            ->label('Import to Master Catalog')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Import to Master Seed Catalog')
            ->modalDescription('This will create or update entries in the Master Seed Catalog based on the selected seed entries.')
            ->action(function (Collection $records) {
                $result = app(ImportToMasterCatalogAction::class)->execute($records);
                
                // Show results
                if ($result['imported'] > 0 || $result['updated'] > 0) {
                    $message = [];
                    if ($result['imported'] > 0) {
                        $message[] = "{$result['imported']} new master catalog entries created";
                    }
                    if ($result['updated'] > 0) {
                        $message[] = "{$result['updated']} existing entries updated with new cultivars";
                    }
                    
                    Notification::make()
                        ->title('Import Successful')
                        ->body(implode('<br>', $message))
                        ->success()
                        ->send();
                }
                
                if (!empty($result['errors'])) {
                    Notification::make()
                        ->title('Some imports failed')
                        ->body('Errors:<br>' . implode('<br>', array_slice($result['errors'], 0, 5)) . 
                               (count($result['errors']) > 5 ? '<br>...and ' . (count($result['errors']) - 5) . ' more errors' : ''))
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }

    protected static function getDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->requiresConfirmation()
            ->modalHeading('Delete Selected Seed Entries')
            ->modalDescription('Are you sure you want to delete the selected seed entries?')
            ->before(function (DeleteBulkAction $action, Collection $records) {
                // Check each record for deletion safety
                $protectedEntries = [];
                $allIssues = [];
                
                foreach ($records as $record) {
                    $issues = app(ValidateSeedEntryDeletionAction::class)->execute($record);
                    if (!empty($issues)) {
                        $protectedEntries[] = $record->common_name . ' - ' . $record->cultivar_name;
                        $allIssues = array_merge($allIssues, $issues);
                    }
                }
                
                if (!empty($protectedEntries)) {
                    // Cancel the action and show the issues
                    $action->cancel();
                    
                    $entryList = implode(', ', $protectedEntries);
                    $issueList = array_unique($allIssues);
                    
                    Notification::make()
                        ->title('Cannot Delete Some Seed Entries')
                        ->body(
                            'The following seed entries cannot be deleted because they are actively being used:' . 
                            '<br><br><strong>' . $entryList . '</strong>' .
                            '<br><br>Issues found:' .
                            '<br><strong>' . implode('</strong><br><strong>', $issueList) . '</strong>' .
                            '<br><br>Please remove these dependencies first, or consider deactivating the seed entries instead.'
                        )
                        ->danger()
                        ->persistent()
                        ->send();
                }
            });
    }
}