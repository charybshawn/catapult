<?php

namespace App\Filament\Resources\ProductInventoryResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\PriceVariation;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use App\Models\ProductInventoryStatus;
use Filament\Forms\Components\Textarea;
use Filament\Forms;
use Filament\Notifications\Notification;

/**
 * ProductInventoryForm for Agricultural Product Inventory Management
 * 
 * Provides comprehensive form functionality for managing agricultural product inventory
 * with specialized handling for microgreens production including lot tracking, expiration
 * dates, and packaging-aware quantity management. Integrates with price variations to
 * ensure inventory accuracy and proper cost accounting in agricultural operations.
 * 
 * @filament_component Form schema builder for ProductInventoryResource
 * @business_domain Agricultural product inventory with batch tracking and expiration management
 * @agricultural_focus Microgreens inventory with production dates, lot numbers, and storage locations
 * 
 * @inventory_features Lot tracking, production/expiration dates, location management for perishables
 * @pricing_integration Price variation selection with packaging-aware quantity handling
 * @business_validation Product-variation compatibility checking and reactive field updates
 * 
 * @agricultural_context Handles perishable inventory with time-sensitive tracking requirements
 * @packaging_awareness Dynamic quantity steps and units based on packaging type (decimal vs whole)
 * @related_models Product, PriceVariation, ProductInventoryStatus for complete inventory context
 */
class ProductInventoryForm
{
    /**
     * Get the complete form schema for agricultural product inventory management.
     * 
     * Assembles comprehensive form sections including product selection with price
     * variation integration, inventory tracking fields, quantity/cost management,
     * and additional information for agricultural business operations.
     * 
     * @return array Complete Filament form schema for agricultural inventory management
     * @form_sections Product info, inventory tracking, quantity/cost, additional notes
     * @agricultural_workflow Supports complete inventory lifecycle for perishable products
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
     * Product information section with reactive price variation selection for agricultural products.
     * 
     * Creates integrated product and price variation selection with agricultural business
     * logic validation. Ensures selected variations belong to the chosen product and
     * provides reactive filtering for agricultural inventory accuracy.
     * 
     * @return Section Product selection with price variation integration and validation
     * @agricultural_validation Prevents mismatched product-variation combinations
     * @business_logic Price variations filtered by product selection for inventory accuracy
     * @reactive_behavior Dynamic variation options based on product selection
     */
    protected static function getProductInformationSection(): Section
    {
        return Section::make('Product Information')
            ->schema([
                Select::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive(),
                Select::make('price_variation_id')
                    ->label('Price Variation')
                    ->relationship('priceVariation', 'name', function ($query, Get $get) {
                        $productId = $get('product_id');
                        if ($productId) {
                            return $query->where('product_id', $productId)
                                ->where('is_active', true);
                        }
                        return $query->where('is_active', true);
                    })
                    ->visible(fn (Get $get) => $get('product_id'))
                    ->required()
                    ->helperText('Select the specific price variation for this inventory batch')
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        // Validate that the selected variation belongs to the selected product
                        if ($state && $get('product_id')) {
                            $variation = PriceVariation::find($state);
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
     * Inventory information section with agricultural batch tracking and storage management.
     * 
     * Provides essential fields for tracking agricultural product batches including
     * optional lot numbers from suppliers, storage location tracking, and production/
     * expiration date management critical for perishable microgreens inventory.
     * 
     * @return Section Inventory tracking fields for agricultural batch management
     * @agricultural_tracking Lot numbers, production dates, expiration dates for perishables
     * @storage_management Location tracking for warehouse and storage organization
     * @perishable_focus Production and expiration dates essential for microgreens operations
     */
    protected static function getInventoryInformationSection(): Section
    {
        return Section::make('Inventory Information')
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('lot_number')
                            ->label('Lot Number')
                            ->helperText('Optional supplier lot number'),
                        TextInput::make('location')
                            ->label('Storage Location')
                            ->placeholder('e.g., Warehouse A, Shelf 3'),
                    ]),
                Grid::make(2)
                    ->schema([
                        DatePicker::make('production_date')
                            ->label('Production Date')
                            ->default(now()),
                        DatePicker::make('expiration_date')
                            ->label('Expiration Date')
                            ->after('production_date'),
                    ]),
            ]);
    }

    /**
     * Quantity and cost section with packaging-aware agricultural measurements.
     * 
     * Provides intelligent quantity input with dynamic steps and units based on
     * packaging type from price variations. Handles both whole unit packaging
     * (clamshells) and decimal quantities (bulk) common in agricultural operations.
     * 
     * @return Section Quantity and cost fields with packaging-aware validation
     * @agricultural_measurements Dynamic units and steps based on packaging type
     * @packaging_integration Decimal steps for bulk, whole steps for packaged products
     * @cost_tracking Per-unit costing for agricultural inventory valuation
     */
    protected static function getQuantityCostSection(): Section
    {
        return Section::make('Quantity & Cost')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->step(function (Get $get) {
                                $priceVariationId = $get('price_variation_id');
                                if ($priceVariationId) {
                                    $priceVariation = PriceVariation::find($priceVariationId);
                                    $packagingType = $priceVariation?->packagingType;
                                    return $packagingType && $packagingType->allowsDecimalQuantity() ? 0.01 : 1;
                                }
                                return 1;
                            })
                            ->suffix(function (Get $get) {
                                $priceVariationId = $get('price_variation_id');
                                if ($priceVariationId) {
                                    $priceVariation = PriceVariation::find($priceVariationId);
                                    $packagingType = $priceVariation?->packagingType;
                                    return $packagingType ? $packagingType->getQuantityUnit() : 'units';
                                }
                                return 'units';
                            })
                            ->reactive(),
                        TextInput::make('cost_per_unit')
                            ->label('Cost per Unit')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->helperText('For calculating inventory value'),
                        Select::make('product_inventory_status_id')
                            ->label('Status')
                            ->relationship('productInventoryStatus', 'name')
                            ->default(fn () => ProductInventoryStatus::where('code', 'active')->first()?->id)
                            ->required(),
                    ]),
            ]);
    }

    /**
     * Additional information section for agricultural inventory notes and documentation.
     * 
     * Provides optional notes field for documenting special conditions, quality
     * observations, or other relevant information about agricultural product batches.
     * Collapsed by default to maintain clean form interface.
     * 
     * @return Section Optional notes section for agricultural batch documentation
     * @agricultural_documentation Quality notes, special conditions, batch observations
     * @ui_design Collapsed by default for clean form presentation
     */
    protected static function getAdditionalInformationSection(): Section
    {
        return Section::make('Additional Information')
            ->schema([
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->collapsed();
    }
}