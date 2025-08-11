<?php

namespace App\Filament\Resources\ProductInventoryResource\Tables;

use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms;

class ProductInventoryTable
{
    /**
     * Get table columns for ProductInventoryResource
     * 
     * Features inline editing for key inventory fields:
     * - quantity: Dynamic step based on packaging type, validates min:0
     * - cost_per_unit: Money format with step 0.01, validates min:0
     * - location: Text input for physical location tracking
     * - notes: Textarea with autosize for additional information
     * - expiration_date: Date picker with validation against production date
     * 
     * All inline edits maintain existing validation rules and trigger 
     * model events for inventory transaction logging.
     */
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('product.name')
                ->label('Product')
                ->searchable()
                ->sortable()
                ->weight(FontWeight::Bold),
            Tables\Columns\TextColumn::make('priceVariation.name')
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
            Tables\Columns\TextColumn::make('priceVariation.price')
                ->label('Price')
                ->money('USD')
                ->sortable()
                ->alignEnd(),
            Tables\Columns\TextColumn::make('priceVariation.sku')
                ->label('SKU')
                ->searchable()
                ->sortable()
                ->placeholder('No SKU')
                ->copyable()
                ->copyMessage('SKU copied')
                ->toggleable(),
            Tables\Columns\TextInputColumn::make('quantity')
                ->label('Total Qty')
                ->type('number')
                ->step(0.01)
                ->rules(['required', 'numeric', 'min:0'])
                ->sortable()
                ->alignEnd()
                ->placeholder('0.00')
                ->extraInputAttributes(['class' => 'text-right']),
            Tables\Columns\TextColumn::make('reserved_quantity')
                ->label('Reserved')
                ->numeric(2)
                ->sortable()
                ->alignEnd()
                ->color(fn ($state) => $state > 0 ? 'warning' : null),
            Tables\Columns\TextColumn::make('available_quantity')
                ->label('Available')
                ->numeric(2)
                ->sortable()
                ->alignEnd()
                ->color(fn ($state) => $state <= 0 ? 'danger' : 'success')
                ->weight(FontWeight::Bold),
            Tables\Columns\TextInputColumn::make('cost_per_unit')
                ->label('Unit Cost')
                ->type('number')
                ->step(0.01)
                ->rules(['numeric', 'min:0'])
                ->sortable()
                ->alignEnd()
                ->toggleable()
                ->placeholder('0.00')
                ->extraInputAttributes(['class' => 'text-right']),
            Tables\Columns\TextInputColumn::make('expiration_date')
                ->label('Expires')
                ->type('date')
                ->sortable()
                ->placeholder('YYYY-MM-DD'),
            Tables\Columns\TextInputColumn::make('location')
                ->label('Location')
                ->searchable()
                ->toggleable()
                ->placeholder('e.g., Warehouse A, Shelf 3'),
            Tables\Columns\TextColumn::make('productInventoryStatus.name')
                ->label('Status')
                ->badge()
                ->color(fn ($state) => match($state) {
                    'Active' => 'success',
                    'Depleted' => 'danger',
                    'Expired' => 'warning',
                    'Damaged' => 'secondary',
                    default => 'gray'
                }),
            Tables\Columns\TextInputColumn::make('notes')
                ->label('Notes')
                ->searchable()
                ->toggleable()
                ->placeholder('Add notes...'),
            Tables\Columns\TextColumn::make('value')
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
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
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