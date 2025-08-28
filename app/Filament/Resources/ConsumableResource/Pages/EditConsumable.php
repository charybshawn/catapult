<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;
use Filament\Schemas\Schema;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\ConsumableResource;
use App\Models\Consumable;
use App\Models\PackagingType;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components;
use App\Filament\Pages\Base\BaseEditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditConsumable extends BaseEditRecord
{
    protected static string $resource = ConsumableResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If this is a packaging type consumable but name is empty, set it from the packaging type
        if (isset($data['type']) && $data['type'] === 'packaging' && empty($data['name']) && !empty($data['packaging_type_id'])) {
            $packagingType = PackagingType::find($data['packaging_type_id']);
            if ($packagingType) {
                $data['name'] = $packagingType->display_name ?? $packagingType->name;
            }
        }
        
        // For seed consumables, generate name from master catalog and cultivar
        if (!empty($data['consumable_type_id']) && $data['consumable_type_id'] == 3) {
            if (!empty($data['master_seed_catalog_id']) && !empty($data['cultivar'])) {
                $masterCatalog = MasterSeedCatalog::find($data['master_seed_catalog_id']);
                if ($masterCatalog) {
                    // Generate name from catalog and cultivar
                    $data['name'] = $masterCatalog->common_name . ' (' . $data['cultivar'] . ')';
                    
                    // Set master_cultivar_id if not set
                    if (empty($data['master_cultivar_id'])) {
                        $masterCultivar = MasterCultivar::where('master_seed_catalog_id', $data['master_seed_catalog_id'])
                            ->where('cultivar_name', $data['cultivar'])
                            ->first();
                        if ($masterCultivar) {
                            $data['master_cultivar_id'] = $masterCultivar->id;
                        }
                    }
                    
                    Log::info('Updated seed consumable name from catalog and cultivar', [
                        'id' => $this->record->id ?? 'new',
                        'master_seed_catalog_id' => $data['master_seed_catalog_id'],
                        'cultivar' => $data['cultivar'],
                        'generated_name' => $data['name'],
                        'master_cultivar_id' => $data['master_cultivar_id'] ?? 'none'
                    ]);
                }
            }
        }
        
        // For seed consumables, if remaining_quantity is set, calculate consumed_quantity
        if (!empty($data['consumable_type_id']) && $data['consumable_type_id'] == 3 && isset($data['remaining_quantity']) && isset($data['total_quantity'])) {
            $total = (float) $data['total_quantity'];
            $remaining = (float) $data['remaining_quantity'];
            $data['consumed_quantity'] = max(0, $total - $remaining);
            
            Log::info('Seed consumable update - calculated consumed quantity:', [
                'id' => $this->record->id ?? 'new',
                'total_quantity' => $total,
                'remaining_quantity' => $remaining,
                'calculated_consumed' => $data['consumed_quantity']
            ]);
        }
        
        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return ConsumableResource::form($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
protected function mutateFormDataBeforeFill(array $data): array
    {
        Log::info('EditConsumable mutateFormDataBeforeFill called', [
            'record_id' => $this->record->id ?? 'unknown',
            'consumable_type_id' => $data['consumable_type_id'] ?? 'null',
            'total_quantity' => $data['total_quantity'] ?? 'null',
            'consumed_quantity' => $data['consumed_quantity'] ?? 'null'
        ]);
        
        // Calculate current stock for display
        if (isset($data['initial_stock']) && isset($data['consumed_quantity'])) {
            $data['current_stock_display'] = max(0, $data['initial_stock'] - $data['consumed_quantity']);
        }
        
        // For seed consumables, calculate remaining_quantity from total_quantity and consumed_quantity
        if (!empty($data['consumable_type_id']) && $data['consumable_type_id'] == 3 && isset($data['total_quantity']) && isset($data['consumed_quantity'])) {
            $data['remaining_quantity'] = max(0, $data['total_quantity'] - $data['consumed_quantity']);
            
            Log::info('Seed consumable form fill - calculated remaining quantity:', [
                'id' => $this->record->id ?? 'unknown',
                'total_quantity' => $data['total_quantity'],
                'consumed_quantity' => $data['consumed_quantity'],
                'calculated_remaining' => $data['remaining_quantity']
            ]);
        }
        
        // For seed consumables, populate cultivar field from master_cultivar_id relationship
        if (!empty($data['consumable_type_id']) && $data['consumable_type_id'] == 3 && !empty($data['master_cultivar_id'])) {
            $masterCultivar = MasterCultivar::find($data['master_cultivar_id']);
            if ($masterCultivar) {
                $data['cultivar'] = $masterCultivar->cultivar_name;
                
                Log::info('Populated cultivar from master_cultivar_id for edit form:', [
                    'record_id' => $this->record->id ?? 'unknown',
                    'master_cultivar_id' => $data['master_cultivar_id'],
                    'cultivar_name' => $data['cultivar'],
                    'master_seed_catalog_id' => $data['master_seed_catalog_id'] ?? 'none'
                ]);
            }
        }
        
        // Ensure is_active is properly cast to boolean for the form
        if (isset($data['is_active'])) {
            $data['is_active'] = (bool) $data['is_active'];
        }
        
        return $data;
    }
    
    // Show seed variety information if available
    protected function getHeaderWidgets(): array
    {
        // Temporarily disabled to fix Livewire error
        return [];
    }
} 