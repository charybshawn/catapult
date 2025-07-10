<?php

namespace App\Filament\Resources\HarvestResource\Widgets;

use App\Models\Harvest;
use App\Models\MasterCultivar;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class HarvestTrendsChart extends ChartWidget
{
    protected static ?string $heading = 'Harvest Trends by Variety';
    
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
    ];
    
    public ?string $filter = 'all';
    
    protected function getFilters(): ?array
    {
        $varieties = MasterCultivar::with('masterSeedCatalog')
            ->whereHas('harvests')
            ->get()
            ->mapWithKeys(function ($cultivar) {
                return [$cultivar->id => $cultivar->full_name];
            })
            ->toArray();
            
        return ['all' => 'All Varieties'] + $varieties;
    }
    
    protected function getData(): array
    {
        $weeks = collect();
        $datasets = collect();
        
        // Get last 8 weeks of data
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();
            $weeks->push([
                'label' => $weekStart->format('M j'),
                'start' => $weekStart,
                'end' => $weekEnd,
            ]);
        }
        
        if ($this->filter === 'all') {
            // Show top 5 varieties by total harvest
            $topVarieties = Harvest::with('masterCultivar.masterSeedCatalog')
                ->selectRaw('master_cultivar_id, SUM(total_weight_grams) as total_weight')
                ->groupBy('master_cultivar_id')
                ->orderByDesc('total_weight')
                ->limit(5)
                ->get();
                
            $colors = [
                'rgb(59, 130, 246)',   // Blue
                'rgb(16, 185, 129)',   // Green
                'rgb(245, 158, 11)',   // Amber
                'rgb(239, 68, 68)',    // Red
                'rgb(139, 92, 246)',   // Purple
            ];
            
            foreach ($topVarieties as $index => $harvest) {
                $variety = $harvest->masterCultivar;
                $weeklyData = $this->getWeeklyDataForVariety($variety->id, $weeks);
                
                $datasets->push([
                    'label' => $variety->full_name,
                    'data' => $weeklyData,
                    'borderColor' => $colors[$index % count($colors)],
                    'backgroundColor' => $colors[$index % count($colors)] . '20',
                    'tension' => 0.4,
                ]);
            }
        } else {
            // Show single variety
            $variety = MasterCultivar::find($this->filter);
            if ($variety) {
                $weeklyData = $this->getWeeklyDataForVariety($variety->id, $weeks);
                
                $datasets->push([
                    'label' => $variety->full_name,
                    'data' => $weeklyData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgb(59, 130, 246, 0.2)',
                    'tension' => 0.4,
                    'fill' => true,
                ]);
            }
        }
        
        return [
            'datasets' => $datasets->toArray(),
            'labels' => $weeks->pluck('label')->toArray(),
        ];
    }
    
    protected function getWeeklyDataForVariety(int $varietyId, Collection $weeks): array
    {
        return $weeks->map(function ($week) use ($varietyId) {
            return Harvest::where('master_cultivar_id', $varietyId)
                ->whereBetween('harvest_date', [$week['start'], $week['end']])
                ->sum('total_weight_grams');
        })->toArray();
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Weight (grams)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Week Starting',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'maintainAspectRatio' => false,
        ];
    }
}