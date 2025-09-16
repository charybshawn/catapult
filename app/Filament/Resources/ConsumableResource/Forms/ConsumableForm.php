<?php

namespace App\Filament\Resources\ConsumableResource\Forms;

use App\Filament\Forms\Components\Common as FormCommon;
use App\Models\ConsumableType;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Log;

class ConsumableForm
{
    /**
     * Get the complete form schema for ConsumableResource
     */
    public static function schema(): array
    {
        // Determine if we're in edit mode - use a safer approach
        $isEditMode = function ($livewire) {
            // Check if livewire has the method first, then check for record existence
            if (method_exists($livewire, 'getOperation')) {
                return $livewire->getOperation() === 'edit';
            }

            // Fallback: check if record exists (edit mode has a record)
            return isset($livewire->record) && $livewire->record !== null;
        };

        return [
            static::getBasicInformationSection($isEditMode),
            static::getInventoryDetailsSection($isEditMode),
            static::getCostInformationSection(),
            static::getAdditionalInformationSection(),
        ];
    }

    /**
     * Basic Information Section
     */
    protected static function getBasicInformationSection($isEditMode): Forms\Components\Section
    {
        return Forms\Components\Section::make('Basic Information')
            ->schema([
                Forms\Components\Select::make('consumable_type_id')
                    ->label('Category')
                    ->options(ConsumableType::options())
                    ->required()
                    ->reactive()
                    ->disabled(fn ($livewire) => $isEditMode($livewire))
                    ->dehydrated()
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($state, Set $set) {
                        $type = ConsumableType::find($state);
                        if (! $type) {
                            return;
                        }

                        // Reset packaging type when type changes
                        if (! $type->isPackaging()) {
                            $set('packaging_type_id', null);
                        }

                        // Reset mix when type changes - keeping this for backwards compatibility
                        if ($type->code !== 'mix') {
                            $set('product_mix_id', null);
                        }

                        // Also reset the name field
                        $set('name', null);
                    }),

                // Item Name Field - varies by type
                Forms\Components\Grid::make()
                    ->schema(function (Get $get, $record = null) {
                        return static::getItemNameFields($get, $record);
                    })
                    ->columnSpanFull(),

                // Supplier field moved to be beside seed entry for seed type
                Forms\Components\Grid::make()
                    ->schema(function (Get $get, $record = null) {
                        return static::getSupplierField($get, $record);
                    })->columnSpanFull(),

                // Seed catalog fields
                ...static::getSeedCatalogFields(),

                static::getActiveStatusField()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Inventory Details Section
     */
    protected static function getInventoryDetailsSection($isEditMode): Forms\Components\Section
    {
        return Forms\Components\Section::make('Inventory Details')
            ->schema([
                // Conditional form fields based on consumable type
                Forms\Components\Grid::make()
                    ->schema(function (Get $get, $record = null) use ($isEditMode) {
                        $typeId = $get('consumable_type_id') ?? $record?->consumable_type_id;
                        $type = $typeId ? ConsumableType::find($typeId) : null;

                        // For seed consumables - use remaining_quantity directly
                        if ($type && $type->isSeed()) {
                            return static::getSeedInventoryFields();
                        }

                        // For all other consumable types - use the standard inventory fields
                        return static::getStandardInventoryFields($isEditMode);
                    })
                    ->columns(3),
            ]);
    }

    /**
     * Get item name fields based on consumable type
     */
    protected static function getItemNameFields(Get $get, $record = null): array
    {
        $typeId = $get('consumable_type_id') ?? $record?->consumable_type_id;
        $type = $typeId ? ConsumableType::find($typeId) : null;

        if ($type && $type->isPackaging()) {
            return static::getPackagingTypeFields();
        } elseif ($type && $type->isSeed()) {
            return static::getSeedNameFields();
        } elseif ($type && $type->code === 'mix') {
            return static::getProductMixFields();
        } else {
            return static::getGeneralNameFields($get);
        }
    }

    /**
     * Get packaging type fields
     */
    protected static function getPackagingTypeFields(): array
    {
        return [
            Forms\Components\Select::make('packaging_type_id')
                ->label('Item Name')
                ->options(function () {
                    return \App\Models\PackagingType::where('is_active', true)
                        ->get()
                        ->mapWithKeys(function ($packagingType) {
                            return [$packagingType->id => $packagingType->display_name];
                        })
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set) {
                    // Get packaging type
                    $packagingType = \App\Models\PackagingType::find($state);

                    // Set the name field from the packaging type
                    if ($packagingType) {
                        $set('name', $packagingType->name);
                    }
                }),

            // Hidden name field for packaging types
            Forms\Components\Hidden::make('name'),
        ];
    }

    /**
     * Get seed name fields
     */
    protected static function getSeedNameFields(): array
    {
        return [
            // Combined seed catalog + cultivar field - this is the primary field for seeds
            Forms\Components\Select::make('seed_selection')
                ->label('Seed Type & Cultivar')
                ->options(\App\Models\MasterSeedCatalog::getCombinedSelectOptions())
                ->searchable()
                ->required()
                ->live(onBlur: true)
                ->afterStateHydrated(function ($component, $state, $record) {
                    // Reconstruct the seed_selection value from existing data when editing
                    if ($record && $record->master_seed_catalog_id) {
                        $catalogId = $record->master_seed_catalog_id;
                        $cultivar = $record->cultivar ?? '';
                        $combinedValue = "{$catalogId}:{$cultivar}";
                        $component->state($combinedValue);
                    }
                })
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state) {
                        $parsed = \App\Models\MasterSeedCatalog::parseCombinedValue($state);
                        
                        $set('master_seed_catalog_id', $parsed['catalog_id']);
                        $set('cultivar', $parsed['cultivar_name']);
                        $set('name', $parsed['catalog']->getDisplayNameWithCultivar($parsed['cultivar_name']));
                    }
                })
                ->columnSpanFull(),

            // Lot/batch number for seeds
            Forms\Components\TextInput::make('lot_no')
                ->label('Lot/Batch Number')
                ->helperText('Optional: Batch identifier')
                ->maxLength(100),

            // Hidden fields for storage - these get populated by the afterStateUpdated callback
            Forms\Components\Hidden::make('cultivar'),
            Forms\Components\Hidden::make('master_seed_catalog_id'),
            Forms\Components\Hidden::make('name'),
        ];
    }

    /**
     * Get product mix fields
     */
    protected static function getProductMixFields(): array
    {
        return [
            Forms\Components\Select::make('product_mix_id')
                ->label('Product Mix')
                ->helperText('Required: Please select a product mix')
                ->options(function () {
                    return \App\Models\ProductMix::where('is_active', true)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state) {
                        $mix = \App\Models\ProductMix::find($state);
                        if ($mix) {
                            $set('name', $mix->name);
                        }
                    }
                }),

            // Hidden name field - will be set from the mix
            Forms\Components\Hidden::make('name'),
        ];
    }

    /**
     * Get general name fields for other types
     */
    protected static function getGeneralNameFields(Get $get): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Item Name')
                ->required()
                ->maxLength(255)
                ->datalist(function (Get $get) {
                    // Only provide autocomplete for certain types
                    $typeId = $get('consumable_type_id');
                    $type = $typeId ? ConsumableType::find($typeId) : null;

                    if ($type && in_array($type->code, ['soil', 'label'])) {
                        return \App\Models\Consumable::where('consumable_type_id', $typeId)
                            ->where('is_active', true)
                            ->pluck('name')
                            ->unique()
                            ->toArray();
                    }

                    return [];
                }),
        ];
    }

    /**
     * Get supplier field based on type
     */
    protected static function getSupplierField(Get $get, $record = null): array
    {
        // Show supplier field for all types now
        return [
            FormCommon::supplierSelect(),
        ];
    }

    /**
     * Get seed catalog fields
     */
    protected static function getSeedCatalogFields(): array
    {
        // Seed catalog fields are now handled in getSeedNameFields() to avoid duplication
        // This method is kept for compatibility but returns empty array
        return [];
    }


    /**
     * Get seed-specific inventory fields
     */
    protected static function getSeedInventoryFields(): array
    {
        return [
            // Grid for initial quantity and unit
            Forms\Components\Grid::make(2)
                ->schema([
                    // Direct total quantity input for seeds
                    Forms\Components\TextInput::make('total_quantity')
                        ->label('Initial Quantity')
                        ->helperText('Total amount purchased/received')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(0)
                        ->step(0.001)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            // Only update remaining if it's currently empty or zero, and we have a valid state
                            if ($state && (!$get('remaining_quantity') || $get('remaining_quantity') == 0)) {
                                $set('remaining_quantity', $state);
                            }
                        }),

                    // Unit of measurement for seeds
                    Forms\Components\Select::make('quantity_unit')
                        ->label('Unit')
                        ->options([
                            'g' => 'Grams (g)',
                            'kg' => 'Kilograms (kg)',
                            'oz' => 'Ounces (oz)',
                            'lb' => 'Pounds (lb)',
                        ])
                        ->required()
                        ->default('g')
                        ->afterStateHydrated(function ($component, $state) {
                            if (!$state) {
                                $component->state('g');
                            }
                        })
                        ->live(onBlur: true),
                ])
                ->columnSpan(2),

            // Remaining quantity for existing inventory
            Forms\Components\TextInput::make('remaining_quantity')
                ->label('Current Remaining')
                ->helperText('Actual weight remaining (e.g., weighed out 498g from 1000g)')
                ->numeric()
                ->minValue(0)
                ->default(function (Get $get) {
                    return (float) $get('total_quantity');
                })
                ->step(0.001)
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                    // Only calculate if we have valid values
                    if ($state !== null && $state !== '') {
                        $total = (float) $get('total_quantity');
                        $remaining = (float) $state;
                        $consumed = max(0, $total - $remaining);
                        $set('consumed_quantity', $consumed);
                    }
                }),

            // Consumed quantity display
            Forms\Components\Placeholder::make('consumed_display')
                ->label('Amount Used')
                ->content(function (Get $get) {
                    $total = (float) $get('total_quantity');
                    $remaining = (float) $get('remaining_quantity');
                    $consumed = max(0, $total - $remaining);
                    $unit = $get('quantity_unit') ?: 'g';

                    return number_format($consumed, 3).' '.$unit.' used';
                }),



            // Hidden fields for compatibility
            Forms\Components\Hidden::make('consumed_quantity')
                ->default(0)
                ->dehydrated(),
            Forms\Components\Hidden::make('initial_stock')
                ->default(1),
            Forms\Components\Hidden::make('quantity_per_unit')
                ->default(1),
            Forms\Components\Hidden::make('consumable_unit_id')
                ->afterStateHydrated(function ($component, $state) {
                    // Default to 'unit' consumable unit for seeds
                    $unitType = \App\Models\ConsumableUnit::where('code', 'unit')->first();
                    $component->state($unitType?->id ?? 1);
                }),
            Forms\Components\Hidden::make('restock_threshold')
                ->default(0),
            Forms\Components\Hidden::make('restock_quantity')
                ->default(0),
        ];
    }

    /**
     * Get standard inventory fields for non-seed types
     */
    protected static function getStandardInventoryFields($isEditMode): array
    {
        return [
            // Quantity field
            Forms\Components\TextInput::make('initial_stock')
                ->label('Quantity')
                ->helperText('Number of units in stock')
                ->numeric()
                ->minValue(0)
                ->required()
                ->default(0)
                ->reactive(),

            // Consumed quantity field (only in edit mode)
            Forms\Components\TextInput::make('consumed_quantity')
                ->label('Used Quantity')
                ->helperText('Number of units consumed')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->reactive()
                ->visible(fn ($livewire) => $isEditMode($livewire)),

            // Available stock display (only in edit mode)
            Forms\Components\TextInput::make('current_stock_display')
                ->label('Available Stock')
                ->helperText('Current available quantity')
                ->disabled()
                ->dehydrated(false)
                ->numeric()
                ->visible(fn ($livewire) => $isEditMode($livewire)),

            // Packaging type (unit type)
            Forms\Components\Select::make('consumable_unit_id')
                ->label('Packaging Type')
                ->helperText('Container or form of packaging')
                ->options([
                    'unit' => 'Unit(s)',
                    'kg' => 'Kilograms',
                    'g' => 'Grams',
                    'l' => 'Liters',
                    'ml' => 'Milliliters',
                ])
                ->required()
                ->default('unit'),

            // Unit size/capacity
            Forms\Components\TextInput::make('quantity_per_unit')
                ->label('Unit Size')
                ->helperText('Capacity or size of each unit (e.g., 107L per bag)')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->step(0.01)
                ->reactive(),

            // Measurement unit for quantity_per_unit
            Forms\Components\Select::make('quantity_unit')
                ->label('Unit of Measurement')
                ->helperText('Unit for the size/capacity value')
                ->options([
                    'g' => 'Grams',
                    'kg' => 'Kilograms',
                    'l' => 'Liters',
                    'ml' => 'Milliliters',
                    'oz' => 'Ounces',
                    'lb' => 'Pounds',
                    'cm' => 'Centimeters',
                    'm' => 'Meters',
                ])
                ->required()
                ->default('l'),

            // Hidden field for total_quantity calculation
            Forms\Components\Hidden::make('total_quantity')
                ->default(0),
        ];
    }

    /**
     * Get active status field
     */
    protected static function getActiveStatusField(): Forms\Components\Component
    {
        return Forms\Components\Toggle::make('is_active')
            ->label('Active')
            ->default(true);
    }

    /**
     * Get cost information section
     */
    protected static function getCostInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Cost Information')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('cost_per_unit')
                            ->label('Cost per Unit')
                            ->prefix('$')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        Forms\Components\TextInput::make('last_purchase_price')
                            ->label('Last Purchase Price')
                            ->prefix('$')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                        Forms\Components\Placeholder::make('total_value')
                            ->label('Total Inventory Value')
                            ->content(function (Get $get) {
                                return '$0.00'; // Simplified for now
                            }),
                    ]),
            ])
            ->collapsed();
    }

    /**
     * Get additional information section
     */
    protected static function getAdditionalInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Additional Information')
            ->schema([
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ])
            ->collapsed();
    }
}
