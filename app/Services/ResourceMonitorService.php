<?php

namespace App\Services;

use App\Services\CropTaskManagementService;
use App\Models\Consumable;
use App\Models\NotificationSetting;
use App\Models\TaskSchedule;
use App\Models\User;
use App\Notifications\ResourceActionRequired;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Agricultural resource monitoring and automated task processing service.
 * 
 * Manages critical agricultural inventory monitoring, crop stage transitions,
 * and automated notifications for resource management. Processes scheduled
 * tasks to ensure continuous operation of agricultural systems including
 * consumable stock levels, crop lifecycle management, and operational alerts.
 *
 * @business_domain Agricultural resource monitoring and automation
 * @related_services CropTaskManagementService, NotificationSetting
 * @used_by Scheduled task system, automated monitoring, resource alerts
 * @agricultural_context Prevents disruption of agricultural operations through proactive monitoring
 */
class ResourceMonitorService
{
    /**
     * Process a scheduled agricultural resource monitoring task.
     * 
     * Central dispatcher for various resource monitoring tasks including
     * consumable inventory checks, crop stage transitions, order monitoring,
     * and product availability alerts. Ensures agricultural operations
     * continue smoothly through automated resource management.
     *
     * @param TaskSchedule $task The scheduled task to process
     * @return array Processing result with success status and message
     * @agricultural_context Supports automated agricultural workflow management
     * @supported_types consumables, crops, orders, products, invoices
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
     * Process agricultural consumable inventory monitoring tasks.
     * 
     * Handles automated monitoring of agricultural supplies including seeds,
     * soil amendments, nutrients, packaging materials, and other consumables
     * essential for continuous agricultural production.
     *
     * @param TaskSchedule $task The consumables task to process
     * @return array Processing result including items monitored
     * @agricultural_context Prevents production delays due to supply shortages
     * @supported_tasks check_low_stock
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
     * Monitor consumable inventory levels and trigger restock notifications.
     * 
     * Identifies agricultural supplies that have reached restock thresholds
     * or are completely out of stock. Sends automated notifications to
     * farm managers to prevent production interruptions due to supply shortages.
     *
     * @param TaskSchedule $task The low stock monitoring task configuration
     * @return array Processing results including notification counts
     * @agricultural_context Critical for maintaining continuous agricultural production
     * @notifications Sends email alerts with direct links to filtered inventory views
     * @thresholds Uses consumable-specific restock_threshold values
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
     * Process automated crop lifecycle and stage transition tasks.
     * 
     * Handles scheduled agricultural crop operations including automated
     * stage transitions (soaking to germinating, growing to ready),
     * crop health monitoring, and harvest scheduling. Delegates complex
     * crop operations to specialized crop management services.
     *
     * @param TaskSchedule $task The crop management task to process
     * @return array Processing result from crop task management
     * @agricultural_context Automates time-sensitive crop lifecycle operations
     * @delegation Uses CropTaskManagementService for complex crop operations
     */
    protected function processCropsTask(TaskSchedule $task): array
    {
        // Check if this is a stage transition task
        if (str_starts_with($task->task_name, 'advance_to_')) {
            $cropTaskManagementService = app(CropTaskManagementService::class);
            return $cropTaskManagementService->processCropStageTask($task);
        }

        return [
            'success' => false,
            'message' => "Unknown crops task: {$task->task_name}",
        ];
    }
    
    /**
     * Process agricultural order monitoring and management tasks.
     * 
     * Handles automated order processing tasks including delivery reminders,
     * recurring order generation, invoice preparation, and customer
     * communication automation. Currently a placeholder for future implementation.
     *
     * @param TaskSchedule $task The order task to process
     * @return array Processing result (currently not implemented)
     * @todo Implement order automation tasks
     * @agricultural_context Will automate customer order lifecycle management
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
     * Process agricultural product monitoring and availability tasks.
     * 
     * Handles automated product management including inventory level monitoring,
     * price update notifications, seasonal availability updates, and
     * product catalog maintenance. Currently a placeholder for future implementation.
     *
     * @param TaskSchedule $task The product task to process
     * @return array Processing result (currently not implemented)
     * @todo Implement product monitoring tasks
     * @agricultural_context Will automate product catalog and availability management
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
     * Process agricultural invoice and billing automation tasks.
     * 
     * Handles automated invoice processing including consolidated invoice
     * generation, payment reminders, overdue account notifications, and
     * billing cycle management. Currently a placeholder for future implementation.
     *
     * @param TaskSchedule $task The invoice task to process
     * @return array Processing result (currently not implemented)
     * @todo Implement invoice automation tasks
     * @agricultural_context Will automate customer billing and payment processes
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