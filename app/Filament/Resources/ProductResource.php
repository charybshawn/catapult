<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use Filament\Schemas\Components\Component;
use App\Filament\Resources\BaseResource;
use App\Filament\Resources\ProductResource\Forms\ProductForm;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\Tables\ProductTable;
use App\Filament\Traits\CsvExportAction;
use App\Models\Product;
use Filament\Forms;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filament resource for managing agricultural products including seed varieties,
 * product mixes, and packaging configurations for microgreens production.
 *
 * This resource serves as the core interface for product catalog management in the
 * agricultural microgreens business, handling both single-variety products (linked
 * to specific seed catalogs) and complex product mixes (multiple varieties with
 * percentage distributions).
 *
 * @filament_resource Manages Product entities with agricultural context
 * @business_domain Agricultural product catalog and inventory management
 * @related_models Product, MasterSeedCatalog, ProductMix, PriceVariation, Category
 * @workflow_support Order planning, inventory tracking, crop plan generation
 * 
 * @agricultural_concepts
 * - Single varieties: Products with one specific seed type for growing
 * - Product mixes: Blends of multiple varieties with defined percentages
 * - Price variations: Different packaging sizes and customer pricing tiers
 * - Crop planning: Integration with planting schedules and harvest projections
 * 
 * @filament_features
 * - Reactive form fields for variety selection (single OR mix, never both)
 * - Dynamic price variation management with global template system
 * - CSV export with relationship data for inventory planning
 * - Agricultural workflow integration (crop plans, order simulation)
 * 
 * @ui_workflow
 * 1. Create product with basic information and category
 * 2. Assign either single variety OR product mix (mutually exclusive)
 * 3. Configure price variations using global templates or custom pricing
 * 4. Set visibility and availability status for customer-facing systems
 * 5. Link to growing recipes for production planning
 * 
 * @business_rules
 * - Products must have either master_seed_catalog_id OR product_mix_id, never both
 * - At least one active price variation required for order processing
 * - Wholesale pricing calculated as percentage discount from retail prices
 * - Products linked to active crop plans cannot be deleted
 * 
 * @performance_considerations
 * - Eager loads relationships (category, masterSeedCatalog, productMix, priceVariations)
 * - Uses chunked queries for bulk operations on large product catalogs
 * - Caches pricing calculations for order simulation workflows
 */
class ProductResource extends BaseResource
{
    use CsvExportAction;
    
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Products';
    protected static string | \UnitEnum | null $navigationGroup = 'Products & Inventory';
    protected static ?int $navigationSort = 1;

    /**
     * Define the form schema for product creation and editing.
     *
     * Delegates to ProductForm class to maintain separation of concerns and
     * support the architectural requirement of keeping main resource files
     * under 150 lines. The form handles complex agricultural product workflows
     * including variety selection, pricing strategy, and packaging configuration.
     *
     * @param Schema $schema Filament schema builder instance
     * @return Schema Complete form schema with agricultural product fields
     * @agricultural_workflow Supports single variety OR product mix assignment
     * @business_context Form validates agricultural business rules (variety exclusivity)
     * @delegation ProductForm::schema() contains the detailed field definitions
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components(ProductForm::schema());
    }

    /**
     * Configure the table display for product management.
     *
     * Creates a comprehensive product listing with agricultural context including
     * variety information, packaging availability, and inventory status. The table
     * supports complex filtering and bulk operations required for farm management.
     *
     * @param Table $table Filament table builder instance
     * @return Table Configured table with agricultural product columns and actions
     * 
     * @table_features
     * - Variety type display (single variety name or product mix)
     * - Available packaging shown as visual badges
     * - Category filtering for agricultural product organization
     * - Status toggles (active, visible in store) for workflow management
     * 
     * @performance_optimization
     * - Eager loads relationships via ProductTable::modifyQuery()
     * - Efficient packaging display without N+1 queries
     * - CSV export optimized for agricultural planning workflows
     * 
     * @business_operations
     * - Clone products for similar variety creation
     * - Bulk status updates for seasonal availability changes
     * - Deletion validation prevents removal of products with active crops
     * 
     * @delegation ProductTable class handles detailed column and action definitions
     */
    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn (Builder $query) => ProductTable::modifyQuery($query))
            ->columns([
                static::getNameColumn(),
                ...array_slice(ProductTable::columns(), 1), // Skip the first column (name) and use the rest
                ...static::getTimestampColumns(),
            ])
            ->filters(ProductTable::filters())
            ->recordActions(ProductTable::actions())
            ->toolbarActions(ProductTable::bulkActions())
            ->headerActions([
                static::getCsvExportAction(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    /**
     * Define CSV export columns optimized for agricultural product planning.
     *
     * Automatically detects core product fields and includes key agricultural
     * relationships (category, variety information, product mix details) that
     * are essential for inventory management and crop planning workflows.
     *
     * @return array Column mappings for CSV export with agricultural context
     * @agricultural_data Includes variety names, cultivar information, mix compositions
     * @business_use Supports external inventory systems and crop planning tools
     * @relationship_inclusion Exports related data for comprehensive product catalogs
     */
    protected static function getCsvExportColumns(): array
    {
        // Get automatically detected columns from database schema
        $autoColumns = static::getColumnsFromSchema();
        
        // Add relationship columns
        return static::addRelationshipColumns($autoColumns, [
            'category' => ['name'],
            'masterSeedCatalog' => ['common_name', 'cultivars'],
            'productMix' => ['name'],
        ]);
    }
    
    /**
     * Define agricultural relationships to include in CSV exports.
     *
     * Specifies which product relationships should be eagerly loaded and
     * included in export data to provide complete agricultural context for
     * external planning and inventory management systems.
     *
     * @return array Relationship names for CSV export eager loading
     * @agricultural_context Includes variety, mix, and category information
     * @performance_optimization Prevents N+1 queries during large exports
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['category', 'masterSeedCatalog', 'productMix'];
    }

    /**
     * Get view panels for comprehensive product information display.
     *
     * Provides detailed agricultural product information organized into logical
     * sections for variety details, pricing strategies, and inventory status.
     * Each panel focuses on specific aspects of agricultural product management.
     *
     * @return array Associative array of panel configurations
     * @agricultural_display Shows variety information, seed inventory, supplier details
     * @business_context Displays pricing variations, packaging options, availability
     * @delegation ProductForm::getPanels() handles detailed panel construction
     */
    public static function getPanels(): array
    {
        return ProductForm::getPanels();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
    
    /**
     * Get simplified form schema for single-page display contexts.
     *
     * Returns the same comprehensive form schema but optimized for contexts
     * where the full product form needs to be displayed in a single view
     * rather than across multiple tabs or sections.
     *
     * @return array Form field configuration for single-page display
     * @ui_context Used in modal forms or simplified creation workflows
     * @agricultural_workflow Maintains full variety selection and pricing capabilities
     * @delegation ProductForm::getSinglePageFormSchema() provides implementation
     */
    public static function getSinglePageFormSchema(): array
    {
        return ProductForm::getSinglePageFormSchema();
    }

    /**
     * Get price variation management component with agricultural pricing context.
     *
     * Provides an interactive interface for applying global price variation templates
     * to products, supporting complex agricultural pricing strategies including
     * different packaging sizes, customer tiers, and seasonal pricing adjustments.
     *
     * @return Component Price variation selection and management interface
     * @agricultural_pricing Supports packaging-based pricing (different container sizes)
     * @business_workflow Template system enables consistent pricing across similar products
     * @ui_interaction Modal-based template selection with preview and application
     * @delegation ProductForm::getPriceVariationSelectionField() handles implementation
     */
    public static function getPriceVariationSelectionField(): Component
    {
        return ProductForm::getPriceVariationSelectionField();
    }

    
} 