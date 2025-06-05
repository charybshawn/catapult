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
use Illuminate\Support\HtmlString;

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
    public $dateRangeMonths = 12;
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
                            ->options(function (callable $get) {
                                // Get the current common name from form state
                                $commonName = $get('selectedCommonName');
                                
                                // Get cultivars filtered by this common name
                                $query = \App\Models\SeedEntry::whereHas('variations.priceHistory')
                                    ->whereNotNull('cultivar_name')
                                    ->where('cultivar_name', '!=', '');
                                
                                if ($commonName) {
                                    $query->where('common_name', $commonName);
                                }
                                
                                return $query->distinct()
                                    ->orderBy('cultivar_name')
                                    ->pluck('cultivar_name', 'cultivar_name')
                                    ->toArray();
                            })
                            ->multiple()
                            ->searchable()
                            ->placeholder('Select cultivars to compare')
                            ->live(),
                    ]),
                    
                Placeholder::make('dateRangeSlider')
                    ->label('Time Period')
                    ->content(function () {
                        return new HtmlString(view('filament.forms.components.date-range-slider', [
                            'statePath' => 'dateRangeMonths',
                            'value' => $this->dateRangeMonths,
                            'min' => 1,
                            'max' => $this->getMaxMonthsAvailable(),
                            'step' => 1,
                            'labels' => $this->getSliderLabels(),
                        ])->render());
                    }),
                    
                Section::make('Chart Data')
                    ->schema([
                        Placeholder::make('chart')
                            ->label('')
                            ->live()
                            ->content(function () {
                                if (empty($this->selectedCultivars)) {
                                    return 'Select at least one cultivar to display price trends.';
                                }
                                
                                $this->loadChartData();
                                
                                if (empty($this->chartData)) {
                                    // Add debug information
                                    $debugInfo = [
                                        'Selected cultivars: ' . count($this->selectedCultivars),
                                        'Date range: ' . $this->dateRangeMonths . ' months',
                                        'Common name filter: ' . ($this->selectedCommonName ?: 'None'),
                                    ];
                                    
                                    return 'No data available for the selected cultivars and date range.<br><br>' .
                                           'Debug info:<br>' . implode('<br>', $debugInfo);
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
        // This method is now handled inline in the form
        // Keeping it for backward compatibility but not used
        return [];
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
        
        if ($this->dateRangeMonths >= $this->getMaxMonthsAvailable()) {
            // "All time" - get the earliest available date
            $earliestDate = SeedPriceHistory::min('scraped_at');
            $startDate = $earliestDate ? Carbon::parse($earliestDate) : $endDate->copy()->subYears(10);
        } else {
            // Calculate start date based on selected months
            $startDate = $endDate->copy()->subMonths($this->dateRangeMonths);
        }
        
        return [$startDate, $endDate];
    }
    
    protected function getMaxMonthsAvailable(): int
    {
        $earliestDate = SeedPriceHistory::min('scraped_at');
        if (!$earliestDate) {
            return 60; // Default to 5 years if no data
        }
        
        $months = Carbon::parse($earliestDate)->diffInMonths(now());
        return max($months, 60); // Minimum 5 years for the slider
    }
    
    protected function getSliderLabels(): array
    {
        $maxMonths = $this->getMaxMonthsAvailable();
        
        return [
            1 => '1 Month',
            3 => '3 Months',
            6 => '6 Months',
            12 => '1 Year',
            24 => '2 Years',
            36 => '3 Years',
            60 => '5 Years',
            $maxMonths => 'All Time',
        ];
    }
    
    protected function loadChartData(): void
    {
        if (empty($this->selectedCultivars)) {
            $this->chartData = [];
            return;
        }
        
        [$startDate, $endDate] = $this->getDateRangeFromSelection();
        
        // Debug: Check if we have any price history at all
        $totalPriceHistory = SeedPriceHistory::count();
        $entriesWithHistory = SeedEntry::whereHas('variations.priceHistory')->count();
        
        \Log::info('Price trends debug', [
            'total_price_history_records' => $totalPriceHistory,
            'entries_with_history' => $entriesWithHistory,
            'selected_cultivars' => $this->selectedCultivars,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]);
        
        $data = SeedPriceHistory::query()
            ->select(
                DB::raw('DATE_FORMAT(scraped_at, "%Y-%m") as month'),
                DB::raw('AVG(price / NULLIF(seed_variations.weight_kg, 0)) as avg_price_per_kg'),
                'seed_entries.cultivar_name'
            )
            ->join('seed_variations', 'seed_price_history.seed_variation_id', '=', 'seed_variations.id')
            ->join('seed_entries', 'seed_variations.seed_entry_id', '=', 'seed_entries.id')
            ->whereIn('seed_entries.cultivar_name', $this->selectedCultivars)
            ->where('scraped_at', '>=', $startDate)
            ->where('scraped_at', '<=', $endDate)
            ->groupBy('month', 'seed_entries.cultivar_name')
            ->orderBy('month')
            ->get();
            
        \Log::info('Query result', [
            'data_count' => $data->count(),
            'first_few_records' => $data->take(3)->toArray(),
        ]);
        
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