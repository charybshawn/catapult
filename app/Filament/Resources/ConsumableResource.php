<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\ConsumableResource\Pages\ListConsumables;
use App\Filament\Resources\ConsumableResource\Pages\CreateConsumable;
use App\Filament\Resources\ConsumableResource\Pages\ViewConsumable;
use App\Filament\Resources\ConsumableResource\Pages\EditConsumable;
use App\Filament\Resources\ConsumableResource\Pages\AdjustStock;
use App\Filament\Resources\ConsumableResource\Forms\ConsumableForm;
use App\Filament\Resources\ConsumableResource\Pages;
use App\Filament\Resources\ConsumableResource\Tables\ConsumableTable;
use App\Filament\Resources\ConsumableResource\Tables\ConsumableTableActions;
use App\Models\Consumable;
use App\Models\ConsumableUnit;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Traits\CsvExportAction;

/**
 * Agricultural inventory management interface for consumable supplies.
 * 
 * Manages the complete inventory lifecycle for agricultural consumables including
 * seeds, soil amendments, packaging materials, and growing supplies. Provides
 * comprehensive stock tracking, supplier relationships, and automated reorder
 * notifications for microgreens production operations.
 * 
 * @filament_resource
 * @business_domain Agricultural inventory management and supply chain tracking
 * @workflow_support Stock adjustments, supplier ordering, reorder notifications
 * @related_models Consumable, ConsumableType, ConsumableUnit, Supplier, MasterSeedCatalog
 * @ui_features Bulk operations, CSV export, stock level monitoring, unit conversions
 * @performance Eager loads all relationships, session-persistent filters
 * @delegation_pattern Delegates to ConsumableForm (552 lines), ConsumableTable (318 lines), ConsumableTableActions (95 lines)
 * 
 * Key Agricultural Features:
 * - Seeds: Links to master seed catalog for variety management and growing parameters
 * - Growing Media: Soil mixes, amendments, and growing substrates with batch tracking
 * - Packaging: Containers, labels, and packaging materials with capacity specifications
 * - Supplies: Chemicals, nutrients, tools, and equipment for production operations
 * 
 * Business Operations:
 * - Automated reorder points based on consumption patterns and lead times
 * - Supplier price tracking and historical cost analysis
 * - Unit conversion system supporting metric and imperial measurements
 * - Stock adjustment workflows with audit trails and reason codes
 * - Integration with crop planning for material requirement planning
 * 
 * @architecture Follows Filament Resource Architecture Guide for maintainability
 */
class ConsumableResource extends BaseResource
{
    use CsvExportAction;
    
    /** @var string The Eloquent model class for agricultural consumables */
    protected static ?string $model = Consumable::class;

    /** @var string Navigation icon representing inventory/cube concept */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    
    /** @var string Navigation label for general consumables overview */
    protected static ?string $navigationLabel = 'All Consumables';
    
    /** @var string Navigation group for inventory management */
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    
    /** @var int Lower priority to appear after specialized resources */
    protected static ?int $navigationSort = 10;

    /**
     * Build the Filament form schema for consumable management.
     * 
     * Delegates to ConsumableForm for complex agricultural form logic including
     * seed variety linking, packaging specifications, supplier relationships,
     * and inventory tracking configurations. Supports different consumable types
     * (seeds, soil, packaging, supplies) with conditional field visibility.
     * 
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form with agricultural inventory fields
     * @delegation ConsumableForm::schema() handles 552 lines of form logic
     * @conditional_fields Form adapts based on consumable type selection
     * @relationships Includes supplier, seed catalog, packaging type selections
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components(ConsumableForm::schema());
    }

    /**
     * Build the Filament data table for consumable inventory overview.
     * 
     * Creates a comprehensive table view for agricultural inventory management with
     * advanced filtering, grouping, and export capabilities. Table displays critical
     * inventory metrics including stock levels, reorder points, supplier information,
     * and cost analysis for informed purchasing decisions.
     * 
     * @param Table $table The Filament table builder
     * @return Table Configured table with agricultural inventory features
     * @delegation ConsumableTable handles 318+ lines of complex table logic
     * @performance Eager loads all relationships to prevent N+1 queries
     * @features Stock level indicators, supplier grouping, type filtering
     * @export CSV export includes related supplier and catalog data
     * @persistence Session-persistent filters, search, and column customization
     * @sorting Default alphabetical sort by name for easy browsing
     * @visual Striped rows for improved readability of large inventory lists
     */
    public static function table(Table $table): Table
    {
        $columns = ConsumableTable::columns();
        $tableColumns = array_merge([static::getNameColumn('Name')], array_slice($columns, 1));
        
        return $table
            ->modifyQueryUsing(fn (Builder $query) => ConsumableTable::modifyQuery($query))
            ->columns($tableColumns)
            ->defaultSort('name', 'asc')
            ->filters(ConsumableTable::filters())
            ->groups(ConsumableTable::groups())
            ->recordActions(ConsumableTableActions::actions())
            ->toolbarActions(ConsumableTableActions::bulkActions())
            ->headerActions([
                static::getCsvExportAction(),
            ])
            ->persistFiltersInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->striped();
    }

    /**
     * Define the page routes and classes for consumable resource.
     * 
     * Provides comprehensive page routing for all consumable management operations
     * including specialized stock adjustment workflow separate from standard CRUD.
     * The adjust-stock page enables precise inventory tracking with reason codes,
     * batch references, and audit trails critical for agricultural compliance.
     * 
     * @return array<string, class-string> Page route mappings
     * @special_pages AdjustStock provides dedicated inventory adjustment workflow
     * @routes Standard CRUD routes plus specialized stock management
     * @workflow_support Separate adjustment page maintains inventory audit trails
     */
    public static function getPages(): array
    {
        return [
            'index' => ListConsumables::route('/'),
            'create' => CreateConsumable::route('/create'),
            'view' => ViewConsumable::route('/{record}'),
            'edit' => EditConsumable::route('/{record}/edit'),
            'adjust-stock' => AdjustStock::route('/{record}/adjust-stock'),
        ];
    }
    
    /**
     * Define CSV export columns for agricultural inventory reporting.
     * 
     * Configures comprehensive export data including related supplier information,
     * seed catalog details, and packaging specifications critical for inventory
     * audits, purchasing analysis, and agricultural compliance reporting.
     * 
     * @return array Export column definitions including relationship data
     * @includes Supplier contact info, seed variety details, packaging specs
     * @business_purpose Supports inventory audits and purchasing decisions
     * @compliance Provides data for agricultural traceability requirements
     */
    protected static function getCsvExportColumns(): array
    {
        $autoColumns = static::getColumnsFromSchema();
        
        return static::addRelationshipColumns($autoColumns, [
            'supplier' => ['name', 'email'],
            'masterSeedCatalog' => ['common_name', 'category'],
            'packagingType' => ['name', 'capacity_volume', 'volume_unit'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export for complete data context.
     * 
     * Ensures exported inventory data includes critical business relationships
     * necessary for comprehensive inventory analysis and supply chain management.
     * 
     * @return array<string> Relationship names to eager load for export
     * @relationships Supplier for sourcing, seed catalog for varieties, packaging for specifications
     * @performance Prevents N+1 queries during large export operations
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['supplier', 'masterSeedCatalog', 'packagingType'];
    }

    /**
     * Get compatible units for consumable unit conversion in agricultural contexts.
     * 
     * Retrieves available measurement units compatible with the consumable's base unit
     * for flexible inventory management. Critical for agricultural operations where
     * materials may be purchased in bulk units but consumed in smaller quantities
     * (e.g., seeds bought by pound, used by gram count).
     * 
     * @param Consumable $record The consumable record requiring unit options
     * @return array<string, string> Compatible units as code => display_name pairs
     * @business_context Agricultural materials often require unit conversions (bulk to usage)
     * @categories Weight units (kg, g, oz), volume units (l, ml), count units (unit, piece)
     * @fallback Returns generic "Unit(s)" when no specific unit system defined
     * @usage Used in stock adjustment forms and inventory transaction recording
     */
    public static function getCompatibleUnits(Consumable $record): array
    {
        if (!$record->consumableUnit) {
            return ['unit' => 'Unit(s)'];
        }
        
        // Get compatible units from the same category
        $compatibleUnits = ConsumableUnit::byCategory($record->consumableUnit->category)
            ->pluck('display_name', 'code')
            ->toArray();
        
        return $compatibleUnits;
    }

    /**
     * Get human-readable label for unit codes in agricultural inventory.
     * 
     * Converts technical unit codes to user-friendly labels for display in forms
     * and reports. Essential for clear communication of quantities in agricultural
     * contexts where precision and understanding are critical for crop success.
     * 
     * @param string $unit Unit code from database or form input
     * @return string Human-readable unit label for UI display
     * @agricultural_units Supports common agricultural measurement systems
     * @weight_units Kilograms, grams, ounces for seeds and soil amendments
     * @volume_units Litres, millilitres for liquid fertilizers and treatments
     * @fallback Returns the original unit code if not found in mapping
     * @ui_purpose Ensures consistent, clear unit display across all interfaces
     */
    public static function getUnitLabel(string $unit): string
    {
        $labels = [
            'unit' => 'Unit(s)',
            'kg' => 'Kilograms',
            'g' => 'Grams',
            'oz' => 'Ounces',
            'l' => 'Litre(s)',
            'ml' => 'Milliliters',
        ];
        
        return $labels[$unit] ?? $unit;
    }
}