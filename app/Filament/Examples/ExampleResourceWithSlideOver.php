<?php

namespace App\Filament\Examples;

use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Support\SlideOverConfigurations;
use App\Filament\Traits\HasConsistentSlideOvers;
use Filament\Resources\Resource;
use Filament\Tables;

/**
 * Example showing how to use the consistent slideover system
 * This is a demonstration file - copy these patterns to your actual resources
 */
class ExampleResourceWithSlideOver extends Resource
{
    use HasConsistentSlideOvers;

    // Example 1: Simple usage with predefined configurations
    public static function tableActionsSimple(): array
    {
        // Uses predefined configuration for products
        return static::getStandardTableActions(SlideOverConfigurations::product());
    }

    public static function headerActionsSimple(): array
    {
        // Uses predefined configuration for products
        return static::getStandardHeaderActions(SlideOverConfigurations::product());
    }

    // Example 2: Customized configuration
    public static function tableActionsCustomized(): array
    {
        return static::getStandardTableActions([
            'viewConfig' => [
                'tooltip' => 'View custom details',
                'heading' => 'Custom Details View',
                'description' => fn($record) => 'Custom description for ' . $record->name,
                'icon' => 'heroicon-o-star',
                'footerActions' => [
                    static::makeQuickAction('custom_action', [
                        'label' => 'Custom Action',
                        'icon' => 'heroicon-o-bolt',
                        'color' => 'primary',
                        'action' => fn($record) => null, // Add your logic here
                    ]),
                ],
            ],
            'editConfig' => [
                'tooltip' => 'Edit custom record',
                'heading' => 'Edit Custom Record',
                'description' => 'Update this record',
                'icon' => 'heroicon-o-cog',
            ],
            // Disable delete action
            'delete' => false,
        ]);
    }

    // Example 3: Mix predefined and custom actions
    public static function tableActionsMixed(): array
    {
        $config = SlideOverConfigurations::user();

        // Customize just the view action
        $config['viewConfig']['footerActions'][] = static::makeQuickAction('special_action', [
            'label' => 'Special Action',
            'icon' => 'heroicon-o-star',
            'color' => 'warning',
            'action' => fn($record) => null, // Add your logic
        ]);

        return static::getStandardTableActions($config);
    }

    // Example 4: Individual action creation
    public static function customViewAction(): ViewAction
    {
        return static::makeViewAction([
            'tooltip' => 'View this specific item',
            'heading' => 'Item Details',
            'description' => fn($record) => 'Viewing item: ' . $record->name,
            'icon' => 'heroicon-o-document-magnifying-glass',
            'footerActions' => [
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->action(fn($record) => null), // Add print logic
            ],
        ]);
    }

    // Example 5: Complete table configuration
    public static function completeTableExample(): Table
    {
        return Table::make()
            ->columns([
                // Your columns here
            ])
            ->recordActions(static::getStandardTableActions(SlideOverConfigurations::product()))
            ->headerActions(static::getStandardHeaderActions(SlideOverConfigurations::product()))
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

/**
 * Usage Examples:
 * 
 * 1. Quick setup for any resource:
 *    ->actions(static::getStandardTableActions(SlideOverConfigurations::user()))
 *    ->headerActions(static::getStandardHeaderActions(SlideOverConfigurations::user()))
 * 
 * 2. Custom configuration:
 *    ->actions(static::getStandardTableActions([
 *        'viewConfig' => ['heading' => 'My Custom View'],
 *        'editConfig' => ['heading' => 'My Custom Edit'],
 *    ]))
 * 
 * 3. Disable certain actions:
 *    ->actions(static::getStandardTableActions([
 *        'view' => false,  // No view action
 *        'delete' => false, // No delete action
 *    ]))
 * 
 * 4. Individual action creation:
 *    Tables\Actions\ViewAction::make() = static::makeViewAction(['heading' => 'Custom'])
 *    Tables\Actions\EditAction::make() = static::makeEditAction(['heading' => 'Custom'])
 *    Tables\Actions\CreateAction::make() = static::makeCreateAction(['label' => 'Custom'])
 */