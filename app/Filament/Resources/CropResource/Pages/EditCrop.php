<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Filament\Resources\CropResource;
use App\Models\Crop;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class EditCrop extends EditRecord
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
        $data['existing_tray_numbers'] = $allTrays;
        
        // Initialize empty array for add_tray_numbers
        $data['add_tray_numbers'] = [];
        
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
        // Get all crops in this grow batch
        $crops = Crop::where('recipe_id', $record->recipe_id)
            ->where('planted_at', $record->planted_at)
            ->where('current_stage', $record->current_stage)
            ->get();
        
        // Create new trays if they were added
        $newTrays = [];
        if (!empty($data['add_tray_numbers']) && is_array($data['add_tray_numbers'])) {
            foreach ($data['add_tray_numbers'] as $newTrayNumber) {
                $newTrayNumber = trim($newTrayNumber);
                if (empty($newTrayNumber)) continue;
                
                // Check if this tray number already exists in this batch
                $existingTray = Crop::where('recipe_id', $record->recipe_id)
                    ->where('planted_at', $record->planted_at)
                    ->where('tray_number', $newTrayNumber)
                    ->first();
                    
                if (!$existingTray) {
                    // Create a new tray with the same data as the current record
                    $newTray = $record->replicate();
                    $newTray->tray_number = $newTrayNumber;
                    $newTray->save();
                    $newTrays[] = $newTrayNumber;
                }
            }
        }
        
        // Remove fields that shouldn't be updated on all trays
        unset($data['existing_tray_numbers']);
        unset($data['add_tray_numbers']);
        
        // Update all crops in the grow batch
        DB::transaction(function () use ($crops, $data) {
            foreach ($crops as $crop) {
                $crop->fill($data)->save();
            }
        });
        
        // Show notification about the update
        $trayCount = count($crops);
        $notification = Notification::make()
            ->title('Grow Batch Updated')
            ->body("Successfully updated {$trayCount} trays.");
            
        // If new trays were added, mention them
        if (!empty($newTrays)) {
            $notification->body("Successfully updated {$trayCount} trays and added " . count($newTrays) . " new trays: " . implode(', ', $newTrays));
        }
        
        $notification->success()->send();
        
        return $record;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 