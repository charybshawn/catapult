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
        // Debug logging
        \Illuminate\Support\Facades\Log::debug('CreateConsumable mutateFormDataBeforeCreate', [
            'name' => $data['name'] ?? 'NULL',
            'cultivar' => $data['cultivar'] ?? 'NULL',
            'master_seed_catalog_id' => $data['master_seed_catalog_id'] ?? 'NULL',
            'consumable_type_id' => $data['consumable_type_id'] ?? 'NULL'
        ]);
        
        // Set default values for required fields that might be null
        $data['quantity_per_unit'] = $data['quantity_per_unit'] ?? 1.0;
        $data['unit'] = $data['unit'] ?? 'g';
        $data['restock_threshold'] = $data['restock_threshold'] ?? 5;
        $data['restock_quantity'] = $data['restock_quantity'] ?? 10;
        $data['cost_per_unit'] = $data['cost_per_unit'] ?? 0.0;
        
        // For seed consumables, set initial_stock from total_quantity and generate name if missing
        if (isset($data['consumable_type_id']) && $data['consumable_type_id'] == 3) {
            $data['initial_stock'] = $data['total_quantity'] ?? 0;
            
            // Generate name if missing for seed consumables
            if (empty($data['name']) && !empty($data['master_seed_catalog_id']) && !empty($data['cultivar'])) {
                $masterCatalog = \App\Models\MasterSeedCatalog::find($data['master_seed_catalog_id']);
                if ($masterCatalog) {
                    $generatedName = $masterCatalog->common_name . ' (' . $data['cultivar'] . ')';
                    $data['name'] = $generatedName;
                    
                    \Illuminate\Support\Facades\Log::debug('Generated name for seed consumable', [
                        'generated_name' => $generatedName,
                        'common_name' => $masterCatalog->common_name,
                        'cultivar' => $data['cultivar']
                    ]);
                    
                    // Also set master_cultivar_id if missing
                    if (empty($data['master_cultivar_id'])) {
                        $masterCultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $data['master_seed_catalog_id'])
                            ->where('cultivar_name', $data['cultivar'])
                            ->first();
                        if ($masterCultivar) {
                            $data['master_cultivar_id'] = $masterCultivar->id;
                        }
                    }
                }
            }
        }
        
        return $data;
    }
} 