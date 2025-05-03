<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Filament\Resources\CropResource;
use App\Models\Crop;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;

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
        
        // Ensure we have at least one tray
        if (empty($trayNumbers)) {
            Notification::make()
                ->title('Error: No tray numbers provided')
                ->body('At least one tray number is required for a grow batch.')
                ->danger()
                ->send();
                
            // Add a default tray number to prevent errors
            $trayNumbers = ['1'];
        }
        
        // Log what we received for debugging
        Log::debug("Create Grow - Tray numbers received:", [
            'original_data' => $data['tray_numbers'] ?? 'none',
            'processed_array' => $trayNumbers,
            'recipe_id' => $data['recipe_id'] ?? 'none',
            'planted_at' => $data['planted_at'] ?? 'none'
        ]);
        
        // Remove the tray_numbers field from the data
        unset($data['tray_numbers']);
        
        // Get the recipe name and seed variety for display
        $recipeName = 'Unknown Recipe';
        $varietyName = 'Unknown Variety';
        
        if (isset($data['recipe_id'])) {
            $recipe = \App\Models\Recipe::find($data['recipe_id']);
            if ($recipe) {
                $recipeName = $recipe->name;
                if ($recipe->seedVariety) {
                    $varietyName = $recipe->seedVariety->name;
                }
            }
        }
        
        // Use a transaction to ensure all records are created or none
        $firstCrop = DB::transaction(function () use ($data, $trayNumbers, $recipeName, $varietyName) {
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
            Log::debug("Create Grow - Records created:", $createdRecords);
            
            // Show a notification with the number of trays created
            $trayCount = count($createdRecords);
            Notification::make()
                ->title('Grow Batch Created')
                ->body("Successfully created grow batch with {$trayCount} trays of {$varietyName} ({$recipeName}).")
                ->success()
                ->send();
            
            return $firstCrop;
        });
        
        return $firstCrop;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 