<?php

namespace App\Filament\Resources\RecipeResource\Tables;

use App\Models\Recipe;
use App\Services\InventoryManagementService;
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
            Tables\Columns\TextColumn::make('name')
                ->searchable()->sortable()->toggleable(isToggledHiddenByDefault: false),
                
            Tables\Columns\TextColumn::make('lot_number')
                ->label('Seed Lot')->searchable()->sortable()->toggleable()
                ->formatStateUsing(fn ($state, $record) => static::formatSeedLotDisplay($state, $record)),
                
            Tables\Columns\TextColumn::make('soilConsumable.name')
                ->label('Soil')->searchable()->sortable()->toggleable(),
                
            Tables\Columns\TextColumn::make('totalDays')
                ->label('Total Days')->toggleable()
                ->getStateUsing(fn (Recipe $record): float => $record->totalDays())
                ->sortable(query: fn (Builder $query, string $direction) => 
                    $query->orderByRaw('(germination_days + blackout_days + light_days) ' . $direction)),
                
            Tables\Columns\TextColumn::make('days_to_maturity')
                ->label('DTM')->numeric(1)->sortable()->toggleable(),
                
            Tables\Columns\TextColumn::make('seed_density_grams_per_tray')
                ->label('Seed Density (g)')->numeric(1)->sortable()->toggleable(),
                
            Tables\Columns\TextColumn::make('expected_yield_grams')
                ->label('Yield (g)')->numeric(0)->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            Tables\Columns\TextColumn::make('germination_days')
                ->label('Germ. Days')->numeric(1)->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            Tables\Columns\TextColumn::make('blackout_days')
                ->label('Blackout Days')->numeric(1)->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            Tables\Columns\TextColumn::make('light_days')
                ->label('Light Days')->numeric(1)->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')->boolean()->sortable()->toggleable(),
                
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
        ];
    }
    
    /**
     * Returns Filament table filters
     */
    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('is_active')
                ->label('Status')
                ->options(['1' => 'Active', 'unit' => 'Inactive']),
                
            Tables\Filters\SelectFilter::make('lot_number')
                ->label('Seed Lot')
                ->options(fn () => static::getSeedLotFilterOptions())
                ->searchable()->preload(),
        ];
    }
    
    /**
     * Returns Filament table actions
     */
    public static function actions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make()->tooltip('View record'),
                Tables\Actions\EditAction::make()->tooltip('Edit recipe'),
                static::getCloneAction(),
                static::getUpdateGrowsAction(),
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
            Tables\Actions\DeleteBulkAction::make()
                ->requiresConfirmation()
                ->before(fn ($action, Collection $records) => static::validateBulkDeletion($action, $records)),
        ];
    }
    
    /**
     * Configure query modifications
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['seedConsumable', 'soilConsumable']);
    }
    
    /**
     * Get default sort configuration
     */
    public static function getDefaultSort(): array
    {
        return ['name', 'asc'];
    }
    
    // ===== HELPER METHODS =====
    
    protected static function formatSeedLotDisplay($state, $record): string
    {
        if (!$state) {
            if ($record->seedConsumable) {
                $available = max(0, $record->seedConsumable->total_quantity - $record->seedConsumable->consumed_quantity);
                $unit = $record->seedConsumable->quantity_unit ?? 'g';
                return $record->seedConsumable->name . " ({$available} {$unit} available)";
            }
            return '-';
        }
        
        // TODO: Extract to App\Services\Recipe\SeedLotDisplayService
        $lotInventoryService = app(InventoryManagementService::class);
        $summary = $lotInventoryService->getLotSummary($state);
        return $summary['available'] <= 0 ? "{$state} (Depleted)" : "{$state} ({$summary['available']}g)";
    }
    
    protected static function getSeedLotFilterOptions(): array
    {
        // TODO: Extract to App\Services\Recipe\SeedLotFilterService
        $lotInventoryService = app(InventoryManagementService::class);
        $lotNumbers = $lotInventoryService->getAllLotNumbers();
        $options = [];
        
        foreach ($lotNumbers as $lotNumber) {
            $summary = $lotInventoryService->getLotSummary($lotNumber);
            $status = $summary['available'] > 0 ? "(Available)" : "(Depleted)";
            $options[$lotNumber] = "{$lotNumber} {$status}";
        }
        
        return $options;
    }
    
    protected static function validateBulkDeletion($action, Collection $records): void
    {
        // TODO: Extract to App\Actions\Recipe\ValidateBulkRecipeDeletionAction
        $harvestedStage = \App\Models\CropStage::findByCode('harvested');
        $recipesWithActiveCrops = $records->filter(function ($record) use ($harvestedStage) {
            return $record->crops()->where('current_stage_id', '!=', $harvestedStage?->id)->count() > 0;
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
    
    protected static function getCloneAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('clone')
            ->icon('heroicon-o-document-duplicate')->tooltip('Clone recipe')
            ->action(function (Recipe $record) {
                // TODO: Extract to App\Actions\Recipe\CloneRecipeAction
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
                
                Notification::make()->success()->title('Recipe cloned successfully')->send();
                return redirect()->route('filament.admin.resources.recipes.edit', ['record' => $clone->id]);
            });
    }
    
    protected static function getUpdateGrowsAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('updateGrows')
            ->icon('heroicon-o-arrow-path')->label('Apply to Grows')
            ->tooltip('Apply recipe parameters to existing grows')->color('warning')
            ->action(function (Recipe $record) {
                // TODO: Extract to App\Actions\Recipe\UpdateGrowsFromRecipeAction
                // Complex business logic (~150 lines) should be moved to dedicated Action class
                Notification::make()
                    ->title('Feature Temporarily Disabled')
                    ->body('This action needs to be refactored to use dedicated Action classes.')
                    ->warning()->send();
            });
    }
    
    protected static function getDeleteAction(): Tables\Actions\Action
    {
        return Tables\Actions\DeleteAction::make()
            ->tooltip('Delete recipe')->requiresConfirmation()
            ->before(function (Tables\Actions\DeleteAction $action, Recipe $record) {
                // TODO: Extract to App\Actions\Recipe\ValidateRecipeDeletionAction
                $harvestedStage = \App\Models\CropStage::findByCode('harvested');
                $activeCropsCount = $record->crops()->where('current_stage_id', '!=', $harvestedStage?->id)->count();
                $totalCropsCount = $record->crops()->count();
                
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
    
    protected static function getDeactivateAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('deactivate')
            ->label('Deactivate')->icon('heroicon-o-eye-slash')->color('warning')->requiresConfirmation()
            ->action(function (Recipe $record) {
                // TODO: Extract to App\Actions\Recipe\DeactivateRecipeAction
                $record->update(['is_active' => false]);
                Notification::make()->title('Recipe Deactivated')
                    ->body("'{$record->name}' has been deactivated.")->success()->send();
            })
            ->visible(fn (Recipe $record) => $record->is_active ?? true);
    }
    
    protected static function getActivateAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('activate')
            ->label('Activate')->icon('heroicon-o-eye')->color('success')
            ->action(function (Recipe $record) {
                // TODO: Extract to App\Actions\Recipe\ActivateRecipeAction
                $record->update(['is_active' => true]);
                Notification::make()->title('Recipe Activated')
                    ->body("'{$record->name}' has been activated.")->success()->send();
            })
            ->visible(fn (Recipe $record) => !($record->is_active ?? true));
    }
}