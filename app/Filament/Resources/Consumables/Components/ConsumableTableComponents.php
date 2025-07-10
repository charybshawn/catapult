<?php

namespace App\Filament\Resources\Consumables\Components;

use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ConsumableResource;
use App\Filament\Traits\CsvExportAction;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStatusBadge;
use App\Filament\Traits\HasStandardActions;
use App\Filament\Traits\HasInventoryStatus;

trait ConsumableTableComponents
{
    use CsvExportAction;
    use HasActiveStatus;
    use HasTimestamps;
    use HasStatusBadge;
    use HasStandardActions;
    use HasInventoryStatus;

    /**
     * Get common table columns for all consumables
     */
    public static function getCommonTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Name')
                ->searchable()
                ->sortable()
                ->toggleable()
                ->url(fn (Consumable $record): string => static::getUrl('edit', ['record' => $record]))
                ->color('primary'),
            Tables\Columns\TextColumn::make('consumableType.name')
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
            Tables\Columns\TextColumn::make('supplier.name')
                ->label('Supplier')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('lot_no')
                ->label('Lot/Batch#')
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('current_stock')
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
            static::getInventoryStatusColumn(),
            static::getActiveStatusBadgeColumn(),
            ...static::getTimestampColumns(),
        ];
    }
    
    /**
     * Get seed-specific columns
     */
    public static function getSeedSpecificColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('masterSeedCatalog.common_name')
                ->label('Master Catalog')
                ->getStateUsing(function ($record) {
                    if ($record->consumableType && $record->consumableType->isSeed() && $record->masterSeedCatalog) {
                        return $record->masterSeedCatalog->common_name;
                    }
                    return null;
                })
                ->searchable()
                ->sortable()
                ->toggleable(),
            Tables\Columns\TextColumn::make('remaining_seed')
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
            Tables\Columns\TextColumn::make('percentage_remaining')
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
    public static function getPackagingSpecificColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('packagingType.capacity_volume')
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
    public static function getCommonFilters(): array
    {
        return [
            ...static::getInventoryFilters(),
            static::getActiveStatusFilter(),
        ];
    }
    
    /**
     * Get type-specific filter toggles
     */
    public static function getTypeFilterToggles(): array
    {
        return [
            Tables\Filters\Filter::make('seeds')
                ->label('Seeds')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'seed')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['seeds'] ?? false) ? 'Seeds' : null),
                
            Tables\Filters\Filter::make('soil')
                ->label('Soil & Growing Media')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'soil')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['soil'] ?? false) ? 'Soil & Growing Media' : null),
                
            Tables\Filters\Filter::make('packaging')
                ->label('Packaging')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'packaging')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['packaging'] ?? false) ? 'Packaging' : null),
                
            Tables\Filters\Filter::make('labels')
                ->label('Labels')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'label')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['labels'] ?? false) ? 'Labels' : null),
                
            Tables\Filters\Filter::make('other')
                ->label('Other')
                ->query(fn (Builder $query) => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'other')))
                ->toggle()
                ->indicateUsing(fn (array $data) => ($data['other'] ?? false) ? 'Other' : null),
        ];
    }
    
    /**
     * Get common grouping options
     */
    public static function getCommonGroups(): array
    {
        return [
            Tables\Grouping\Group::make('name')
                ->label('Name')
                ->collapsible(),
            Tables\Grouping\Group::make('consumableType.name')
                ->label('Type')
                ->collapsible(),
            Tables\Grouping\Group::make('supplier.name')
                ->label('Supplier')
                ->collapsible(),
        ];
    }
    
    /**
     * Configure common table settings
     */
    public static function configureCommonTable(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->toggleColumnsTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label('Columns')
                    ->icon('heroicon-m-view-columns')
            );
    }
}