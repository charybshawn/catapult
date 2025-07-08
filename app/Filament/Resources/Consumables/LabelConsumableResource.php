<?php

namespace App\Filament\Resources\Consumables;

use App\Filament\Resources\Consumables\LabelConsumableResource\Pages;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Filament\Traits\CsvExportAction;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStatusBadge;
use App\Filament\Traits\HasStandardActions;
use App\Filament\Traits\HasInventoryStatus;

class LabelConsumableResource extends BaseResource
{
    use CsvExportAction;
    use HasActiveStatus;
    use HasTimestamps;
    use HasStatusBadge;
    use HasStandardActions;
    use HasInventoryStatus;
    
    protected static ?string $model = Consumable::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Labels';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('consumableType', fn ($query) => $query->where('code', 'label'));
    }

    public static function form(Form $form): Form
    {
        $isEditMode = $form->getOperation() === 'edit';
        
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        // Hidden consumable type field - always label
                        Forms\Components\Hidden::make('consumable_type_id')
                            ->default(fn () => ConsumableType::findByCode('label')?->id),
                        
                        // Label Name with autocomplete
                        Forms\Components\TextInput::make('name')
                            ->label('Label Name/Type')
                            ->helperText('Type of label (e.g., "Plant Tags", "Shipping Labels")')
                            ->required()
                            ->maxLength(255)
                            ->datalist(function () {
                                return Consumable::whereHas('consumableType', fn ($q) => $q->where('code', 'label'))
                                    ->where('is_active', true)
                                    ->pluck('name')
                                    ->unique()
                                    ->toArray();
                            })
                            ->columnSpanFull(),
                        
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
                                // Quantity field
                                Forms\Components\TextInput::make('initial_stock')
                                    ->label('Quantity')
                                    ->helperText('Number of roll/sheets/packs')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->default(0)
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) use ($isEditMode) {
                                        if (null !== $get('quantity_per_unit') && $get('quantity_per_unit') > 0) {
                                            $availableStock = $isEditMode 
                                                ? max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity')) 
                                                : (float)$get('initial_stock');
                                            
                                            $set('total_quantity', $availableStock * (float)$get('quantity_per_unit'));
                                        }
                                    }),
                                
                                // Consumed quantity (only in edit mode)
                                Forms\Components\TextInput::make('consumed_quantity')
                                    ->label('Used Quantity')
                                    ->helperText('Number of roll/sheets/packs used')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->reactive()
                                    ->visible($isEditMode)
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        if (null !== $get('quantity_per_unit') && $get('quantity_per_unit') > 0) {
                                            $availableStock = max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity'));
                                            $set('total_quantity', $availableStock * (float)$get('quantity_per_unit'));
                                        }
                                    }),
                                
                                // Packaging unit type (roll, sheet, pack, etc.)
                                Forms\Components\Select::make('consumable_unit_id')
                                    ->label('Packaging Type')
                                    ->helperText('How labels are packaged')
                                    ->options(ConsumableUnit::options())
                                    ->required()
                                    ->default(fn () => ConsumableUnit::findByCode('roll')?->id ?? ConsumableUnit::findByCode('unit')?->id),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Labels per unit
                                Forms\Components\TextInput::make('quantity_per_unit')
                                    ->label('Labels per Unit')
                                    ->helperText('Number of labels per roll/sheet/pack')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(100)
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) use ($isEditMode) {
                                        $availableStock = $isEditMode
                                            ? max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity'))
                                            : (float)$get('initial_stock');
                                        
                                        $set('total_quantity', $availableStock * (float)$get('quantity_per_unit'));
                                    }),
                                
                                // Hidden quantity unit (always labels)
                                Forms\Components\Hidden::make('quantity_unit')
                                    ->default('labels'),
                            ]),
                        
                        // Total labels display
                        Forms\Components\Placeholder::make('total_labels_display')
                            ->label('Total Labels Available')
                            ->content(function (Get $get) use ($isEditMode) {
                                $availableStock = $isEditMode
                                    ? max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity'))
                                    : (float)$get('initial_stock');
                                    
                                $labelsPerUnit = (float)$get('quantity_per_unit');
                                
                                if ($labelsPerUnit > 0) {
                                    $totalLabels = $availableStock * $labelsPerUnit;
                                    return number_format($totalLabels, 0) . ' labels';
                                }
                                
                                return '0 labels';
                            })
                            ->columnSpanFull(),
                        
                        // Hidden field for total_quantity
                        Forms\Components\Hidden::make('total_quantity')
                            ->default(0),
                    ]),
                
                Forms\Components\Section::make('Restock Settings')
                    ->schema([
                        Forms\Components\TextInput::make('restock_threshold')
                            ->label('Restock Threshold')
                            ->helperText('Minimum number of units to maintain')
                            ->numeric()
                            ->required()
                            ->default(1),
                            
                        Forms\Components\TextInput::make('restock_quantity')
                            ->label('Restock Quantity')
                            ->helperText('Number of units to order when restocking')
                            ->numeric()
                            ->required()
                            ->default(5),
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
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Label Type')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Consumable $record): string => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
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
                    
                Tables\Columns\TextColumn::make('total_labels')
                    ->label('Total Labels')
                    ->getStateUsing(function ($record) {
                        $availableStock = max(0, $record->initial_stock - $record->consumed_quantity);
                        return $availableStock * $record->quantity_per_unit;
                    })
                    ->formatStateUsing(fn ($state) => number_format($state, 0) . ' labels')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->orderByRaw("((initial_stock - consumed_quantity) * quantity_per_unit) {$direction}")
                    ),
                    
                Tables\Columns\TextColumn::make('quantity_per_unit')
                    ->label('Labels per Unit')
                    ->numeric()
                    ->sortable(),
                    
                static::getInventoryStatusColumn(),
                static::getActiveStatusBadgeColumn(),
                ...static::getTimestampColumns(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('consumable_unit_id')
                    ->label('Packaging Type')
                    ->relationship('consumableUnit', 'name')
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
            'index' => Pages\ListLabelConsumables::route('/'),
            'create' => Pages\CreateLabelConsumable::route('/create'),
            'view' => Pages\ViewLabelConsumable::route('/{record}'),
            'edit' => Pages\EditLabelConsumable::route('/{record}/edit'),
        ];
    }
    
    protected static function getCsvExportColumns(): array
    {
        $autoColumns = static::getColumnsFromSchema();
        
        return static::addRelationshipColumns($autoColumns, [
            'supplier' => ['name', 'email'],
        ]);
    }
    
    protected static function getCsvExportRelationships(): array
    {
        return ['supplier'];
    }
}