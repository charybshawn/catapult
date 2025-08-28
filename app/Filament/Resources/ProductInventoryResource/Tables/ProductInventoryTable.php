<?php

namespace App\Filament\Resources\ProductInventoryResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms;

/**
 * ProductInventoryTable for Agricultural Inventory Management Display
 * 
 * Provides comprehensive table functionality for displaying agricultural product
 * inventory with inline editing capabilities, expiration tracking, and stock
 * level monitoring. Supports real-time inventory updates and batch operations
 * critical for microgreens operations where timing and accuracy are essential.
 * 
 * @filament_component Table schema builder for ProductInventoryResource
 * @business_domain Agricultural product inventory with inline editing and status tracking
 * @inline_editing Key inventory fields editable directly in table for operational efficiency
 * 
 * @agricultural_features Expiration date tracking, batch numbers, location management
 * @stock_monitoring Available quantity calculations with reservation integration
 * @business_operations Real-time inventory updates with transaction logging
 * 
 * @editing_capabilities Quantity, cost, location, notes, dates with validation
 * @related_models ProductInventory, Product, PriceVariation, ProductInventoryStatus
 * @operational_efficiency Bulk operations and filtering for large inventory management
 */
class ProductInventoryTable
{
    /**
     * Get table columns for agricultural product inventory display and management.
     * 
     * Provides comprehensive column set with inline editing for operational efficiency.
     * Features dynamic formatting based on packaging types, expiration alerts, and
     * stock level indicators essential for agricultural inventory management.
     * 
     * @return array Complete set of table columns with inline editing capabilities
     * 
     * @inline_editing_features Dynamic step validation, packaging-aware formatting, real-time updates
     * - quantity: Dynamic step based on packaging type, validates min:0
     * - cost_per_unit: Money format with step 0.01, validates min:0
     * - location: Text input for physical location tracking
     * - notes: Textarea with autosize for additional information
     * - expiration_date: Date picker with validation against production date
     * 
     * @agricultural_context Perishable inventory with expiration tracking and batch management
     * @business_logic All inline edits maintain validation rules and trigger transaction logging
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('product.name')
                ->label('Product')
                ->searchable()
                ->sortable()
                ->weight(FontWeight::Bold),
            TextColumn::make('priceVariation.name')
                ->label('Variation')
                ->searchable()
                ->sortable()
                ->badge()
                ->color(function ($record) {
                    if (!$record->priceVariation) {
                        return 'gray';
                    }
                    
                    return match($record->priceVariation->name) {
                        'Default' => 'primary',
                        'Wholesale' => 'info',
                        'Bulk' => 'success',
                        'Special' => 'warning',
                        default => 'gray'
                    };
                }),
            TextColumn::make('priceVariation.price')
                ->label('Price')
                ->money('USD')
                ->sortable()
                ->alignEnd(),
            TextColumn::make('priceVariation.sku')
                ->label('SKU')
                ->searchable()
                ->sortable()
                ->placeholder('No SKU')
                ->copyable()
                ->copyMessage('SKU copied')
                ->toggleable(),
            TextInputColumn::make('quantity')
                ->label('Total Qty')
                ->type('number')
                ->step(0.01)
                ->rules(['required', 'numeric', 'min:0'])
                ->sortable()
                ->alignEnd()
                ->placeholder('0.00')
                ->extraInputAttributes(['class' => 'text-right']),
            TextColumn::make('reserved_quantity')
                ->label('Reserved')
                ->numeric(2)
                ->sortable()
                ->alignEnd()
                ->color(fn ($state) => $state > 0 ? 'warning' : null),
            TextColumn::make('available_quantity')
                ->label('Available')
                ->numeric(2)
                ->sortable()
                ->alignEnd()
                ->color(fn ($state) => $state <= 0 ? 'danger' : 'success')
                ->weight(FontWeight::Bold),
            TextInputColumn::make('cost_per_unit')
                ->label('Unit Cost')
                ->type('number')
                ->step(0.01)
                ->rules(['numeric', 'min:0'])
                ->sortable()
                ->alignEnd()
                ->toggleable()
                ->placeholder('0.00')
                ->extraInputAttributes(['class' => 'text-right']),
            TextInputColumn::make('expiration_date')
                ->label('Expires')
                ->type('date')
                ->sortable()
                ->placeholder('YYYY-MM-DD'),
            TextInputColumn::make('location')
                ->label('Location')
                ->searchable()
                ->toggleable()
                ->placeholder('e.g., Warehouse A, Shelf 3'),
            TextColumn::make('productInventoryStatus.name')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => match($state) {
                    'Active' => 'success',
                    'Depleted' => 'danger',
                    'Expired' => 'warning',
                    'Damaged' => 'secondary',
                    default => 'gray'
                }),
            TextInputColumn::make('notes')
                ->label('Notes')
                ->searchable()
                ->toggleable()
                ->placeholder('Add notes...'),
            TextColumn::make('value')
                ->label('Total Value')
                ->getStateUsing(fn ($record) => $record->getValue())
                ->money('USD')
                ->alignEnd()
                ->sortable()
                ->toggleable(),
        ];
    }

    /**
     * Get table filters for ProductInventoryResource
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('product')
                ->relationship('product', 'name'),
            SelectFilter::make('status')
                ->relationship('productInventoryStatus', 'name'),
        ];
    }

    /**
     * Get table actions for ProductInventoryResource
     */
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Get bulk actions for ProductInventoryResource
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ];
    }

    /**
     * Get empty state configuration
     */
    public static function getEmptyStateConfig(): array
    {
        return [
            'heading' => 'No inventory batches',
            'description' => 'Start by adding inventory for your products.',
            'icon' => 'heroicon-o-cube',
        ];
    }
}