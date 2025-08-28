<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\HarvestResource\Pages\ListHarvests;
use App\Filament\Resources\HarvestResource\Pages\EditHarvest;
use App\Filament\Resources\HarvestResource\Forms\HarvestForm;
use App\Filament\Resources\HarvestResource\Pages;
use App\Filament\Resources\HarvestResource\Tables\HarvestTable;
use App\Models\Harvest;
use Filament\Tables\Table;
use App\Filament\Traits\CsvExportAction;

/**
 * Simplified harvest tracking and yield management interface for agricultural operations.
 * 
 * Manages streamlined harvest data collection with cultivar-based recording for
 * microgreens production. Focuses on essential yield tracking with weight measurement
 * per cultivar, eliminating complex tray relationships for efficient data entry.
 * Provides critical data for production optimization and variety performance analysis.
 * 
 * @filament_resource
 * @business_domain Simplified agricultural harvest tracking and yield management
 * @workflow_support Cultivar-based yield recording, performance analysis, quality control
 * @related_models Harvest, MasterCultivar, User
 * @ui_features Multi-cultivar harvest recording, variety grouping, weight statistics, CSV export
 * @production_integration Streamlined harvest workflow without complex crop relationships
 * @analytics Cultivar performance analysis, seasonal yield trends, weight-based metrics
 * 
 * Simplified Harvest Data Collection:
 * - Cultivar Selection: Active varieties available for harvest recording
 * - Weight Tracking: Direct weight measurement per cultivar in grams
 * - Quality Documentation: Harvest notes and observations
 * - Date Recording: Harvest date with validation to prevent future dates
 * - Staff Attribution: User identification for harvest accountability
 * 
 * Agricultural Business Features:
 * - Variety Performance Analysis: Compare yields across different cultivars by weight
 * - Seasonal Yield Tracking: Monitor production consistency over time
 * - Quality Control Documentation: Track harvest notes and quality observations
 * - Staff Performance Metrics: Individual harvester productivity tracking
 * - Production Efficiency Analysis: Streamlined data entry for operational efficiency
 * 
 * Simplified Production Operations:
 * - Modal-based harvest recording for efficient data entry
 * - Multi-cultivar harvest support in single session
 * - Cumulative harvest tracking through multiple records per cultivar/date
 * - Direct weight entry without tray complexity
 * - Integration with order fulfillment through cultivar-based planning
 * 
 * Business Intelligence:
 * - Variety profitability analysis based on cultivar weight yields
 * - Seasonal demand forecasting using simplified harvest patterns
 * - Quality trend identification through harvest note analysis
 * - Staff productivity analysis through streamlined workflow metrics
 * - Simplified reporting focused on cultivar performance and weight yields
 * 
 * @delegation Delegates to HarvestForm and HarvestTable for modular architecture
 * @csv_export Comprehensive harvest data export optimized for cultivar-based analysis
 */
class HarvestResource extends BaseResource
{
    use CsvExportAction;
    
    /** @var string The Eloquent model class for agricultural harvest tracking */
    protected static ?string $model = Harvest::class;

    /** @var string Navigation icon representing scale/measurement concept */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scale';

    /** @var string Navigation label for harvest tracking */
    protected static ?string $navigationLabel = 'Harvests';

    /** @var string Navigation group for production-related resources */
    protected static string | \UnitEnum | null $navigationGroup = 'Production';

    /** @var int Third navigation position for harvest workflow */
    protected static ?int $navigationSort = 3;

    /** @var string Parent navigation item for hierarchical organization */
    protected static ?string $navigationParentItem = 'Grows';

    /**
     * Build the Filament form schema for agricultural harvest data collection.
     * 
     * Delegates to HarvestForm for complex harvest recording logic including
     * cultivar selection, yield tracking with weight measurement, and quality 
     * assessment documentation. Form provides efficient harvest recording
     * capabilities with support for both create and edit modes.
     * 
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form with harvest tracking and yield measurement
     * @delegation HarvestForm::schema() handles cultivar selection and yield recording logic
     * @cultivar_integration Dynamic cultivar selection based on active varieties
     * @yield_tracking Weight measurement options for production tracking
     * @quality_control Notes field for harvest observations and quality documentation
     * @dual_mode Supports both multi-cultivar creation and single-record editing
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components(HarvestForm::schema());
    }

    /**
     * Build the Filament data table for harvest tracking and yield analysis.
     * 
     * Creates comprehensive harvest overview with variety grouping, yield
     * statistics, and performance metrics. Table design optimizes for harvest
     * analysis with date-based grouping, variety performance comparison, and
     * integrated export capabilities for detailed production analysis.
     * 
     * @param Table $table The Filament table builder
     * @return Table Configured table with harvest tracking and yield analysis
     * @delegation HarvestTable handles complex column definitions and statistics
     * @performance Query optimization for harvest data and variety relationships
     * @grouping Date-based default grouping for chronological harvest review
     * @statistics Yield summaries and averages for performance analysis
     * @export Comprehensive CSV export for detailed harvest analysis
     * @sorting Chronological default sort for recent harvest prioritization
     * @desktop_optimization Dropdown grouping for desktop usability
     */
    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->modifyQueryUsing(fn ($query) => HarvestTable::modifyQuery($query))
            ->columns(HarvestTable::columns())
            ->defaultSort('harvest_date', 'desc')
            ->groups(HarvestTable::groups())
            ->defaultGroup('harvest_date')
            ->groupsInDropdownOnDesktop()
            ->filters(HarvestTable::filters())
            ->recordActions(HarvestTable::actions())
            ->headerActions([
                static::getCsvExportAction(),
            ])
            ->toolbarActions(HarvestTable::bulkActions());
    }

    /**
     * Define relationship managers for harvest resource.
     * 
     * No relationship managers configured as harvest relationships are managed
     * through their respective resources and the harvest workflow focuses on
     * data collection and yield analysis rather than relationship editing.
     * 
     * @return array<class-string> Empty array - relationships managed in dedicated resources
     * @workflow_focus Harvest management concentrates on yield tracking and analysis
     * @design_pattern Harvest data collection without relationship complexity
     * @performance Avoids heavy relationship loading on harvest recording pages
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Define the page routes and classes for harvest resource.
     * 
     * Provides streamlined harvest management workflow with create, list, and edit
     * capabilities. Create page handles the simplified cultivar-based harvest entry,
     * list view provides analysis and filtering, and edit supports data corrections.
     * No separate view page as edit provides comprehensive harvest data access.
     * 
     * @return array<string, class-string> Page route mappings
     * @routes Complete CRUD workflow optimized for harvest data management
     * @workflow Create, list, and edit workflow for simplified harvest recording
     * @simplified_create Cultivar-based harvest entry without complex tray relationships
     */
    public static function getPages(): array
    {
        return [
            'index' => ListHarvests::route('/'),
            'edit' => EditHarvest::route('/{record}/edit'),
        ];
    }
    
    /**
     * Define CSV export columns for simplified harvest analysis reporting.
     * 
     * Configures streamlined harvest data export including variety information,
     * weight metrics, and harvester attribution for production analysis, yield
     * optimization, and quality control assessment across different varieties
     * and time periods using the simplified cultivar-based approach.
     * 
     * @return array Export column definitions with variety and user context
     * @includes Variety details for performance comparison across cultivars
     * @harvester User information for staff performance and quality tracking
     * @yield_metrics Total weight per cultivar for simplified yield tracking
     * @temporal Harvest dates for seasonal and trend analysis
     * @quality Notes field for quality issue tracking and improvement
     */
    protected static function getCsvExportColumns(): array
    {
        $coreColumns = [
            'id' => 'ID',
            'master_cultivar_id' => 'Cultivar ID',
            'total_weight_grams' => 'Total Weight (g)',
            'harvest_date' => 'Harvest Date',
            'user_id' => 'User ID',
            'notes' => 'Notes',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
        
        return static::addRelationshipColumns($coreColumns, [
            'masterCultivar' => ['common_name', 'cultivar_name'],
            'user' => ['name', 'email'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export for complete harvest context.
     * 
     * Ensures exported harvest data includes critical variety and user information
     * necessary for comprehensive yield analysis, staff performance evaluation,
     * and variety comparison studies essential for production optimization.
     * 
     * @return array<string> Relationship names to eager load for export
     * @relationships Cultivar for variety analysis, user for harvester tracking
     * @performance Prevents N+1 queries during large harvest data exports
     * @analysis Provides complete context for harvest performance studies
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['masterCultivar', 'user'];
    }
}
