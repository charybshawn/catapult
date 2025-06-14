<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Pages\Base\BaseCreateRecord;
use App\Models\PriceVariation;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateProduct extends BaseCreateRecord
{
    protected static string $resource = ProductResource::class;
    
    protected $listeners = ['updateVariation', 'deleteVariation', 'setAsDefault', 'addCustomVariation'];
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    public function create(bool $another = false): void
    {
        try {
            Log::info('CreateProduct: Create method called');
            Log::info('Form state:', $this->form->getState());
            parent::create($another);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('CreateProduct: Validation error', [
                'errors' => $e->errors(),
                'validator' => $e->validator ? $e->validator->failed() : 'no validator'
            ]);
            
            // Show the validation errors to the user
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title('Validation Error')
                        ->body($field . ': ' . $message)
                        ->persistent()
                        ->send();
                }
            }
            
            throw $e;
        } catch (\Exception $e) {
            Log::error('CreateProduct: Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Show the error to the user
            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error Creating Product')
                ->body($e->getMessage())
                ->persistent()
                ->send();
                
            throw $e;
        }
    }
    
    /**
     * Handle before the record is created
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('CreateProduct: Form data before create', $data);
        // Store pending template IDs for after creation
        if (isset($data['pending_template_ids'])) {
            session()->flash('pending_template_ids', $data['pending_template_ids']);
            unset($data['pending_template_ids']);
        }
        
        // Store selected templates and custom variations for after creation (legacy support)
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
        try {
            $this->createPriceVariationsFromTemplates();
            $this->createProductPhoto();
        } catch (\Exception $e) {
            Log::error('Error in afterCreate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't throw the exception, just log it
            // This allows the redirect to happen
        }
    }
    
    /**
     * Create price variations from selected templates
     */
    protected function createPriceVariationsFromTemplates(): void
    {
        $product = $this->record;
        $pendingTemplateIds = session()->get('pending_template_ids', []);
        $selectedTemplates = session()->get('selected_templates', []);
        $customVariations = session()->get('custom_variations', []);
        
        $createdCount = 0;
        
        // Create variations from pending template IDs (new method)
        if (!empty($pendingTemplateIds)) {
            $templates = \App\Models\PriceVariation::whereIn('id', $pendingTemplateIds)
                ->where('is_global', true)
                ->where('is_active', true)
                ->get();
                
            $hasDefault = false;
            foreach ($templates as $template) {
                // Determine name based on packaging type
                $name = 'Default';
                if ($template->packaging_type_id) {
                    $packagingType = \App\Models\PackagingType::find($template->packaging_type_id);
                    if ($packagingType) {
                        $name = $packagingType->name;
                    }
                }
                
                $variation = \App\Models\PriceVariation::create([
                    'product_id' => $product->id,
                    'template_id' => $template->id,
                    'packaging_type_id' => $template->packaging_type_id,
                    'name' => $name,
                    'sku' => $template->sku,
                    'fill_weight_grams' => $template->fill_weight_grams,
                    'price' => $template->price,
                    'is_default' => !$hasDefault, // First one becomes default
                    'is_global' => false, // Product-specific
                    'is_active' => true,
                ]);
                $createdCount++;
                $hasDefault = true;
            }
        }
        
        // Create variations from selected templates (legacy method)
        foreach ($selectedTemplates as $templateData) {
            // Determine name based on packaging type
            $name = 'Default';
            if (!empty($templateData['packaging_type_id'])) {
                $packagingType = \App\Models\PackagingType::find($templateData['packaging_type_id']);
                if ($packagingType) {
                    $name = $packagingType->name;
                }
            }
            
            $variation = PriceVariation::create([
                'product_id' => $product->id,
                'template_id' => $templateData['id'], // Store the template ID
                'packaging_type_id' => $templateData['packaging_type_id'],
                'name' => $name,
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
            // Determine name based on packaging type
            $name = 'Default';
            if (!empty($variationData['packaging_type_id'])) {
                $packagingType = \App\Models\PackagingType::find($variationData['packaging_type_id']);
                if ($packagingType) {
                    $name = $packagingType->name;
                }
            }
            
            $variation = PriceVariation::create([
                'product_id' => $product->id,
                'packaging_type_id' => $variationData['packaging_type_id'] ?? null,
                'name' => $name,
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
            $this->sendCustomNotification(
                Notification::make()
                    ->title("Product created successfully!")
                    ->body("Created {$product->name} with {$createdCount} price variations from templates.")
                    ->success()
            );
        }
        
        // Clear session data
        session()->forget(['pending_template_ids', 'selected_templates', 'custom_variations']);
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
    
    /**
     * Update a price variation from the table
     */
    public function updateVariation($variationId, array $data)
    {
        $variation = PriceVariation::findOrFail($variationId);
        
        // Ensure the variation belongs to this product
        if ($variation->product_id !== $this->record->id) {
            return;
        }
        
        // Determine the name based on packaging type
        $name = 'Default';
        if (!empty($data['packaging_type_id'])) {
            $packagingType = \App\Models\PackagingType::find($data['packaging_type_id']);
            if ($packagingType) {
                $name = $packagingType->name;
            }
        }
        
        $variation->update([
            'name' => $name,
            'packaging_type_id' => $data['packaging_type_id'] ?: null,
            'sku' => $data['sku'] ?: null,
            'fill_weight_grams' => $data['fill_weight_grams'] ?: null,
            'price' => $data['price'],
        ]);
        
        Notification::make()
            ->title('Price variation updated')
            ->success()
            ->send();
            
        $this->dispatch('refresh-price-variations');
    }
    
    /**
     * Delete a price variation
     */
    public function deleteVariation($variationId)
    {
        $variation = PriceVariation::findOrFail($variationId);
        
        // Ensure the variation belongs to this product
        if ($variation->product_id !== $this->record->id) {
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
        
        $variation->delete();
        
        // If we deleted the default, make another one default
        if ($variation->is_default) {
            $newDefault = $this->record->priceVariations()->where('is_active', true)->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }
        
        Notification::make()
            ->title('Price variation deleted')
            ->success()
            ->send();
            
        $this->dispatch('refresh-price-variations');
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
        
        Notification::make()
            ->title('Default price variation updated')
            ->success()
            ->send();
            
        $this->dispatch('refresh-price-variations');
    }
    
    /**
     * Add a custom price variation
     */
    public function addCustomVariation()
    {
        $variation = PriceVariation::create([
            'product_id' => $this->record->id,
            'name' => 'Default',
            'price' => 0,
            'is_default' => $this->record->priceVariations()->count() === 0,
            'is_active' => true,
            'is_global' => false,
        ]);
        
        Notification::make()
            ->title('Price variation added')
            ->body('Please edit the variation to set its details.')
            ->success()
            ->send();
            
        $this->dispatch('refresh-price-variations');
    }
} 