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
        
        // For non-seed types, get current_stock from non_seed_stock field
        if ($data['type'] !== 'seed' && isset($data['non_seed_stock'])) {
            $data['current_stock'] = $data['non_seed_stock'];
            unset($data['non_seed_stock']); // Remove temporary field
        }
        
        // For seeds, set current_stock from seed_packet_count
        if ($data['type'] === 'seed' && isset($data['seed_packet_count'])) {
            // Current stock for seeds is the number of packets
            $data['current_stock'] = $data['seed_packet_count'];
            unset($data['seed_packet_count']); // Remove temporary field
        }
        
        // For packaging types, clear weight-related fields
        if ($data['type'] === 'packaging') {
            $data['quantity_per_unit'] = null;
            $data['quantity_unit'] = null;
            $data['total_quantity'] = null;
        }
        // Calculate total quantity for other types
        else if (isset($data['quantity_per_unit']) && $data['quantity_per_unit'] > 0) {
            $data['total_quantity'] = $data['current_stock'] * $data['quantity_per_unit'];
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 