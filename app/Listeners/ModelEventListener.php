<?php

namespace App\Listeners;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Comprehensive Eloquent model lifecycle monitoring for agricultural business operations.
 * 
 * Monitors all model events (create, update, delete, retrieve) across the agricultural
 * microgreens production system. Provides detailed audit trail for business-critical
 * models including crops, orders, products, and inventory changes. Supports configurable
 * logging levels and model-specific event filtering for optimal monitoring coverage.
 * 
 * @business_domain Complete model lifecycle monitoring for agricultural operations
 * @audit_trail Comprehensive change tracking for business compliance and debugging
 * @agricultural_models Crops, orders, products, recipes, inventory, customer data
 * @configuration_driven Model-specific logging rules and event filtering
 */
class ModelEventListener
{
    /**
     * Models excluded from lifecycle logging to prevent recursive logging loops.
     * 
     * Models that should not trigger activity logging to prevent infinite loops
     * and unnecessary noise in audit trails. Primarily excludes activity log
     * models themselves and other monitoring-related models.
     * 
     * @var array<class-string> Fully qualified model class names to exclude
     */
    protected array $excludedModels = [
        Activity::class,
    ];

    /**
     * Handle model retrieval events for agricultural data access monitoring.
     * 
     * Logs model retrieval with context about loaded attributes and relationships
     * for agricultural data access pattern analysis and security monitoring.
     * Tracks access to sensitive agricultural business data.
     * 
     * @param Model $model Retrieved model instance
     * @return void
     * 
     * @access_monitoring Tracks agricultural data access patterns
     * @security_audit Monitors sensitive agricultural business data access
     */
    public function retrieved(Model $model): void
    {
        if ($this->shouldLog($model, 'retrieved')) {
            activity('model_retrieved')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'attributes_loaded' => array_keys($model->getAttributes()),
                    'relations_loaded' => array_keys($model->getRelations()),
                    'accessed_from' => Request::path(),
                ])
                ->log('Model retrieved');
        }
    }

    /**
     * Handle model creation initiation events for agricultural business operations.
     * 
     * Logs model creation attempts before persistence with all attribute data
     * for agricultural business audit trail. Critical for tracking creation
     * of crops, orders, products, and other core business entities.
     * 
     * @param Model $model Model instance being created
     * @return void
     * 
     * @business_audit Pre-creation logging for agricultural business entities
     * @data_integrity Tracks all agricultural data before database persistence
     */
    public function creating(Model $model): void
    {
        if ($this->shouldLog($model, 'creating')) {
            activity('model_creating')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'attributes' => $model->getAttributes(),
                    'creating_from' => Request::path(),
                ])
                ->log('Model being created');
        }
    }

    /**
     * Handle model update initiation events with change tracking for agricultural operations.
     * 
     * Logs model update attempts with original values and proposed changes
     * for comprehensive agricultural business change audit trail. Essential
     * for tracking modifications to crops, orders, inventory, and pricing.
     * 
     * @param Model $model Model instance being updated
     * @return void
     * 
     * @change_tracking Detailed before/after comparison for agricultural data
     * @business_compliance Audit trail for agricultural business data modifications
     */
    public function updating(Model $model): void
    {
        if ($this->shouldLog($model, 'updating')) {
            activity('model_updating')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'original' => $model->getOriginal(),
                    'changes' => $model->getDirty(),
                    'updating_from' => Request::path(),
                ])
                ->log('Model being updated');
        }
    }

    /**
     * Handle model save events for both creation and update operations in agricultural system.
     * 
     * Logs model save operations with context about whether this is a new record
     * creation or existing record update. Provides unified save tracking for
     * agricultural business entities across all persistence operations.
     * 
     * @param Model $model Model instance being saved
     * @return void
     * 
     * @unified_tracking Covers both create and update operations for agricultural models
     * @persistence_monitoring Tracks all database write operations for business entities
     */
    public function saving(Model $model): void
    {
        if ($this->shouldLog($model, 'saving')) {
            $isCreating = !$model->exists;
            
            activity('model_saving')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'is_creating' => $isCreating,
                    'attributes' => $model->getAttributes(),
                    'dirty' => $model->getDirty(),
                ])
                ->log($isCreating ? 'Model being saved (new)' : 'Model being saved (existing)');
        }
    }

    /**
     * Handle model deletion initiation events for agricultural data protection.
     * 
     * Logs model deletion attempts with complete attribute capture before removal
     * from database. Critical for agricultural business continuity and recovery
     * from accidental deletion of crops, orders, or production data.
     * 
     * @param Model $model Model instance being deleted
     * @return void
     * 
     * @data_protection Pre-deletion logging for agricultural business data recovery
     * @business_continuity Tracks deletion of critical agricultural entities
     */
    public function deleting(Model $model): void
    {
        if ($this->shouldLog($model, 'deleting')) {
            activity('model_deleting')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'attributes' => $model->getAttributes(),
                    'is_soft_delete' => method_exists($model, 'trashed'),
                    'deleting_from' => Request::path(),
                ])
                ->log('Model being deleted');
        }
    }

    /**
     * Handle model restoration events from soft delete for agricultural data recovery.
     * 
     * Logs model restoration from soft delete state for agricultural business
     * data recovery operations. Important for tracking restoration of crops,
     * orders, or other accidentally deleted agricultural entities.
     * 
     * @param Model $model Model instance being restored from soft delete
     * @return void
     * 
     * @data_recovery Tracks restoration of soft-deleted agricultural entities
     * @business_restoration Agricultural data recovery audit trail
     */
    public function restored(Model $model): void
    {
        if ($this->shouldLog($model, 'restored')) {
            activity('model_restored')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'restored_from' => Request::path(),
                ])
                ->log('Model restored from soft delete');
        }
    }

    /**
     * Handle permanent model deletion events for critical agricultural data loss tracking.
     * 
     * Logs permanent model deletion (bypass soft delete) with complete data capture
     * for critical agricultural business audit trail. Tracks permanent removal
     * of crops, orders, or production data that cannot be recovered.
     * 
     * @param Model $model Model instance being permanently deleted
     * @return void
     * 
     * @permanent_deletion Irreversible agricultural data removal tracking
     * @critical_audit High-priority logging for permanent business data loss
     */
    public function forceDeleted(Model $model): void
    {
        if ($this->shouldLog($model, 'forceDeleted')) {
            activity('model_force_deleted')
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'model_id' => $model->getKey(),
                    'attributes' => $model->getAttributes(),
                    'force_deleted_from' => Request::path(),
                ])
                ->log('Model permanently deleted');
        }
    }

    /**
     * Handle model replication events for agricultural data duplication tracking.
     * 
     * Logs model replication operations for agricultural business entity duplication
     * such as cloning product templates, recurring order patterns, or recipe
     * variations. Important for tracking data derivation and template usage.
     * 
     * @param Model $model Model instance being replicated
     * @return void
     * 
     * @data_duplication Tracks agricultural business entity replication
     * @template_usage Monitors cloning of products, recipes, and order templates
     */
    public function replicating(Model $model): void
    {
        if ($this->shouldLog($model, 'replicating')) {
            activity('model_replicating')
                ->performedOn($model)
                ->causedBy(Auth::user())
                ->withProperties([
                    'model_class' => get_class($model),
                    'original_id' => $model->getKey(),
                    'attributes' => $model->getAttributes(),
                ])
                ->log('Model being replicated');
        }
    }

    /**
     * Determine if model event should be logged based on configuration and model settings.
     * 
     * Applies comprehensive filtering logic including model exclusions, event-specific
     * rules, and global configuration to determine logging eligibility. Supports
     * model-level configuration for fine-grained agricultural data monitoring.
     * 
     * @param Model $model Model instance to evaluate for logging
     * @param string $event Model event name (creating, updating, deleting, etc.)
     * @return bool True if event should be logged, false to skip
     * 
     * @filtering_logic Multi-level rules for agricultural model monitoring
     * @configuration_driven Respects model-specific and global logging settings
     */
    protected function shouldLog(Model $model, string $event): bool
    {
        // Skip excluded models
        if (in_array(get_class($model), $this->excludedModels)) {
            return false;
        }

        // Skip if model explicitly disables logging
        if (property_exists($model, 'logModelEvents') && $model->logModelEvents === false) {
            return false;
        }

        // Skip specific events if configured
        if (property_exists($model, 'dontLogEvents') && in_array($event, $model->dontLogEvents)) {
            return false;
        }

        // Check if only specific events should be logged
        if (property_exists($model, 'onlyLogEvents') && !in_array($event, $model->onlyLogEvents)) {
            return false;
        }

        // Check global configuration
        $enabledEvents = config('logging.model_events', ['creating', 'updating', 'deleting']);
        return in_array($event, $enabledEvents);
    }

    /**
     * Register comprehensive model event listeners for agricultural system monitoring.
     * 
     * Dynamically registers event listeners for all Eloquent model lifecycle events
     * to provide complete agricultural business entity monitoring coverage.
     * Called during application bootstrap for system-wide model monitoring.
     * 
     * @return void
     * 
     * @system_bootstrap Registers listeners during application initialization
     * @comprehensive_monitoring Covers all model events for agricultural entities
     */
    public static function registerModelListeners(): void
    {
        $events = [
            'retrieved',
            'creating',
            'created',
            'updating',
            'updated',
            'saving',
            'saved',
            'deleting',
            'deleted',
            'restoring',
            'restored',
            'replicating',
        ];

        foreach ($events as $event) {
            Model::$event(function (Model $model) use ($event) {
                app(static::class)->$event($model);
            });
        }
    }
}