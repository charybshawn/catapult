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
        // Transform the record data using the display service
        $displayService = app(CropBatchDisplayService::class);
        $transformedData = $displayService->getCachedForBatch($this->record->id);
        
        if ($transformedData) {
            // Add the transformed attributes to the data
            $data['recipe_name'] = $transformedData->recipe_name;
            $data['current_stage_name'] = $transformedData->current_stage_name;
            $data['current_stage_code'] = $transformedData->current_stage_code;
            $data['crop_count'] = $transformedData->crop_count;
            $data['stage_age_display'] = $transformedData->stage_age_display;
            $data['time_to_next_stage_display'] = $transformedData->time_to_next_stage_display;
            $data['total_age_display'] = $transformedData->total_age_display;
            $data['tray_numbers'] = $transformedData->tray_numbers;
            $data['germination_date_formatted'] = $transformedData->germination_date_formatted;
            $data['expected_harvest_formatted'] = $transformedData->expected_harvest_formatted;
        }
        
        return $data;
    }
}