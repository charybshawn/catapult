<?php

namespace App\Filament\Widgets;

use App\Models\SeedEntry;
use App\Models\SeedPriceHistory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SeedPriceTrendsWidget extends ChartWidget
{
    protected static ?string $heading = null;
    
    public function getHeading(): ?string
    {
        $unitLabel = match($this->priceUnit) {
            'g' => 'per Gram',
            'custom' => 'for ' . $this->customGramAmount . 'g',
            default => 'per Kg'
        };
        
        return "Seed Price Trends ({$unitLabel})";
    }
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $maxHeight = '300px';
    
    protected static ?array $options = [
        'plugins' => [
            'legend' => [
                'display' => true,
            ],
        ],
    ];

    public ?string $filter = 'month';

    public ?array $cultivarNames = [];
    public ?string $commonNameFilter = null;
    public bool $separateBySupplier = false;
    public bool $mergeSimilarCultivars = false;
    public string $priceUnit = 'kg';
    public ?float $customGramAmount = null;

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
    
    public function mount(array $cultivarNames = [], ?string $commonNameFilter = null, bool $separateBySupplier = false, bool $mergeSimilarCultivars = false, string $priceUnit = 'kg', ?float $customGramAmount = null): void
    {
        $this->cultivarNames = $cultivarNames;
        $this->commonNameFilter = $commonNameFilter;
        $this->separateBySupplier = $separateBySupplier;
        $this->mergeSimilarCultivars = $mergeSimilarCultivars;
        $this->priceUnit = $priceUnit;
        $this->customGramAmount = $customGramAmount;
    }
    
    protected function getListeners(): array
    {
        return [
            'updateCultivars' => 'updateCultivars',
        ];
    }
    
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

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'week' => 'Last 4 Weeks',
            'month' => 'Last 6 Months',
            'year' => 'Last Year',
        ];
    }

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