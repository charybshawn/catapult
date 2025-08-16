<?php

namespace App\Filament\Resources\CropResource\Actions;

use App\Models\Recipe;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

/**
 * Crop Batch Debug Action
 * Dedicated action class for crop batch debugging functionality
 */
class CropBatchDebugAction
{
    /**
     * Create the debug action
     */
    public static function make(): Action
    {
        return Action::make('debug')
            ->label('')
            ->icon('heroicon-o-code-bracket')
            ->tooltip('Debug Info')
            ->action(function ($record) {
                // Get first crop for detailed information
                $firstCrop = \App\Models\Crop::where('crop_batch_id', $record->id)->first();
                
                // Get recipe data
                $recipe = Recipe::find($record->recipe_id);
                
                // Get stage history for the batch
                $stageHistory = \App\Models\CropStageHistory::where('crop_batch_id', $record->id)
                    ->with(['stage', 'createdBy'])
                    ->orderBy('entered_at', 'asc')
                    ->get();
                
                // Additional debugging: Check for stage history by crop_id instead of crop_batch_id
                $stageHistoryByCrop = [];
                if ($firstCrop) {
                    $stageHistoryByCrop = \App\Models\CropStageHistory::where('crop_id', $firstCrop->id)
                        ->with(['stage', 'createdBy'])
                        ->orderBy('entered_at', 'asc')
                        ->get();
                }
                
                // Check all stage history records in the system to see if we can find any reference
                $allStageHistoryForBatch = \App\Models\CropStageHistory::whereIn('crop_id', function($query) use ($record) {
                    $query->select('id')
                          ->from('crops')
                          ->where('crop_batch_id', $record->id);
                })->with(['stage', 'createdBy'])->get();
                
                // Get all crops in this batch for debugging
                $allCropsInBatch = \App\Models\Crop::where('crop_batch_id', $record->id)->get();
                
                // Render the blade view with all the data
                $htmlOutput = view('filament.actions.crop-batch-debug', [
                    'record' => $record,
                    'firstCrop' => $firstCrop,
                    'recipe' => $recipe,
                    'stageHistory' => $stageHistory,
                    'stageHistoryByCrop' => $stageHistoryByCrop,
                    'allStageHistoryForBatch' => $allStageHistoryForBatch,
                    'allCropsInBatch' => $allCropsInBatch,
                ])->render();

                Notification::make()
                    ->title('Crop Batch Debug Information')
                    ->body($htmlOutput)
                    ->persistent()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray'),
                    ])
                    ->send();
            });
    }

    /**
     * Gather all debug data for a crop batch record
     */
    protected static function gatherDebugData($record): array
    {
        $now = now();

        // Get first crop for detailed information
        $firstCrop = \App\Models\Crop::where('crop_batch_id', $record->id)->first();

        return [
            'batch' => static::getBatchData($record, $now),
            'stage_timestamps' => static::getStageTimestamps($record),
            'recipe' => static::getRecipeData($record),
            'time_calculations' => static::getTimeCalculations($record, $firstCrop),
        ];
    }

    /**
     * Get batch information data
     */
    protected static function getBatchData($record, Carbon $now): array
    {
        return [
            'Batch ID' => $record->id,
            'Crop Count' => $record->crop_count,
            'Tray Numbers' => implode(', ', $record->tray_numbers_array ?? []),
            'Recipe ID' => $record->recipe_id,
            'Recipe Name' => $record->recipe_name ?? 'Unknown',
            'Current Stage' => $record->current_stage_name.' (ID: '.($record->current_stage_id ?? 'N/A').')',
            'Stage Color' => $record->current_stage_color ?? 'N/A',
            'Current Time' => $now->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get stage timestamps data with safe formatting
     */
    protected static function getStageTimestamps($record): array
    {
        return [
            'Soaking At' => $record->soaking_at ? (is_string($record->soaking_at) ? $record->soaking_at : $record->soaking_at->format('Y-m-d H:i:s')) : 'N/A',
            'Germination At' => $record->germination_at ? (is_string($record->germination_at) ? $record->germination_at : $record->germination_at->format('Y-m-d H:i:s')) : 'N/A',
            'Blackout At' => $record->blackout_at ? (is_string($record->blackout_at) ? $record->blackout_at : $record->blackout_at->format('Y-m-d H:i:s')) : 'N/A',
            'Light At' => $record->light_at ? (is_string($record->light_at) ? $record->light_at : $record->light_at->format('Y-m-d H:i:s')) : 'N/A',
            'Harvested At' => $record->harvested_at ? (is_string($record->harvested_at) ? $record->harvested_at : $record->harvested_at->format('Y-m-d H:i:s')) : 'N/A',
            'Expected Harvest' => $record->expected_harvest_at ? (is_string($record->expected_harvest_at) ? $record->expected_harvest_at : $record->expected_harvest_at->format('Y-m-d H:i:s')) : 'N/A',
        ];
    }

    /**
     * Get recipe data
     */
    protected static function getRecipeData($record): array
    {
        $recipe = Recipe::find($record->recipe_id);

        if (! $recipe) {
            return [];
        }

        $cultivarName = 'N/A';
        if ($recipe->common_name && $recipe->cultivar_name) {
            $cultivarName = $recipe->common_name.' - '.$recipe->cultivar_name;
        } elseif ($recipe->common_name) {
            $cultivarName = $recipe->common_name;
        }

        return [
            'Recipe ID' => $recipe->id,
            'Recipe Name' => $recipe->name,
            'Variety' => $cultivarName,
            'Lot Number' => $recipe->lot_number ?? 'N/A',
            'Common Name' => $recipe->common_name ?? 'N/A',
            'Cultivar' => $recipe->cultivar_name ?? 'N/A',
            'Category' => $recipe->category ?? 'N/A',
            'Master Seed Cat ID' => $recipe->master_seed_catalog_id ?? 'N/A',
            'Master Cultivar ID' => $recipe->master_cultivar_id ?? 'N/A',
            'Germination Days' => $recipe->germination_days ?? 'N/A',
            'Blackout Days' => $recipe->blackout_days ?? 'N/A',
            'Light Days' => $recipe->light_days ?? 'N/A',
            'Days to Maturity' => $recipe->days_to_maturity ?? 'N/A',
            'Seed Soak Hours' => $recipe->seed_soak_hours ?? 'N/A',
            'Requires Soaking' => $recipe->requires_soaking ? 'Yes' : 'No',
            'Seed Density (g/tray)' => $recipe->seed_density_grams_per_tray ?? 'N/A',
            'Expected Yield (g)' => $recipe->expected_yield_grams ?? 'N/A',
            'Buffer %' => $recipe->buffer_percentage ?? 'N/A',
            'Is Active' => $recipe->is_active ? 'Yes' : 'No',
        ];
    }

    /**
     * Get time calculations and timeline data
     */
    protected static function getTimeCalculations($record, $firstCrop): array
    {
        $timeCalculations = [];

        // Current stage age
        $timeCalculations['Current Stage Age'] = [
            'Display Value' => $record->stage_age_display ?? 'Unknown',
            'Minutes' => $record->stage_age_minutes ?? 'N/A',
        ];

        // Time to next stage
        $timeCalculations['Time to Next Stage'] = [
            'Display Value' => $record->time_to_next_stage_display ?? 'Unknown',
            'Minutes' => $record->time_to_next_stage_minutes ?? 'N/A',
        ];

        // Total crop age
        $timeCalculations['Total Crop Age'] = [
            'Display Value' => $record->total_age_display ?? 'Unknown',
            'Minutes' => $record->total_age_minutes ?? 'N/A',
        ];

        // Add stage timeline using service
        if ($firstCrop) {
            $timelineService = app(\App\Services\CropStageTimelineService::class);
            $timeline = $timelineService->generateTimeline($firstCrop);

            $timeCalculations['Stage Timeline'] = [];
            foreach ($timeline as $stageCode => $stage) {
                $status = $stage['status'] ?? 'unknown';
                $duration = $stage['duration'] ?? 'N/A';
                $timeCalculations['Stage Timeline'][$stage['name']] = ucfirst($status).
                    ($duration !== 'N/A' && $duration ? " ({$duration})" : '');
            }

            // Crop debug details (removed redundant "Planting At")
            $timeCalculations['--- CROP DEBUG ---'] = [];
            $timeCalculations['--- CROP DEBUG ---']['Crop ID'] = $firstCrop->id;
            $timeCalculations['--- CROP DEBUG ---']['Current Stage ID'] = $firstCrop->current_stage_id;
            $timeCalculations['--- CROP DEBUG ---']['Current Stage Code'] = $firstCrop->currentStage?->code ?? 'NULL';
            $timeCalculations['--- CROP DEBUG ---']['Germination At'] = $firstCrop->germination_at ? (is_string($firstCrop->germination_at) ? $firstCrop->germination_at : $firstCrop->germination_at->format('Y-m-d H:i:s')) : 'NULL';
            $timeCalculations['--- CROP DEBUG ---']['Stage Age Minutes'] = $firstCrop->stage_age_minutes ?? 'NULL';
            $timeCalculations['--- CROP DEBUG ---']['Stage Age Display'] = $firstCrop->stage_age_display ?? 'NULL';
        }

        // Next stage info
        $recipe = Recipe::find($record->recipe_id);
        $currentStageCode = $record->currentStage?->code;
        if ($recipe && $currentStageCode !== 'harvested') {
            $nextStage = match ($currentStageCode) {
                'soaking' => 'germination',
                'germination' => 'blackout',
                'blackout' => 'light',
                'light' => 'harvested',
                default => null
            };

            if ($nextStage) {
                $timeCalculations['Next Stage Info'] = [
                    'Current Stage' => $record->current_stage_name,
                    'Next Stage' => $nextStage,
                ];
            }
        }

        return $timeCalculations;
    }

    /**
     * Format debug output as HTML
     */
    protected static function formatDebugOutput(array $debugData): string
    {
        $batchDataHtml = static::formatDebugSection('Batch Information', $debugData['batch']);
        $stageDataHtml = static::formatDebugSection('Stage Timestamps', $debugData['stage_timestamps']);
        $recipeDataHtml = ! empty($debugData['recipe']) ?
            static::formatDebugSection('Recipe Data', $debugData['recipe']) :
            '<div class="text-gray-500 dark:text-gray-400 mb-4">Recipe not found</div>';
        $timeCalcHtml = static::formatTimeCalculationsSection($debugData['time_calculations']);

        return $batchDataHtml.$stageDataHtml.$recipeDataHtml.$timeCalcHtml;
    }

    /**
     * Format debug section HTML
     */
    protected static function formatDebugSection(string $title, array $data): string
    {
        $html = '<div class="mb-4">';
        $html .= '<h3 class="text-lg font-medium mb-2">'.$title.'</h3>';
        $html .= '<div class="overflow-auto max-h-48 space-y-1">';

        foreach ($data as $key => $value) {
            $html .= '<div class="flex">';
            $html .= '<span class="font-medium w-32">'.$key.':</span>';
            $html .= '<span class="text-gray-600 dark:text-gray-400">'.$value.'</span>';
            $html .= '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Format time calculations section HTML
     */
    protected static function formatTimeCalculationsSection(array $timeCalculations): string
    {
        $html = '<div class="mb-4">';
        $html .= '<h3 class="text-lg font-medium mb-2">Time Calculations</h3>';
        $html .= '<div class="overflow-auto max-h-80 space-y-4">';

        foreach ($timeCalculations as $section => $data) {
            $html .= '<div class="border-t pt-2">';
            $html .= '<h4 class="font-medium text-blue-600 dark:text-blue-400 mb-1">'.$section.'</h4>';

            foreach ($data as $key => $value) {
                $html .= '<div class="flex">';
                $html .= '<span class="font-medium w-40 text-sm">'.$key.':</span>';
                $html .= '<span class="text-gray-600 dark:text-gray-400 text-sm">'.$value.'</span>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div></div>';

        return $html;
    }
}
