<?php

namespace App\Filament\Resources\RecipeResource\RelationManagers;

use App\Models\Recipe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ComponentRecipesRelationManager extends RelationManager
{
    protected static string $relationship = 'componentRecipes';

    protected static ?string $recordTitleAttribute = 'name';
    
    protected static ?string $title = 'Mix Components';
    
    public function isVisible(): bool
    {
        return $this->ownerRecord->is_mix;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('component_recipe_id')
                    ->label('Component Recipe')
                    ->options(fn () => Recipe::where('is_mix', false)
                        ->where('id', '!=', $this->ownerRecord->id)
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                    
                Forms\Components\TextInput::make('percentage')
                    ->label('Percentage (%)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(25)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Component Recipe')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('seedVariety.name')
                    ->label('Seed Variety')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('pivot.percentage')
                    ->label('Percentage')
                    ->suffix('%')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn() => $this->ownerRecord->is_mix),
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