<?php

namespace App\Filament\Resources\PriceVariationResource\Tables;

use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\BaseResource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\BulkActionGroup;
use App\Models\PriceVariation;
use Filament\Tables\Table;
use App\Filament\Traits\CsvExportAction;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * PriceVariation Table Component for Agricultural Product Pricing Display
 * 
 * Provides comprehensive table functionality for displaying agricultural product
 * price variations with specialized formatting for weight-based pricing, packaging
 * information, and agricultural business context. Supports complex filtering,
 * bulk operations, and CSV export for microgreens business operations.
 * 
 * @filament_component Table schema builder for PriceVariationResource
 * @business_domain Agricultural product pricing with packaging and weight display
 * @architectural_pattern Extracted from PriceVariationResource following Filament Resource Architecture Guide
 * @complexity_target Max 300 lines through delegation to specialized action classes
 * 
 * @display_features Weight formatting, packaging integration, pricing unit display
 * @agricultural_focus Microgreens pricing with per-gram, per-package calculations
 * @filtering_support Product, packaging, pricing type, status filters for agricultural context
 * 
 * @export_functionality CSV export with agricultural product and packaging relationship data
 * @bulk_operations Activate/deactivate, delete with agricultural business considerations
 * @related_classes PriceVariationTableActions for action definitions and complex operations
 */
class PriceVariationTable
{
    use CsvExportAction;

    /**
     * Get all table columns for agricultural price variation display.
     * 
     * Assembles comprehensive column set including product relationships,
     * pricing information, packaging details, and status indicators.
     * Designed for agricultural business users managing complex pricing structures.
     * 
     * @return array Complete set of table columns for price variation management
     * @agricultural_display Product names, packaging types, weight/quantity formatting
     * @business_context Default/template indicators, active status, pricing information
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
     * Get product column for agricultural product identification.
     * 
     * Displays linked agricultural product name with placeholder for global templates.
     * Essential for identifying which microgreen product each pricing variation applies to.
     * 
     * @return TextColumn Sortable and searchable product name with template handling
     * @agricultural_context Shows microgreen product names or "Global Template" indicator
     * @business_logic Null product_id displays as "Global Template" for reusable pricing
     */
    protected static function getProductColumn(): TextColumn
    {
        return TextColumn::make('product.name')
            ->label('Product')
            ->sortable()
            ->searchable()
            ->placeholder('Global Template');
    }

    protected static function getNameColumn(): TextColumn
    {
        return TextColumn::make('name')
            ->label('Name')
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    /**
     * Get packaging type column for agricultural container display.
     * 
     * Shows packaging container information with badge styling and placeholder
     * for package-free variations. Critical for understanding agricultural product
     * presentation and pricing context in microgreens operations.
     * 
     * @return TextColumn Packaging type with badge styling and package-free handling
     * @agricultural_packaging Clamshells, bulk containers, or "Package-Free" indicator
     * @visual_design Badge color coding - primary for packaged, gray for package-free
     */
    protected static function getPackagingTypeColumn(): TextColumn
    {
        return TextColumn::make('packagingType.name')
            ->label('Packaging Type')
            ->sortable()
            ->placeholder('Package-Free')
            ->badge()
            ->color(fn ($state) => $state ? 'primary' : 'gray');
    }

    /**
     * Get SKU column
     */
    protected static function getSkuColumn(): TextColumn
    {
        return TextColumn::make('sku')
            ->label('SKU/UPC')
            ->searchable();
    }

    /**
     * Get fill weight column with agricultural measurement formatting.
     * 
     * Provides intelligent weight/quantity display based on packaging type and
     * variation context. Handles agricultural measurement standards including
     * metric conversions and specialized formatting for different packaging types.
     * 
     * @return TextColumn Weight/quantity column with agricultural formatting logic
     * @agricultural_measurements Grams, pounds, trays, units with context-aware display
     * @business_logic Template, package-free, and packaged variations formatted differently
     * @measurement_conversions Gram-to-pound conversions for agricultural bulk sales
     */
    protected static function getFillWeightColumn(): TextColumn
    {
        return TextColumn::make('fill_weight')
            ->label('Weight/Qty')
            ->formatStateUsing(function ($state, $record) {
                return static::formatFillWeight($state, $record);
            })
            ->sortable();
    }

    /**
     * Get price column
     */
    protected static function getPriceColumn(): TextColumn
    {
        return TextColumn::make('price')
            ->money('USD')
            ->sortable();
    }

    /**
     * Get is default column
     */
    protected static function getIsDefaultColumn(): IconColumn
    {
        return IconColumn::make('is_default')
            ->label('Default')
            ->boolean();
    }

    /**
     * Get is global column
     */
    protected static function getIsGlobalColumn(): IconColumn
    {
        return IconColumn::make('is_global')
            ->label('Template')
            ->boolean();
    }

    /**
     * Get is active column
     */
    protected static function getIsActiveColumn(): IconColumn
    {
        return IconColumn::make('is_active')
            ->label('Active')
            ->boolean();
    }

    /**
     * Get created at column
     */
    protected static function getCreatedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Get updated at column
     */
    protected static function getUpdatedAtColumn(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Get all table filters for agricultural pricing management.
     * 
     * Provides comprehensive filtering options including product selection,
     * packaging types, and status indicators. Essential for managing large
     * numbers of price variations in agricultural business operations.
     * 
     * @return array Complete set of filters for price variation management
     * @agricultural_filtering Product-based, packaging-based, and status-based filtering
     * @business_utility Helps users find specific pricing variations quickly
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
    protected static function getProductFilter(): SelectFilter
    {
        return SelectFilter::make('product')
            ->relationship('product', 'name')
            ->searchable()
            ->preload()
            ->label('Product');
    }

    /**
     * Get packaging type filter
     */
    protected static function getPackagingTypeFilter(): SelectFilter
    {
        return SelectFilter::make('packagingType')
            ->relationship('packagingType', 'name')
            ->searchable()
            ->preload()
            ->label('Packaging Type');
    }

    /**
     * Get is default filter
     */
    protected static function getIsDefaultFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_default')
            ->label('Default Price');
    }

    /**
     * Get is global filter
     */
    protected static function getIsGlobalFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_global')
            ->label('Global Templates');
    }

    /**
     * Get is active filter
     */
    protected static function getIsActiveFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_active');
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
            BulkActionGroup::make([
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
     * Format fill weight display based on agricultural packaging context.
     * 
     * Implements complex formatting logic for displaying weights and quantities
     * based on packaging type, variation context, and agricultural measurement standards.
     * Handles global templates, package-free variations, and packaged products differently.
     * 
     * @param mixed $state Fill weight value from database
     * @param object $record PriceVariation record with packaging relationships
     * @return string Formatted weight/quantity display with appropriate units
     * 
     * @agricultural_formatting Trays, grams with pound conversions, units based on context
     * @business_logic Global templates show "Template", package-free uses variation name hints
     * @measurement_standards Metric primary with imperial conversions for agricultural sales
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
        return PriceVariation::class;
    }

    /**
     * Get table configuration with persistence
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->columns(static::columns())
            ->filters(static::filters())
            ->recordActions(static::actions())
            ->toolbarActions(static::bulkActions())
            ->headerActions(static::headerActions());
    }
}