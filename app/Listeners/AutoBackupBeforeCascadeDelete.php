<?php

namespace App\Listeners;

use Exception;
use App\Models\Setting;
use App\Services\SimpleBackupService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Automatic database backup system for critical model deletion protection.
 * 
 * Provides automatic database backup creation before cascading delete operations
 * on critical agricultural models. Prevents data loss from accidental deletions
 * of core business entities like orders, products, recipes, and suppliers that
 * have complex relationship cascades in microgreens production system.
 * 
 * @business_domain Data protection for agricultural business operations
 * @data_safety Automatic backup creation before destructive operations
 * @agricultural_context Protects crop data, orders, recipes, supplier information
 * @compliance Maintains data recovery capabilities for business continuity
 */
class AutoBackupBeforeCascadeDelete
{
    /**
     * Database backup service for creating automatic backups.
     * 
     * @var SimpleBackupService Service for database backup operations
     */
    private SimpleBackupService $backupService;

    /**
     * Critical agricultural models with significant cascading delete relationships.
     * 
     * Models that, when deleted, trigger extensive cascade deletions affecting
     * core agricultural business operations. Automatic backups protect against
     * accidental data loss from complex relationship dependencies.
     * 
     * @var array<string> Fully qualified class names of critical models
     * @business_critical Models essential to agricultural operations continuity
     */
    private array $criticalModels = [
        'App\Models\User',
        'App\Models\Order', 
        'App\Models\Product',
        'App\Models\Supplier',
        'App\Models\Recipe',
        'App\Models\TimeCard',
        'App\Models\MasterSeedCatalog',
        'App\Models\ProductMix',
        'App\Models\PackagingType',
        'App\Models\MasterCultivar',
    ];

    /**
     * Initialize the auto-backup listener with backup service dependency.
     * 
     * @param SimpleBackupService $backupService Service for creating database backups
     */
    public function __construct(SimpleBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Handle model deletion events by creating automatic backup before cascade.
     * 
     * Listens for model deletion events and creates database backups for critical
     * agricultural models before cascading deletes occur. Configurable via system
     * settings to enable/disable backup creation based on operational needs.
     * 
     * @param mixed $event Model deletion event containing the model being deleted
     * @return void
     * 
     * @business_process Data protection workflow for critical model deletions
     * @agricultural_safety Prevents loss of crop, order, and production data
     * @error_handling Logs backup failures but doesn't prevent deletions
     */
    public function handle($event): void
    {
        // Check if auto-backup is enabled via system settings
        if (!Setting::getValue('auto_backup_before_cascade_delete', true)) {
            return;
        }

        $model = $event->model ?? $event;
        
        // Only backup for critical models with cascading relationships
        if (!$this->shouldBackupForModel($model)) {
            return;
        }

        try {
            $modelName = $this->getModelShortName($model);
            $identifier = $this->getModelIdentifier($model);
            
            $backupName = "cascade_delete_{$modelName}_{$identifier}_" . now()->format('Y-m-d_H-i-s');
            
            Log::info("Creating automatic backup before cascading delete", [
                'model' => get_class($model),
                'identifier' => $identifier,
                'backup_name' => $backupName
            ]);

            $this->backupService->createBackup($backupName);
            
            Log::info("Automatic backup created successfully", [
                'backup_name' => $backupName
            ]);

        } catch (Exception $e) {
            Log::error("Failed to create automatic backup before cascading delete", [
                'model' => get_class($model),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Don't prevent the deletion, but log the failure
            // In production, you might want to prevent deletion if backup fails
        }
    }

    /**
     * Determine if automatic backup should be created for the given model.
     * 
     * Checks if the model being deleted is in the critical models list and
     * is a valid Eloquent model instance. Only critical agricultural business
     * models trigger automatic backup creation.
     * 
     * @param mixed $model Model object to evaluate for backup eligibility
     * @return bool True if model requires backup, false otherwise
     * 
     * @business_rule Only critical models with cascading relationships need backup
     * @performance_optimization Selective backup creation to reduce storage usage
     */
    private function shouldBackupForModel($model): bool
    {
        if (!$model instanceof Model) {
            return false;
        }

        return in_array(get_class($model), $this->criticalModels);
    }

    /**
     * Extract short model name for backup file naming.
     * 
     * Converts full model class name to simple lowercase name for use in
     * backup file naming convention. Helps create readable backup names
     * that identify the triggering model type.
     * 
     * @param mixed $model Model object to extract name from
     * @return string Lowercase short model name (e.g., 'product', 'order')
     * 
     * @naming_convention Consistent backup file naming for agricultural models
     */
    private function getModelShortName($model): string
    {
        $className = get_class($model);
        return strtolower(class_basename($className));
    }

    /**
     * Extract meaningful identifier from model for backup file naming.
     * 
     * Attempts to generate a human-readable identifier from the model being deleted
     * to make backup files easier to identify and correlate with business records.
     * Falls back through ID, name, email, or generic identifier.
     * 
     * @param mixed $model Model object to extract identifier from
     * @return string Model identifier for backup file naming
     * 
     * @business_context Helps identify which agricultural record triggered backup
     * @naming_convention Readable identifiers for backup file organization
     */
    private function getModelIdentifier($model): string
    {
        // Try to get a meaningful identifier for agricultural business records
        if (isset($model->id)) {
            return (string) $model->id;
        }
        
        if (isset($model->name)) {
            return slug($model->name);
        }
        
        if (isset($model->email)) {
            return slug($model->email);
        }
        
        return 'unknown';
    }
}