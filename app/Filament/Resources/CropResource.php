<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\CropResource\Pages\ListCrops;
use App\Filament\Resources\CropResource\Pages\CreateCrop;
use App\Filament\Resources\CropResource\Pages\ViewCrop;
use App\Filament\Resources\CropResource\Pages\EditCrop;
use App\Filament\Resources\CropResource\Forms\CropForm;
use App\Filament\Resources\CropResource\Pages;
use App\Filament\Resources\CropResource\Tables\CropTable;
use App\Models\Crop;
use Filament\Infolists;
use Filament\Tables\Table;

/**
 * Individual crop production management interface for agricultural tracking.
 * 
 * Manages detailed tracking of individual crop units through their complete
 * growth lifecycle from seed soaking through harvest. Provides comprehensive
 * stage transition management, growth timeline monitoring, and production
 * quality control for microgreens cultivation operations.
 * 
 * @filament_resource
 * @business_domain Agricultural production management and crop lifecycle tracking
 * @workflow_support Stage transitions, timeline monitoring, quality control
 * @related_models Crop, Recipe, CropStage, CropBatch, MasterSeedCatalog
 * @ui_features Stage transition actions, timeline visualization, progress tracking
 * @navigation Hidden from main navigation - accessed through CropBatch management
 * @production_context Individual crop units within larger production batches
 * 
 * Agricultural Lifecycle Management:
 * - Soaking: Seed preparation phase with time and weight tracking
 * - Germination: Sprouting phase with environmental monitoring
 * - Blackout: Growth development under controlled light conditions
 * - Light: Final growth phase under full lighting for leaf development  
 * - Harvest: Completion phase with yield recording and quality assessment
 * 
 * Production Features:
 * - Recipe-based growth parameters with automatic stage progression
 * - Conditional workflow based on seed variety requirements (soaking vs direct planting)
 * - Tray management and space allocation tracking throughout growth cycle
 * - Environmental condition monitoring (temperature, humidity, lighting)
 * - Stage history tracking for quality control and troubleshooting
 * - Integration with order fulfillment for harvest timing coordination
 * 
 * Business Operations:
 * - Real-time crop status monitoring for production planning
 * - Stage transition automation based on recipe specifications
 * - Quality control checkpoints at each growth phase
 * - Yield prediction and harvest scheduling optimization
 * - Production efficiency analysis and performance metrics
 * - Integration with inventory management for consumable tracking
 * 
 * @delegation Delegates to CropForm and CropTable for modular architecture
 * @access_pattern Accessed through CropBatch interface for batch-focused workflow
 */
class CropResource extends BaseResource
{
    /** @var string The Eloquent model class for individual agricultural crops */
    protected static ?string $model = Crop::class;

    /** @var string Navigation icon representing growth/fire/energy concept */
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-fire';

    /** @var string Navigation label for individual crop management */
    protected static ?string $navigationLabel = 'Individual Crops';

    /** @var string Navigation group for production-related resources */
    protected static string | \UnitEnum | null $navigationGroup = 'Production';

    /** @var int Low priority navigation position (accessed through batch interface) */
    protected static ?int $navigationSort = 99;
    
    /**
     * Hide resource from primary navigation for focused workflow.
     * 
     * Individual crops are managed through the CropBatch interface to maintain
     * production-focused workflow. This prevents UI clutter while preserving
     * direct access for debugging and detailed crop management when needed.
     * 
     * @return bool Always false - access through CropBatch management
     * @workflow_design Batch-focused production management prevents information overload
     * @access_method Available through batch detail pages and direct URL access
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /** @var string Record identifier for page titles and breadcrumbs */
    protected static ?string $recordTitleAttribute = 'id';

    /**
     * Build the Filament form schema for crop production management.
     * 
     * Delegates to CropForm for complex agricultural form logic including recipe
     * selection, conditional soaking workflows, tray management, and growth
     * parameter configuration. Form adapts based on seed variety requirements
     * and production stage with dynamic field visibility and validation.
     * 
     * @param Schema $schema The Filament form schema builder
     * @return Schema Configured form with agricultural production tracking fields
     * @delegation CropForm::schema() handles complex conditional form logic
     * @conditional_fields Form adapts based on recipe soaking requirements
     * @agricultural_workflow Supports both soaking and direct planting workflows
     * @tray_management Dynamic tray assignment based on production methodology
     * @seed_calculations Automatic seed weight calculations for soaking phases
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components(CropForm::schema());
    }

    /**
     * Build the Filament data table for crop production overview.
     * 
     * Creates comprehensive production table with real-time crop status, stage
     * progression indicators, and production metrics. Optimized for production
     * monitoring with stage-based grouping and timeline tracking for harvest
     * scheduling and quality control operations.
     * 
     * @param Table $table The Filament table builder
     * @return Table Configured table with crop production features
     * @delegation CropTable handles columns, actions, filters, and grouping
     * @performance Eager loads recipe, cultivar, and stage relationships
     * @features Stage indicators, timeline tracking, bulk stage transitions
     * @sorting Default chronological order (newest first) for production relevance
     * @grouping Stage-based grouping for production workflow visualization
     * @monitoring Real-time status indicators for harvest timing coordination
     */
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => CropTable::modifyQuery($query))
            ->columns(CropTable::columns())
            ->filters(CropTable::filters())
            ->recordActions(CropTable::actions())
            ->toolbarActions(CropTable::bulkActions())
            ->groups(CropTable::groups())
            ->defaultSort('id', 'desc');
    }

    /**
     * Build the Filament infolist for detailed crop production visualization.
     * 
     * Creates comprehensive read-only view of crop status with detailed timeline
     * tracking, stage progression monitoring, and production metrics. Essential
     * for quality control inspection, troubleshooting production issues, and
     * harvest timing coordination with customer orders.
     * 
     * @param Schema $schema The Filament infolist schema builder
     * @return Schema Configured infolist with crop production details
     * @agricultural_context Displays complete growth timeline from soaking to harvest
     * @production_metrics Shows stage duration, progress tracking, and timing estimates
     * @quality_control Provides detailed view for production issue investigation
     * @harvest_coordination Timeline data supports order fulfillment scheduling
     * @conditional_display Soaking information shown only for varieties requiring it
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Crop Details')
                    ->schema([
                        TextEntry::make('recipe.name')
                            ->label('Recipe'),
                        TextEntry::make('currentStage.name')
                            ->label('Current Stage')
                            ->badge(),
                        TextEntry::make('tray_number')
                            ->label('Tray Number'),
                        TextEntry::make('tray_count')
                            ->label('Tray Count'),
                    ])
                    ->columns(2),

                Section::make('Timeline')
                    ->schema([
                        TextEntry::make('soaking_at')
                            ->label('Soaking Started')
                            ->dateTime()
                            ->visible(fn ($record) => $record->requires_soaking),
                        TextEntry::make('germination_at')
                            ->label('Germination')
                            ->dateTime(),
                        TextEntry::make('blackout_at')
                            ->label('Blackout')
                            ->dateTime(),
                        TextEntry::make('light_at')
                            ->label('Light')
                            ->dateTime(),
                        TextEntry::make('harvested_at')
                            ->label('Harvested')
                            ->dateTime(),
                    ])
                    ->columns(3),

                Section::make('Progress')
                    ->schema([
                        TextEntry::make('time_to_next_stage_display')
                            ->label('Time to Next Stage'),
                        TextEntry::make('stage_age_display')
                            ->label('Time in Current Stage'),
                        TextEntry::make('total_age_display')
                            ->label('Total Age'),
                    ])
                    ->columns(3),
            ]);
    }

    /**
     * Define relationship managers for crop resource.
     * 
     * No relationship managers configured as crop relationships are managed
     * through their respective resources or the parent CropBatch interface.
     * This maintains clean separation of concerns and prevents UI complexity
     * in the individual crop management workflow.
     * 
     * @return array<class-string> Empty array - relationships managed elsewhere
     * @design_pattern Relationships managed through dedicated resources
     * @performance Avoids loading heavy relationship data on crop detail pages
     * @workflow Individual crop focus without relationship complexity
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Define the page routes and classes for crop resource.
     * 
     * Provides complete CRUD workflow for individual crop management including
     * detailed view page with comprehensive timeline and progress tracking.
     * Despite being hidden from navigation, full page access supports debugging,
     * quality control inspection, and detailed production analysis.
     * 
     * @return array<string, class-string> Page route mappings
     * @routes Complete CRUD workflow for comprehensive crop management
     * @detailed_view Includes comprehensive infolist with timeline tracking
     * @access_method Available through direct URL despite hidden navigation
     * @debugging Full page access supports production troubleshooting
     */
    public static function getPages(): array
    {
        return [
            'index' => ListCrops::route('/'),
            'create' => CreateCrop::route('/create'),
            'view' => ViewCrop::route('/{record}'),
            'edit' => EditCrop::route('/{record}/edit'),
        ];
    }
}
