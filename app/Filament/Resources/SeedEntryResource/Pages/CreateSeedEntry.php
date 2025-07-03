<?php

namespace App\Filament\Resources\SeedEntryResource\Pages;

use App\Filament\Resources\SeedEntryResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseCreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class CreateSeedEntry extends BaseCreateRecord
{
    protected static string $resource = SeedEntryResource::class;
    
    protected function handleRecordCreation(array $data): Model
    {
        // Extract variations data before creating the main record
        $variations = $data['variations'] ?? [];
        unset($data['variations']);
        
        // Create the seed entry
        $record = static::getModel()::create($data);
        
        // Create variations with default values
        foreach ($variations as $variationData) {
            // Set default values for required fields
            $variationData['last_checked_at'] = $variationData['last_checked_at'] ?? now();
            $variationData['seed_entry_id'] = $record->id;
            
            // Create the variation
            $record->variations()->create($variationData);
        }
        
        return $record;
    }
    
    /**
     * Livewire method to add a new variation - not available during creation
     */
    public function addVariation()
    {
        Notification::make()
            ->title('Save First')
            ->body('Please save the seed entry first, then you can add price variations.')
            ->warning()
            ->send();
    }
    
    /**
     * Livewire method to edit a specific variation - not available during creation
     */
    public function editVariation($variationId)
    {
        Notification::make()
            ->title('Save First')
            ->body('Please save the seed entry first, then you can manage price variations.')
            ->warning()
            ->send();
    }
    
    /**
     * Livewire method to bulk edit variations - not available during creation
     */
    public function bulkEditVariations()
    {
        Notification::make()
            ->title('Save First')
            ->body('Please save the seed entry first, then you can manage price variations.')
            ->warning()
            ->send();
    }
} 