<?php

namespace App\Filament\Resources\ProductInventoryResource\Tables;

use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;

class ProductInventoryTable
{
    /**
     * Get table columns for ProductInventoryResource
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
            Tables\Columns\TextColumn::make('quantity')
                ->label('Total Qty')
                ->numeric(2)
                ->sortable()
                ->alignEnd(),
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
            Tables\Columns\TextColumn::make('cost_per_unit')
                ->label('Unit Cost')
                ->money('USD')
                ->sortable()
                ->alignEnd()
                ->toggleable(),
            Tables\Columns\TextColumn::make('expiration_date')
                ->label('Expires')
                ->date()
                ->sortable()
                ->color(fn ($state) => $state && $state <= now()->addDays(30) ? 'danger' : null)
                ->icon(fn ($state) => $state && $state <= now()->addDays(30) ? 'heroicon-o-exclamation-triangle' : null),
            Tables\Columns\TextColumn::make('location')
                ->label('Location')
                ->searchable()
                ->toggleable(),
            Tables\Columns\BadgeColumn::make('productInventoryStatus.name')
                ->label('Status')
                ->color(fn ($state) => match($state) {
                    'Active' => 'success',
                    'Depleted' => 'danger',
                    'Expired' => 'warning',
                    'Damaged' => 'secondary',
                    default => 'gray'
                }),
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