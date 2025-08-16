<?php

namespace App\Filament\Resources\Consumables\Components;

use App\Filament\Traits\CsvExportAction;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasInventoryStatus;
use App\Filament\Traits\HasStandardActions;
use App\Filament\Traits\HasStatusBadge;
use App\Filament\Traits\HasTimestamps;
use App\Models\Consumable;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Consumable Table Components Trait
 *
 * Provides reusable table components for Filament consumable resources.
 * This trait encapsulates common table columns, filters, groups, and configurations
 * that can be shared across different consumable resource implementations.
 *
 * Architecture:
 * - Aggregates multiple Filament traits for consistent behavior
 * - Provides type-specific column sets (seeds, packaging)
 * - Implements business logic for inventory calculations
 * - Maintains color-coded status indicators
 *
 * Usage:
 * Include this trait in Filament resource table classes that need
 * standardized consumable table functionality.
 *
 * @see \App\Filament\Resources\Consumables\SeedResource
 * @see \App\Filament\Resources\ConsumableResource
 */
trait ConsumableTableComponents
{
    use CsvExportAction;
    use HasActiveStatus;
    use HasInventoryStatus;
    use HasStandardActions;
    use HasStatusBadge;
    use HasTimestamps;

    /**
     * Get common table columns for all consumables.
     *
     * Provides the standard set of columns used across all consumable types,
     * including name, type, supplier, lot number, stock calculations, and status indicators.
     * Implements dynamic color coding based on consumable type and inventory levels.
     *
     * @return array<\Filament\Tables\Columns\Column> Array of configured table columns
     */
    public static function getCommonTableColumns(): array
    {
        return [
            \App\Filament\Resources\BaseResource::getNameColumn('Name')
                ->url(fn (Consumable $record): string => static::getUrl('edit', ['record' => $record]))
                ->color('primary'),
            Tables\Columns\TextColumn::make('consumableType.name')
                ->label('Type')
                ->badge()
                // Color-coded badges for different consumable types
                // packaging=green, label=blue, soil=yellow, seed=purple, other=gray
                ->color(function ($record): string {
                    if (! $record->consumableType) {
                        return 'gray';
                    }

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
                // Calculate remaining stock: initial purchase minus consumed amount
                ->getStateUsing(fn ($record) => $record ? max(0, $record->initial_stock - $record->consumed_quantity) : 0)
                ->numeric()
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw("(initial_stock - consumed_quantity) {$direction}")
                )
                ->formatStateUsing(function ($state, $record) {
                    if (! $record) {
                        return $state;
                    }

                    // For seed consumables, show actual remaining amount with seed-specific units
                    if ($record->consumableType && $record->consumableType->isSeed()) {
                        $remaining = max(0, $record->total_quantity - $record->consumed_quantity);

                        return "{$remaining} {$record->quantity_unit}";
                    }

                    // For other types, use the consumable unit symbol (pcs, kg, etc.)
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
     * Get seed-specific columns.
     *
     * Provides specialized columns for seed consumables, including remaining seed
     * calculations and percentage remaining with color-coded status indicators.
     * These columns use different calculation methods than standard consumables
     * due to seeds being measured in weight units rather than discrete quantities.
     *
     * @return array<\Filament\Tables\Columns\Column> Array of seed-specific table columns
     */
    public static function getSeedSpecificColumns(): array
    {
        return [

            Tables\Columns\TextColumn::make('remaining_seed')
                ->label('Remaining Seed')
                ->getStateUsing(function ($record) {
                    if (! $record || ! $record->consumableType || ! $record->consumableType->isSeed()) {
                        return null;
                    }

                    // Calculate actual remaining seed weight: total_quantity - consumed_quantity
                    // Uses weight-based calculation specific to seed inventory tracking
                    return max(0, $record->total_quantity - $record->consumed_quantity);
                })
                ->formatStateUsing(function ($state, $record) {
                    if (! $record || ! $record->consumableType || ! $record->consumableType->isSeed() || $state === null) {
                        return '-';
                    }

                    return "{$state} {$record->quantity_unit}";
                })
                ->numeric()
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'seed'))
                    ->orderByRaw("(total_quantity - consumed_quantity) {$direction}")
                )
                ->size('sm')
                ->toggleable(),
            Tables\Columns\TextColumn::make('percentage_remaining')
                ->label('% Remaining')
                ->getStateUsing(function ($record) {
                    if (! $record || ! $record->consumableType || ! $record->consumableType->isSeed()) {
                        return null;
                    }

                    // Calculate percentage based on original purchase weight vs current weight
                    // Formula: (current_amount / original_amount) * 100
                    // Original amount = initial_stock (units) * quantity_per_unit (weight per unit)
                    $originalAmount = $record->initial_stock * $record->quantity_per_unit;
                    $currentAmount = $record->total_quantity;

                    if ($originalAmount <= 0) {
                        return null;
                    }

                    $percentage = ($currentAmount / $originalAmount) * 100;

                    return round($percentage, 1);
                })
                ->formatStateUsing(function ($state) {
                    if ($state === null) {
                        return '-';
                    }

                    return "{$state}%";
                })
                ->badge()
                // Color-coded inventory status: red≤10%, yellow≤25%, blue≤50%, green>50%
                ->color(fn ($state): string => match (true) {
                    $state === null => 'gray',
                    $state <= 10 => 'danger',    // Critical: needs immediate reorder
                    $state <= 25 => 'warning',   // Low: should reorder soon
                    $state <= 50 => 'info',      // Medium: monitor closely
                    default => 'success',        // Good: adequate stock
                })
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->whereHas('consumableType', fn ($q) => $q->where('code', 'seed'))
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
     * Get packaging-specific columns.
     *
     * Provides specialized columns for packaging consumables, including
     * capacity information and volume measurements specific to containers,
     * trays, and other packaging materials.
     *
     * @return array<\Filament\Tables\Columns\Column> Array of packaging-specific table columns
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
     * Get common filters for all consumables.
     *
     * Provides standard filtering options that apply to all consumable types,
     * including inventory status filters and active/inactive status toggles.
     *
     * @return array<\Filament\Tables\Filters\BaseFilter> Array of common table filters
     */
    public static function getCommonFilters(): array
    {
        return [
            ...static::getInventoryFilters(),
            static::getActiveStatusFilter(),
        ];
    }

    /**
     * Get type-specific filter toggles.
     *
     * Provides toggle filters for each consumable type (seeds, soil, packaging, labels, other).
     * These filters allow users to quickly view specific categories of consumables
     * and can be combined with other filters for refined searches.
     *
     * @return array<\Filament\Tables\Filters\Filter> Array of type-based toggle filters
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
     * Get common grouping options.
     *
     * Provides standard grouping functionality for organizing consumables
     * by name, type, or supplier. All groups are collapsible to improve
     * table readability when dealing with large datasets.
     *
     * @return array<\Filament\Tables\Grouping\Group> Array of table grouping options
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
     * Configure common table settings.
     *
     * Applies standard table configuration including default behaviors
     * from HasStandardActions trait and sets up the column toggle trigger
     * with consistent styling and labeling.
     *
     * @param  \Filament\Tables\Table  $table  The table instance to configure
     * @return \Filament\Tables\Table  The configured table instance
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
