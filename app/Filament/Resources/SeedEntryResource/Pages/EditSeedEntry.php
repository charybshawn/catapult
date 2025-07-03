<?php

namespace App\Filament\Resources\SeedEntryResource\Pages;

use App\Filament\Resources\SeedEntryResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Notifications\Notification;
use App\Models\SeedVariation;

class EditSeedEntry extends BaseEditRecord
{
    protected static string $resource = SeedEntryResource::class;
    
    protected $listeners = ['updateVariation', 'deleteVariation', 'addVariation', 'editVariation'];
    
    public $editingVariation = null;
    public $editingVariationData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    /**
     * Add a new seed variation
     */
    public function addVariation()
    {
        try {
            $variation = SeedVariation::create([
                'seed_entry_id' => $this->record->id,
                'size' => 'New Size',
                'current_price' => 1.00, // Default $1.00 to pass validation
                'currency' => 'USD',
                'weight_kg' => 0.025, // Default 25g
                'unit' => 'grams', // Default unit
                'is_available' => true,
                'last_checked_at' => now(),
            ]);
            
            // Refresh the record to ensure the relationship is updated
            $this->record->refresh();
            
            Notification::make()
                ->title('Variation added successfully')
                ->body("New variation '{$variation->size}' created with ID {$variation->id}. The form will refresh to show the new variation.")
                ->success()
                ->send();
                
            // Refresh the form to show the new variation
            $this->dispatch('$refresh');
            
            // Prevent any form submission
            return false;
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to create variation')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    /**
     * Update a seed variation
     */
    public function updateVariation($variationId, array $data)
    {
        $variation = SeedVariation::findOrFail($variationId);
        
        // Ensure the variation belongs to this seed entry
        if ($variation->seed_entry_id !== $this->record->id) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You cannot update this variation.')
                ->danger()
                ->send();
            return;
        }
        
        // Prepare update data
        $updateData = [
            'size' => $data['size'] ?? $variation->size,
            'sku' => $data['sku'] ?? $variation->sku,
            'weight_kg' => $data['weight_kg'] ?? $variation->weight_kg,
            'current_price' => $data['current_price'] ?? $variation->current_price,
            'currency' => $data['currency'] ?? $variation->currency,
            'is_available' => $data['is_available'] ?? $variation->is_available,
        ];
        
        $variation->update($updateData);
        
        // Refresh the record to ensure the relationship is updated
        $this->record->refresh();
        
        Notification::make()
            ->title('Variation updated')
            ->success()
            ->send();
            
        // Refresh the form
        $this->dispatch('$refresh');
    }
    
    /**
     * Delete a seed variation
     */
    public function deleteVariation($variationId)
    {
        try {
            $variation = SeedVariation::findOrFail($variationId);
            
            // Ensure the variation belongs to this seed entry
            if ($variation->seed_entry_id !== $this->record->id) {
                Notification::make()
                    ->title('Unauthorized')
                    ->body('You cannot delete this variation.')
                    ->danger()
                    ->send();
                return;
            }
            
            $variationName = $variation->size;
            $variation->delete();
            
            // Refresh the record to ensure the relationship is updated
            $this->record->refresh();
            
            Notification::make()
                ->title('Variation deleted')
                ->body("'{$variationName}' has been removed.")
                ->success()
                ->send();
                
            // Refresh the form
            $this->dispatch('$refresh');
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error deleting variation')
                ->body('An error occurred while deleting the seed variation.')
                ->danger()
                ->send();
        }
    }
    
    /**
     * Edit a specific variation inline
     */
    public function editVariation($variationId)
    {
        $variation = SeedVariation::find($variationId);
        
        if (!$variation) {
            Notification::make()
                ->title('Variation not found')
                ->danger()
                ->send();
            return;
        }
        
        // Set up editing state
        $this->editingVariation = $variationId;
        $this->editingVariationData = [
            'size' => $variation->size,
            'current_price' => $variation->current_price,
            'weight_kg' => $variation->weight_kg,
            'currency' => $variation->currency,
            'sku' => $variation->sku,
            'is_available' => $variation->is_available,
        ];
        
        // Refresh to show the edit form
        $this->dispatch('$refresh');
    }
    
    /**
     * Save the edited variation
     */
    public function saveVariation()
    {
        if (!$this->editingVariation) {
            return;
        }
        
        $variation = SeedVariation::find($this->editingVariation);
        
        if (!$variation || $variation->seed_entry_id !== $this->record->id) {
            Notification::make()
                ->title('Invalid variation')
                ->danger()
                ->send();
            return;
        }
        
        // Validate the data
        $this->validate([
            'editingVariationData.size' => 'required|string|max:255',
            'editingVariationData.current_price' => 'required|numeric|min:0.01|max:50000',
            'editingVariationData.weight_kg' => 'required|numeric|min:0.001',
            'editingVariationData.currency' => 'required|string|in:USD,CAD,EUR',
            'editingVariationData.sku' => 'nullable|string|max:255',
            'editingVariationData.is_available' => 'boolean',
        ]);
        
        // Update the variation
        $variation->update($this->editingVariationData);
        
        // Clear editing state
        $this->editingVariation = null;
        $this->editingVariationData = [];
        
        // Refresh the record and form
        $this->record->refresh();
        $this->dispatch('$refresh');
        
        Notification::make()
            ->title('Variation updated')
            ->success()
            ->send();
    }
    
    /**
     * Cancel editing
     */
    public function cancelEditVariation()
    {
        $this->editingVariation = null;
        $this->editingVariationData = [];
        $this->dispatch('$refresh');
    }
    
    /**
     * Bulk edit variations (placeholder)
     */
    public function bulkEditVariations()
    {
        $this->dispatch('scrollToVariations');
        
        Notification::make()
            ->title('Bulk Edit')
            ->body('Use the relation manager tab below to manage multiple variations at once.')
            ->info()
            ->send();
    }
} 