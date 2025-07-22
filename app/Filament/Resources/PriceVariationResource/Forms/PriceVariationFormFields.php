<?php

namespace App\Filament\Resources\PriceVariationResource\Forms;

use Filament\Forms;

/**
 * PriceVariation Form Fields Helper
 * Extracted to keep main form class under 300 lines
 * Contains individual field definitions
 */
class PriceVariationFormFields
{
    /**
     * Get product selection field for non-global variations
     */
    public static function getProductSelectionField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('product_id')
            ->relationship('product', 'name')
            ->label('Product')
            ->required(fn (Forms\Get $get): bool => !$get('is_global'))
            ->searchable()
            ->preload()
            ->placeholder('Select a product...')
            ->visible(fn (Forms\Get $get): bool => !$get('is_global'))
            ->columnSpanFull();
    }

    /**
     * Get pricing type field
     */
    public static function getPricingTypeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('pricing_type')
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
     * Get pricing unit field
     */
    public static function getPricingUnitField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('pricing_unit')
            ->label('Pricing Unit')
            ->options([
                'per_item' => 'Per Item/Package',
                'per_g' => 'Per Gram',
                'per_kg' => 'Per Kilogram',
                'per_lb' => 'Per Pound',
                'per_oz' => 'Per Ounce',
            ])
            ->default('per_item')
            ->live()
            ->visible(fn (Forms\Get $get): bool => 
                $get('pricing_type') === 'bulk' || 
                $get('pricing_type') === 'wholesale' ||
                !$get('packaging_type_id')
            );
    }

    /**
     * Get name field with auto-generation
     */
    public static function getNameField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('name')
            ->label('Variation Name')
            ->default('Auto-generated')
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                // Mark as manual if user manually edits the name
                if ($state !== 'Auto-generated' && $state !== $get('generated_name')) {
                    $set('is_name_manual', true);
                }
            })
            ->suffixAction(
                Forms\Components\Actions\Action::make('reset_to_auto')
                    ->icon('heroicon-o-arrow-path')
                    ->tooltip('Reset to auto-generated name')
                    ->action(function (callable $set, callable $get) {
                        $set('is_name_manual', false);
                        PriceVariationFormHelpers::generateVariationName($get('packaging_type_id'), $get('pricing_type'), $set, $get);
                    })
            );
    }

    /**
     * Get price field with dynamic label and calculations
     */
    public static function getPriceField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('price')
            ->label(function (Forms\Get $get): string {
                $unit = $get('pricing_unit');
                return match($unit) {
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
            ->helperText(function (Forms\Get $get): ?string {
                return PriceVariationFormHelpers::calculateTotalPriceHelperText($get);
            })
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                // Auto-generate name when price changes
                PriceVariationFormHelpers::generateVariationName($get('packaging_type_id'), $get('pricing_type'), $set, $get);
            });
    }

    /**
     * Get packaging type field
     */
    public static function getPackagingTypeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('packaging_type_id')
            ->relationship('packagingType', 'name', function ($query) {
                return $query->where('is_active', true);
            })
            ->getOptionLabelFromRecordUsing(fn (\App\Models\PackagingType $record): string => $record->display_name)
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
     * Get fill weight field with dynamic label and validation
     */
    public static function getFillWeightField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('fill_weight')
            ->label(function (Forms\Get $get): string {
                $pricingUnit = $get('pricing_unit');
                return match($pricingUnit) {
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
            ->placeholder(function (Forms\Get $get): string {
                $packagingId = $get('packaging_type_id');
                if (!$packagingId) {
                    return 'Enter amount';
                }
                return 'Enter weight or quantity';
            })
            ->suffix(function (Forms\Get $get): string {
                $pricingUnit = $get('pricing_unit');
                return match($pricingUnit) {
                    'per_g' => 'g',
                    'per_kg' => 'kg',
                    'per_lb' => 'lbs',
                    'per_oz' => 'oz',
                    'per_item' => 'units',
                    default => 'g'
                };
            })
            ->hint(function (Forms\Get $get): string {
                $packagingId = $get('packaging_type_id');
                if (!$packagingId) {
                    return 'Package-free variation';
                }
                
                $packaging = \App\Models\PackagingType::find($packagingId);
                if ($packaging && $packaging->capacity_weight) {
                    return 'Package capacity: ' . $packaging->capacity_weight . 'g';
                }
                return '';
            })
            ->required(function (Forms\Get $get): bool {
                return PriceVariationFormHelpers::isFillWeightRequired($get);
            })
            ->live();
    }

    /**
     * Get hidden tracking fields
     */
    public static function getHiddenFields(): array
    {
        return [
            Forms\Components\Hidden::make('is_name_manual')
                ->default(false),
            Forms\Components\Hidden::make('generated_name'),
        ];
    }
}