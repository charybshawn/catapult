<?php

namespace Tests\TestHelpers;

use App\Models\Product;
use App\Models\PriceVariation;
use App\Models\ProductMix;
use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;
use App\Models\Category;
use App\Models\PackagingType;
use Illuminate\Support\Collection;

class OrderSimulatorTestHelpers
{
    /**
     * Create a complete single variety product with price variations
     *
     * @param array $productData
     * @param array $varietyData
     * @param array $variations
     * @return array
     */
    public static function createSingleVarietyProduct(
        array $productData = [],
        array $varietyData = [],
        array $variations = []
    ): array {
        // Create category
        $category = Category::factory()->create([
            'name' => $productData['category_name'] ?? 'Test Category'
        ]);

        // Create variety first
        $varietyDefaults = [
            'common_name' => 'Test Variety',
            'category' => 'test',
            'is_active' => true,
        ];
        
        // Remove cultivar_name from varietyData since it's handled by the cultivar relationship
        $cultivarName = $varietyData['cultivar_name'] ?? null;
        $cleanVarietyData = array_diff_key($varietyData, ['cultivar_name' => '']);
        $variety = MasterSeedCatalog::create(array_merge($varietyDefaults, $cleanVarietyData));
        
        // Create cultivar if cultivar_name was provided
        if ($cultivarName) {
            MasterCultivar::create([
                'master_seed_catalog_id' => $variety->id,
                'cultivar_name' => $cultivarName,
                'is_active' => true,
            ]);
        }

        // Create product
        $productDefaults = [
            'name' => 'Test Product',
            'active' => true,
            'category_id' => $category->id,
            'master_seed_catalog_id' => $variety->id,
        ];
        $product = Product::create(array_merge($productDefaults, $productData));

        // Create price variations
        $createdVariations = [];
        if (empty($variations)) {
            // Create default variation
            $variations = [
                [
                    'name' => 'Default',
                    'price' => 10.00,
                    'fill_weight_grams' => 113.4,
                    'pricing_type' => 'retail',
                    'is_active' => true,
                    'is_default' => true,
                ]
            ];
        }

        foreach ($variations as $variationData) {
            $variationDefaults = [
                'product_id' => $product->id,
                'pricing_type' => 'retail',
                'is_active' => true,
            ];
            $createdVariations[] = PriceVariation::create(array_merge($variationDefaults, $variationData));
        }

        return [
            'category' => $category,
            'variety' => $variety,
            'product' => $product,
            'variations' => $createdVariations,
        ];
    }

    /**
     * Create a product mix with multiple varieties and price variations
     *
     * @param array $mixData
     * @param array $varieties
     * @param array $productData
     * @param array $variations
     * @return array
     */
    public static function createMixProduct(
        array $mixData = [],
        array $varieties = [],
        array $productData = [],
        array $variations = []
    ): array {
        // Create category
        $category = Category::factory()->create([
            'name' => $productData['category_name'] ?? 'Mix Category'
        ]);

        // Create varieties for the mix
        $createdVarieties = [];
        if (empty($varieties)) {
            // Create default varieties
            $varieties = [
                [
                    'data' => [
                        'common_name' => 'Mix Variety 1',
                        'cultivar_name' => 'First',
                        'category' => 'test',
                        'is_active' => true,
                    ],
                    'percentage' => 60
                ],
                [
                    'data' => [
                        'common_name' => 'Mix Variety 2',
                        'cultivar_name' => 'Second',
                        'category' => 'test',
                        'is_active' => true,
                    ],
                    'percentage' => 40
                ]
            ];
        }

        foreach ($varieties as $varietyInfo) {
            $varietyData = $varietyInfo['data'];
            $cultivarName = $varietyData['cultivar_name'] ?? null;
            
            // Remove cultivar_name from varietyData since it's handled by the cultivar relationship
            unset($varietyData['cultivar_name']);
            
            // Create the variety first
            $variety = MasterSeedCatalog::create($varietyData);
            
            // Create cultivar if cultivar_name was provided
            if ($cultivarName) {
                MasterCultivar::create([
                    'master_seed_catalog_id' => $variety->id,
                    'cultivar_name' => $cultivarName,
                    'is_active' => true,
                ]);
            }
            
            $createdVarieties[] = [
                'variety' => $variety,
                'percentage' => $varietyInfo['percentage']
            ];
        }

        // Create product mix
        $mixDefaults = [
            'name' => 'Test Mix',
            'description' => 'Test product mix',
            'is_active' => true,
        ];
        $productMix = ProductMix::create(array_merge($mixDefaults, $mixData));

        // Attach varieties to mix
        foreach ($createdVarieties as $varietyInfo) {
            $productMix->masterSeedCatalogs()->attach(
                $varietyInfo['variety']->id,
                ['percentage' => $varietyInfo['percentage']]
            );
        }

        // Create mix product
        $productDefaults = [
            'name' => 'Test Mix Product',
            'active' => true,
            'category_id' => $category->id,
            'product_mix_id' => $productMix->id,
        ];
        $product = Product::create(array_merge($productDefaults, $productData));

        // Create price variations
        $createdVariations = [];
        if (empty($variations)) {
            // Create default variation
            $variations = [
                [
                    'name' => 'Default Mix',
                    'price' => 15.00,
                    'fill_weight_grams' => 226.8,
                    'pricing_type' => 'retail',
                    'is_active' => true,
                ]
            ];
        }

        foreach ($variations as $variationData) {
            $variationDefaults = [
                'product_id' => $product->id,
                'pricing_type' => 'retail',
                'is_active' => true,
            ];
            $createdVariations[] = PriceVariation::create(array_merge($variationDefaults, $variationData));
        }

        return [
            'category' => $category,
            'varieties' => $createdVarieties,
            'product_mix' => $productMix,
            'product' => $product,
            'variations' => $createdVariations,
        ];
    }

    /**
     * Create standard bulk pricing variations for a product
     *
     * @param Product $product
     * @return array
     */
    public static function createBulkPricingVariations(Product $product): array
    {
        $variations = [
            [
                'name' => 'Bulk - 1lb',
                'price' => 20.00,
                'fill_weight_grams' => 453.6,
                'pricing_type' => 'bulk',
                'pricing_unit' => 'per_lb',
            ],
            [
                'name' => 'Bulk - 5lb',
                'price' => 90.00,
                'fill_weight_grams' => 2268.0,
                'pricing_type' => 'bulk',
                'pricing_unit' => 'per_lb',
            ],
            [
                'name' => 'Bulk - 10lb',
                'price' => 170.00,
                'fill_weight_grams' => 4536.0,
                'pricing_type' => 'bulk',
                'pricing_unit' => 'per_lb',
            ]
        ];

        $createdVariations = [];
        foreach ($variations as $variationData) {
            $variationData['product_id'] = $product->id;
            $variationData['is_active'] = true;
            $createdVariations[] = PriceVariation::create($variationData);
        }

        return $createdVariations;
    }

    /**
     * Create packaging types for testing
     *
     * @return array
     */
    public static function createPackagingTypes(): array
    {
        $types = [
            ['name' => '4oz Container', 'description' => '4 ounce container'],
            ['name' => '8oz Container', 'description' => '8 ounce container'],
            ['name' => '1lb Container', 'description' => '1 pound container'],
            ['name' => 'Live Tray', 'description' => 'Live plant tray'],
        ];

        $createdTypes = [];
        foreach ($types as $typeData) {
            $typeData['is_active'] = true;
            $createdTypes[] = PackagingType::create($typeData);
        }

        return $createdTypes;
    }

    /**
     * Generate order items for testing calculations
     *
     * @param array $productVariations Array of ['product' => Product, 'variation' => PriceVariation, 'quantity' => int]
     * @return array
     */
    public static function generateOrderItems(array $productVariations): array
    {
        $orderItems = [];
        
        foreach ($productVariations as $item) {
            $orderItems[] = [
                'product_id' => $item['product']->id,
                'price_variation_id' => $item['variation']->id,
                'quantity' => $item['quantity'],
            ];
        }

        return $orderItems;
    }

    /**
     * Assert variety totals match expected values
     *
     * @param array $results
     * @param array $expectedTotals Array of ['variety_id' => expected_grams]
     */
    public static function assertVarietyTotals(array $results, array $expectedTotals): void
    {
        $actualTotals = collect($results['variety_totals'])
            ->keyBy('variety_id')
            ->map(fn($variety) => $variety['total_grams'])
            ->toArray();

        foreach ($expectedTotals as $varietyId => $expectedGrams) {
            assert(
                isset($actualTotals[$varietyId]),
                "Expected variety {$varietyId} not found in results"
            );
            assert(
                abs($actualTotals[$varietyId] - $expectedGrams) < 0.01,
                "Expected variety {$varietyId} to have {$expectedGrams}g, got {$actualTotals[$varietyId]}g"
            );
        }
    }

    /**
     * Assert summary totals match expected values
     *
     * @param array $results
     * @param int $expectedVarieties
     * @param int $expectedItems
     * @param float $expectedGrams
     */
    public static function assertSummaryTotals(
        array $results,
        int $expectedVarieties,
        int $expectedItems,
        float $expectedGrams
    ): void {
        $summary = $results['summary'];
        
        assert(
            $summary['total_varieties'] === $expectedVarieties,
            "Expected {$expectedVarieties} varieties, got {$summary['total_varieties']}"
        );
        assert(
            $summary['total_items'] === $expectedItems,
            "Expected {$expectedItems} items, got {$summary['total_items']}"
        );
        assert(
            abs($summary['total_grams'] - $expectedGrams) < 0.01,
            "Expected {$expectedGrams}g total, got {$summary['total_grams']}g"
        );
    }

    /**
     * Create filtered price variations (excluding wholesale and live tray)
     *
     * @param Product $product
     * @return array
     */
    public static function createFilteredPriceVariations(Product $product): array
    {
        $variations = [
            // These should be visible
            [
                'name' => 'Retail Standard',
                'price' => 10.00,
                'fill_weight_grams' => 113.4,
                'pricing_type' => 'retail',
            ],
            [
                'name' => 'Bulk Container',
                'price' => 30.00,
                'fill_weight_grams' => 453.6,
                'pricing_type' => 'bulk',
            ],
            [
                'name' => 'Premium Pack',
                'price' => 15.00,
                'fill_weight_grams' => 226.8,
                'pricing_type' => 'retail',
            ],
        ];

        $filteredVariations = [
            // These should be filtered out
            [
                'name' => 'Wholesale Container',
                'price' => 8.00,
                'fill_weight_grams' => 113.4,
                'pricing_type' => 'wholesale',
            ],
            [
                'name' => 'Live Tray',
                'price' => 25.00,
                'fill_weight_grams' => null,
                'pricing_type' => 'retail',
            ],
            [
                'name' => 'Premium Wholesale',
                'price' => 12.00,
                'fill_weight_grams' => 226.8,
                'pricing_type' => 'retail',
            ],
        ];

        $createdVariations = [];
        $createdFilteredVariations = [];

        foreach ($variations as $variationData) {
            $variationData['product_id'] = $product->id;
            $variationData['is_active'] = true;
            $createdVariations[] = PriceVariation::create($variationData);
        }

        foreach ($filteredVariations as $variationData) {
            $variationData['product_id'] = $product->id;
            $variationData['is_active'] = true;
            $createdFilteredVariations[] = PriceVariation::create($variationData);
        }

        return [
            'visible_variations' => $createdVariations,
            'filtered_variations' => $createdFilteredVariations,
        ];
    }

    /**
     * Get weight conversion factors for different units
     *
     * @return array
     */
    public static function getWeightConversionFactors(): array
    {
        return [
            'g' => 1.0,
            'kg' => 1000.0,
            'lb' => 453.592,
            'oz' => 28.3495,
        ];
    }

    /**
     * Convert weight between units
     *
     * @param float $weight
     * @param string $fromUnit
     * @param string $toUnit
     * @return float
     */
    public static function convertWeight(float $weight, string $fromUnit, string $toUnit): float
    {
        $factors = self::getWeightConversionFactors();
        
        if (!isset($factors[$fromUnit]) || !isset($factors[$toUnit])) {
            throw new \InvalidArgumentException("Unsupported weight unit conversion: {$fromUnit} to {$toUnit}");
        }

        // Convert to grams first, then to target unit
        $grams = $weight * $factors[$fromUnit];
        return $grams / $factors[$toUnit];
    }

    /**
     * Generate realistic test data for comprehensive testing
     *
     * @return array
     */
    public static function generateRealisticTestScenario(): array
    {
        // Create single variety products
        $basilProduct = self::createSingleVarietyProduct(
            ['name' => 'Genovese Basil Seeds'],
            ['common_name' => 'Basil', 'cultivar_name' => 'Genovese'],
            [
                ['name' => 'Retail - 4oz', 'price' => 8.99, 'fill_weight_grams' => 113.4],
                ['name' => 'Bulk - 1lb', 'price' => 32.99, 'fill_weight_grams' => 453.6, 'pricing_type' => 'bulk'],
            ]
        );

        $tomatoProduct = self::createSingleVarietyProduct(
            ['name' => 'Cherry Tomato Seeds'],
            ['common_name' => 'Tomato', 'cultivar_name' => 'Cherry'],
            [
                ['name' => 'Retail - 4oz', 'price' => 12.99, 'fill_weight_grams' => 113.4],
                ['name' => 'Retail - 8oz', 'price' => 22.99, 'fill_weight_grams' => 226.8],
            ]
        );

        // Create mix product
        $saladMix = self::createMixProduct(
            ['name' => 'Premium Salad Mix'],
            [
                [
                    'data' => ['common_name' => 'Lettuce', 'cultivar_name' => 'Buttercrunch', 'category' => 'lettuce', 'is_active' => true],
                    'percentage' => 40
                ],
                [
                    'data' => ['common_name' => 'Lettuce', 'cultivar_name' => 'Red Oak Leaf', 'category' => 'lettuce', 'is_active' => true],
                    'percentage' => 35
                ],
                [
                    'data' => ['common_name' => 'Arugula', 'cultivar_name' => 'Rocket', 'category' => 'arugula', 'is_active' => true],
                    'percentage' => 25
                ]
            ],
            ['name' => 'Premium Salad Mix Seeds'],
            [
                ['name' => 'Mix - 8oz', 'price' => 18.99, 'fill_weight_grams' => 226.8],
                ['name' => 'Mix Bulk - 2lb', 'price' => 65.99, 'fill_weight_grams' => 907.2, 'pricing_type' => 'bulk'],
            ]
        );

        return [
            'basil' => $basilProduct,
            'tomato' => $tomatoProduct,
            'salad_mix' => $saladMix,
        ];
    }
}