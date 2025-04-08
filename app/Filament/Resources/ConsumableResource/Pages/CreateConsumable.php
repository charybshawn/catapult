<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Models\PackagingType;
use Filament\Resources\Pages\CreateRecord;

class CreateConsumable extends CreateRecord
{
    protected static string $resource = ConsumableResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If this is a packaging type consumable but name is empty, set it from the packaging type
        if ($data['type'] === 'packaging' && empty($data['name']) && !empty($data['packaging_type_id'])) {
            $packagingType = PackagingType::find($data['packaging_type_id']);
            if ($packagingType) {
                $data['name'] = $packagingType->display_name ?? $packagingType->name;
            }
        }
        
        // Calculate total quantity
        if (in_array($data['type'], ['soil', 'seed']) && !empty($data['quantity_per_unit'])) {
            $data['total_quantity'] = $data['current_stock'] * $data['quantity_per_unit'];
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 