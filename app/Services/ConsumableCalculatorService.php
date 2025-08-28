<?php

namespace App\Services;

use App\Models\Consumable;

/**
 * Agricultural consumables calculation and inventory management service.
 * 
 * Provides comprehensive calculations for agricultural consumables including
 * seeds, soil, packaging, and growing supplies. Manages stock levels, usage
 * patterns, reorder calculations, and cost analysis for microgreens production
 * operations. Essential for maintaining adequate supply levels and optimizing
 * agricultural resource allocation.
 * 
 * @business_domain Agricultural consumables and supply chain management
 * @agricultural_supplies Seeds, soil amendments, packaging, and growing materials
 * @inventory_management Stock tracking, usage analysis, and reorder automation
 * @cost_optimization Unit cost calculations and supplier cost comparisons
 * 
 * @example
 * $calculator = new ConsumableCalculatorService();
 * $availableStock = $calculator->calculateAvailableStock($seedConsumable);
 * $reorderSuggestion = $calculator->calculateReorderSuggestion($soilConsumable);
 * 
 * @features
 * - Multi-unit measurement support (grams, pounds, liters, pieces)
 * - Usage rate analysis and consumption forecasting
 * - Automated reorder threshold calculations
 * - Cost per unit standardization for supplier comparison
 * - Stock availability and depletion timeline prediction
 * 
 * @see Consumable For consumable model and relationships
 * @see InventoryManagementService For broader inventory operations
 * @see CropPlanningService For agricultural resource planning
 */
class ConsumableCalculatorService
{
    /**
     * Standardized measurement units for agricultural consumables.
     * 
     * Defines supported units for quantifying agricultural supplies including
     * weight measurements for seeds and soil, volume measurements for liquids,
     * and standardized cooking measurements for nutrient solutions.
     * 
     * @agricultural_standards Common units used in microgreens production
     * @weight_volume Covers both dry goods (seeds/soil) and liquid nutrients
     * @measurement_consistency Ensures uniform quantification across system
     * 
     * @var array<string, string> Unit code to display name mapping
     */
    private const MEASUREMENT_UNITS = [
        'g' => 'Grams',              // Primary weight unit for seeds and small quantities
        'kg' => 'Kilograms',         // Bulk weight unit for soil and large supplies
        'oz' => 'Ounces',            // Imperial weight for US suppliers
        'lb' => 'Pounds',            // Common packaging unit for agricultural supplies
        'ml' => 'Milliliters',       // Liquid nutrients and amendments
        'L' => 'Liters',             // Bulk liquid supplies
        'tsp' => 'Teaspoons',        // Small liquid measurements
        'tbsp' => 'Tablespoons',     // Medium liquid measurements
        'cup' => 'Cups'              // Standard kitchen measurement for solutions
    ];

    /**
     * Agricultural consumable categories for microgreens production.
     * 
     * Categorizes consumables by their role in the agricultural production
     * process. Each type has different usage patterns, storage requirements,
     * and reorder considerations in microgreens operations.
     * 
     * @agricultural_categories Groups supplies by production function
     * @inventory_classification Enables type-specific management policies
     * @production_workflow Maps to different stages of agricultural process
     * 
     * @var array<string, string> Type code to display name mapping
     */
    private const TYPES = [
        'packaging' => 'Packaging',   // Containers, trays, clamshells for product packaging
        'soil' => 'Soil',           // Growing media, substrates, and soil amendments
        'seed' => 'Seed',           // Seeds and growing varieties for crop production
        'label' => 'Label',         // Product labeling and identification materials
        'other' => 'Other'          // Miscellaneous agricultural supplies and tools
    ];

    /**
     * Agricultural consumable packaging and counting units.
     * 
     * Defines discrete unit types for consumables that are counted rather
     * than measured by weight or volume. Essential for packaging materials,
     * containers, and other countable agricultural supplies.
     * 
     * @packaging_units Standard units for agricultural packaging and containers
     * @discrete_counting Units for countable rather than measurable supplies
     * @inventory_tracking Supports piece-based inventory management
     * 
     * @var array<string, string> Unit type code to display name mapping
     */
    private const UNIT_TYPES = [
        'pieces' => 'Pieces',        // Individual countable items
        'rolls' => 'Rolls',          // Labels, plastic wrap, packaging materials
        'bags' => 'Bags',            // Soil bags, seed packages, bulk supplies
        'containers' => 'Containers', // Trays, pots, growing containers
        'packages' => 'Packages',    // Bundled supplies and multi-item packages
        'bottles' => 'Bottles',      // Liquid nutrients, amendments, solutions
        'sheets' => 'Sheets'         // Labels, instruction sheets, documentation
    ];

    /**
     * Calculate current available stock for agricultural consumable.
     * 
     * Determines remaining quantity available for use by subtracting consumed
     * quantity from initial stock. Ensures stock levels never go negative to
     * prevent invalid inventory states in agricultural operations.
     * 
     * @stock_calculation Basic inventory arithmetic with safety constraints
     * @agricultural_tracking Maintains accurate supply availability
     * @inventory_integrity Prevents negative stock situations
     * 
     * @param Consumable $consumable Agricultural consumable to calculate stock for
     * @return float Available quantity in consumable's native units
     * 
     * @example
     * $seedStock = $this->calculateAvailableStock($arugulaSeed);
     * if ($seedStock < 100) {
     *     // Need to reorder arugula seeds
     * }
     */
    public function calculateAvailableStock(Consumable $consumable): float
    {
        return max(0, $consumable->initial_stock - $consumable->consumed_quantity);
    }

    /**
     * Calculate total usable quantity from available stock units.
     * 
     * Converts available stock units (containers, bags, packages) into total
     * usable quantity by multiplying by quantity per unit. Essential for
     * understanding actual agricultural supply availability when supplies
     * are packaged in bulk containers.
     * 
     * @quantity_conversion Converts packaging units to usable quantities
     * @agricultural_planning Determines actual material available for production
     * @bulk_packaging Handles supplies sold in multi-unit packages
     * 
     * @param Consumable $consumable Agricultural consumable with packaging information
     * @return float Total usable quantity in base measurement units
     * 
     * @example
     * // Soil sold in 50lb bags, have 3 bags available
     * $totalSoilPounds = $this->calculateTotalQuantity($soilConsumable);
     * // Returns: 3 bags × 50lb/bag = 150lb total soil
     */
    public function calculateTotalQuantity(Consumable $consumable): float
    {
        $availableStock = $this->calculateAvailableStock($consumable);
        
        if (!$consumable->quantity_per_unit) {
            return $availableStock;
        }

        return $availableStock * $consumable->quantity_per_unit;
    }

    /**
     * Calculate standardized cost per gram for agricultural supply comparison.
     * 
     * Converts varied packaging costs to standardized cost per gram enabling
     * direct comparison between suppliers and package sizes. Critical for
     * agricultural cost optimization and supplier evaluation decisions.
     * 
     * @cost_standardization Enables apples-to-apples supplier comparisons
     * @agricultural_economics Supports cost-effective supply procurement
     * @supplier_analysis Facilitates vendor cost evaluation
     * @weight_conversion Standardizes pricing across different package sizes
     * 
     * @param Consumable $consumable Agricultural consumable with cost and quantity data
     * @return float|null Cost per gram in currency units or null if cannot calculate
     * 
     * @example
     * $seedCostPerGram = $this->calculateCostPerGram($seedConsumable);
     * $soilCostPerGram = $this->calculateCostPerGram($soilConsumable);
     * // Compare costs: $0.12/g vs $0.08/g for supplier decision
     */
    public function calculateCostPerGram(Consumable $consumable): ?float
    {
        if (!$consumable->cost_per_unit || !$consumable->quantity_per_unit) {
            return null;
        }

        // Convert to cost per gram for standardized comparison
        $quantityInGrams = $this->convertToGrams($consumable->quantity_per_unit, $consumable->quantity_unit);
        
        if (!$quantityInGrams) {
            return null;
        }

        return $consumable->cost_per_unit / $quantityInGrams;
    }

    /**
     * Calculate daily agricultural consumable usage rate from consumption history.
     * 
     * Determines average daily consumption rate based on total consumed quantity
     * and time period of use. Essential for forecasting supply needs and
     * optimizing agricultural inventory management and reorder timing.
     * 
     * @usage_analytics Analyzes historical consumption patterns
     * @agricultural_forecasting Predicts future supply requirements
     * @inventory_planning Supports automated reorder calculations
     * @consumption_tracking Monitors agricultural resource utilization rates
     * 
     * @param Consumable $consumable Agricultural consumable to analyze
     * @param int $days Analysis period for usage calculation (default 30 days)
     * @return float Average daily consumption rate in consumable units
     * 
     * @example
     * $dailySeedUsage = $this->calculateUsageRate($seedConsumable, 30);
     * // Returns: 15.3g/day average seed consumption
     * 
     * @future_enhancement Integrate with activity logs for more accurate tracking
     */
    public function calculateUsageRate(Consumable $consumable, int $days = 30): float
    {
        // Calculate usage rate based on consumption history
        // TODO: Integrate with activity log for more precise tracking
        if ($consumable->consumed_quantity <= 0) {
            return 0; // No consumption recorded yet
        }

        // Estimate daily usage from total consumption over active period
        $daysActive = max(1, $consumable->created_at->diffInDays(now()));
        return $consumable->consumed_quantity / $daysActive;
    }

    /**
     * Calculate estimated days until agricultural supply restock required.
     * 
     * Predicts timeline until reorder threshold is reached based on current
     * stock levels and historical usage patterns. Critical for preventing
     * agricultural production disruptions due to supply shortages.
     * 
     * @supply_forecasting Predicts future inventory depletion timeline
     * @agricultural_planning Prevents production disruptions from stockouts
     * @reorder_automation Supports automated procurement scheduling
     * @business_continuity Ensures uninterrupted agricultural operations
     * 
     * @param Consumable $consumable Agricultural consumable to analyze
     * @return int|null Days until restock needed or null if cannot calculate
     * 
     * @calculation_logic
     * Days = (Current Stock - Restock Threshold) ÷ Daily Usage Rate
     * 
     * @example
     * $daysUntilReorder = $this->calculateDaysUntilRestock($soilConsumable);
     * if ($daysUntilReorder <= 7) {
     *     // Alert: Need to reorder soil within a week
     * }
     */
    public function calculateDaysUntilRestock(Consumable $consumable): ?int
    {
        $availableStock = $this->calculateAvailableStock($consumable);
        $usageRate = $this->calculateUsageRate($consumable);

        if ($usageRate <= 0) {
            return null; // No usage pattern to calculate from
        }

        $stockUntilReorder = $availableStock - $consumable->restock_threshold;
        
        if ($stockUntilReorder <= 0) {
            return 0; // Already needs restock
        }

        return (int) ceil($stockUntilReorder / $usageRate);
    }

    /**
     * Retrieve all supported measurement units for agricultural consumables.
     * 
     * Returns complete list of valid measurement units for consumable quantification
     * including weights, volumes, and cooking measurements. Used for form validation,
     * dropdown population, and unit conversion operations.
     * 
     * @unit_definitions Provides complete list of supported measurement units
     * @form_support Enables dropdown population for user interfaces
     * @validation_reference Used for input validation and data integrity
     * 
     * @return array<string, string> Unit code to display name mapping
     */
    public function getValidMeasurementUnits(): array
    {
        return self::MEASUREMENT_UNITS;
    }

    /**
     * Retrieve all supported agricultural consumable categories.
     * 
     * Returns complete list of consumable types used in microgreens production
     * for categorization, filtering, and inventory management. Each type has
     * specific characteristics affecting storage, usage, and reorder policies.
     * 
     * @agricultural_categories Complete list of production supply categories
     * @inventory_classification Enables type-based filtering and reporting
     * @form_support Populates category selection in user interfaces
     * 
     * @return array<string, string> Type code to display name mapping
     */
    public function getValidTypes(): array
    {
        return self::TYPES;
    }

    /**
     * Retrieve all supported unit types for discrete agricultural consumables.
     * 
     * Returns complete list of countable unit types for packaging and discrete
     * consumables that cannot be measured by weight or volume. Used for
     * inventory management of containers, packaging, and countable supplies.
     * 
     * @discrete_units Complete list of countable unit types
     * @packaging_management Supports container and packaging inventory
     * @form_support Enables unit type selection in user interfaces
     * 
     * @return array<string, string> Unit type code to display name mapping
     */
    public function getValidUnitTypes(): array
    {
        return self::UNIT_TYPES;
    }

    /**
     * Convert agricultural consumable quantities to standardized gram units.
     * 
     * Converts various weight and volume units to grams for standardized
     * calculations and comparisons. Essential for cost per gram calculations
     * and cross-supplier analysis. Handles common agricultural measurements.
     * 
     * @unit_standardization Converts diverse units to common gram standard
     * @agricultural_conversion Handles typical farm supply measurements
     * @cost_comparison Enables standardized cost per gram calculations
     * @internal Utility method for calculation standardization
     * 
     * @param float $quantity Amount in original units
     * @param string|null $unit Original unit code for conversion
     * @return float|null Quantity in grams or null if unit not convertible
     * 
     * @conversion_factors
     * - g: 1.0 (base unit)
     * - kg: 1000.0
     * - oz: 28.3495
     * - lb: 453.592
     * - ml: 1.0 (approximated for calculation)
     * - L: 1000.0
     */
    private function convertToGrams(float $quantity, ?string $unit): ?float
    {
        if (!$unit) {
            return null;
        }

        return match ($unit) {
            'g' => $quantity,
            'kg' => $quantity * 1000,
            'oz' => $quantity * 28.3495,
            'lb' => $quantity * 453.592,
            'ml' => $quantity,        // Approximate 1ml ≈ 1g for liquid density
            'L' => $quantity * 1000,  // Convert liters to grams
            default => null           // Cannot convert volume/discrete units to weight
        };
    }

    /**
     * Generate formatted display name for agricultural consumable.
     * 
     * Creates user-friendly display name for consumables with special handling
     * for seed varieties and other agricultural supplies. Maintains consistency
     * in user interfaces and reports while accommodating variety-specific naming.
     * 
     * @display_formatting Creates consistent user-facing consumable names
     * @agricultural_naming Handles seed varieties and agricultural supply naming
     * @ui_support Provides formatted names for interfaces and reports
     * 
     * @param Consumable $consumable Agricultural consumable to format name for
     * @return string Formatted display name for user interfaces
     * 
     * @example
     * $displayName = $this->formatDisplayName($seedConsumable);
     * // Returns: "Arugula Seeds - Astro Variety" or simplified name
     */
    public function formatDisplayName(Consumable $consumable): string
    {
        if ($consumable->consumableType && $consumable->consumableType->isSeed()) {
            // Seed consumables: use base name (cultivar info in master catalog)
            return $consumable->name;
        }
        
        // All other agricultural consumables use standard name
        return $consumable->name;
    }

    /**
     * Generate comprehensive reorder recommendation for agricultural consumable.
     * 
     * Analyzes current stock, usage patterns, and consumption forecasts to
     * provide actionable reorder recommendations. Includes urgency assessment,
     * suggested quantities, and timeline projections for agricultural supply
     * chain management and procurement planning.
     * 
     * @reorder_intelligence Comprehensive analysis for procurement decisions
     * @agricultural_planning Prevents supply disruptions in production
     * @usage_forecasting Projects future needs based on consumption patterns
     * @procurement_automation Supports automated purchasing workflows
     * 
     * @param Consumable $consumable Agricultural consumable to analyze
     * @return array Comprehensive reorder recommendation with analytics
     * 
     * @recommendation_structure
     * [
     *   'needs_reorder' => bool,           // Immediate reorder required
     *   'current_stock' => float,          // Available quantity
     *   'restock_threshold' => float,      // Minimum stock level
     *   'suggested_quantity' => float,     // Recommended order quantity
     *   'usage_rate_per_day' => float,     // Daily consumption rate
     *   'days_until_restock' => int|null,  // Timeline until reorder needed
     *   'urgency' => string                // Priority level (critical/high/medium/low)
     * ]
     * 
     * @example
     * $suggestion = $this->calculateReorderSuggestion($soilConsumable);
     * if ($suggestion['urgency'] === 'critical') {
     *     // Immediate procurement action required
     *     $this->createEmergencyPurchaseOrder($soilConsumable);
     * }
     */
    public function calculateReorderSuggestion(Consumable $consumable): array
    {
        $availableStock = $this->calculateAvailableStock($consumable);
        $usageRate = $this->calculateUsageRate($consumable);
        $daysUntilRestock = $this->calculateDaysUntilRestock($consumable);

        $suggestion = [
            'needs_reorder' => $availableStock <= $consumable->restock_threshold,
            'current_stock' => $availableStock,
            'restock_threshold' => $consumable->restock_threshold,
            'suggested_quantity' => $consumable->restock_quantity,
            'usage_rate_per_day' => $usageRate,
            'days_until_restock' => $daysUntilRestock,
            'urgency' => $this->calculateUrgency($daysUntilRestock)
        ];

        return $suggestion;
    }

    /**
     * Determine reorder urgency level from supply depletion timeline.
     * 
     * Assigns urgency classification based on days until restock threshold
     * is reached. Enables prioritization of procurement activities and
     * escalation of critical supply shortages in agricultural operations.
     * 
     * @urgency_classification Categorizes reorder priority for operational response
     * @supply_management Enables escalation of critical shortages
     * @procurement_priority Helps prioritize purchasing activities
     * @internal Supporting logic for reorder recommendation system
     * 
     * @param int|null $daysUntilRestock Days until restock needed or null
     * @return string Urgency level classification
     * 
     * @urgency_levels
     * - 'critical': 0 days or less (immediate action required)
     * - 'high': 1-7 days (urgent procurement needed)
     * - 'medium': 8-30 days (plan for reorder)
     * - 'low': 31+ days (routine monitoring)
     * - 'unknown': Cannot calculate (insufficient data)
     */
    private function calculateUrgency(?int $daysUntilRestock): string
    {
        if ($daysUntilRestock === null) {
            return 'unknown';
        }

        if ($daysUntilRestock <= 0) {
            return 'critical';
        }

        if ($daysUntilRestock <= 7) {
            return 'high';
        }

        if ($daysUntilRestock <= 30) {
            return 'medium';
        }

        return 'low';
    }
}