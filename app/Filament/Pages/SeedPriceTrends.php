<?php

namespace App\Filament\Pages;

use App\Models\SeedEntry;
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
use Filament\Forms\Components\Placeholder;
use Filament\Support\RawJs;

class SeedPriceTrends extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static string $view = 'filament.pages.seed-price-trends';
    
    protected static ?string $title = 'Seed Price Trends';
    
    protected static ?string $navigationGroup = 'Analytics & Reports';
    
    protected static ?int $navigationSort = 2;
    
    public $selectedCultivars = [];
    public $selectedCommonName = null;
    public $dateRange = '1_year';
    public $chartData = [];
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('selectedCommonName')
                            ->label('Filter by Common Name')
                            ->options(function () {
                                return $this->getCommonNameOptions();
                            })
                            ->searchable()
                            ->placeholder('All common names')
                            ->live()
                            ->afterStateUpdated(function () {
                                $this->selectedCultivars = [];
                            })
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('clear')
                                    ->icon('heroicon-m-x-mark')
                                    ->action(function () {
                                        $this->selectedCommonName = null;
                                        $this->selectedCultivars = [];
                                    })
                                    ->visible(fn () => !empty($this->selectedCommonName))
                            ),
                        
                        Select::make('selectedCultivars')
                            ->label('Select Cultivars')
                            ->options(function () {
                                return $this->getCultivarOptions();
                            })
                            ->multiple()
                            ->searchable()
                            ->placeholder('Select cultivars to compare'),
                    ]),
                    
                Select::make('dateRange')
                    ->label('Time Period')
                    ->options([
                        '1_year' => '1 Year',
                        '3_years' => '3 Years', 
                        '5_years' => '5 Years',
                        'all_time' => 'All Time',
                    ])
                    ->default('1_year')
                    ->required()
                    ->live(),
                    
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
    
    public function getCultivarOptions(): array
    {
        $query = SeedEntry::select('cultivar_name', 'id')
            ->whereHas('variations.priceHistory')
            ->distinct();
        
        // Filter by common name if selected
        if ($this->selectedCommonName) {
            $query->where('common_name', $this->selectedCommonName);
        }
        
        return $query->orderBy('cultivar_name')
            ->pluck('cultivar_name', 'id')
            ->toArray();
    }
    
    public function getCommonNameOptions(): array
    {
        // Get unique common names that have price data
        return SeedEntry::whereHas('variations.priceHistory')
            ->whereNotNull('common_name')
            ->where('common_name', '!=', '')
            ->distinct()
            ->orderBy('common_name')
            ->pluck('common_name', 'common_name')
            ->toArray();
    }
    
    protected function getDateRangeFromSelection(): array
    {
        $endDate = now();
        
        switch ($this->dateRange) {
            case '1_year':
                $startDate = $endDate->copy()->subYear();
                break;
            case '3_years':
                $startDate = $endDate->copy()->subYears(3);
                break;
            case '5_years':
                $startDate = $endDate->copy()->subYears(5);
                break;
            case 'all_time':
                // Get the earliest date from price history
                $earliestDate = SeedPriceHistory::min('scraped_at');
                $startDate = $earliestDate ? Carbon::parse($earliestDate) : $endDate->copy()->subYears(10);
                break;
            default:
                $startDate = $endDate->copy()->subYear();
        }
        
        return [$startDate, $endDate];
    }
    
    protected function loadChartData(): void
    {
        if (empty($this->selectedCultivars)) {
            $this->chartData = [];
            return;
        }
        
        [$startDate, $endDate] = $this->getDateRangeFromSelection();
        
        $data = SeedPriceHistory::query()
            ->select(
                DB::raw('DATE_FORMAT(scraped_at, "%Y-%m") as month'),
                DB::raw('AVG(price / NULLIF(seed_variations.weight_kg, 0)) as avg_price_per_kg'),
                'seed_entries.id as seed_entry_id',
                'seed_entries.cultivar_name'
            )
            ->join('seed_variations', 'seed_price_history.seed_variation_id', '=', 'seed_variations.id')
            ->join('seed_entries', 'seed_variations.seed_entry_id', '=', 'seed_entries.id')
            ->whereIn('seed_entries.id', $this->selectedCultivars)
            ->where('scraped_at', '>=', $startDate)
            ->where('scraped_at', '<=', $endDate)
            ->groupBy('month', 'seed_entries.id', 'seed_entries.cultivar_name')
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