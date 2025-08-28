<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * CropPlan Model for Agricultural Production Planning and Scheduling
 * 
 * Manages individual crop production plans that coordinate order fulfillment
 * with growing schedules, recipe requirements, and harvest timing. Each crop plan
 * represents a specific variety that needs to be grown to satisfy customer orders
 * with precise timing calculations for agricultural production.
 * 
 * This model handles:
 * - Order-driven production planning with delivery date alignment
 * - Recipe-based resource calculations (seeds, trays, growing space)
 * - Agricultural timeline coordination (soak, plant, harvest dates)
 * - Approval workflows for production authorization
 * - Resource requirement calculations and allocation
 * - Integration with crop generation and harvest tracking
 * 
 * Business Context:
 * Agricultural production requires precise timing coordination between customer
 * delivery commitments and biological growing cycles. Crop plans bridge this gap by:
 * - Calculating backward from delivery dates to determine planting schedules
 * - Aggregating order requirements into efficient production batches
 * - Validating resource availability before committing to production
 * - Providing production teams with detailed growing instructions
 * - Tracking production status from planning through harvest
 * 
 * Planning workflow ensures:
 * - Customer orders receive fresh products at requested delivery dates
 * - Growing space and resources are efficiently utilized
 * - Production teams have clear, prioritized growing schedules
 * - Inventory requirements are communicated to purchasing teams
 * 
 * @property int $id Primary key
 * @property int|null $aggregated_crop_plan_id Link to aggregate plan for batch coordination
 * @property int $order_id Customer order driving this crop production plan
 * @property int $recipe_id Growing recipe to be used for production
 * @property int $variety_id Master seed catalog variety being grown
 * @property int $status_id Current crop plan status (draft, active, completed, cancelled)
 * @property int $trays_needed Number of growing trays required for production
 * @property float $grams_needed Total grams of seeds required for production
 * @property float $grams_per_tray Average grams of seeds per tray for this variety
 * @property \Carbon\Carbon $plant_by_date Latest date to plant crops for on-time delivery
 * @property \Carbon\Carbon|null $seed_soak_date Date to start seed soaking (if required)
 * @property \Carbon\Carbon $expected_harvest_date Anticipated harvest completion date
 * @property \Carbon\Carbon $delivery_date Customer delivery date driving timeline
 * @property array|null $calculation_details JSON details of planning calculations
 * @property array|null $order_items_included Order items included in this crop plan
 * @property int|null $created_by User who created this crop plan
 * @property int|null $approved_by User who approved this plan for production
 * @property \Carbon\Carbon|null $approved_at Timestamp when plan was approved
 * @property string|null $notes Production notes and special instructions
 * @property string|null $admin_notes Administrative notes for planning team
 * @property bool $is_missing_recipe Flag indicating recipe is missing or inactive
 * @property string|null $missing_recipe_notes Details about recipe availability issues
 * @property \Carbon\Carbon $created_at Crop plan creation timestamp
 * @property \Carbon\Carbon $updated_at Last plan modification
 * 
 * @relationship order BelongsTo relationship to customer Order driving production
 * @relationship recipe BelongsTo relationship to Recipe for growing instructions
 * @relationship createdBy BelongsTo relationship to User who created plan
 * @relationship approvedBy BelongsTo relationship to User who approved plan
 * @relationship status BelongsTo relationship to CropPlanStatus lookup
 * @relationship crops HasMany relationship to actual Crops generated from plan
 * @relationship aggregatedCropPlan BelongsTo relationship to aggregate plan
 * @relationship variety BelongsTo relationship to MasterSeedCatalog variety
 * 
 * @business_rules
 * - Plant by date calculated backward from delivery date minus growing time
 * - Seed soak date precedes plant date by recipe soak hours (if applicable)
 * - Trays needed calculated from grams needed divided by recipe seed density
 * - Plans must be approved before crop generation can begin
 * - Missing recipes prevent plan approval until resolved
 * - Overdue plans require immediate attention or order adjustment
 * - Approved plans cannot be modified without re-approval workflow
 * 
 * @workflow_patterns
 * Crop Plan Generation:
 * 1. Customer order received with delivery date requirements
 * 2. System calculates backward timeline from delivery date
 * 3. Recipe validated for variety availability and active status
 * 4. Resource requirements calculated (trays, seeds, space)
 * 5. Crop plan created in draft status for review
 * 6. Planning team reviews and approves production schedule
 * 7. Approved plans trigger crop generation and resource allocation
 * 
 * Production Workflow:
 * 1. Draft crop plans reviewed for resource availability
 * 2. Plans approved by production manager or automated system
 * 3. Crop generation creates individual crop records
 * 4. Production teams execute growing schedules
 * 5. Harvest coordination ensures on-time completion
 * 6. Plans marked completed when crops harvested and delivered
 * 
 * Status Lifecycle:
 * - Draft: Plan created, awaiting review and approval
 * - Active: Plan approved and ready for crop generation
 * - Completed: All crops harvested and delivered successfully
 * - Cancelled: Plan cancelled due to order changes or issues
 * 
 * @agricultural_context
 * Agricultural timing calculations account for:
 * - Seed soaking periods for varieties requiring pre-treatment
 * - Germination phases requiring controlled temperature and moisture
 * - Blackout periods for stem elongation development
 * - Light growing phases for color and flavor development
 * - Harvest windows for peak quality and customer satisfaction
 * - Buffer time for processing, packaging, and delivery logistics
 * 
 * @performance_considerations
 * - Calculation details stored as JSON for complex planning scenarios
 * - Order items cached to prevent N+1 queries during planning
 * - Status queries optimized with proper indexing on status_id
 * - Date calculations use efficient Carbon date arithmetic
 * 
 * @see \App\Models\Order For customer orders driving production plans
 * @see \App\Models\Recipe For growing instructions and timing calculations
 * @see \App\Models\Crop For individual crops generated from plans
 * @see \App\Services\CropPlanningService For planning algorithms and calculations
 * 
 * @author Agricultural Systems Team
 * @package App\Models
 */
class CropPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * Defines which crop plan fields can be bulk assigned during creation
     * and updates, supporting agricultural production planning workflows.
     * Includes all planning parameters, timing, and resource calculations.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'aggregated_crop_plan_id',
        'order_id',
        'recipe_id',
        'variety_id',
        'status_id',
        'trays_needed',
        'grams_needed',
        'grams_per_tray',
        'plant_by_date',
        'seed_soak_date',
        'expected_harvest_date',
        'delivery_date',
        'calculation_details',
        'order_items_included',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
        'admin_notes',
        'is_missing_recipe',
        'missing_recipe_notes',
    ];

    /**
     * The attributes that should be cast to appropriate data types.
     * 
     * Ensures proper handling of dates for agricultural timing calculations,
     * JSON data for complex planning details, and boolean flags for status tracking.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'plant_by_date' => 'date',
        'seed_soak_date' => 'date',
        'expected_harvest_date' => 'date',
        'delivery_date' => 'date',
        'approved_at' => 'datetime',
        'calculation_details' => 'array',
        'order_items_included' => 'array',
        'is_missing_recipe' => 'boolean',
    ];

    /**
     * Get the order that this crop plan is fulfilling.
     * 
     * Returns the customer order driving this production plan. Essential for
     * understanding customer requirements, delivery dates, and order priorities
     * in agricultural production scheduling.
     * 
     * @return BelongsTo<Order, CropPlan> Customer order driving production
     * @business_context Orders determine delivery dates and production priorities
     * @usage Production planning and customer requirement analysis
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the recipe to be used for growing this crop plan.
     * 
     * Returns the specific growing instructions including timing parameters,
     * seed densities, and environmental conditions for producing this variety.
     * Critical for resource calculations and production execution.
     * 
     * @return BelongsTo<Recipe, CropPlan> Growing recipe for production
     * @business_context Recipe determines resource needs and growing timeline
     * @usage Resource calculations and production instruction generation
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Get the user who created this crop plan.
     * 
     * Returns the planning team member or automated system that generated
     * this production plan. Supports accountability and planning workflow
     * tracking for agricultural production management.
     * 
     * @return BelongsTo<User, CropPlan> User who created the plan
     * @business_context Tracks responsibility for planning decisions
     * @usage Planning workflow management and accountability tracking
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this crop plan for production.
     * 
     * Returns the production manager or authorized user who reviewed and
     * approved this plan for execution. Essential for production authorization
     * and quality control in agricultural operations.
     * 
     * @return BelongsTo<User, CropPlan> User who approved the plan
     * @business_context Approval required before production can begin
     * @usage Production authorization and quality control workflows
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the current status of this crop plan.
     * 
     * Returns the workflow status (draft, active, completed, cancelled)
     * indicating the current phase of the production plan. Controls
     * available actions and production team workflows.
     * 
     * @return BelongsTo<CropPlanStatus, CropPlan> Current plan status
     * @business_context Status controls production workflow and available actions
     * @usage Status-based filtering and workflow management
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CropPlanStatus::class, 'status_id');
    }

    /**
     * Get the actual crops generated from this plan.
     * 
     * Returns the individual crop records created from this plan during
     * production execution. Supports tracking actual production against
     * planned requirements for agricultural operations.
     * 
     * @return HasMany<Crop> Individual crops generated from this plan
     * @business_context Links planning to actual production execution
     * @usage Production tracking and actual vs planned analysis
     */
    public function crops(): HasMany
    {
        return $this->hasMany(Crop::class, 'crop_plan_id');
    }

    /**
     * Get the aggregated crop plan containing this individual plan.
     * 
     * Returns the parent aggregate that groups multiple crop plans for
     * efficient batch production and resource coordination. Supports
     * production optimization and resource allocation strategies.
     * 
     * @return BelongsTo<CropPlanAggregate, CropPlan> Parent aggregate plan
     * @business_context Aggregates coordinate multiple plans for efficiency
     * @usage Batch production planning and resource optimization
     */
    public function aggregatedCropPlan(): BelongsTo
    {
        return $this->belongsTo(CropPlanAggregate::class);
    }

    /**
     * Get the seed variety to be grown in this plan.
     * 
     * Returns the master seed catalog entry specifying the exact
     * microgreens variety to be produced. Essential for recipe selection,
     * resource calculations, and quality specifications.
     * 
     * @return BelongsTo<MasterSeedCatalog, CropPlan> Variety to be grown
     * @business_context Variety determines growing requirements and market value
     * @usage Recipe matching and quality specification workflows
     */
    public function variety(): BelongsTo
    {
        return $this->belongsTo(MasterSeedCatalog::class, 'variety_id');
    }

    /**
     * Check if this crop plan has been approved for production.
     * 
     * Determines if plan has been reviewed and authorized for execution
     * by checking for 'active' status. Approved plans can proceed with
     * crop generation and resource allocation.
     * 
     * @return bool True if plan is approved and ready for production
     * @business_context Approval required before production resources committed
     * @usage Production authorization and workflow control
     */
    public function isApproved(): bool
    {
        return $this->status?->code === 'active';
    }

    /**
     * Check if this crop plan is still in draft status.
     * 
     * Determines if plan is awaiting review and approval. Draft plans
     * can be modified and require approval before production begins.
     * Used for filtering pending plans in management workflows.
     * 
     * @return bool True if plan is in draft status awaiting approval
     * @business_context Draft plans require review before production commitment
     * @usage Planning workflow filtering and status-based actions
     */
    public function isDraft(): bool
    {
        return $this->status?->code === 'draft';
    }

    /**
     * Check if this crop plan can be approved for production.
     * 
     * Validates that plan is in appropriate status for approval workflow.
     * Only draft plans can be approved; active or completed plans require
     * different workflows for modifications.
     * 
     * @return bool True if plan is eligible for approval
     * @business_context Prevents inappropriate status transitions
     * @usage Approval workflow validation and UI control
     */
    public function canBeApproved(): bool
    {
        return $this->status?->code === 'draft';
    }

    /**
     * Check if crops can be generated from this plan.
     * 
     * Validates that plan has been approved (active status) before allowing
     * crop generation and resource commitment. Prevents premature production
     * starts and ensures proper approval workflows.
     * 
     * @return bool True if plan is approved and ready for crop generation
     * @business_context Prevents unauthorized production starts
     * @usage Crop generation workflow control and validation
     */
    public function canGenerateCrops(): bool
    {
        return $this->status?->code === 'active';
    }

    /**
     * Approve this crop plan for production.
     * 
     * Transitions plan from draft to active status with approval tracking.
     * Records approving user and timestamp for accountability and audit trails.
     * Enables crop generation and resource allocation workflows.
     * 
     * @param User|null $user User approving the plan (null for system approval)
     * @return void
     * @throws Exception If plan cannot be approved in current status
     * @business_context Approval authorizes resource commitment and production start
     * @usage Production authorization workflows and approval tracking
     */
    public function approve(?User $user = null): void
    {
        if (!$this->canBeApproved()) {
            throw new Exception('Crop plan cannot be approved in current status: ' . $this->status?->name);
        }

        $activeStatus = CropPlanStatus::findByCode('active');
        $this->update([
            'status_id' => $activeStatus->id,
            'approved_by' => $user?->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Mark crop plan as generating crops.
     * 
     * Legacy method for status transition during crop generation process.
     * Modern system uses 'active' status throughout production phase.
     * Validates approval before allowing generation to proceed.
     * 
     * @return void
     * @throws Exception If plan is not approved for crop generation
     * @business_context Ensures proper approval before production begins
     * @usage Legacy crop generation workflow support
     * @note Modern system maintains 'active' status during generation
     */
    public function markAsGenerating(): void
    {
        if (!$this->canGenerateCrops()) {
            throw new Exception('Crop plan must be approved before generating crops');
        }

        // Note: 'generating' status doesn't exist in new system, using 'active'
        $activeStatus = CropPlanStatus::findByCode('active');
        $this->update(['status_id' => $activeStatus->id]);
    }

    /**
     * Mark crop plan as completed.
     * 
     * Transitions plan to completed status when all associated crops have
     * been harvested and delivered successfully. Indicates successful
     * production cycle completion for reporting and analysis.
     * 
     * @return void
     * @business_context Completion indicates successful order fulfillment
     * @usage Production tracking and completion workflows
     */
    public function markAsCompleted(): void
    {
        $completedStatus = CropPlanStatus::findByCode('completed');
        $this->update(['status_id' => $completedStatus->id]);
    }

    /**
     * Cancel this crop plan.
     * 
     * Transitions plan to cancelled status due to order changes, resource
     * constraints, or other issues preventing production. Allows resources
     * to be reallocated to other production priorities.
     * 
     * @return void
     * @business_context Cancellation frees resources for other production
     * @usage Order modification and resource reallocation workflows
     */
    public function cancel(): void
    {
        $cancelledStatus = CropPlanStatus::findByCode('cancelled');
        $this->update(['status_id' => $cancelledStatus->id]);
    }

    /**
     * Get the color associated with the current status.
     * 
     * Returns color code for UI display and status visualization.
     * Provides consistent color coding across the application for
     * crop plan status representation and dashboard displays.
     * 
     * @return string Color code for status display (e.g., 'green', 'yellow', 'red')
     * @business_context Visual status indicators improve workflow efficiency
     * @usage UI status badges, dashboard displays, and status visualizations
     */
    public function getStatusColorAttribute(): string
    {
        return $this->status?->color ?? 'gray';
    }

    /**
     * Get the number of days until planting deadline.
     * 
     * Calculates time remaining before plant-by date for priority scheduling
     * and urgency assessment. Negative values indicate overdue plans requiring
     * immediate attention or order adjustment.
     * 
     * @return int Days until planting (negative if overdue)
     * @business_context Timing critical for agricultural production success
     * @usage Priority scheduling and urgency assessment workflows
     */
    public function getDaysUntilPlantingAttribute(): int
    {
        return now()->diffInDays($this->plant_by_date, false);
    }

    /**
     * Check if this crop plan is overdue for planting.
     * 
     * Determines if plant-by date has passed for incomplete plans.
     * Only considers draft and active plans as overdue since completed
     * or cancelled plans are no longer actionable.
     * 
     * @return bool True if plan is overdue and still actionable
     * @business_context Overdue plans risk delivery date failures
     * @usage Priority alerts and production scheduling workflows
     */
    public function isOverdue(): bool
    {
        return $this->plant_by_date->isPast() && in_array($this->status?->code, ['draft', 'active']);
    }

    /**
     * Check if this crop plan is urgent (needs immediate attention).
     * 
     * Identifies plans requiring immediate action due to approaching
     * plant-by dates. Two-day threshold provides time for preparation
     * and resource allocation before critical planting deadline.
     * 
     * @return bool True if plan needs immediate attention (≤ 2 days)
     * @business_context Urgent plans require priority resource allocation
     * @usage Priority scheduling and alert systems
     */
    public function isUrgent(): bool
    {
        return $this->days_until_planting <= 2 && in_array($this->status?->code, ['draft', 'active']);
    }
}
