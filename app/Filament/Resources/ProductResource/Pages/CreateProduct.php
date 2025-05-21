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
     * This allows us to create price variations immediately
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Save any price-related data from the form
        // It will be accessible after creation
        session()->flash('product_creation_prices', [
            'base_price' => $data['base_price'] ?? null,
            'wholesale_price' => $data['wholesale_price'] ?? null,
            'bulk_price' => $data['bulk_price'] ?? null,
            'special_price' => $data['special_price'] ?? null,
        ]);
        
        return $data;
    }
    
    /**
     * Handle after the record is created
     */
    protected function afterCreate(): void
    {
        // Create default price variations based on the base price
        $this->createDefaultPriceVariations();
    }
    
    /**
     * Create the default price variations for the product
     */
    protected function createDefaultPriceVariations(): void
    {
        $product = $this->record;
        
        // Get price data from session if available
        $priceData = session()->get('product_creation_prices', []);
        
        // Only proceed if we have price data
        if (!empty($priceData['base_price']) && $priceData['base_price'] > 0) {
            // Create all standard price variations with the provided prices
            $variations = $product->createAllStandardPriceVariations($priceData);
            
            $count = count($variations);
            if ($count > 0) {
                Notification::make()
                    ->title("Created {$count} price variations")
                    ->body("Price variations were automatically created based on the product's price settings.")
                    ->success()
                    ->send();
            }
        }
        
        // Clear the session data
        session()->forget('product_creation_prices');
    }
} 