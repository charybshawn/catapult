<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecipeResource\Pages;
use App\Filament\Resources\RecipeResource\RelationManagers;
use App\Models\Recipe;
use App\Models\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;

class RecipeResource extends Resource
{
    protected static ?string $model = Recipe::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Recipes';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Recipe Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('seed_variety_id')
                            ->relationship('seedVariety', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('crop_type')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\Select::make('supplier_id')
                                    ->relationship('supplier', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ])
                            ->required(),

                        Forms\Components\Select::make('supplier_soil_id')
                            ->label('Soil Supplier')
                            ->options(fn () => Supplier::where('type', 'soil')
                                ->orWhereNull('type')
                                ->pluck('name', 'id'))
                            ->searchable(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Growing Parameters')
                    ->schema([
                        Forms\Components\TextInput::make('seed_soak_days')
                            ->label('Seed Soak Days')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Forms\Components\TextInput::make('germination_days')
                            ->label('Germination Days')
                            ->helperText('Days in germination stage')
                            ->numeric()
                            ->minValue(0)
                            ->default(3)
                            ->required(),

                        Forms\Components\TextInput::make('blackout_days')
                            ->label('Blackout Days')
                            ->helperText('Days in blackout stage')
                            ->numeric()
                            ->minValue(0)
                            ->default(2)
                            ->required(),

                        Forms\Components\TextInput::make('light_days')
                            ->label('Light Days')
                            ->helperText('Days under light until harvest')
                            ->numeric()
                            ->minValue(0)
                            ->default(7)
                            ->required(),
                        
                        Forms\Components\TextInput::make('seed_density_grams_per_tray')
                            ->label('Seed Density (g/tray)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required(),
                            
                        Forms\Components\TextInput::make('expected_yield_grams')
                            ->label('Expected Yield (g/tray)')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Notes')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('seedVariety.name')
                    ->label('Seed Variety')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('soilSupplier.name')
                    ->label('Soil Supplier')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('totalDays')
                    ->label('Total Days')
                    ->getStateUsing(fn (Recipe $record): int => $record->totalDays())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('(germination_days + blackout_days + light_days) ' . $direction);
                    }),
                    
                Tables\Columns\TextColumn::make('seed_density_grams_per_tray')
                    ->label('Seed Density (g)')
                    ->numeric(1)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('expected_yield_grams')
                    ->label('Yield (g)')
                    ->numeric(0)
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                    
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
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
                    
                Tables\Filters\SelectFilter::make('seed_variety_id')
                    ->label('Seed Variety')
                    ->relationship('seedVariety', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to delete this recipe?')
                    ->modalSubmitActionLabel('Delete')
                    ->before(function (Tables\Actions\DeleteAction $action, Recipe $record) {
                        // Check if recipe has ACTIVE crops specifically
                        $activeCropsCount = $record->crops()->where('current_stage', '!=', 'harvested')->count();
                        $totalCropsCount = $record->crops()->count();
                        
                        if ($activeCropsCount > 0) {
                            // There are ACTIVE crops for this recipe, let's confirm with the user
                            $action->requiresConfirmation(false); // Disable the default confirmation
                            
                            $action->modalContent(view(
                                'filament.resources.recipe-resource.pages.recipe-crop-delete-warning',
                                [
                                    'activeCropsCount' => $activeCropsCount,
                                    'totalCropsCount' => $totalCropsCount,
                                    'recipeName' => $record->name,
                                    'hasActiveCrops' => true,
                                ]
                            ));
                            
                            $action->modalSubmitAction(
                                fn (Tables\Actions\DeleteAction $action) => $action
                                    ->label('Delete recipe and all ' . $totalCropsCount . ' crops')
                                    ->color('danger')
                            );
                            
                            // Override the delete action
                            $action->action(function () use ($record) {
                                try {
                                    Log::info('Starting deletion of recipe ID: ' . $record->id);
                                    
                                    // Delete related crops first
                                    $cropCount = $record->crops()->count();
                                    Log::info("Deleting {$cropCount} crops for recipe ID: " . $record->id);
                                    $record->crops()->delete();
                                    
                                    // Then delete the recipe
                                    Log::info('Deleting recipe ID: ' . $record->id);
                                    $record->delete();
                                    
                                    Log::info('Successfully deleted recipe ID: ' . $record->id);
                                    
                                    Notification::make()
                                        ->success()
                                        ->title('Recipe and associated crops deleted')
                                        ->send();
                                } catch (\Exception $e) {
                                    Log::error('Error deleting recipe: ' . $e->getMessage());
                                    
                                    Notification::make()
                                        ->danger()
                                        ->title('Error deleting recipe')
                                        ->body('An error occurred while deleting: ' . $e->getMessage())
                                        ->send();
                                }
                            });
                        } else if ($totalCropsCount > 0) {
                            // There are only inactive/completed crops for this recipe
                            $action->requiresConfirmation(false); // Disable the default confirmation
                            
                            $action->modalContent(view(
                                'filament.resources.recipe-resource.pages.recipe-crop-delete-warning',
                                [
                                    'activeCropsCount' => 0,
                                    'totalCropsCount' => $totalCropsCount,
                                    'recipeName' => $record->name,
                                    'hasActiveCrops' => false,
                                ]
                            ));
                            
                            $action->modalSubmitAction(
                                fn (Tables\Actions\DeleteAction $action) => $action
                                    ->label('Delete recipe and associated crops')
                                    ->color('danger')
                            );
                            
                            // Use the same action as above
                            $action->action(function () use ($record) {
                                try {
                                    Log::info('Starting deletion of recipe ID: ' . $record->id);
                                    
                                    // Delete related crops first
                                    $cropCount = $record->crops()->count();
                                    Log::info("Deleting {$cropCount} crops for recipe ID: " . $record->id);
                                    $record->crops()->delete();
                                    
                                    // Then delete the recipe
                                    Log::info('Deleting recipe ID: ' . $record->id);
                                    $record->delete();
                                    
                                    Log::info('Successfully deleted recipe ID: ' . $record->id);
                                    
                                    Notification::make()
                                        ->success()
                                        ->title('Recipe and associated crops deleted')
                                        ->send();
                                } catch (\Exception $e) {
                                    Log::error('Error deleting recipe: ' . $e->getMessage());
                                    
                                    Notification::make()
                                        ->danger()
                                        ->title('Error deleting recipe')
                                        ->body('An error occurred while deleting: ' . $e->getMessage())
                                        ->send();
                                }
                            });
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete these recipes?')
                        ->modalSubmitActionLabel('Delete')
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            // Check if any of the selected recipes have ACTIVE crops
                            $recipesWithActiveCrops = $records->filter(function ($record) {
                                return $record->crops()->where('current_stage', '!=', 'harvested')->count() > 0;
                            });
                            
                            $recipesWithCrops = $records->filter(function ($record) {
                                return $record->crops()->count() > 0;
                            });
                            
                            if ($recipesWithActiveCrops->isNotEmpty()) {
                                // There are recipes with ACTIVE crops, let's confirm with the user
                                $recipesCount = $recipesWithActiveCrops->count();
                                $totalRecipesWithCrops = $recipesWithCrops->count();
                                $activecropsCount = $recipesWithActiveCrops->map(fn ($recipe) => $recipe->crops()->where('current_stage', '!=', 'harvested')->count())->sum();
                                $totalCropsCount = $recipesWithCrops->map(fn ($recipe) => $recipe->crops()->count())->sum();
                                
                                $action->requiresConfirmation(false); // Disable the default confirmation
                                
                                $action->modalContent(view(
                                    'filament.resources.recipe-resource.pages.recipe-crops-bulk-delete-warning',
                                    [
                                        'recipesCount' => $recipesCount,
                                        'totalRecipesWithCrops' => $totalRecipesWithCrops,
                                        'activeCropsCount' => $activecropsCount,
                                        'totalCropsCount' => $totalCropsCount,
                                        'hasActiveCrops' => true,
                                    ]
                                ));
                                
                                $action->modalSubmitAction(
                                    fn (Tables\Actions\DeleteBulkAction $action) => $action
                                        ->label('Delete recipes and all crops')
                                        ->color('danger')
                                );
                                
                                // Override the delete action
                                $action->action(function () use ($records) {
                                    try {
                                        Log::info('Starting bulk deletion of ' . $records->count() . ' recipes');
                                        
                                        // Force cascade delete all selected recipes and their crops
                                        $recipes = Recipe::whereIn('id', $records->pluck('id'))->get();
                                        
                                        foreach ($recipes as $recipe) {
                                            // Delete related crops first
                                            $cropCount = $recipe->crops()->count();
                                            Log::info("Deleting {$cropCount} crops for recipe ID: " . $recipe->id);
                                            $recipe->crops()->delete();
                                            
                                            // Then delete the recipe
                                            Log::info('Deleting recipe ID: ' . $recipe->id);
                                            $recipe->delete();
                                        }
                                        
                                        Log::info('Successfully completed bulk deletion');
                                        
                                        Notification::make()
                                            ->success()
                                            ->title('Recipes and associated crops deleted')
                                            ->send();
                                    } catch (\Exception $e) {
                                        Log::error('Error in bulk recipe deletion: ' . $e->getMessage());
                                        
                                        Notification::make()
                                            ->danger()
                                            ->title('Error deleting recipes')
                                            ->body('An error occurred while deleting: ' . $e->getMessage())
                                            ->send();
                                    }
                                });
                            } else if ($recipesWithCrops->isNotEmpty()) {
                                // There are recipes with only inactive/completed crops
                                $totalRecipesWithCrops = $recipesWithCrops->count();
                                $totalCropsCount = $recipesWithCrops->map(fn ($recipe) => $recipe->crops()->count())->sum();
                                
                                $action->requiresConfirmation(false); // Disable the default confirmation
                                
                                $action->modalContent(view(
                                    'filament.resources.recipe-resource.pages.recipe-crops-bulk-delete-warning',
                                    [
                                        'recipesCount' => 0,
                                        'totalRecipesWithCrops' => $totalRecipesWithCrops,
                                        'activeCropsCount' => 0,
                                        'totalCropsCount' => $totalCropsCount,
                                        'hasActiveCrops' => false,
                                    ]
                                ));
                                
                                $action->modalSubmitAction(
                                    fn (Tables\Actions\DeleteBulkAction $action) => $action
                                        ->label('Delete recipes and associated crops')
                                        ->color('danger')
                                );
                                
                                // Use the same action as above
                                $action->action(function () use ($records) {
                                    try {
                                        Log::info('Starting bulk deletion of ' . $records->count() . ' recipes');
                                        
                                        // Force cascade delete all selected recipes and their crops
                                        $recipes = Recipe::whereIn('id', $records->pluck('id'))->get();
                                        
                                        foreach ($recipes as $recipe) {
                                            // Delete related crops first
                                            $cropCount = $recipe->crops()->count();
                                            Log::info("Deleting {$cropCount} crops for recipe ID: " . $recipe->id);
                                            $recipe->crops()->delete();
                                            
                                            // Then delete the recipe
                                            Log::info('Deleting recipe ID: ' . $recipe->id);
                                            $recipe->delete();
                                        }
                                        
                                        Log::info('Successfully completed bulk deletion');
                                        
                                        Notification::make()
                                            ->success()
                                            ->title('Recipes and associated crops deleted')
                                            ->send();
                                    } catch (\Exception $e) {
                                        Log::error('Error in bulk recipe deletion: ' . $e->getMessage());
                                        
                                        Notification::make()
                                            ->danger()
                                            ->title('Error deleting recipes')
                                            ->body('An error occurred while deleting: ' . $e->getMessage())
                                            ->send();
                                    }
                                });
                            }
                        }),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->action(fn (Recipe $recipe) => $recipe->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (Recipe $recipe) => $recipe->update(['is_active' => false])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // No relation managers needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecipes::route('/'),
            'create' => Pages\CreateRecipe::route('/create'),
            'view' => Pages\ViewRecipe::route('/{record}'),
            'edit' => Pages\EditRecipe::route('/{record}/edit'),
        ];
    }
}
