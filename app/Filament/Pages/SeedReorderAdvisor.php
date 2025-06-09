<?php

namespace App\Filament\Pages;

use App\Models\SeedEntry;
use App\Models\SeedVariation;
use App\Filament\Widgets\SmartSeedRecommendationsWidget;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Enums\FontWeight;

class SeedReorderAdvisor extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static string $view = 'filament.pages.seed-reorder-advisor';
    
    protected static ?string $title = 'Seed Reorder Advisor';
    
    protected static ?string $navigationGroup = 'Analytics & Reports';
    
    protected static ?int $navigationSort = 3;
    
    public $selectedCommonName = null;
    public $selectedCultivars = [];
    public $selectedSeedSize = null;
    public $displayCurrency = 'CAD';
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Grid::make(4)->schema([
                    Select::make('selectedCommonName')
                        ->label('Filter by Common Name')
                        ->options(function () {
                            return $this->getCommonNameOptions();
                        })
                        ->searchable()
                        ->placeholder('Select Common Name')
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            $this->selectedCultivars = []; // Reset cultivars when common name changes
                            $this->resetTable();
                            $this->dispatch('filtersUpdated', 
                                selectedCommonName: $state, 
                                selectedCultivars: [], 
                                selectedSeedSize: $this->selectedSeedSize
                            );
                        }),
                    Select::make('selectedCultivars')
                        ->label('Filter by Cultivars')
                        ->options(function () {
                            return $this->getCultivarOptions();
                        })
                        ->multiple()
                        ->searchable()
                        ->placeholder('Select Cultivars')
                        ->visible(fn() => !empty($this->selectedCommonName))
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            $this->resetTable();
                            $this->dispatch('filtersUpdated', 
                                selectedCommonName: $this->selectedCommonName, 
                                selectedCultivars: $state ?? [], 
                                selectedSeedSize: $this->selectedSeedSize
                            );
                        }),
                    Select::make('selectedSeedSize')
                        ->label('Seed Size Category')
                        ->options([
                            'x-small' => 'X-Small Seeds (0-500g)',
                            'small' => 'Small Seeds (1-5kg)',
                            'medium' => 'Medium Seeds (5-10kg)', 
                            'large' => 'Large Seeds (25kg)',
                        ])
                        ->placeholder('Select Seed Size')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            $this->resetTable();
                            $this->dispatch('filtersUpdated', 
                                selectedCommonName: $this->selectedCommonName, 
                                selectedCultivars: $this->selectedCultivars, 
                                selectedSeedSize: $state
                            );
                        }),
                    Select::make('displayCurrency')
                        ->label('Display Currency')
                        ->options([
                            'CAD' => '🇨🇦 CAD (Canadian Dollar)',
                            'USD' => '🇺🇸 USD (US Dollar)',
                        ])
                        ->default('CAD')
                        ->live()
                        ->afterStateUpdated(function ($state) {
                            $this->resetTable();
                        }),
                ]),
            ]);
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('seedEntry.common_name')
                    ->label('Common Name')
                    ->weight(FontWeight::Bold)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seedEntry.cultivar_name')
                    ->label('Cultivar')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('seedEntry.supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('size_description')
                    ->label('Size')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight_kg')
                    ->label('Weight (kg)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_price')
                    ->label(fn() => 'Price (' . $this->displayCurrency . ')')
                    ->getStateUsing(fn (SeedVariation $record): string => 
                        $this->displayCurrency === 'CAD' 
                            ? $record->getFormattedPriceWithConversion('CAD')
                            : $record->getFormattedPriceWithConversion('USD')
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_kg')
                    ->label(fn() => 'Price per kg (' . $this->displayCurrency . ')')
                    ->getStateUsing(function (SeedVariation $record): string {
                        $pricePerKg = $this->displayCurrency === 'CAD' 
                            ? $record->price_per_kg_in_cad 
                            : $record->price_per_kg_in_usd;
                        
                        $symbol = $this->displayCurrency === 'CAD' ? 'CDN$' : 'USD$';
                        return $pricePerKg ? $symbol . number_format($pricePerKg, 2) : 'N/A';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        if ($this->displayCurrency === 'CAD') {
                            return $query->orderByRaw('
                                CASE 
                                    WHEN currency = "CAD" THEN current_price / NULLIF(weight_kg, 0)
                                    WHEN currency = "USD" THEN (current_price * 1.35) / NULLIF(weight_kg, 0)
                                    ELSE current_price / NULLIF(weight_kg, 0)
                                END ' . $direction
                            );
                        } else {
                            return $query->orderByRaw('
                                CASE 
                                    WHEN currency = "USD" THEN current_price / NULLIF(weight_kg, 0)
                                    WHEN currency = "CAD" THEN (current_price * 0.74) / NULLIF(weight_kg, 0)
                                    ELSE current_price / NULLIF(weight_kg, 0)
                                END ' . $direction
                            );
                        }
                    }),
                Tables\Columns\IconColumn::make('is_in_stock')
                    ->label('In Stock')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('consumable.formatted_current_stock')
                    ->label('Current Stock')
                    ->placeholder('Not linked'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('supplier')
                    ->relationship('seedEntry.supplier', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Supplier'),
                Tables\Filters\SelectFilter::make('size_description')
                    ->options(function () {
                        return SeedVariation::query()
                            ->whereNotNull('size_description')
                            ->distinct()
                            ->orderBy('size_description')
                            ->pluck('size_description', 'size_description')
                            ->toArray();
                    })
                    ->searchable()
                    ->label('Size'),
                Tables\Filters\Filter::make('weight_range')
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\TextInput::make('weight_from')
                                    ->label('Weight From (kg)')
                                    ->numeric()
                                    ->placeholder('Min weight'),
                                \Filament\Forms\Components\TextInput::make('weight_to')
                                    ->label('Weight To (kg)')
                                    ->numeric()
                                    ->placeholder('Max weight'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['weight_from'],
                                fn (Builder $query, $weight): Builder => $query->where('weight_kg', '>=', $weight),
                            )
                            ->when(
                                $data['weight_to'],
                                fn (Builder $query, $weight): Builder => $query->where('weight_kg', '<=', $weight),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['weight_from'] ?? null) {
                            $indicators[] = 'Weight from: ' . $data['weight_from'] . ' kg';
                        }
                        if ($data['weight_to'] ?? null) {
                            $indicators[] = 'Weight to: ' . $data['weight_to'] . ' kg';
                        }
                        return $indicators;
                    }),
                Tables\Filters\TernaryFilter::make('is_in_stock')
                    ->label('Stock Status')
                    ->placeholder('All items')
                    ->trueLabel('In Stock')
                    ->falseLabel('Out of Stock')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_in_stock', true),
                        false: fn (Builder $query) => $query->where('is_in_stock', false),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->url(fn (SeedVariation $record): string => route('filament.admin.resources.seed-variations.edit', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ])
            ->defaultSort('price_per_kg', 'asc')
            ->defaultGroup('seedEntry.common_name')
            ->filtersFormColumns(2); // Display filters in 2 columns for better layout
    }
    
    protected function getTableQuery(): Builder
    {
        $query = SeedVariation::query()
            ->with(['seedEntry.supplier', 'consumable']);
            
        if ($this->selectedCommonName) {
            $query->whereHas('seedEntry', function ($q) {
                $q->where('common_name', $this->selectedCommonName);
                
                if (!empty($this->selectedCultivars)) {
                    $q->whereIn('cultivar_name', $this->selectedCultivars);
                }
            });
        }
        
        return $query;
    }
    
    protected function getCommonNameOptions(): array
    {
        // Get unique common names from seed entries that have variations
        $commonNames = SeedEntry::whereNotNull('common_name')
            ->whereHas('variations')
            ->distinct()
            ->orderBy('common_name')
            ->pluck('common_name', 'common_name')
            ->filter()
            ->toArray();
        
        return $commonNames;
    }
    
    protected function getCultivarOptions(): array
    {
        if (!$this->selectedCommonName) {
            return [];
        }
        
        // Get unique cultivars for the selected common name
        $cultivars = SeedEntry::where('common_name', $this->selectedCommonName)
            ->whereNotNull('cultivar_name')
            ->whereHas('variations')
            ->distinct()
            ->orderBy('cultivar_name')
            ->pluck('cultivar_name', 'cultivar_name')
            ->filter()
            ->toArray();
        
        return $cultivars;
    }
    
    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    public function getFormActions(): array
    {
        return []; // Remove default form actions since this is just a filter form
    }
    
    /**
     * Extract common name from full cultivar name
     * 
     * @param string $cultivarName
     * @return string
     */
    protected function extractCommonName(string $cultivarName): string
    {
        if (empty($cultivarName) || $cultivarName === 'Unknown Cultivar') {
            return 'Unknown';
        }
        
        // Remove common suffixes and prefixes
        $cleaned = trim($cultivarName);
        
        // Remove organic/non-gmo/heirloom suffixes
        $cleaned = preg_replace('/\s*-\s*(Organic|Non-GMO|Heirloom|Certified).*$/i', '', $cleaned);
        
        // If there's a dash, take everything before the first dash as the common name
        if (strpos($cleaned, ' - ') !== false) {
            $parts = explode(' - ', $cleaned, 2);
            return trim($parts[0]);
        }
        
        // If there's a comma, take everything before the first comma
        if (strpos($cleaned, ',') !== false) {
            $parts = explode(',', $cleaned, 2);
            return trim($parts[0]);
        }
        
        // Return the whole name if no separators found
        return $cleaned;
    }
} 