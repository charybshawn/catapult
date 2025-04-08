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
        // Ensure the quantity fields are visible if the type is soil or seed
        if (isset($data['type']) && in_array($data['type'], ['soil', 'seed'])) {
            // Make sure quantity fields are initialized with their proper values
            if (!isset($data['quantity_per_unit']) && isset($data['total_quantity']) && $data['current_stock'] > 0) {
                $data['quantity_per_unit'] = $data['total_quantity'] / $data['current_stock'];
            }
            
            // Set a default quantity unit if not set
            if (!isset($data['quantity_unit'])) {
                $data['quantity_unit'] = $data['type'] === 'soil' ? 'l' : 'g';
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
        
        // Calculate total quantity
        if (in_array($data['type'], ['soil', 'seed']) && !empty($data['quantity_per_unit'])) {
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