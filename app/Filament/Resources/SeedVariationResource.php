<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeedVariationResource\Pages;
use App\Filament\Resources\SeedVariationResource\RelationManagers;
use App\Models\SeedVariation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;

class SeedVariationResource extends Resource
{
    protected static ?string $model = SeedVariation::class;

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Seed Variations';
    
    protected static ?string $navigationGroup = 'Seed Inventory';
    
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('seed_entry_id')
                    ->relationship('seedEntry', 'supplier_product_title')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('size_description')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('sku')
                    ->maxLength(255),
                Forms\Components\TextInput::make('weight_kg')
                    ->numeric()
                    ->step('0.0001')
                    ->label('Weight (kg)'),
                Forms\Components\TextInput::make('original_weight_value')
                    ->numeric()
                    ->label('Original Weight Value'),
                Forms\Components\TextInput::make('original_weight_unit')
                    ->maxLength(255)
                    ->label('Original Weight Unit'),
                Forms\Components\TextInput::make('current_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Select::make('currency')
                    ->options([
                        'USD' => 'USD',
                        'CAD' => 'CAD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ])
                    ->default('USD')
                    ->required(),
                Forms\Components\Toggle::make('is_in_stock')
                    ->required()
                    ->default(true),
                Forms\Components\DateTimePicker::make('last_checked_at')
                    ->required()
                    ->default(now()),
                Forms\Components\Select::make('consumable_id')
                    ->relationship('consumable', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm(function () {
                        return \App\Models\Consumable::getSeedFormSchema();
                    })
                    ->label('Connected Inventory Item'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('seedEntry.seedCultivar.name')
                    ->label('Cultivar')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('seedEntry.supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('size_description')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_kg')
                    ->numeric()
                    ->label('Weight (kg)')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_kg')
                    ->label('Price per kg')
                    ->money('USD')
                    ->getStateUsing(fn (SeedVariation $record): ?float => $record->price_per_kg)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('current_price / NULLIF(weight_kg, 0) ' . $direction);
                    }),
                Tables\Columns\IconColumn::make('is_in_stock')
                    ->boolean()
                    ->label('In Stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('consumable.current_stock')
                    ->label('Current Stock')
                    ->formatStateUsing(fn ($state, SeedVariation $record) => 
                        $record->consumable 
                            ? $record->consumable->formatted_current_stock 
                            : 'Not linked'
                    ),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('seedEntry.seedCultivar.name')
            ->filters([
                Tables\Filters\SelectFilter::make('cultivar')
                    ->relationship('seedEntry.seedCultivar', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Cultivar'),
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('seedEntry.supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        '1' => 'In Stock',
                        '0' => 'Out of Stock',
                    ])
                    ->attribute('is_in_stock'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PriceHistoryRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeedVariations::route('/'),
            'create' => Pages\CreateSeedVariation::route('/create'),
            'edit' => Pages\EditSeedVariation::route('/{record}/edit'),
        ];
    }
}
