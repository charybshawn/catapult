<?php

namespace App\Filament\Resources\SeedEntryResource\Pages;

use App\Filament\Resources\SeedEntryResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Notifications\Notification;

class EditSeedEntry extends BaseEditRecord
{
    protected static string $resource = SeedEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    /**
     * Livewire method to add a new variation
     */
    public function addVariation()
    {
        // Scroll to the variations section and open it
        $this->dispatch('scrollToVariations');
        
        Notification::make()
            ->title('Add Variation')
            ->body('Use the "Variations" tab below to add a new price variation.')
            ->info()
            ->send();
    }
    
    /**
     * Livewire method to edit a specific variation
     */
    public function editVariation($variationId)
    {
        $variation = \App\Models\SeedVariation::find($variationId);
        
        if (!$variation) {
            Notification::make()
                ->title('Variation not found')
                ->danger()
                ->send();
            return;
        }
        
        // Scroll to variations and show info
        $this->dispatch('scrollToVariations');
        
        Notification::make()
            ->title('Edit Variation')
            ->body("Use the \"Variations\" tab below to edit \"{$variation->size}\".")
            ->info()
            ->send();
    }
    
    /**
     * Livewire method to bulk edit variations
     */
    public function bulkEditVariations()
    {
        $this->dispatch('scrollToVariations');
        
        Notification::make()
            ->title('Bulk Edit')
            ->body('Use the "Variations" tab below to manage multiple variations at once.')
            ->info()
            ->send();
    }
} 