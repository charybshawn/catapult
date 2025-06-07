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

class SmartSeedRecommendationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.smart-seed-recommendations';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 0; // Show first
    
    public $listeners = ['filtersUpdated' => 'updateFilters'];
    
    public $selectedCommonName = null;
    public $selectedCultivars = [];
    public $selectedSeedSize = null;
    
    public function getViewData(): array
    {
        $recommendations = $this->getTopRecommendations($this->selectedCommonName, $this->selectedCultivars, $this->selectedSeedSize);
        
        return [
            'recommendations' => $recommendations,
            'selectedCommonName' => $this->selectedCommonName,
            'selectedCultivars' => $this->selectedCultivars,
            'selectedSeedSize' => $this->selectedSeedSize,
        ];
    }
    
    public function updateFilters($selectedCommonName = null, $selectedCultivars = [], $selectedSeedSize = null)
    {
        $this->selectedCommonName = $selectedCommonName;
        $this->selectedCultivars = $selectedCultivars ?? [];
        $this->selectedSeedSize = $selectedSeedSize;
    }
    
    protected function getTopRecommendations(?string $selectedCommonName = null, array $selectedCultivars = [], ?string $selectedSeedSize = null): Collection
    {
        $recommendations = $this->getSmartRecommendations($selectedCommonName, $selectedCultivars, $selectedSeedSize);
        
        // Return all recommendations for the widget to handle categorization
        return $recommendations;
    }
    
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
    
    protected function enrichVariationData($variation, ?string $selectedSeedSize = null)
    {
        // Calculate all metrics
        $pricePerKg = $variation->current_price / $variation->weight_kg;
        $pricePerGram = $pricePerKg / 1000;
        
        // Add calculated fields
        $variation->common_name = $variation->seedEntry->common_name;
        $variation->cultivar_name = $variation->seedEntry->cultivar_name;
        $variation->supplier_name = $variation->seedEntry->supplier->name ?? 'Unknown';
        $variation->supplier_product_url = $variation->seedEntry->supplier_product_url;
        $variation->price_per_kg = $pricePerKg;
        $variation->price_per_gram = $pricePerGram;
        
        // Store the selected seed size for scoring
        $variation->selected_seed_size = $selectedSeedSize;
        
        return $variation;
    }
    
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