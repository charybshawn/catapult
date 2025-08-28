<?php

namespace App\Filament\Resources\ConsumableResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\PackagingType;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use App\Models\ProductMix;
use App\Models\Consumable;
use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Component;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Models\ConsumableType;
use Filament\Forms;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive consumable inventory form builder for agricultural supply management.
 *
 * Provides sophisticated form configuration for managing diverse agricultural
 * consumables including seeds, soil, packaging, and production supplies. Features
 * dynamic field generation based on consumable type, intelligent name generation,
 * seed catalog integration, and specialized inventory tracking for different
 * supply categories in microgreens production operations.
 *
 * @filament_form Complex form builder for agricultural consumable inventory
 * @business_domain Agricultural supply chain and inventory management
 * @inventory_types Seeds, soil, packaging, mixes, and production consumables
 * @dynamic_behavior Context-aware field generation based on consumable type
 * @agricultural_integration Seed catalog linkage and cultivar management
 */
class ConsumableForm
{
    /**
     * Generate comprehensive form schema for agricultural consumable management.
     *
     * Creates complete form structure with dynamic sections based on consumable
     * type, including basic information, inventory details, cost information,
     * and additional notes. Adapts field visibility and requirements based on
     * agricultural supply category (seeds, soil, packaging, etc.).
     *
     * @return array Complete Filament form schema for consumable management
     * @form_sections Basic info, inventory details, cost info, additional notes
     * @dynamic_structure Adapts to different consumable types and requirements
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
     * Generate basic information section for consumable identification and categorization.
     *
     * Creates comprehensive section for consumable type selection, name generation,
     * supplier information, and seed catalog integration. Features dynamic field
     * behavior based on consumable category with specialized handling for seeds,
     * packaging, mixes, and general supplies.
     *
     * @param callable $isEditMode Function to determine if form is in edit mode
     * @return Section Filament form section with basic consumable information
     * @agricultural_types Seeds, soil, packaging, mixes, and production supplies
     * @dynamic_fields Context-aware field generation and validation
     */
    protected static function getBasicInformationSection($isEditMode): Section
    {
        return Section::make('Basic Information')
            ->schema([
                Select::make('consumable_type_id')
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
                Grid::make()
                    ->schema(function (Get $get, $record = null) {
                        return static::getItemNameFields($get, $record);
                    })
                    ->columnSpanFull(),

                // Supplier field moved to be beside seed entry for seed type
                Grid::make()
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
     * Generate inventory details section for specialized stock management.
     *
     * Creates inventory section with specialized fields for different consumable
     * types. Seeds use weight-based tracking with remaining quantities, while
     * other supplies use unit-based inventory with packaging information and
     * consumption tracking.
     *
     * @param callable $isEditMode Function to determine if form is in edit mode
     * @return Section Filament form section with inventory management fields
     * @inventory_specialization Seeds use weight tracking, others use unit counting
     * @consumption_tracking Usage monitoring and remaining stock calculations
     */
    protected static function getInventoryDetailsSection($isEditMode): Section
    {
        return Section::make('Inventory Details')
            ->schema([
                // Conditional form fields based on consumable type
                Grid::make()
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
     * Generate dynamic item name fields based on consumable type selection.
     *
     * Creates context-appropriate name fields for different agricultural
     * consumable categories. Packaging types use dropdown selection, seeds
     * use auto-generated names from catalog integration, mixes use product
     * mix selection, and general supplies use manual text input.
     *
     * @param Get $get Form state getter for dynamic field generation
     * @param mixed $record Existing record for edit operations
     * @return array Dynamic field configuration based on consumable type
     * @field_types Packaging (dropdown), seeds (auto-gen), mixes (selection), general (text)
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
            Select::make('packaging_type_id')
                ->label('Item Name')
                ->options(function () {
                    return PackagingType::where('is_active', true)
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
                    $packagingType = PackagingType::find($state);

                    // Set the name field from the packaging type
                    if ($packagingType) {
                        $set('name', $packagingType->name);
                    }
                }),

            // Hidden name field for packaging types
            Hidden::make('name'),
        ];
    }

    /**
     * Get seed name fields
     */
    protected static function getSeedNameFields(): array
    {
        return [
            FormCommon::supplierSelect(),

            // Read-only name field that will be auto-generated
            TextInput::make('name')
                ->label('Generated Name')
                ->readonly()
                ->helperText('Auto-generated from seed catalog and cultivar selection')
                ->placeholder('Will be generated automatically'),

            // Hidden cultivar field for storage
            Hidden::make('cultivar'),
        ];
    }

    /**
     * Get product mix fields
     */
    protected static function getProductMixFields(): array
    {
        return [
            Select::make('product_mix_id')
                ->label('Product Mix')
                ->helperText('Required: Please select a product mix')
                ->options(function () {
                    return ProductMix::where('is_active', true)
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state) {
                        $mix = ProductMix::find($state);
                        if ($mix) {
                            $set('name', $mix->name);
                        }
                    }
                }),

            // Hidden name field - will be set from the mix
            Hidden::make('name'),
        ];
    }

    /**
     * Get general name fields for other types
     */
    protected static function getGeneralNameFields(Get $get): array
    {
        return [
            TextInput::make('name')
                ->label('Item Name')
                ->required()
                ->maxLength(255)
                ->datalist(function (Get $get) {
                    // Only provide autocomplete for certain types
                    $typeId = $get('consumable_type_id');
                    $type = $typeId ? ConsumableType::find($typeId) : null;

                    if ($type && in_array($type->code, ['soil', 'label'])) {
                        return Consumable::where('consumable_type_id', $typeId)
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
        $typeId = $get('consumable_type_id') ?? $record?->consumable_type_id;
        $type = $typeId ? ConsumableType::find($typeId) : null;

        if ($type && $type->isSeed()) {
            // For seed type, supplier is already in the grid with master_seed_catalog_id
            return [];
        } else {
            // For other types, show supplier field here
            return [
                FormCommon::supplierSelect(),
            ];
        }
    }

    /**
     * Generate seed catalog integration fields for agricultural seed management.
     *
     * Creates comprehensive seed selection fields including master seed catalog
     * dropdown and cultivar selection with automatic name generation. Features
     * dynamic cultivar options based on catalog selection and intelligent
     * name formatting for agricultural seed identification.
     *
     * @return array Filament form fields for seed catalog integration
     * @agricultural_integration Master catalog and cultivar relationship management
     * @name_generation Automatic seed name creation from catalog + cultivar
     */
    protected static function getSeedCatalogFields(): array
    {
        return [
            // Seed catalog field - simplified approach
            Select::make('master_seed_catalog_id')
                ->label('Seed Catalog')
                ->options(function () {
                    return MasterSeedCatalog::query()
                        ->where('is_active', true)
                        ->pluck('common_name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->visible(function (Get $get, $record = null): bool {
                    $typeId = $get('consumable_type_id') ?? $record?->consumable_type_id;
                    $type = $typeId ? ConsumableType::find($typeId) : null;

                    return $type && $type->isSeed();
                })
                ->required(function (Get $get, $record = null): bool {
                    $typeId = $get('consumable_type_id') ?? $record?->consumable_type_id;
                    $type = $typeId ? ConsumableType::find($typeId) : null;

                    return $type && $type->isSeed();
                })
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if ($state) {
                        $masterCatalog = MasterSeedCatalog::find($state);
                        if ($masterCatalog) {
                            static::handleSeedCatalogUpdate($masterCatalog, $set, $get);
                        }
                    }
                }),

            // Cultivar field - now uses proper relationships
            Select::make('master_cultivar_id')
                ->label('Cultivar')
                ->options(function (Get $get) {
                    $catalogId = $get('master_seed_catalog_id');
                    if ($catalogId) {
                        return MasterCultivar::where('master_seed_catalog_id', $catalogId)
                            ->where('is_active', true)
                            ->pluck('cultivar_name', 'id')
                            ->toArray();
                    }

                    return [];
                })
                ->searchable()
                ->visible(function (Get $get, $record = null): bool {
                    $typeId = $get('consumable_type_id') ?? $record?->consumable_type_id;
                    $type = $typeId ? ConsumableType::find($typeId) : null;

                    return $type && $type->isSeed();
                })
                ->required(function (Get $get, $record = null): bool {
                    $typeId = $get('consumable_type_id') ?? $record?->consumable_type_id;
                    $type = $typeId ? ConsumableType::find($typeId) : null;

                    return $type && $type->isSeed();
                })
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    // Generate name from common name and cultivar
                    $catalogId = $get('master_seed_catalog_id');
                    $cultivarId = $state;

                    if ($catalogId && $cultivarId) {
                        $masterCatalog = MasterSeedCatalog::find($catalogId);
                        $masterCultivar = MasterCultivar::find($cultivarId);
                        
                        if ($masterCatalog && $masterCultivar) {
                            $name = $masterCatalog->common_name.' ('.$masterCultivar->cultivar_name.')';
                            $set('name', $name);
                            // Also set the cultivar string for backwards compatibility if needed
                            $set('cultivar', $masterCultivar->cultivar_name);
                        }
                    }
                })
                ->columnSpanFull(),
        ];
    }

    /**
     * Handle seed catalog selection update
     */
    protected static function handleSeedCatalogUpdate($masterCatalog, Set $set, Get $get): void
    {
        // Auto-select first cultivar if none selected
        $cultivarId = $get('master_cultivar_id');
        if (! $cultivarId) {
            // Get first active cultivar from the relationship
            $firstCultivar = MasterCultivar::where('master_seed_catalog_id', $masterCatalog->id)
                ->where('is_active', true)
                ->first();
            
            if ($firstCultivar) {
                $set('master_cultivar_id', $firstCultivar->id);
                $set('cultivar', $firstCultivar->cultivar_name);
                $name = $masterCatalog->common_name.' ('.$firstCultivar->cultivar_name.')';
                $set('name', $name);
            }
        } else {
            // Update name with existing cultivar
            $masterCultivar = MasterCultivar::find($cultivarId);
            if ($masterCultivar) {
                $name = $masterCatalog->common_name.' ('.$masterCultivar->cultivar_name.')';
                $set('name', $name);
                $set('cultivar', $masterCultivar->cultivar_name);
            }
        }
    }

    /**
     * Generate specialized inventory fields for agricultural seed weight tracking.
     *
     * Creates weight-based inventory management for seeds including initial
     * quantity, remaining quantity, consumption calculation, and lot tracking.
     * Features automatic consumption calculation and precision decimal support
     * for accurate agricultural seed inventory management.
     *
     * @return array Filament form fields for seed weight inventory management
     * @weight_tracking Initial, remaining, and consumed quantity calculations
     * @agricultural_precision Decimal support for gram-level accuracy
     */
    protected static function getSeedInventoryFields(): array
    {
        return [
            // Grid for initial quantity and unit
            Grid::make(2)
                ->schema([
                    // Direct total quantity input for seeds
                    TextInput::make('total_quantity')
                        ->label('Initial Quantity')
                        ->helperText('Total amount purchased/received')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(0)
                        ->step(0.001)
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            // When initial quantity changes, update remaining if it hasn't been manually set
                            if (! $get('remaining_quantity') || $get('remaining_quantity') == 0) {
                                $set('remaining_quantity', $state);
                            }
                        }),

                    // Unit of measurement for seeds
                    Select::make('quantity_unit')
                        ->label('Unit')
                        ->options([
                            'g' => 'Grams (g)',
                            'kg' => 'Kilograms (kg)',
                            'oz' => 'Ounces (oz)',
                            'lb' => 'Pounds (lb)',
                        ])
                        ->required()
                        ->default('g')
                        ->reactive(),
                ])
                ->columnSpan(2),

            // Remaining quantity for existing inventory
            TextInput::make('remaining_quantity')
                ->label('Current Remaining')
                ->helperText('Actual weight remaining (e.g., weighed out 498g from 1000g)')
                ->numeric()
                ->minValue(0)
                ->default(function (Get $get) {
                    return (float) $get('total_quantity');
                })
                ->step(0.001)
                ->reactive()
                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                    $total = (float) $get('total_quantity');
                    $remaining = (float) $state;
                    $consumed = max(0, $total - $remaining);
                    $set('consumed_quantity', $consumed);

                    // Log the calculation for debugging
                    Log::info('Remaining quantity updated:', [
                        'total' => $total,
                        'remaining' => $remaining,
                        'consumed' => $consumed,
                    ]);
                }),

            // Consumed quantity display
            Placeholder::make('consumed_display')
                ->label('Amount Used')
                ->content(function (Get $get) {
                    $total = (float) $get('total_quantity');
                    $remaining = (float) $get('remaining_quantity');
                    $consumed = max(0, $total - $remaining);
                    $unit = $get('quantity_unit') ?: 'g';

                    return number_format($consumed, 3).' '.$unit.' used';
                }),

            // Lot/batch number for seeds
            TextInput::make('lot_no')
                ->label('Lot/Batch Number')
                ->helperText('Optional: Batch identifier')
                ->maxLength(100),

            // Hidden fields for compatibility
            Hidden::make('consumed_quantity')
                ->default(0)
                ->dehydrated(),
            Hidden::make('initial_stock')
                ->default(1),
            Hidden::make('quantity_per_unit')
                ->default(1),
            Hidden::make('restock_threshold')
                ->default(0),
            Hidden::make('restock_quantity')
                ->default(0),
        ];
    }

    /**
     * Generate standard inventory fields for unit-based agricultural supplies.
     *
     * Creates comprehensive inventory management for non-seed consumables
     * including quantity tracking, consumption monitoring, packaging type
     * selection, and unit size specifications. Supports diverse agricultural
     * supply categories with flexible measurement units.
     *
     * @param callable $isEditMode Function to determine field visibility in edit mode
     * @return array Filament form fields for unit-based inventory management
     * @supply_tracking Soil, packaging, labels, and production consumables
     * @measurement_flexibility Multiple unit types and capacity specifications
     */
    protected static function getStandardInventoryFields($isEditMode): array
    {
        return [
            // Quantity field
            TextInput::make('initial_stock')
                ->label('Quantity')
                ->helperText('Number of units in stock')
                ->numeric()
                ->minValue(0)
                ->required()
                ->default(0)
                ->reactive(),

            // Consumed quantity field (only in edit mode)
            TextInput::make('consumed_quantity')
                ->label('Used Quantity')
                ->helperText('Number of units consumed')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->reactive()
                ->visible(fn ($livewire) => $isEditMode($livewire)),

            // Available stock display (only in edit mode)
            TextInput::make('current_stock_display')
                ->label('Available Stock')
                ->helperText('Current available quantity')
                ->disabled()
                ->dehydrated(false)
                ->numeric()
                ->visible(fn ($livewire) => $isEditMode($livewire)),

            // Packaging type (unit type)
            Select::make('consumable_unit_id')
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
            TextInput::make('quantity_per_unit')
                ->label('Unit Size')
                ->helperText('Capacity or size of each unit (e.g., 107L per bag)')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->step(0.01)
                ->reactive(),

            // Measurement unit for quantity_per_unit
            Select::make('quantity_unit')
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
            Hidden::make('total_quantity')
                ->default(0),
        ];
    }

    /**
     * Get active status field
     */
    protected static function getActiveStatusField(): Component
    {
        return Toggle::make('is_active')
            ->label('Active')
            ->default(true);
    }

    /**
     * Get cost information section
     */
    protected static function getCostInformationSection(): Section
    {
        return Section::make('Cost Information')
            ->schema([
                Grid::make(3)
                    ->schema([
                        TextInput::make('cost_per_unit')
                            ->label('Cost per Unit')
                            ->prefix('$')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                        TextInput::make('last_purchase_price')
                            ->label('Last Purchase Price')
                            ->prefix('$')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),
                        Placeholder::make('total_value')
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
    protected static function getAdditionalInformationSection(): Section
    {
        return Section::make('Additional Information')
            ->schema([
                Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ])
            ->collapsed();
    }
}
