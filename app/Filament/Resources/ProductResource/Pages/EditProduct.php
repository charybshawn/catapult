<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;

class EditProduct extends BaseEditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function afterSave(): void
    {
        $priceFields = [
            'base_price' => 'Default',
            'wholesale_price' => 'Wholesale',
            'bulk_price' => 'Bulk',
            'special_price' => 'Special'
        ];
        
        $updatedPrices = [];
        $created = [];
        
        // Check each price field
        foreach ($priceFields as $field => $variationName) {
            if ($this->record->wasChanged($field) && $this->record->{$field}) {
                // Find the variation if it exists
                $variation = $this->record->priceVariations()
                    ->where('name', $variationName)
                    ->first();
                
                if ($variation) {
                    // Update existing variation
                    $variation->update(['price' => $this->record->{$field}]);
                    $updatedPrices[] = $variationName;
                } else {
                    // Create new variation based on the field name
                    $method = 'create' . $variationName . 'PriceVariation';
                    if (method_exists($this->record, $method)) {
                        $this->record->{$method}();
                        $created[] = $variationName;
                    }
                }
            }
        }
        
        // Show notification for updated variations
        if (!empty($updatedPrices)) {
            Notification::make()
                ->title(count($updatedPrices) . ' price variation(s) updated')
                ->body('Updated: ' . implode(', ', $updatedPrices))
                ->success()
                ->send();
        }
        
        // Show notification for created variations
        if (!empty($created)) {
            Notification::make()
                ->title(count($created) . ' price variation(s) created')
                ->body('Created: ' . implode(', ', $created))
                ->success()
                ->send();
        }
    }
} 