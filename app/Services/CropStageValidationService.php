<?php

namespace App\Services;

use App\Models\Crop;
use App\Models\CropStage;
use App\Models\Recipe;
use App\Models\TaskSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Agricultural crop stage transition validation service for microgreens production.
 * 
 * Enforces comprehensive business rules and agricultural constraints governing
 * crop lifecycle stage transitions in microgreens production. Validates timing,
 * recipe adherence, batch consistency, and production workflow integrity to
 * maintain quality standards and prevent agricultural operation errors.
 * 
 * @business_domain Agricultural production workflow validation and quality control
 * @agricultural_workflow Enforces microgreens growing stage progression rules
 * @production_safety Prevents invalid transitions that would compromise crop quality
 * @business_impact Maintains production standards and prevents costly agricultural mistakes
 * 
 * @agricultural_stages
 * - Soaking: Initial seed hydration (6-24 hours depending on variety)
 * - Germination: Seed sprouting phase (1-3 days in darkness/humidity)
 * - Blackout: Root establishment phase (0-4 days variety dependent)
 * - Light: Photosynthetic growth phase (3-10 days under grow lights)
 * - Harvested: Final stage indicating crop completion
 * 
 * @validation_categories
 * - Transition Rules: Valid forward/backward stage movements
 * - Required Fields: Mandatory data for each agricultural stage
 * - Timing Constraints: Minimum/maximum durations based on recipes
 * - Recipe Compliance: Stage requirements based on variety specifications
 * - Batch Consistency: Group operation validation for efficiency
 * - Dependency Checks: Prevents data corruption during stage changes
 * 
 * @business_rules
 * - Stage transitions must follow agricultural progression order
 * - Recipe requirements override default stage durations
 * - Batch operations require consistent timing and stage alignment
 * - Reversions require careful dependency validation
 * - Historical data integrity must be preserved
 * 
 * @agricultural_flexibility
 * - 10% early advancement tolerance for operational needs
 * - Recipe-specific stage skipping (e.g., no blackout for some varieties)
 * - Batch timing variations within acceptable windows
 * - Administrative overrides for quality control corrections
 * 
 * @example
 * // Validate crop stage advancement
 * $validator = new CropStageValidationService();
 * $result = $validator->canAdvanceToStage($crop, $nextStage, now());
 * 
 * if ($result['valid']) {
 *     // Proceed with stage transition
 * } else {
 *     // Handle validation errors: $result['errors']
 * }
 * 
 * // Validate batch operations
 * $batchResult = $validator->validateBatchConsistency($crops);
 * 
 * @features
 * - Comprehensive agricultural rule enforcement
 * - Recipe-based timing validation
 * - Batch operation consistency checking
 * - Dependency-aware stage reversion
 * - Detailed error and warning reporting
 * - Flexible tolerance for operational needs
 * 
 * @see Crop For crop model and agricultural data structure
 * @see CropStage For stage definitions and progression rules
 * @see Recipe For variety-specific growing requirements
 * @see CropStageTransitionService For actual transition execution
 */
class CropStageValidationService
{
    /**
     * Agricultural stage progression rules and constraints for microgreens production.
     * 
     * Defines comprehensive business rules governing crop lifecycle transitions,
     * including valid advancement paths, reversion capabilities, required data
     * fields, and validation checkpoints for each agricultural stage.
     * 
     * @business_context Core agricultural workflow rules for production quality
     * @agricultural_logic Enforces microgreens-specific growing stage requirements
     * @production_safety Prevents invalid transitions that compromise crop quality
     * 
     * @rule_structure
     * - can_advance_to: Valid forward stage transitions
     * - can_revert_to: Permitted backward stage corrections
     * - required_fields: Mandatory data for stage completion validation
     * - validates_on_advance: Additional checks before allowing transition
     * 
     * @agricultural_constraints
     * - Soaking: Must complete before germination, requires tray allocation
     * - Germination: Entry point for non-soaking varieties, requires planting timestamp
     * - Blackout: Optional stage (recipe-dependent), enables root establishment
     * - Light: Photosynthetic growth phase, final growing stage before harvest
     * - Harvested: Terminal stage, limited reversion to prevent data corruption
     */
    private const STAGE_RULES = [
        'soaking' => [
            'can_advance_to' => ['germination'],
            'can_revert_to' => [],
            'required_fields' => ['soaking_at', 'requires_soaking'],
            'validates_on_advance' => ['tray_numbers_required'],
        ],
        'germination' => [
            'can_advance_to' => ['blackout', 'light'], // Can skip blackout if recipe has 0 blackout days
            'can_revert_to' => ['soaking'], // Only if originally soaked
            'required_fields' => ['planting_at'],
            'validates_on_advance' => [],
        ],
        'blackout' => [
            'can_advance_to' => ['light'],
            'can_revert_to' => ['germination'],
            'required_fields' => ['germination_at'],
            'validates_on_advance' => [],
        ],
        'light' => [
            'can_advance_to' => ['harvested'],
            'can_revert_to' => ['blackout', 'germination'], // Can revert to germination if no blackout stage
            'required_fields' => ['germination_at'],
            'validates_on_advance' => [],
        ],
        'harvested' => [
            'can_advance_to' => [],
            'can_revert_to' => ['light'],
            'required_fields' => ['light_at'],
            'validates_on_advance' => [],
        ],
    ];

    /**
     * Validate agricultural crop stage advancement against comprehensive business rules.
     * 
     * Performs complete validation of proposed crop stage transition, checking
     * agricultural timing constraints, recipe compliance, required field completion,
     * and workflow progression rules. Prevents production errors that could
     * compromise crop quality or violate microgreens growing standards.
     * 
     * @business_purpose Enforce agricultural quality standards and prevent production errors
     * @agricultural_validation Comprehensive crop lifecycle transition rule enforcement
     * @production_safety Protects against invalid transitions that damage crops or operations
     * 
     * @param Crop $crop Agricultural crop requiring stage transition validation
     * @param CropStage $targetStage Desired agricultural stage for transition
     * @param Carbon $transitionTime Proposed timestamp for agricultural transition
     * @return array Comprehensive validation results with errors, warnings, and approval status
     *   - 'valid' (bool): Whether transition meets all agricultural requirements
     *   - 'errors' (array): Blocking issues preventing agricultural transition
     *   - 'warnings' (array): Non-blocking concerns about timing or conditions
     * 
     * @validation_checks
     * - Transition Permission: Verifies stage advancement is agriculturally valid
     * - Required Fields: Ensures current stage completion data is present
     * - Timing Constraints: Validates minimum agricultural stage duration
     * - Timestamp Sequence: Maintains chronological agricultural progression
     * - Recipe Compliance: Enforces variety-specific growing requirements
     * 
     * @agricultural_rules
     * - Must follow defined stage progression (soaking→germination→blackout→light→harvested)
     * - Minimum stage durations based on recipe specifications
     * - Required agricultural data completion before advancement
     * - Recipe-specific stage skipping permissions (blackout bypass)
     * - 10% early advancement tolerance for operational flexibility
     * 
     * @example
     * // Validate advancing from germination to light stage
     * $result = $validator->canAdvanceToStage($crop, $lightStage, now());
     * 
     * if ($result['valid']) {
     *     // Safe to advance crop stage
     *     $crop->advanceToStage($lightStage);
     * } else {
     *     // Review blocking errors
     *     foreach ($result['errors'] as $error) {
     *         Log::warning("Stage advancement blocked: $error");
     *     }
     * }
     * 
     * // Check for operational warnings
     * if (!empty($result['warnings'])) {
     *     // Log concerns but allow advancement
     *     foreach ($result['warnings'] as $warning) {
     *         Log::info("Agricultural concern: $warning");
     *     }
     * }
     */
    public function canAdvanceToStage(Crop $crop, CropStage $targetStage, Carbon $transitionTime): array
    {
        $currentStage = CropStage::find($crop->current_stage_id);
        if (!$currentStage) {
            return [
                'valid' => false,
                'errors' => ['Current stage not found'],
                'warnings' => []
            ];
        }
        
        $errors = [];

        // Check if transition is allowed
        if (!$this->isTransitionAllowed($currentStage, $targetStage)) {
            $errors[] = "Cannot advance from {$currentStage->name} to {$targetStage->name}";
        }

        // Validate required fields are present
        $missingFields = $this->validateRequiredFields($crop, $currentStage);
        if (!empty($missingFields)) {
            $errors[] = "Missing required fields for current stage: " . implode(', ', $missingFields);
        }

        // Validate minimum stage duration
        $durationError = $this->validateStageDuration($crop, $currentStage, $transitionTime);
        if ($durationError) {
            $errors[] = $durationError;
        }

        // Validate timestamp sequence
        $sequenceError = $this->validateTimestampWillBeValid($crop, $targetStage, $transitionTime);
        if ($sequenceError) {
            $errors[] = $sequenceError;
        }

        // Check recipe-specific rules
        $recipeErrors = $this->validateRecipeRules($crop, $currentStage, $targetStage);
        if (!empty($recipeErrors)) {
            $errors = array_merge($errors, $recipeErrors);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $this->getTransitionWarnings($crop, $currentStage, $targetStage),
        ];
    }

    /**
     * Validate if a crop can revert to the previous stage
     *
     * @param Crop $crop
     * @param CropStage $targetStage
     * @return array Validation result
     */
    public function canRevertToStage(Crop $crop, CropStage $targetStage): array
    {
        $currentStage = CropStage::find($crop->current_stage_id);
        if (!$currentStage) {
            return [
                'valid' => false,
                'errors' => ['Current stage not found'],
                'warnings' => []
            ];
        }
        
        $errors = [];

        // Check if reversion is allowed
        if (!$this->isReversionAllowed($currentStage, $targetStage)) {
            $errors[] = "Cannot revert from {$currentStage->name} to {$targetStage->name}";
        }

        // Check for dependent data that would be orphaned
        $dependencies = $this->checkDependencies($crop, $currentStage);
        if (!empty($dependencies)) {
            $errors[] = "Cannot revert due to existing dependencies: " . implode(', ', $dependencies);
        }

        // Special validation for harvested crops
        if ($currentStage->code === 'harvested' && $crop->harvests()->exists()) {
            $errors[] = "Cannot revert crops with harvest records";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $this->getReversionWarnings($crop, $currentStage, $targetStage),
        ];
    }

    /**
     * Validate a batch can transition together
     *
     * @param Collection $crops
     * @return array Detailed validation results
     */
    public function validateBatchConsistency(Collection $crops): array
    {
        $issues = [];
        $details = [];

        // All crops must be in same stage
        $stages = $crops->pluck('current_stage_id')->unique();
        if ($stages->count() > 1) {
            $stageNames = CropStage::whereIn('id', $stages)->pluck('name')->implode(', ');
            $issues[] = "Crops are in different stages: {$stageNames}";
            $details['stage_mismatch'] = true;
        }

        // All crops must have same recipe
        $recipes = $crops->pluck('recipe_id')->unique();
        if ($recipes->count() > 1) {
            $issues[] = "Batch contains {$recipes->count()} different recipes";
            $details['recipe_mismatch'] = true;
        }

        // Check for timing inconsistencies
        $timingIssues = $this->validateBatchTiming($crops);
        if (!empty($timingIssues)) {
            $issues = array_merge($issues, $timingIssues);
            $details['timing_issues'] = $timingIssues;
        }

        // Check for individual crop issues
        $cropIssues = [];
        foreach ($crops as $crop) {
            if ($crop->watering_suspended_at) {
                $cropIssues[] = "Crop {$crop->tray_number} has suspended watering";
            }
        }

        if (!empty($cropIssues)) {
            $issues = array_merge($issues, $cropIssues);
            $details['crop_issues'] = $cropIssues;
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'details' => $details,
            'summary' => [
                'total_crops' => $crops->count(),
                'unique_stages' => $stages->count(),
                'unique_recipes' => $recipes->count(),
                'suspended_count' => $crops->whereNotNull('watering_suspended_at')->count(),
            ]
        ];
    }

    /**
     * Check if transition is allowed between stages
     */
    private function isTransitionAllowed(CropStage $from, CropStage $to): bool
    {
        $rules = self::STAGE_RULES[$from->code] ?? [];
        $allowedTransitions = $rules['can_advance_to'] ?? [];
        
        return in_array($to->code, $allowedTransitions);
    }

    /**
     * Check if reversion is allowed between stages
     */
    private function isReversionAllowed(CropStage $from, CropStage $to): bool
    {
        $rules = self::STAGE_RULES[$from->code] ?? [];
        $allowedReversions = $rules['can_revert_to'] ?? [];
        
        return in_array($to->code, $allowedReversions);
    }

    /**
     * Validate required fields for current stage
     */
    private function validateRequiredFields(Crop $crop, CropStage $stage): array
    {
        $rules = self::STAGE_RULES[$stage->code] ?? [];
        $requiredFields = $rules['required_fields'] ?? [];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($crop->$field)) {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    /**
     * Validate minimum stage duration has been met
     */
    private function validateStageDuration(Crop $crop, CropStage $currentStage, Carbon $transitionTime): ?string
    {
        // Get the timestamp field for current stage
        $timestampField = $this->getTimestampField($currentStage->code);
        if (!$timestampField || !$crop->$timestampField) {
            return null;
        }

        $stageStartTime = $crop->$timestampField;
        $actualDuration = $stageStartTime->diffInHours($transitionTime);
        
        // Get expected duration from recipe
        $recipe = $crop->recipe;
        if (!$recipe) {
            return null;
        }

        $expectedDuration = $this->getExpectedStageDuration($recipe, $currentStage);
        if ($expectedDuration === null) {
            return null;
        }

        // Allow 10% early advancement for agricultural operational flexibility
        $minimumDuration = $expectedDuration * 0.9;
        
        if ($actualDuration < $minimumDuration) {
            return sprintf(
                "%s stage requires at least %.1f hours (%.1f days), only %.1f hours elapsed",
                $currentStage->name,
                $minimumDuration,
                $minimumDuration / 24,
                $actualDuration
            );
        }

        return null;
    }

    /**
     * Validate timestamp will maintain proper sequence
     */
    private function validateTimestampWillBeValid(Crop $crop, CropStage $targetStage, Carbon $transitionTime): ?string
    {
        $targetField = $this->getTimestampField($targetStage->code);
        if (!$targetField) {
            return null;
        }

        // Check against all existing timestamps
        $timestamps = [
            'soaking' => $crop->soaking_at,
            'germination' => $crop->germination_at,
            'blackout' => $crop->blackout_at,
            'light' => $crop->light_at,
            'harvested' => $crop->harvested_at,
        ];

        foreach ($timestamps as $stage => $timestamp) {
            if (!$timestamp || $stage === $targetStage->code) {
                continue;
            }

            $stageObj = CropStage::where('code', $stage)->first();
            if (!$stageObj) {
                continue;
            }

            // If target stage should come after this stage, transition time must be after timestamp
            if ($targetStage->sort_order > $stageObj->sort_order && $transitionTime->isBefore($timestamp)) {
                return "{$targetStage->name} time must be after {$stage} time ({$timestamp->format('Y-m-d H:i')})";
            }

            // If target stage should come before this stage, transition time must be before timestamp
            if ($targetStage->sort_order < $stageObj->sort_order && $transitionTime->isAfter($timestamp)) {
                return "{$targetStage->name} time must be before {$stage} time ({$timestamp->format('Y-m-d H:i')})";
            }
        }

        return null;
    }

    /**
     * Validate recipe-specific rules
     */
    private function validateRecipeRules(Crop $crop, CropStage $currentStage, CropStage $targetStage): array
    {
        $errors = [];
        $recipe = $crop->recipe;

        if (!$recipe) {
            return $errors;
        }

        // Check if trying to advance to blackout when recipe has 0 blackout days
        if ($targetStage->code === 'blackout' && $recipe->blackout_days == 0) {
            $errors[] = "Recipe has no blackout stage (0 days)";
        }

        // Check if skipping required stages
        if ($currentStage->code === 'germination' && $targetStage->code === 'light') {
            if ($recipe->blackout_days > 0) {
                $errors[] = "Cannot skip blackout stage when recipe requires {$recipe->blackout_days} blackout days";
            }
        }

        return $errors;
    }

    /**
     * Check for dependencies that would prevent reversion
     */
    private function checkDependencies(Crop $crop, CropStage $currentStage): array
    {
        $dependencies = [];

        // Check for harvest records
        if ($currentStage->code === 'harvested' && $crop->harvests()->exists()) {
            $dependencies[] = 'harvest records';
        }

        // Check for active agricultural task schedules that shouldn't be disrupted
        $criticalTasks = TaskSchedule::where('resource_type', 'crops')
            ->where('conditions->crop_id', $crop->id)
            ->where('is_active', true)
            ->where('task_name', 'LIKE', '%critical%')
            ->exists();

        if ($criticalTasks) {
            $dependencies[] = 'critical active tasks';
        }

        return $dependencies;
    }

    /**
     * Get warnings for transitions (non-blocking issues)
     */
    private function getTransitionWarnings(Crop $crop, CropStage $from, CropStage $to): array
    {
        $warnings = [];

        // Warn if advancing earlier than expected
        $recipe = $crop->recipe;
        if ($recipe) {
            $expectedDuration = $this->getExpectedStageDuration($recipe, $from);
            if ($expectedDuration) {
                $timestampField = $this->getTimestampField($from->code);
                if ($timestampField && $crop->$timestampField) {
                    $actualDuration = $crop->$timestampField->diffInHours(now());
                    if ($actualDuration < $expectedDuration * 0.75) {
                        $warnings[] = sprintf(
                            "Advancing earlier than typical (%.1f hours vs expected %.1f hours)",
                            $actualDuration,
                            $expectedDuration
                        );
                    }
                }
            }
        }

        // Warn about suspended watering
        if ($crop->watering_suspended_at) {
            $warnings[] = "Crop has suspended watering since " . $crop->watering_suspended_at->format('Y-m-d H:i');
        }

        return $warnings;
    }

    /**
     * Get warnings for reversions
     */
    private function getReversionWarnings(Crop $crop, CropStage $from, CropStage $to): array
    {
        $warnings = [];

        // Warn about data that will be cleared
        $timestampField = $this->getTimestampField($from->code);
        if ($timestampField && $crop->$timestampField) {
            $warnings[] = "Will clear {$from->name} timestamp: " . $crop->$timestampField->format('Y-m-d H:i');
        }

        // Warn about task schedules that will be affected
        $activeTasks = TaskSchedule::where('resource_type', 'crops')
            ->where('conditions->crop_id', $crop->id)
            ->where('is_active', true)
            ->count();

        if ($activeTasks > 0) {
            $warnings[] = "{$activeTasks} active task schedules will be affected";
        }

        return $warnings;
    }

    /**
     * Validate timing consistency across a batch
     */
    private function validateBatchTiming(Collection $crops): array
    {
        $issues = [];

        // For soaking crops, check if soaking_at times are consistent
        if ($crops->first()->requires_soaking) {
            $soakingTimes = $crops->pluck('soaking_at')->filter()->unique();
            if ($soakingTimes->count() > 1) {
                $issues[] = "Batch has inconsistent soaking times";
            }
        }

        // Check planting times for non-soaking crops
        $plantingTimes = $crops->pluck('planting_at')->filter()->unique();
        if ($plantingTimes->count() > 1) {
            $timeDiff = $plantingTimes->max()->diffInHours($plantingTimes->min());
            if ($timeDiff > 1) {
                $issues[] = "Batch has planting times spanning {$timeDiff} hours";
            }
        }

        return $issues;
    }

    /**
     * Get timestamp field for a stage code
     */
    private function getTimestampField(string $stageCode): ?string
    {
        $map = [
            'soaking' => 'soaking_at',
            'germination' => 'germination_at',
            'blackout' => 'blackout_at',
            'light' => 'light_at',
            'harvested' => 'harvested_at',
        ];

        return $map[$stageCode] ?? null;
    }

    /**
     * Get expected stage duration from recipe
     */
    private function getExpectedStageDuration(Recipe $recipe, CropStage $stage): ?float
    {
        switch ($stage->code) {
            case 'soaking':
                return $recipe->seed_soak_hours;
            case 'germination':
                return ($recipe->germination_days ?? 0) * 24;
            case 'blackout':
                return ($recipe->blackout_days ?? 0) * 24;
            case 'light':
                return ($recipe->light_days ?? 0) * 24;
            default:
                return null;
        }
    }
}