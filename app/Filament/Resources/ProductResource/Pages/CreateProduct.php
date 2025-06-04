<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Pages\Base\BaseCreateRecord;
use App\Models\PriceVariation;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateProduct extends BaseCreateRecord
{
    protected static string $resource = ProductResource::class;
    
    /**
     * Handle before the record is created
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Store selected templates and custom variations for after creation
        if (isset($data['selected_templates'])) {
            session()->flash('selected_templates', json_decode($data['selected_templates'], true));
            unset($data['selected_templates']);
        }
        
        if (isset($data['custom_variations'])) {
            session()->flash('custom_variations', json_decode($data['custom_variations'], true));
            unset($data['custom_variations']);
        }
        
        // Handle photo upload
        if (isset($data['photo']) && $data['photo']) {
            session()->flash('product_photo', $data['photo']);
            unset($data['photo']);
        }
        
        return $data;
    }
    
    /**
     * Handle after the record is created
     */
    protected function afterCreate(): void
    {
        $this->createPriceVariationsFromTemplates();
        $this->createProductPhoto();
    }
    
    /**
     * Create price variations from selected templates
     */
    protected function createPriceVariationsFromTemplates(): void
    {
        $product = $this->record;
        $selectedTemplates = session()->get('selected_templates', []);
        $customVariations = session()->get('custom_variations', []);
        
        $createdCount = 0;
        
        // Create variations from selected templates
        foreach ($selectedTemplates as $templateData) {
            $variation = PriceVariation::create([
                'product_id' => $product->id,
                'packaging_type_id' => $templateData['packaging_type_id'],
                'name' => $templateData['name'],
                'sku' => $templateData['sku'] ?? null,
                'fill_weight_grams' => $templateData['fill_weight_grams'],
                'price' => $templateData['price'],
                'is_default' => $templateData['is_default'],
                'is_global' => false, // Product-specific
                'is_active' => $templateData['is_active'],
            ]);
            $createdCount++;
        }
        
        // Create custom variations
        foreach ($customVariations as $variationData) {
            $variation = PriceVariation::create([
                'product_id' => $product->id,
                'packaging_type_id' => $variationData['packaging_type_id'] ?? null,
                'name' => $variationData['name'],
                'sku' => $variationData['sku'] ?? null,
                'fill_weight_grams' => $variationData['fill_weight_grams'] ?? null,
                'price' => $variationData['price'],
                'is_default' => $variationData['is_default'] ?? false,
                'is_global' => false,
                'is_active' => $variationData['is_active'] ?? true,
            ]);
            $createdCount++;
        }
        
        // Show notification if variations were created
        if ($createdCount > 0) {
            Notification::make()
                ->title("Created {$createdCount} price variations")
                ->body("Price variations were created from the selected templates.")
                ->success()
                ->send();
        }
        
        // Clear session data
        session()->forget(['selected_templates', 'custom_variations']);
    }
    
    /**
     * Create product photo if uploaded
     */
    protected function createProductPhoto(): void
    {
        $product = $this->record;
        $photoPath = session()->get('product_photo');
        
        if ($photoPath) {
            $product->photos()->create([
                'photo' => $photoPath,
                'is_default' => true,
                'order' => 1,
            ]);
            
            session()->forget('product_photo');
        }
    }
} 