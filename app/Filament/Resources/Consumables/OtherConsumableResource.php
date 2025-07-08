<?php

namespace App\Filament\Resources\Consumables;

use App\Filament\Resources\Consumables\OtherConsumableResource\Pages;
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

class OtherConsumableResource extends BaseResource
{
    use CsvExportAction;
    use HasActiveStatus;
    use HasTimestamps;
    use HasStatusBadge;
    use HasStandardActions;
    use HasInventoryStatus;
    
    protected static ?string $model = Consumable::class;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationLabel = 'Other Consumables';
    protected static ?string $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('consumableType', fn ($query) => $query->where('code', 'other'));
    }

    public static function form(Form $form): Form
    {
        $isEditMode = $form->getOperation() === 'edit';
        
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        // Hidden consumable type field - always other
                        Forms\Components\Hidden::make('consumable_type_id')
                            ->default(fn () => ConsumableType::findByCode('other')?->id),
                        
                        // Item name
                        Forms\Components\TextInput::make('name')
                            ->label('Item Name')
                            ->helperText('Name of the consumable item')
                            ->required()
                            ->maxLength(255)
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
                                    ->helperText('Number of units in stock')
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
                                    ->helperText('Number of units used')
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
                                
                                // Unit type
                                Forms\Components\Select::make('consumable_unit_id')
                                    ->label('Unit Type')
                                    ->helperText('How item is packaged/counted')
                                    ->options(ConsumableUnit::options())
                                    ->required()
                                    ->default(fn () => ConsumableUnit::findByCode('unit')?->id),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Size/capacity per unit (optional)
                                Forms\Components\TextInput::make('quantity_per_unit')
                                    ->label('Size/Capacity per Unit')
                                    ->helperText('Optional: size, capacity, or amount per unit')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(1)
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, Get $get) use ($isEditMode) {
                                        $availableStock = $isEditMode
                                            ? max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity'))
                                            : (float)$get('initial_stock');
                                        
                                        $set('total_quantity', $availableStock * (float)$get('quantity_per_unit'));
                                    }),
                                
                                // Unit of measurement (flexible)
                                Forms\Components\Select::make('quantity_unit')
                                    ->label('Unit of Measurement')
                                    ->helperText('Unit for size/capacity if applicable')
                                    ->options([
                                        'g' => 'Grams',
                                        'kg' => 'Kilograms',
                                        'l' => 'Liters',
                                        'ml' => 'Milliliters',
                                        'oz' => 'Ounces',
                                        'lb' => 'Pounds',
                                        'cm' => 'Centimeters',
                                        'm' => 'Meters',
                                        'ft' => 'Feet',
                                        'in' => 'Inches',
                                        'pcs' => 'Pieces',
                                        'unit' => 'Unit',
                                    ])
                                    ->default('unit'),
                            ]),
                        
                        // Total amount display (if applicable)
                        Forms\Components\Placeholder::make('total_amount_display')
                            ->label('Total Amount Available')
                            ->content(function (Get $get) use ($isEditMode) {
                                $availableStock = $isEditMode
                                    ? max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity'))
                                    : (float)$get('initial_stock');
                                    
                                $amountPerUnit = (float)$get('quantity_per_unit');
                                $unit = $get('quantity_unit') ?: 'unit';
                                
                                if ($amountPerUnit > 0 && $amountPerUnit !== 1) {
                                    $totalAmount = $availableStock * $amountPerUnit;
                                    return number_format($totalAmount, 2) . ' ' . $unit;
                                }
                                
                                return $availableStock . ' ' . ($availableStock == 1 ? 'unit' : 'units');
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
                            ->helperText('Minimum units to maintain in inventory')
                            ->numeric()
                            ->required()
                            ->default(5),
                            
                        Forms\Components\TextInput::make('restock_quantity')
                            ->label('Restock Quantity')
                            ->helperText('Units to order when restocking')
                            ->numeric()
                            ->required()
                            ->default(10),
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
                    ->label('Item Name')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Consumable $record): string => static::getUrl('edit', ['record' => $record]))
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('consumableUnit.name')
                    ->label('Unit Type')
                    ->badge()
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
                    
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->getStateUsing(function ($record) {
                        $availableStock = max(0, $record->initial_stock - $record->consumed_quantity);
                        $amountPerUnit = $record->quantity_per_unit ?: 1;
                        return $availableStock * $amountPerUnit;
                    })
                    ->formatStateUsing(function ($state, $record) {
                        $unit = $record->quantity_unit ?: 'unit';
                        if ($record->quantity_per_unit && $record->quantity_per_unit !== 1) {
                            return number_format($state, 2) . ' ' . $unit;
                        }
                        return $state . ' ' . ($state == 1 ? 'unit' : 'units');
                    })
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->orderByRaw("((initial_stock - consumed_quantity) * quantity_per_unit) {$direction}")
                    ),
                    
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
                    ->label('Unit Type')
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
            'index' => \App\Filament\Resources\Consumables\OtherConsumableResource\Pages\ListOtherConsumables::route('/'),
            'create' => \App\Filament\Resources\Consumables\OtherConsumableResource\Pages\CreateOtherConsumable::route('/create'),
            'view' => \App\Filament\Resources\Consumables\OtherConsumableResource\Pages\ViewOtherConsumable::route('/{record}'),
            'edit' => \App\Filament\Resources\Consumables\OtherConsumableResource\Pages\EditOtherConsumable::route('/{record}/edit'),
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