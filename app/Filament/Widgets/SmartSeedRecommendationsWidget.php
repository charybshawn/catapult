<?php

namespace App\Filament\Widgets;

use App\Models\SeedVariation;
use App\Models\SeedEntry;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

/**
 * Intelligent seed recommendation engine widget for optimized agricultural procurement.
 *
 * Provides AI-driven seed variety recommendations based on pricing efficiency,
 * package size optimization, and agricultural production requirements. Features
 * advanced filtering by common name, cultivar selection, seed size categories,
 * and multi-currency pricing analysis with intelligent scoring algorithms.
 *
 * @filament_widget Custom widget with AI-powered recommendation engine
 * @business_domain Agricultural seed procurement optimization and cost analysis
 * @intelligence_features Price efficiency scoring, optimal weight calculation, cultivar comparison
 * @dashboard_position Sort order 0 (first), full width for prominent recommendation display
 * @procurement_ai Advanced algorithms for agricultural purchasing decision support
 */
class SmartSeedRecommendationsWidget extends Widget
{
    /** @var string Blade view template for smart recommendations display */
    protected string $view = 'filament.widgets.smart-seed-recommendations';
    
    /** @var string Widget column span for full-width recommendation display */
    protected int | string | array $columnSpan = 'full';
    
    /** @var int Widget sort order for prominent dashboard positioning */
    protected static ?int $sort = 0;
    
    /** @var array Livewire event listeners for dynamic filter updates */
    public $listeners = ['filtersUpdated' => 'updateFilters'];
    
    /** @var string|null Selected common name for recommendation filtering */
    public $selectedCommonName = null;
    
    /** @var array Selected cultivar names for targeted recommendations */
    public $selectedCultivars = [];
    
    /** @var string|null Selected seed size category for optimal packaging */
    public $selectedSeedSize = null;
    
    /** @var string Display currency preference (CAD or USD) */
    public $displayCurrency = 'CAD';
    
    /**
     * Prepare comprehensive recommendation data for widget display.
     *
     * Aggregates smart recommendations with current filter context and
     * currency preferences to provide complete data package for the
     * Blade template rendering and user interaction.
     *
     * @return array Complete dataset for smart recommendation widget display
     * @business_context Includes recommendations with filter states and currency
     * @intelligence_data AI-powered recommendations with scoring and reasoning
     */
    public function getViewData(): array
    {
        $recommendations = $this->getTopRecommendations($this->selectedCommonName, $this->selectedCultivars, $this->selectedSeedSize);
        
        return [
            'recommendations' => $recommendations,
            'selectedCommonName' => $this->selectedCommonName,
            'selectedCultivars' => $this->selectedCultivars,
            'selectedSeedSize' => $this->selectedSeedSize,
            'displayCurrency' => $this->displayCurrency,
        ];
    }
    
    /**
     * Update recommendation filters via Livewire event for dynamic analysis.
     *
     * Responds to filter changes from user interactions or parent components
     * to update recommendation parameters and trigger fresh analysis with
     * new criteria for agricultural seed procurement optimization.
     *
     * @param string|null $selectedCommonName Updated common name filter
     * @param array $selectedCultivars Updated cultivar selection array
     * @param string|null $selectedSeedSize Updated seed size category
     */
    public function updateFilters($selectedCommonName = null, $selectedCultivars = [], $selectedSeedSize = null)
    {
        $this->selectedCommonName = $selectedCommonName;
        $this->selectedCultivars = $selectedCultivars ?? [];
        $this->selectedSeedSize = $selectedSeedSize;
    }
    
    /**
     * Retrieve top seed recommendations based on current filter criteria.
     *
     * Delegates to the smart recommendation engine with current filtering
     * parameters to generate prioritized seed variety suggestions for
     * agricultural procurement decision-making.
     *
     * @param string|null $selectedCommonName Common name filter for recommendations
     * @param array $selectedCultivars Cultivar names for targeted suggestions
     * @param string|null $selectedSeedSize Seed size category for package optimization
     * @return Collection Prioritized seed recommendations with intelligence scores
     */
    protected function getTopRecommendations(?string $selectedCommonName = null, array $selectedCultivars = [], ?string $selectedSeedSize = null): Collection
    {
        $recommendations = $this->getSmartRecommendations($selectedCommonName, $selectedCultivars, $selectedSeedSize);
        
        // Return all recommendations for the widget to handle categorization
        return $recommendations;
    }
    
    /**
     * Generate intelligent seed recommendations using advanced scoring algorithms.
     *
     * Implements sophisticated recommendation engine that analyzes in-stock seed
     * variations with price efficiency scoring, optimal weight calculations,
     * and agricultural suitability assessments. Provides ranked recommendations
     * with detailed reasoning for procurement decision support.
     *
     * @param string|null $selectedCommonName Common name filter for variety selection
     * @param array $selectedCultivars Specific cultivars for targeted analysis
     * @param string|null $selectedSeedSize Seed size category for package optimization
     * @return Collection Scored and ranked seed recommendations with intelligence metrics
     * @business_logic Combines price efficiency (60%) and weight optimization (40%)
     * @agricultural_intelligence Accounts for variety-specific package size preferences
     */
    protected function getSmartRecommendations(?string $selectedCommonName = null, array $selectedCultivars = [], ?string $selectedSeedSize = null): Collection
    {
        // Build query for in-stock seed variations
        $query = SeedVariation::query()
            ->with(['seedEntry.supplier'])
            ->where('is_in_stock', true)
            ->whereHas('seedEntry', function($q) use ($selectedCommonName, $selectedCultivars) {
                $q->whereNotNull('common_name')
                  ->where(DB::raw('CHAR_LENGTH(TRIM(common_name))'), '>', 0);
                  
                // Filter by selected common name if provided
                if ($selectedCommonName) {
                    $q->where('common_name', $selectedCommonName);
                    
                    // Filter by selected cultivars if provided
                    if (!empty($selectedCultivars)) {
                        $q->whereIn('cultivar_name', $selectedCultivars);
                    }
                }
            })
            ->where('weight_kg', '>', 0);

        $allVariations = $query->get()->map(function ($variation) use ($selectedSeedSize) {
            return $this->enrichVariationData($variation, $selectedSeedSize);
        });

        if ($allVariations->isEmpty()) {
            return collect();
        }

        $recommendations = collect();
        
        if ($selectedCommonName) {
            // If a specific common name is selected, return all varieties with scores
            $varieties = $allVariations;
            
            // Score all varieties and return them all for the template to categorize
            $scoredVarieties = $varieties->map(function ($variation) use ($varieties) {
                // Calculate a simple overall score for ranking
                $priceScore = $this->calculatePriceEfficiencyScore($variation, $varieties);
                $weightScore = $this->calculateOptimalWeightScore($variation);
                
                // For seed size filtering, weight appropriateness should be much more important
                // If weight score is very low (inappropriate size), heavily penalize
                if ($weightScore < 60) {
                    $variation->intelligence_score = round($weightScore * 0.8 + $priceScore * 0.2);
                } else {
                    $variation->intelligence_score = round(($priceScore * 0.6) + ($weightScore * 0.4));
                }
                
                $variation->reasoning = "Price efficiency: {$priceScore}%, Weight preference: {$weightScore}%";
                
                return $variation;
            });
            
            return $scoredVarieties->sortByDesc('intelligence_score');
        } else {
            // If no specific common name selected, return empty recommendations
            return collect();
        }
        
        return $recommendations->sortByDesc('intelligence_score');
    }
    
    /**
     * Enrich seed variation data with calculated metrics and display formatting.
     *
     * Augments raw seed variation data with derived pricing calculations,
     * supplier information, currency conversions, and display-ready formatting
     * for comprehensive recommendation presentation in agricultural procurement.
     *
     * @param \App\Models\SeedVariation $variation Seed variation to enrich
     * @param string|null $selectedSeedSize Seed size category for optimization context
     * @return \App\Models\SeedVariation Enhanced variation with calculated metrics
     * @business_calculations Price per kg, price per gram, currency conversions
     * @display_formatting Supplier names, currency symbols, contextual data
     */
    protected function enrichVariationData($variation, ?string $selectedSeedSize = null)
    {
        // Calculate metrics using converted prices for consistent comparison
        $displayPrice = $this->displayCurrency === 'CAD' ? $variation->price_in_cad : $variation->price_in_usd;
        $pricePerKg = $displayPrice / $variation->weight_kg;
        $pricePerGram = $pricePerKg / 1000;
        
        // Add calculated fields
        $variation->common_name = $variation->seedEntry->common_name;
        $variation->cultivar_name = $variation->seedEntry->cultivar_name;
        $variation->supplier_name = $variation->seedEntry->supplier->name ?? 'Unknown';
        $variation->supplier_product_url = $variation->seedEntry->supplier_product_url;
        $variation->display_price = $displayPrice;
        $variation->price_per_kg = $pricePerKg;
        $variation->price_per_gram = $pricePerGram;
        $variation->display_currency = $this->displayCurrency;
        $variation->currency_symbol = $this->displayCurrency === 'CAD' ? 'CDN$' : 'USD$';
        
        // Store the selected seed size for scoring
        $variation->selected_seed_size = $selectedSeedSize;
        
        return $variation;
    }
    
    /**
     * Calculate price efficiency score for seed variation recommendation ranking.
     *
     * Evaluates price competitiveness by comparing price per kg against the
     * full range of available options within the same variety group. Higher
     * scores indicate better value for agricultural procurement decisions.
     *
     * @param \App\Models\SeedVariation $variation Variation to score for price efficiency
     * @param \Illuminate\Support\Collection $varieties All variations for comparison
     * @return int Price efficiency score (0-100) with 100 being most cost-effective
     * @scoring_algorithm Normalized position within min-max price range
     * @procurement_value Higher scores indicate better agricultural investment value
     */
    protected function calculatePriceEfficiencyScore($variation, $varieties): int
    {
        if (!$varieties || $varieties->isEmpty()) {
            return 50; // Default score if no comparison data
        }
        
        $pricePerKg = $variation->price_per_kg;
        $minPrice = $varieties->min('price_per_kg');
        $maxPrice = $varieties->max('price_per_kg');
        
        if (!$minPrice || !$maxPrice || ($maxPrice - $minPrice) == 0) {
            return 100;
        }
        
        return round((1 - (($pricePerKg - $minPrice) / ($maxPrice - $minPrice))) * 100);
    }
    
    /**
     * Calculate optimal weight score based on seed size category and agricultural usage.
     *
     * Evaluates package size appropriateness using sophisticated weight-based
     * scoring that considers agricultural production requirements, storage efficiency,
     * and economic order quantities for different seed size categories in
     * microgreens operations.
     *
     * @param \App\Models\SeedVariation $variation Variation to evaluate for weight optimization
     * @return int Weight optimization score (0-100) with 100 being ideal package size
     * @agricultural_logic Different optimal ranges for x-small, small, medium, large categories
     * @scoring_categories X-small (0.2-0.5kg), Small (2-4kg), Medium (6-9kg), Large (22-28kg)
     * @business_context Balances storage costs, waste minimization, and procurement efficiency
     */
    protected function calculateOptimalWeightScore($variation): int
    {
        $weightKg = $variation->weight_kg;
        $commonName = strtolower($variation->common_name ?? '');
        
        // Use user-selected seed size
        $seedSize = $variation->selected_seed_size;
        
        // Determine ideal weight based on seed size category
        switch ($seedSize) {
            case 'x-small':
                // X-Small seeds: 0-500g sweet spot (0.25kg optimal)
                if ($weightKg >= 0.2 && $weightKg <= 0.5) {
                    return 100;
                } elseif ($weightKg >= 0.1 && $weightKg <= 0.8) {
                    return 85;
                } elseif ($weightKg >= 0.05 && $weightKg <= 1) {
                    return 70;
                } elseif ($weightKg >= 1 && $weightKg <= 2) {
                    return 30; // Penalize moderately large sizes
                } elseif ($weightKg >= 2 && $weightKg <= 5) {
                    return 20; // Heavily penalize large sizes
                } else {
                    return 10; // Very heavily penalize massive sizes (>5kg)
                }
                
            case 'small':
                // Small seeds: 1-5kg sweet spot (3kg optimal)
                if ($weightKg >= 2 && $weightKg <= 4) {
                    return 100;
                } elseif ($weightKg >= 1 && $weightKg <= 5) {
                    return 85;
                } elseif ($weightKg >= 0.5 && $weightKg <= 8) {
                    return 70;
                } else {
                    return 50;
                }
                
            case 'medium':
                // Medium seeds: 5-10kg sweet spot (7.5kg optimal)
                if ($weightKg >= 6 && $weightKg <= 9) {
                    return 100;
                } elseif ($weightKg >= 5 && $weightKg <= 10) {
                    return 85;
                } elseif ($weightKg >= 3 && $weightKg <= 15) {
                    return 70;
                } else {
                    return 50;
                }
                
            case 'large':
                // Large seeds: 25kg sweet spot
                if ($weightKg >= 22 && $weightKg <= 28) {
                    return 100;
                } elseif ($weightKg >= 20 && $weightKg <= 30) {
                    return 85;
                } elseif ($weightKg >= 15 && $weightKg <= 40) {
                    return 70;
                } else {
                    return 50;
                }
                
            default:
                // No seed size selected - return neutral score
                return 70;
        }
    }
}