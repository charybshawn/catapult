<?php

namespace App\Filament\Resources\CropBatchResource\Pages;

use App\Actions\Crop\CreateCrop;
use App\Filament\Resources\CropBatchResource;
use App\Models\CropBatch;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCropBatch extends CreateRecord
{
    protected static string $resource = CropBatchResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Clean up the data before passing to the action
        $cleanedData = $this->cleanFormData($data);

        // Use the CreateCrop action to properly create the batch and crops
        $createCropAction = app(CreateCrop::class);
        $crop = $createCropAction->execute($cleanedData);

        // Return the crop batch, not the individual crop
        return $crop->cropBatch;
    }

    /**
     * Clean and normalize form data before processing
     */
    protected function cleanFormData(array $data): array
    {
        // Handle tray_numbers field which might come as complex array structure from Livewire
        if (isset($data['tray_numbers'])) {
            $trayNumbers = $data['tray_numbers'];

            // If it's an array, extract the actual values
            if (is_array($trayNumbers)) {
                // Flatten and filter the array to get actual tray numbers
                $flatTrayNumbers = [];
                foreach ($trayNumbers as $value) {
                    if (is_string($value) && !empty(trim($value))) {
                        $flatTrayNumbers[] = trim($value);
                    } elseif (is_array($value)) {
                        // Handle nested arrays from Livewire
                        foreach ($value as $subValue) {
                            if (is_string($subValue) && !empty(trim($subValue))) {
                                $flatTrayNumbers[] = trim($subValue);
                            }
                        }
                    }
                }
                $data['tray_numbers'] = $flatTrayNumbers;
            } elseif (is_string($trayNumbers)) {
                $data['tray_numbers'] = [trim($trayNumbers)];
            } else {
                $data['tray_numbers'] = [];
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}