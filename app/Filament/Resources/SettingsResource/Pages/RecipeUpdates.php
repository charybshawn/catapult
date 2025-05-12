<?php

namespace App\Filament\Resources\SettingsResource\Pages;

use App\Filament\Resources\SettingsResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use App\Models\Recipe;
use App\Models\Crop;
use App\Observers\CropObserver;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Actions\Action;

class RecipeUpdates extends Page
{
    protected static string $resource = SettingsResource::class;

    protected static string $view = 'filament.resources.settings-resource.pages.recipe-updates';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Update Existing Grows with Recipe Changes')
                    ->description('This tool allows you to update existing grows with changes from their recipes. Use with caution as this will modify existing data.')
                    ->schema([
                        Forms\Components\Select::make('recipe_id')
                            ->label('Recipe')
                            ->options(Recipe::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('affected_grows_count', null)),
                            
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
                            ->content(function (Forms\Get $get, Forms\Set $set) {
                                $recipeId = $get('recipe_id');
                                $stage = $get('current_stage');
                                
                                if (!$recipeId) {
                                    return 'Please select a recipe';
                                }
                                
                                $query = Crop::where('recipe_id', $recipeId)
                                    ->where('current_stage', '!=', 'harvested');
                                
                                if ($stage !== 'all') {
                                    $query->where('current_stage', $stage);
                                }
                                
                                $recipe = Recipe::find($recipeId);
                                $count = $query->count();
                                
                                if ($count === 0) {
                                    return "No active grows found for recipe: {$recipe->name}";
                                }
                                
                                return "{$count} grows will be affected for recipe: {$recipe->name}";
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
                            ->helperText('This action cannot be undone. Please back up your data before proceeding.'),
                    ]),
            ])
            ->statePath('data');
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('updateGrows')
                ->label('Update Grows')
                ->action('updateGrows')
                ->color('warning')
                ->requiresConfirmation(),
        ];
    }
    
    public function updateGrows(): void
    {
        $this->validate();
        
        $data = $this->form->getState();
        
        if (!$data['confirm_updates']) {
            Notification::make()
                ->title('Confirmation Required')
                ->body('You must confirm that you understand this action will modify existing grows.')
                ->danger()
                ->send();
            return;
        }
        
        // Get the recipe
        $recipe = Recipe::find($data['recipe_id']);
        
        if (!$recipe) {
            Notification::make()
                ->title('Recipe Not Found')
                ->body('Could not find the selected recipe.')
                ->danger()
                ->send();
            return;
        }
        
        // Build the query for crops to update
        $query = Crop::where('recipe_id', $recipe->id)
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
        DB::beginTransaction();
        
        try {
            foreach ($crops as $crop) {
                $needsUpdate = false;
                
                // Check if we need to update expected harvest date
                $recalculateHarvestDate = $data['update_expected_harvest_dates'] ?? false;
                
                // Update the crop based on options selected
                if ($data['update_germination_days'] && $crop->current_stage === 'germination') {
                    $needsUpdate = true;
                    // Update time_to_next_stage values for crops in germination stage
                    if ($crop->germination_at) {
                        $stageEnd = $crop->germination_at->copy()->addDays($recipe->germination_days);
                        if (now()->gt($stageEnd)) {
                            $crop->time_to_next_stage_minutes = 0;
                            $crop->time_to_next_stage_display = 'Ready to advance';
                        } else {
                            $minutes = now()->diffInMinutes($stageEnd);
                            $crop->time_to_next_stage_minutes = $minutes;
                            $crop->time_to_next_stage_display = app(CropObserver::class)->formatDuration(now()->diff($stageEnd));
                        }
                    }
                }
                
                if ($data['update_blackout_days'] && $crop->current_stage === 'blackout') {
                    $needsUpdate = true;
                    // Update time_to_next_stage values for crops in blackout stage
                    if ($crop->blackout_at) {
                        $stageEnd = $crop->blackout_at->copy()->addDays($recipe->blackout_days);
                        if (now()->gt($stageEnd)) {
                            $crop->time_to_next_stage_minutes = 0;
                            $crop->time_to_next_stage_display = 'Ready to advance';
                        } else {
                            $minutes = now()->diffInMinutes($stageEnd);
                            $crop->time_to_next_stage_minutes = $minutes;
                            $crop->time_to_next_stage_display = app(CropObserver::class)->formatDuration(now()->diff($stageEnd));
                        }
                    }
                }
                
                if ($data['update_light_days'] && $crop->current_stage === 'light') {
                    $needsUpdate = true;
                    // Update time_to_next_stage values for crops in light stage
                    if ($crop->light_at) {
                        $stageEnd = $crop->light_at->copy()->addDays($recipe->light_days);
                        if (now()->gt($stageEnd)) {
                            $crop->time_to_next_stage_minutes = 0;
                            $crop->time_to_next_stage_display = 'Ready to advance';
                        } else {
                            $minutes = now()->diffInMinutes($stageEnd);
                            $crop->time_to_next_stage_minutes = $minutes;
                            $crop->time_to_next_stage_display = app(CropObserver::class)->formatDuration(now()->diff($stageEnd));
                        }
                    }
                }
                
                if ($data['update_days_to_maturity'] || $recalculateHarvestDate) {
                    $needsUpdate = true;
                    // Recalculate expected harvest date
                    if ($crop->planted_at && $recipe->days_to_maturity) {
                        $crop->expected_harvest_at = $crop->planted_at->copy()->addDays($recipe->days_to_maturity);
                    }
                }
                
                // Save the crop if any changes were made
                if ($needsUpdate) {
                    $crop->save();
                    $updatedCrops++;
                }
            }
            
            // Commit the transaction
            DB::commit();
            
            Notification::make()
                ->title('Grows Updated Successfully')
                ->body("Updated {$updatedCrops} out of {$totalCrops} grows to match recipe settings.")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();
            
            Notification::make()
                ->title('Error Updating Grows')
                ->body('An error occurred: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
} 