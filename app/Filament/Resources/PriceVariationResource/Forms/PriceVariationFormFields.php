<?php

namespace App\Filament\Resources\PriceVariationResource\Forms;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use App\Models\PackagingType;
use Filament\Forms\Components\Hidden;
use App\Filament\Resources\BaseResource;
use Filament\Forms;

/**
 * PriceVariation Form Fields Helper for Agricultural Product Pricing
 * 
 * Provides specialized Filament form field definitions for agricultural product
 * price variations with complex business logic including auto-generation, dynamic
 * labeling, and agricultural-specific validation. Supports weight-based pricing,
 * packaging calculations, and integration with agricultural inventory systems.
 * 
 * @filament_component Specialized field definitions for price variation forms
 * @business_domain Agricultural product pricing with packaging and weight calculations
 * @architectural_purpose Extracted to keep main form class under 300 lines following architecture guide
 * 
 * @field_types Product selection, pricing configuration, packaging integration, weight calculations
 * @agricultural_focus Microgreens business with per-gram, per-package, and bulk pricing needs
 * @auto_generation Smart field auto-population based on agricultural business rules
 * 
 * @related_classes PriceVariationForm (main form), PriceVariationFormHelpers (business logic)
 * @validation_context Agricultural weight standards, packaging capacity constraints
 * @business_integration Product catalog, packaging types, pricing templates for agricultural operations
 */
class PriceVariationFormFields
{
    /**
     * Get product selection field for non-global pricing variations.
     * 
     * Creates a searchable product selector that's only visible and required for
     * product-specific pricing variations (not global templates). Essential for
     * linking agricultural product pricing to specific microgreen varieties.
     * 
     * @return Select Searchable product relationship field with conditional visibility
     * @business_rule Required for product-specific variations, hidden for global templates
     * @agricultural_context Links pricing to specific microgreen products in catalog
     * @ui_behavior Preloaded options with search capability for large product catalogs
     */
    public static function getProductSelectionField(): Select
    {
        return Select::make('product_id')
            ->relationship('product', 'name')
            ->label('Product')
            ->required(fn (Get $get): bool => ! $get('is_global'))
            ->searchable()
            ->preload()
            ->placeholder('Select a product...')
            ->visible(fn (Get $get): bool => ! $get('is_global'))
            ->columnSpanFull();
    }

    /**
     * Get pricing type field for agricultural customer segments.
     * 
     * Provides selection between retail, wholesale, bulk, special, and custom pricing
     * types. Each type has different implications for pricing units and calculations.
     * Reactive field that triggers auto-generation of variation names and pricing unit visibility.
     * 
     * @return Select Pricing type selector with agricultural business segments
     * @agricultural_segments retail (individual consumers), wholesale (restaurants), bulk (distributors)
     * @business_logic Bulk pricing shows additional unit options, triggers name generation
     * @pricing_strategy Different customer types require different pricing approaches in agriculture
     */
    public static function getPricingTypeField(): Select
    {
        return Select::make('pricing_type')
            ->label('Pricing Type')
            ->options([
                'retail' => 'Retail',
                'wholesale' => 'Wholesale',
                'bulk' => 'Bulk',
                'special' => 'Special',
                'custom' => 'Custom',
            ])
            ->default('retail')
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                // Auto-generate name when pricing type changes
                PriceVariationFormHelpers::generateVariationName($get('packaging_type_id'), $state, $set, $get);
                // Show pricing unit for bulk
                if ($state === 'bulk') {
                    $set('show_pricing_unit', true);
                }
            });
    }

    /**
     * Get pricing unit field for weight and quantity-based calculations.
     * 
     * Configures how prices are calculated - per item/package, per gram, per kilogram, etc.
     * Critical for agricultural products where both package and weight pricing are common.
     * Visibility depends on pricing type selection, particularly important for bulk sales.
     * 
     * @return Select Pricing unit selector with agricultural measurement standards
     * @agricultural_units per_g (microgreens sold by weight), per_item (packaged products)
     * @business_logic Visible for bulk/wholesale pricing or when no packaging specified
     * @weight_standards Supports metric (gram, kg) and imperial (pound, ounce) measurements
     */
    public static function getPricingUnitField(): Select
    {
        return Select::make('pricing_unit')
            ->label('Pricing Unit')
            ->options([
                'per_item' => 'Per Item/Package',
                'per_g' => 'Per Gram',
                'per_kg' => 'Per Kilogram',
                'per_lb' => 'Per Pound',
                'per_oz' => 'Per Ounce',
            ])
            ->default('per_item')
            ->live(onBlur: true)
            ->visible(fn (Get $get): bool => $get('pricing_type') === 'bulk' ||
                $get('pricing_type') === 'wholesale' ||
                ! $get('packaging_type_id')
            );
    }

    /**
     * Get name field with intelligent auto-generation for agricultural variations.
     * 
     * Provides variation naming with automatic generation based on pricing type and packaging.
     * Users can manually override auto-generated names while maintaining system tracking.
     * Includes reset action to return to auto-generated naming.
     * 
     * @return TextInput Name field with auto-generation and manual override capability
     * @auto_generation Combines pricing type and packaging into readable variation names
     * @agricultural_examples "Retail - Clamshell", "Wholesale - Bulk Container", "Package-Free - Bulk"
     * @user_control Manual override detection with reset-to-auto action button
     */
    public static function getNameField(): TextInput
    {
        return BaseResource::getNameField('Variation Name')
            ->default('Auto-generated')
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                // Mark as manual if user manually edits the name
                if ($state !== 'Auto-generated' && $state !== $get('generated_name')) {
                    $set('is_name_manual', true);
                }
            })
            ->suffixAction(
                Action::make('reset_to_auto')
                    ->icon('heroicon-o-arrow-path')
                    ->tooltip('Reset to auto-generated name')
                    ->action(function (callable $set, callable $get) {
                        $set('is_name_manual', false);
                        PriceVariationFormHelpers::generateVariationName($get('packaging_type_id'), $get('pricing_type'), $set, $get);
                    })
            );
    }

    /**
     * Get price field with dynamic labeling and agricultural pricing calculations.
     * 
     * Creates price input with labels that change based on pricing unit selection.
     * Includes helper text showing total price calculations for weight-based pricing.
     * Essential for agricultural products where per-gram pricing needs total cost display.
     * 
     * @return TextInput Price field with dynamic labels and calculation helpers
     * @dynamic_labels Changes from "Price" to "Price per Gram", "Price per Kilogram", etc.
     * @agricultural_calculations Shows total package price when using weight-based pricing
     * @business_integration Triggers variation name auto-generation on price changes
     */
    public static function getPriceField(): TextInput
    {
        return TextInput::make('price')
            ->label(function (Get $get): string {
                $unit = $get('pricing_unit');

                return match ($unit) {
                    'per_g' => 'Price per Gram',
                    'per_kg' => 'Price per Kilogram',
                    'per_lb' => 'Price per Pound',
                    'per_oz' => 'Price per Ounce',
                    default => 'Price',
                };
            })
            ->numeric()
            ->prefix('$')
            ->placeholder('0.000')
            ->minValue(0)
            ->step(0.001)
            ->required()
            ->inputMode('decimal')
            ->helperText(function (Get $get): ?string {
                return PriceVariationFormHelpers::calculateTotalPriceHelperText($get);
            })
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                // Auto-generate name when price changes
                PriceVariationFormHelpers::generateVariationName($get('packaging_type_id'), $get('pricing_type'), $set, $get);
            });
    }

    /**
     * Get packaging type field for agricultural product containers.
     * 
     * Provides selection of packaging types (clamshells, bulk containers, etc.) with
     * active status filtering and display name formatting. Optional field allowing
     * package-free variations for bulk agricultural sales.
     * 
     * @return Select Packaging type selector with agricultural container options
     * @agricultural_packaging Clamshells for retail, bulk containers for wholesale, package-free for custom
     * @business_logic Optional field - null values indicate package-free variations
     * @integration Triggers variation name auto-generation and affects weight calculations
     */
    public static function getPackagingTypeField(): Select
    {
        return Select::make('packaging_type_id')
            ->relationship('packagingType', 'name', function ($query) {
                return $query->where('is_active', true);
            })
            ->getOptionLabelFromRecordUsing(fn (PackagingType $record): string => $record->display_name)
            ->label('Packaging')
            ->placeholder('Select packaging or leave empty')
            ->searchable()
            ->preload()
            ->nullable()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                // Auto-generate name when packaging changes
                PriceVariationFormHelpers::generateVariationName($state, $get('pricing_type'), $set, $get);
            })
            ->hint('Optional')
            ->helperText('Choose the packaging type for this price variation')
            ->dehydrated(true)
            ->visible(true);
    }

    /**
     * Get fill weight field with agricultural measurement standards.
     * 
     * Provides weight/quantity input with dynamic labeling based on pricing unit.
     * Critical for agricultural products where consistent fill weights affect pricing
     * and inventory calculations. Includes packaging capacity hints and unit suffixes.
     * 
     * @return TextInput Weight/quantity field with agricultural measurement support
     * @agricultural_measurements Supports grams (primary), kilograms, pounds, ounces for microgreens
     * @packaging_integration Shows packaging capacity hints when container selected
     * @business_validation Required based on pricing context and agricultural standards
     */
    public static function getFillWeightField(): TextInput
    {
        return TextInput::make('fill_weight')
            ->label(function (Get $get): string {
                $pricingUnit = $get('pricing_unit');

                return match ($pricingUnit) {
                    'per_g' => 'Weight (grams)',
                    'per_kg' => 'Weight (kg)',
                    'per_lb' => 'Weight (lbs)',
                    'per_oz' => 'Weight (oz)',
                    'per_item' => 'Quantity (units)',
                    default => 'Fill Weight (grams)'
                };
            })
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->placeholder(function (Get $get): string {
                $packagingId = $get('packaging_type_id');
                if (! $packagingId) {
                    return 'Enter amount';
                }

                return 'Enter weight or quantity';
            })
            ->suffix(function (Get $get): string {
                $pricingUnit = $get('pricing_unit');

                return match ($pricingUnit) {
                    'per_g' => 'g',
                    'per_kg' => 'kg',
                    'per_lb' => 'lbs',
                    'per_oz' => 'oz',
                    'per_item' => 'units',
                    default => 'g'
                };
            })
            ->hint(function (Get $get): string {
                $packagingId = $get('packaging_type_id');
                if (! $packagingId) {
                    return 'Package-free variation';
                }

                $packaging = PackagingType::find($packagingId);
                if ($packaging && $packaging->capacity_weight) {
                    return 'Package capacity: '.$packaging->capacity_weight.'g';
                }

                return '';
            })
            ->required(function (Get $get): bool {
                return PriceVariationFormHelpers::isFillWeightRequired($get);
            })
            ->live(onBlur: true);
    }

    /**
     * Get hidden tracking fields for form state management.
     * 
     * Provides hidden fields that track whether variation names are manually overridden
     * and store generated names for comparison. Essential for maintaining intelligent
     * auto-generation behavior in agricultural pricing forms.
     * 
     * @return array Hidden fields for tracking manual overrides and generated values
     * @tracking_purpose Prevents auto-generation from overwriting manual user inputs
     * @agricultural_context Maintains naming consistency across similar agricultural products
     */
    public static function getHiddenFields(): array
    {
        return [
            Hidden::make('is_name_manual')
                ->default(false),
            Hidden::make('generated_name'),
        ];
    }
}
