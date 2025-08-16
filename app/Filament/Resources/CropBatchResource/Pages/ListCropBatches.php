<?php

namespace App\Filament\Resources\CropBatchResource\Pages;

use App\Filament\Resources\CropBatchResource;
use App\Models\CropBatch;
use App\Services\CropBatchDisplayService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListCropBatches extends ListRecords
{
    protected static string $resource = CropBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createCrop')
                ->label('New Crop')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url('/admin/crops/create')
                ->button(),
            Actions\Action::make('viewIndividual')
                ->label('View Individual Crops')
                ->icon('heroicon-o-list-bullet')
                ->color('info')
                ->url('/admin/crops')
                ->tooltip('Switch to individual crop view'),
        ];
    }

    /**
     * Override the table query to use optimized CropBatch scopes
     */
    protected function getTableQuery(): ?Builder
    {
        return CropBatch::forListDisplay()->activeOnly();
    }

    /**
     * Transform records using the service layer for calculated fields
     */
    public function getTableRecords(): Collection
    {
        // Get the raw CropBatch models
        $cropBatches = $this->getTableQuery()->get();
        
        // Add transformed display data as attributes to each model
        $displayService = app(CropBatchDisplayService::class);
        
        foreach ($cropBatches as $batch) {
            $transformedData = $displayService->getCachedForBatch($batch->id);
            if ($transformedData) {
                // Add the transformed attributes to the model
                $batch->setAttribute('recipe_name', $transformedData->recipe_name);
                $batch->setAttribute('tray_numbers', $transformedData->tray_numbers);
                $batch->setAttribute('stage_age_display', $transformedData->stage_age_display);
                $batch->setAttribute('time_to_next_stage_display', $transformedData->time_to_next_stage_display);
                $batch->setAttribute('total_age_display', $transformedData->total_age_display);
                $batch->setAttribute('tray_numbers_formatted', $transformedData->tray_numbers_formatted);
                $batch->setAttribute('current_stage_name', $transformedData->current_stage_name);
                $batch->setAttribute('current_stage_code', $transformedData->current_stage_code);
                $batch->setAttribute('germination_date_formatted', $transformedData->germination_date_formatted);
                $batch->setAttribute('expected_harvest_formatted', $transformedData->expected_harvest_formatted);
            }
        }
        
        return $cropBatches;
    }
}