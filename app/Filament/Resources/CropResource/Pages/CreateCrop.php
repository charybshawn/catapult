<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Filament\Resources\CropResource;
use App\Models\Crop;
use App\Models\Recipe;
use App\Services\CropTaskManagementService;
use App\Services\InventoryManagementService;
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
        
        // Check if recipe requires soaking - if so, tray numbers are optional
        $recipe = null;
        if (isset($data['recipe_id'])) {
            $recipe = Recipe::find($data['recipe_id']);
        }
        
        // Ensure we have at least one tray (unless soaking stage)
        if (empty($trayNumbers)) {
            if ($recipe && $recipe->requiresSoaking()) {
                // For soaking crops, use placeholder tray numbers
                $trayNumbers = ['SOAKING-1']; // Placeholder until actual trays are assigned
            } else {
                Notification::make()
                    ->title('Error: No tray numbers provided')
                    ->body('At least one tray number is required for a grow batch.')
                    ->danger()
                    ->send();
                    
                // Add a default tray number to prevent errors
                $trayNumbers = ['1'];
            }
        }
        
        // Log what we received for debugging
        Log::debug("Create Grow - Tray numbers received:", [
            'original_data' => $data['tray_numbers'] ?? 'none',
            'processed_array' => $trayNumbers,
            'recipe_id' => $data['recipe_id'] ?? 'none',
            'planting_at' => $data['planting_at'] ?? 'none'
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
            $plantedAt = Carbon::parse($data['planting_at']);
            
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
            
            // Handle seed deduction using new lot-based FIFO system
            if ($recipe && $recipe->lot_number && $recipe->seed_density_grams_per_tray) {
                try {
                    $totalSeedRequired = $recipe->seed_density_grams_per_tray * count($createdRecords);
                    // Use injected inventory service
                    
                    // Check if lot has sufficient quantity and is not depleted
                    if ($recipe->isLotDepleted()) {
                        Log::error('Cannot create crops: Recipe lot is marked as depleted', [
                            'recipe_id' => $recipe->id,
                            'lot_number' => $recipe->lot_number,
                            'required_amount' => $totalSeedRequired
                        ]);
                        
                        Notification::make()
                            ->title('Lot Depleted Error')
                            ->body("Cannot create crops: Recipe lot '{$recipe->lot_number}' is marked as depleted. Please assign a new lot to this recipe.")
                            ->danger()
                            ->persistent()
                            ->send();
                            
                        throw new \Exception("Cannot create crops: Recipe lot '{$recipe->lot_number}' is marked as depleted.");
                    }
                    
                    // Check if lot has enough available quantity
                    if (!app(InventoryManagementService::class)->canConsumeFromLot($recipe->lot_number, $totalSeedRequired)) {
                        $availableQuantity = $recipe->getLotQuantity();
                        
                        Log::warning('Insufficient lot stock for crop batch creation', [
                            'recipe_id' => $recipe->id,
                            'lot_number' => $recipe->lot_number,
                            'required_amount' => $totalSeedRequired,
                            'available_stock' => $availableQuantity,
                            'trays_requested' => count($createdRecords)
                        ]);
                        
                        Notification::make()
                            ->title('Insufficient Lot Stock')
                            ->body("Cannot create crops: Lot '{$recipe->lot_number}' has insufficient stock. Required: {$totalSeedRequired}g, Available: {$availableQuantity}g")
                            ->danger()
                            ->persistent()
                            ->send();
                            
                        throw new \Exception("Cannot create crops: Lot '{$recipe->lot_number}' has insufficient stock. Required: {$totalSeedRequired}g, Available: {$availableQuantity}g");
                    }
                    
                    // Consume from lot using FIFO
                    $consumptionResults = app(InventoryManagementService::class)->consumeFromLot(
                        $recipe->lot_number,
                        $totalSeedRequired,
                        $recipe,
                        auth()->user()
                    );
                    
                    Log::info('Seed automatically deducted from lot using FIFO', [
                        'recipe_id' => $recipe->id,
                        'lot_number' => $recipe->lot_number,
                        'total_amount_deducted' => $totalSeedRequired,
                        'trays_created' => count($createdRecords),
                        'amount_per_tray' => $recipe->seed_density_grams_per_tray,
                        'unit' => 'g',
                        'consumption_results' => $consumptionResults,
                        'remaining_lot_stock' => $recipe->getLotQuantity()
                    ]);
                    
                    // Check if lot is now depleted and mark if necessary
                    if ($recipe->getLotQuantity() <= 0) {
                        $recipe->markLotDepleted();
                        
                        Notification::make()
                            ->title('Lot Depleted')
                            ->body("Lot '{$recipe->lot_number}' has been fully consumed and marked as depleted. Please assign a new lot to this recipe for future use.")
                            ->warning()
                            ->persistent()
                            ->send();
                    }
                } catch (\Exception $e) {
                    Log::error('Error deducting seed inventory from lot for crop batch', [
                        'recipe_id' => $recipe->id,
                        'lot_number' => $recipe->lot_number,
                        'error' => $e->getMessage(),
                        'trays_created' => count($createdRecords)
                    ]);
                    
                    Notification::make()
                        ->title('Seed Deduction Error')
                        ->body("Crops created but error occurred during seed deduction from lot '{$recipe->lot_number}': {$e->getMessage()}")
                        ->danger()
                        ->persistent()
                        ->send();
                }
            }
            
            // Now manually schedule tasks for the first crop (representing the batch)
            if ($firstCrop) {
                try {
                    app(CropTaskManagementService::class)->scheduleAllStageTasks($firstCrop);
                } catch (\Exception $e) {
                    Log::warning('Error scheduling tasks after bulk crop creation', [
                        'error' => $e->getMessage(),
                        'crop_id' => $firstCrop->id
                    ]);
                }
            }
            
            // --- Generate TaskSchedules for the batch using the service --- 
            if ($firstCrop && $recipe) {
                // Use CropTaskManagementService to generate tasks
                app(CropTaskManagementService::class)->scheduleAllStageTasks($firstCrop);
            } else {
                 // Log if task generation is skipped (shouldn't happen if validation passed)
                 Log::warning("Skipping TaskSchedule generation due to missing firstCrop or recipe.", [
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
    
} 