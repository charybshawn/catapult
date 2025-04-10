<?php

namespace App\Services;

use App\Models\Consumable;
use App\Models\NotificationSetting;
use App\Models\TaskSchedule;
use App\Models\User;
use App\Notifications\ResourceActionRequired;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ResourceMonitorService
{
    /**
     * Process a scheduled task.
     *
     * @param TaskSchedule $task
     * @return array
     */
    public function processTask(TaskSchedule $task): array
    {
        // Determine which type of resource to process
        return match ($task->resource_type) {
            'consumables' => $this->processConsumablesTask($task),
            'crops' => $this->processCropsTask($task),
            'orders' => $this->processOrdersTask($task),
            'products' => $this->processProductsTask($task),
            'invoices' => $this->processInvoicesTask($task),
            default => [
                'success' => false,
                'message' => "Unknown resource type: {$task->resource_type}",
            ],
        };
    }
    
    /**
     * Process a consumables task.
     *
     * @param TaskSchedule $task
     * @return array
     */
    protected function processConsumablesTask(TaskSchedule $task): array
    {
        // For now, we'll implement the low stock monitoring
        if ($task->task_name === 'check_low_stock') {
            return $this->checkLowStockConsumables($task);
        }
        
        return [
            'success' => false,
            'message' => "Unknown consumables task: {$task->task_name}",
        ];
    }
    
    /**
     * Check for low stock consumable items.
     *
     * @param TaskSchedule $task
     * @return array
     */
    protected function checkLowStockConsumables(TaskSchedule $task): array
    {
        // Get items where quantity is less than or equal to restock_threshold
        $lowStockItems = Consumable::whereRaw('current_stock <= restock_threshold')
            ->where('current_stock', '>', 0) // Exclude out of stock items
            ->where('is_active', true)
            ->get();
        
        $outOfStockItems = Consumable::where('current_stock', '<=', 0)
            ->where('is_active', true)
            ->get();
        
        $processedCount = 0;
        
        // Process low stock items
        if ($lowStockItems->count() > 0) {
            $setting = NotificationSetting::findByTypeAndEvent('consumables', 'low_stock');
            
            if ($setting && $setting->shouldSendEmail()) {
                $recipients = collect($setting->recipients);
                
                if ($recipients->isNotEmpty()) {
                    $data = [
                        'items' => $lowStockItems->map(function ($item) {
                            return [
                                'name' => $item->display_name,
                                'quantity' => $item->current_stock,
                                'unit' => $item->unit,
                                'restock_quantity' => $item->restock_quantity,
                            ];
                        })->toArray(),
                        'count' => $lowStockItems->count(),
                    ];
                    
                    $subject = $setting->getEmailSubject($data);
                    $body = $setting->getEmailBody($data);
                    
                    Notification::route('mail', $recipients->toArray())
                        ->notify(new ResourceActionRequired(
                            $subject,
                            $body,
                            route('filament.admin.resources.consumables.index', ['tableFilters[needs_restock]' => true]),
                            'View Low Stock Items'
                        ));
                    
                    $processedCount += $lowStockItems->count();
                }
            }
        }
        
        // Process out of stock items
        if ($outOfStockItems->count() > 0) {
            $setting = NotificationSetting::findByTypeAndEvent('consumables', 'out_of_stock');
            
            if ($setting && $setting->shouldSendEmail()) {
                $recipients = collect($setting->recipients);
                
                if ($recipients->isNotEmpty()) {
                    $data = [
                        'items' => $outOfStockItems->map(function ($item) {
                            return [
                                'name' => $item->display_name,
                                'restock_quantity' => $item->restock_quantity,
                            ];
                        })->toArray(),
                        'count' => $outOfStockItems->count(),
                    ];
                    
                    $subject = $setting->getEmailSubject($data);
                    $body = $setting->getEmailBody($data);
                    
                    Notification::route('mail', $recipients->toArray())
                        ->notify(new ResourceActionRequired(
                            $subject,
                            $body,
                            route('filament.admin.resources.consumables.index', ['tableFilters[out_of_stock]' => true]),
                            'View Out of Stock Items'
                        ));
                    
                    $processedCount += $outOfStockItems->count();
                }
            }
        }
        
        return [
            'success' => true,
            'message' => "Processed {$processedCount} consumable items",
        ];
    }
    
    /**
     * Process a crops task.
     *
     * @param TaskSchedule $task
     * @return array
     */
    protected function processCropsTask(TaskSchedule $task): array
    {
        // Check if this is a stage transition task
        if (str_starts_with($task->task_name, 'advance_to_')) {
            $cropTaskService = new CropTaskService();
            return $cropTaskService->processCropStageTask($task);
        }

        return [
            'success' => false,
            'message' => "Unknown crops task: {$task->task_name}",
        ];
    }
    
    /**
     * Process an orders task.
     *
     * @param TaskSchedule $task
     * @return array
     */
    protected function processOrdersTask(TaskSchedule $task): array
    {
        // To be implemented in future phases
        return [
            'success' => false,
            'message' => "Orders tasks not yet implemented",
        ];
    }
    
    /**
     * Process a products task.
     *
     * @param TaskSchedule $task
     * @return array
     */
    protected function processProductsTask(TaskSchedule $task): array
    {
        // To be implemented in future phases
        return [
            'success' => false,
            'message' => "Products tasks not yet implemented",
        ];
    }
    
    /**
     * Process an invoices task.
     *
     * @param TaskSchedule $task
     * @return array
     */
    protected function processInvoicesTask(TaskSchedule $task): array
    {
        // To be implemented in future phases
        return [
            'success' => false,
            'message' => "Invoices tasks not yet implemented",
        ];
    }
} 