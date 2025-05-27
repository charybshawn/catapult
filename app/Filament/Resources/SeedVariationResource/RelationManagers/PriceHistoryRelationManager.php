<?php

namespace App\Filament\Resources\SeedVariationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PriceHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'priceHistory';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$')
                    ->columnSpan(1),
                Forms\Components\Select::make('currency')
                    ->options([
                        'USD' => 'USD',
                        'CAD' => 'CAD',
                        'EUR' => 'EUR',
                        'GBP' => 'GBP',
                    ])
                    ->default('USD')
                    ->required()
                    ->columnSpan(1),
                Forms\Components\Toggle::make('is_in_stock')
                    ->required()
                    ->default(true)
                    ->columnSpan(1),
                Forms\Components\DateTimePicker::make('scraped_at')
                    ->required()
                    ->default(now())
                    ->columnSpan(1),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('price')
            ->columns([
                Tables\Columns\TextColumn::make('price')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('currency')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_in_stock')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scraped_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ])
            ->defaultSort('scraped_at', 'desc');
    }
} 