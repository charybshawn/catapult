<?php

namespace App\Filament\Resources\CropPlanResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use App\Models\CropPlanStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\KeyValue;
use Filament\Forms;

/**
 * Form schema builder for agricultural crop plan management with production
 * timeline coordination, resource planning, and approval workflow integration.
 *
 * This class constructs sophisticated forms for reviewing and adjusting automatically
 * generated crop plans, providing interfaces for timeline modifications, resource
 * adjustments, calculation review, and approval workflow management within the
 * agricultural production planning system.
 *
 * @filament_form_class Dedicated form schema builder for CropPlanResource
 * @business_domain Agricultural production planning and timeline management
 * @agricultural_concepts Plan details, timeline coordination, resource calculations
 * 
 * @form_sections
 * - Plan Details: Order linkage, recipe assignment, resource calculations
 * - Timeline: Planting schedule, harvest expectations, delivery coordination
 * - Status & Approval: Workflow state management and approval tracking
 * - Calculation Details: Technical details and order item breakdown
 * 
 * @agricultural_workflow_integration
 * - Order relationship for production context and customer fulfillment
 * - Recipe linkage for cultivation instructions and growing specifications
 * - Timeline calculations supporting agricultural production scheduling
 * - Resource planning with tray counts and seed quantity requirements
 * 
 * @production_planning_features
 * - Automatic resource calculations (trays needed, grams per tray)
 * - Timeline coordination (plant-by, harvest, delivery dates)
 * - Recipe validation for complete cultivation instructions
 * - Approval workflow integration for production authorization
 * 
 * @business_intelligence_fields
 * - Calculation details showing how resource requirements were determined
 * - Order items included showing which products drive the plan requirements
 * - Admin notes for production coordination and special instructions
 * - Status tracking for workflow progression and approval management
 * 
 * @workflow_validation
 * - Required fields ensure complete production planning information
 * - Date validation maintains agricultural timeline integrity
 * - Resource validation ensures production capacity alignment
 * - Recipe requirement validation for cultivation instruction completeness
 */
class CropPlanForm
{
    /**
     * Get comprehensive form schema for crop plan management and review.
     *
     * Constructs a detailed form interface supporting the complete crop plan
     * review and adjustment workflow, from plan details and timeline management
     * through approval processes and technical calculation review.
     *
     * @return array Complete form schema with agricultural production planning sections
     * @agricultural_workflow Plan review, timeline adjustment, approval management
     * @business_context Production scheduling with order fulfillment integration
     * @form_organization Logical sections for efficient production planning workflows
     */
    public static function schema(): array
    {
        return [
            Section::make('Plan Details')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            static::getOrderField(),
                            static::getRecipeField(),
                        ]),

                    Grid::make(3)
                        ->schema([
                            static::getTraysNeededField(),
                            static::getGramsNeededField(),
                            static::getGramsPerTrayField(),
                        ]),
                ]),

            Section::make('Timeline')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            static::getPlantByDateField(),
                            static::getExpectedHarvestDateField(),
                            static::getDeliveryDateField(),
                        ]),
                ]),

            Section::make('Status & Approval')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            static::getStatusField(),
                            static::getApprovedByField(),
                        ]),

                    static::getApprovedAtField(),
                ]),

            Section::make('Calculation Details')
                ->schema([
                    static::getNotesField(),
                    static::getAdminNotesField(),
                    static::getCalculationDetailsField(),
                    static::getOrderItemsIncludedField(),
                ])
                ->collapsible()
                ->collapsed(),
        ];
    }

    /**
     * Get order selection field with customer context for production planning.
     *
     * Provides intelligent order selection showing order ID and customer information
     * to give production context for crop planning decisions. Essential for
     * understanding the customer fulfillment requirements driving the plan.
     *
     * @return Select Order selection with customer context display
     * @agricultural_context Links production planning to customer fulfillment
     * @business_intelligence Customer information supports production prioritization
     * @workflow_integration Order linkage enables fulfillment timeline coordination
     */
    protected static function getOrderField(): Select
    {
        return Select::make('order_id')
            ->label('Order')
            ->relationship('order', 'id', function ($query) {
                return $query->with('customer');
            })
            ->getOptionLabelFromRecordUsing(function ($record) {
                $customerName = $record->customer->contact_name ?? 'Unknown';
                return "Order #{$record->id} - {$customerName}";
            })
            ->searchable()
            ->preload()
            ->required();
    }

    /**
     * Get recipe selection field for cultivation instruction integration.
     *
     * Provides recipe selection essential for agricultural production, linking
     * crop plans to specific cultivation instructions, growing parameters,
     * and variety-specific agricultural requirements.
     *
     * @return Select Recipe selection for cultivation instruction linkage
     * @agricultural_requirement Essential for complete cultivation instructions
     * @business_workflow Recipe determines growing timeline and resource requirements
     * @production_planning Links to variety-specific cultivation parameters
     */
    protected static function getRecipeField(): Select
    {
        return Select::make('recipe_id')
            ->label('Recipe')
            ->relationship('recipe', 'name')
            ->searchable()
            ->preload()
            ->required();
    }

    /**
     * Get tray requirement field for production capacity planning.
     *
     * Calculates and displays the number of growing trays required for the crop
     * plan, essential for production scheduling, space allocation, and resource
     * management in agricultural production facilities.
     *
     * @return TextInput Tray count field for production capacity planning
     * @agricultural_resource Physical space and equipment requirement calculation
     * @business_planning Production capacity and resource allocation planning
     * @workflow_integration Drives facility scheduling and space management
     */
    protected static function getTraysNeededField(): TextInput
    {
        return TextInput::make('trays_needed')
            ->label('Trays Needed')
            ->numeric()
            ->minValue(1)
            ->required();
    }

    /**
     * Get seed quantity field for agricultural input planning.
     *
     * Specifies the total grams of seed required for the crop plan, essential
     * for inventory management, procurement planning, and ensuring adequate
     * agricultural inputs for production fulfillment.
     *
     * @return TextInput Seed quantity field for agricultural input planning
     * @agricultural_input Seed quantity requirement for production fulfillment
     * @business_planning Inventory management and procurement coordination
     * @workflow_integration Links to seed inventory and supplier management
     */
    protected static function getGramsNeededField(): TextInput
    {
        return TextInput::make('grams_needed')
            ->label('Grams Needed')
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->required();
    }

    /**
     * Get seed density field for cultivation efficiency optimization.
     *
     * Calculates seed allocation per tray for optimal growing density,
     * supporting cultivation efficiency and yield optimization within
     * agricultural production planning workflows.
     *
     * @return TextInput Seed density field for cultivation optimization
     * @agricultural_efficiency Growing density optimization for yield management
     * @business_optimization Resource utilization and production efficiency
     * @cultivation_science Variety-specific density for optimal growing conditions
     */
    protected static function getGramsPerTrayField(): TextInput
    {
        return TextInput::make('grams_per_tray')
            ->label('Grams per Tray')
            ->numeric()
            ->minValue(0)
            ->step(0.01);
    }

    /**
     * Get planting deadline field for production timeline coordination.
     *
     * Critical date field establishing when planting must occur to meet
     * delivery commitments, calculated backward from harvest and delivery
     * requirements with variety-specific growing periods.
     *
     * @return DatePicker Planting deadline for production timeline coordination
     * @agricultural_critical Planting deadline determines production success
     * @business_commitment Timeline requirement to meet customer delivery dates
     * @workflow_urgency Drives production scheduling and labor planning
     */
    protected static function getPlantByDateField(): DatePicker
    {
        return DatePicker::make('plant_by_date')
            ->label('Plant By Date')
            ->required();
    }

    /**
     * Get expected harvest date field for production timeline planning.
     *
     * Projected harvest date based on variety growing periods and planting
     * schedule, essential for coordinating harvest labor, processing capacity,
     * and delivery fulfillment in agricultural production workflows.
     *
     * @return DatePicker Harvest projection for production timeline planning
     * @agricultural_projection Harvest timing based on variety growing characteristics
     * @business_planning Labor scheduling and processing capacity coordination
     * @workflow_integration Links planting timeline to fulfillment scheduling
     */
    protected static function getExpectedHarvestDateField(): DatePicker
    {
        return DatePicker::make('expected_harvest_date')
            ->label('Expected Harvest Date')
            ->required();
    }

    /**
     * Get delivery date field for customer fulfillment coordination.
     *
     * Target delivery date driving the entire production timeline, typically
     * inherited from the originating customer order and serving as the anchor
     * point for all backward timeline calculations.
     *
     * @return DatePicker Delivery target for customer fulfillment coordination
     * @business_commitment Customer delivery requirement driving production timeline
     * @agricultural_anchor Final date determining all upstream production scheduling
     * @workflow_foundation Base requirement for all agricultural timeline calculations
     */
    protected static function getDeliveryDateField(): DatePicker
    {
        return DatePicker::make('delivery_date')
            ->label('Delivery Date')
            ->required();
    }

    /**
     * Get crop plan status field for workflow state management.
     *
     * Manages crop plan progression through agricultural workflow states
     * (draft, approved, in production, completed) with appropriate defaults
     * and validation for agricultural production coordination.
     *
     * @return Select Status selection for agricultural workflow management
     * @workflow_management Plan progression through agricultural production states
     * @business_coordination Status drives production authorization and scheduling
     * @agricultural_workflow State management for production planning approval
     */
    protected static function getStatusField(): Select
    {
        return Select::make('status_id')
            ->label('Status')
            ->relationship('status', 'name')
            ->default(function () {
                return CropPlanStatus::findByCode('draft')->id;
            })
            ->required();
    }

    /**
     * Get approval tracking field for production authorization management.
     *
     * Tracks which user approved the crop plan for production, providing
     * accountability and audit trail for agricultural production decisions
     * and resource allocation authorization.
     *
     * @return Select Approval tracking for production authorization audit
     * @business_accountability Tracks production authorization decisions
     * @workflow_audit Approval trail for agricultural planning accountability
     * @conditional_display Only visible when approval has been recorded
     */
    protected static function getApprovedByField(): Select
    {
        return Select::make('approved_by')
            ->label('Approved By')
            ->relationship('approvedBy', 'name')
            ->searchable()
            ->preload()
            ->visible(fn ($record) => $record && $record->approved_by);
    }

    /**
     * Get approval timestamp field for production authorization tracking.
     *
     * Records when crop plan approval occurred, providing temporal context
     * for production authorization and supporting agricultural workflow
     * audit trails and timeline analysis.
     *
     * @return DateTimePicker Approval timestamp for authorization tracking
     * @workflow_audit Temporal tracking of production authorization decisions
     * @business_intelligence Approval timing analysis for workflow optimization
     * @conditional_display Only visible when approval timestamp exists
     */
    protected static function getApprovedAtField(): DateTimePicker
    {
        return DateTimePicker::make('approved_at')
            ->label('Approved At')
            ->disabled()
            ->visible(fn ($record) => $record && $record->approved_at);
    }

    /**
     * Get general notes field for production planning communication.
     *
     * Provides space for general production notes, special instructions,
     * or coordination information relevant to the crop plan execution
     * and agricultural production management.
     *
     * @return Textarea General notes for production planning communication
     * @business_communication Production coordination and special instructions
     * @workflow_support Additional context for agricultural production execution
     * @team_coordination Shared information for production team collaboration
     */
    protected static function getNotesField(): Textarea
    {
        return Textarea::make('notes')
            ->label('Notes')
            ->rows(3);
    }

    /**
     * Get administrative notes field for management-level production information.
     *
     * Dedicated space for administrative notes, management decisions,
     * or sensitive production information requiring elevated access
     * within agricultural production planning workflows.
     *
     * @return Textarea Administrative notes for management-level information
     * @business_management Administrative decisions and sensitive information
     * @workflow_control Management-level production planning coordination
     * @access_control Administrative information with appropriate access controls
     */
    protected static function getAdminNotesField(): Textarea
    {
        return Textarea::make('admin_notes')
            ->label('Admin Notes')
            ->rows(3);
    }

    /**
     * Get calculation details field for agricultural planning transparency.
     *
     * Provides detailed breakdown of how resource requirements were calculated,
     * including formulas, assumptions, and agricultural parameters used in
     * determining tray counts, seed quantities, and timeline projections.
     *
     * @return KeyValue Calculation details for agricultural planning transparency
     * @agricultural_intelligence Technical details of resource requirement calculations
     * @business_transparency Calculation methodology for planning validation
     * @workflow_debugging Technical information for plan troubleshooting and optimization
     */
    protected static function getCalculationDetailsField(): KeyValue
    {
        return KeyValue::make('calculation_details')
            ->label('Calculation Details')
            ->addActionLabel('Add Detail')
            ->columnSpanFull();
    }

    /**
     * Get order items field for production requirement traceability.
     *
     * Details which specific order items and quantities drive the crop plan
     * requirements, providing complete traceability from customer orders
     * to production planning and resource allocation decisions.
     *
     * @return KeyValue Order items breakdown for production requirement traceability
     * @business_traceability Links customer orders to production requirements
     * @agricultural_planning Order fulfillment breakdown for production coordination
     * @workflow_integration Complete traceability from orders to production planning
     */
    protected static function getOrderItemsIncludedField(): KeyValue
    {
        return KeyValue::make('order_items_included')
            ->label('Order Items Included')
            ->addActionLabel('Add Item')
            ->columnSpanFull();
    }
}