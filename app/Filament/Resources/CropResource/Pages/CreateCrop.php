<?php

namespace App\Filament\Resources\CropResource\Pages;

use App\Actions\Crop\CreateCrop as CreateCropAction;
use App\Filament\Resources\CropResource;
use App\Models\Recipe;
use App\Filament\Pages\BaseCreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CreateCrop extends BaseCreateRecord
{
    protected static string $resource = CropResource::class;
    
    protected function getRedirectUrl(): string
    {
        return '/admin/crop-batches';
    }
    
    protected function handleRecordCreation(array $data): Model
    {
        try {
            // Prepare data for action
            $actionData = [
                'recipe_id' => $data['recipe_id'],
                'order_id' => $data['order_id'] ?? null,
                'crop_plan_id' => $data['crop_plan_id'] ?? null,
                'tray_count' => $data['tray_count'] ?? 1,
                'tray_numbers' => $data['tray_numbers'] ?? null, // Fixed: use plural form
                'notes' => $data['notes'] ?? null,
            ];
            
            // Execute the action using dependency injection
            $createCropAction = app(CreateCropAction::class);
            $crop = $createCropAction->execute($actionData);
            
            // Get recipe info for notification
            $recipe = Recipe::find($data['recipe_id']);
            $recipeName = $recipe->name;
            $varietyService = app(\App\Services\RecipeVarietyService::class);
            $varietyName = $varietyService->getFullVarietyName($recipe);
            
            // Show success notification
            $message = $recipe->requiresSoaking() ?
                "Created {$varietyName} ({$recipeName}) in soaking stage. Soaking duration: {$recipe->seed_soak_hours} hours." :
                "Created {$varietyName} ({$recipeName}) in germination stage.";
            
            Notification::make()
                ->title('Crop Created Successfully')
                ->body($message)
                ->success()
                ->send();
            
            return $crop;
            
        } catch (\Exception $e) {
            Log::error('Error creating crop', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            Notification::make()
                ->title('Error Creating Crop')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
            
            throw $e;
        }
    }
}