<?php

namespace App\Filament\Resources\SeedEntryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('size_description')
            ->columns([
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
                    ->getStateUsing(fn ($record): ?float => 
                        $record->weight_kg && $record->weight_kg > 0 ? 
                        $record->current_price / $record->weight_kg : null
                    )
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('current_price / NULLIF(weight_kg, 0) ' . $direction);
                    }),
                Tables\Columns\IconColumn::make('is_in_stock')
                    ->boolean()
                    ->label('In Stock')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stock_status')
                    ->options([
                        '1' => 'In Stock',
                        '0' => 'Out of Stock',
                    ])
                    ->attribute('is_in_stock'),
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