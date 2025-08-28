<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\CropPlanResource\Pages\CalendarCropPlans;
use App\Filament\Resources\CropPlanResource\Pages\ListCropPlans;
use App\Filament\Resources\CropPlanResource\Pages\EditCropPlan;
use App\Filament\Resources\CropPlanResource\Forms\CropPlanForm;
use App\Filament\Resources\CropPlanResource\Pages;
use App\Filament\Resources\CropPlanResource\Tables\CropPlanTable;
use App\Models\CropPlan;
use App\Filament\Resources\Base\BaseResource;
use Filament\Tables\Table;

/**
 * Filament resource for agricultural crop planning and production scheduling
 * with automated order-driven planning and harvest timeline management.
 *
 * This resource manages the sophisticated crop planning workflow that bridges
 * customer orders with actual agricultural production. It handles automatic
 * plan generation from orders, timeline calculations based on variety growing
 * periods, and approval workflows for production coordination.
 *
 * @filament_resource Manages CropPlan entities with agricultural production scheduling
 * @business_domain Agricultural production planning and crop timing coordination
 * @related_models CropPlan, Order, Recipe, CropPlanStatus, MasterSeedCatalog
 * @workflow_support Order-driven production planning and harvest scheduling
 * 
 * @agricultural_concepts
 * - Crop planning: Automated calculation of planting dates from delivery requirements
 * - Timeline management: Plant-by dates, expected harvest, delivery coordination
 * - Recipe integration: Growing instructions and variety-specific cultivation data
 * - Production scheduling: Tray requirements, seed quantities, space planning
 * 
 * @planning_automation
 * - Order analysis: Extracts variety requirements and delivery dates from orders
 * - Timeline calculations: Works backward from delivery to determine planting schedules
 * - Resource planning: Calculates tray counts and seed requirements automatically
 * - Buffer management: Includes safety margins for agricultural production variability
 * 
 * @workflow_features
 * - Draft → Approved workflow with agricultural validation
 * - Missing recipe detection and creation workflow integration
 * - Bulk approval operations for production scheduling efficiency
 * - Real-time urgency indicators for planting deadlines
 * - Calendar integration for visual production planning
 * 
 * @business_intelligence
 * - Days until planting with color-coded urgency indicators
 * - Resource summaries (total trays, total grams) for production capacity planning
 * - Missing recipe identification for cultivation workflow completion
 * - Grouping by variety and dates for production scheduling optimization
 * 
 * @agricultural_validation
 * - Planting timeline validation against variety growing periods
 * - Resource availability checking for production capacity management
 * - Recipe requirement validation for cultivation instruction completeness
 * - Order fulfillment validation to ensure delivery commitments are met
 * 
 * @performance_considerations
 * - Calendar-first interface optimized for visual production scheduling
 * - Session-persistent filters for production workflow efficiency
 * - Eager loading of complex relationships prevents N+1 queries
 * - Automated plan generation with bulk processing for efficiency
 */
class CropPlanResource extends BaseResource
{
    protected static ?string $model = CropPlan::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Crop Plans';

    protected static string | \UnitEnum | null $navigationGroup = 'Production';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * Define form schema for crop plan editing and review.
     *
     * Provides a comprehensive interface for reviewing and adjusting automatically
     * generated crop plans, including timeline modifications, resource adjustments,
     * and approval workflow management. Form emphasizes agricultural context and
     * production planning requirements.
     *
     * @param Schema $schema Filament schema builder instance
     * @return Schema Complete crop plan form with agricultural production context
     * @agricultural_workflow Timeline adjustment, resource planning, approval management
     * @business_context Production scheduling with order fulfillment integration
     * @delegation CropPlanForm::schema() contains detailed field definitions
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components(CropPlanForm::schema());
    }

    /**
     * Configure comprehensive crop planning table with agricultural production visualization.
     *
     * Creates a sophisticated production planning interface with timeline visualization,
     * resource summaries, urgency indicators, and workflow actions tailored for
     * agricultural production management. Supports both detailed list and calendar
     * views for different planning perspectives.
     *
     * @param Table $table Filament table builder instance
     * @return Table Complete crop planning table with agricultural production features
     * 
     * @table_features
     * - Timeline visualization with plant-by dates and harvest scheduling
     * - Resource summaries showing total trays and grams needed
     * - Urgency indicators with color-coded days-until-planting display
     * - Missing recipe detection with workflow integration for completion
     * - Customer and order linkage for production context
     * 
     * @agricultural_intelligence
     * - Automatic urgency calculation for planting deadlines
     * - Resource aggregation for production capacity planning
     * - Variety grouping for cultivation efficiency optimization
     * - Timeline grouping for production scheduling coordination
     * 
     * @workflow_integration
     * - Approval actions with agricultural validation
     * - Recipe creation workflow for missing cultivation instructions
     * - Bulk operations for production scheduling efficiency
     * - Order linkage for customer fulfillment context
     * 
     * @performance_optimization
     * - Session-persistent filters for production workflow continuity
     * - Default sorting by plant-by date for timeline-focused management
     * - Eager loading of relationships prevents N+1 queries
     * - Efficient grouping and summarization for large production datasets
     * 
     * @delegation CropPlanTable class handles detailed column and action definitions
     */
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(CropPlanTable::modifyQuery(...))
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->columns(CropPlanTable::columns())
            ->defaultSort('plant_by_date', 'asc')
            ->filters(CropPlanTable::filters())
            ->headerActions(CropPlanTable::headerActions())
            ->recordActions(CropPlanTable::actions())
            ->toolbarActions(CropPlanTable::bulkActions())
            ->groups(CropPlanTable::groups());
    }

    public static function getPages(): array
    {
        return [
            'index' => CalendarCropPlans::route('/'),
            'list' => ListCropPlans::route('/list'),
            'edit' => EditCropPlan::route('/{record}/edit'),
        ];
    }

    /**
     * Disable manual crop plan creation to maintain agricultural workflow integrity.
     *
     * Crop plans are automatically generated from confirmed orders through the
     * agricultural planning service to ensure timeline accuracy, resource
     * calculations, and order fulfillment alignment. Manual creation bypasses
     * essential agricultural validation and planning automation.
     *
     * @return bool Always false to prevent manual creation bypassing workflow
     * @agricultural_workflow Maintains order-driven planning automation
     * @business_integrity Ensures accurate timeline and resource calculations
     * @workflow_protection Prevents bypass of agricultural planning validation
     */
    public static function canCreate(): bool
    {
        return false; // Crop plans are auto-generated from orders
    }
}
