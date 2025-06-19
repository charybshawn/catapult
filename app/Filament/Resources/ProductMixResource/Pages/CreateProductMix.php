<?php

namespace App\Filament\Resources\ProductMixResource\Pages;

use App\Filament\Resources\ProductMixResource;
use App\Filament\Pages\Base\BaseCreateRecord;
use Filament\Notifications\Notification;

class CreateProductMix extends BaseCreateRecord
{
    protected static string $resource = ProductMixResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove the masterSeedCatalogs from data as we'll handle it manually
        unset($data['masterSeedCatalogs']);
        
        // Validate the percentages add up to 100
        $this->validatePercentages();
        
        return $data;
    }
    
    protected function afterCreate(): void
    {
        // Manually sync the relationship with cultivar data
        $components = $this->data['masterSeedCatalogs'] ?? [];
        $syncData = [];
        
        foreach ($components as $component) {
            if (isset($component['master_seed_catalog_id']) && isset($component['percentage'])) {
                $syncData[$component['master_seed_catalog_id']] = [
                    'percentage' => $component['percentage'],
                    'cultivar' => $component['cultivar'] ?? null,
                ];
            }
        }
        
        \Illuminate\Support\Facades\Log::info('Creating mix with components:', $syncData);
        
        $this->record->masterSeedCatalogs()->sync($syncData);
    }
    
    protected function validatePercentages(): void
    {
        $components = $this->data['masterSeedCatalogs'] ?? [];
        $total = 0;
        
        foreach ($components as $component) {
            if (isset($component['percentage']) && is_numeric($component['percentage'])) {
                $total += floatval($component['percentage']);
            }
        }
        
        // Round to 2 decimal places to match database precision
        $total = round($total, 2);
        
        // Allow for very small floating point differences
        if (abs($total - 100) > 0.01) {
            Notification::make()
                ->title('Invalid Mix Percentages')
                ->body('The total percentage must equal 100%. Current total: ' . number_format($total, 2) . '%')
                ->danger()
                ->persistent()
                ->send();
                
            $this->halt();
        }
    }
} 