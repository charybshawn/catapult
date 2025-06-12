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
        
        // For seed consumables, if remaining_quantity is set, calculate consumed_quantity
        if ($data['type'] === 'seed' && isset($data['remaining_quantity']) && isset($data['total_quantity'])) {
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
        
        return $data;
    }
    
    // Show seed variety information if available
    protected function getHeaderWidgets(): array
    {
        // Temporarily disabled to fix Livewire error
        return [];
    }
} 