<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Filament\Resources\CropResource;
use App\Models\Crop;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateCrop extends CreateRecord
{
    protected static string $resource = CropResource::class;
    
    protected function handleRecordCreation(array $data): Model
    {
        // Extract tray numbers
        $trayNumbers = [];
        
        if (isset($data['tray_numbers'])) {
            // Convert to array regardless of input format
            if (is_array($data['tray_numbers'])) {
                $trayNumbers = $data['tray_numbers'];
            } elseif (is_string($data['tray_numbers'])) {
                // Try to decode JSON string
                $decoded = json_decode($data['tray_numbers'], true);
                if (is_array($decoded)) {
                    $trayNumbers = $decoded;
                } else {
                    // Fallback - split by comma
                    $trayNumbers = explode(',', $data['tray_numbers']);
                }
            }
        }
        
        // Log what we received for debugging
        Log::debug("Create Crop - Tray numbers received:", [
            'original_data' => $data['tray_numbers'] ?? 'none',
            'processed_array' => $trayNumbers
        ]);
        
        // Remove the tray_numbers field from the data
        unset($data['tray_numbers']);
        
        // Use a transaction to ensure all records are created or none
        $firstCrop = DB::transaction(function () use ($data, $trayNumbers) {
            $firstCrop = null;
            $createdRecords = [];
            
            // Create a separate record for each tray number
            foreach ($trayNumbers as $trayNumber) {
                // Clean the tray number
                $trayNum = trim($trayNumber);
                if (empty($trayNum)) continue;
                
                // Create a new crop record with this tray number
                $cropData = array_merge($data, [
                    'tray_number' => $trayNum,
                    // Set safe default values for computed time fields
                    'time_to_next_stage_minutes' => 0,
                    'time_to_next_stage_status' => 'Unknown',
                    'stage_age_minutes' => 0,
                    'stage_age_status' => '0m',
                    'total_age_minutes' => 0,
                    'total_age_status' => '0m',
                ]);
                $crop = Crop::create($cropData);
                $createdRecords[] = [
                    'id' => $crop->id,
                    'tray_number' => $crop->tray_number
                ];
                
                if (!$firstCrop) {
                    $firstCrop = $crop;
                }
            }
            
            // Log what we created
            Log::debug("Create Crop - Records created:", $createdRecords);
            
            return $firstCrop;
        });
        
        return $firstCrop;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 