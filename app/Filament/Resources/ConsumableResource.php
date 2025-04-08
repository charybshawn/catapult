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

class ConsumableResource extends Resource
{
    protected static ?string $model = Consumable::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Consumables & Supplies';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Consumable Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'packaging' => 'Packaging',
                                'label' => 'Label',
                                'soil' => 'Soil',
                                'seed' => 'Seed',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('packaging_type_id', null);
                                $set('name', ''); // Clear name when type changes
                                
                                // Set default unit based on type
                                $unit = match ($state) {
                                    'soil' => 'bags',
                                    'seed' => 'packets',
                                    'packaging' => 'pieces',
                                    default => 'pieces',
                                };
                                $set('unit', $unit);
                                
                                // Set default quantity unit based on type
                                if ($state === 'soil') {
                                    $set('quantity_unit', 'l');
                                } elseif ($state === 'seed') {
                                    $set('quantity_unit', 'g');
                                }
                            }),
                            
                        Forms\Components\TextInput::make('name')
                            ->required(fn ($get) => $get('type') !== 'packaging')
                            ->hidden(fn ($get) => $get('type') === 'packaging')
                            ->dehydrated(true)
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'packaging' => 'Packaging',
                                        'label' => 'Label',
                                        'soil' => 'Soil',
                                        'seed' => 'Seed',
                                        'other' => 'Other',
                                    ])
                                    ->default('other')
                                    ->required(),
                                Forms\Components\Textarea::make('contact_info')
                                    ->label('Contact Information')
                                    ->rows(3),
                            ]),
                            
                        Forms\Components\Select::make('packaging_type_id')
                            ->label('Packaging Type')
                            ->relationship('packagingType', 'display_name')
                            ->options(fn () => PackagingType::all()->pluck('display_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->visible(fn ($get) => $get('type') === 'packaging')
                            ->required(fn ($get) => $get('type') === 'packaging')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    // When packaging type is selected, automatically set the name
                                    $packagingType = PackagingType::find($state);
                                    if ($packagingType) {
                                        $set('name', $packagingType->display_name ?? $packagingType->name);
                                    }
                                } else {
                                    // Clear name if packaging type is cleared
                                    $set('name', '');
                                }
                            })
                            ->helperText('The name will be automatically set from the packaging type')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Base Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Base product name without size (e.g., "Clamshell")'),
                                Forms\Components\Grid::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('capacity_volume')
                                            ->label('Volume')
                                            ->required()
                                            ->numeric()
                                            ->minValue(0.01)
                                            ->step(0.01),
                                        Forms\Components\Select::make('volume_unit')
                                            ->label('Unit')
                                            ->options([
                                                'oz' => 'Ounces (oz)',
                                                'ml' => 'Milliliters (ml)',
                                                'l' => 'Liters (l)',
                                                'pt' => 'Pints (pt)',
                                                'qt' => 'Quarts (qt)',
                                                'gal' => 'Gallons (gal)',
                                            ])
                                            ->default('oz')
                                            ->required(),
                                    ])
                                    ->columns(2),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Stock Information')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('current_stock')
                                ->label('Current Stock')
                                ->numeric()
                                ->required()
                                ->default(0),
                            Forms\Components\TextInput::make('unit')
                                ->label('Unit of Measurement')
                                ->helperText('How this item is counted in inventory (pieces, boxes, rolls, etc.)')
                                ->required()
                                ->default(function ($get) {
                                    return match ($get('type')) {
                                        'soil' => 'bags',
                                        'seed' => 'packets',
                                        'packaging' => 'pieces',
                                        default => 'pieces',
                                    };
                                })
                                ->maxLength(50),
                        ])->columns(2),
                        
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('quantity_per_unit')
                                ->label('Quantity Per Unit')
                                ->helperText('The amount contained in each unit (e.g., liters in each bag, seeds in each packet)')
                                ->numeric()
                                ->visible(fn ($get) => in_array($get('type'), ['soil', 'seed']))
                                ->required(fn ($get) => in_array($get('type'), ['soil', 'seed']))
                                ->minValue(0.01)
                                ->step(0.01)
                                ->default(1),
                            Forms\Components\Select::make('quantity_unit')
                                ->label('Quantity Unit')
                                ->helperText('The measurement unit for the contents of each inventory unit')
                                ->options([
                                    'l' => 'Liters (l)',
                                    'gal' => 'Gallons (gal)',
                                    'cu_ft' => 'Cubic Feet (cu ft)',
                                    'quarts' => 'Quarts (qt)',
                                    'g' => 'Grams (g)',
                                    'kg' => 'Kilograms (kg)',
                                    'lb' => 'Pounds (lb)',
                                    'count' => 'Count',
                                ])
                                ->visible(fn ($get) => in_array($get('type'), ['soil', 'seed']))
                                ->required(fn ($get) => in_array($get('type'), ['soil', 'seed']))
                                ->default(fn ($get) => $get('type') === 'soil' ? 'l' : 'g')
                                ->reactive(),
                        ])
                        ->columns(2)
                        ->visible(fn ($get) => in_array($get('type'), ['soil', 'seed'])),
                        
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('restock_threshold')
                                ->label(fn ($get) => $get('type') === 'seed' ? 'Restock Threshold (grams)' : 'Restock Threshold')
                                ->helperText(fn ($get) => $get('type') === 'seed' 
                                    ? 'Total seed weight (grams) at which to restock, regardless of packet count' 
                                    : 'Amount at which an item should be restocked')
                                ->numeric()
                                ->required()
                                ->default(fn ($get) => $get('type') === 'seed' ? 100 : 10),
                            Forms\Components\TextInput::make('restock_quantity')
                                ->label('Restock Quantity')
                                ->helperText('Quantity to order when restocking')
                                ->numeric()
                                ->required()
                                ->default(50),
                        ])->columns(2),
                        
                        Forms\Components\TextInput::make('cost_per_unit')
                            ->label('Cost Per Unit ($)')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->default(0),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'packaging' => 'success',
                        'label' => 'info',
                        'soil' => 'warning',
                        'seed' => 'emerald',
                        'other' => 'gray',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('packagingType.display_name')
                    ->label('Packaging Spec')
                    ->searchable()
                    ->sortable()
                    ->visible(fn ($record) => $record && $record->type === 'packaging'),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => $record ? ' ' . $record->unit : ''),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Total Quantity')
                    ->visible(fn ($record) => $record && in_array($record->type, ['soil', 'seed']) && $record->quantity_per_unit)
                    ->formatStateUsing(fn ($record) => 
                        $record && $record->total_quantity 
                            ? number_format($record->total_quantity, 2) . ' ' . ($record->quantity_unit ?? '') 
                            : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('restock_threshold')
                    ->label('Restock At')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        if (!$record) return null;
                        
                        if ($record->type === 'seed') {
                            return number_format($record->restock_threshold) . ' ' . ($record->quantity_unit ?? 'g');
                        }
                        
                        return number_format($record->restock_threshold) . ' ' . $record->unit;
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($record): string => $record ? match (true) {
                        $record->isOutOfStock() => 'danger',
                        $record->needsRestock() => 'warning',
                        default => 'success',
                    } : 'gray')
                    ->formatStateUsing(function ($record): string {
                        if (!$record) return 'Unknown';
                        
                        if ($record->isOutOfStock()) {
                            return 'Out of Stock';
                        }
                        
                        if ($record->needsRestock()) {
                            if ($record->type === 'seed') {
                                return 'Low Seed Weight';
                            }
                            return 'Reorder Needed';
                        }
                        
                        return 'In Stock';
                    }),
                Tables\Columns\TextColumn::make('cost_per_unit')
                    ->label('Unit Cost')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('name')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'packaging' => 'Packaging',
                        'label' => 'Label',
                        'soil' => 'Soil',
                        'seed' => 'Seed',
                        'other' => 'Other',
                    ]),
                Tables\Filters\Filter::make('needs_restock')
                    ->label('Needs Restock')
                    ->query(fn (Builder $query) => $query->whereRaw('current_stock <= restock_threshold')),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->where('current_stock', '<=', 0)),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Items')
                    ->trueLabel('Active Items')
                    ->falseLabel('Inactive Items'),
            ])
            ->actions([
                Tables\Actions\Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount to Add')
                            ->numeric()
                            ->required()
                            ->default(fn (Consumable $record) => $record->restock_quantity),
                    ])
                    ->action(function (Consumable $record, array $data): void {
                        $record->add($data['amount']);
                    }),
                Tables\Actions\Action::make('deduct')
                    ->label('Deduct')
                    ->icon('heroicon-o-minus')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount to Deduct')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(fn (Consumable $record) => $record ? $record->current_stock : 0),
                    ])
                    ->action(function (Consumable $record, array $data): void {
                        if ($record) {
                            $record->deduct($data['amount']);
                        }
                    })
                    ->visible(fn (Consumable $record) => $record && $record->current_stock > 0),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('restock_bulk')
                        ->label('Restock')
                        ->icon('heroicon-o-plus')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record) {
                                    $record->add($record->restock_quantity);
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('Toggle Active Status')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record) {
                                    $record->update(['is_active' => !$record->is_active]);
                                }
                            }
                        }),
                ]),
            ]);
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
} 