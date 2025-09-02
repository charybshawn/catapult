<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\RecipeResource\Pages\ListRecipes;
use App\Filament\Resources\RecipeResource\Pages\CreateRecipe;
use App\Filament\Resources\RecipeResource\Pages\EditRecipe;
use App\Filament\Resources\RecipeResource\Forms\RecipeForm;
use App\Filament\Resources\RecipeResource\Pages;
use App\Filament\Resources\RecipeResource\Tables\RecipeTable;
use App\Models\Recipe;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\LogOptions;

/**
 * Agricultural recipe management interface for microgreens production parameters.
 * 
 * Manages comprehensive growing recipes that define complete production workflows
 * for different seed varieties and cultivars. Each recipe specifies precise
 * timing, environmental conditions, and resource requirements for consistent,
 * high-quality microgreens production from seed to harvest.
 * 
 * @filament_resource
 * @business_domain Agricultural production recipe management and standardization
 * @workflow_support Recipe creation, growing parameter optimization, production planning
 * @related_models Recipe, MasterSeedCatalog, MasterCultivar, Consumable, CropStage
 * @ui_features Recipe comparison, bulk recipe operations, growing parameter validation
 * @production_integration Direct integration with crop creation and batch planning
 * @activity_logging Complete audit trail for recipe modifications and optimizations
 * 
 * Agricultural Recipe Components:
 * - Seed Variety: Links to master seed catalog for botanical specifications
 * - Cultivar Selection: Specific variety characteristics and growing requirements
 * - Seed Lot Tracking: Batch-specific parameters based on seed lot performance
 * - Growing Media: Soil and substrate specifications for optimal growth
 * - Environmental Parameters: Temperature, humidity, and lighting requirements
 * 
 * Production Workflow Definition:
 * - Soaking Phase: Pre-germination seed preparation with timing specifications
 * - Germination Stage: Initial sprouting period with controlled conditions
 * - Blackout Period: Controlled darkness for stem elongation and root development
 * - Light Exposure: Final growth phase under full spectrum lighting
 * - Harvest Timing: Optimal harvest window for peak quality and yield
 * 
 * Quality Control Features:
 * - Yield Expectations: Target harvest weights for production planning
 * - Seed Density Calculations: Optimal seeding rates for tray utilization
 * - Timeline Validation: Ensures realistic and achievable growing schedules
 * - Resource Requirements: Consumable materials needed for recipe execution
 * - Performance Tracking: Historical data for recipe optimization
 * 
 * Business Operations:
 * - Production Standardization: Consistent results across different crops
 * - Resource Planning: Accurate material requirements for inventory management
 * - Scheduling Integration: Recipe timelines drive crop planning and order fulfillment
 * - Quality Assurance: Documented procedures for consistent product quality
 * - Cost Management: Resource consumption tracking for pricing optimization
 * 
 * @delegation Delegates to RecipeForm and RecipeTable for modular architecture
 * @activity_tracking Comprehensive logging of recipe changes for traceability
 */
class RecipeResource extends BaseResource
{
    /** @var string The Eloquent model class for agricultural recipes */
    protected static ?string $model = Recipe::class;

    /** @var string Navigation icon representing recipe/beaker/scientific concept */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-beaker';
    
    /** @var string Navigation label for recipe management */
    protected static ?string $navigationLabel = 'Recipes';
    
    /** @var string Navigation group for production-related resources */
    protected static string | \UnitEnum | null $navigationGroup = 'Production';
    
    /** @var int High priority navigation position for production workflow */
    protected static ?int $navigationSort = 1;
    
    /**
     * Enable navigation registration for primary production workflow.
     * 
     * Recipes are fundamental to production operations and require easy access
     * for recipe creation, modification, and reference during crop planning.
     * High visibility in navigation supports efficient production management.
     * 
     * @return bool Always true - recipes are core to production workflow
     * @workflow_priority Primary navigation for production team access
     * @usage_pattern Frequently accessed during crop planning and production setup
     */
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    /**
     * Build the Filament form schema for agricultural recipe management.
     * 
     * Delegates to RecipeForm for complex recipe creation and editing logic
     * including seed variety selection, growing parameter specification, and
     * resource requirement calculations. Form provides comprehensive input
     * validation and real-time feedback for recipe optimization.
     * 
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form with agricultural recipe management fields
     * @delegation RecipeForm::schema() handles complex recipe logic and validations
     * @variety_selection Dynamic cultivar loading based on seed variety selection
     * @parameter_validation Ensures realistic and achievable growing timelines
     * @resource_integration Links to consumables for material requirement tracking
     * @yield_calculation Automatic calculations for expected harvest quantities
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components(RecipeForm::schema())->columns(1);
    }

    /**
     * Build the Filament data table for recipe overview and comparison.
     * 
     * Creates comprehensive recipe listing with key growing parameters, resource
     * requirements, and performance metrics. Table design facilitates recipe
     * comparison, selection for crop planning, and identification of optimization
     * opportunities for production efficiency improvements.
     * 
     * @param Table $table The Filament table builder
     * @return Table Configured table with agricultural recipe management features
     * @delegation RecipeTable handles complex column definitions and actions
     * @performance Query optimization for recipe catalog browsing
     * @comparison Multi-column view enables recipe parameter comparison
     * @sorting Alphabetical default for easy recipe location and reference
     * @bulk_operations Recipe-specific actions plus standard active status management
     * @filtering Advanced filters for recipe selection by variety, timing, or resource
     */
    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn (Builder $query) => RecipeTable::modifyQuery($query))
            ->columns([
                static::getNameColumn(),
                ...array_slice(RecipeTable::columns(), 1), // Skip the first column (name) and use the rest
            ])
            ->defaultSort('name', 'asc')
            ->filters(RecipeTable::filters())
            ->recordActions(RecipeTable::actions())
            ->toolbarActions([
                BulkActionGroup::make([
                    // Recipe-specific bulk actions from RecipeTable
                    ...RecipeTable::getBulkActions(),
                    // Standard active status bulk actions from BaseResource
                    ...static::getActiveStatusBulkActions(),
                ]),
            ]);
    }

    /**
     * Define relationship managers for recipe resource.
     * 
     * No relationship managers configured as recipe relationships are managed
     * through their respective specialized resources. This maintains clean
     * separation of concerns and prevents UI complexity in the recipe
     * management workflow focused on parameter optimization.
     * 
     * @return array<class-string> Empty array - relationships managed in dedicated interfaces
     * @design_pattern Focused recipe interface without relationship complexity
     * @workflow Recipe management concentrates on growing parameters and optimization
     * @relationships Crops and stages accessible through dedicated production interfaces
     */
    public static function getRelations(): array
    {
        return [
            // No relation managers needed
        ];
    }

    /**
     * Define the page routes and classes for recipe resource.
     * 
     * Provides streamlined recipe management workflow focused on creation and
     * editing of growing parameters. No separate view page as edit interface
     * provides comprehensive access to all recipe data and parameter adjustment
     * capabilities essential for production optimization.
     * 
     * @return array<string, class-string> Page route mappings
     * @routes Create and edit workflow optimized for recipe development
     * @workflow Edit-focused interface supports iterative recipe optimization
     * @production_focus Streamlined workflow for frequent recipe adjustments
     */
    public static function getPages(): array
    {
        return [
            'index' => ListRecipes::route('/'),
            'create' => CreateRecipe::route('/create'),
            'edit' => EditRecipe::route('/{record}/edit'),
        ];
    }

    /**
     * Configure activity logging options for recipe change tracking.
     * 
     * Comprehensive logging of recipe modifications supports production quality
     * control, troubleshooting, and optimization analysis. Critical for maintaining
     * traceability of growing parameter changes and their impact on crop
     * performance and yield outcomes.
     * 
     * @return LogOptions Configured activity logging for recipe changes
     * @traceability Complete audit trail of recipe parameter modifications
     * @quality_control Change tracking supports production issue investigation
     * @optimization Historical data enables recipe performance analysis
     * @compliance Maintains documentation for agricultural quality standards
     * @efficiency Only logs changed fields to minimize storage overhead
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 
                'common_name',
                'cultivar_name',
                'lot_number', 
                'germination_days', 
                'blackout_days', 
                'light_days',
                'expected_yield_grams',
                'seed_density_grams_per_tray',
                'is_active',
                'planting_notes',
                'harvesting_notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}