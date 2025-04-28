<?php

namespace App\Filament\Resources\RecipeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StagesRelationManager extends RelationManager
{
    protected static string $relationship = 'stages';

    protected static ?string $recordTitleAttribute = 'stage';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('stage')
                    ->options([
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                    ])
                    ->required(),
                    
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('temperature_min_celsius')
                            ->label('Min Temp (°C)')
                            ->numeric()
                            ->step(0.1)
                            ->required(),
                            
                        Forms\Components\TextInput::make('temperature_max_celsius')
                            ->label('Max Temp (°C)')
                            ->numeric()
                            ->step(0.1)
                            ->required(),
                    ]),
                    
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('humidity_min_percent')
                            ->label('Min Humidity (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                            
                        Forms\Components\TextInput::make('humidity_max_percent')
                            ->label('Max Humidity (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    ]),
                    
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stage')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'germination' => 'info',
                        'blackout' => 'gray',
                        'light' => 'success',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('temperature_min_celsius')
                    ->label('Min Temp')
                    ->suffix(' °C'),
                    
                Tables\Columns\TextColumn::make('temperature_max_celsius')
                    ->label('Max Temp')
                    ->suffix(' °C'),
                    
                Tables\Columns\TextColumn::make('humidity_min_percent')
                    ->label('Min Humidity')
                    ->suffix('%'),
                    
                Tables\Columns\TextColumn::make('humidity_max_percent')
                    ->label('Max Humidity')
                    ->suffix('%'),
                    
                Tables\Columns\TextColumn::make('notes')
                    ->limit(30)
                    ->wrap(),
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