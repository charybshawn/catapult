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
        if ($data['type'] === 'packaging' && empty($data['name']) && !empty($data['packaging_type_id'])) {
            $packagingType = PackagingType::find($data['packaging_type_id']);
            if ($packagingType) {
                $data['name'] = $packagingType->display_name ?? $packagingType->name;
            }
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
        
        return $data;
    }
    
    // Show seed variety information if available
    protected function getHeaderWidgets(): array
    {
        // Temporarily disabled to fix Livewire error
        return [];
    }
} 