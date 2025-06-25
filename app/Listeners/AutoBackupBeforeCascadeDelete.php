<?php

namespace App\Listeners;

use App\Models\Setting;
use App\Services\SimpleBackupService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AutoBackupBeforeCascadeDelete
{
    private SimpleBackupService $backupService;

    // Models that have significant cascading delete relationships
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

    public function __construct(SimpleBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function handle($event): void
    {
        // Check if auto-backup is enabled
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

        } catch (\Exception $e) {
            Log::error("Failed to create automatic backup before cascading delete", [
                'model' => get_class($model),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't prevent the deletion, but log the failure
            // In production, you might want to prevent deletion if backup fails
        }
    }

    private function shouldBackupForModel($model): bool
    {
        if (!$model instanceof Model) {
            return false;
        }

        return in_array(get_class($model), $this->criticalModels);
    }

    private function getModelShortName($model): string
    {
        $className = get_class($model);
        return strtolower(class_basename($className));
    }

    private function getModelIdentifier($model): string
    {
        // Try to get a meaningful identifier
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