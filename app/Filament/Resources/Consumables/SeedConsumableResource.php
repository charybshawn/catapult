<?php

namespace App\Filament\Resources\Consumables;

use App\Filament\Resources\ConsumableResource;
use App\Models\ConsumableType;
use App\Models\MasterSeedCatalog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use App\Filament\Forms\Components\Common as FormCommon;

class SeedConsumableResource extends ConsumableResource
{
    protected static ?string $model = \App\Models\Consumable::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Seeds';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'seeds';
    protected static ?string $modelLabel = 'Seed';
    protected static ?string $pluralModelLabel = 'Seeds';
    
    /**
     * Override to filter for seed type only
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('consumableType', function ($query) {
                $query->where('code', 'seed');
            });
    }
    
    /**
     * Override form to implement seed-specific fields
     */
    public static function form(Form $form): Form
    {
        $isEditMode = $form->getOperation() === 'edit';
        
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        // Hidden consumable type ID (always seed)
                        Forms\Components\Hidden::make('consumable_type_id')
                            ->default(fn () => ConsumableType::where('code', 'seed')->first()?->id)
                            ->dehydrated(),
                            
                        // Supplier field
                        FormCommon::supplierSelect()
                            ->columnSpan(1),
                            
                        // Master seed catalog selection
                        Forms\Components\Select::make('master_seed_catalog_id')
                            ->label('Seed Catalog')
                            ->options(function () {
                                return MasterSeedCatalog::query()
                                    ->where('is_active', true)
                                    ->pluck('common_name', 'id')
                                    ->mapWithKeys(function ($name, $id) {
                                        return [$id => ucwords(strtolower($name))];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    // Reset cultivar when catalog changes
                                    $set('master_cultivar_id', null);
                                    $set('name', null);
                                }
                            })
                            ->columnSpan(1),
                            
                        // Cultivar selection based on selected catalog
                        Forms\Components\Select::make('master_cultivar_id')
                            ->label('Cultivar')
                            ->options(function (Forms\Get $get) {
                                $catalogId = $get('master_seed_catalog_id');
                                if (!$catalogId) return [];
                                
                                return \App\Models\MasterCultivar::where('master_seed_catalog_id', $catalogId)
                                    ->where('is_active', true)
                                    ->pluck('cultivar_name', 'id')
                                    ->mapWithKeys(function ($name, $id) {
                                        return [$id => ucwords(strtolower($name))];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $catalogId = $get('master_seed_catalog_id');
                                    if ($catalogId) {
                                        $masterCatalog = MasterSeedCatalog::find($catalogId);
                                        $masterCultivar = \App\Models\MasterCultivar::find($state);
                                        
                                        if ($masterCatalog && $masterCultivar) {
                                            $commonName = ucwords(strtolower($masterCatalog->common_name));
                                            $cultivarName = ucwords(strtolower($masterCultivar->cultivar_name));
                                            
                                            $set('name', $commonName . ' (' . $cultivarName . ')');
                                        }
                                    }
                                }
                            })
                            ->columnSpan(1),
                            
                        // Hidden field for name (set automatically)
                        Forms\Components\Hidden::make('name'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Inventory Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Initial quantity for seeds
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
                            ]),
                            
                        // Remaining quantity for existing inventory
                        Forms\Components\TextInput::make('remaining_quantity')
                            ->label('Current Remaining')
                            ->helperText('Actual weight remaining (e.g., weighed out 498g from 1000g)')
                            ->numeric()
                            ->minValue(0)
                            ->default(function (Forms\Get $get) {
                                return (float) $get('total_quantity');
                            })
                            ->step(0.001)
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                $total = (float) $get('total_quantity');
                                $remaining = (float) $state;
                                $consumed = max(0, $total - $remaining);
                                $set('consumed_quantity', $consumed);
                                
                                Log::info('Seed remaining quantity updated:', [
                                    'total' => $total,
                                    'remaining' => $remaining,
                                    'consumed' => $consumed
                                ]);
                            }),
                            
                        // Consumed quantity display
                        Forms\Components\Placeholder::make('consumed_display')
                            ->label('Amount Used')
                            ->content(function (Forms\Get $get) {
                                $total = (float) $get('total_quantity');
                                $remaining = (float) $get('remaining_quantity');
                                $consumed = max(0, $total - $remaining);
                                $unit = $get('quantity_unit') ?: 'g';
                                
                                // Calculate percentage remaining
                                $percentage = $total > 0 ? round(($remaining / $total) * 100, 1) : 0;
                                
                                return number_format($consumed, 3) . ' ' . $unit . ' used (' . $percentage . '% remaining)';
                            }),
                            
                        // Lot/batch number for seeds
                        Forms\Components\TextInput::make('lot_no')
                            ->label('Lot/Batch Number')
                            ->helperText('Optional: Batch identifier from supplier')
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
                    ]),
                
                Forms\Components\Section::make('Cost Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('cost_per_unit')
                                    ->label(fn (Forms\Get $get) => 'Cost per ' . ($get('quantity_unit') ?: 'g'))
                                    ->prefix('$')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->helperText('Cost per unit of weight'),
                                    
                                Forms\Components\TextInput::make('last_purchase_price')
                                    ->label('Last Purchase Price')
                                    ->prefix('$')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($record) => $record !== null),
                                    
                                Forms\Components\Placeholder::make('total_value')
                                    ->label('Total Inventory Value')
                                    ->content(function (Forms\Get $get) {
                                        $costPerUnit = (float) $get('cost_per_unit');
                                        $remaining = (float) $get('remaining_quantity');
                                        $value = $remaining * $costPerUnit;
                                        return '$' . number_format($value, 2);
                                    }),
                            ]),
                    ])
                    ->collapsed(),
                    
                static::getAdditionalInformationSection()
                    ->collapsed(),
            ]);
    }
    
    /**
     * Override table to show seed-specific columns
     */
    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'supplier',
                'consumableType',
                'masterSeedCatalog',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->url(fn ($record): string => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('cultivar')
                    ->label('Cultivar')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('masterSeedCatalog.common_name')
                    ->label('Master Catalog')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('lot_no')
                    ->label('Lot/Batch#')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('remaining_seed')
                    ->label('Remaining Seed')
                    ->getStateUsing(function ($record) {
                        // Calculate remaining from total_quantity minus consumed_quantity
                        $remaining = max(0, $record->total_quantity - $record->consumed_quantity);
                        return $remaining;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        return number_format($state, 3) . ' ' . $record->quantity_unit;
                    })
                    ->numeric()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->orderByRaw("(total_quantity - consumed_quantity) {$direction}")
                    )
                    ->size('sm'),
                    
                Tables\Columns\TextColumn::make('percentage_remaining')
                    ->label('% Remaining')
                    ->getStateUsing(function ($record) {
                        if (!$record->total_quantity || $record->total_quantity <= 0) return 0;
                        
                        $remaining = max(0, $record->total_quantity - $record->consumed_quantity);
                        $percentage = ($remaining / $record->total_quantity) * 100;
                        return round($percentage, 1);
                    })
                    ->formatStateUsing(fn ($state) => "{$state}%")
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state <= 10 => 'danger',
                        $state <= 25 => 'warning',
                        $state <= 50 => 'info',
                        default => 'success',
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->whereNotNull('total_quantity')
                              ->where('total_quantity', '>', 0)
                              ->orderByRaw("((total_quantity - consumed_quantity) / total_quantity * 100) {$direction}")
                    )
                    ->size('sm'),
                    
                static::getInventoryStatusColumn(),
                static::getActiveStatusBadgeColumn(),
                ...static::getTimestampColumns(),
            ])
            ->defaultSort(function (Builder $query) {
                return $query->orderByRaw('(total_quantity - consumed_quantity) ASC');
            })
            ->filters([
                // Supplier filter
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                    
                // Master catalog filter
                Tables\Filters\SelectFilter::make('master_seed_catalog_id')
                    ->label('Master Catalog')
                    ->options(fn () => MasterSeedCatalog::where('is_active', true)->pluck('common_name', 'id'))
                    ->searchable(),
                    
                // Status filters
                ...static::getInventoryFilters(),
                static::getActiveStatusFilter(),
            ])
            ->groups([
                Tables\Grouping\Group::make('name')
                    ->label('Name')
                    ->collapsible(),
                Tables\Grouping\Group::make('supplier.name')
                    ->label('Supplier')
                    ->collapsible(),
                Tables\Grouping\Group::make('masterSeedCatalog.common_name')
                    ->label('Master Catalog')
                    ->collapsible(),
            ])
            ->actions(static::getStandardTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ...static::getStandardBulkActions(),
                    ...static::getInventoryBulkActions(),
                ]),
            ])
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Consumables\SeedConsumableResource\Pages\ListSeeds::route('/'),
            'create' => \App\Filament\Resources\Consumables\SeedConsumableResource\Pages\CreateSeed::route('/create'),
            'view' => \App\Filament\Resources\Consumables\SeedConsumableResource\Pages\ViewSeed::route('/{record}'),
            'edit' => \App\Filament\Resources\Consumables\SeedConsumableResource\Pages\EditSeed::route('/{record}/edit'),
        ];
    }
}