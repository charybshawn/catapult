<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Pages\Base\BaseEditRecord;
use App\Models\PriceVariation;
use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EditProduct extends BaseEditRecord
{
    protected static string $resource = ProductResource::class;
    
    protected $listeners = ['updateVariation', 'deleteVariation', 'setAsDefault', 'addCustomVariation'];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    /**
     * Handle before the record is saved
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Store selected templates and custom variations for after save
        if (isset($data['selected_templates'])) {
            session()->flash('updated_selected_templates', json_decode($data['selected_templates'], true));
            unset($data['selected_templates']);
        }
        
        if (isset($data['custom_variations'])) {
            session()->flash('updated_custom_variations', json_decode($data['custom_variations'], true));
            unset($data['custom_variations']);
        }
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        // Handle price variations selector updates
        $this->updatePriceVariationsFromTemplates();
        
        // Handle legacy price fields (fallback for backward compatibility)
        $this->handleLegacyPriceFields();
    }
    
    /**
     * Update price variations from the selector component
     */
    protected function updatePriceVariationsFromTemplates(): void
    {
        $product = $this->record;
        $selectedTemplates = session()->get('updated_selected_templates', []);
        $customVariations = session()->get('updated_custom_variations', []);
        
        if (empty($selectedTemplates) && empty($customVariations)) {
            return; // No changes to process
        }
        
        // Get current template-based variations for comparison
        $currentTemplateVariations = $product->priceVariations()
            ->with('packagingType')
            ->whereNotNull('template_id')
            ->get()
            ->keyBy('template_id');
        
        // Get current custom variations
        $currentCustomVariations = $product->priceVariations()
            ->with('packagingType')
            ->whereNull('template_id')
            ->get();
        
        $createdCount = 0;
        $updatedCount = 0;
        $deletedCount = 0;
        
        // Process selected templates
        $selectedTemplateIds = collect($selectedTemplates)->pluck('id')->toArray();
        
        foreach ($selectedTemplates as $templateData) {
            $templateId = $templateData['id'];
            
            if ($currentTemplateVariations->has($templateId)) {
                // Update existing variation
                $variation = $currentTemplateVariations->get($templateId);
                
                // Use the template's original name instead of generating a new one
                $name = $templateData['name'] ?? 'Default';
                
                $variation->update([
                    'name' => $name,
                    'price' => $templateData['price'],
                    'packaging_type_id' => $templateData['packaging_type_id'],
                    'fill_weight_grams' => $templateData['fill_weight_grams'],
                    'is_default' => $templateData['is_default'],
                    'is_active' => $templateData['is_active'],
                ]);
                $updatedCount++;
            } else {
                // Create new variation from template
                
                // Use the template's original name instead of generating a new one
                $name = $templateData['name'] ?? 'Default';
                
                PriceVariation::create([
                    'product_id' => $product->id,
                    'template_id' => $templateId,
                    'packaging_type_id' => $templateData['packaging_type_id'],
                    'name' => $name,
                    'sku' => $templateData['sku'] ?? null,
                    'fill_weight_grams' => $templateData['fill_weight_grams'],
                    'price' => $templateData['price'],
                    'is_default' => $templateData['is_default'],
                    'is_global' => false,
                    'is_active' => $templateData['is_active'],
                ]);
                $createdCount++;
            }
        }
        
        // Remove template variations that are no longer selected
        $variationsToDelete = $currentTemplateVariations->filter(function ($variation) use ($selectedTemplateIds) {
            return !in_array($variation->template_id, $selectedTemplateIds);
        });
        
        foreach ($variationsToDelete as $variation) {
            $variation->delete();
            $deletedCount++;
        }
        
        // Process custom variations (for now, this is minimal since custom editing is not fully implemented)
        // This would be expanded when custom variation editing is implemented
        
        // Show notification if any changes were made
        if ($createdCount > 0 || $updatedCount > 0 || $deletedCount > 0) {
            $message = [];
            if ($createdCount > 0) $message[] = "{$createdCount} created";
            if ($updatedCount > 0) $message[] = "{$updatedCount} updated";
            if ($deletedCount > 0) $message[] = "{$deletedCount} removed";
            
            Notification::make()
                ->title('Price variations updated successfully!')
                ->body('Template variations: ' . implode(', ', $message))
                ->success()
                ->send();
        }
        
        // Clear session data
        session()->forget(['updated_selected_templates', 'updated_custom_variations']);
    }
    
    /**
     * Handle legacy price fields for backward compatibility
     */
    protected function handleLegacyPriceFields(): void
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
        
        // Show notification for updated variations (only if no template variations were processed)
        if (!empty($updatedPrices) && !session()->has('updated_selected_templates')) {
            Notification::make()
                ->title(count($updatedPrices) . ' price variation(s) updated')
                ->body('Updated: ' . implode(', ', $updatedPrices))
                ->success()
                ->send();
        }
        
        // Show notification for created variations (only if no template variations were processed)
        if (!empty($created) && !session()->has('updated_selected_templates')) {
            Notification::make()
                ->title(count($created) . ' price variation(s) created')
                ->body('Created: ' . implode(', ', $created))
                ->success()
                ->send();
        }
    }
    
    /**
     * Update a price variation from the table
     */
    public function updateVariation($variationId, array $data)
    {
        Log::info('updateVariation called', ['variationId' => $variationId, 'data' => $data]);
        
        $variation = PriceVariation::findOrFail($variationId);
        
        // Ensure the variation belongs to this product
        if ($variation->product_id !== $this->record->id) {
            Notification::make()
                ->title('Unauthorized')
                ->body('You cannot update this variation.')
                ->danger()
                ->send();
            return;
        }
        
        // Prepare update data from the data array
        $updateData = [];
        
        if (isset($data['name'])) {
            $updateData['name'] = trim($data['name']);
            $updateData['is_name_manual'] = $data['is_name_manual'] ?? true;
        }
        
        if (isset($data['pricing_type'])) {
            $updateData['pricing_type'] = $data['pricing_type'];
        }
        
        if (isset($data['pricing_unit'])) {
            $updateData['pricing_unit'] = $data['pricing_unit'];
        }
        
        if (isset($data['packaging_type_id'])) {
            $updateData['packaging_type_id'] = $data['packaging_type_id'] ?: null;
        }
        
        if (isset($data['sku'])) {
            $updateData['sku'] = $data['sku'] ?: null;
        }
        
        if (isset($data['fill_weight_grams'])) {
            $updateData['fill_weight_grams'] = $data['fill_weight_grams'] ?: null;
        }
        
        if (isset($data['price'])) {
            $updateData['price'] = $data['price'];
        }
        
        if (!empty($updateData)) {
            $variation->update($updateData);
            
            // Refresh the record to ensure the relationship is updated
            $this->record->refresh();
            
            Notification::make()
                ->title('Price variation updated')
                ->success()
                ->send();
        }
        
        // Dispatch event to refresh the form
        $this->dispatch('$refresh');
    }
    
    /**
     * Delete a price variation
     */
    public function deleteVariation($variationId)
    {
        try {
            $variation = PriceVariation::findOrFail($variationId);
            
            // Ensure the variation belongs to this product
            if ($variation->product_id !== $this->record->id) {
                Notification::make()
                    ->title('Unauthorized')
                    ->body('You cannot delete this variation.')
                    ->danger()
                    ->send();
                return;
            }
            
            // Don't allow deleting the default variation if it's the only one
            if ($variation->is_default && $this->record->priceVariations()->count() === 1) {
                Notification::make()
                    ->title('Cannot delete the only price variation')
                    ->body('This product must have at least one price variation.')
                    ->danger()
                    ->send();
                return;
            }
            
            $variationName = $variation->name;
            $wasDefault = $variation->is_default;
            
            $variation->delete();
            
            // If we deleted the default, make another one default
            if ($wasDefault) {
                $newDefault = $this->record->priceVariations()->where('is_active', true)->first();
                if ($newDefault) {
                    $newDefault->update(['is_default' => true]);
                }
            }
            
            // Refresh the record to ensure the relationship is updated
            $this->record->refresh();
            
            Notification::make()
                ->title('Price variation deleted')
                ->body("'{$variationName}' has been removed.")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error deleting variation')
                ->body('An error occurred while deleting the price variation.')
                ->danger()
                ->send();
                
            Log::error('Error deleting price variation', [
                'variation_id' => $variationId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Set a variation as default
     */
    public function setAsDefault($variationId)
    {
        $variation = PriceVariation::findOrFail($variationId);
        
        // Ensure the variation belongs to this product
        if ($variation->product_id !== $this->record->id) {
            return;
        }
        
        // Remove default from all other variations
        $this->record->priceVariations()->update(['is_default' => false]);
        
        // Set this variation as default
        $variation->update(['is_default' => true]);
        
        // Refresh the record to ensure the relationship is updated
        $this->record->refresh();
        
        Notification::make()
            ->title('Default price variation updated')
            ->success()
            ->send();
    }
    
    /**
     * Add a custom price variation
     */
    public function addCustomVariation()
    {
        $variation = PriceVariation::create([
            'product_id' => $this->record->id,
            'name' => 'Default',
            'pricing_type' => 'retail',
            'pricing_unit' => 'per_item',
            'price' => 0,
            'is_default' => $this->record->priceVariations()->count() === 0,
            'is_active' => true,
            'is_global' => false,
            'is_name_manual' => false,
        ]);
        
        // Refresh the record to ensure the relationship is updated
        $this->record->refresh();
        
        Notification::make()
            ->title('Price variation added')
            ->body('Please edit the variation to set its details.')
            ->success()
            ->send();
    }
} 