<?php

namespace App\Filament\Resources\ConsumableResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class ConsumableTable
{
    /**
     * Modify the base query for the table
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'supplier',
            'consumableType',
            'consumableUnit',
            'masterSeedCatalog',
            'masterCultivar',
            'packagingType'
        ]);
    }

    /**
     * Get table columns for ConsumableResource
     */
    public static function columns(): array
    {
        return array_merge(
            static::getCommonTableColumns(),
            static::getSeedSpecificColumns(),
            static::getPackagingSpecificColumns()
        );
    }

    /**
     * Get table filters for ConsumableResource
     */
    public static function filters(): array
    {
        return array_merge(
            static::getTypeFilterToggles(),
            static::getCommonFilters()
        );
    }

    /**
     * Get table groups for ConsumableResource
     */
    public static function groups(): array
    {
        return static::getCommonGroups();
    }

    /**
     * Get common table columns for all consumables
     */
    protected static function getCommonTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('Name')
                ->searchable()
                ->sortable()
                ->toggleable()
                ->color('primary'),
            TextColumn::make('consumableType.name')
                ->label('Type')
                ->badge()
                ->color(function ($record): string {
                    if (!$record->consumableType) return 'gray';
                    return match ($record->consumableType->code) {
                        'packaging' => 'success',
                        'label' => 'info', 
                        'soil' => 'warning',
                        'seed' => 'primary',
                        default => 'gray',
                    };
                })
                ->toggleable(),
            TextColumn::make('supplier.name')
                ->label('Supplier')
                ->searchable()
                ->sortable()
                ->toggleable(),
            TextColumn::make('lot_no')
                ->label('Lot/Batch#')
                ->searchable()
                ->sortable()
                ->toggleable(),
            TextColumn::make('current_stock')
                ->label('Available Quantity')
                ->getStateUsing(fn ($record) => $record ? max(0, $record->initial_stock - $record->consumed_quantity) : 0)
                ->numeric()
                ->sortable(query: fn (Builder $query, string $direction): Builder => 
                    $query->orderByRaw("(initial_stock - consumed_quantity) {$direction}")
                )
                ->formatStateUsing(function ($state, $record) {
                    if (!$record) return $state;
                    
                    // For seed consumables, show actual remaining amount
                    if ($record->consumableType && $record->consumableType->isSeed()) {
                        $remaining = max(0, $record->total_quantity - $record->consumed_quantity);
                        return "{$remaining} {$record->quantity_unit}";
                    }
                    
                    // For other types, use the consumable unit symbol
                    $displayUnit = $record->consumableUnit ? $record->consumableUnit->symbol : 'unit(s)';
                    
                    return "{$state} {$displayUnit}";
                })
                ->size('sm')
                ->toggleable(),
            static::getActiveStatusBadgeColumn(),
            ...static::getTimestampColumns(),
        ];
    }
    
    /**
     * Get seed-specific columns
     */
    protected static function getSeedSpecificColumns(): array
    {
        return [
            TextColumn::make('remaining_seed')
                ->label('Remaining Seed')
                ->getStateUsing(function ($record) {
                    if (!$record || !$record->consumableType || !$record->consumableType->isSeed()) return null;
                    
                    // Calculate actual remaining seed: total_quantity - consumed_quantity
                    return max(0, $record->total_quantity - $record->consumed_quantity);
                })
                ->formatStateUsing(function ($state, $record) {
                    if (!$record || !$record->consumableType || !$record->consumableType->isSeed() || $state === null) return '-';
                    
                    return "{$state} {$record->quantity_unit}";
                })
                ->numeric()
                ->sortable(query: fn (Builder $query, string $direction): Builder => 
                    $query->whereHas('consumableType', fn ($q) => $q->where('code', 'seed'))
                          ->orderByRaw("(total_quantity - consumed_quantity) {$direction}")
                )
                ->size('sm')
                ->toggleable(),
            TextColumn::make('percentage_remaining')
                ->label('% Remaining')
                ->getStateUsing(function ($record) {
                    if (!$record || !$record->consumableType || !$record->consumableType->isSeed()) return null;
                    
                    // For seeds, calculate percentage based on original purchase vs current amount
                    $originalAmount = $record->initial_stock * $record->quantity_per_unit;
                    $currentAmount = $record->total_quantity;
                    
                    if ($originalAmount <= 0) return null;
                    
                    $percentage = ($currentAmount / $originalAmount) * 100;
                    return round($percentage, 1);
                })
                ->formatStateUsing(function ($state) {
                    if ($state === null) return '-';
                    return "{$state}%";
                })
                ->badge()
                ->color(fn ($state): string => match (true) {
                    $state === null => 'gray',
                    $state <= 10 => 'danger',
                    $state <= 25 => 'warning',
                    $state <= 50 => 'info',
                    default => 'success',
                })
                ->sortable(query: fn (Builder $query, string $direction): Builder => 
                    $query->whereHas('consumableType', fn ($q) => $q->where('code', 'seed'))
                          ->whereNotNull('total_quantity')
                          ->where('total_quantity', '>', 0)
                          ->where('initial_stock', '>', 0)
                          ->where('quantity_per_unit', '>', 0)
                          ->orderByRaw("(total_quantity / (initial_stock * quantity_per_unit) * 100) {$direction}")
                )
                ->size('sm')
                ->toggleable(),
        ];
    }
    
    /**
     * Get packaging-specific columns
     */
    protected static function getPackagingSpecificColumns(): array
    {
        return [
            TextColumn::make('packagingType.capacity_volume')
                ->label('Capacity')
                ->getStateUsing(function ($record) {
                    if ($record->packagingType) {
                        return "{$record->packagingType->capacity_volume} {$record->packagingType->volume_unit}";
                    }
                    return null;
                })
                ->sortable()
                ->toggleable(),
        ];
    }
    
    /**
     * Get common filters for all consumables
     */
    protected static function getCommonFilters(): array
    {
        return [
            ...static::getInventoryFilters(),
            static::getActiveStatusFilter(),
        ];
    }
    
    /**
     * Get type-specific filter toggles
     */
    protected static function getTypeFilterToggles(): array
    {
        return [
            Filter::make('seeds')
                ->label('Seeds')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'seed')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['seeds'] ?? false) ? 'Seeds' : null),
                
            Filter::make('soil')
                ->label('Soil & Growing Media')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'soil')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['soil'] ?? false) ? 'Soil & Growing Media' : null),
                
            Filter::make('packaging')
                ->label('Packaging')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'packaging')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['packaging'] ?? false) ? 'Packaging' : null),
                
            Filter::make('labels')
                ->label('Labels')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'label')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['labels'] ?? false) ? 'Labels' : null),
                
            Filter::make('other')
                ->label('Other')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'other')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['other'] ?? false) ? 'Other' : null),
        ];
    }
    
    /**
     * Get common grouping options
     */
    protected static function getCommonGroups(): array
    {
        return [
            Group::make('name')
                ->label('Name')
                ->collapsible(),
            Group::make('consumableType.name')
                ->label('Type')
                ->collapsible(),
            Group::make('supplier.name')
                ->label('Supplier')
                ->collapsible(),
        ];
    }

    /**
     * Get default sort for the table
     */
    public static function getDefaultSort(): array
    {
        return ['name', 'asc'];
    }

    /**
     * Placeholder methods for trait functionality
     * These would normally come from traits but we can't access them here
     */

    protected static function getActiveStatusBadgeColumn(): Column
    {
        return IconColumn::make('is_active')
            ->label('Active')
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('gray');
    }

    protected static function getTimestampColumns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->label('Updated')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected static function getInventoryFilters(): array
    {
        return [
            TernaryFilter::make('low_stock')
                ->label('Low Stock')
                ->placeholder('All items')
                ->trueLabel('Low stock only')
                ->falseLabel('Exclude low stock')
                ->queries(
                    true: fn (Builder $query) => $query->whereRaw('(initial_stock - consumed_quantity) <= restock_threshold'),
                    false: fn (Builder $query) => $query->whereRaw('(initial_stock - consumed_quantity) > restock_threshold'),
                ),
        ];
    }

    protected static function getActiveStatusFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_active')
            ->label('Active Status');
    }
}