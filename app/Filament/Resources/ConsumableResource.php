<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsumableResource\Pages;
use App\Models\Consumable;
use App\Models\PackagingType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class ConsumableResource extends Resource
{
    protected static ?string $model = Consumable::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Consumables & Supplies';
    protected static ?string $navigationGroup = 'Inventory & Supplies';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        // Determine if we're in edit mode
        $isEditMode = $form->getOperation() === 'edit';
        
        // Define the quantity schema based on operation
        $quantitySchema = $isEditMode
            ? [
                Forms\Components\TextInput::make('initial_stock')
                    ->label('Initial Quantity')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default(0)
                    ->reactive()
                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => 
                        $set('current_stock_display', max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity')))
                    ),
                Forms\Components\TextInput::make('consumed_quantity')
                    ->label('Consumed Quantity')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->reactive()
                    ->afterStateUpdated(fn (Forms\Set $set, Forms\Get $get) => 
                        $set('current_stock_display', max(0, (float)$get('initial_stock') - (float)$get('consumed_quantity')))
                    ),
                Forms\Components\TextInput::make('current_stock_display')
                    ->label('Available Stock')
                    ->disabled()
                    ->dehydrated(false)
                    ->numeric(),
            ] 
            : [
                Forms\Components\TextInput::make('initial_stock')
                    ->label('Initial Quantity')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default(0),
            ];
        
        // Add unit field to both cases
        $quantitySchema[] = Forms\Components\Select::make('unit')
            ->label('Unit of Measure')
            ->options([
                'unit' => 'Unit(s)',
                'kg' => 'Kilograms',
                'g' => 'Grams',
                'oz' => 'Ounces',
                'l' => 'Litre(s)',
                'ml' => 'Milliliters',
            ])
            ->required()
            ->default('unit');
        
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(Consumable::getValidTypes())
                            ->required()
                            ->reactive()
                            ->disabled($isEditMode) // Disable in edit mode
                            ->dehydrated() // Ensure the field value is still submitted
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Reset packaging type when type changes
                                if ($state !== 'packaging') {
                                    $set('packaging_type_id', null);
                                }
                            }),
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->datalist(function (Forms\Get $get) {
                                // Only provide autocomplete for seed type
                                if ($get('type') === 'seed') {
                                    return Consumable::where('type', 'seed')
                                        ->where('is_active', true)
                                        ->pluck('name')
                                        ->unique()
                                        ->toArray();
                                }
                                return [];
                            }),
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('packaging_type_id')
                            ->label('Packaging Type')
                            ->relationship('packagingType', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->display_name)
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'packaging'),
                        Forms\Components\TextInput::make('lot_no')
                            ->label('Lot/Batch Number')
                            ->helperText('Will be converted to uppercase')
                            ->maxLength(100)
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['seed', 'soil'])),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->columnSpanFull()
                            ->inline(false),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Inventory Details')
                    ->schema([
                        Forms\Components\Fieldset::make('Quantity')
                            ->schema($quantitySchema)->columns(2),
                        
                        Forms\Components\Fieldset::make('Restock Settings')
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
                            ])->columns(2),
                    ]),
                
                Forms\Components\Section::make('Costs')
                    ->schema([
                        Forms\Components\TextInput::make('cost_per_unit')
                            ->label('Cost Per Unit')
                            ->helperText('How much each unit costs to purchase')
                            ->numeric()
                            ->prefix('$')
                            ->default(0),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
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
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Available Quantity')
                    ->getStateUsing(fn ($record) => $record ? max(0, $record->initial_stock - $record->consumed_quantity) : 0)
                    ->numeric()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => 
                        $query->orderByRaw("(initial_stock - consumed_quantity) {$direction}")
                    )
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record) return $state;
                        
                        // Map unit codes to their full names
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
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ])
            ->actions([
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('primary')
                    ->form([
                        Forms\Components\TextInput::make('adjustment_type')
                            ->label('Adjustment Type')
                            ->dehydrated(false)
                            ->default('add')
                            ->hiddenLabel()
                            ->hidden()
                            ->disabled(),
                        Forms\Components\Tabs::make('adjustment_tabs')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Add Stock')
                                    ->icon('heroicon-o-plus')
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->schema([
                                                Forms\Components\TextInput::make('add_amount')
                                                    ->label('Amount to Add')
                                                    ->numeric()
                                                    ->step(0.001)
                                                    ->minValue(0.001)
                                                    ->required()
                                                    ->default(fn (Consumable $record) => $record->restock_quantity),
                                                Forms\Components\Select::make('add_unit')
                                                    ->label('Unit')
                                                    ->options(fn (Consumable $record) => self::getCompatibleUnits($record))
                                                    ->default(fn (Consumable $record) => $record->unit)
                                                    ->required(),
                                            ])->columns(2),
                                    ])
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('adjustment_type', 'add')),
                                Forms\Components\Tabs\Tab::make('Consume Stock')
                                    ->icon('heroicon-o-minus')
                                    ->schema([
                                        Forms\Components\Grid::make()
                                            ->schema([
                                                Forms\Components\TextInput::make('consume_amount')
                                                    ->label('Amount to Consume')
                                                    ->numeric()
                                                    ->step(0.001)
                                                    ->minValue(0.001)
                                                    ->required()
                                                    ->default(1),
                                                Forms\Components\Select::make('consume_unit')
                                                    ->label('Unit')
                                                    ->options(fn (Consumable $record) => self::getCompatibleUnits($record))
                                                    ->default(fn (Consumable $record) => $record->unit)
                                                    ->required(),
                                            ])->columns(2),
                                    ])
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('adjustment_type', 'consume')),
                            ]),
                    ])
                    ->action(function (Consumable $record, array $data): void {
                        if ($data['adjustment_type'] === 'add' && isset($data['add_amount'])) {
                            $record->add((float)$data['add_amount'], $data['add_unit'] ?? null);
                        } elseif ($data['adjustment_type'] === 'consume' && isset($data['consume_amount'])) {
                            $record->deduct((float)$data['consume_amount'], $data['consume_unit'] ?? null);
                        }
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
        ];
    }

    /**
     * Get compatible units for a consumable for unit conversion
     * 
     * @param Consumable $record The consumable record
     * @return array Compatible units
     */
    protected static function getCompatibleUnits(Consumable $record): array
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
    protected static function getUnitLabel(string $unit): string
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