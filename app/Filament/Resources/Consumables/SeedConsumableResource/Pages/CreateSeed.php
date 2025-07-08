<?php

namespace App\Filament\Resources\Consumables\SeedConsumableResource\Pages;

use App\Filament\Resources\Consumables\SeedConsumableResource;
use App\Filament\Resources\ConsumableResource\Pages\CreateConsumable;
use App\Models\ConsumableType;

class CreateSeed extends CreateConsumable
{
    protected static string $resource = SeedConsumableResource::class;
    
    public function mount(): void
    {
        parent::mount();
        
        // Get the seed type ID
        $seedTypeId = ConsumableType::where('code', 'seed')->first()?->id;
        
        // Initialize form data with seed-specific defaults
        $this->form->fill([
            'consumable_type_id' => $seedTypeId,
            'is_active' => true,
            'consumed_quantity' => 0,
            'initial_stock' => 1, // Seeds always have initial_stock of 1
            'total_quantity' => 0,
            'quantity_unit' => 'g', // Default to grams
            'quantity_per_unit' => 1,
            'restock_threshold' => 0,
            'restock_quantity' => 0,
        ]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure seed type is set
        $data['consumable_type_id'] = ConsumableType::where('code', 'seed')->first()?->id;
        
        // Seeds always have initial_stock of 1
        $data['initial_stock'] = 1;
        $data['quantity_per_unit'] = 1;
        
        // Set remaining_quantity to total_quantity if not set
        if (!isset($data['remaining_quantity'])) {
            $data['remaining_quantity'] = $data['total_quantity'] ?? 0;
        }
        
        // Calculate consumed_quantity
        $data['consumed_quantity'] = max(0, ($data['total_quantity'] ?? 0) - ($data['remaining_quantity'] ?? 0));
        
        // Set default values for required fields
        $data['restock_threshold'] = $data['restock_threshold'] ?? 0;
        $data['restock_quantity'] = $data['restock_quantity'] ?? 0;
        $data['cost_per_unit'] = $data['cost_per_unit'] ?? 0.0;
        
        return $data;
    }
    
    public function getTitle(): string
    {
        return 'Create Seed';
    }
}