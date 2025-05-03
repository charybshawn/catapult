<?php

namespace App\Filament\Resources\RecurringOrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecurringOrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'recurringOrderItems';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('recipe_id')
                            ->relationship('recipe', 'name')
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('$')
                            ->required(),
                        Forms\Components\TextInput::make('expected_yield_grams')
                            ->label('Expected Yield (grams)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('g')
                            ->required(),
                    ]),
                Forms\Components\TextInput::make('quantity')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('$')
                    ->helperText('Leave blank to use standard item price')
                    ->nullable(),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->nullable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.recipe.name')
                    ->label('Recipe')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->label('Price (Each)')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return $record->price ?? $record->item->price ?? 0;
                    }),
                Tables\Columns\TextColumn::make('subtotal')
                    ->money('USD')
                    ->label('Subtotal')
                    ->getStateUsing(function ($record) {
                        $price = $record->price ?? $record->item->price ?? 0;
                        return $price * $record->quantity;
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
} 