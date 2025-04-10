<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Filament\Resources\CropResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components;
use Illuminate\Database\Eloquent\Model;

class EditCrop extends EditRecord
{
    protected static string $resource = CropResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // For the edit page, convert the single tray_number to an array for the TagsInput
        // The tray_number could be a string like "1,2,3" or just a single value
        if (isset($data['tray_number'])) {
            // Handle any possible format
            $trayNumber = $data['tray_number'];
            
            // If it's already comma-separated, split it
            if (strpos($trayNumber, ',') !== false) {
                $data['tray_numbers'] = array_map('trim', explode(',', $trayNumber));
            } else {
                $data['tray_numbers'] = [$trayNumber];
            }
        } else {
            $data['tray_numbers'] = [];
        }
        
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // In edit mode, we only update a single record, so take the first tray number
        if (!empty($data['tray_numbers']) && is_array($data['tray_numbers'])) {
            // Use the first tray number only
            $record->tray_number = trim($data['tray_numbers'][0]);
        }
        
        // Remove the tray_numbers field from the data
        unset($data['tray_numbers']);
        
        // Update the record with the remaining data
        $record->fill($data)->save();
        
        return $record;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 