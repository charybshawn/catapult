<?php

namespace App\Forms\Components;

use Filament\Forms\Components\ViewField;
use App\Models\Product;
use App\Models\PriceVariation;

class ProductInventoryVariationsTable extends ViewField
{
    protected string $view = 'forms.components.product-inventory-variations-table';

    public static function make(string $name): static
    {
        $static = parent::make($name);
        return $static;
    }

    public function getProductId(): ?int
    {
        return $this->evaluate($this->productId ?? null);
    }

    public function productId(int | callable | null $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function getVariations(): array
    {
        $productId = $this->getProductId();
        
        if (!$productId) {
            return [];
        }

        $product = Product::with(['priceVariations.packagingType'])->find($productId);
        
        if (!$product) {
            return [];
        }

        return $product->priceVariations()
            ->where('is_active', true)
            ->with('packagingType')
            ->get()
            ->map(function ($variation) {
                return [
                    'id' => $variation->id,
                    'name' => $variation->name,
                    'packaging_name' => $variation->packagingType?->display_name ?? 'Package-Free',
                    'fill_weight' => $variation->fill_weight_grams,
                    'price' => $variation->price,
                    'sku' => $variation->sku,
                    // Default inventory values
                    'quantity' => 0,
                    'cost_per_unit' => 0,
                    'location' => '',
                    'lot_number' => '',
                    'production_date' => now()->toDateString(),
                    'expiration_date' => null,
                    'notes' => '',
                ];
            })
            ->toArray();
    }

    public function getBatchNumber(): string
    {
        $productId = $this->getProductId();
        
        if (!$productId) {
            return '';
        }

        $product = Product::find($productId);
        
        return $product?->getNextBatchNumber() ?? '';
    }
}