<?php

namespace App\Filament\Resources\PriceVariationResource\Forms;

use Filament\Forms;

/**
 * PriceVariation Form Component
 * Extracted from PriceVariationResource form method (lines 34-317)
 * Following Filament Resource Architecture Guide patterns
 * Under 300 lines as per requirements
 */
class PriceVariationForm
{
    /**
     * Get the complete price variation form schema
     */
    public static function schema(): array
    {
        return [
            static::getTemplateToggleSection(),
            static::getBasicInformationSection(),
            static::getProductDetailsSection(),
            static::getSettingsSection(),
        ];
    }

    /**
     * Get global template toggle section
     */
    protected static function getTemplateToggleSection(): Forms\Components\Group
    {
        return Forms\Components\Group::make([
            Forms\Components\Toggle::make('is_global')
                ->label('Global Pricing Template')
                ->helperText('Create a reusable template for any product')
                ->default(false)
                ->live(onBlur: true)()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if ($state) {
                        $set('is_default', false);
                        $set('product_id', null);
                    }
                }),
        ])
            ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900 p-4 rounded-lg mb-6']);
    }

    /**
     * Get basic information section
     */
    protected static function getBasicInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Basic Information')
            ->description(fn (Forms\Get $get): string => $get('is_global')
                    ? 'This template can be applied to any product'
                    : 'Define pricing for a specific product'
            )
            ->schema([
                PriceVariationFormFields::getProductSelectionField(),
                static::getPricingTypeAndUnitGrid(),
                static::getCoreFieldsGrid(),
            ])
            ->collapsible()
            ->persistCollapsed(false);
    }

    /**
     * Get pricing type and unit selector grid
     */
    protected static function getPricingTypeAndUnitGrid(): Forms\Components\Grid
    {
        return Forms\Components\Grid::make(2)
            ->schema([
                PriceVariationFormFields::getPricingTypeField(),
                PriceVariationFormFields::getPricingUnitField(),
            ]);
    }

    /**
     * Get core fields grid (name, price, packaging)
     */
    protected static function getCoreFieldsGrid(): Forms\Components\Grid
    {
        return Forms\Components\Grid::make(3)
            ->schema([
                PriceVariationFormFields::getNameField(),
                ...PriceVariationFormFields::getHiddenFields(),
                PriceVariationFormFields::getPriceField(),
                PriceVariationFormFields::getPackagingTypeField(),
            ]);
    }

    /**
     * Get product details section
     */
    protected static function getProductDetailsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Product Details')
            ->description('Specify quantity, weight, or packaging details')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        PriceVariationFormFields::getFillWeightField(),
                        static::getSkuField(),
                    ]),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Get SKU field
     */
    protected static function getSkuField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('sku')
            ->label('SKU / Barcode')
            ->placeholder('Optional product code')
            ->maxLength(255);
    }

    /**
     * Get settings section
     */
    protected static function getSettingsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Settings')
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        static::getActiveToggle(),
                        static::getDefaultToggle(),
                    ]),
                static::getDescriptionField(),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Get active toggle
     */
    protected static function getActiveToggle(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('is_active')
            ->label('Active')
            ->helperText('Enable this price variation')
            ->default(true);
    }

    /**
     * Get default toggle
     */
    protected static function getDefaultToggle(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('is_default')
            ->label('Default Price')
            ->helperText('Use as the default price for this product')
            ->default(false)
            ->visible(fn (Forms\Get $get): bool => ! $get('is_global'))
            ->disabled(fn (Forms\Get $get): bool => $get('is_global'));
    }

    /**
     * Get description field
     */
    protected static function getDescriptionField(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('description')
            ->label('Notes')
            ->placeholder('Optional notes about this price variation...')
            ->rows(2)
            ->columnSpanFull();
    }
}
