<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditProduct extends EditRecord
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
        // If base_price was changed, update the default price variation
        if ($this->record->wasChanged('base_price')) {
            $defaultVariation = $this->record->defaultPriceVariation();
            
            if ($defaultVariation) {
                $defaultVariation->update([
                    'price' => $this->record->base_price,
                ]);
                
                Notification::make()
                    ->title('Default price variation updated')
                    ->success()
                    ->send();
            } else {
                // Create a default price variation if none exists
                $this->record->createDefaultPriceVariation();
                
                Notification::make()
                    ->title('Default price variation created')
                    ->success()
                    ->send();
            }
        }
    }
} 