<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsumableResource\Pages;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use App\Models\PackagingType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Filament\Tables\Components\Common as TableCommon;
use App\Filament\Traits\CsvExportAction;

class ConsumableResource extends BaseResource
{
    use CsvExportAction;
    
    protected static ?string $model = Consumable::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Consumables & Supplies';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        // Determine if we're in edit mode
        $isEditMode = $form->getOperation() === 'edit';
        
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('consumable_type_id')
                            ->label('Category')
                            ->options(ConsumableType::options())
                            ->required()
                            ->reactive()
                            ->disabled($isEditMode)
                            ->dehydrated()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $type = ConsumableType::find($state);
                                if (!$type) return;
                                
                                // Reset packaging type when type changes
                                if (!$type->isPackaging()) {
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
                            ->schema(function (Get $get) {
                                $typeId = $get('consumable_type_id');
                                $type = $typeId ? ConsumableType::find($typeId) : null;
                                
                                if ($type && $type->isPackaging()) {
                                    // Dropdown for packaging types
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
                                        Forms\Components\Hidden::make('name')
                                    ];
                                } else if ($type && $type->isSeed()) {
                                    // For seed types, just show supplier since cultivar is handled separately
                                    return [
                                        FormCommon::supplierSelect(),
                                        
                                        // Hidden fields - will be set from the master cultivar selection
                                        Forms\Components\Hidden::make('name'),
                                        Forms\Components\Hidden::make('cultivar'),
                                        Forms\Components\Hidden::make('master_seed_catalog_id'),
                                    ];
                                } else if ($type && $type->code === 'mix') {
                                    // Product mix selection
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
                                            ->live()
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
                                } else {
                                    // Text input for other types
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
                                                    return Consumable::where('consumable_type_id', $typeId)
                                                        ->where('is_active', true)
                                                        ->pluck('name')
                                                        ->unique()
                                                        ->toArray();
                                                }
                                                return [];
                                            })
                                    ];
                                }
                            })
                            ->columnSpanFull(),
                        
                        // Supplier field moved to be beside seed entry for seed type
                        Forms\Components\Grid::make()
                            ->schema(function (Get $get) {
                                $typeId = $get('consumable_type_id');
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
                            })->columnSpanFull(),
                        
                        // Seed cultivar field - outside conditional logic to fix Livewire binding
                        Forms\Components\Select::make('master_cultivar_id')
                            ->label('Seed Cultivar')
                            ->options(function () {
                                return \App\Models\MasterCultivar::query()
                                    ->with('masterSeedCatalog')
                                    ->where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(function ($cultivar) {
                                        return [$cultivar->id => $cultivar->full_name];
                                    });
                            })
                            ->searchable()
                            ->visible(function (Get $get): bool {
                                $typeId = $get('consumable_type_id');
                                $type = $typeId ? ConsumableType::find($typeId) : null;
                                return $type && $type->isSeed();
                            })
                            ->required(function (Get $get): bool {
                                $typeId = $get('consumable_type_id');
                                $type = $typeId ? ConsumableType::find($typeId) : null;
                                return $type && $type->isSeed();
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $cultivar = \App\Models\MasterCultivar::find($state);
                                    if ($cultivar) {
                                        $set('name', $cultivar->full_name);
                                        $set('cultivar', $cultivar->cultivar_name);
                                        $set('master_seed_catalog_id', $cultivar->master_seed_catalog_id);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        FormCommon::activeToggle()
                            ->columnSpanFull()
                            ->inline(false),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Inventory Details')
                    ->schema([
                        // Conditional form fields based on consumable type
                        Forms\Components\Grid::make()
                            ->schema(function (Get $get) use ($isEditMode) {
                                $typeId = $get('consumable_type_id');
                                $type = $typeId ? ConsumableType::find($typeId) : null;
                                
                                // For seed consumables - simplified approach with direct total quantity
                                if ($type && $type->isSeed()) {
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
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                        // When initial quantity changes, update remaining if it hasn't been manually set
                                                        if (!$get('remaining_quantity') || $get('remaining_quantity') == 0) {
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
                                                    ->reactive(),
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
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                                $total = (float) $get('total_quantity');
                                                $remaining = (float) $state;
                                                $consumed = max(0, $total - $remaining);
                                                $set('consumed_quantity', $consumed);
                                                
                                                // Log the calculation for debugging
                                                \Illuminate\Support\Facades\Log::info('Remaining quantity updated:', [
                                                    'total' => $total,
                                                    'remaining' => $remaining,
                                                    'consumed' => $consumed
                                                ]);
                                            }),
                                            
                                        // Consumed quantity display
                                        Forms\Components\Placeholder::make('consumed_display')
                                            ->label('Amount Used')
                                            ->content(function (Get $get) {
                                                $total = (float) $get('total_quantity');
                                                $remaining = (float) $get('remaining_quantity');
                                                $consumed = max(0, $total - $remaining);
                                                $unit = $get('quantity_unit') ?: 'g';
                                                return number_format($consumed, 3) . ' ' . $unit . ' used';
                                            }),
                                            
                                            
                                        // Lot/batch number for seeds
                                        Forms\Components\TextInput::make('lot_no')
                                            ->label('Lot/Batch Number')
                                            ->helperText('Optional: Batch identifier')
                                            ->maxLength(100),
                                            
                                        // Hidden fields for compatibility
                                        Forms\Components\Hidden::make('consumed_quantity')
                                            ->default(0)
                                            ->dehydrated(),
                                        Forms\Components\Hidden::make('initial_stock')
                                            ->default(1),
                                        Forms\Components\Hidden::make('quantity_per_unit')
                                            ->default(1),
                                        Forms\Components\Hidden::make('restock_threshold')
                                            ->default(0),
                                        Forms\Components\Hidden::make('restock_quantity')
                                            ->default(0),
                                    ];
                                }
                                
                                // For all other consumable types - standard approach
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
                            })
                            ->columns(3),
                        
                        Forms\Components\Fieldset::make('Restock Settings')
                            ->visible(false) // Temporarily hidden/deprecated
                            ->schema([
                                // Info placeholder for seeds showing conversion
                                Forms\Components\Placeholder::make('restock_info')
                                    ->label('')
                                    ->content(function (Get $get) {
                                        if ($get('type') !== 'seed') {
                                            return '';
                                        }
                                        
                                        $threshold = (float)($get('restock_threshold') ?: 0);
                                        $quantity = (float)($get('restock_quantity') ?: 0);
                                        $unit = $get('quantity_unit') ?: 'g';
                                        
                                        // Convert to show in multiple units for clarity
                                        $conversions = [];
                                        
                                        if ($unit === 'kg' && $threshold > 0 && $quantity > 0) {
                                            $conversions[] = "Threshold: {$threshold} kg = " . number_format($threshold * 1000, 0) . " g";
                                            $conversions[] = "Reorder: {$quantity} kg = " . number_format($quantity * 1000, 0) . " g";
                                        } elseif ($unit === 'g' && $threshold > 0 && $quantity > 0) {
                                            $conversions[] = "Threshold: {$threshold} g = " . number_format($threshold / 1000, 3) . " kg";
                                            $conversions[] = "Reorder: {$quantity} g = " . number_format($quantity / 1000, 3) . " kg";
                                        } elseif ($unit === 'lb' && $threshold > 0 && $quantity > 0) {
                                            $conversions[] = "Threshold: {$threshold} lb = " . number_format($threshold * 453.592, 0) . " g";
                                            $conversions[] = "Reorder: {$quantity} lb = " . number_format($quantity * 453.592, 0) . " g";
                                        }
                                        
                                        if (!empty($conversions)) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">' . 
                                                '<p class="font-medium mb-1">Current Settings:</p>' .
                                                implode('<br>', array_map(fn($c) => "• {$c}", $conversions)) . 
                                                '</div>'
                                            );
                                        }
                                        
                                        return '';
                                    })
                                    ->visible(fn (Forms\Get $get) => $get('type') === 'seed' && 
                                        ((float)($get('restock_threshold') ?: 0) > 0 || (float)($get('restock_quantity') ?: 0) > 0)
                                    )
                                    ->reactive()
                                    ->columnSpanFull(),
                                    
                                Forms\Components\TextInput::make('restock_threshold')
                                    ->label(fn (Forms\Get $get) => 
                                        $get('type') === 'seed' 
                                            ? 'Restock Threshold (' . ($get('quantity_unit') ?: 'g') . ')' 
                                            : 'Restock Threshold'
                                    )
                                    ->helperText(function (Get $get) {
                                        $typeId = $get('consumable_type_id');
                                        $type = $typeId ? ConsumableType::find($typeId) : null;
                                        
                                        if ($type && $type->isSeed()) {
                                            $unit = $get('quantity_unit') ?: 'g';
                                            $unitLabel = match($unit) {
                                                'kg' => 'kilograms',
                                                'g' => 'grams',
                                                'oz' => 'ounces',
                                                'lb' => 'pounds',
                                                default => $unit
                                            };
                                            
                                            // Provide example values
                                            $example = match($unit) {
                                                'kg' => 'e.g., 0.5 for 500g or 2 for 2kg',
                                                'g' => 'e.g., 500 for 500g or 2000 for 2kg',
                                                'lb' => 'e.g., 1 for 1 pound',
                                                default => ''
                                            };
                                            
                                            return "When total weight falls below this amount in {$unitLabel}, reorder. {$example}";
                                        }
                                        return 'When stock falls below this number, reorder';
                                    })
                                    ->numeric()
                                    ->required()
                                    ->default(function (Get $get) {
                                        if ($get('type') === 'seed') {
                                            // Default based on unit
                                            return match($get('quantity_unit')) {
                                                'kg' => 0.5,  // 0.5 kg
                                                'g' => 500,   // 500 g
                                                'lb' => 1,    // 1 pound
                                                'oz' => 16,   // 16 ounces
                                                default => 500
                                            };
                                        }
                                        return 5;
                                    })
                                    ->step(fn (Forms\Get $get) => 
                                        $get('quantity_unit') === 'kg' ? 0.001 : 1
                                    )
                                    ->reactive(),
                                Forms\Components\TextInput::make('restock_quantity')
                                    ->label(function (Get $get) {
                                        $typeId = $get('consumable_type_id');
                                        $type = $typeId ? ConsumableType::find($typeId) : null;
                                        
                                        if ($type && $type->isSeed()) {
                                            return 'Restock Quantity (' . ($get('quantity_unit') ?: 'g') . ')';
                                        }
                                        return 'Restock Quantity';
                                    })
                                    ->helperText(function (Get $get) {
                                        $typeId = $get('consumable_type_id');
                                        $type = $typeId ? ConsumableType::find($typeId) : null;
                                        
                                        if ($type && $type->isSeed()) {
                                            $unit = $get('quantity_unit') ?: 'g';
                                            $unitLabel = match($unit) {
                                                'kg' => 'kilograms',
                                                'g' => 'grams',
                                                'oz' => 'ounces',
                                                'lb' => 'pounds',
                                                default => $unit
                                            };
                                            
                                            // Provide example values
                                            $example = match($unit) {
                                                'kg' => 'e.g., 1 for 1kg or 5 for 5kg',
                                                'g' => 'e.g., 1000 for 1kg or 5000 for 5kg',
                                                'lb' => 'e.g., 2.2 for 1kg',
                                                default => ''
                                            };
                                            
                                            return "Amount to order when restocking in {$unitLabel}. {$example}";
                                        }
                                        return 'How many to order when restocking';
                                    })
                                    ->numeric()
                                    ->required()
                                    ->default(function (Get $get) {
                                        $typeId = $get('consumable_type_id');
                                        $type = $typeId ? ConsumableType::find($typeId) : null;
                                        
                                        if ($type && $type->isSeed()) {
                                            // Default based on unit
                                            return match($get('quantity_unit')) {
                                                'kg' => 1,     // 1 kg
                                                'g' => 1000,   // 1000 g
                                                'lb' => 2.2,   // 2.2 pounds (1 kg)
                                                'oz' => 35.3,  // 35.3 ounces (1 kg)
                                                default => 1000
                                            };
                                        }
                                        return 10;
                                    })
                                    ->step(fn (Forms\Get $get) => 
                                        $get('quantity_unit') === 'kg' ? 0.001 : 1
                                    )
                                    ->reactive(),
                            ])->columns(2),
                    ]),
                
                Forms\Components\Section::make('Cost Information')
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
                    ->visible(function (Get $get) {
                        $typeId = $get('consumable_type_id');
                        $type = $typeId ? ConsumableType::find($typeId) : null;
                        return $type && $type->isSeed();
                    }),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        FormCommon::notesTextarea()
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'supplier',
                'consumableType',
                'consumableUnit',
                'masterSeedCatalog',
                'seedEntry',
                'packagingType'
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (Consumable $record): string => ConsumableResource::getUrl('edit', ['record' => $record]))
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->consumableType && $record->consumableType->isPackaging() && $record->packagingType) {
                            return "{$state} ({$record->packagingType->capacity_volume} {$record->packagingType->volume_unit})";
                        }
                        return $state;
                    })
                    ->color('primary'),
                Tables\Columns\TextColumn::make('consumableType.name')
                    ->label('Type')
                    ->badge()
                    ->color(function ($record): string {
                        if (!$record->consumableType) return 'gray';
                        return match ($record->consumableType->code) {
                            'packaging' => 'success',
                            'label' => 'info', 
                            'soil' => 'warning',
                            'seed' => 'primary',
                            default => 'gray',
                        };
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('masterSeedCatalog.common_name')
                    ->label('Master Catalog')
                    ->getStateUsing(function ($record) {
                        if ($record->consumableType && $record->consumableType->isSeed() && $record->masterSeedCatalog) {
                            return $record->masterSeedCatalog->common_name;
                        } elseif ($record->consumableType && $record->consumableType->isSeed() && $record->seedEntry) {
                            // Fallback for existing records
                            return $record->seedEntry->common_name . ' - ' . $record->seedEntry->cultivar_name;
                        }
                        return null;
                    })
                    ->searchable()
                    ->sortable()
                    ->visible(fn ($livewire): bool => $livewire->activeTab === null || $livewire->activeTab === 'seed')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('lot_no')
                    ->label('Lot/Batch#')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Available Quantity')
                    ->getStateUsing(fn ($record) => $record ? max(0, $record->initial_stock - $record->consumed_quantity) : 0)
                    ->numeric()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->orderByRaw("(initial_stock - consumed_quantity) {$direction}")
                    )
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record) return $state;
                        
                        // For seed consumables, show total weight
                        if ($record->consumableType && $record->consumableType->isSeed()) {
                            return "{$record->total_quantity} {$record->quantity_unit}";
                        }
                        
                        // For other types, use the consumable unit symbol
                        $displayUnit = $record->consumableUnit ? $record->consumableUnit->symbol : 'unit(s)';
                        
                        return "{$state} {$displayUnit}";
                    })
                    ->size('sm')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('remaining_seed')
                    ->label('Remaining Seed')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->consumableType || !$record->consumableType->isSeed()) return null;
                        
                        // Calculate remaining from total_quantity minus consumed_quantity in same units
                        $remaining = max(0, $record->total_quantity - $record->consumed_quantity);
                        return $remaining;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record || !$record->consumableType || !$record->consumableType->isSeed() || $state === null) return '-';
                        
                        return "{$state} {$record->quantity_unit}";
                    })
                    ->numeric()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->whereHas('consumableType', fn ($q) => $q->where('code', 'seed'))
                              ->orderByRaw("(total_quantity - consumed_quantity) {$direction}")
                    )
                    ->size('sm')
                    ->visible(fn ($livewire): bool => $livewire->activeTab === null || $livewire->activeTab === 'seed')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('percentage_remaining')
                    ->label('% Remaining')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->consumableType || !$record->consumableType->isSeed() || !$record->total_quantity || $record->total_quantity <= 0) return null;
                        
                        $remaining = max(0, $record->total_quantity - $record->consumed_quantity);
                        $percentage = ($remaining / $record->total_quantity) * 100;
                        return round($percentage, 1);
                    })
                    ->formatStateUsing(function ($state) {
                        if ($state === null) return '-';
                        return "{$state}%";
                    })
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state === null => 'gray',
                        $state <= 10 => 'danger',
                        $state <= 25 => 'warning',
                        $state <= 50 => 'info',
                        default => 'success',
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->whereHas('consumableType', fn ($q) => $q->where('code', 'seed'))
                              ->whereNotNull('total_quantity')
                              ->where('total_quantity', '>', 0)
                              ->orderByRaw("((total_quantity - consumed_quantity) / total_quantity * 100) {$direction}")
                    )
                    ->size('sm')
                    ->visible(fn ($livewire): bool => $livewire->activeTab === null || $livewire->activeTab === 'seed')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record): string => $record ? match (true) {
                        $record->isOutOfStock() => 'danger',
                        $record->needsRestock() => 'warning',
                        default => 'success',
                    } : 'gray')
                    ->formatStateUsing(fn ($record): string => $record ? match (true) {
                        $record->isOutOfStock() => 'Out of Stock',
                        $record->needsRestock() => 'Reorder Needed',
                        default => 'In Stock',
                    } : 'Unknown')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Active')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->sortable()
                    ->toggleable(),
                ...static::getTimestampColumns(),
                // Seed cultivar column removed - seed consumables now linked through SeedVariation
            ])
            ->defaultSort(function (Builder $query) {
                return $query->orderByRaw('(initial_stock - consumed_quantity) ASC');
            })
            ->filters([
                // Colorful type filter buttons
                Tables\Filters\Filter::make('seeds')
                    ->label('🌱 Seeds')
                    ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'seed')))
                    ->toggle()
                    ->indicateUsing(fn (array $data) => ($data['seeds'] ?? false) ? '🌱 Seeds' : null),
                    
                Tables\Filters\Filter::make('soil')
                    ->label('🌍 Soil & Growing Media')
                    ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'soil')))
                    ->toggle()
                    ->indicateUsing(fn (array $data) => ($data['soil'] ?? false) ? '🌍 Soil & Growing Media' : null),
                    
                Tables\Filters\Filter::make('packaging')
                    ->label('📦 Packaging')
                    ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'packaging')))
                    ->toggle()
                    ->indicateUsing(fn (array $data) => ($data['packaging'] ?? false) ? '📦 Packaging' : null),
                    
                Tables\Filters\Filter::make('labels')
                    ->label('🏷️ Labels')
                    ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'label')))
                    ->toggle()
                    ->indicateUsing(fn (array $data) => ($data['labels'] ?? false) ? '🏷️ Labels' : null),
                    
                Tables\Filters\Filter::make('other')
                    ->label('⚡ Other')
                    ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'other')))
                    ->toggle()
                    ->indicateUsing(fn (array $data) => ($data['other'] ?? false) ? '⚡ Other' : null),
                    
                // Status filters with icons
                Tables\Filters\Filter::make('needs_restock')
                    ->label('⚠️ Needs Restock')
                    ->query(fn (Builder $query) => $query->whereRaw('(total_quantity - consumed_quantity) <= restock_threshold'))
                    ->toggle()
                    ->indicateUsing(fn (array $data) => ($data['needs_restock'] ?? false) ? '⚠️ Needs Restock' : null),
                    
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('🚫 Out of Stock')
                    ->query(fn (Builder $query) => $query->whereRaw('(total_quantity - consumed_quantity) <= 0'))
                    ->toggle()
                    ->indicateUsing(fn (array $data) => ($data['out_of_stock'] ?? false) ? '🚫 Out of Stock' : null),
                    
                Tables\Filters\Filter::make('low_stock')
                    ->label('📉 Low Stock')
                    ->query(fn (Builder $query) => $query->whereRaw('(total_quantity - consumed_quantity) > 0 AND (total_quantity - consumed_quantity) <= restock_threshold'))
                    ->toggle()
                    ->indicateUsing(fn (array $data) => ($data['low_stock'] ?? false) ? '📉 Low Stock' : null),
                    
                Tables\Filters\Filter::make('inactive')
                    ->label('💤 Inactive')
                    ->query(fn (Builder $query) => $query->where('is_active', false))
                    ->toggle()
                    ->indicateUsing(fn (array $data) => ($data['inactive'] ?? false) ? '💤 Inactive' : null),
            ])
            ->groups([
                Tables\Grouping\Group::make('name')
                    ->label('Name')
                    ->collapsible(),
                Tables\Grouping\Group::make('consumableType.name')
                    ->label('Type')
                    ->collapsible(),
                Tables\Grouping\Group::make('supplier.name')
                    ->label('Supplier')
                    ->collapsible(),
            ])
            ->actions(static::getDefaultTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ...static::getDefaultBulkActions(),
                    Tables\Actions\BulkAction::make('bulk_add_stock')
                        ->label('Add Stock')
                        ->icon('heroicon-o-plus')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('Amount to Add')
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0.001)
                                ->required()
                                ->default(10),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->add((float) $data['amount']);
                            }
                        }),
                    Tables\Actions\BulkAction::make('bulk_consume_stock')
                        ->label('Consume Stock')
                        ->icon('heroicon-o-minus')
                        ->form([
                            Forms\Components\TextInput::make('amount')
                                ->label('Amount to Consume')
                                ->numeric()
                                ->step(0.001)
                                ->minValue(0.001)
                                ->required()
                                ->default(1),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->deduct((float) $data['amount']);
                            }
                        }),
                ]),
            ])
            ->headerActions([
                static::getCsvExportAction(),
            ])
            ->toggleColumnsTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Columns')
                    ->icon('heroicon-m-view-columns')
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsumables::route('/'),
            'create' => Pages\CreateConsumable::route('/create'),
            'view' => Pages\ViewConsumable::route('/{record}'),
            'edit' => Pages\EditConsumable::route('/{record}/edit'),
            'adjust-stock' => Pages\AdjustStock::route('/{record}/adjust-stock'),
        ];
    }
    
    /**
     * Define CSV export columns for Consumables
     */
    protected static function getCsvExportColumns(): array
    {
        $autoColumns = static::getColumnsFromSchema();
        
        return static::addRelationshipColumns($autoColumns, [
            'supplier' => ['name', 'email'],
            'masterSeedCatalog' => ['common_name', 'category'],
            'seedEntry' => ['common_name', 'cultivar_name'],
            'packagingType' => ['name', 'capacity_volume', 'volume_unit'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['supplier', 'masterSeedCatalog', 'seedEntry', 'packagingType'];
    }

    /**
     * Get compatible units for a consumable for unit conversion
     * 
     * @param Consumable $record The consumable record
     * @return array Compatible units
     */
    public static function getCompatibleUnits(Consumable $record): array
    {
        if (!$record->consumableUnit) {
            return ['unit' => 'Unit(s)'];
        }
        
        // Get compatible units from the same category
        $compatibleUnits = ConsumableUnit::byCategory($record->consumableUnit->category)
            ->pluck('display_name', 'code')
            ->toArray();
        
        return $compatibleUnits;
    }

    /**
     * Get human-readable label for unit
     * 
     * @param string $unit Unit code
     * @return string Human-readable unit label
     */
    public static function getUnitLabel(string $unit): string
    {
        $labels = [
            'unit' => 'Unit(s)',
            'kg' => 'Kilograms',
            'g' => 'Grams',
            'oz' => 'Ounces',
            'l' => 'Litre(s)',
            'ml' => 'Milliliters',
        ];
        
        return $labels[$unit] ?? $unit;
    }
} 