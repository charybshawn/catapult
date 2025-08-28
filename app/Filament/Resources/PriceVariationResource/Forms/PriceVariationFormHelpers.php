<?php

namespace App\Filament\Resources\PriceVariationResource\Forms;

use App\Models\PackagingType;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms;

/**
 * PriceVariation Form Helpers for Agricultural Pricing Business Logic
 * 
 * Provides business logic methods for agricultural product price variation forms,
 * including intelligent name generation, pricing calculations, and validation rules.
 * Contains agricultural-specific calculations for weight-based pricing and
 * packaging-aware naming conventions used throughout microgreens operations.
 * 
 * @business_logic Agricultural pricing calculations and naming patterns
 * @architectural_purpose Extracted helper methods to keep main form class under 300 lines
 * @calculation_focus Weight conversions, total price calculations, agricultural measurement standards
 * 
 * @agricultural_calculations Per-gram to per-package pricing conversions for microgreens
 * @naming_patterns Combines pricing types with packaging for readable variation names
 * @validation_rules Agricultural business rules for weight requirements and pricing contexts
 * 
 * @related_classes PriceVariationForm (main form), PriceVariationFormFields (field definitions)
 * @measurement_standards Metric and imperial weight conversions for agricultural products
 * @business_integration PackagingType model integration for agricultural container specifications
 */
class PriceVariationFormHelpers
{
    /**
     * Generate intelligent variation names for agricultural product pricing.
     * 
     * Creates readable variation names by combining pricing type and packaging information.
     * Respects manual overrides to prevent auto-generation from overwriting user inputs.
     * Essential for maintaining consistent naming across agricultural product variations.
     * 
     * @param int|null $packagingId PackagingType ID or null for package-free variations
     * @param string|null $pricingType retail, wholesale, bulk, special, or custom
     * @param callable $set Form state setter function
     * @param callable $get Form state getter function
     * @return void Updates form state with generated name
     * 
     * @agricultural_examples "Retail - Clamshell", "Wholesale - Bulk Container", "Package-Free - Bulk"
     * @business_logic Skips generation if user has manually overridden the name
     * @naming_pattern "[Pricing Type] - [Packaging Name]" or "[Pricing Type] - Package-Free"
     */
    public static function generateVariationName($packagingId, $pricingType, callable $set, callable $get): void
    {
        // Don't auto-generate if name is manually overridden
        if ($get('is_name_manual')) {
            return;
        }
        
        $parts = [];
        
        // 1. Add pricing type (capitalized)
        if ($pricingType) {
            $pricingTypeNames = [
                'retail' => 'Retail',
                'wholesale' => 'Wholesale',
                'bulk' => 'Bulk',
                'special' => 'Special',
                'custom' => 'Custom',
            ];
            $parts[] = $pricingTypeNames[$pricingType] ?? ucfirst($pricingType);
        }
        
        // 2. Add packaging information (without volume/size)
        if ($packagingId) {
            $packaging = PackagingType::find($packagingId);
            if ($packaging) {
                $parts[] = $packaging->name;
            }
        } else {
            // Handle package-free variations
            $parts[] = 'Package-Free';
        }
        
        // Join with " - " separator
        $generatedName = implode(' - ', $parts);
        if ($generatedName) {
            $set('name', $generatedName);
            $set('generated_name', $generatedName); // Store for comparison
        }
    }

    /**
     * Calculate total price helper text for weight-based agricultural pricing.
     * 
     * Computes and formats total package price when using per-weight pricing units.
     * Essential for agricultural products where per-gram pricing needs to show
     * total cost per package for customer understanding and business calculations.
     * 
     * @param Get $get Form state getter for accessing current field values
     * @return string|null Formatted total price text or null if not applicable
     * 
     * @agricultural_calculations Converts per-gram, per-kg, per-lb, per-oz to total package price
     * @business_context Helps customers understand total cost when buying by weight
     * @weight_conversions Handles metric (g, kg) and imperial (lb, oz) conversions for agriculture
     */
    public static function calculateTotalPriceHelperText(Get $get): ?string
    {
        $unit = $get('pricing_unit');
        if ($unit && $unit !== 'per_item') {
            $fillWeight = $get('fill_weight');
            if ($fillWeight && is_numeric($fillWeight)) {
                $price = $get('price');
                if ($price && is_numeric($price)) {
                    // Calculate total price based on unit
                    $total = match($unit) {
                        'per_g' => $price * $fillWeight,
                        'per_kg' => $price * ($fillWeight / 1000),
                        'per_lb' => $price * ($fillWeight / 453.592),
                        'per_oz' => $price * ($fillWeight / 28.35),
                        default => 0,
                    };
                    return 'Total price: $' . number_format($total, 2);
                }
            }
        }
        return null;
    }

    /**
     * Check if fill weight is required based on agricultural pricing context.
     * 
     * Determines weight requirement based on pricing type, units, and packaging selection.
     * Implements agricultural business rules where weight specifications are crucial
     * for inventory management and pricing calculations but optional for templates.
     * 
     * @param Get $get Form state getter for accessing current field values
     * @return bool True if fill weight is required for this agricultural pricing context
     * 
     * @business_rules Global templates don't require weight, per-gram package-free variations optional
     * @agricultural_context Weight critical for packaged products and non-per-gram pricing
     * @validation_logic Required for inventory management and pricing accuracy in agriculture
     */
    public static function isFillWeightRequired(Get $get): bool
    {
        // Not required for global templates
        if ($get('is_global')) {
            return false;
        }
        
        // Not required for per-gram pricing with no packaging
        $pricingUnit = $get('pricing_unit');
        $packagingId = $get('packaging_type_id');
        if ($pricingUnit === 'per_g' && !$packagingId) {
            return false;
        }
        
        // Required for all other cases
        return true;
    }
}