<?php

namespace App\Filament\Resources\ProductMixResource\Pages;

use App\Filament\Resources\ProductMixResource;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Actions;

class EditProductMix extends BaseEditRecord
{
    protected static string $resource = ProductMixResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->tooltip('Delete mix'),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $components = [];
        
        // Get the product mix record with its seedVarieties
        $productMix = $this->getRecord();
        $seedVarieties = $productMix->seedVarieties;
        
        // Build the components array for the repeater
        foreach ($seedVarieties as $variety) {
            $components[] = [
                'seed_variety_id' => $variety->id, 
                'percentage' => $variety->pivot->percentage,
            ];
        }
        
        $data['components'] = $components;
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Get the record being edited
        $record = $this->record;
        
        // Get the form data
        $data = $this->data;
        
        // Handle components if they exist
        if (isset($data['components']) && is_array($data['components'])) {
            // First detach all existing relationships
            $record->seedVarieties()->detach();
            
            // Then reattach with updated data
            foreach ($data['components'] as $component) {
                if (isset($component['seed_variety_id']) && isset($component['percentage'])) {
                    $record->seedVarieties()->attach($component['seed_variety_id'], [
                        'percentage' => $component['percentage'],
                    ]);
                }
            }
        }
    }
} 