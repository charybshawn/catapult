<?php

namespace App\Filament\Resources\RecurringOrderResource\Pages;

use App\Filament\Resources\RecurringOrderResource;
use App\Services\RecurringOrderService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListRecurringOrders extends ListRecords
{
    protected static string $resource = RecurringOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_next_orders')
                ->label('Generate Next Orders')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action(function () {
                    $service = app(RecurringOrderService::class);
                    $results = $service->processRecurringOrders();
                    
                    $generatedCount = count($results['generated']);
                    $skippedCount = count($results['skipped']);
                    $errorCount = count($results['errors']);
                    
                    if ($generatedCount > 0) {
                        Notification::make()
                            ->title('Orders Generated Successfully')
                            ->body("Generated {$generatedCount} new orders" . 
                                   ($skippedCount > 0 ? ", skipped {$skippedCount}" : "") . 
                                   ($errorCount > 0 ? ", {$errorCount} errors" : ""))
                            ->success()
                            ->send();
                    } elseif ($skippedCount > 0) {
                        Notification::make()
                            ->title('No Orders Generated')
                            ->body("All {$skippedCount} recurring orders were skipped (not yet due)")
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
                    $this->refreshFormData();
                })
                ->requiresConfirmation()
                ->modalDescription('This will generate new orders for all recurring templates that are due for their next generation.')
                ->button(),
                
            Actions\CreateAction::make()
                ->label('Create Template')
                ->icon('heroicon-o-plus'),
        ];
    }
}
