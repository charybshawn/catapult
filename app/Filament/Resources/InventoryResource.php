<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryResource\Pages;
use App\Models\Inventory;
use App\Models\Supplier;
use App\Models\SeedVariety;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Inventory';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_type')
                    ->label('Item Type')
                    ->options([
                        'soil' => 'Soil',
                        'seed' => 'Seed',
                        'consumable' => 'Consumable',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('seed_variety_id', null)),
                Forms\Components\Select::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('seed_variety_id')
                    ->label('Seed Variety')
                    ->relationship('seedVariety', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Forms\Get $get) => $get('item_type') === 'seed'),
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (Forms\Get $get) => $get('item_type') !== 'seed'),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('unit')
                    ->label('Unit')
                    ->required()
                    ->maxLength(50)
                    ->default(function (Forms\Get $get) {
                        return match ($get('item_type')) {
                            'soil' => 'bag',
                            'seed' => 'g',
                            'consumable' => 'unit',
                            default => 'unit',
                        };
                    }),
                Forms\Components\TextInput::make('restock_threshold')
                    ->label('Restock Threshold')
                    ->numeric()
                    ->required()
                    ->default(5),
                Forms\Components\TextInput::make('restock_quantity')
                    ->label('Restock Quantity')
                    ->numeric()
                    ->required()
                    ->default(10),
                Forms\Components\DateTimePicker::make('last_ordered_at')
                    ->label('Last Ordered At'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'soil' => 'success',
                        'seed' => 'info',
                        'consumable' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('seedVariety.name')
                    ->label('Seed Variety')
                    ->searchable()
                    ->visible(fn ($record) => $record && $record->item_type === 'seed'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->visible(fn ($record) => $record && $record->item_type !== 'seed'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => $record ? ' ' . $record->unit : ''),
                Tables\Columns\TextColumn::make('restock_threshold')
                    ->label('Restock At')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn ($record) => $record ? ' ' . $record->unit : ''),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($record): string => $record ? match (true) {
                        $record->quantity <= 0 => 'danger',
                        $record->quantity <= $record->restock_threshold => 'warning',
                        default => 'success',
                    } : 'gray')
                    ->formatStateUsing(fn ($record): string => $record ? match (true) {
                        $record->quantity <= 0 => 'Out of Stock',
                        $record->quantity <= $record->restock_threshold => 'Reorder Needed',
                        default => 'In Stock',
                    } : 'Unknown'),
                Tables\Columns\TextColumn::make('last_ordered_at')
                    ->label('Last Ordered')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('quantity', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('item_type')
                    ->options([
                        'soil' => 'Soil',
                        'seed' => 'Seed',
                        'consumable' => 'Consumable',
                    ]),
                Tables\Filters\Filter::make('needs_restock')
                    ->label('Needs Restock')
                    ->query(fn (Builder $query) => $query->whereRaw('quantity <= restock_threshold')),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query) => $query->where('quantity', '<=', 0)),
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
                            ->default(fn (Inventory $record) => $record->restock_quantity),
                    ])
                    ->action(function (Inventory $record, array $data): void {
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventory::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }
} 