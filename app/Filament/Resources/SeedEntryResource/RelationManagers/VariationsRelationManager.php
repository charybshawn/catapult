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
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('size')
                            ->label('Size Description')
                            ->placeholder('e.g., 25g, 1 oz, Large packet')
                            ->helperText('Descriptive size as shown to customers')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('sku')
                            ->maxLength(255)
                            ->placeholder('SKU-001'),
                    ]),
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('weight_kg')
                            ->numeric()
                            ->step('0.0001')
                            ->label('Weight (kg)')
                            ->placeholder('0.025')
                            ->helperText('Weight in kilograms for calculations'),
                        Forms\Components\Select::make('unit')
                            ->label('Unit')
                            ->options([
                                'grams' => 'Grams',
                                'kg' => 'Kilograms', 
                                'oz' => 'Ounces',
                                'lbs' => 'Pounds',
                                'mg' => 'Milligrams',
                            ])
                            ->default('grams')
                            ->required()
                            ->helperText('Unit for database storage'),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD',
                                'CAD' => 'CAD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                            ])
                            ->default('CAD')
                            ->required(),
                    ]),
                
                // Hidden fields - kept for data integrity but not shown to user
                Forms\Components\Hidden::make('original_weight_value'),
                Forms\Components\Hidden::make('original_weight_unit'),
                Forms\Components\TextInput::make('current_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Toggle::make('is_available')
                    ->label('Available')
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
            ->recordTitleAttribute('size')
            ->columns([
                Tables\Columns\TextColumn::make('size')
                    ->label('Size Description')
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
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Available')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_checked_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('availability')
                    ->options([
                        '1' => 'Available',
                        '0' => 'Not Available',
                    ])
                    ->attribute('is_available'),
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