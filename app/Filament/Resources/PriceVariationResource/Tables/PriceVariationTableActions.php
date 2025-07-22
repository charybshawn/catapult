<?php

namespace App\Filament\Resources\PriceVariationResource\Tables;

use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * PriceVariation Table Actions Component
 * Extracted from PriceVariationResource table actions (lines 466-565)
 * Following Filament Resource Architecture Guide patterns
 * Max 100 lines as per requirements for action components
 */
class PriceVariationTableActions
{
    /**
     * Get row actions for table
     */
    public static function getRowActions(): array
    {
        return [
            Tables\Actions\EditAction::make()
                ->tooltip('Edit price variation'),
            static::getApplyTemplateAction(),
            Tables\Actions\DeleteAction::make()
                ->tooltip('Delete price variation')
                ->requiresConfirmation()
                ->modalHeading('Delete Price Variation')
                ->modalDescription('Are you sure you want to delete this price variation? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, delete it'),
        ];
    }

    /**
     * Get delete bulk action
     */
    public static function getDeleteBulkAction(): Tables\Actions\DeleteBulkAction
    {
        return Tables\Actions\DeleteBulkAction::make()
            ->requiresConfirmation()
            ->modalHeading('Delete Price Variations')
            ->modalDescription('Are you sure you want to delete the selected price variations? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete them');
    }

    /**
     * Get activate bulk action
     */
    public static function getActivateBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-check-circle')
            ->action(fn (Builder $query) => $query->update(['is_active' => true]));
    }

    /**
     * Get deactivate bulk action
     */
    public static function getDeactivateBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-x-circle')
            ->action(fn (Builder $query) => $query->update(['is_active' => false]));
    }

    /**
     * Get apply template action (complex action will be extracted to Action class)
     */
    protected static function getApplyTemplateAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('apply_template')
            ->label('Apply to Product')
            ->icon('heroicon-o-document-duplicate')
            ->color('success')
            ->visible(fn ($record) => $record->is_global)
            ->form(static::getApplyTemplateForm())
            ->action(function ($record, array $data) {
                // Delegate to business logic action
                app(\App\Actions\PriceVariation\ApplyTemplateAction::class)
                    ->execute($record, $data);
                
                \Filament\Notifications\Notification::make()
                    ->title('Template Applied Successfully')
                    ->body('The template has been applied to the selected product.')
                    ->success()
                    ->send();
            })
            ->tooltip('Apply this template to a specific product');
    }

    /**
     * Get apply template form
     */
    protected static function getApplyTemplateForm(): array
    {
        return [
            Forms\Components\Select::make('product_id')
                ->label('Product')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('name')
                ->label('Variation Name')
                ->required()
                ->default(fn ($record) => $record->name),
            Forms\Components\TextInput::make('fill_weight')
                ->label('Fill Weight (grams)')
                ->numeric()
                ->minValue(0)
                ->suffix('g')
                ->helperText('Specify the actual fill weight for this product')
                ->required(),
            Forms\Components\TextInput::make('sku')
                ->label('SKU/UPC Code')
                ->maxLength(255)
                ->default(fn ($record) => $record->sku),
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Custom Price')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->default(fn ($record) => $record->price)
                        ->required()
                        ->helperText(fn ($record) => 'Template price: $' . number_format($record->price, 2)),
                    Forms\Components\Placeholder::make('price_comparison')
                        ->label('Price Override')
                        ->content('Enter a custom price above to override the template pricing')
                        ->extraAttributes(['class' => 'prose text-sm']),
                ]),
            Forms\Components\Toggle::make('is_default')
                ->label('Make this the default price for the product')
                ->default(false),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ];
    }
}