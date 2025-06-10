<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Filament\Resources\CropResource;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\CropTask;
use App\Services\CropTaskGenerator;
use App\Filament\Pages\BaseCreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Carbon\Carbon;

class CreateCrop extends BaseCreateRecord
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
            $recipe = Recipe::find($data['recipe_id']);
            if ($recipe) {
                $recipeName = $recipe->name;
                if ($recipe->seedEntry) {
                    $varietyName = $recipe->seedEntry->common_name . ' - ' . $recipe->seedEntry->cultivar_name;
                }
            } else {
                // Handle case where recipe not found, maybe throw error or default
                Log::error("Recipe not found for ID: " . $data['recipe_id']);
                // Optionally send a failure notification and abort
                Notification::make()
                    ->title('Error: Recipe Not Found')
                    ->danger()
                    ->send();
                // You might want to throw an exception here to stop execution
                throw new \Exception("Recipe not found for ID: " . $data['recipe_id']);
            }
        } else {
             // Handle case where recipe_id is missing
             Log::error("Recipe ID missing in data for Crop creation.");
             throw new \Exception("Recipe ID is required.");
        }
        
        // Use a transaction to ensure all records are created or none
        $firstCrop = DB::transaction(function () use ($data, $trayNumbers, $recipe, $varietyName, $recipeName) {
            $firstCrop = null;
            $createdRecords = [];
            $plantedAt = Carbon::parse($data['planted_at']);
            
            // Enable bulk operation mode to prevent memory issues from model events
            Crop::enableBulkOperation();
            
            try {
                // Create a separate record for each tray number
                foreach ($trayNumbers as $trayNumber) {
                // Clean the tray number
                $trayNum = trim($trayNumber);
                if (empty($trayNum)) continue;
                
                // Create a new crop record with this tray number
                $cropData = array_merge($data, [
                    'tray_number' => $trayNum,
                    'planting_at' => $plantedAt,
                    'time_to_next_stage_minutes' => 0,
                    'time_to_next_stage_status' => 'Calculating...',
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
            
            } finally {
                // Always disable bulk operation mode
                Crop::disableBulkOperation();
            }
            
            // Manually handle seed deduction since we disabled bulk operation mode
            if ($recipe && $recipe->seedConsumable && $recipe->seed_density_grams_per_tray) {
                try {
                    $totalSeedRequired = $recipe->seed_density_grams_per_tray * count($createdRecords);
                    $seedConsumable = $recipe->seedConsumable;
                    
                    // Convert required amount to the same unit as the seed consumable for comparison
                    $inventoryService = app(\App\Services\InventoryService::class);
                    $currentStock = $inventoryService->getCurrentStock($seedConsumable);
                    
                    // Check if we have enough seed (convert units if needed for comparison)
                    $requiredInSeedUnits = match($seedConsumable->quantity_unit) {
                        'kg' => $totalSeedRequired / 1000, // Convert grams to kg
                        'g' => $totalSeedRequired, // Already in grams
                        default => $totalSeedRequired // For other units, assume direct comparison
                    };
                    
                    if ($currentStock >= $requiredInSeedUnits) {
                        // Deduct the total seed amount for all trays
                        $seedConsumable->deduct(
                            $totalSeedRequired,
                            'g' // Recipe seed density is always in grams
                        );
                        
                        Log::info('Seed automatically deducted for crop batch', [
                            'recipe_id' => $recipe->id,
                            'seed_consumable_id' => $seedConsumable->id,
                            'total_amount_deducted' => $totalSeedRequired,
                            'trays_created' => count($createdRecords),
                            'amount_per_tray' => $recipe->seed_density_grams_per_tray,
                            'unit' => 'g',
                            'remaining_stock' => $currentStock - $requiredInSeedUnits
                        ]);
                    } else {
                        Log::warning('Insufficient seed stock for crop batch creation', [
                            'recipe_id' => $recipe->id,
                            'seed_consumable_id' => $seedConsumable->id,
                            'required_amount' => $totalSeedRequired,
                            'current_stock' => $currentStock,
                            'seed_unit' => $seedConsumable->quantity_unit,
                            'trays_requested' => count($createdRecords)
                        ]);
                        
                        // Optionally add a warning to the notification
                        Notification::make()
                            ->title('Low Seed Stock Warning')
                            ->body("Crops created but insufficient seed stock. Required: {$totalSeedRequired}g, Available: {$currentStock} {$seedConsumable->quantity_unit}")
                            ->warning()
                            ->send();
                    }
                } catch (\Exception $e) {
                    Log::warning('Error deducting seed inventory for crop batch', [
                        'recipe_id' => $recipe->id,
                        'error' => $e->getMessage(),
                        'trays_created' => count($createdRecords)
                    ]);
                }
            }
            
            // Now manually schedule tasks for the first crop (representing the batch)
            if ($firstCrop) {
                try {
                    app(\App\Services\CropTaskService::class)->scheduleAllStageTasks($firstCrop);
                } catch (\Exception $e) {
                    Log::warning('Error scheduling tasks after bulk crop creation', [
                        'error' => $e->getMessage(),
                        'crop_id' => $firstCrop->id
                    ]);
                }
            }
            
            // --- Generate CropTasks for the batch using the service --- 
            if ($firstCrop && $recipe) {
                // Instantiate the generator service
                $taskGenerator = new CropTaskGenerator(); 
                // Call the method to generate tasks
                $taskGenerator->generateTasksForBatch($firstCrop, $recipe);
            } else {
                 // Log if task generation is skipped (shouldn't happen if validation passed)
                 Log::warning("Skipping CropTask generation due to missing firstCrop or recipe.", [
                    'firstCropExists' => !!$firstCrop,
                    'recipeExists' => !!$recipe
                 ]);
            }
            // --- End Task Generation Call ---

            // Log what we created
            Log::debug("Create Grow - Records created:", $createdRecords);
            
            // Show a notification with the number of trays created
            $trayCount = count($createdRecords);
            Notification::make()
                ->title('Grow Batch Created')
                ->body("Successfully created grow batch with {$trayCount} trays of {$varietyName} ({$recipeName}). Crop tasks scheduled.")
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