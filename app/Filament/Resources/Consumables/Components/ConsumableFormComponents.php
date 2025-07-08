<?php

namespace App\Filament\Resources\Consumables\Components;

use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasTimestamps;

trait ConsumableFormComponents
{
    use HasActiveStatus;
    use HasTimestamps;

    /**
     * Get the basic information section that's common to all consumables
     */
    public static function getConsumableBasicInformationSection(bool $includeType = true): Forms\Components\Section
    {
        $schema = [];
        
        if ($includeType) {
            $schema[] = Forms\Components\Select::make('consumable_type_id')
                ->label('Category')
                ->options(ConsumableType::options())
                ->required()
                ->reactive()
                ->disabled(fn($operation) => $operation === 'edit')
                ->dehydrated()
                ->columnSpanFull();
        }
        
        return Forms\Components\Section::make('Basic Information')
            ->schema($schema)
            ->columns(2);
    }
    
    /**
     * Get supplier field
     */
    public static function getSupplierField(): Forms\Components\Select
    {
        return FormCommon::supplierSelect();
    }
    
    /**
     * Get active status field
     */
    public static function getActiveField(): Forms\Components\Toggle
    {
        return static::getActiveStatusField()
            ->columnSpanFull();
    }
    
    /**
     * Get cost information section
     */
    public static function getCostInformationSection(bool $alwaysVisible = false): Forms\Components\Section
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
                            ->step(0.01)
                            ->helperText(function (Get $get) {
                                $typeId = $get('consumable_type_id');
                                $type = $typeId ? ConsumableType::find($typeId) : null;
                                
                                if ($type && $type->isSeed()) {
                                    return 'Cost per ' . ($get('quantity_unit') ?: 'unit');
                                } else {
                                    $unitId = $get('consumable_unit_id');
                                    $unit = $unitId ? ConsumableUnit::find($unitId) : null;
                                    return 'Cost per ' . ($unit ? $unit->symbol : 'unit');
                                }
                            }),
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
                                $costPerUnit = (float) $get('cost_per_unit');
                                $typeId = $get('consumable_type_id');
                                $type = $typeId ? ConsumableType::find($typeId) : null;
                                
                                if ($type && $type->isSeed()) {
                                    $total = (float) $get('total_quantity');
                                    $consumed = (float) $get('consumed_quantity');
                                    $available = max(0, $total - $consumed);
                                    $value = $available * $costPerUnit;
                                } else {
                                    $initial = (float) $get('initial_stock');
                                    $consumed = (float) $get('consumed_quantity');
                                    $available = max(0, $initial - $consumed);
                                    $value = $available * $costPerUnit;
                                }
                                return '$' . number_format($value, 2);
                            }),
                    ]),
            ])
            ->collapsed()
            ->visible(function (Get $get) use ($alwaysVisible) {
                if ($alwaysVisible) {
                    return true;
                }
                $typeId = $get('consumable_type_id');
                $type = $typeId ? ConsumableType::find($typeId) : null;
                return $type && $type->isSeed();
            });
    }
    
    /**
     * Get restock settings fieldset (currently deprecated/hidden)
     */
    public static function getRestockSettings(): Forms\Components\Fieldset
    {
        return Forms\Components\Fieldset::make('Restock Settings')
            ->visible(false) // Temporarily hidden/deprecated
            ->schema([
                Forms\Components\TextInput::make('restock_threshold')
                    ->label('Restock Threshold')
                    ->helperText('When stock falls below this number, reorder')
                    ->numeric()
                    ->required()
                    ->default(5),
                Forms\Components\TextInput::make('restock_quantity')
                    ->label('Restock Quantity')
                    ->helperText('How many to order when restocking')
                    ->numeric()
                    ->required()
                    ->default(10),
            ])->columns(2);
    }
    
    /**
     * Get standard inventory fields for non-seed consumables
     */
    public static function getStandardInventoryFields(bool $isEditMode = false): array
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
                ->reactive()
                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) use ($isEditMode) {
                    if ($isEditMode) {
                        $set('current_stock_display', max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity')));
                    }
                    
                    // If unit capacity is set, also update total quantity calculation
                    if (null !== $get('quantity_per_unit') && $get('quantity_per_unit') > 0) {
                        $availableStock = $isEditMode 
                            ? max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity')) 
                            : (float)$get('initial_stock');
                        
                        $set('total_quantity', $availableStock * (float)$get('quantity_per_unit'));
                    }
                }),
            
            // Consumed quantity field (only in edit mode)
            Forms\Components\TextInput::make('consumed_quantity')
                ->label('Used Quantity')
                ->helperText('Number of units consumed')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->reactive()
                ->visible($isEditMode)
                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                    $set('current_stock_display', max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity')));
                    
                    // If unit capacity is set, also update total quantity calculation
                    if (null !== $get('quantity_per_unit') && $get('quantity_per_unit') > 0) {
                        $availableStock = max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity'));
                        $set('total_quantity', $availableStock * (float)$get('quantity_per_unit'));
                    }
                }),
            
            // Available stock display (only in edit mode)
            Forms\Components\TextInput::make('current_stock_display')
                ->label('Available Stock')
                ->helperText('Current available quantity')
                ->disabled()
                ->dehydrated(false)
                ->numeric()
                ->visible($isEditMode),
            
            // Packaging type (unit type)
            Forms\Components\Select::make('consumable_unit_id')
                ->label('Packaging Type')
                ->helperText('Container or form of packaging')
                ->options(ConsumableUnit::options())
                ->required()
                ->default(function () {
                    return ConsumableUnit::findByCode('unit')?->id;
                }),
            
            // Unit size/capacity
            Forms\Components\TextInput::make('quantity_per_unit')
                ->label('Unit Size')
                ->helperText('Capacity or size of each unit (e.g., 107L per bag)')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->step(0.01)
                ->reactive()
                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) use ($isEditMode) {
                    // Update total quantity based on available stock and unit size
                    $availableStock = $isEditMode
                        ? max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity'))
                        : (float)$get('initial_stock');
                    
                    $set('total_quantity', $availableStock * (float)$get('quantity_per_unit'));
                }),
            
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
}