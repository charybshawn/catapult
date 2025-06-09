<?php

namespace App\Filament\Widgets;

use App\Models\SeedVariation;
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
            ->with(['seedEntry', 'seedEntry.supplier', 'consumable'])
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
            Tables\Columns\TextColumn::make('seedEntry.common_name')
                ->label('Common Name')
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
                        fn (Builder $query, $value): Builder => $query->whereHas('seedEntry', function ($q) use ($value) {
                            $q->where('common_name', $value);
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
        // Get common names from seed entries that have in-stock variations
        // This ensures we only show options that are actually available for reorder
        $seedEntries = \App\Models\SeedEntry::whereHas('variations', function($q) {
                $q->where('is_in_stock', true);
            })
            ->whereNotNull('common_name')
            ->distinct()
            ->orderBy('common_name')
            ->pluck('common_name');
        
        $commonNames = [];
        foreach ($seedEntries as $commonName) {
            if (!empty($commonName)) {
                $commonNames[$commonName] = $commonName;
            }
        }
        
        return $commonNames;
    }
    
} 