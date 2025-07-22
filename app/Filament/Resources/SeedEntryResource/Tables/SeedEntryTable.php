<?php

namespace App\Filament\Resources\SeedEntryResource\Tables;

use App\Actions\SeedEntry\ValidateSeedEntryDeletionAction;
use App\Filament\Resources\SeedEntryResource\Tables\SeedEntryTableActions;
use App\Models\SeedEntry;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class SeedEntryTable
{
    /**
     * Returns Filament table columns for SeedEntry
     */
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('common_name')
                ->label('Common Name')
                ->searchable()
                ->sortable()
                ->weight(FontWeight::Bold)
                ->toggleable(),
            Tables\Columns\TextColumn::make('cultivar_name')
                ->label('Cultivar')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('supplier.name')
                ->label('Supplier')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('supplier_sku')
                ->label('Supplier SKU')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('url')
                ->label('Product URL')
                ->searchable()
                ->limit(50)
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\ImageColumn::make('image_url')
                ->label('Image')
                ->circular()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('description')
                ->label('Description')
                ->limit(50)
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('tags')
                ->label('Tags')
                ->badge()
                ->separator(',')
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('variations_count')
                ->counts('variations')
                ->label('Variations')
                ->sortable()
                ->toggleable(),
            static::getStockStatusColumn(),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean()
                ->sortable()
                ->toggleable(),
            static::getUsageStatusColumn(),
        ];
    }

    /**
     * Returns Filament table filters for SeedEntry
     */
    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('common_name')
                ->options(function () {
                    return \App\Models\SeedEntry::whereNotNull('common_name')
                        ->where('common_name', '<>', '')
                        ->distinct()
                        ->orderBy('common_name')
                        ->pluck('common_name', 'common_name')
                        ->toArray();
                })
                ->searchable()
                ->label('Common Name'),
            Tables\Filters\SelectFilter::make('cultivar_name')
                ->options(function () {
                    return \App\Models\SeedEntry::whereNotNull('cultivar_name')
                        ->where('cultivar_name', '<>', '')
                        ->distinct()
                        ->orderBy('cultivar_name')
                        ->pluck('cultivar_name', 'cultivar_name')
                        ->toArray();
                })
                ->searchable()
                ->label('Cultivar'),
            Tables\Filters\SelectFilter::make('supplier')
                ->relationship('supplier', 'name')
                ->searchable()
                ->preload()
                ->label('Supplier'),
            Tables\Filters\SelectFilter::make('is_active')
                ->label('Status')
                ->options([
                    '1' => 'Active',
                    '0' => 'Inactive',
                ]),
            static::getUsageStatusFilter(),
            static::getStockStatusFilter(),
        ];
    }

    /**
     * Returns Filament table actions for SeedEntry
     */
    public static function actions(): array
    {
        return SeedEntryTableActions::actions();
    }

    /**
     * Returns Filament table bulk actions for SeedEntry
     */
    public static function bulkActions(): array
    {
        return SeedEntryTableActions::bulkActions();
    }

    /**
     * Modify query to include relationships
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('variations');
    }

    protected static function getStockStatusColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('stock_status')
            ->label('Stock Status')
            ->getStateUsing(function (SeedEntry $record): string {
                $variations = $record->variations;
                
                if ($variations->isEmpty()) {
                    return 'No Variations';
                }
                
                $inStockCount = $variations->where('is_in_stock', true)->count();
                $totalCount = $variations->count();
                
                if ($inStockCount === 0) {
                    return 'All Out of Stock';
                } elseif ($inStockCount === $totalCount) {
                    return 'All In Stock';
                } else {
                    return "Partial ({$inStockCount}/{$totalCount} in stock)";
                }
            })
            ->badge()
            ->color(fn (string $state): string => match (true) {
                str_contains($state, 'All In Stock') => 'success',
                str_contains($state, 'Partial') => 'warning',
                str_contains($state, 'All Out of Stock') => 'danger',
                str_contains($state, 'No Variations') => 'gray',
                default => 'gray',
            })
            ->tooltip(function (SeedEntry $record): string {
                $variations = $record->variations;
                
                if ($variations->isEmpty()) {
                    return 'This seed entry has no price variations defined.';
                }
                
                $inStock = $variations->where('is_in_stock', true);
                $outOfStock = $variations->where('is_in_stock', false);
                
                $tooltip = "Total variations: {$variations->count()}";
                
                if ($inStock->count() > 0) {
                    $tooltip .= "\n\nIn Stock:\n" . $inStock->pluck('size_description')->join(', ');
                }
                
                if ($outOfStock->count() > 0) {
                    $tooltip .= "\n\nOut of Stock:\n" . $outOfStock->pluck('size_description')->join(', ');
                }
                
                return $tooltip;
            })
            ->sortable(false)
            ->toggleable();
    }

    protected static function getUsageStatusColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('usage_status')
            ->label('Usage Status')
            ->getStateUsing(function (SeedEntry $record): string {
                $issues = app(ValidateSeedEntryDeletionAction::class)->execute($record);
                if (empty($issues)) {
                    return 'Available';
                }
                return 'In Use (' . count($issues) . ' dependencies)';
            })
            ->badge()
            ->color(fn (string $state): string => match (true) {
                str_contains($state, 'Available') => 'success',
                str_contains($state, 'In Use') => 'warning',
                default => 'gray',
            })
            ->tooltip(function (SeedEntry $record): string {
                $issues = app(ValidateSeedEntryDeletionAction::class)->execute($record);
                if (empty($issues)) {
                    return 'This seed entry is not being used and can be safely deleted.';
                }
                return 'This seed entry is in use: ' . implode('; ', $issues);
            })
            ->sortable(false)
            ->toggleable(isToggledHiddenByDefault: true);
    }

    protected static function getUsageStatusFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('usage_status')
            ->label('Usage Status')
            ->options([
                'available' => 'Available for Deletion',
                'in_use' => 'In Use (Cannot Delete)',
            ])
            ->query(function (Builder $query, array $data) {
                if (isset($data['value'])) {
                    if ($data['value'] === 'available') {
                        // Find seed entries that are not in use
                        $query->whereDoesntHave('recipes')
                            ->whereDoesntHave('consumables', function($query) {
                                $query->where('is_active', true);
                            });
                    } elseif ($data['value'] === 'in_use') {
                        // Find seed entries that are in use
                        $query->where(function ($query) {
                            $query->whereHas('recipes')
                                ->orWhereHas('consumables', function($query) {
                                    $query->where('is_active', true);
                                });
                        });
                    }
                }
                return $query;
            });
    }

    protected static function getStockStatusFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('stock_status')
            ->label('Stock Status')
            ->options([
                'all_in_stock' => 'All In Stock',
                'partial_stock' => 'Partial Stock',
                'all_out_of_stock' => 'All Out of Stock',
                'no_variations' => 'No Variations',
            ])
            ->query(function (Builder $query, array $data) {
                if (isset($data['value'])) {
                    switch ($data['value']) {
                        case 'all_in_stock':
                            // Entries where all variations are in stock
                            $query->whereHas('variations')
                                ->whereDoesntHave('variations', function($query) {
                                    $query->where('is_in_stock', false);
                                });
                            break;
                        case 'partial_stock':
                            // Entries with mix of in stock and out of stock
                            $query->whereHas('variations', function($query) {
                                $query->where('is_in_stock', true);
                            })
                            ->whereHas('variations', function($query) {
                                $query->where('is_in_stock', false);
                            });
                            break;
                        case 'all_out_of_stock':
                            // Entries where all variations are out of stock
                            $query->whereHas('variations')
                                ->whereDoesntHave('variations', function($query) {
                                    $query->where('is_in_stock', true);
                                });
                            break;
                        case 'no_variations':
                            // Entries with no variations
                            $query->whereDoesntHave('variations');
                            break;
                    }
                }
                return $query;
            });
    }
}