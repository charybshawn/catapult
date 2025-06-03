<?php

namespace App\Filament\Widgets;

use App\Models\SeedCultivar;
use App\Models\SeedPriceHistory;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SeedPriceTrendsWidget extends ChartWidget
{
    protected static ?string $heading = 'Seed Price Trends';
    
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

    public ?array $cultivarIds = [];

    public function getDescription(): ?string
    {
        return 'Average price per kg trends for selected seed cultivars';
    }

    protected function getData(): array
    {
        if (empty($this->cultivarIds)) {
            // Default to top 5 cultivars with most price history entries
            $this->cultivarIds = SeedCultivar::query()
                ->join('seed_entries', 'seed_cultivars.id', '=', 'seed_entries.seed_cultivar_id')
                ->join('seed_variations', 'seed_entries.id', '=', 'seed_variations.seed_entry_id')
                ->join('seed_price_history', 'seed_variations.id', '=', 'seed_price_history.seed_variation_id')
                ->groupBy('seed_cultivars.id')
                ->orderByRaw('COUNT(seed_price_history.id) DESC')
                ->limit(5)
                ->pluck('seed_cultivars.id')
                ->toArray();
        }

        $cultivars = SeedCultivar::whereIn('id', $this->cultivarIds)->pluck('name', 'id');

        $period = match ($this->filter) {
            'week' => [Carbon::now()->subWeeks(4), Carbon::now(), 'day'],
            'month' => [Carbon::now()->subMonths(6), Carbon::now(), 'month'],
            'year' => [Carbon::now()->subYears(1), Carbon::now(), 'month'],
            default => [Carbon::now()->subMonths(6), Carbon::now(), 'month'],
        };

        $datasets = [];
        $labels = [];

        foreach ($cultivars as $id => $name) {
            $history = SeedPriceHistory::query()
                ->select(
                    DB::raw("DATE_FORMAT(scraped_at, '%Y-%m') as date"),
                    DB::raw('AVG(price / NULLIF(seed_variations.weight_kg, 0)) as avg_price_per_kg')
                )
                ->join('seed_variations', 'seed_price_history.seed_variation_id', '=', 'seed_variations.id')
                ->join('seed_entries', 'seed_variations.seed_entry_id', '=', 'seed_entries.id')
                ->where('seed_entries.seed_cultivar_id', $id)
                ->whereBetween('scraped_at', [$period[0], $period[1]])
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(fn ($item) => [$item->date => round($item->avg_price_per_kg, 2)]);

            if ($history->isNotEmpty()) {
                $datasets[] = [
                    'label' => $name,
                    'data' => $history->values()->toArray(),
                    'borderColor' => $this->getRandomColor($id),
                    'fill' => false,
                ];

                // Collect all unique labels
                $labels = array_unique(array_merge($labels, $history->keys()->toArray()));
            }
        }

        // Sort labels chronologically
        sort($labels);

        // Ensure all datasets have values for all labels (filling gaps with null)
        foreach ($datasets as &$dataset) {
            $data = [];
            foreach ($labels as $label) {
                $value = null;
                foreach ($dataset['data'] as $index => $val) {
                    if (isset($history[$label])) {
                        $value = $history[$label];
                        break;
                    }
                }
                $data[] = $value;
            }
            $dataset['data'] = $data;
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
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

    private function getRandomColor($seed = null): string
    {
        if ($seed) {
            srand($seed);
        }
        
        return 'rgb(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ')';
    }
} 