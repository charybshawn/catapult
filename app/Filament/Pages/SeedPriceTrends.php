<?php

namespace App\Filament\Pages;

use App\Models\SeedEntry;
use App\Filament\Widgets\SeedPriceTrendsWidget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

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
    public $separateBySupplier = false;
    public $mergeSimilarCultivars = false;
    public $priceUnit = 'kg'; // 'kg' or 'g'
    public $customGramAmount = null;
    
    public function mount(): void
    {
        $this->form->fill([
            'selectedCommonName' => $this->selectedCommonName,
            'selectedCultivars' => $this->selectedCultivars,
            'separateBySupplier' => $this->separateBySupplier,
            'mergeSimilarCultivars' => $this->mergeSimilarCultivars,
            'priceUnit' => $this->priceUnit,
            'customGramAmount' => $this->customGramAmount,
        ]);
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('selectedCommonName')
                            ->label('Filter by Common Name')
                            ->options($this->getCommonNameOptions())
                            ->searchable()
                            ->placeholder('All common names')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->selectedCultivars = [];
                            }),
                        
                        Select::make('selectedCultivars')
                            ->label('Select Cultivars')
                            ->options(function (callable $get) {
                                $commonName = $get('selectedCommonName');
                                
                                $query = SeedEntry::whereHas('variations.priceHistory')
                                    ->whereNotNull('cultivar_name')
                                    ->where('cultivar_name', '<>', '');
                                
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
                            ->live()
                            ->reactive()
                            ->dehydrated()
                            ->afterStateUpdated(function ($state) {
                                // State now contains cultivar names directly
                                $this->selectedCultivars = $state ?? [];
                                
                                // Dispatch event to update the widget
                                $this->dispatch('updateCultivars', $this->selectedCultivars, $this->selectedCommonName, $this->separateBySupplier, $this->mergeSimilarCultivars, $this->priceUnit, $this->customGramAmount);
                            })
                            ->hidden(fn (callable $get) => empty($get('selectedCommonName'))),
                    ]),
                    
                Section::make('Chart Options')
                    ->schema([
                        Radio::make('priceUnit')
                            ->label('Price Unit')
                            ->options([
                                'kg' => 'Price per kilogram (kg)',
                                'g' => 'Price per gram (g)',
                                'custom' => 'Custom gram amount',
                            ])
                            ->default('kg')
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->priceUnit = $state;
                                // Update widget when unit changes
                                $this->dispatch('updateCultivars', $this->selectedCultivars, $this->selectedCommonName, $this->separateBySupplier, $this->mergeSimilarCultivars, $this->priceUnit, $this->customGramAmount);
                            })
                            ->hidden(fn (callable $get) => empty($get('selectedCultivars'))),
                        
                        TextInput::make('customGramAmount')
                            ->label('Custom Gram Amount')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->placeholder('e.g., 25 for 25 grams')
                            ->suffix('grams')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->customGramAmount = $state;
                                // Update widget when custom amount changes
                                $this->dispatch('updateCultivars', $this->selectedCultivars, $this->selectedCommonName, $this->separateBySupplier, $this->mergeSimilarCultivars, $this->priceUnit, $this->customGramAmount);
                            })
                            ->visible(fn (callable $get) => $get('priceUnit') === 'custom')
                            ->required(fn (callable $get) => $get('priceUnit') === 'custom'),
                        
                        Toggle::make('separateBySupplier')
                            ->label('Separate lines by supplier')
                            ->helperText('Show separate lines for each supplier-cultivar combination')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->separateBySupplier = $state;
                                // Update widget when toggle changes
                                $this->dispatch('updateCultivars', $this->selectedCultivars, $this->selectedCommonName, $this->separateBySupplier, $this->mergeSimilarCultivars, $this->priceUnit, $this->customGramAmount);
                            })
                            ->hidden(fn (callable $get) => empty($get('selectedCultivars'))),
                        
                        Toggle::make('mergeSimilarCultivars')
                            ->label('Merge similar cultivars')
                            ->helperText('Combine data from cultivars that appear to be the same variety with different names')
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                $this->mergeSimilarCultivars = $state;
                                // Update widget when toggle changes
                                $this->dispatch('updateCultivars', $this->selectedCultivars, $this->selectedCommonName, $this->separateBySupplier, $this->mergeSimilarCultivars, $this->priceUnit, $this->customGramAmount);
                            })
                            ->visible(function (callable $get) {
                                $selectedCultivars = $get('selectedCultivars') ?? [];
                                $commonName = $get('selectedCommonName');
                                
                                if (count($selectedCultivars) < 2 || !$commonName) {
                                    return false;
                                }
                                
                                $similarities = $this->findSimilarCultivars($selectedCultivars, $commonName);
                                return !empty($similarities);
                            }),
                        
                        Placeholder::make('similar_cultivars')
                            ->label('Potential Duplicates')
                            ->content(function (callable $get) {
                                $selectedCultivars = $get('selectedCultivars') ?? [];
                                $commonName = $get('selectedCommonName');
                                
                                if (count($selectedCultivars) < 2 || !$commonName) {
                                    return '';
                                }
                                
                                $similarities = $this->findSimilarCultivars($selectedCultivars, $commonName);
                                
                                if (empty($similarities)) {
                                    return '<span class="text-green-600">✅ No potential duplicates detected</span>';
                                }
                                
                                $html = '<div class="space-y-2">';
                                foreach ($similarities as $similarity) {
                                    $html .= '<div class="p-2 bg-yellow-50 border border-yellow-200 rounded">';
                                    $html .= '<span class="text-yellow-800">⚠️ <strong>' . $similarity['cultivar1'] . '</strong> and <strong>' . $similarity['cultivar2'] . '</strong></span><br>';
                                    $html .= '<span class="text-sm text-yellow-600">Similarity: ' . $similarity['score'] . '% | Reason: ' . $similarity['reason'] . '</span>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->hidden(fn (callable $get) => empty($get('selectedCultivars'))),
                    ]),
            ]);
    }
    
    protected function getHeaderWidgets(): array
    {
        if (!empty($this->selectedCultivars)) {
            return [
                SeedPriceTrendsWidget::make([
                    'cultivarNames' => $this->selectedCultivars,
                    'commonNameFilter' => $this->selectedCommonName,
                    'separateBySupplier' => $this->separateBySupplier,
                    'mergeSimilarCultivars' => $this->mergeSimilarCultivars,
                    'priceUnit' => $this->priceUnit,
                    'customGramAmount' => $this->customGramAmount,
                ]),
            ];
        }
        
        return [];
    }
    
    public function getCommonNameOptions(): array
    {
        return SeedEntry::whereHas('variations.priceHistory')
            ->whereNotNull('common_name')
            ->where('common_name', '<>', '')
            ->distinct()
            ->orderBy('common_name')
            ->pluck('common_name', 'common_name')
            ->toArray();
    }
    
    protected function findSimilarCultivars(array $selectedCultivars, string $commonName): array
    {
        $similarities = [];
        
        // Compare each cultivar with every other cultivar
        for ($i = 0; $i < count($selectedCultivars); $i++) {
            for ($j = $i + 1; $j < count($selectedCultivars); $j++) {
                $cultivar1 = $selectedCultivars[$i];
                $cultivar2 = $selectedCultivars[$j];
                
                $similarity = $this->calculateSimilarity($cultivar1, $cultivar2);
                
                if ($similarity['score'] >= 70) { // 70% similarity threshold
                    $similarities[] = [
                        'cultivar1' => $cultivar1,
                        'cultivar2' => $cultivar2,
                        'score' => $similarity['score'],
                        'reason' => $similarity['reason'],
                    ];
                }
            }
        }
        
        return $similarities;
    }
    
    protected function calculateSimilarity(string $name1, string $name2): array
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));
        
        // Exact match
        if ($name1 === $name2) {
            return ['score' => 100, 'reason' => 'Exact match'];
        }
        
        // One is contained in the other (e.g., "Red" vs "Red Garnet")
        $contains1 = str_contains($name1, $name2);
        $contains2 = str_contains($name2, $name1);
        
        if ($contains1 || $contains2) {
            $shorter = strlen($name1) < strlen($name2) ? $name1 : $name2;
            $longer = strlen($name1) >= strlen($name2) ? $name1 : $name2;
            
            // If one name is completely contained in the other, it's likely the same cultivar
            // with a shorter vs longer name (e.g., "Red" vs "Red Garnet")
            // We should give this a high score, especially if the shorter name is at the start
            $startsWithShorter = str_starts_with($longer, $shorter);
            $startsWithSpaceSeparated = str_starts_with($longer, $shorter . ' ');
            
            if ($startsWithShorter || $startsWithSpaceSeparated) {
                // The shorter name is at the start of the longer name (exact or space-separated)
                $score = 95;
                $reason = 'One name is prefix of the other';
            } else {
                // The shorter name is contained but not at the start
                $score = 85;
                $reason = 'One name contains the other';
            }
            
            return ['score' => $score, 'reason' => $reason];
        }
        
        // Similar roots with descriptors (e.g., "Red Garnet" vs "Red Ruby")
        $words1 = explode(' ', $name1);
        $words2 = explode(' ', $name2);
        $commonWords = array_intersect($words1, $words2);
        
        if (!empty($commonWords)) {
            $maxWords = max(count($words1), count($words2));
            $score = (count($commonWords) / $maxWords) * 85;
            if ($score >= 50) {
                return ['score' => round($score), 'reason' => 'Share common words: ' . implode(', ', $commonWords)];
            }
        }
        
        // Levenshtein distance for typos/variations
        $distance = levenshtein($name1, $name2);
        $maxLength = max(strlen($name1), strlen($name2));
        $score = ((($maxLength - $distance) / $maxLength) * 100);
        
        if ($score >= 70) {
            return ['score' => round($score), 'reason' => 'Similar spelling (edit distance)'];
        }
        
        return ['score' => round($score), 'reason' => 'Different cultivars'];
    }
} 