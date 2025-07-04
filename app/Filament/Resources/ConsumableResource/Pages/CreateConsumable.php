<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Filament\Pages\Base\BaseCreateRecord;

class CreateConsumable extends BaseCreateRecord
{
    protected static string $resource = ConsumableResource::class;
    
    public function mount(): void
    {
        parent::mount();
        
        // Initialize form data with proper defaults
        $this->form->fill([
            'consumable_type_id' => 3, // Default to seed type
            'is_active' => true,
            'consumed_quantity' => 0,
            'initial_stock' => 0,
            'total_quantity' => 0,
        ]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values for required fields that might be null
        $data['quantity_per_unit'] = $data['quantity_per_unit'] ?? 1.0;
        $data['unit'] = $data['unit'] ?? 'g';
        $data['restock_threshold'] = $data['restock_threshold'] ?? 5;
        $data['restock_quantity'] = $data['restock_quantity'] ?? 10;
        $data['cost_per_unit'] = $data['cost_per_unit'] ?? 0.0;
        
        // For seed consumables, set initial_stock from total_quantity
        if (isset($data['consumable_type_id']) && $data['consumable_type_id'] == 3) {
            $data['initial_stock'] = $data['total_quantity'] ?? 0;
        }
        
        return $data;
    }
} 