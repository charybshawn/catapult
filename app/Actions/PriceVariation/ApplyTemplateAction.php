<?php

namespace App\Actions\PriceVariation;

use InvalidArgumentException;
use App\Models\Product;
use App\Models\PriceVariation;

/**
 * Apply Template Action
 * Extracted from PriceVariationResource apply_template action (lines 518-537)
 * Following Filament Resource Architecture Guide patterns
 * Max 100 lines as per requirements for Action classes
 */
class ApplyTemplateAction
{
    /**
     * Execute the apply template action
     */
    public function execute(PriceVariation $template, array $data): PriceVariation
    {
        // Validate that the source is a global template
        if (!$template->is_global) {
            throw new InvalidArgumentException('Only global templates can be applied to products');
        }

        // Create a new product-specific variation based on the template
        return PriceVariation::create([
            'product_id' => $data['product_id'],
            'packaging_type_id' => $template->packaging_type_id,
            'name' => $data['name'],
            'sku' => $data['sku'],
            'fill_weight' => $data['fill_weight'],
            'price' => $data['price'],
            'pricing_type' => $template->pricing_type,
            'pricing_unit' => $template->pricing_unit,
            'is_default' => $data['is_default'],
            'is_global' => false, // Always false for applied variations
            'is_active' => $data['is_active'],
            'description' => $template->description,
        ]);
    }

    /**
     * Validate template application data
     */
    public function validate(PriceVariation $template, array $data): array
    {
        $errors = [];

        // Check if product already has a variation with the same packaging
        $existingVariation = PriceVariation::where('product_id', $data['product_id'])
            ->where('packaging_type_id', $template->packaging_type_id)
            ->where('pricing_type', $template->pricing_type)
            ->first();

        if ($existingVariation) {
            $errors[] = 'Product already has a variation with this packaging and pricing type';
        }

        // Validate that the product exists
        if (!$data['product_id'] || !Product::find($data['product_id'])) {
            $errors[] = 'Invalid product selected';
        }

        // Validate required fields
        if (empty($data['name'])) {
            $errors[] = 'Variation name is required';
        }

        if (!is_numeric($data['price']) || $data['price'] < 0) {
            $errors[] = 'Valid price is required';
        }

        if (!is_numeric($data['fill_weight']) || $data['fill_weight'] <= 0) {
            $errors[] = 'Valid fill weight is required';
        }

        return $errors;
    }

    /**
     * Get suggested values for applying template
     */
    public function getSuggestedValues(PriceVariation $template, int $productId): array
    {
        $product = Product::find($productId);
        
        if (!$product) {
            return [];
        }

        return [
            'name' => $this->generateSuggestedName($template, $product),
            'price' => $template->price,
            'sku' => $this->generateSuggestedSku($template, $product),
            'fill_weight' => $template->fill_weight,
        ];
    }

    /**
     * Generate suggested name for the applied variation
     */
    protected function generateSuggestedName(PriceVariation $template, Product $product): string
    {
        $pricingType = ucfirst($template->pricing_type);
        $packaging = $template->packagingType ? $template->packagingType->name : 'Package-Free';
        
        return "{$pricingType} - {$packaging}";
    }

    /**
     * Generate suggested SKU for the applied variation
     */
    protected function generateSuggestedSku(PriceVariation $template, Product $product): ?string
    {
        // Use template SKU as base if available
        if ($template->sku) {
            // Append product identifier to make it unique
            return $template->sku . '-' . $product->id;
        }

        return null;
    }
}