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
                    // Get all trays in this grow batch
                    $record = $this->getRecord();
                    Crop::where('recipe_id', $record->recipe_id)
                        ->where('planted_at', $record->planted_at)
                        ->delete();
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                }),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Get the current record being edited
        $record = $this->getRecord();
        
        // Get all trays in this grow batch
        $allTrays = Crop::where('recipe_id', $record->recipe_id)
            ->where('planted_at', $record->planted_at)
            ->where('current_stage', $record->current_stage)
            ->pluck('tray_number')
            ->toArray();
        
        // Add existing tray numbers to form data
        $data['tray_numbers'] = $allTrays;
        
        // Since we're using MIN/MAX/AVG for group data, ensure we have accurate values for selected fields
        if (isset($record->tray_number_list)) {
            $data['tray_number_list'] = $record->tray_number_list;
        }
        
        // Get the first actual record from this group to ensure we have complete data
        $firstRecord = Crop::where('recipe_id', $record->recipe_id)
            ->where('planted_at', $record->planted_at)
            ->where('current_stage', $record->current_stage)
            ->first();
            
        if ($firstRecord) {
            // Copy any fields that might be missed by the aggregation
            $data['notes'] = $firstRecord->notes ?? '';
        }
        
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Get all current trays in this grow batch
        $currentTrays = Crop::where('recipe_id', $record->recipe_id)
            ->where('planted_at', $record->planted_at)
            ->where('current_stage', $record->current_stage)
            ->get();
            
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
        
        // Find trays to remove (trays that exist in currentTrays but not in newTrayNumbers)
        $traysToRemove = $currentTrays->filter(function ($crop) use ($newTrayNumbers) {
            return !in_array($crop->tray_number, $newTrayNumbers);
        });
        
        // Find trays to add (numbers in newTrayNumbers but not in currentTrays)
        $currentTrayNumbers = $currentTrays->pluck('tray_number')->toArray();
        $traysToAdd = array_diff($newTrayNumbers, $currentTrayNumbers);
        
        // Remove trays that are no longer needed
        foreach ($traysToRemove as $tray) {
            $tray->delete();
        }
        
        // Add new trays
        foreach ($traysToAdd as $trayNumber) {
            $newTray = $record->replicate();
            $newTray->tray_number = $trayNumber;
            $newTray->save();
        }
        
        // Remove the tray_numbers field as it's not a database column
        unset($data['tray_numbers']);
        
        // Update all remaining crops in the grow batch
        DB::transaction(function () use ($currentTrays, $data) {
            foreach ($currentTrays as $crop) {
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
        
        if ($traysToRemove->isNotEmpty()) {
            $notification->body("Removed trays: " . implode(', ', $traysToRemove->pluck('tray_number')->toArray()));
        }
        
        $notification->success()->send();
        
        return $record;
    }
} 