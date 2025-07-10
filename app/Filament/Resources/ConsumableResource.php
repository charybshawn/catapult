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
use Illuminate\Support\Facades\Log;
use App\Filament\Tables\Components\Common as TableCommon;
use App\Filament\Traits\CsvExportAction;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStatusBadge;
use App\Filament\Traits\HasStandardActions;
use App\Filament\Traits\HasInventoryStatus;
use App\Filament\Resources\Consumables\Components\ConsumableFormComponents;
use App\Filament\Resources\Consumables\Components\ConsumableTableComponents;

class ConsumableResource extends BaseResource
{
    use CsvExportAction;
    use HasActiveStatus;
    use HasTimestamps;
    use HasStatusBadge;
    use HasStandardActions;
    use HasInventoryStatus;
    use ConsumableFormComponents;
    use ConsumableTableComponents;
    
    protected static ?string $model = Consumable::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'All Consumables';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 10; // Lower priority to appear after specialized resources

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
                            ->schema(function (Get $get, $record = null) {
                                $typeId = $get('consumable_type_id') ?? $record?->consumable_type_id;
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
                                    // For seed types, show supplier and generated name
                                    return [
                                        FormCommon::supplierSelect(),
                                        
                                        // Read-only name field that will be auto-generated
                                        Forms\Components\TextInput::make('name')
                                            ->label('Generated Name')
                                            ->readonly()
                                            ->helperText('Auto-generated from seed catalog and cultivar selection')
                                            ->placeholder('Will be generated automatically'),
                                        
                                        // Hidden cultivar field for storage
                                        Forms\Components\Hidden::make('cultivar'),
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
                            ->schema(function (Get $get, $record = null) {
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
                            })->columnSpanFull(),
                        
                        // Seed catalog field - simplified approach
                        Forms\Components\Select::make('master_seed_catalog_id')
                            ->label('Seed Catalog')
                            ->options(function () {
                                return \App\Models\MasterSeedCatalog::query()
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
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state) {
                                    $masterCatalog = \App\Models\MasterSeedCatalog::find($state);
                                    if ($masterCatalog) {
                                        // Auto-select first cultivar if none selected
                                        $cultivar = $get('cultivar');
                                        if (!$cultivar) {
                                            $cultivars = is_array($masterCatalog->cultivars) ? $masterCatalog->cultivars : [];
                                            $firstCultivar = $cultivars[0] ?? '';
                                            $set('cultivar', $firstCultivar);
                                            
                                            // Generate name immediately if we have a cultivar
                                            if ($firstCultivar) {
                                                $name = $masterCatalog->common_name . ' (' . $firstCultivar . ')';
                                                $set('name', $name);
                                                
                                                // Set master_cultivar_id
                                                $masterCultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $state)
                                                    ->where('cultivar_name', $firstCultivar)
                                                    ->first();
                                                if ($masterCultivar) {
                                                    $set('master_cultivar_id', $masterCultivar->id);
                                                }
                                            }
                                        } else {
                                            // Update name with existing cultivar
                                            $name = $masterCatalog->common_name . ' (' . $cultivar . ')';
                                            $set('name', $name);
                                            
                                            // Set master_cultivar_id
                                            $masterCultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $state)
                                                ->where('cultivar_name', $cultivar)
                                                ->first();
                                            if ($masterCultivar) {
                                                $set('master_cultivar_id', $masterCultivar->id);
                                            }
                                        }
                                    }
                                }
                            }),
                            
                        // Cultivar field - separate field for better control
                        Forms\Components\Select::make('cultivar')
                            ->label('Cultivar')
                            ->options(function (Get $get) {
                                $catalogId = $get('master_seed_catalog_id');
                                if ($catalogId) {
                                    $masterCatalog = \App\Models\MasterSeedCatalog::find($catalogId);
                                    if ($masterCatalog) {
                                        $cultivars = is_array($masterCatalog->cultivars) ? $masterCatalog->cultivars : [];
                                        return array_combine($cultivars, $cultivars);
                                    }
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
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                // Generate name from common name and cultivar
                                $catalogId = $get('master_seed_catalog_id');
                                $cultivar = $state;
                                
                                if ($catalogId && $cultivar) {
                                    $masterCatalog = \App\Models\MasterSeedCatalog::find($catalogId);
                                    if ($masterCatalog) {
                                        $name = $masterCatalog->common_name . ' (' . $cultivar . ')';
                                        $set('name', $name);
                                        
                                        // Also set the master_cultivar_id
                                        $masterCultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $catalogId)
                                            ->where('cultivar_name', $cultivar)
                                            ->first();
                                        if ($masterCultivar) {
                                            $set('master_cultivar_id', $masterCultivar->id);
                                        }
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        static::getActiveStatusField()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Inventory Details')
                    ->schema([
                        // Conditional form fields based on consumable type
                        Forms\Components\Grid::make()
                            ->schema(function (Get $get, $record = null) use ($isEditMode) {
                                $typeId = $get('consumable_type_id') ?? $record?->consumable_type_id;
                                $type = $typeId ? ConsumableType::find($typeId) : null;
                                
                                // For seed consumables - use remaining_quantity directly
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
                                                Log::info('Remaining quantity updated:', [
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
                                
                                // For all other consumable types - use the standard inventory fields
                                return static::getStandardInventoryFields($isEditMode);
                            })
                            ->columns(3),
                    ]),
                
                static::getCostInformationSection()
                    ->collapsed(),
                    
                static::getAdditionalInformationSection()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::configureCommonTable($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'supplier',
                'consumableType',
                'consumableUnit',
                'masterSeedCatalog',
                'packagingType'
            ]))
            ->columns(array_merge(
                static::getCommonTableColumns(),
                static::getSeedSpecificColumns(),
                static::getPackagingSpecificColumns()
            ))
            ->defaultSort(function (Builder $query) {
                return $query->orderByRaw('(initial_stock - consumed_quantity) ASC');
            })
            ->filters(array_merge(
                static::getTypeFilterToggles(),
                static::getCommonFilters()
            ))
            ->groups(static::getCommonGroups())
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
            'packagingType' => ['name', 'capacity_volume', 'volume_unit'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['supplier', 'masterSeedCatalog', 'packagingType'];
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