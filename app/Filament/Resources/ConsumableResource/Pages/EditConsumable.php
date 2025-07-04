<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Models\Consumable;
use App\Models\PackagingType;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components;
use App\Filament\Pages\Base\BaseEditRecord;
use Illuminate\Database\Eloquent\Model;

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
        
        // For seed consumables, handle master seed catalog composite key
        if (isset($data['type']) && $data['type'] === 'seed' && !empty($data['master_seed_catalog_id'])) {
            // Parse composite key if present: catalog_id:cultivar_index
            $catalogId = $data['master_seed_catalog_id'];
            $cultivarIndex = null;
            $selectedCultivarName = null;
            
            if (strpos($data['master_seed_catalog_id'], ':') !== false) {
                [$catalogId, $cultivarIndex] = explode(':', $data['master_seed_catalog_id'], 2);
                $cultivarIndex = (int)$cultivarIndex;
            }
            
            // Get the master seed catalog and ensure it exists
            $masterCatalog = \App\Models\MasterSeedCatalog::find($catalogId);
            if ($masterCatalog) {
                // Get the specific cultivar if an index was provided
                $cultivars = is_array($masterCatalog->cultivars) ? $masterCatalog->cultivars : [];
                if ($cultivarIndex !== null && isset($cultivars[$cultivarIndex])) {
                    $selectedCultivarName = ucwords(strtolower($cultivars[$cultivarIndex]));
                } else {
                    $selectedCultivarName = !empty($cultivars) ? ucwords(strtolower($cultivars[0])) : 'Unknown Cultivar';
                }
                
                // Store the actual catalog ID (not the composite key) in the database
                $data['master_seed_catalog_id'] = $catalogId;
                
                // Update name from the master catalog with the specific cultivar
                $commonName = ucwords(strtolower($masterCatalog->common_name));
                $data['name'] = $commonName . ' (' . $selectedCultivarName . ')';
                
                \Illuminate\Support\Facades\Log::info('Updating seed consumable from master catalog', [
                    'id' => $this->record->id ?? 'new',
                    'original_selection' => $catalogId . ($cultivarIndex !== null ? ':' . $cultivarIndex : ''),
                    'master_seed_catalog_id' => $data['master_seed_catalog_id'],
                    'name' => $data['name'],
                    'selected_cultivar' => $selectedCultivarName
                ]);
            }
        }
        
        // For seed consumables, if remaining_quantity is set, calculate consumed_quantity
        if (isset($data['type']) && $data['type'] === 'seed' && isset($data['remaining_quantity']) && isset($data['total_quantity'])) {
            $total = (float) $data['total_quantity'];
            $remaining = (float) $data['remaining_quantity'];
            $data['consumed_quantity'] = max(0, $total - $remaining);
            
            \Illuminate\Support\Facades\Log::info('Seed consumable update:', [
                'id' => $this->record->id ?? 'new',
                'total_quantity' => $total,
                'remaining_quantity' => $remaining,
                'calculated_consumed' => $data['consumed_quantity']
            ]);
        }
        
        return $data;
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return ConsumableResource::form($form);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
protected function mutateFormDataBeforeFill(array $data): array
    {
        
        // Calculate current stock for display
        if (isset($data['initial_stock']) && isset($data['consumed_quantity'])) {
            $data['current_stock_display'] = max(0, $data['initial_stock'] - $data['consumed_quantity']);
        }
        
        // For seed consumables, calculate remaining_quantity from total_quantity and consumed_quantity
        if (isset($data['type']) && $data['type'] === 'seed' && isset($data['total_quantity']) && isset($data['consumed_quantity'])) {
            $data['remaining_quantity'] = max(0, $data['total_quantity'] - $data['consumed_quantity']);
            
            \Illuminate\Support\Facades\Log::info('Seed consumable form fill:', [
                'id' => $this->record->id ?? 'unknown',
                'total_quantity' => $data['total_quantity'],
                'consumed_quantity' => $data['consumed_quantity'],
                'calculated_remaining' => $data['remaining_quantity']
            ]);
        }
        
        // For seed consumables, convert the master_seed_catalog_id to composite key format for proper display
        if (isset($data['type']) && $data['type'] === 'seed' && !empty($data['master_seed_catalog_id']) && is_numeric($data['master_seed_catalog_id'])) {
            $catalogId = $data['master_seed_catalog_id'];
            $masterCatalog = \App\Models\MasterSeedCatalog::find($catalogId);
            
            if ($masterCatalog && !empty($data['name'])) {
                // Try to determine which cultivar was selected based on the stored name
                $cultivars = is_array($masterCatalog->cultivars) ? $masterCatalog->cultivars : [];
                $cultivarIndex = 0; // Default to first cultivar
                
                // Extract cultivar name from the stored name (e.g., "Broccoli (Walthams)" -> "Walthams")
                if (preg_match('/\(([^)]+)\)$/', $data['name'], $matches)) {
                    $storedCultivarName = strtolower(trim($matches[1]));
                    
                    // Find the index of the matching cultivar
                    foreach ($cultivars as $index => $cultivar) {
                        if (strtolower(trim($cultivar)) === $storedCultivarName) {
                            $cultivarIndex = $index;
                            break;
                        }
                    }
                }
                
                // Set the composite key format for the form field
                if (!empty($cultivars)) {
                    $data['master_seed_catalog_id'] = $catalogId . ':' . $cultivarIndex;
                }
                
                \Illuminate\Support\Facades\Log::info('Converting master catalog ID to composite key for form fill:', [
                    'original_id' => $catalogId,
                    'composite_key' => $data['master_seed_catalog_id'],
                    'detected_cultivar_index' => $cultivarIndex,
                    'stored_name' => $data['name']
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