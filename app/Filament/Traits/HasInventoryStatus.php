<?php

namespace App\Filament\Traits;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Tables;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

/**
 * Has Inventory Status Trait
 * 
 * Specialized inventory status management for agricultural Filament resources.
 * Provides comprehensive inventory tracking UI components including stock levels,
 * reorder thresholds, and bulk inventory operations for agricultural supplies.
 * 
 * @filament_trait Inventory status management for agricultural resources
 * @agricultural_use Inventory tracking for seeds, soil, packaging, and agricultural consumables
 * @inventory_features Stock status badges, reorder alerts, bulk inventory operations
 * @business_context Agricultural supply chain inventory management and reorder automation
 * 
 * Key features:
 * - Agricultural inventory status visualization (In Stock, Out of Stock, Reorder Needed)
 * - Current stock calculations with agricultural unit display
 * - Inventory filtering by stock levels and reorder status
 * - Bulk inventory operations (add stock, consume stock)
 * - Restock threshold and quantity management for agricultural supplies
 * 
 * @package App\Filament\Traits
 * @author Shawn
 * @since 2024
 */
trait HasInventoryStatus
{
    /**
     * Get inventory status column for agricultural consumables.
     * 
     * @agricultural_context Inventory status display for agricultural supplies with color-coded alerts
     * @param string $label Column display label
     * @return TextColumn Status badge column with agricultural inventory color coding
     * @status_types In Stock (success), Reorder Needed (warning), Out of Stock (danger)
     */
    public static function getInventoryStatusColumn(string $label = 'Status'): TextColumn
    {
        return TextColumn::make('status')
            ->label($label)
            ->badge()
            ->color(fn ($record): string => $record ? match (true) {
                $record->isOutOfStock() => 'danger',
                $record->needsRestock() => 'warning',
                default => 'success',
            } : 'gray')
            ->formatStateUsing(fn ($record): string => $record ? match (true) {
                $record->isOutOfStock() => 'Out of Stock',
                $record->needsRestock() => 'Reorder Needed',
                default => 'In Stock',
            } : 'Unknown')
            ->toggleable();
    }
    
    /**
     * Get current stock column with agricultural unit display.
     * 
     * @agricultural_context Available stock display for agricultural consumables with proper units
     * @param string $label Column display label
     * @return TextColumn Current stock column with agricultural unit formatting (e.g., "150.5 kg")
     * @calculation Displays (initial_stock - consumed_quantity) with agricultural unit symbols
     */
    public static function getCurrentStockColumn(string $label = 'Available Quantity'): TextColumn
    {
        return TextColumn::make('current_stock')
            ->label($label)
            ->getStateUsing(fn ($record) => $record ? max(0, $record->initial_stock - $record->consumed_quantity) : 0)
            ->numeric()
            ->sortable(query: fn (Builder $query, string $direction): Builder => 
                $query->orderByRaw("(initial_stock - consumed_quantity) {$direction}")
            )
            ->formatStateUsing(function ($state, $record) {
                if (!$record) return $state;
                
                $displayUnit = $record->consumableUnit ? $record->consumableUnit->symbol : 'unit(s)';
                
                return "{$state} {$displayUnit}";
            })
            ->toggleable();
    }
    
    /**
     * Get inventory filters
     */
    public static function getInventoryFilters(): array
    {
        return [
            Filter::make('needs_restock')
                ->label('Needs Restock')
                ->query(fn (Builder $query) => $query->whereRaw('(total_quantity - consumed_quantity) <= restock_threshold'))
                ->toggle(),
                
            Filter::make('out_of_stock')
                ->label('Out of Stock')
                ->query(fn (Builder $query) => $query->whereRaw('(total_quantity - consumed_quantity) <= 0'))
                ->toggle(),
                
            Filter::make('low_stock')
                ->label('Low Stock')
                ->query(fn (Builder $query) => $query->whereRaw('(total_quantity - consumed_quantity) > 0 AND (total_quantity - consumed_quantity) <= restock_threshold'))
                ->toggle(),
        ];
    }
    
    /**
     * Get inventory bulk actions for agricultural supply management.
     * 
     * @agricultural_context Bulk inventory operations for agricultural consumables and supplies
     * @return array Bulk actions for adding and consuming agricultural inventory
     * @operations Add Stock (receiving supplies), Consume Stock (using in production)
     */
    public static function getInventoryBulkActions(): array
    {
        return [
            BulkAction::make('bulk_add_stock')
                ->label('Add Stock')
                ->icon('heroicon-o-plus')
                ->form([
                    TextInput::make('amount')
                        ->label('Amount to Add')
                        ->numeric()
                        ->step(0.001)
                        ->minValue(0.001)
                        ->required()
                        ->default(10),
                ])
                ->action(function ($records, array $data) {
                    foreach ($records as $record) {
                        $record->add((float) $data['amount']);
                    }
                })
                ->deselectRecordsAfterCompletion(),
                
            BulkAction::make('bulk_consume_stock')
                ->label('Consume Stock')
                ->icon('heroicon-o-minus')
                ->form([
                    TextInput::make('amount')
                        ->label('Amount to Consume')
                        ->numeric()
                        ->step(0.001)
                        ->minValue(0.001)
                        ->required()
                        ->default(1),
                ])
                ->action(function ($records, array $data) {
                    foreach ($records as $record) {
                        $record->deduct((float) $data['amount']);
                    }
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }
    
    /**
     * Get restock settings fields for forms
     */
    public static function getRestockSettingsFields(): array
    {
        return [
            TextInput::make('restock_threshold')
                ->label('Restock Threshold')
                ->helperText('When stock falls below this number, reorder')
                ->numeric()
                ->required()
                ->default(5),
                
            TextInput::make('restock_quantity')
                ->label('Restock Quantity')
                ->helperText('How many to order when restocking')
                ->numeric()
                ->required()
                ->default(10),
        ];
    }
    
    /**
     * Get inventory settings section for agricultural supply forms.
     * 
     * @agricultural_context Inventory configuration section for agricultural consumables
     * @return Section Form section with restock threshold and quantity settings
     * @settings Restock threshold and restock quantity for agricultural supply management
     */
    public static function getInventorySection(): Section
    {
        return Section::make('Inventory Settings')
            ->schema([
                Grid::make(2)
                    ->schema(static::getRestockSettingsFields()),
            ]);
    }
}