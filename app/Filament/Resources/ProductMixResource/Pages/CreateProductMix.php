<?php

namespace App\Filament\Resources\ProductMixResource\Pages;

use App\Filament\Resources\ProductMixResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductMix extends CreateRecord
{
    protected static string $resource = ProductMixResource::class;
    
    protected function afterCreate(): void
    {
        // Get the newly created record
        $record = $this->record;
        
        // Get the form data
        $data = $this->data;
        
        // Handle components if they exist
        if (isset($data['components']) && is_array($data['components'])) {
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