<?php

namespace App\Filament\Resources\CropBatchResource\Pages;

use App\Filament\Resources\CropBatchResource;
use App\Models\CropBatch;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditCropBatch extends EditRecord
{
    protected static string $resource = CropBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->before(function (CropBatch $record) {
                    // Delete all crops in the batch
                    $record->crops()->delete();
                }),
        ];
    }

    /**
     * Mutate form data before filling the form
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load the batch with crops and their recipes
        $batch = $this->record->load(['crops.recipe', 'crops.currentStage']);
        
        // Get data from the first crop for batch-wide fields
        $firstCrop = $batch->crops->first();
        
        if ($firstCrop) {
            $data['current_stage_id'] = $firstCrop->current_stage_id;
            $data['notes'] = $firstCrop->notes;
            $data['soaking_at'] = $firstCrop->soaking_at;
            $data['planting_at'] = $firstCrop->planting_at;
            $data['germination_at'] = $firstCrop->germination_at;
            $data['blackout_at'] = $firstCrop->blackout_at;
            $data['light_at'] = $firstCrop->light_at;
            $data['harvested_at'] = $firstCrop->harvested_at;
        }
        
        return $data;
    }

    /**
     * Handle saving the batch updates
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        DB::beginTransaction();
        
        try {
            // Extract batch-wide fields
            $batchFields = [
                'current_stage_id' => $data['current_stage_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'soaking_at' => $data['soaking_at'] ?? null,
                'planting_at' => $data['planting_at'] ?? null,
                'germination_at' => $data['germination_at'] ?? null,
                'blackout_at' => $data['blackout_at'] ?? null,
                'light_at' => $data['light_at'] ?? null,
                'harvested_at' => $data['harvested_at'] ?? null,
            ];
            
            // Remove null values to avoid overwriting with nulls
            $batchFields = array_filter($batchFields, function ($value) {
                return $value !== null;
            });
            
            // Load crops with necessary relationships to avoid lazy loading issues
            $crops = $record->crops()->with(['recipe', 'currentStage'])->get();
            
            // Update all crops in the batch with batch-wide fields
            foreach ($crops as $crop) {
                $crop->fill($batchFields);
                $crop->save();
            }
            
            // Handle tray number updates from TagsInput
            if (isset($data['tray_numbers']) && is_array($data['tray_numbers'])) {
                $newTrayNumbers = array_values($data['tray_numbers']); // Ensure sequential array
                $existingCrops = $crops->sortBy('tray_number')->values();
                
                // Update existing crops with new tray numbers
                foreach ($existingCrops as $index => $crop) {
                    if (isset($newTrayNumbers[$index])) {
                        $crop->update(['tray_number' => $newTrayNumbers[$index]]);
                    }
                }
                
                // If there are more tray numbers than crops, we might need to handle that
                // For now, we'll just update existing crops
            }
            
            DB::commit();
            
            Notification::make()
                ->success()
                ->title('Batch updated successfully')
                ->body("Updated {$record->crops()->count()} crops in the batch")
                ->send();
            
            return $record;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update crop batch', [
                'batch_id' => $record->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->danger()
                ->title('Update failed')
                ->body('Failed to update the batch: ' . $e->getMessage())
                ->persistent()
                ->send();
            
            throw $e;
        }
    }
    
    /**
     * Get the form schema for batch editing
     */
    protected function getFormSchema(): array
    {
        return \App\Filament\Resources\CropBatchResource\Forms\CropBatchEditForm::schema();
    }
}