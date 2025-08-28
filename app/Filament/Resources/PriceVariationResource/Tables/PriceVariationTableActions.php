<?php

namespace App\Filament\Resources\PriceVariationResource\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Actions\Action;
use App\Actions\PriceVariation\ApplyTemplateAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * PriceVariation Table Actions Component for Agricultural Pricing Operations
 * 
 * Provides table row and bulk actions for agricultural product price variation management,
 * including template application functionality for creating product-specific variations
 * from global templates. Supports standard CRUD operations with agricultural business
 * logic integration through dedicated action classes.
 * 
 * @filament_component Table actions for PriceVariationResource
 * @business_domain Agricultural product pricing with template application capability
 * @architectural_pattern Extracted from PriceVariationResource following Filament Resource Architecture Guide
 * @complexity_target Max 100 lines through delegation to business logic action classes
 * 
 * @template_functionality Apply global pricing templates to specific agricultural products
 * @bulk_operations Activate/deactivate, delete operations for agricultural pricing management
 * @business_integration ApplyTemplateAction for complex template-to-product conversions
 * 
 * @agricultural_context Microgreens pricing template system with product-specific application
 * @related_classes ApplyTemplateAction for business logic, standard Filament actions for CRUD
 * @form_integration Complex template application forms with agricultural product context
 */
class PriceVariationTableActions
{
    /**
     * Get row actions for agricultural price variation table.
     * 
     * Provides edit, template application, and delete actions for individual
     * price variations. Template application is only visible for global templates,
     * allowing conversion to product-specific variations with agricultural context.
     * 
     * @return array Row actions including edit, apply template, and delete
     * @agricultural_functionality Template application for creating product-specific pricing
     * @business_safety Delete action includes confirmation for data protection
     * @conditional_visibility Apply template only shown for global template variations
     */
    public static function getRowActions(): array
    {
        return [
            EditAction::make()
                ->tooltip('Edit price variation'),
            static::getApplyTemplateAction(),
            DeleteAction::make()
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
    public static function getDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->requiresConfirmation()
            ->modalHeading('Delete Price Variations')
            ->modalDescription('Are you sure you want to delete the selected price variations? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, delete them');
    }

    /**
     * Get activate bulk action
     */
    public static function getActivateBulkAction(): BulkAction
    {
        return BulkAction::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-check-circle')
            ->action(fn (Builder $query) => $query->update(['is_active' => true]));
    }

    /**
     * Get deactivate bulk action
     */
    public static function getDeactivateBulkAction(): BulkAction
    {
        return BulkAction::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-x-circle')
            ->action(fn (Builder $query) => $query->update(['is_active' => false]));
    }

    /**
     * Get apply template action for converting global templates to product-specific variations.
     * 
     * Provides complex form for applying global pricing templates to specific agricultural
     * products with customization options for fill weight, pricing, and SKU information.
     * Delegates business logic to ApplyTemplateAction for proper separation of concerns.
     * 
     * @return Action Template application action with agricultural product form
     * @business_logic Delegates to ApplyTemplateAction for template-to-product conversion
     * @agricultural_form Product selection, weight specification, pricing customization
     * @template_system Core functionality for reusable pricing patterns in agriculture
     */
    protected static function getApplyTemplateAction(): Action
    {
        return Action::make('apply_template')
            ->label('Apply to Product')
            ->icon('heroicon-o-document-duplicate')
            ->color('success')
            ->visible(fn ($record) => $record->is_global)
            ->schema(static::getApplyTemplateForm())
            ->action(function ($record, array $data) {
                // Delegate to business logic action
                app(ApplyTemplateAction::class)
                    ->execute($record, $data);
                
                Notification::make()
                    ->title('Template Applied Successfully')
                    ->body('The template has been applied to the selected product.')
                    ->success()
                    ->send();
            })
            ->tooltip('Apply this template to a specific product');
    }

    /**
     * Get apply template form for agricultural product pricing customization.
     * 
     * Builds comprehensive form for converting global pricing templates into
     * product-specific variations with agricultural context including weight
     * specifications, pricing overrides, and inventory considerations.
     * 
     * @return array Form schema for template application to agricultural products
     * @agricultural_fields Product selection, fill weight, pricing customization for microgreens
     * @business_logic Default values from template with override capability
     * @inventory_integration SKU fields and active status for agricultural inventory systems
     */
    protected static function getApplyTemplateForm(): array
    {
        return [
            Select::make('product_id')
                ->label('Product')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('name')
                ->label('Variation Name')
                ->required()
                ->default(fn ($record) => $record->name),
            TextInput::make('fill_weight')
                ->label('Fill Weight (grams)')
                ->numeric()
                ->minValue(0)
                ->suffix('g')
                ->helperText('Specify the actual fill weight for this product')
                ->required(),
            TextInput::make('sku')
                ->label('SKU/UPC Code')
                ->maxLength(255)
                ->default(fn ($record) => $record->sku),
            Grid::make(2)
                ->schema([
                    TextInput::make('price')
                        ->label('Custom Price')
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->default(fn ($record) => $record->price)
                        ->required()
                        ->helperText(fn ($record) => 'Template price: $' . number_format($record->price, 2)),
                    Placeholder::make('price_comparison')
                        ->label('Price Override')
                        ->content('Enter a custom price above to override the template pricing')
                        ->extraAttributes(['class' => 'prose text-sm']),
                ]),
            Toggle::make('is_default')
                ->label('Make this the default price for the product')
                ->default(false),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ];
    }
}