<?php

namespace App\Filament\Resources\SeedEntryResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        TextInput::make('size')
                            ->label('Size Description')
                            ->placeholder('e.g., 25g, 1 oz, Large packet')
                            ->helperText('Descriptive size as shown to customers')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('sku')
                            ->maxLength(255)
                            ->placeholder('SKU-001'),
                    ]),
                Grid::make(3)
                    ->schema([
                        TextInput::make('weight_kg')
                            ->numeric()
                            ->step('0.0001')
                            ->label('Weight (kg)')
                            ->placeholder('0.025')
                            ->helperText('Weight in kilograms for calculations'),
                        Select::make('unit')
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
                        Select::make('currency')
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
                Hidden::make('original_weight_value'),
                Hidden::make('original_weight_unit'),
                TextInput::make('current_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Toggle::make('is_available')
                    ->label('Available')
                    ->required()
                    ->default(true),
                DateTimePicker::make('last_checked_at')
                    ->required()
                    ->default(now()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('size')
            ->columns([
                TextColumn::make('size')
                    ->label('Size Description')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('weight_kg')
                    ->numeric()
                    ->label('Weight (kg)')
                    ->sortable(),
                TextColumn::make('current_price')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('price_per_kg')
                    ->label('Price per kg')
                    ->money('USD')
                    ->getStateUsing(fn ($record): ?float => 
                        $record->weight_kg && $record->weight_kg > 0 ? 
                        $record->current_price / $record->weight_kg : null
                    )
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('current_price / NULLIF(weight_kg, 0) ' . $direction);
                    }),
                IconColumn::make('is_available')
                    ->boolean()
                    ->label('Available')
                    ->sortable(),
                TextColumn::make('last_checked_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('availability')
                    ->options([
                        '1' => 'Available',
                        '0' => 'Not Available',
                    ])
                    ->attribute('is_available'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
} 