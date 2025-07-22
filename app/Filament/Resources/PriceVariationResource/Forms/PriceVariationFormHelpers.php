<?php

namespace App\Filament\Resources\PriceVariationResource\Forms;

use Filament\Forms;

/**
 * PriceVariation Form Helpers
 * Extracted helper methods to keep main form class under 300 lines
 * Contains business logic and calculation methods
 */
class PriceVariationFormHelpers
{
    /**
     * Generate variation name in format: "Pricing Type - Packaging"
     * Example: "Retail - Clamshell"
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
            $packaging = \App\Models\PackagingType::find($packagingId);
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
     * Calculate total price helper text
     */
    public static function calculateTotalPriceHelperText(Forms\Get $get): ?string
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
     * Check if fill weight is required
     */
    public static function isFillWeightRequired(Forms\Get $get): bool
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