<?php

namespace App\Filament\Pages;

use App\Models\SeedEntry;
use App\Models\SeedVariation;
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
    
    public function mount(): void
    {
        $this->form->fill();
    }
    
    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Select::make('selectedCommonName')
                    ->label('Filter by Common Name')
                    ->options(function () {
                        return $this->getCommonNameOptions();
                    })
                    ->searchable()
                    ->placeholder('All Common Names')
                    ->live()
                    ->afterStateUpdated(function () {
                        $this->resetTable();
                    }),
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
                    ->label('Price')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_per_kg')
                    ->label('Price per kg')
                    ->money(fn ($record) => $record->currency)
                    ->getStateUsing(fn (SeedVariation $record): ?float => $record->price_per_kg)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw('current_price / NULLIF(weight_kg, 0) ' . $direction);
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
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->url(fn (SeedVariation $record): string => route('filament.admin.resources.seed-variations.edit', ['record' => $record]))
                    ->icon('heroicon-o-eye'),
            ])
            ->defaultSort('price_per_kg', 'asc')
            ->defaultGroup('seedEntry.common_name');
    }
    
    protected function getTableQuery(): Builder
    {
        $query = SeedVariation::query()
            ->with(['seedEntry.supplier', 'consumable'])
            ->where('is_in_stock', true);
            
        if ($this->selectedCommonName) {
            $query->whereHas('seedEntry', function ($q) {
                $q->where('common_name', $this->selectedCommonName);
            });
        }
        
        return $query;
    }
    
    protected function getCommonNameOptions(): array
    {
        // Get unique common names from seed entries that have in-stock variations
        $commonNames = SeedEntry::whereNotNull('common_name')
            ->whereHas('variations', function($q) {
                $q->where('is_in_stock', true);
            })
            ->distinct()
            ->orderBy('common_name')
            ->pluck('common_name', 'common_name')
            ->filter()
            ->toArray();
        
        return $commonNames;
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