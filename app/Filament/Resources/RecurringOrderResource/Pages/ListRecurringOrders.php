<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use App\Filament\Resources\RecurringOrderResource;
use App\Services\RecurringOrderService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

/**
 * List recurring orders page for agricultural delivery automation management.
 * Provides comprehensive management interface for recurring order templates including
 * manual generation triggers, template creation, and monitoring of automated delivery schedules.
 *
 * @business_domain Agricultural recurring orders and automated delivery management
 * @page_context Recurring order template listing with generation and management capabilities
 * @automation_features Manual order generation trigger, template monitoring, status tracking
 * @service_integration RecurringOrderService for order processing and generation operations
 * @notification_system Comprehensive feedback for generation results and error handling
 */
class ListRecurringOrders extends ListRecords
{
    protected static string $resource = RecurringOrderResource::class;

    /**
     * Get header actions for recurring order template management and generation operations.
     * Provides manual generation trigger and template creation capabilities for agricultural
     * delivery automation with comprehensive result notification and error handling.
     *
     * @manual_generation Trigger for processing due recurring order templates
     * @template_creation Action for creating new recurring delivery schedules
     * @result_notification Comprehensive feedback on generation success, warnings, and errors
     * @agricultural_context Supports continuous microgreens delivery automation
     * @return array Header actions for recurring order management
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_next_orders')
                ->label('Generate Next Orders')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    $service = app(RecurringOrderService::class);
                    $results = $service->processRecurringOrders();
                    
                    $generatedCount = $results['generated'];
                    $processedCount = $results['processed'];
                    $deactivatedCount = $results['deactivated'];
                    $errorCount = count($results['errors']);
                    
                    if ($generatedCount > 0) {
                        Notification::make()
                            ->title('Orders Generated Successfully')
                            ->body("Generated {$generatedCount} new orders from {$processedCount} templates" . 
                                   ($deactivatedCount > 0 ? ", deactivated {$deactivatedCount}" : "") . 
                                   ($errorCount > 0 ? ", {$errorCount} errors" : ""))
                            ->success()
                            ->send();
                    } elseif ($processedCount > 0) {
                        Notification::make()
                            ->title('No Orders Generated')
                            ->body("Processed {$processedCount} recurring orders but none were due for generation" . 
                                   ($deactivatedCount > 0 ? ", deactivated {$deactivatedCount}" : ""))
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No Active Recurring Orders')
                            ->body('No active recurring order templates found')
                            ->info()
                            ->send();
                    }
                    
                    // Refresh the table to show updated next generation dates
                    $this->resetTable();
                })
                ->requiresConfirmation()
                ->modalDescription('This will generate new orders for all recurring templates that are due for their next generation.')
                ->button(),
                
            CreateAction::make()
                ->label('Create Template')
                ->icon('heroicon-o-plus'),
        ];
    }
}
