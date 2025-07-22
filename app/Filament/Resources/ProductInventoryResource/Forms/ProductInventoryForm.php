<?php

namespace App\Filament\Resources\ProductInventoryResource\Forms;

use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;

class ProductInventoryForm
{
    /**
     * Get the complete form schema for ProductInventoryResource
     */
    public static function schema(): array
    {
        return [
            static::getProductInformationSection(),
            static::getInventoryInformationSection(),
            static::getQuantityCostSection(),
            static::getAdditionalInformationSection(),
        ];
    }

    /**
     * Product information section with reactive price variation selection
     */
    protected static function getProductInformationSection(): Section
    {
        return Section::make('Product Information')
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive(),
                Forms\Components\Select::make('price_variation_id')
                    ->label('Price Variation')
                    ->relationship('priceVariation', 'name', function ($query, Forms\Get $get) {
                        $productId = $get('product_id');
                        if ($productId) {
                            return $query->where('product_id', $productId)
                                ->where('is_active', true);
                        }
                        return $query->where('is_active', true);
                    })
                    ->visible(fn (Forms\Get $get) => $get('product_id'))
                    ->required()
                    ->helperText('Select the specific price variation for this inventory batch')
                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                        // Validate that the selected variation belongs to the selected product
                        if ($state && $get('product_id')) {
                            $variation = \App\Models\PriceVariation::find($state);
                            if ($variation && $variation->product_id != $get('product_id')) {
                                $set('price_variation_id', null);
                                Notification::make()
                                    ->title('Invalid Selection')
                                    ->body('The selected price variation does not belong to the selected product.')
                                    ->danger()
                                    ->send();
                            }
                        }
                    }),
            ])
            ->columns(2);
    }

    /**
     * Inventory information section with lot number and storage details
     */
    protected static function getInventoryInformationSection(): Section
    {
        return Section::make('Inventory Information')
            ->schema([
                Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('lot_number')
                            ->label('Lot Number')
                            ->helperText('Optional supplier lot number'),
                        Forms\Components\TextInput::make('location')
                            ->label('Storage Location')
                            ->placeholder('e.g., Warehouse A, Shelf 3'),
                    ]),
                Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('production_date')
                            ->label('Production Date')
                            ->default(now()),
                        Forms\Components\DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->after('production_date'),
                    ]),
            ]);
    }

    /**
     * Quantity and cost section with dynamic step and suffix based on packaging type
     */
    protected static function getQuantityCostSection(): Section
    {
        return Section::make('Quantity & Cost')
            ->schema([
                Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(function (Forms\Get $get) {
                                $priceVariationId = $get('price_variation_id');
                                if ($priceVariationId) {
                                    $priceVariation = \App\Models\PriceVariation::find($priceVariationId);
                                    $packagingType = $priceVariation?->packagingType;
                                    return $packagingType && $packagingType->allowsDecimalQuantity() ? 0.01 : 1;
                                }
                                return 1;
                            })
                            ->suffix(function (Forms\Get $get) {
                                $priceVariationId = $get('price_variation_id');
                                if ($priceVariationId) {
                                    $priceVariation = \App\Models\PriceVariation::find($priceVariationId);
                                    $packagingType = $priceVariation?->packagingType;
                                    return $packagingType ? $packagingType->getQuantityUnit() : 'units';
                                }
                                return 'units';
                            })
                            ->reactive(),
                        Forms\Components\TextInput::make('cost_per_unit')
                            ->label('Cost per Unit')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->helperText('For calculating inventory value'),
                        Forms\Components\Select::make('product_inventory_status_id')
                            ->label('Status')
                            ->relationship('productInventoryStatus', 'name')
                            ->default(fn () => \App\Models\ProductInventoryStatus::where('code', 'active')->first()?->id)
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Additional information section with notes field
     */
    protected static function getAdditionalInformationSection(): Section
    {
        return Section::make('Additional Information')
            ->schema([
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->collapsed();
    }
}