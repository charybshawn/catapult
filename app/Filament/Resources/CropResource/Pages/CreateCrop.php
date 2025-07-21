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
    
    protected CreateCropAction $createCropAction;
    
    public function __construct()
    {
        parent::__construct();
        $this->createCropAction = app(CreateCropAction::class);
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
                'tray_number' => $data['tray_number'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];
            
            // Execute the action
            $crop = $this->createCropAction->execute($actionData);
            
            // Get recipe info for notification
            $recipe = Recipe::find($data['recipe_id']);
            $recipeName = $recipe->name;
            $varietyName = $recipe->seedEntry ? 
                $recipe->seedEntry->common_name . ' - ' . $recipe->seedEntry->cultivar_name : 
                'Unknown Variety';
            
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