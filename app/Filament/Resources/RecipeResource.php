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
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\LogOptions;

class RecipeResource extends Resource
{
    protected static ?string $model = Recipe::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Recipes';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 1;

    public static function getFormSchema(): array
    {
        return [
            Forms\Components\Section::make('Recipe Information')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\Select::make('seed_variety_id')
                        ->relationship('seedVariety', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                            return $action
                                ->modalHeading('Create Seed Variety')
                                ->modalSubmitActionLabel('Create Seed Variety')
                                ->modalWidth('lg')
                                ->form([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Variety Name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('crop_type')
                                        ->label('Crop Type')
                                        ->default('microgreens')
                                        ->maxLength(255),
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                ]);
                        }),

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
                    Forms\Components\TextInput::make('days_to_maturity')
                        ->label('Days to Maturity (DTM)')
                        ->helperText('Total days from planting to harvest')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(12)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($get('germination_days') ?? 0);
                            $blackout = floatval($get('blackout_days') ?? 0);
                            $dtm = floatval($state ?? 0);
                            
                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);
                        }),

                    Forms\Components\TextInput::make('seed_soak_hours')
                        ->label('Seed Soak Hours')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0),

                    Forms\Components\TextInput::make('germination_days')
                        ->label('Germination Days')
                        ->helperText('Days in germination stage')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(3)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($state ?? 0);
                            $blackout = floatval($get('blackout_days') ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);
                            
                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);
                        }),

                    Forms\Components\TextInput::make('blackout_days')
                        ->label('Blackout Days')
                        ->helperText('Days in blackout stage')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(2)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($get('germination_days') ?? 0);
                            $blackout = floatval($state ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);
                            
                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);
                        }),

                    Forms\Components\TextInput::make('light_days')
                        ->label('Light Days')
                        ->helperText('Automatically calculated from DTM - (germination + blackout)')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true)
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, callable $set, Forms\Get $get) {
                            // Calculate initial value when form loads
                            if ($get('days_to_maturity')) {
                                $germ = floatval($get('germination_days') ?? 0);
                                $blackout = floatval($get('blackout_days') ?? 0);
                                $dtm = floatval($get('days_to_maturity') ?? 0);
                                
                                $lightDays = max(0, $dtm - ($germ + $blackout));
                                $set('light_days', $lightDays);
                            }
                        }),
                    
                    Forms\Components\TextInput::make('seed_density_grams_per_tray')
                        ->label('Seed Density (g/tray)')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->default(25)
                        ->required(),
                        
                    Forms\Components\TextInput::make('expected_yield_grams')
                        ->label('Expected Yield (g/tray)')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01),
                ])
                ->columns(2),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema(self::getFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                    
                Tables\Columns\TextColumn::make('seedConsumable.name')
                    ->label('Seed')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('soilConsumable.name')
                    ->label('Soil')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('totalDays')
                    ->label('Total Days')
                    ->getStateUsing(fn (Recipe $record): float => $record->totalDays())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('(germination_days + blackout_days + light_days) ' . $direction);
                    })
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('days_to_maturity')
                    ->label('DTM')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('seed_density_grams_per_tray')
                    ->label('Seed Density (g)')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('expected_yield_grams')
                    ->label('Yield (g)')
                    ->numeric(0)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('germination_days')
                    ->label('Germ. Days')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('blackout_days')
                    ->label('Blackout Days')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('light_days')
                    ->label('Light Days')
                    ->numeric(1)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
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
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit recipe'),
                Tables\Actions\Action::make('clone')
                    ->icon('heroicon-o-document-duplicate')
                    ->tooltip('Clone recipe')
                    ->action(function (Recipe $record) {
                        $clone = $record->replicate();
                        $clone->name = $record->name . ' (Clone)';
                        $clone->save();
                        
                        // Clone related records
                        foreach ($record->stages as $stage) {
                            $stageClone = $stage->replicate();
                            $stageClone->recipe_id = $clone->id;
                            $stageClone->save();
                        }
                        
                        foreach ($record->wateringSchedule as $schedule) {
                            $scheduleClone = $schedule->replicate();
                            $scheduleClone->recipe_id = $clone->id;
                            $scheduleClone->save();
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Recipe cloned successfully')
                            ->send();
                            
                        return redirect()->route('filament.admin.resources.recipes.edit', ['record' => $clone->id]);
                    }),
                Tables\Actions\Action::make('updateGrows')
                    ->icon('heroicon-o-arrow-path')
                    ->label('Apply to Grows')
                    ->tooltip('Apply recipe parameters to existing grows')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Apply Recipe Parameters to Existing Grows')
                    ->modalDescription(fn (Recipe $record) => "This will update existing grows using {$record->name} with the current recipe parameters.")
                    ->form([
                        Forms\Components\Select::make('current_stage')
                            ->label('Current Stage Filter')
                            ->options([
                                'all' => 'All Stages',
                                'germination' => 'Germination Only',
                                'blackout' => 'Blackout Only',
                                'light' => 'Light Only',
                            ])
                            ->default('all')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('affected_grows_count', null)),
                            
                        Forms\Components\Placeholder::make('affected_grows_count')
                            ->label('Affected Grows')
                            ->content(function (Recipe $record, Forms\Get $get) {
                                $stage = $get('current_stage');
                                
                                $query = \App\Models\Crop::where('recipe_id', $record->id)
                                    ->where('current_stage', '!=', 'harvested');
                                
                                if ($stage !== 'all') {
                                    $query->where('current_stage', $stage);
                                }
                                
                                $count = $query->count();
                                
                                if ($count === 0) {
                                    return "No active grows found for this recipe";
                                }
                                
                                return "{$count} grows will be affected";
                            }),
                            
                        Forms\Components\Checkbox::make('update_germination_days')
                            ->label('Update Germination Days'),
                            
                        Forms\Components\Checkbox::make('update_blackout_days')
                            ->label('Update Blackout Days'),
                            
                        Forms\Components\Checkbox::make('update_light_days')
                            ->label('Update Light Days'),
                            
                        Forms\Components\Checkbox::make('update_days_to_maturity')
                            ->label('Update Days to Maturity'),
                            
                        Forms\Components\Checkbox::make('update_expected_harvest_dates')
                            ->label('Update Expected Harvest Dates')
                            ->helperText('This will recalculate harvest dates based on the recipe settings'),
                            
                        Forms\Components\Checkbox::make('confirm_updates')
                            ->label('I understand this will modify existing grows')
                            ->required()
                            ->helperText('This action cannot be undone.'),
                    ])
                    ->action(function (Recipe $record, array $data) {
                        if (!isset($data['confirm_updates']) || !$data['confirm_updates']) {
                            Notification::make()
                                ->title('Confirmation Required')
                                ->body('You must confirm that you understand this action will modify existing grows.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Build the query for crops to update
                        $query = \App\Models\Crop::where('recipe_id', $record->id)
                            ->where('current_stage', '!=', 'harvested');
                        
                        if ($data['current_stage'] !== 'all') {
                            $query->where('current_stage', $data['current_stage']);
                        }
                        
                        // Get the crops to update
                        $crops = $query->get();
                        
                        if ($crops->isEmpty()) {
                            Notification::make()
                                ->title('No Grows Found')
                                ->body('No active grows found for the selected recipe and stage.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Track counters for notification
                        $totalCrops = $crops->count();
                        $updatedCrops = 0;
                        
                        // Begin a database transaction
                        \Illuminate\Support\Facades\DB::beginTransaction();
                        
                        try {
                            foreach ($crops as $crop) {
                                $needsUpdate = false;
                                
                                // Check if we need to update expected harvest date
                                $recalculateHarvestDate = $data['update_expected_harvest_dates'] ?? false;
                                
                                // Update the crop based on options selected
                                if (($data['update_germination_days'] ?? false) && $crop->current_stage === 'germination') {
                                    $needsUpdate = true;
                                    // Update time_to_next_stage values for crops in germination stage
                                    if ($crop->germination_at) {
                                        $stageEnd = $crop->germination_at->copy()->addDays($record->germination_days);
                                        if (now()->gt($stageEnd)) {
                                            $crop->time_to_next_stage_minutes = 0;
                                            $crop->time_to_next_stage_display = 'Ready to advance';
                                        } else {
                                            $minutes = now()->diffInMinutes($stageEnd);
                                            $crop->time_to_next_stage_minutes = $minutes;
                                            $crop->time_to_next_stage_display = app(\App\Observers\CropObserver::class)->formatDuration(now()->diff($stageEnd));
                                        }
                                    }
                                }
                                
                                if (($data['update_blackout_days'] ?? false) && $crop->current_stage === 'blackout') {
                                    $needsUpdate = true;
                                    // Update time_to_next_stage values for crops in blackout stage
                                    if ($crop->blackout_at) {
                                        $stageEnd = $crop->blackout_at->copy()->addDays($record->blackout_days);
                                        if (now()->gt($stageEnd)) {
                                            $crop->time_to_next_stage_minutes = 0;
                                            $crop->time_to_next_stage_display = 'Ready to advance';
                                        } else {
                                            $minutes = now()->diffInMinutes($stageEnd);
                                            $crop->time_to_next_stage_minutes = $minutes;
                                            $crop->time_to_next_stage_display = app(\App\Observers\CropObserver::class)->formatDuration(now()->diff($stageEnd));
                                        }
                                    }
                                }
                                
                                if (($data['update_light_days'] ?? false) && $crop->current_stage === 'light') {
                                    $needsUpdate = true;
                                    // Update time_to_next_stage values for crops in light stage
                                    if ($crop->light_at) {
                                        $stageEnd = $crop->light_at->copy()->addDays($record->light_days);
                                        if (now()->gt($stageEnd)) {
                                            $crop->time_to_next_stage_minutes = 0;
                                            $crop->time_to_next_stage_display = 'Ready to advance';
                                        } else {
                                            $minutes = now()->diffInMinutes($stageEnd);
                                            $crop->time_to_next_stage_minutes = $minutes;
                                            $crop->time_to_next_stage_display = app(\App\Observers\CropObserver::class)->formatDuration(now()->diff($stageEnd));
                                        }
                                    }
                                }
                                
                                if (($data['update_days_to_maturity'] ?? false) || $recalculateHarvestDate) {
                                    $needsUpdate = true;
                                    // Recalculate expected harvest date
                                    if ($crop->planted_at && $record->days_to_maturity) {
                                        $crop->expected_harvest_at = $crop->planted_at->copy()->addDays($record->days_to_maturity);
                                    }
                                }
                                
                                // Save the crop if any changes were made
                                if ($needsUpdate) {
                                    $crop->save();
                                    $updatedCrops++;
                                }
                            }
                            
                            // Commit the transaction
                            \Illuminate\Support\Facades\DB::commit();
                            
                            Notification::make()
                                ->title('Grows Updated Successfully')
                                ->body("Updated {$updatedCrops} out of {$totalCrops} grows to match recipe settings.")
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            // Rollback the transaction on error
                            \Illuminate\Support\Facades\DB::rollBack();
                            
                            Notification::make()
                                ->title('Error Updating Grows')
                                ->body('An error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete recipe')
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
                                    
                                    // Delete the recipe - related crops will be cascaded automatically
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
                            
                            // Use the same simplified action
                            $action->action(function () use ($record) {
                                try {
                                    Log::info('Starting deletion of recipe ID: ' . $record->id);
                                    
                                    // Delete the recipe - related crops will be cascaded automatically
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
                                
                                // Override the delete action with a simpler version
                                $action->action(function () use ($records) {
                                    try {
                                        Log::info('Starting bulk deletion of ' . $records->count() . ' recipes');
                                        
                                        // Delete the recipes - crops will be automatically cascaded
                                        Recipe::whereIn('id', $records->pluck('id'))->delete();
                                        
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
                                
                                // Use the same simplified action
                                $action->action(function () use ($records) {
                                    try {
                                        Log::info('Starting bulk deletion of ' . $records->count() . ' recipes');
                                        
                                        // Delete the recipes - crops will be automatically cascaded
                                        Recipe::whereIn('id', $records->pluck('id'))->delete();
                                        
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
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                        }),
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
            'edit' => Pages\EditRecipe::route('/{record}/edit'),
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'seed_variety_id', 
                'supplier_soil_id', 
                'germination_days', 
                'blackout_days', 
                'light_days',
                'expected_yield_grams',
                'seed_density_grams_per_tray',
                'is_active',
                'planting_notes',
                'germination_notes',
                'blackout_notes',
                'light_notes',
                'harvesting_notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
