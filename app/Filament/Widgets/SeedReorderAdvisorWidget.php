<?php

namespace App\Filament\Widgets;

use App\Models\SeedVariation;
use App\Models\SeedCultivar;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class SeedReorderAdvisorWidget extends BaseWidget
{
    protected static ?string $heading = 'Recommended Seed Reorders';
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getTableQuery(): Builder
    {
        return SeedVariation::query()
            ->with(['seedEntry.seedCultivar', 'seedEntry.supplier', 'consumable'])
            ->whereHas('consumable', function (Builder $query) {
                $query->whereRaw('quantity <= restock_level');
            })
            ->where('is_in_stock', true)
            ->orderBy('current_price');
    }
    
    public function getTableRecordsPerPageSelectOptions(): array
    {
        return [5, 10, 25, 50];
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('common_name')
                ->label('Common Name')
                ->getStateUsing(function ($record) {
                    return $this->extractCommonName($record->seedEntry->seedCultivar->name);
                })
                ->searchable(query: function ($query, $search) {
                    return $query->whereHas('seedEntry.seedCultivar', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('seedEntry.seedCultivar.name')
                ->label('Full Cultivar')
                ->toggleable(isToggledHiddenByDefault: true)
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('seedEntry.supplier.name')
                ->label('Supplier')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('size_description')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('current_price')
                ->money('USD')
                ->sortable(),
            Tables\Columns\TextColumn::make('price_per_kg')
                ->label('Price per kg')
                ->money('USD')
                ->getStateUsing(fn (SeedVariation $record): ?float => 
                    $record->weight_kg && $record->weight_kg > 0 ? 
                    $record->current_price / $record->weight_kg : null
                )
                ->sortable(),
            Tables\Columns\TextColumn::make('consumable.quantity')
                ->label('Current Stock')
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('consumable.restock_level')
                ->label('Restock Level')
                ->numeric()
                ->sortable(),
        ];
    }
    
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view_product')
                ->label('View Product')
                ->url(fn (SeedVariation $record): string => route('filament.admin.resources.seed-variations.edit', $record))
                ->icon('heroicon-o-eye'),
            Tables\Actions\Action::make('visit_url')
                ->label('Visit Supplier')
                ->url(fn (SeedVariation $record): string => $record->seedEntry->supplier_product_url)
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->openUrlInNewTab(),
        ];
    }
    
    protected function getTableFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('common_name')
                ->options(function () {
                    return $this->getCommonNameOptions();
                })
                ->query(function (Builder $query, array $data): Builder {
                    return $query->when(
                        $data['value'],
                        fn (Builder $query, $value): Builder => $query->whereHas('seedEntry.seedCultivar', function ($q) use ($value) {
                            // Filter by common name using multiple patterns
                            $q->where(function ($subQuery) use ($value) {
                                $subQuery->where('name', 'LIKE', $value . ' - %')  // "Basil - Genovese"
                                        ->orWhere('name', 'LIKE', $value . ',%')    // "Basil, Sweet"
                                        ->orWhere('name', '=', $value)              // Exact match "Basil"
                                        ->orWhere('name', 'LIKE', $value . ' %');   // "Basil Sweet" (space)
                            });
                        })
                    );
                })
                ->searchable()
                ->label('Common Name'),
            Tables\Filters\SelectFilter::make('supplier')
                ->relationship('seedEntry.supplier', 'name')
                ->searchable()
                ->preload()
                ->label('Supplier'),
        ];
    }
    
    protected function getCommonNameOptions(): array
    {
        // Extract unique common names from all cultivars
        $cultivars = SeedCultivar::orderBy('name')->pluck('name');
        $commonNames = [];
        
        foreach ($cultivars as $cultivarName) {
            $commonName = $this->extractCommonName($cultivarName);
            if (!empty($commonName) && $commonName !== 'Unknown') {
                $commonNames[$commonName] = $commonName;
            }
        }
        
        // Sort alphabetically and return
        ksort($commonNames);
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