<?php

namespace App\Filament\Resources\SettingsResource\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\CropStage;
use Filament\Forms\Components\Checkbox;
use Exception;
use App\Filament\Resources\SettingsResource;
use App\Models\Crop;
use App\Models\Recipe;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class RecipeUpdates extends Page
{
    protected static string $resource = SettingsResource::class;

    protected string $view = 'filament.resources.settings-resource.pages.recipe-updates';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Update Existing Grows with Recipe Changes')
                    ->description('This tool allows you to update existing grows with changes from their recipes. Use with caution as this will modify existing data.')
                    ->schema([
                        Select::make('recipe_id')
                            ->label('Recipe')
                            ->options(Recipe::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set) => $set('affected_grows_count', null)),

                        Select::make('current_stage')
                            ->label('Current Stage Filter')
                            ->options([
                                'all' => 'All Stages',
                                'germination' => 'Germination Only',
                                'blackout' => 'Blackout Only',
                                'light' => 'Light Only',
                            ])
                            ->default('all')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set) => $set('affected_grows_count', null)),

                        Placeholder::make('affected_grows_count')
                            ->label('Affected Grows')
                            ->content(function (Get $get, Set $set) {
                                $recipeId = $get('recipe_id');
                                $stage = $get('current_stage');

                                if (! $recipeId) {
                                    return 'Please select a recipe';
                                }

                                $harvestedStage = CropStage::findByCode('harvested');
                                $query = Crop::where('recipe_id', $recipeId)
                                    ->where('current_stage_id', '!=', $harvestedStage?->id);

                                if ($stage !== 'all') {
                                    $stageRecord = CropStage::findByCode($stage);
                                    if ($stageRecord) {
                                        $query->where('current_stage_id', $stageRecord->id);
                                    }
                                }

                                $recipe = Recipe::find($recipeId);
                                $count = $query->count();

                                if ($count === 0) {
                                    return "No active grows found for recipe: {$recipe->name}";
                                }

                                return "{$count} grows will be affected for recipe: {$recipe->name}";
                            }),

                        Checkbox::make('update_germination_days')
                            ->label('Update Germination Days'),

                        Checkbox::make('update_blackout_days')
                            ->label('Update Blackout Days'),

                        Checkbox::make('update_light_days')
                            ->label('Update Light Days'),

                        Checkbox::make('update_days_to_maturity')
                            ->label('Update Days to Maturity'),

                        Checkbox::make('update_expected_harvest_dates')
                            ->label('Update Expected Harvest Dates')
                            ->helperText('This will recalculate harvest dates based on the recipe settings'),

                        Checkbox::make('confirm_updates')
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

        if (! $data['confirm_updates']) {
            Notification::make()
                ->title('Confirmation Required')
                ->body('You must confirm that you understand this action will modify existing grows.')
                ->danger()
                ->send();

            return;
        }

        // Get the recipe
        $recipe = Recipe::find($data['recipe_id']);

        if (! $recipe) {
            Notification::make()
                ->title('Recipe Not Found')
                ->body('Could not find the selected recipe.')
                ->danger()
                ->send();

            return;
        }

        // Build the query for crops to update
        $harvestedStage = CropStage::findByCode('harvested');
        $query = Crop::where('recipe_id', $recipe->id)
            ->where('current_stage_id', '!=', $harvestedStage?->id);

        if ($data['current_stage'] !== 'all') {
            $stageRecord = CropStage::findByCode($data['current_stage']);
            if ($stageRecord) {
                $query->where('current_stage_id', $stageRecord->id);
            }
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
                    // Time calculations are now handled in crop_batches_list_view
                }

                if ($data['update_blackout_days'] && $crop->current_stage === 'blackout') {
                    $needsUpdate = true;
                    // Time calculations are now handled in crop_batches_list_view
                }

                if ($data['update_light_days'] && $crop->current_stage === 'light') {
                    $needsUpdate = true;
                    // Time calculations are now handled in crop_batches_list_view
                }

                if ($data['update_days_to_maturity'] || $recalculateHarvestDate) {
                    $needsUpdate = true;
                    // Expected harvest date is now calculated in crop_batches_list_view
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

        } catch (Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            Notification::make()
                ->title('Error Updating Grows')
                ->body('An error occurred: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
