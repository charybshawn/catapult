<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PriceVariationResource\Pages;
use App\Filament\Resources\PriceVariationResource\RelationManagers;
use App\Models\PriceVariation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PriceVariationResource extends Resource
{
    protected static ?string $model = PriceVariation::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Sales & Products';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Price Variation Details')
                    ->schema([
                        Forms\Components\Placeholder::make('global_info')
                            ->content('This is a global price variation that can be applied to any product.')
                            ->visible(fn (Forms\Get $get): bool => $get('is_global')),
                            
                        Forms\Components\Select::make('item_id')
                            ->relationship('item', 'name')
                            ->label('Product')
                            ->required(fn (Forms\Get $get): bool => !$get('is_global'))
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                                Forms\Components\Toggle::make('active')
                                    ->label('Active')
                                    ->default(true),
                                Forms\Components\Toggle::make('is_visible_in_store')
                                    ->label('Visible in Store')
                                    ->default(true),
                            ])
                            ->visible(fn (Forms\Get $get): bool => !$get('is_global')),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                    
                                Forms\Components\Select::make('unit')
                                    ->options([
                                        'item' => 'Per Item',
                                        'lbs' => 'Pounds',
                                        'gram' => 'Grams',
                                        'kg' => 'Kilograms',
                                        'oz' => 'Ounces',
                                    ])
                                    ->required(),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU/UPC Code')
                                    ->maxLength(255),
                                
                                Forms\Components\TextInput::make('weight')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->suffix(fn (Forms\Get $get): string => match ($get('unit')) {
                                        'lbs' => 'lbs',
                                        'gram' => 'g',
                                        'kg' => 'kg',
                                        'oz' => 'oz',
                                        default => '',
                                    })
                                    ->visible(fn (Forms\Get $get): bool => $get('unit') !== 'item'),
                            ]),
                        
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->minValue(0)
                            ->required(),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('is_default')
                                    ->label('Default Price for Product')
                                    ->default(false)
                                    ->visible(fn (Forms\Get $get): bool => !$get('is_global'))
                                    ->disabled(fn (Forms\Get $get): bool => $get('is_global')),
                                    
                                Forms\Components\Toggle::make('is_global')
                                    ->label('Global Pricing')
                                    ->helperText('When enabled, this price variation can be used with any product')
                                    ->default(false)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            // If making global, clear the product association and default status
                                            $set('is_default', false);
                                            $set('item_id', null);
                                        }
                                    }),
                                    
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Product')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Global Price'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'item' => 'Per Item',
                        'lbs' => 'Pounds',
                        'gram' => 'Grams',
                        'kg' => 'Kilograms',
                        'oz' => 'Ounces',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU/UPC'),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_global')
                    ->label('Global')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('item')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Product'),
                Tables\Filters\SelectFilter::make('unit')
                    ->options([
                        'item' => 'Per Item',
                        'lbs' => 'Pounds',
                        'gram' => 'Grams',
                        'kg' => 'Kilograms',
                        'oz' => 'Ounces',
                    ]),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Price'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn (Builder $query) => $query->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn (Builder $query) => $query->update(['is_active' => false])),
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
            'index' => Pages\ListPriceVariations::route('/'),
            'create' => Pages\CreatePriceVariation::route('/create'),
            'edit' => Pages\EditPriceVariation::route('/{record}/edit'),
        ];
    }
}
