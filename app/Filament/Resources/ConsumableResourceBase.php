<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\ConsumableResource\Pages\ListConsumables;
use App\Filament\Resources\ConsumableResource\Pages\CreateConsumable;
use App\Filament\Resources\ConsumableResource\Pages\ViewConsumable;
use App\Filament\Resources\ConsumableResource\Pages\EditConsumable;
use App\Filament\Resources\ConsumableResource\Pages\AdjustStock;
use App\Filament\Resources\ConsumableResource\Pages;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\ConsumableUnit;
use App\Models\PackagingType;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use Illuminate\Support\Facades\Log;
use App\Filament\Tables\Components\Common as TableCommon;
use App\Filament\Traits\CsvExportAction;
use App\Filament\Traits\HasActiveStatus;
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStatusBadge;
use App\Filament\Traits\HasStandardActions;
use App\Filament\Traits\HasInventoryStatus;
use App\Filament\Resources\Consumables\Components\ConsumableFormComponents;
use App\Filament\Resources\Consumables\Components\ConsumableTableComponents;

/**
 * Abstract Base Resource for Agricultural Consumable Inventory Management.
 * 
 * Provides unified foundation for managing different types of agricultural consumables
 * including seeds, packaging materials, soils, and growing media. Implements standardized
 * inventory tracking, supplier management, and cost analysis patterns across all
 * consumable categories in the microgreens production system.
 *
 * @package App\Filament\Resources
 * @extends BaseResource
 * 
 * **Business Context:**
 * - **Inventory Management**: Real-time stock levels, consumption tracking, reorder alerts
 * - **Supplier Coordination**: Multi-supplier pricing, delivery tracking, procurement optimization
 * - **Cost Analysis**: Unit costs, usage patterns, waste reduction metrics
 * - **Production Planning**: Stock availability for production scheduling
 * 
 * **Agricultural Applications:**
 * - **Seeds**: Variety catalog, germination rates, seasonal availability
 * - **Packaging**: Container types, food safety compliance, capacity planning
 * - **Growing Media**: Soil blends, nutrient content, pH considerations
 * - **Supplies**: Tools, equipment, maintenance materials
 * 
 * **Architecture Pattern:**
 * - Abstract base providing common consumable functionality
 * - Type-specific subclasses (SeedResource, PackagingResource, SoilResource)
 * - Shared inventory management and supplier relationship patterns
 * - Extensible schema system for category-specific attributes
 * 
 * **Key Features:**
 * 1. **Polymorphic Type System**: Handles multiple consumable categories seamlessly
 * 2. **Inventory Automation**: Automatic consumption tracking and reorder alerts
 * 3. **Supplier Integration**: Multi-supplier pricing and procurement workflows
 * 4. **Cost Optimization**: Unit cost analysis and waste reduction reporting
 * 5. **Production Integration**: Stock availability for production planning
 * 
 * @uses ConsumableFormComponents For standardized form field patterns
 * @uses ConsumableTableComponents For inventory display and filtering
 * @uses HasInventoryStatus For stock level monitoring and alerts
 */
abstract class ConsumableResourceBase extends BaseResource
{
    use CsvExportAction;
    use HasActiveStatus;
    use HasTimestamps;
    use HasStatusBadge;
    use HasStandardActions;
    use HasInventoryStatus;
    use ConsumableFormComponents;
    use ConsumableTableComponents;
    
    protected static ?string $model = Consumable::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 1;

    /**
     * Get the consumable type code for this resource subclass.
     * 
     * Each consumable resource subclass must define its specific type code
     * (e.g., 'seed', 'packaging', 'soil') to properly filter and categorize
     * inventory items in the agricultural management system.
     * 
     * @return string Consumable type code for database filtering
     * @abstract_method Must be implemented by all consumable resource subclasses
     * @business_context Different consumable types have distinct management requirements
     */
    abstract public static function getConsumableTypeCode(): string;
    
    /**
     * Get type-specific form schema fields for the consumable category.
     * 
     * Allows each consumable type to define specialized form fields beyond the
     * common inventory management fields. For example, seeds need variety and
     * germination fields, while packaging needs capacity and material fields.
     * 
     * @param bool $isEditMode Whether form is in edit mode (affects field availability)
     * @return array Type-specific form field configurations
     * @abstract_method Implemented by subclasses for specialized data collection
     * @agricultural_customization Each type has unique agricultural characteristics
     */
    abstract protected static function getTypeSpecificFormSchema(bool $isEditMode): array;
    
    /**
     * Get type-specific table columns for the consumable category.
     * 
     * Defines columns that are specific to the consumable type for optimal data
     * display. Seeds might show variety and germination rate, while packaging
     * shows capacity and food safety certifications.
     * 
     * @return array Type-specific table column configurations
     * @abstract_method Implemented by subclasses for optimal data presentation
     * @ui_customization Tailored display for different agricultural materials
     */
    abstract protected static function getTypeSpecificTableColumns(): array;
    
    /**
     * Get inventory management schema specific to the consumable type.
     * 
     * Defines inventory-specific fields that vary by consumable category.
     * Seeds may need lot numbers and expiration dates, while packaging
     * requires capacity and material specifications for inventory planning.
     * 
     * @param bool $isEditMode Whether form is in edit mode
     * @return array Inventory management field configurations
     * @abstract_method Implemented by subclasses for specialized inventory tracking
     * @inventory_management Type-specific stock and usage tracking requirements
     */
    abstract protected static function getInventoryDetailsSchema(bool $isEditMode): array;

    public static function form(Schema $schema): Schema
    {
        // Determine if we're in edit mode
        $isEditMode = $schema->getOperation() === 'edit';
        
        return $schema
            ->components(static::getFormSchema($isEditMode));
    }
    
    /**
     * Build complete form schema combining common and type-specific components.
     * 
     * Constructs a comprehensive form by merging standardized consumable management
     * fields with type-specific customizations. Automatically injects the consumable
     * type ID and organizes fields into logical sections for optimal user experience.
     * 
     * **Form Structure:**
     * 1. **Basic Information**: Name, type, supplier, active status
     * 2. **Inventory Details**: Stock levels, units, reorder thresholds
     * 3. **Cost Information**: Pricing, supplier terms, cost analysis
     * 4. **Additional Information**: Notes, specifications, attachments
     * 
     * @param bool $isEditMode Whether form is in edit mode (affects field behavior)
     * @return array Complete form schema with all sections and fields
     * 
     * @form_architecture Combines base, type-specific, and inventory schemas
     * @ui_organization Logical section grouping for complex agricultural data
     * @business_workflow Supports both creation and inventory adjustment workflows
     */
    protected static function getFormSchema(bool $isEditMode): array
    {
        return [
            Section::make('Basic Information')
                ->schema(array_merge(
                    [
                        // Hidden field for consumable type (set by sub-resource)
                        Hidden::make('consumable_type_id')
                            ->default(fn() => ConsumableType::findByCode(static::getConsumableTypeCode())?->id)
                            ->dehydrated(),
                    ],
                    static::getTypeSpecificFormSchema($isEditMode),
                    [
                        static::getActiveStatusField()
                            ->columnSpanFull(),
                    ]
                ))
                ->columns(2),
            
            Section::make('Inventory Details')
                ->schema(static::getInventoryDetailsSchema($isEditMode))
                ->columns(3),
            
            static::getCostInformationSection()
                ->collapsed(),
                
            static::getAdditionalInformationSection()
                ->collapsed(),
        ];
    }

    /**
     * Configure comprehensive inventory management table for consumable resources.
     * 
     * Creates a sophisticated table interface optimized for agricultural inventory
     * management with automatic stock level monitoring, supplier relationship display,
     * and type-specific filtering. Implements intelligent sorting by stock levels
     * to prioritize items needing attention.
     * 
     * **Table Features:**
     * - **Smart Sorting**: Default sort by remaining stock (lowest first)
     * - **Relationship Loading**: Eager loads suppliers, types, and specifications
     * - **Type Filtering**: Automatically filters by consumable type code
     * - **Inventory Actions**: Bulk stock adjustments and status updates
     * - **Export Capabilities**: CSV export with relationship data included
     * 
     * **Agricultural Optimization:**
     * - Low stock items appear first for immediate attention
     * - Supplier information readily available for reordering
     * - Type-specific columns show relevant agricultural characteristics
     * - Bulk operations for efficient inventory management
     * 
     * @param Table $table Filament table instance to configure
     * @return Table Fully configured inventory management table
     * 
     * @inventory_focus Prioritizes stock management and reorder workflows
     * @performance_optimization Eager loading prevents N+1 query issues
     * @agricultural_ui Specialized for farm inventory management patterns
     */
    public static function table(Table $table): Table
    {
        return static::configureCommonTable($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'supplier',
                'consumableType',
                'consumableUnit',
                'masterSeedCatalog',
                'packagingType'
            ])->whereHas('consumableType', fn ($q) => $q->where('code', static::getConsumableTypeCode())))
            ->columns(array_merge(
                static::getCommonTableColumns(),
                static::getTypeSpecificTableColumns()
            ))
            ->defaultSort(function (Builder $query) {
                return $query->orderByRaw('(initial_stock - consumed_quantity) ASC');
            })
            ->filters(array_merge(
                static::getCommonFilters(),
                static::getTypeSpecificFilters()
            ))
            ->groups(static::getCommonGroups())
            ->recordActions(static::getStandardTableActions())
            ->toolbarActions([
                BulkActionGroup::make([
                    ...static::getStandardBulkActions(),
                    ...static::getInventoryBulkActions(),
                ]),
            ])
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }
    
    /**
     * Get type-specific filters for table
     */
    protected static function getTypeSpecificFilters(): array
    {
        return [];
    }

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
     * Define CSV export columns for Consumables
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
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['supplier', 'masterSeedCatalog', 'packagingType'];
    }

    /**
     * Retrieve compatible measurement units for agricultural inventory conversions.
     * 
     * Identifies units within the same measurement category (weight, volume, count)
     * that can be used for inventory adjustments and conversions. Essential for
     * handling different packaging sizes and supplier unit variations.
     * 
     * **Unit Categories:**
     * - **Weight**: grams, kilograms, ounces, pounds
     * - **Volume**: milliliters, liters, fluid ounces
     * - **Count**: units, packages, cases, bulk quantities
     * 
     * @param Consumable $record The consumable requiring unit compatibility check
     * @return array Compatible units with codes and display names
     * 
     * @agricultural_measurement Handles diverse packaging and measurement standards
     * @inventory_flexibility Supports multiple unit types per consumable category
     * @business_integration Accommodates supplier variation in packaging units
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
     * Convert unit codes to human-readable labels for agricultural inventory display.
     * 
     * Provides standardized, user-friendly unit labels for inventory management
     * interfaces. Supports both metric and imperial measurements commonly used
     * in agricultural operations and supplier communications.
     * 
     * **Supported Unit Types:**
     * - **Weight Units**: Grams, kilograms, ounces (seeds, soil, amendments)
     * - **Volume Units**: Milliliters, liters (liquid nutrients, treatments)
     * - **Count Units**: Individual units, packages, cases (containers, tools)
     * 
     * @param string $unit Unit code from database or form input
     * @return string Human-readable unit label for UI display
     * 
     * @ui_presentation Ensures consistent unit display across all interfaces
     * @agricultural_standards Supports common farm measurement conventions
     * @internationalization Handles both metric and imperial unit systems
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