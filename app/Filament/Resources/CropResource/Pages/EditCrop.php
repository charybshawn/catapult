<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Filament\Resources\CropResource;
use App\Models\Crop;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Forms\Components;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class EditCrop extends BaseEditRecord
{
    protected static string $resource = CropResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->action(function () {
                    // Delete all crops in this batch
                    $record = $this->getRecord();
                    $record->crops()->delete();
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the current record being edited (CropBatch)
        $record = $this->getRecord();
        
        // Add existing tray numbers from the CropBatch
        $data['tray_numbers'] = $record->tray_numbers;
        
        // Get the first actual crop record from this batch to get individual crop data
        $firstCrop = $record->crops()->first();
            
        if ($firstCrop) {
            // Copy any fields that exist on individual crops but not on the batch
            $data['notes'] = $firstCrop->notes ?? '';
            
            // Copy timestamp fields from the first crop
            $data['soaking_at'] = $firstCrop->soaking_at;
            $data['planting_at'] = $firstCrop->planting_at;
            $data['germination_at'] = $firstCrop->germination_at;
            $data['blackout_at'] = $firstCrop->blackout_at;
            $data['light_at'] = $firstCrop->light_at;
            $data['harvested_at'] = $firstCrop->harvested_at;
        }
        
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // $record is a CropBatch instance
        // Get all current crops in this batch
        $currentCrops = $record->crops;
            
        // Get the new tray numbers from the form and ensure it's a flat array
        $newTrayNumbers = [];
        if (isset($data['tray_numbers'])) {
            if (is_array($data['tray_numbers'])) {
                foreach ($data['tray_numbers'] as $trayNumber) {
                    if (is_array($trayNumber)) {
                        // Handle nested array structure from TagsInput
                        foreach ($trayNumber as $number) {
                            if (is_string($number) && !empty(trim($number))) {
                                $newTrayNumbers[] = trim($number);
                            }
                        }
                    } elseif (is_string($trayNumber) && !empty(trim($trayNumber))) {
                        $newTrayNumbers[] = trim($trayNumber);
                    }
                }
            } elseif (is_string($data['tray_numbers'])) {
                // Handle case where it might be a comma-separated string
                $numbers = explode(',', $data['tray_numbers']);
                foreach ($numbers as $number) {
                    if (!empty(trim($number))) {
                        $newTrayNumbers[] = trim($number);
                    }
                }
            }
        }
        
        // Find crops to remove (crops that exist in currentCrops but not in newTrayNumbers)
        $cropsToRemove = $currentCrops->filter(function ($crop) use ($newTrayNumbers) {
            return !in_array($crop->tray_number, $newTrayNumbers);
        });
        
        // Find trays to add (numbers in newTrayNumbers but not in currentCrops)
        $currentTrayNumbers = $currentCrops->pluck('tray_number')->toArray();
        $traysToAdd = array_diff($newTrayNumbers, $currentTrayNumbers);
        
        // Remove the tray_numbers field as it's not a database column
        unset($data['tray_numbers']);
        
        DB::transaction(function () use ($cropsToRemove, $traysToAdd, $currentCrops, $data, $record) {
            // Remove crops that are no longer needed
            foreach ($cropsToRemove as $crop) {
                $crop->delete();
            }
            
            // Add new crops for new tray numbers
            if (!empty($traysToAdd) && $currentCrops->isNotEmpty()) {
                // Use the first crop as a template
                $templateCrop = $currentCrops->first();
                foreach ($traysToAdd as $trayNumber) {
                    $newCrop = $templateCrop->replicate();
                    $newCrop->tray_number = $trayNumber;
                    $newCrop->save();
                }
            }
            
            // Update all crops in the batch with the new data
            // Refresh the crops collection to include newly added ones
            $record->load('crops');
            foreach ($record->crops as $crop) {
                $crop->fill($data)->save();
            }
        });
        
        // Show notification about the update
        $notification = Notification::make()
            ->title('Grow Batch Updated')
            ->body("Successfully updated tray configuration.");
            
        if (!empty($traysToAdd)) {
            $notification->body("Added trays: " . implode(', ', $traysToAdd));
        }
        
        if ($cropsToRemove->isNotEmpty()) {
            $notification->body("Removed trays: " . implode(', ', $cropsToRemove->pluck('tray_number')->toArray()));
        }
        
        $notification->success()->send();
        
        return $record;
    }
} 