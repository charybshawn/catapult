<?php

namespace App\Filament\Widgets;

use App\Models\SeedEntry;
use App\Models\SeedPriceHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Advanced seed price trends analytics widget for agricultural procurement intelligence.
 *
 * Provides sophisticated price tracking and trend analysis for agricultural seeds
 * across multiple suppliers, time periods, and measurement units. Features cultivar
 * comparison, supplier separation, similarity merging, and flexible price unit
 * calculations for strategic procurement planning in microgreens production.
 *
 * @filament_widget Interactive chart widget for seed price analytics
 * @business_domain Agricultural seed procurement and cost analysis
 * @analytics_features Cultivar comparison, supplier trends, price unit conversion
 * @dashboard_position Full width with customizable time periods and filters
 * @procurement_intelligence Strategic analysis for optimal seed purchasing decisions
 */
class SeedPriceTrendsWidget extends ChartWidget
{
    /** @var string|null Dynamic heading based on selected price unit */
    protected ?string $heading = null;
    
    /**
     * Generate dynamic heading based on selected price unit for clarity.
     *
     * Creates contextually appropriate heading showing the price unit being
     * analyzed (per gram, per kg, or custom gram amount) to ensure users
     * understand the pricing context for agricultural procurement analysis.
     *
     * @return string|null Contextual heading with price unit information
     * @ui_behavior Dynamic heading updates based on user-selected price unit
     */
    public function getHeading(): ?string
    {
        $unitLabel = match($this->priceUnit) {
            'g' => 'per Gram',
            'custom' => 'for ' . $this->customGramAmount . 'g',
            default => 'per Kg'
        };
        
        return "Seed Price Trends ({$unitLabel})";
    }
    
    /** @var string Widget column span for full-width price trend display */
    protected int | string | array $columnSpan = 'full';
    
    /** @var string Maximum height constraint for chart display */
    protected ?string $maxHeight = '300px';
    
    /** @var array Chart.js configuration options for legend display */
    protected ?array $options = [
        'plugins' => [
            'legend' => [
                'display' => true,
            ],
        ],
    ];

    /** @var string|null Time period filter for price trend analysis */
    public ?string $filter = 'month';

    /** @var array Selected cultivar names for price comparison */
    public ?array $cultivarNames = [];
    
    /** @var string|null Common name filter for seed variety grouping */
    public ?string $commonNameFilter = null;
    
    /** @var bool Whether to separate price trends by supplier */
    public bool $separateBySupplier = false;
    
    /** @var bool Whether to merge similar cultivar names for cleaner display */
    public bool $mergeSimilarCultivars = false;
    
    /** @var string Price unit calculation (kg, g, or custom) */
    public string $priceUnit = 'kg';
    
    /** @var float|null Custom gram amount for flexible price calculations */
    public ?float $customGramAmount = null;

    /**
     * Generate descriptive text for current price analysis configuration.
     *
     * Provides detailed information about selected cultivars, common name filters,
     * and pricing units to help users understand the scope and context of the
     * displayed price trend analysis.
     *
     * @return string|null Description of current analysis parameters
     * @ui_context Shows selected cultivars, filters, and price unit information
     */
    public function getDescription(): ?string
    {
        $cultivarList = empty($this->cultivarNames) ? 'None selected' : implode(', ', $this->cultivarNames);
        $count = is_array($this->cultivarNames) ? count($this->cultivarNames) : 'not array';
        $commonNameInfo = $this->commonNameFilter ? " for {$this->commonNameFilter}" : '';
        
        $unitLabel = match($this->priceUnit) {
            'g' => 'gram',
            'custom' => $this->customGramAmount . 'g',
            default => 'kg'
        };
        
        return "Selected cultivars: {$cultivarList} (Count: {$count}){$commonNameInfo} | Showing average price per {$unitLabel} trends (in-stock only)";
    }
    
    /**
     * Initialize widget with price analysis configuration parameters.
     *
     * Sets up the widget with user-specified cultivars, filtering options,
     * supplier separation preferences, and price unit calculations for
     * customized agricultural procurement analysis.
     *
     * @param array $cultivarNames Selected cultivar names for comparison
     * @param string|null $commonNameFilter Common name filter for variety grouping
     * @param bool $separateBySupplier Whether to show trends by supplier
     * @param bool $mergeSimilarCultivars Whether to merge similar cultivar names
     * @param string $priceUnit Price calculation unit (kg, g, or custom)
     * @param float|null $customGramAmount Custom gram amount for price calculations
     */
    public function mount(array $cultivarNames = [], ?string $commonNameFilter = null, bool $separateBySupplier = false, bool $mergeSimilarCultivars = false, string $priceUnit = 'kg', ?float $customGramAmount = null): void
    {
        $this->cultivarNames = $cultivarNames;
        $this->commonNameFilter = $commonNameFilter;
        $this->separateBySupplier = $separateBySupplier;
        $this->mergeSimilarCultivars = $mergeSimilarCultivars;
        $this->priceUnit = $priceUnit;
        $this->customGramAmount = $customGramAmount;
    }
    
    /**
     * Get Livewire event listeners for dynamic widget updates.
     *
     * @return array Event listener mappings for real-time configuration updates
     */
    protected function getListeners(): array
    {
        return [
            'updateCultivars' => 'updateCultivars',
        ];
    }
    
    /**
     * Update widget configuration via Livewire event for dynamic analysis.
     *
     * Responds to configuration changes from parent components or user
     * interactions to update price trend analysis parameters and refresh
     * the chart display with new cultivar selections and options.
     *
     * @param array $cultivars Updated cultivar names for price analysis
     * @param string|null $commonName Updated common name filter
     * @param bool $separateBySupplier Whether to separate trends by supplier
     * @param bool $mergeSimilarCultivars Whether to merge similar cultivar names
     * @param string $priceUnit Price calculation unit preference
     * @param float|null $customGramAmount Custom gram amount for calculations
     */
    public function updateCultivars($cultivars, $commonName = null, $separateBySupplier = false, $mergeSimilarCultivars = false, $priceUnit = 'kg', $customGramAmount = null): void
    {
        $this->cultivarNames = $cultivars;
        $this->commonNameFilter = $commonName;
        $this->separateBySupplier = $separateBySupplier;
        $this->mergeSimilarCultivars = $mergeSimilarCultivars;
        $this->priceUnit = $priceUnit;
        $this->customGramAmount = $customGramAmount;
        // Force re-render
        $this->dispatch('$refresh');
    }

    /**
     * Generate comprehensive seed price trend data for agricultural procurement analysis.
     *
     * Performs complex data analysis including cultivar selection, similarity merging,
     * supplier separation, time period filtering, and price unit calculations.
     * Handles multi-dimensional price tracking across suppliers and time periods
     * with sophisticated data alignment and visualization preparation.
     *
     * @return array Chart.js compatible dataset with aligned price trends
     * @business_logic Processes price history with supplier and cultivar groupings
     * @agricultural_analytics Supports strategic seed procurement decision-making
     * @data_processing Aligns time series data across multiple cultivars and suppliers
     */
    protected function getData(): array
    {
        if (empty($this->cultivarNames)) {
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }

        $cultivars = collect($this->cultivarNames)->mapWithKeys(fn($name) => [$name => $name]);
        
        // Handle merging of similar cultivars if enabled
        if ($this->mergeSimilarCultivars) {
            $cultivars = $this->mergeSimilarCultivarNames($cultivars);
        }

        $period = match ($this->filter) {
            'week' => [Carbon::now()->subWeeks(4), Carbon::now(), 'day'],
            'month' => [Carbon::now()->subMonths(6), Carbon::now(), 'month'],
            'year' => [Carbon::now()->subYears(1), Carbon::now(), 'month'],
            default => [Carbon::now()->subMonths(6), Carbon::now(), 'month'],
        };

        $labels = [];
        $allHistoryData = [];
        
        // First pass: collect all data and labels
        foreach ($cultivars as $name => $originalNames) {
            // Handle both merged and single cultivar names
            $cultivarNames = is_array($originalNames) ? $originalNames : [$name];
            if ($this->separateBySupplier) {
                // Group by supplier when separation is enabled
                $supplierData = SeedPriceHistory::query()
                    ->select(
                        DB::raw("DATE_FORMAT(scraped_at, '%Y-%m') as date"),
                        DB::raw($this->getPriceCalculationSQL() . ' as avg_price_per_unit'),
                        'suppliers.name as supplier_name'
                    )
                    ->join('seed_variations', 'seed_price_history.seed_variation_id', '=', 'seed_variations.id')
                    ->join('seed_entries', 'seed_variations.seed_entry_id', '=', 'seed_entries.id')
                    ->join('suppliers', 'seed_entries.supplier_id', '=', 'suppliers.id')
                    ->whereIn('seed_entries.cultivar_name', $cultivarNames)
                    ->where('seed_variations.is_in_stock', true)
                    ->when($this->commonNameFilter, function($query) {
                        $query->where('seed_entries.common_name', $this->commonNameFilter);
                    })
                    ->whereBetween('scraped_at', [$period[0], $period[1]])
                    ->groupBy('date', 'suppliers.id', 'suppliers.name')
                    ->orderBy('date')
                    ->get()
                    ->groupBy('supplier_name');
                
                foreach ($supplierData as $supplierName => $records) {
                    $history = $records->mapWithKeys(fn ($item) => [$item->date => round($item->avg_price_per_unit, 2)]);
                    $key = "{$name} ({$supplierName})";
                    $allHistoryData[$key] = $history;
                    // Collect all unique labels
                    $labels = array_unique(array_merge($labels, $history->keys()->toArray()));
                }
            } else {
                // Regular grouping by cultivar only
                $history = SeedPriceHistory::query()
                    ->select(
                        DB::raw("DATE_FORMAT(scraped_at, '%Y-%m') as date"),
                        DB::raw($this->getPriceCalculationSQL() . ' as avg_price_per_unit')
                    )
                    ->join('seed_variations', 'seed_price_history.seed_variation_id', '=', 'seed_variations.id')
                    ->join('seed_entries', 'seed_variations.seed_entry_id', '=', 'seed_entries.id')
                    ->whereIn('seed_entries.cultivar_name', $cultivarNames)
                    ->where('seed_variations.is_in_stock', true)
                    ->when($this->commonNameFilter, function($query) {
                        $query->where('seed_entries.common_name', $this->commonNameFilter);
                    })
                    ->whereBetween('scraped_at', [$period[0], $period[1]])
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get()
                    ->mapWithKeys(fn ($item) => [$item->date => round($item->avg_price_per_unit, 2)]);

                if ($history->isNotEmpty()) {
                    $allHistoryData[$name] = $history;
                    // Collect all unique labels
                    $labels = array_unique(array_merge($labels, $history->keys()->toArray()));
                }
            }
        }

        // Sort labels chronologically
        sort($labels);
        
        // Second pass: create aligned datasets
        $alignedDatasets = [];
        foreach ($allHistoryData as $name => $history) {
            // Align data with all labels
            $data = [];
            foreach ($labels as $label) {
                $data[] = $history->get($label, null);
            }
            
            $alignedDatasets[] = [
                'label' => $name,
                'data' => $data,
                'borderColor' => $this->getColorForCultivar($name),
                'backgroundColor' => $this->getColorForCultivar($name) . '20',
                'fill' => false,
            ];
        }

        // Debug: Check what we're actually returning
        $debugInfo = [
            'input_cultivars' => $this->cultivarNames,
            'processed_cultivars' => array_keys($allHistoryData),
            'labels_count' => count($labels),
            'datasets_count' => count($alignedDatasets),
            'dataset_labels' => array_column($alignedDatasets, 'label'),
        ];
        
        return [
            'labels' => $labels,
            'datasets' => $alignedDatasets,
            'debug' => $debugInfo, // This will show in browser console
        ];
    }

    /**
     * Get chart type for price trend visualization.
     *
     * @return string Chart.js chart type identifier for line chart display
     */
    protected function getType(): string
    {
        return 'line';
    }

    /**
     * Get available time period filters for price trend analysis.
     *
     * @return array|null Available filter options with labels
     */
    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last 4 Weeks',
            'month' => 'Last 6 Months',
            'year' => 'Last Year',
        ];
    }

    /**
     * Generate consistent color assignment for cultivar visualization.
     *
     * Uses hash-based color selection to ensure each cultivar consistently
     * receives the same color across different chart renderings, improving
     * user experience and data interpretation in price trend analysis.
     *
     * @param string $cultivarName Cultivar name for color assignment
     * @return string Hex color code for consistent chart visualization
     * @ui_consistency Maintains same color for each cultivar across refreshes
     */
    private function getColorForCultivar(string $cultivarName): string
    {
        // Professional color palette for charts
        $colors = [
            '#3B82F6', // Blue
            '#EF4444', // Red
            '#10B981', // Green
            '#F59E0B', // Amber
            '#8B5CF6', // Purple
            '#EC4899', // Pink
            '#14B8A6', // Teal
            '#F97316', // Orange
            '#6366F1', // Indigo
            '#84CC16', // Lime
        ];
        
        // Use hash of cultivar name to consistently assign colors
        $hash = crc32($cultivarName);
        $index = abs($hash) % count($colors);
        
        return $colors[$index];
    }
    
    /**
     * Merge similar cultivar names for cleaner price trend visualization.
     *
     * Uses sophisticated similarity algorithms to group cultivar names that
     * represent the same variety but have slight naming variations across
     * suppliers. Reduces chart complexity while maintaining data accuracy
     * for agricultural procurement analysis.
     *
     * @param \Illuminate\Support\Collection $cultivars Collection of cultivar names to process
     * @return \Illuminate\Support\Collection Merged cultivar groups with combined names
     * @business_logic Groups similar names using 70% similarity threshold
     * @data_processing Combines price data from similar cultivar name variations
     */
    private function mergeSimilarCultivarNames($cultivars)
    {
        $mergedGroups = [];
        $processedNames = [];
        
        foreach ($cultivars as $name => $displayName) {
            if (in_array($name, $processedNames)) {
                continue;
            }
            
            $similarNames = [$name];
            $processedNames[] = $name;
            
            // Find similar names
            foreach ($cultivars as $otherName => $otherDisplayName) {
                if ($name === $otherName || in_array($otherName, $processedNames)) {
                    continue;
                }
                
                $similarity = $this->calculateCultivarSimilarity($name, $otherName);
                if ($similarity >= 70) { // 70% similarity threshold
                    $similarNames[] = $otherName;
                    $processedNames[] = $otherName;
                }
            }
            
            // Create merged name (use the longest/most descriptive name)
            $longestName = collect($similarNames)->sortByDesc('strlen')->first();
            $mergedName = count($similarNames) > 1 ? 
                $longestName . ' (merged: ' . implode(', ', array_diff($similarNames, [$longestName])) . ')' : 
                $longestName;
            
            $mergedGroups[$mergedName] = $similarNames;
        }
        
        return collect($mergedGroups)->mapWithKeys(function ($originalNames, $mergedName) {
            return [$mergedName => $originalNames];
        });
    }
    
    /**
     * Calculate similarity score between two cultivar names for merging decisions.
     *
     * Implements multi-tier similarity detection including substring matching,
     * prefix detection, and word-based comparison to identify cultivar name
     * variations that should be grouped together for cleaner price analysis.
     *
     * @param string $name1 First cultivar name for comparison
     * @param string $name2 Second cultivar name for comparison
     * @return float Similarity score (0-100) for merging threshold evaluation
     * @algorithm_logic Substring containment (95%), prefix matching (85%), word overlap (50-85%)
     */
    private function calculateCultivarSimilarity(string $name1, string $name2): float
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));
        
        // One contains the other
        if (str_contains($name1, $name2) || str_contains($name2, $name1)) {
            $shorter = strlen($name1) < strlen($name2) ? $name1 : $name2;
            $longer = strlen($name1) >= strlen($name2) ? $name1 : $name2;
            
            // If one name is a prefix of the other, it's likely the same cultivar
            $startsWithShorter = str_starts_with($longer, $shorter);
            $startsWithSpaceSeparated = str_starts_with($longer, $shorter . ' ');
            
            if ($startsWithShorter || $startsWithSpaceSeparated) {
                return 95;
            } else {
                return 85;
            }
        }
        
        // Similar words
        $words1 = explode(' ', $name1);
        $words2 = explode(' ', $name2);
        $commonWords = array_intersect($words1, $words2);
        
        if (!empty($commonWords)) {
            $maxWords = max(count($words1), count($words2));
            $score = (count($commonWords) / $maxWords) * 85;
            if ($score >= 50) {
                return $score;
            }
        }
        
        return 0;
    }
    
    /**
     * Generate SQL calculation for flexible price unit conversion.
     *
     * Creates appropriate SQL expression for calculating prices in different
     * units (per kg, per gram, or custom gram amounts) to support flexible
     * agricultural procurement analysis and comparison across different
     * package sizes and measurement preferences.
     *
     * @return string SQL expression for price unit calculation
     * @business_logic Handles kg to gram conversions and custom amounts
     * @procurement_support Enables price comparison across different package sizes
     */
    private function getPriceCalculationSQL(): string
    {
        return match($this->priceUnit) {
            'g' => 'AVG(price / NULLIF(seed_variations.weight_kg, 0) / 1000)',
            'custom' => $this->customGramAmount ? 
                'AVG(price / NULLIF(seed_variations.weight_kg, 0) * ' . ($this->customGramAmount / 1000) . ')' : 
                'AVG(price / NULLIF(seed_variations.weight_kg, 0))',
            default => 'AVG(price / NULLIF(seed_variations.weight_kg, 0))'
        };
    }
} 