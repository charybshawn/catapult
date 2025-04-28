<?php

namespace App\Filament\Resources\ConsumableResource\Pages;

use App\Filament\Resources\ConsumableResource;
use App\Models\Consumable;
use App\Models\PackagingType;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditConsumable extends EditRecord
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
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        parent::afterSave();
        
        // Add any custom logic after save if needed
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
        $record = $this->getRecord();
        
        // Only show seed variety info for seed type consumables with a variety
        if ($record->type === 'seed' && $record->seedVariety) {
            return [
                \Filament\Widgets\StatsOverviewWidget::make([
                    \Filament\Widgets\StatsOverviewWidget\Stat::make('Variety Information', $record->seedVariety->name)
                        ->description("Crop Type: {$record->seedVariety->crop_type}")
                        ->descriptionIcon('heroicon-o-information-circle'),
                        
                    \Filament\Widgets\StatsOverviewWidget\Stat::make('Germination Rate', 
                        $record->seedVariety->germination_rate ? "{$record->seedVariety->germination_rate}%" : 'Not specified')
                        ->color('success')
                        ->icon('heroicon-o-arrow-trending-up'),
                        
                    \Filament\Widgets\StatsOverviewWidget\Stat::make('Days to Maturity', 
                        $record->seedVariety->days_to_maturity ?? 'Not specified')
                        ->color('info')
                        ->icon('heroicon-o-calendar'),
                ])
            ];
        }
        
        return [];
    }
} 