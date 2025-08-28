<?php

namespace App\Filament\Resources\RecipeResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Models\Recipe;
use App\Models\RecipeOptimizedView;
use App\Models\Consumable;
use App\Filament\Resources\Consumables\SeedResource;
use Filament\Forms;
use Filament\Tables;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RecipeTable
{
    /**
     * Returns Filament table columns
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()->sortable()->toggleable(isToggledHiddenByDefault: false),
                
            TextColumn::make('seed_lot_display')
                ->label('Seed Lot')->searchable(['lot_number', 'seed_consumable_name'])->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->orderBy('lot_number', $direction);
                })->toggleable(),
                
            TextColumn::make('soil_consumable_name')
                ->label('Soil')->searchable()->sortable()->toggleable(),
                
            TextColumn::make('total_days')
                ->label('Total Days')->toggleable()
                ->numeric(1)
                ->sortable(),
                
            TextColumn::make('days_to_maturity')
                ->label('DTM')->numeric(1)->sortable()->toggleable(),
                
            TextColumn::make('seed_density_grams_per_tray')
                ->label('Seed Density (g)')->numeric(1)->sortable()->toggleable(),
                
            TextColumn::make('expected_yield_grams')
                ->label('Yield (g)')->numeric(0)->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            TextColumn::make('germination_days')
                ->label('Germ. Days')->numeric(1)->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            TextColumn::make('blackout_days')
                ->label('Blackout Days')->numeric(1)->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            TextColumn::make('light_days')
                ->label('Light Days')->numeric(1)->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            IconColumn::make('is_active')
                ->label('Active')->boolean()->sortable()->toggleable(),
                
            TextColumn::make('created_at')
                ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            TextColumn::make('updated_at')
                ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }
    
    /**
     * Returns Filament table filters
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('is_active')
                ->label('Status')
                ->options(['1' => 'Active', 'unit' => 'Inactive']),
                
            Filter::make('lot_number')
                ->schema([
                    TextInput::make('lot_number')
                        ->label('Seed Lot')
                        ->placeholder('Enter lot number'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['lot_number'],
                            fn (Builder $query, $value): Builder => $query->where('lot_number', 'like', "%{$value}%"),
                        );
                }),
        ];
    }
    
    /**
     * Returns Filament table actions
     */
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->tooltip('View record'),
                EditAction::make()->tooltip('Edit recipe'),
                static::getCloneAction(),
                static::getUpdateGrowsAction(),
                static::getViewSeedLotAction(),
                static::getDeleteAction(),
                static::getDeactivateAction(),
                static::getActivateAction(),
            ])
            ->label('Actions')->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')->color('gray')->button(),
        ];
    }
    
    /**
     * Returns individual bulk actions
     */
    public static function getBulkActions(): array
    {
        return [
            DeleteBulkAction::make()
                ->requiresConfirmation()
                ->before(fn ($action, Collection $records) => static::validateBulkDeletion($action, $records)),
        ];
    }
    
    /**
     * Configure query modifications
     */
    public static function modifyQuery(Builder $query): Builder
    {
        // No need to eager load when using the view
        return $query;
    }
    
    /**
     * Get default sort configuration
     */
    public static function getDefaultSort(): array
    {
        return ['name', 'asc'];
    }
    
    // ===== HELPER METHODS =====
    
    protected static function validateBulkDeletion($action, Collection $records): void
    {
        // TODO: Extract to App\Actions\Recipe\ValidateBulkRecipeDeletionAction
        $recipesWithActiveCrops = $records->filter(function ($record) {
            return $record->active_crops_count > 0;
        });
        
        if ($recipesWithActiveCrops->isNotEmpty()) {
            $action->cancel();
            Notification::make()
                ->title('Cannot Delete Recipes')
                ->body('Selected recipes have active crops. Consider deactivating instead.')
                ->danger()->persistent()->send();
        }
    }
    
    // ===== ACTION DEFINITIONS =====
    
    protected static function getCloneAction(): Action
    {
        return Action::make('clone')
            ->icon('heroicon-o-document-duplicate')->tooltip('Clone recipe')
            ->action(function ($record) {
                // Need to get the actual Recipe model to clone
                $recipe = Recipe::find($record->id);
                
                // TODO: Extract to App\Actions\Recipe\CloneRecipeAction
                $clone = $recipe->replicate();
                $clone->name = $recipe->name . ' (Clone)';
                $clone->save();
                
                // Clone related records
                foreach ($recipe->stages as $stage) {
                    $stageClone = $stage->replicate();
                    $stageClone->recipe_id = $clone->id;
                    $stageClone->save();
                }
                
                foreach ($recipe->wateringSchedule as $schedule) {
                    $scheduleClone = $schedule->replicate();
                    $scheduleClone->recipe_id = $clone->id;
                    $scheduleClone->save();
                }
                
                Notification::make()->success()->title('Recipe cloned successfully')->send();
                return redirect()->route('filament.admin.resources.recipes.edit', ['record' => $clone->id]);
            });
    }
    
    protected static function getUpdateGrowsAction(): Action
    {
        return Action::make('updateGrows')
            ->icon('heroicon-o-arrow-path')->label('Apply to Grows')
            ->tooltip('Apply recipe parameters to existing grows')->color('warning')
            ->action(function ($record) {
                // TODO: Extract to App\Actions\Recipe\UpdateGrowsFromRecipeAction
                // Complex business logic (~150 lines) should be moved to dedicated Action class
                Notification::make()
                    ->title('Feature Temporarily Disabled')
                    ->body('This action needs to be refactored to use dedicated Action classes.')
                    ->warning()->send();
            });
    }
    
    protected static function getDeleteAction(): Action
    {
        return DeleteAction::make()
            ->tooltip('Delete recipe')->requiresConfirmation()
            ->before(function (DeleteAction $action, $record) {
                // TODO: Extract to App\Actions\Recipe\ValidateRecipeDeletionAction
                $activeCropsCount = $record->active_crops_count;
                $totalCropsCount = $record->total_crops_count;
                
                if ($activeCropsCount > 0 || $totalCropsCount > 0) {
                    $action->cancel();
                    $message = $activeCropsCount > 0 
                        ? "This recipe has {$activeCropsCount} active crops in progress."
                        : "This recipe has {$totalCropsCount} completed crops in the system.";
                    
                    Notification::make()
                        ->title('Cannot Delete Recipe')
                        ->body($message . ' Consider deactivating instead.')
                        ->danger()->persistent()->send();
                }
            });
    }
    
    protected static function getDeactivateAction(): Action
    {
        return Action::make('deactivate')
            ->label('Deactivate')->icon('heroicon-o-eye-slash')->color('warning')->requiresConfirmation()
            ->action(function ($record) {
                // TODO: Extract to App\Actions\Recipe\DeactivateRecipeAction
                Recipe::where('id', $record->id)->update(['is_active' => false]);
                Notification::make()->title('Recipe Deactivated')
                    ->body("'{$record->name}' has been deactivated.")->success()->send();
            })
            ->visible(fn ($record) => $record->is_active ?? true);
    }
    
    protected static function getActivateAction(): Action
    {
        return Action::make('activate')
            ->label('Activate')->icon('heroicon-o-eye')->color('success')
            ->action(function ($record) {
                // TODO: Extract to App\Actions\Recipe\ActivateRecipeAction
                Recipe::where('id', $record->id)->update(['is_active' => true]);
                Notification::make()->title('Recipe Activated')
                    ->body("'{$record->name}' has been activated.")->success()->send();
            })
            ->visible(fn ($record) => !($record->is_active ?? true));
    }
    
    protected static function getViewSeedLotAction(): Action
    {
        return Action::make('view_seed_lot')
            ->label('View Seed Lot')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->tooltip('View the seed lot/consumable details')
            ->url(function ($record) {
                if ($record->seed_consumable_id) {
                    return SeedResource::getUrl('edit', ['record' => $record->seed_consumable_id]);
                }
                
                return null;
            })
            ->visible(function ($record) {
                return $record->seed_consumable_id;
            });
    }
}