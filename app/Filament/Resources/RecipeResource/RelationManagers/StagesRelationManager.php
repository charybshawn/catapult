<?php

namespace App\Filament\Resources\RecipeResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
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

class StagesRelationManager extends RelationManager
{
    protected static string $relationship = 'stages';

    protected static ?string $recordTitleAttribute = 'stage';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('stage')
                    ->options([
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                    ])
                    ->required(),
                    
                Grid::make()
                    ->schema([
                        TextInput::make('temperature_min_celsius')
                            ->label('Min Temp (°C)')
                            ->numeric()
                            ->step(0.1)
                            ->required(),
                            
                        TextInput::make('temperature_max_celsius')
                            ->label('Max Temp (°C)')
                            ->numeric()
                            ->step(0.1)
                            ->required(),
                    ]),
                    
                Grid::make()
                    ->schema([
                        TextInput::make('humidity_min_percent')
                            ->label('Min Humidity (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                            
                        TextInput::make('humidity_max_percent')
                            ->label('Max Humidity (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    ]),
                    
                Textarea::make('notes')
                    ->maxLength(65535),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stage')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'germination' => 'info',
                        'blackout' => 'gray',
                        'light' => 'success',
                        default => 'gray',
                    }),
                    
                TextColumn::make('temperature_min_celsius')
                    ->label('Min Temp')
                    ->suffix(' °C'),
                    
                TextColumn::make('temperature_max_celsius')
                    ->label('Max Temp')
                    ->suffix(' °C'),
                    
                TextColumn::make('humidity_min_percent')
                    ->label('Min Humidity')
                    ->suffix('%'),
                    
                TextColumn::make('humidity_max_percent')
                    ->label('Max Humidity')
                    ->suffix('%'),
                    
                TextColumn::make('notes')
                    ->limit(30)
                    ->wrap(),
            ])
            ->filters([
                //
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