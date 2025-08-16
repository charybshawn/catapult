<?php

namespace App\Filament\Resources\CropBatchResource\Pages;

use App\Filament\Resources\CropBatchResource;
use App\Services\CropBatchDisplayService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCropBatch extends ViewRecord
{
    protected static string $resource = CropBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit Batch')
                ->icon('heroicon-o-pencil-square'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load the actual model with relationships for the infolist
        $this->record->load([
            'crops',
            'recipe.masterSeedCatalog',
            'recipe.masterCultivar'
        ]);
        
        // Transform the record data using the display service
        $displayService = app(CropBatchDisplayService::class);
        $transformedData = $displayService->getCachedForBatch($this->record->id);
        
        if ($transformedData) {
            // Set the transformed attributes directly on the record
            $this->record->setAttribute('recipe_name', $transformedData->recipe_name);
            $this->record->setAttribute('current_stage_name', $transformedData->current_stage_name);
            $this->record->setAttribute('current_stage_code', $transformedData->current_stage_code);
            $this->record->setAttribute('current_stage_color', 'gray'); // Add default color
            $this->record->setAttribute('crop_count', $transformedData->crop_count);
            $this->record->setAttribute('stage_age_display', $transformedData->stage_age_display);
            $this->record->setAttribute('time_to_next_stage_display', $transformedData->time_to_next_stage_display);
            $this->record->setAttribute('total_age_display', $transformedData->total_age_display);
            $this->record->setAttribute('tray_numbers', $transformedData->tray_numbers);
            $this->record->setAttribute('tray_numbers_array', $transformedData->tray_numbers);
            $this->record->setAttribute('germination_date_formatted', $transformedData->germination_date_formatted);
            $this->record->setAttribute('expected_harvest_formatted', $transformedData->expected_harvest_formatted);
            
            // Also set raw data fields for the infolist
            $data['recipe_name'] = $transformedData->recipe_name;
            $data['current_stage_name'] = $transformedData->current_stage_name;
            $data['stage_age_display'] = $transformedData->stage_age_display;
            $data['time_to_next_stage_display'] = $transformedData->time_to_next_stage_display;
            $data['total_age_display'] = $transformedData->total_age_display;
        }
        
        return $data;
    }
}