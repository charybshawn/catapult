<?php

namespace App\Filament\Resources\PriceVariationResource\Tables;

use App\Filament\Traits\CsvExportAction;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * PriceVariation Table Component
 * Extracted from PriceVariationResource table method (lines 363-566)
 * Following Filament Resource Architecture Guide patterns
 * Max 300 lines as per requirements
 */
class PriceVariationTable
{
    use CsvExportAction;

    /**
     * Get all table columns
     */
    public static function columns(): array
    {
        return [
            static::getProductColumn(),
            static::getNameColumn(),
            static::getPackagingTypeColumn(),
            static::getSkuColumn(),
            static::getFillWeightColumn(),
            static::getPriceColumn(),
            static::getIsDefaultColumn(),
            static::getIsGlobalColumn(),
            static::getIsActiveColumn(),
            static::getCreatedAtColumn(),
            static::getUpdatedAtColumn(),
        ];
    }

    /**
     * Get product column
     */
    protected static function getProductColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('product.name')
            ->label('Product')
            ->sortable()
            ->searchable()
            ->placeholder('Global Template');
    }

    /**
     * Get name column
     */
    protected static function getNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('name')
            ->searchable();
    }

    /**
     * Get packaging type column
     */
    protected static function getPackagingTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('packagingType.name')
            ->label('Packaging Type')
            ->sortable()
            ->placeholder('Package-Free')
            ->badge()
            ->color(fn ($state) => $state ? 'primary' : 'gray');
    }

    /**
     * Get SKU column
     */
    protected static function getSkuColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('sku')
            ->label('SKU/UPC')
            ->searchable();
    }

    /**
     * Get fill weight column with complex formatting
     */
    protected static function getFillWeightColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('fill_weight')
            ->label('Weight/Qty')
            ->formatStateUsing(function ($state, $record) {
                return static::formatFillWeight($state, $record);
            })
            ->sortable();
    }

    /**
     * Get price column
     */
    protected static function getPriceColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('price')
            ->money('USD')
            ->sortable();
    }

    /**
     * Get is default column
     */
    protected static function getIsDefaultColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('is_default')
            ->label('Default')
            ->boolean();
    }

    /**
     * Get is global column
     */
    protected static function getIsGlobalColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('is_global')
            ->label('Template')
            ->boolean();
    }

    /**
     * Get is active column
     */
    protected static function getIsActiveColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('is_active')
            ->label('Active')
            ->boolean();
    }

    /**
     * Get created at column
     */
    protected static function getCreatedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Get updated at column
     */
    protected static function getUpdatedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('updated_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Get all table filters
     */
    public static function filters(): array
    {
        return [
            static::getProductFilter(),
            static::getPackagingTypeFilter(),
            static::getIsDefaultFilter(),
            static::getIsGlobalFilter(),
            static::getIsActiveFilter(),
        ];
    }

    /**
     * Get product filter
     */
    protected static function getProductFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('product')
            ->relationship('product', 'name')
            ->searchable()
            ->preload()
            ->label('Product');
    }

    /**
     * Get packaging type filter
     */
    protected static function getPackagingTypeFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('packagingType')
            ->relationship('packagingType', 'name')
            ->searchable()
            ->preload()
            ->label('Packaging Type');
    }

    /**
     * Get is default filter
     */
    protected static function getIsDefaultFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('is_default')
            ->label('Default Price');
    }

    /**
     * Get is global filter
     */
    protected static function getIsGlobalFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('is_global')
            ->label('Global Templates');
    }

    /**
     * Get is active filter
     */
    protected static function getIsActiveFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('is_active');
    }

    /**
     * Get table actions
     */
    public static function actions(): array
    {
        return PriceVariationTableActions::getRowActions();
    }

    /**
     * Get bulk actions
     */
    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                PriceVariationTableActions::getDeleteBulkAction(),
                PriceVariationTableActions::getActivateBulkAction(),
                PriceVariationTableActions::getDeactivateBulkAction(),
            ]),
        ];
    }

    /**
     * Get header actions
     */
    public static function headerActions(): array
    {
        return [
            static::getCsvExportAction(),
        ];
    }

    /**
     * Format fill weight display based on context
     */
    protected static function formatFillWeight($state, $record): string
    {
        if ($record->is_global && !$state) {
            return 'Template';
        }
        
        if (!$state) {
            return 'N/A';
        }
        
        // Handle package-free variations (no packaging type)
        if (!$record->packagingType) {
            // Determine format based on variation name
            $name = strtolower($record->name);
            if (str_contains($name, 'tray') || str_contains($name, 'live')) {
                return $state . ' tray' . ($state != 1 ? 's' : '');
            }
            if (str_contains($name, 'bulk') || str_contains($name, 'lb') || str_contains($name, 'pound')) {
                return $state . 'g (' . number_format($state / 454, 2) . 'lb)';
            }
            if (str_contains($name, 'each') || str_contains($name, 'unit') || str_contains($name, 'piece')) {
                return $state . ' unit' . ($state != 1 ? 's' : '');
            }
            // Default for package-free
            return $state . ' units';
        }
        
        // Special formatting for different packaging types
        if ($record->packagingType) {
            $packagingName = strtolower($record->packagingType->name);
            if (str_contains($packagingName, 'live') || str_contains($packagingName, 'tray')) {
                return $state . ' tray' . ($state != 1 ? 's' : '');
            }
            if (str_contains($packagingName, 'bulk')) {
                return $state . 'g (' . number_format($state / 454, 2) . 'lb)';
            }
        }
        
        return $state . 'g';
    }

    /**
     * Define CSV export columns for Price Variations
     */
    protected static function getCsvExportColumns(): array
    {
        $autoColumns = static::getColumnsFromSchema();
        
        return static::addRelationshipColumns($autoColumns, [
            'product' => ['name', 'base_price'],
            'packagingType' => ['name', 'capacity_volume', 'volume_unit'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['product', 'packagingType'];
    }
    
    /**
     * Get the model class for CSV export
     */
    protected static function getModelClass(): string
    {
        return \App\Models\PriceVariation::class;
    }

    /**
     * Get table configuration with persistence
     */
    public static function configure(Tables\Table $table): Tables\Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->columns(static::columns())
            ->filters(static::filters())
            ->actions(static::actions())
            ->bulkActions(static::bulkActions())
            ->headerActions(static::headerActions());
    }
}