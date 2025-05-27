<?php

namespace App\Filament\Pages;

use App\Models\SeedCultivar;
use App\Models\SeedPriceHistory;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Support\RawJs;

class SeedPriceTrends extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static string $view = 'filament.pages.seed-price-trends';
    
    protected static ?string $title = 'Seed Price Trends';
    
    protected static ?string $navigationGroup = 'Seed Inventory';
    
    public $selectedCultivars = [];
    public $startDate;
    public $endDate;
    public $chartData = [];
    
    public function mount(): void
    {
        $this->startDate = now()->subMonths(12)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->form->fill();
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        Select::make('selectedCultivars')
                            ->label('Select Cultivars')
                            ->options(function () {
                                return $this->getCultivarOptions();
                            })
                            ->multiple()
                            ->searchable()
                            ->placeholder('Select cultivars to compare')
                            ->columnSpan(1),
                        
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->required()
                            ->default(now()->subMonths(12))
                            ->columnSpan(1),
                            
                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->required()
                            ->default(now())
                            ->columnSpan(1),
                    ]),
                    
                Section::make('Chart Data')
                    ->schema([
                        Placeholder::make('chart')
                            ->label('')
                            ->content(function () {
                                if (empty($this->selectedCultivars)) {
                                    return 'Select at least one cultivar to display price trends.';
                                }
                                
                                $this->loadChartData();
                                
                                if (empty($this->chartData)) {
                                    return 'No data available for the selected cultivars and date range.';
                                }
                                
                                return view('filament.pages.components.price-trend-chart', [
                                    'chartData' => $this->chartData,
                                ])->render();
                            }),
                    ]),
            ])
            ->statePath('data');
    }
    
    protected function getCultivarOptions(): array
    {
        return SeedCultivar::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
    
    protected function loadChartData(): void
    {
        if (empty($this->selectedCultivars)) {
            $this->chartData = [];
            return;
        }
        
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        
        $data = SeedPriceHistory::query()
            ->select(
                DB::raw('DATE_FORMAT(scraped_at, "%Y-%m") as month'),
                DB::raw('AVG(price / NULLIF(seed_variations.weight_kg, 0)) as avg_price_per_kg'),
                'seed_entries.seed_cultivar_id',
                'seed_cultivars.name as cultivar_name'
            )
            ->join('seed_variations', 'seed_price_history.seed_variation_id', '=', 'seed_variations.id')
            ->join('seed_entries', 'seed_variations.seed_entry_id', '=', 'seed_entries.id')
            ->join('seed_cultivars', 'seed_entries.seed_cultivar_id', '=', 'seed_cultivars.id')
            ->whereIn('seed_entries.seed_cultivar_id', $this->selectedCultivars)
            ->where('scraped_at', '>=', $startDate)
            ->where('scraped_at', '<=', $endDate)
            ->groupBy('month', 'seed_entries.seed_cultivar_id', 'seed_cultivars.name')
            ->orderBy('month')
            ->get();
        
        // Prepare data for Chart.js
        $months = $data->pluck('month')->unique()->sort()->values()->toArray();
        $cultivars = $data->pluck('cultivar_name')->unique()->values()->toArray();
        
        $datasets = [];
        foreach ($cultivars as $cultivar) {
            $cultivarData = $data->where('cultivar_name', $cultivar);
            $priceData = [];
            
            foreach ($months as $month) {
                $monthData = $cultivarData->where('month', $month)->first();
                $priceData[] = $monthData ? round($monthData->avg_price_per_kg, 2) : null;
            }
            
            $datasets[] = [
                'label' => $cultivar,
                'data' => $priceData,
                'fill' => false,
                'borderWidth' => 2,
            ];
        }
        
        $this->chartData = [
            'labels' => $months,
            'datasets' => $datasets,
        ];
    }
} 