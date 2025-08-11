<?php

namespace Database\Seeders\Data;

use App\Models\PackagingType;
use App\Models\PriceVariation;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PriceVariationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates price variations based on the current production data.
     * It includes both global template variations and product-specific variations.
     */
    public function run(): void
    {
        // Clear existing price variations to avoid duplicates
        $this->command->info('Clearing existing price variations...');
        PriceVariation::truncate();

        // Create global template variations (these serve as templates for products)
        $this->command->info('Creating global template variations...');
        $this->createGlobalVariations();

        // Create product-specific variations
        $this->command->info('Creating product-specific variations...');
        $this->createProductSpecificVariations();

        $this->command->info('Price variations seeded successfully!');
    }

    /**
     * Create global template variations (is_global = true)
     */
    private function createGlobalVariations(): void
    {
        $clamshell24oz = PackagingType::where('name', 'Clamshell (24oz)')->first();
        $clamshell32oz = PackagingType::where('name', 'Clamshell (32oz)')->first();

        $globalVariations = [
            [
                'name' => 'Retail - Clamshell (24oz) - $5.00',
                'product_id' => null,
                'template_id' => null,
                'packaging_type_id' => $clamshell24oz?->id,
                'pricing_type' => 'retail',
                'pricing_unit' => null,
                'sku' => null,
                'fill_weight_grams' => null,
                'price' => 5.00,
                'is_default' => false,
                'is_global' => true,
                'is_active' => true,
                'is_name_manual' => false,
            ],
            [
                'name' => 'Wholesale - Clamshell (24oz) - $3.50',
                'product_id' => null,
                'template_id' => null,
                'packaging_type_id' => $clamshell24oz?->id,
                'pricing_type' => 'wholesale',
                'pricing_unit' => 'per_item',
                'sku' => null,
                'fill_weight_grams' => null,
                'price' => 3.50,
                'is_default' => false,
                'is_global' => true,
                'is_active' => true,
                'is_name_manual' => false,
            ],
            [
                'name' => 'Retail - Clamshell (32oz) - $5.00',
                'product_id' => null,
                'template_id' => null,
                'packaging_type_id' => $clamshell32oz?->id,
                'pricing_type' => 'retail',
                'pricing_unit' => null,
                'sku' => null,
                'fill_weight_grams' => null,
                'price' => 5.00,
                'is_default' => false,
                'is_global' => true,
                'is_active' => true,
                'is_name_manual' => false,
            ],
            [
                'name' => 'Retail - Clamshell (32oz) - $3.50',
                'product_id' => null,
                'template_id' => null,
                'packaging_type_id' => $clamshell32oz?->id,
                'pricing_type' => 'retail',
                'pricing_unit' => null,
                'sku' => null,
                'fill_weight_grams' => null,
                'price' => 3.50,
                'is_default' => false,
                'is_global' => true,
                'is_active' => true,
                'is_name_manual' => false,
            ],
            [
                'name' => 'Retail - Live Tray',
                'product_id' => null,
                'template_id' => null,
                'packaging_type_id' => null,
                'pricing_type' => 'retail',
                'pricing_unit' => 'per_item',
                'sku' => null,
                'fill_weight_grams' => null,
                'price' => 20.00,
                'is_default' => false,
                'is_global' => true,
                'is_active' => true,
                'is_name_manual' => true,
            ],
            [
                'name' => 'Retail - Bulk',
                'product_id' => null,
                'template_id' => null,
                'packaging_type_id' => null,
                'pricing_type' => 'retail',
                'pricing_unit' => 'per_item',
                'sku' => null,
                'fill_weight_grams' => null,
                'price' => 0.20,
                'is_default' => false,
                'is_global' => true,
                'is_active' => true,
                'is_name_manual' => true,
            ],
        ];

        foreach ($globalVariations as $variation) {
            PriceVariation::create($variation);
        }
    }

    /**
     * Create product-specific variations based on current production data
     */
    private function createProductSpecificVariations(): void
    {
        $clamshell24oz = PackagingType::where('name', 'Clamshell (24oz)')->first();
        $clamshell32oz = PackagingType::where('name', 'Clamshell (32oz)')->first();

        // Get template IDs that were just created
        $retailClamshell24Template = PriceVariation::where('is_global', true)
            ->where('pricing_type', 'retail')
            ->where('packaging_type_id', $clamshell24oz?->id)
            ->where('price', 5.00)
            ->first()?->id;

        $wholesaleClamshell24Template = PriceVariation::where('is_global', true)
            ->where('pricing_type', 'wholesale')
            ->where('packaging_type_id', $clamshell24oz?->id)
            ->first()?->id;

        $retailClamshell32Template = PriceVariation::where('is_global', true)
            ->where('pricing_type', 'retail')
            ->where('packaging_type_id', $clamshell32oz?->id)
            ->where('price', 5.00)
            ->first()?->id;

        $retailClamshell32SecondTemplate = PriceVariation::where('is_global', true)
            ->where('pricing_type', 'retail')
            ->where('packaging_type_id', $clamshell32oz?->id)
            ->where('price', 3.50)
            ->first()?->id;

        $bulkTemplate = PriceVariation::where('is_global', true)
            ->where('name', 'Retail - Bulk')
            ->first()?->id;

        // Product-specific variations with their actual data
        $productVariations = [
            // Sunflower Shoots (Product ID: 2)
            [
                'product_name' => 'Sunflower Shoots',
                'variations' => [
                    [
                        'name' => 'Retail - Clamshell (24oz) - $5.00',
                        'template_id' => $retailClamshell24Template,
                        'packaging_type_id' => $clamshell24oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => 'per_item',
                        'sku' => null,
                        'fill_weight_grams' => 80.00,
                        'price' => 5.00,
                        'is_default' => true,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                    [
                        'name' => 'Wholesale - Clamshell (24oz) - $3.50',
                        'template_id' => $wholesaleClamshell24Template,
                        'packaging_type_id' => $clamshell24oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => 'per_item',
                        'sku' => null,
                        'fill_weight_grams' => 80.00,
                        'price' => 3.50,
                        'is_default' => false,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                    [
                        'name' => 'Retail - Bulk',
                        'template_id' => $bulkTemplate,
                        'packaging_type_id' => null,
                        'pricing_type' => 'retail',
                        'pricing_unit' => 'per_lb',
                        'sku' => null,
                        'fill_weight_grams' => 453,
                        'price' => 33.00,
                        'is_default' => false,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                ],
            ],

            // Pea Shoots (Product ID: 3)
            [
                'product_name' => 'Pea Shoots',
                'variations' => [
                    [
                        'name' => 'Retail - Clamshell (32oz) - $5.00',
                        'template_id' => $retailClamshell32Template,
                        'packaging_type_id' => $clamshell32oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => 'per_item',
                        'sku' => null,
                        'fill_weight_grams' => 70.00,
                        'price' => 5.00,
                        'is_default' => true,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                    [
                        'name' => 'Retail - Clamshell (32oz) - $3.50',
                        'template_id' => $retailClamshell32SecondTemplate,
                        'packaging_type_id' => $clamshell32oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => null,
                        'sku' => null,
                        'fill_weight_grams' => 70.00,
                        'price' => 3.50,
                        'is_default' => false,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                    [
                        'name' => 'Retail - Bulk',
                        'template_id' => $bulkTemplate,
                        'packaging_type_id' => null,
                        'pricing_type' => 'retail',
                        'pricing_unit' => 'per_lb',
                        'sku' => null,
                        'fill_weight_grams' => 453,
                        'price' => 18.00,
                        'is_default' => false,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                ],
            ],

            // Broccoli (Product ID: 4)
            [
                'product_name' => 'Broccoli',
                'variations' => [
                    [
                        'name' => 'Retail - Clamshell (24oz) - $5.00',
                        'template_id' => $retailClamshell24Template,
                        'packaging_type_id' => $clamshell24oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => null,
                        'sku' => null,
                        'fill_weight_grams' => 60,
                        'price' => 5.00,
                        'is_default' => true,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                    [
                        'name' => 'Wholesale - Clamshell (24oz) - $3.50',
                        'template_id' => $wholesaleClamshell24Template,
                        'packaging_type_id' => $clamshell24oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => null,
                        'sku' => null,
                        'fill_weight_grams' => 60,
                        'price' => 3.50,
                        'is_default' => false,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                ],
            ],

            // Rainbow Mix (Product ID: 7)
            [
                'product_name' => 'Rainbow Mix',
                'variations' => [
                    [
                        'name' => 'Retail - Clamshell (24oz) - $5.00',
                        'template_id' => $retailClamshell24Template,
                        'packaging_type_id' => $clamshell24oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => null,
                        'sku' => null,
                        'fill_weight_grams' => 70.00,
                        'price' => 5.00,
                        'is_default' => true,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                    [
                        'name' => 'Wholesale - Clamshell (24oz) - $3.50',
                        'template_id' => $wholesaleClamshell24Template,
                        'packaging_type_id' => $clamshell24oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => null,
                        'sku' => null,
                        'fill_weight_grams' => 70.00,
                        'price' => 3.50,
                        'is_default' => false,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                    [
                        'name' => 'Retail - Bulk',
                        'template_id' => $bulkTemplate,
                        'packaging_type_id' => null,
                        'pricing_type' => 'retail',
                        'pricing_unit' => 'per_lb',
                        'sku' => null,
                        'fill_weight_grams' => 453,
                        'price' => 33.00,
                        'is_default' => false,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                ],
            ],

            // Spicy Mix (Product ID: 8)
            [
                'product_name' => 'Spicy Mix',
                'variations' => [
                    [
                        'name' => 'Retail - Clamshell (24oz) - $5.00',
                        'template_id' => $retailClamshell24Template,
                        'packaging_type_id' => $clamshell24oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => null,
                        'sku' => null,
                        'fill_weight_grams' => 60,
                        'price' => 5.00,
                        'is_default' => true,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                    [
                        'name' => 'Wholesale - Clamshell (24oz) - $3.50',
                        'template_id' => $wholesaleClamshell24Template,
                        'packaging_type_id' => $clamshell24oz?->id,
                        'pricing_type' => 'retail',
                        'pricing_unit' => null,
                        'sku' => null,
                        'fill_weight_grams' => 60,
                        'price' => 3.50,
                        'is_default' => false,
                        'is_global' => false,
                        'is_active' => true,
                        'is_name_manual' => false,
                    ],
                ],
            ],
        ];

        // Create product-specific variations
        foreach ($productVariations as $productData) {
            $product = Product::where('name', $productData['product_name'])->first();

            if (! $product) {
                $this->command->warn("Product '{$productData['product_name']}' not found, skipping variations.");

                continue;
            }

            foreach ($productData['variations'] as $variationData) {
                $variationData['product_id'] = $product->id;
                PriceVariation::create($variationData);
            }

            $this->command->info('Created '.count($productData['variations'])." variations for {$product->name}");
        }
    }
}
