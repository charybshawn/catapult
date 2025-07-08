<?php

namespace App\Filament\Resources\Consumables;

use App\Filament\Resources\Consumables\PackagingConsumableResource\Pages;
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
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use Illuminate\Support\Facades\Log;
use App\Filament\Traits\CsvExportAction;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStatusBadge;
use App\Filament\Traits\HasStandardActions;
use App\Filament\Traits\HasInventoryStatus;

class PackagingConsumableResource extends BaseResource
{
    use CsvExportAction;
    use HasActiveStatus;
    use HasTimestamps;
    use HasStatusBadge;
    use HasStandardActions;
    use HasInventoryStatus;
    
    protected static ?string $model = Consumable::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Packaging';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('consumableType', fn ($query) => $query->where('code', 'packaging'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        // Hidden consumable type field - always packaging
                        Forms\Components\Hidden::make('consumable_type_id')
                            ->default(fn () => ConsumableType::findByCode('packaging')?->id),
                        
                        // Packaging Type Selection
                        Forms\Components\Select::make('packaging_type_id')
                            ->label('Packaging Type')
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
                                $packagingType = PackagingType::find($state);
                                if ($packagingType) {
                                    $set('name', $packagingType->name);
                                }
                            })
                            ->columnSpanFull(),
                            
                        // Hidden name field
                        Forms\Components\Hidden::make('name'),
                        
                        // Supplier
                        FormCommon::supplierSelect()
                            ->columnSpanFull(),
                        
                        static::getActiveStatusField()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Inventory Details')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                // Unit-based quantity field
                                Forms\Components\TextInput::make('initial_stock')
                                    ->label('Quantity')
                                    ->helperText('Number of units in stock')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->default(0),
                                
                                // Consumed quantity (only in edit mode)
                                Forms\Components\TextInput::make('consumed_quantity')
                                    ->label('Used Quantity')
                                    ->helperText('Number of units consumed')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->visible(fn ($operation) => $operation === 'edit'),
                                
                                // Packaging unit type
                                Forms\Components\Select::make('consumable_unit_id')
                                    ->label('Unit Type')
                                    ->helperText('How packaging is counted')
                                    ->options(ConsumableUnit::options())
                                    ->required()
                                    ->default(fn () => ConsumableUnit::findByCode('unit')?->id),
                            ]),
                        
                        Forms\Components\Placeholder::make('volume_info')
                            ->label('Packaging Information')
                            ->content(function (Get $get) {
                                $packagingTypeId = $get('packaging_type_id');
                                if (!$packagingTypeId) return 'Select a packaging type to see volume information.';
                                
                                $packagingType = PackagingType::find($packagingTypeId);
                                if (!$packagingType) return '';
                                
                                return "Volume: {$packagingType->capacity_volume} {$packagingType->volume_unit} per unit";
                            })
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Restock Settings')
                    ->schema([
                        Forms\Components\TextInput::make('restock_threshold')
                            ->label('Restock Threshold')
                            ->helperText('Minimum units to maintain in inventory')
                            ->numeric()
                            ->required()
                            ->default(10),
                            
                        Forms\Components\TextInput::make('restock_quantity')
                            ->label('Restock Quantity')
                            ->helperText('Units to order when restocking')
                            ->numeric()
                            ->required()
                            ->default(50),
                    ])
                    ->columns(2),
                    
                static::getAdditionalInformationSection()
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
                'packagingType'
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->packagingType) {
                            return "{$state} ({$record->packagingType->capacity_volume} {$record->packagingType->volume_unit})";
                        }
                        return $state;
                    })
                    ->url(fn (Consumable $record): string => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('packagingType.material')
                    ->label('Material')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Available')
                    ->getStateUsing(fn ($record) => max(0, $record->initial_stock - $record->consumed_quantity))
                    ->numeric()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->orderByRaw("(initial_stock - consumed_quantity) {$direction}")
                    )
                    ->formatStateUsing(function ($state, $record) {
                        $unit = $record->consumableUnit ? $record->consumableUnit->symbol : 'unit(s)';
                        return "{$state} {$unit}";
                    }),
                    
                static::getInventoryStatusColumn(),
                static::getActiveStatusBadgeColumn(),
                ...static::getTimestampColumns(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('packaging_type_id')
                    ->label('Packaging Type')
                    ->options(function () {
                        return PackagingType::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload(),
                    
                ...static::getInventoryFilters(),
                static::getActiveStatusFilter(),
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
            'index' => Pages\ListPackagingConsumables::route('/'),
            'create' => Pages\CreatePackagingConsumable::route('/create'),
            'view' => Pages\ViewPackagingConsumable::route('/{record}'),
            'edit' => Pages\EditPackagingConsumable::route('/{record}/edit'),
        ];
    }
    
    protected static function getCsvExportColumns(): array
    {
        $autoColumns = static::getColumnsFromSchema();
        
        return static::addRelationshipColumns($autoColumns, [
            'supplier' => ['name', 'email'],
            'packagingType' => ['name', 'capacity_volume', 'volume_unit', 'material'],
        ]);
    }
    
    protected static function getCsvExportRelationships(): array
    {
        return ['supplier', 'packagingType'];
    }
}