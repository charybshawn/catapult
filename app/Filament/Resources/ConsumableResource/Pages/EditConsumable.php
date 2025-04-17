<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Models\PackagingType;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditConsumable extends EditRecord
{
    protected static string $resource = ConsumableResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // This ensures the form is filled correctly with existing data
        if (isset($data['type'])) {
            // For seeds, we need to set up the seed-specific fields
            if ($data['type'] === 'seed') {
                // Initialize quantity_per_unit if not set
                if (!isset($data['quantity_per_unit']) || $data['quantity_per_unit'] <= 0) {
                    $data['quantity_per_unit'] = 1; // Default to 1g per packet if not specified
                }
                
                // Set the seed packet count (number of packets)
                $data['seed_packet_count'] = $data['current_stock'];
                
                // Set default quantity unit for seeds
                $data['quantity_unit'] = 'g';
            }
            // For non-seed types, set the non_seed_stock field
            else {
                $data['non_seed_stock'] = $data['current_stock'];
                
                // For soil, make sure quantity fields are initialized
                if ($data['type'] === 'soil' && !isset($data['quantity_per_unit']) && isset($data['total_quantity']) && $data['current_stock'] > 0) {
                    $data['quantity_per_unit'] = $data['total_quantity'] / $data['current_stock'];
                }
                
                // Set a default quantity unit if not set
                if ($data['type'] === 'soil' && !isset($data['quantity_unit'])) {
                    $data['quantity_unit'] = 'l';
                }
            }
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 