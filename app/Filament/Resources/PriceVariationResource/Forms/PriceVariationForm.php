<?php

namespace App\Filament\Resources\PriceVariationResource\Forms;

use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms;

/**
 * PriceVariation Form Component for Agricultural Product Pricing Management
 * 
 * Builds comprehensive Filament form schemas for agricultural product price variations,
 * supporting both product-specific pricing and global reusable pricing templates.
 * Handles complex agricultural pricing scenarios including retail, wholesale, bulk,
 * and custom pricing structures with packaging-specific calculations.
 * 
 * @filament_component Form schema builder for PriceVariationResource
 * @business_domain Agricultural product pricing with packaging variations
 * @architectural_pattern Extracted from PriceVariationResource following Filament Resource Architecture Guide
 * @complexity_target Under 300 lines through delegation to specialized field and helper classes
 * 
 * @usage_context Agricultural microgreens business with complex packaging and pricing needs
 * @pricing_types retail, wholesale, bulk, special, custom with per-unit or per-weight calculations
 * @packaging_support Clamshells, bulk containers, package-free variations with weight calculations
 * 
 * @related_classes PriceVariationFormFields, PriceVariationFormHelpers for field definitions and business logic
 * @business_rules Global templates can be applied to any product, product-specific variations enforce uniqueness
 * @validation_logic Ensures proper pricing unit calculations and packaging compatibility
 */
class PriceVariationForm
{
    /**
     * Get the complete price variation form schema with all sections.
     * 
     * Assembles the full form structure for agricultural product price variations,
     * combining template controls, basic information, product details, and settings
     * into a cohesive form experience that handles both simple and complex pricing scenarios.
     * 
     * @return array Complete Filament form schema with all sections and components
     * @business_logic Supports both global templates (reusable) and product-specific variations
     * @form_sections Template toggle, basic info, product details, settings for comprehensive coverage
     * @ui_pattern Collapsible sections with smart visibility based on global template selection
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
     * Get global template toggle section for reusable pricing templates.
     * 
     * Creates a prominent toggle control that allows users to create global pricing
     * templates that can be applied to any agricultural product. When enabled,
     * automatically clears product-specific fields and prevents default pricing selection.
     * 
     * @return Group Styled template toggle with background highlighting and live updates
     * @business_rule Global templates cannot be product-specific or set as default
     * @ui_behavior Live updates clear product_id and is_default when enabled
     * @agricultural_context Allows creating reusable pricing patterns for similar microgreen products
     */
    protected static function getTemplateToggleSection(): Group
    {
        return Group::make([
            Toggle::make('is_global')
                ->label('Global Pricing Template')
                ->helperText('Create a reusable template for any product')
                ->default(false)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state) {
                        $set('is_default', false);
                        $set('product_id', null);
                    }
                }),
        ])
            ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900 p-4 rounded-lg mb-6']);
    }

    /**
     * Get basic information section with dynamic product selection and core pricing fields.
     * 
     * Builds the primary form section containing product selection (for non-global variations),
     * pricing type selection, pricing unit configuration, and core fields like name and price.
     * Section description changes dynamically based on global template state.
     * 
     * @return Section Collapsible section with product selection and pricing configuration
     * @business_logic Product selection only visible for non-global variations
     * @pricing_types retail, wholesale, bulk, special, custom with different unit calculations
     * @agricultural_context Supports per-gram pricing for bulk microgreens and per-package for retail
     */
    protected static function getBasicInformationSection(): Section
    {
        return Section::make('Basic Information')
            ->description(fn (Get $get): string => $get('is_global')
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
     * Get pricing type and unit selector grid for agricultural pricing models.
     * 
     * Creates a two-column grid containing pricing type selection (retail, wholesale, bulk)
     * and pricing unit configuration (per-item, per-gram, per-kilogram, etc.).
     * Essential for agricultural products where pricing varies by customer type and weight.
     * 
     * @return Grid Two-column layout with pricing type and unit selectors
     * @agricultural_pricing Supports weight-based pricing common in microgreens industry
     * @business_logic Pricing unit visibility depends on selected pricing type
     */
    protected static function getPricingTypeAndUnitGrid(): Grid
    {
        return Grid::make(2)
            ->schema([
                PriceVariationFormFields::getPricingTypeField(),
                PriceVariationFormFields::getPricingUnitField(),
            ]);
    }

    /**
     * Get core fields grid containing essential variation information.
     * 
     * Creates a three-column grid with the variation name (auto-generated or manual),
     * price field with dynamic labeling based on pricing unit, and packaging type
     * selection. Includes hidden fields for tracking manual name overrides.
     * 
     * @return Grid Three-column layout with name, price, and packaging fields
     * @auto_generation Name field auto-generates from pricing type and packaging selection
     * @agricultural_context Packaging types include clamshells, bulk containers, package-free options
     */
    protected static function getCoreFieldsGrid(): Grid
    {
        return Grid::make(3)
            ->schema([
                PriceVariationFormFields::getNameField(),
                ...PriceVariationFormFields::getHiddenFields(),
                PriceVariationFormFields::getPriceField(),
                PriceVariationFormFields::getPackagingTypeField(),
            ]);
    }

    /**
     * Get product details section for weight and quantity specifications.
     * 
     * Creates a collapsible section containing fill weight/quantity fields and SKU
     * information. Critical for agricultural products where weight consistency
     * affects pricing calculations and inventory management.
     * 
     * @return Section Collapsed by default section with product detail fields
     * @agricultural_importance Weight specifications crucial for microgreens pricing
     * @business_logic Fill weight calculations affect total price display and inventory
     */
    protected static function getProductDetailsSection(): Section
    {
        return Section::make('Product Details')
            ->description('Specify quantity, weight, or packaging details')
            ->schema([
                Grid::make(2)
                    ->schema([
                        PriceVariationFormFields::getFillWeightField(),
                        static::getSkuField(),
                    ]),
            ])
            ->collapsible()
            ->collapsed();
    }

    /**
     * Get SKU field for product identification and barcode tracking.
     * 
     * Provides optional SKU/barcode field for agricultural product variations.
     * Important for retail operations where different packaging sizes need
     * unique identifiers for point-of-sale and inventory systems.
     * 
     * @return TextInput Optional SKU/barcode field with appropriate validation
     * @retail_context Essential for retail microgreen sales with multiple package sizes
     * @inventory_tracking Helps track specific variations in agricultural inventory systems
     */
    protected static function getSkuField(): TextInput
    {
        return TextInput::make('sku')
            ->label('SKU / Barcode')
            ->placeholder('Optional product code')
            ->maxLength(255);
    }

    /**
     * Get settings section for variation status and configuration options.
     * 
     * Creates a collapsible section containing active/inactive status toggle,
     * default pricing designation (for product-specific variations), and
     * optional description field for additional notes about the pricing variation.
     * 
     * @return Section Collapsed settings section with status controls and notes
     * @business_rule Only one variation per product can be marked as default
     * @agricultural_context Allows deactivating seasonal pricing without deletion
     */
    protected static function getSettingsSection(): Section
    {
        return Section::make('Settings')
            ->schema([
                Grid::make(2)
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
     * Get active toggle for variation availability control.
     * 
     * Controls whether this price variation is available for selection
     * in orders and pricing calculations. Allows temporary deactivation
     * without deleting agricultural pricing data.
     * 
     * @return Toggle Active status control defaulting to true
     * @agricultural_use Useful for seasonal pricing that may be temporarily unavailable
     * @business_logic Inactive variations are hidden from order forms but preserved in system
     */
    protected static function getActiveToggle(): Toggle
    {
        return Toggle::make('is_active')
            ->label('Active')
            ->helperText('Enable this price variation')
            ->default(true);
    }

    /**
     * Get default toggle for primary pricing designation.
     * 
     * Allows marking this variation as the default price for the associated product.
     * Only visible for product-specific variations (not global templates).
     * Business rule enforcement ensures only one variation per product can be default.
     * 
     * @return Toggle Default pricing designation with conditional visibility
     * @business_rule Enforced uniqueness - only one default variation per product
     * @agricultural_context Typically retail pricing is default with wholesale as alternative
     */
    protected static function getDefaultToggle(): Toggle
    {
        return Toggle::make('is_default')
            ->label('Default Price')
            ->helperText('Use as the default price for this product')
            ->default(false)
            ->visible(fn (Get $get): bool => ! $get('is_global'))
            ->disabled(fn (Get $get): bool => $get('is_global'));
    }

    /**
     * Get description field for additional pricing variation notes.
     * 
     * Provides optional textarea for documenting special conditions,
     * seasonal adjustments, or other important information about this
     * agricultural product pricing variation.
     * 
     * @return Textarea Optional notes field spanning full column width
     * @agricultural_use Document seasonal pricing, special customer agreements, or quality premiums
     * @business_context Helps track reasoning behind different pricing structures
     */
    protected static function getDescriptionField(): Textarea
    {
        return Textarea::make('description')
            ->label('Notes')
            ->placeholder('Optional notes about this price variation...')
            ->rows(2)
            ->columnSpanFull();
    }
}
