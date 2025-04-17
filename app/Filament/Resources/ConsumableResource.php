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
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(Consumable::getValidTypes())
                            ->required()
                            ->reactive()
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
                        Forms\Components\Group::make([
                            Forms\Components\TextInput::make('seed_packet_count')
                                ->label('Units (qty)')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->visible(fn (Forms\Get $get) => $get('type') === 'seed')
                                ->default(0),
                            Forms\Components\TextInput::make('non_seed_stock')
                                ->label('Units (qty)')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->visible(fn (Forms\Get $get) => $get('type') !== 'seed')
                                ->default(0),
                            Forms\Components\TextInput::make('unit')
                                ->label('Unit Type')
                                ->helperText('How this item is stored (pieces, boxes, rolls, bags, containers, etc.)')
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
                                ->label('Weight Per Unit')
                                ->helperText('How much each unit weighs (e.g., grams per packet)')
                                ->numeric()
                                ->required()
                                ->default(0)
                                ->visible(fn (Forms\Get $get) => $get('type') !== 'packaging'),
                            Forms\Components\Select::make('quantity_unit')
                                ->label('Weight Measurement Unit')
                                ->helperText('Unit used to measure the weight (grams, kilograms, etc.)')
                                ->options(Consumable::getValidMeasurementUnits())
                                ->required()
                                ->default('g')
                                ->reactive()
                                ->visible(fn (Forms\Get $get) => $get('type') !== 'packaging'),
                        ])->columns(2),
                        
                        Forms\Components\Group::make([
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
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'packaging' => 'success',
                        'label' => 'info',
                        'soil' => 'warning',
                        'seed' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('packagingType.name')
                    ->label('Packaging Type')
                    ->formatStateUsing(fn ($state, $record) => $record && $record->packagingType ? $record->packagingType->display_name : null)
                    ->visible(fn ($record) => $record && $record->type === 'packaging'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => $record ? ' ' . $record->unit : '')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('quantity_per_unit')
                    ->label('Weight Per Unit')
                    ->numeric()
                    ->suffix(fn ($record) => $record && $record->quantity_unit ? ' ' . $record->quantity_unit : '')
                    ->size('sm')
                    ->visible(fn ($record) => $record && $record->type !== 'packaging'),
                Tables\Columns\TextColumn::make('formatted_total_weight')
                    ->label('Total Weight')
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('total_quantity', $direction))
                    ->weight('bold')
                    ->color('success')
                    ->visible(fn ($record) => $record && $record->type !== 'packaging'),
                Tables\Columns\TextColumn::make('restock_threshold')
                    ->label('Restock At')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => $record ? ' ' . $record->unit : '')
                    ->size('sm'),
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
                    } : 'Unknown'),
                Tables\Columns\TextColumn::make('last_ordered_at')
                    ->label('Last Ordered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('current_stock', 'asc')
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
                    ->query(fn (Builder $query) => $query->whereRaw('current_stock <= restock_threshold')),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->where('current_stock', '<=', 0)),
                Tables\Filters\Filter::make('inactive')
                    ->label('Inactive')
                    ->query(fn (Builder $query) => $query->where('is_active', false)),
            ])
            ->actions([
                Tables\Actions\Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-o-shopping-cart')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('restock_bulk')
                        ->label('Restock')
                        ->icon('heroicon-o-shopping-cart')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->add($record->restock_quantity);
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