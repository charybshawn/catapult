<?php

namespace App\Filament\Resources\Consumables\SeedConsumableResource\Pages;

use App\Filament\Resources\Consumables\SeedConsumableResource;
use App\Filament\Resources\ConsumableResource\Pages\EditConsumable;
use App\Models\ConsumableType;
use App\Models\MasterSeedCatalog;
use Illuminate\Support\Facades\Log;

class EditSeed extends EditConsumable
{
    protected static string $resource = SeedConsumableResource::class;
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Call parent to get base mutations
        $data = parent::mutateFormDataBeforeFill($data);
        
        // Calculate remaining_quantity from total_quantity and consumed_quantity
        if (isset($data['total_quantity']) && isset($data['consumed_quantity'])) {
            $data['remaining_quantity'] = max(0, $data['total_quantity'] - $data['consumed_quantity']);
            
            Log::info('Seed form fill:', [
                'id' => $this->record->id ?? 'unknown',
                'total_quantity' => $data['total_quantity'],
                'consumed_quantity' => $data['consumed_quantity'],
                'calculated_remaining' => $data['remaining_quantity']
            ]);
        }
        
        // If we have a legacy cultivar string but no master_cultivar_id, try to find the matching cultivar
        if (!empty($data['cultivar']) && empty($data['master_cultivar_id']) && !empty($data['master_seed_catalog_id'])) {
            $cultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $data['master_seed_catalog_id'])
                ->where('cultivar_name', 'LIKE', '%' . $data['cultivar'] . '%')
                ->first();
            
            if ($cultivar) {
                $data['master_cultivar_id'] = $cultivar->id;
                Log::info('Found matching cultivar for legacy string', [
                    'consumable_id' => $this->record->id,
                    'cultivar_string' => $data['cultivar'],
                    'master_cultivar_id' => $cultivar->id
                ]);
            }
        }
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure seed type is set
        $data['consumable_type_id'] = ConsumableType::where('code', 'seed')->first()?->id;
        
        // Ensure proper name formatting from master catalog and cultivar
        if (!empty($data['master_seed_catalog_id']) && !empty($data['master_cultivar_id'])) {
            $masterCatalog = MasterSeedCatalog::find($data['master_seed_catalog_id']);
            $masterCultivar = \App\Models\MasterCultivar::find($data['master_cultivar_id']);
            
            if ($masterCatalog && $masterCultivar) {
                $commonName = ucwords(strtolower($masterCatalog->common_name));
                $cultivarName = ucwords(strtolower($masterCultivar->cultivar_name));
                
                $data['name'] = $commonName . ' (' . $cultivarName . ')';
                
                Log::info('Updating seed from master catalog', [
                    'id' => $this->record->id ?? 'new',
                    'master_seed_catalog_id' => $data['master_seed_catalog_id'],
                    'master_cultivar_id' => $data['master_cultivar_id'],
                    'name' => $data['name']
                ]);
            }
        }
        
        // Calculate consumed_quantity from remaining_quantity
        if (isset($data['remaining_quantity']) && isset($data['total_quantity'])) {
            $total = (float) $data['total_quantity'];
            $remaining = (float) $data['remaining_quantity'];
            $data['consumed_quantity'] = max(0, $total - $remaining);
            
            Log::info('Seed update:', [
                'id' => $this->record->id ?? 'new',
                'total_quantity' => $total,
                'remaining_quantity' => $remaining,
                'calculated_consumed' => $data['consumed_quantity']
            ]);
        }
        
        // Seeds always have initial_stock of 1
        $data['initial_stock'] = 1;
        $data['quantity_per_unit'] = 1;
        
        return $data;
    }
    
    public function getTitle(): string
    {
        return 'Edit Seed';
    }
}