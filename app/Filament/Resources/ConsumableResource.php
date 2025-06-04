<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsumableResource\Pages;
use App\Models\Consumable;
use App\Models\PackagingType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Filament\Tables\Components\Common as TableCommon;

class ConsumableResource extends BaseResource
{
    protected static ?string $model = Consumable::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Consumables & Supplies';
    protected static ?string $navigationGroup = 'Inventory & Materials';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        // Determine if we're in edit mode
        $isEditMode = $form->getOperation() === 'edit';
        
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Category')
                            ->options([
                                'seed' => 'Seed',
                                'soil' => 'Soil',
                                'packaging' => 'Packaging',
                                'mix' => 'Product Mix',
                                'label' => 'Label',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->reactive()
                            ->disabled($isEditMode)
                            ->dehydrated()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Reset packaging type when type changes
                                if ($state !== 'packaging') {
                                    $set('packaging_type_id', null);
                                }
                                
                                // Reset mix when type changes
                                if ($state !== 'mix') {
                                    $set('product_mix_id', null);
                                }
                                
                                // Also reset the name field
                                $set('name', null);
                            }),

                        // Item Name Field - varies by type
                        Forms\Components\Grid::make()
                            ->schema(function (Forms\Get $get) {
                                if ($get('type') === 'packaging') {
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
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
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
                                } else if ($get('type') === 'seed') {
                                    // Simple, explicit seed variety selection
                                    return [
                                        Forms\Components\Select::make('seed_variety_id')
                                            ->label('Seed Variety')
                                            ->helperText('Required: Please select a seed variety')
                                            ->options(function () {
                                                // Get unique seed varieties by name, preferring older IDs
                                                $options = \App\Models\SeedVariety::where('is_active', true)
                                                    ->orderBy('id', 'asc')
                                                    ->get()
                                                    ->groupBy('name')
                                                    ->map(function ($group) {
                                                        // Use the first (oldest) record for each name
                                                        return $group->first();
                                                    })
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                                    
                                                return $options;
                                            })
                                            ->searchable()
                                            ->required()
                                            ->live() // Make the field live to update instantly
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Variety Name')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('crop_type')
                                                    ->label('Crop Type')
                                                    ->default('microgreens')
                                                    ->maxLength(255),
                                                Forms\Components\Toggle::make('is_active')
                                                    ->label('Active')
                                                    ->default(true),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                return \App\Models\SeedVariety::create($data)->id;
                                            })
                                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                                return $action
                                                    ->modalHeading('Create Seed Variety')
                                                    ->modalSubmitActionLabel('Create Seed Variety')
                                                    ->modalWidth('lg');
                                            })
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $seedVariety = \App\Models\SeedVariety::find($state);
                                                    if ($seedVariety) {
                                                        $set('name', $seedVariety->name);
                                                    }
                                                }
                                            }),
                                            
                                        // Hidden name field - will be set from the seed variety
                                        Forms\Components\Hidden::make('name'),
                                    ];
                                } else if ($get('type') === 'mix') {
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
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
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
                                            ->datalist(function (Forms\Get $get) {
                                                // Only provide autocomplete for certain types
                                                if (in_array($get('type'), ['soil', 'label'])) {
                                                    return Consumable::where('type', $get('type'))
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
                        
                        FormCommon::supplierSelect(),
                        
                        FormCommon::activeToggle()
                            ->columnSpanFull()
                            ->inline(false),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Inventory Details')
                    ->schema([
                        // Conditional form fields based on consumable type
                        Forms\Components\Grid::make()
                            ->schema(function (Forms\Get $get) use ($isEditMode) {
                                $type = $get('type');
                                
                                // For seed consumables - simplified approach with direct total quantity
                                if ($type === 'seed') {
                                    return [
                                        // Direct total quantity input for seeds
                                        Forms\Components\TextInput::make('total_quantity')
                                            ->label('Total Weight')
                                            ->helperText('Total weight of seed (e.g., 3 for 3 KG)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->required()
                                            ->default(0)
                                            ->step(0.001),
                                            
                                        // Unit of measurement for seeds
                                        Forms\Components\Select::make('quantity_unit')
                                            ->label('Unit of Measurement')
                                            ->helperText('Unit for the total weight')
                                            ->options([
                                                'g' => 'Grams',
                                                'kg' => 'Kilograms',
                                                'oz' => 'Ounces',
                                                'lb' => 'Pounds',
                                            ])
                                            ->required()
                                            ->default('g'),
                                            
                                        // Lot/batch number for seeds
                                        Forms\Components\TextInput::make('lot_no')
                                            ->label('Lot/Batch Number')
                                            ->helperText('Batch identifier for this seed (will be stored as uppercase)')
                                            ->maxLength(100),
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
                                    Forms\Components\Select::make('unit')
                                        ->label('Packaging Type')
                                        ->helperText('Container or form of packaging')
                                        ->options([
                                            'unit' => 'Unit(s)',
                                            'bag' => 'Bag(s)',
                                            'box' => 'Box(es)',
                                            'bottle' => 'Bottle(s)',
                                            'container' => 'Container(s)',
                                            'roll' => 'Roll(s)',
                                            'packet' => 'Packet(s)',
                                            'kg' => 'Kilogram(s)',
                                            'g' => 'Gram(s)',
                                            'l' => 'Liter(s)',
                                            'ml' => 'Milliliter(s)',
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
                            ->schema([
                                Forms\Components\TextInput::make('restock_threshold')
                                    ->label('Restock Threshold')
                                    ->helperText(function (Forms\Get $get) {
                                        return $get('type') === 'seed' 
                                            ? 'When total quantity falls below this amount, reorder' 
                                            : 'When stock falls below this number, reorder';
                                    })
                                    ->numeric()
                                    ->required()
                                    ->default(function (Forms\Get $get) {
                                        return $get('type') === 'seed' ? 500 : 5;
                                    }),
                                Forms\Components\TextInput::make('restock_quantity')
                                    ->label('Restock Quantity')
                                    ->helperText(function (Forms\Get $get) {
                                        return $get('type') === 'seed'
                                            ? 'How much to order when restocking'
                                            : 'How many to order when restocking';
                                    })
                                    ->numeric()
                                    ->required()
                                    ->default(function (Forms\Get $get) {
                                        return $get('type') === 'seed' ? 1000 : 10;
                                    }),
                            ])->columns(2),
                    ]),
                
                Forms\Components\Section::make('Costs')
                    ->schema([
                        FormCommon::priceInput('cost_per_unit', 'Cost Per Unit')
                            ->helperText('How much each unit costs to purchase'),
                        FormCommon::notesTextarea()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn (Consumable $record): string => ConsumableResource::getUrl('edit', ['record' => $record]))
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->type === 'packaging' && $record->packagingType) {
                            return "{$state} ({$record->packagingType->capacity_volume} {$record->packagingType->volume_unit})";
                        }
                        return $state;
                    })
                    ->color('primary'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'packaging' => 'success',
                        'label' => 'info',
                        'soil' => 'warning',
                        'seed' => 'primary',
                        default => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
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
                        if ($record->type === 'seed') {
                            return "{$record->total_quantity} {$record->quantity_unit}";
                        }
                        
                        // For other types, show units as before
                        $unitMap = [
                            'l' => 'litre(s)',
                            'g' => 'gram(s)',
                            'kg' => 'kilogram(s)',
                            'oz' => 'ounce(s)',
                            'unit' => 'unit(s)',
                        ];
                        
                        $displayUnit = $unitMap[$record->unit] ?? $record->unit;
                        
                        return "{$state} {$displayUnit}";
                    })
                    ->size('sm')
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
                static::getActiveBadgeColumn(),
                ...static::getTimestampColumns(),
                Tables\Columns\TextColumn::make('seedVariety.name')
                    ->label('Variety')
                    ->description(fn (Model $record): ?string => 
                        $record->seedVariety ? "({$record->seedVariety->crop_type})" : null
                    )
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn ($livewire): bool => $livewire->activeTab === null || $livewire->activeTab === 'seed'),
            ])
            ->defaultSort(function (Builder $query) {
                return $query->orderByRaw('(initial_stock - consumed_quantity) ASC');
            })
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'packaging' => 'Packaging',
                        'label' => 'Labels',
                        'soil' => 'Soil',
                        'seed' => 'Seeds',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('needs_restock')
                    ->label('Needs Restock')
                    ->query(fn (Builder $query) => $query->whereRaw('initial_stock - consumed_quantity <= restock_threshold')),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->whereRaw('initial_stock <= consumed_quantity')),
                Tables\Filters\Filter::make('inactive')
                    ->label('Inactive')
                    ->query(fn (Builder $query) => $query->where('is_active', false)),
                // Add seed variety filter
                Tables\Filters\SelectFilter::make('seed_variety_id')
                    ->label('Seed Variety')
                    ->relationship('seedVariety', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($livewire): bool => $livewire->activeTab === null || $livewire->activeTab === 'seed'),
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
     * Get compatible units for a consumable for unit conversion
     * 
     * @param Consumable $record The consumable record
     * @return array Compatible units
     */
    public static function getCompatibleUnits(Consumable $record): array
    {
        // Base units always include the record's own unit
        $units = [$record->unit => self::getUnitLabel($record->unit)];
        
        // Add weight-based compatible units
        if ($record->unit === 'kg') {
            $units['g'] = 'Grams';
        } else if ($record->unit === 'g') {
            $units['kg'] = 'Kilograms';
        }
        
        // Add volume-based compatible units
        if ($record->unit === 'l') {
            $units['ml'] = 'Milliliters';
        } else if ($record->unit === 'ml') {
            $units['l'] = 'Liters';
        }
        
        return $units;
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