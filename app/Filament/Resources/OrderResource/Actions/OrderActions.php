<?php

namespace App\Filament\Resources\OrderResource\Actions;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\OrderPlanningService;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

class OrderActions
{
    /**
     * Get all row actions for the table
     */
    public static function getRowActions(): array
    {
        return [
            static::getGenerateNextRecurringAction(),
            static::getRecalculatePricesAction(),
            static::getGenerateCropPlansAction(),
            static::getConvertToInvoiceAction(),
            static::getConvertToRecurringAction(),
            static::getTransitionStatusAction(),
        ];
    }

    /**
     * Get all bulk actions for the table
     */
    public static function getBulkActions(): array
    {
        return [
            static::getCreateConsolidatedInvoiceAction(),
            static::getBulkStatusUpdateAction(),
        ];
    }

    protected static function getGenerateNextRecurringAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generate_next_recurring')
            ->label('Generate Next Order')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->visible(fn (Order $record): bool => 
                $record->status?->code === 'template' && 
                $record->is_recurring
            )
            ->requiresConfirmation()
            ->modalHeading('Generate Next Recurring Order')
            ->modalDescription(fn (Order $record) => 
                "This will create the next order in the recurring series for {$record->customer->contact_name}."
            )
            ->action(function (Order $record) {
                try {
                    $recurringOrderService = app(\App\Services\RecurringOrderService::class);
                    $newOrder = $recurringOrderService->generateNextOrder($record);
                    
                    if ($newOrder) {
                        Notification::make()
                            ->title('Recurring Order Generated')
                            ->body("Order #{$newOrder->id} has been created successfully.")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('View Order')
                                    ->url(route('filament.admin.resources.orders.edit', ['record' => $newOrder->id]))
                            ])
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No Order Generated')
                            ->body('No new order was generated. It may not be time for the next recurring order yet.')
                            ->warning()
                            ->send();
                    }
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error Generating Order')
                        ->body('Failed to generate recurring order: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getRecalculatePricesAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('recalculate_prices')
            ->label('Recalculate Prices')
            ->icon('heroicon-o-calculator')
            ->color('info')
            ->visible(fn (Order $record): bool => 
                $record->status?->code !== 'template' && 
                $record->status?->code !== 'cancelled' &&
                !$record->status?->is_final &&
                $record->customer->isWholesaleCustomer() &&
                $record->orderItems->isNotEmpty()
            )
            ->requiresConfirmation()
            ->modalHeading('Recalculate Order Prices')
            ->modalDescription(function (Order $record) {
                $currentTotal = $record->totalAmount();
                $discount = $record->customer->wholesale_discount_percentage ?? 0;
                return "This will recalculate all item prices using the current wholesale discount ({$discount}%). Current total: $" . number_format($currentTotal, 2);
            })
            ->action(function (Order $record) {
                try {
                    $oldTotal = $record->totalAmount();
                    $updatedItems = 0;
                    
                    foreach ($record->orderItems as $item) {
                        if (!$item->product || !$item->price_variation_id) {
                            continue;
                        }
                        
                        $currentPrice = $item->product->getPriceForSpecificCustomer(
                            $record->customer,
                            $item->price_variation_id
                        );
                        
                        if (abs($currentPrice - $item->price) > 0.001) {
                            $item->price = $currentPrice;
                            $item->save();
                            $updatedItems++;
                        }
                    }
                    
                    $newTotal = $record->totalAmount();
                    $difference = $oldTotal - $newTotal;
                    
                    if ($updatedItems > 0) {
                        Notification::make()
                            ->title('Prices Recalculated')
                            ->body("Updated {$updatedItems} items. New total: $" . number_format($newTotal, 2) . " (saved $" . number_format($difference, 2) . ")")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('No Changes Needed')
                            ->body('All prices are already up to date.')
                            ->info()
                            ->send();
                    }
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error Recalculating Prices')
                        ->body('Failed to recalculate prices: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getGenerateCropPlansAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('generate_crop_plans')
            ->label('Generate Crop Plans')
            ->icon('heroicon-o-sparkles')
            ->color('success')
            ->visible(fn (Order $record): bool => 
                $record->requiresCropProduction() &&
                !$record->isInFinalState() &&
                !$record->cropPlans()->exists()
            )
            ->requiresConfirmation()
            ->modalHeading('Generate Crop Plans')
            ->modalDescription(fn (Order $record) => 
                "This will analyze the order items and generate crop plans based on the delivery date."
            )
            ->action(function (Order $record) {
                $orderPlanningService = app(OrderPlanningService::class);
                $result = $orderPlanningService->generatePlansForOrder($record);
                
                if ($result['success']) {
                    Notification::make()
                        ->title('Crop Plans Generated')
                        ->body("Successfully generated {$result['plans']->count()} crop plans.")
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Generation Failed')
                        ->body(implode(' ', $result['issues']))
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getConvertToInvoiceAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('convert_to_invoice')
            ->label('Create Invoice')
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->visible(fn (Order $record): bool => 
                $record->status?->code !== 'template' && 
                $record->requires_invoice &&
                !$record->invoice
            )
            ->requiresConfirmation()
            ->modalHeading('Create Invoice')
            ->modalDescription(fn (Order $record) => 
                "This will create an invoice for Order #{$record->id} totaling $" . number_format($record->totalAmount(), 2) . "."
            )
            ->action(function (Order $record) {
                try {
                    $invoice = \App\Models\Invoice::createFromOrder($record);
                    
                    Notification::make()
                        ->title('Invoice Created')
                        ->body("Invoice #{$invoice->id} has been created successfully.")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('View Invoice')
                                ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id]))
                        ])
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error Creating Invoice')
                        ->body('Failed to create invoice: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getConvertToRecurringAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('convert_to_recurring')
            ->label('Convert to Recurring')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->visible(fn (Order $record): bool => 
                !$record->is_recurring && 
                $record->status?->code !== 'template' &&
                $record->parent_recurring_order_id === null &&
                $record->customer &&
                $record->orderItems()->count() > 0
            )
            ->form([
                Forms\Components\Section::make('Recurring Settings')
                    ->schema([
                        Forms\Components\Select::make('frequency')
                            ->label('Frequency')
                            ->options([
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('weekly')
                            ->required()
                            ->reactive(),
                            
                        Forms\Components\TextInput::make('interval')
                            ->label('Interval (weeks)')
                            ->helperText('For bi-weekly: enter 2 for every 2 weeks')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->maxValue(12)
                            ->visible(fn (Get $get) => $get('frequency') === 'biweekly'),
                            
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now()->addWeek())
                            ->required()
                            ->minDate(now()),
                            
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date (Optional)')
                            ->helperText('Leave blank for indefinite recurring')
                            ->minDate(fn (Get $get) => $get('start_date')),
                    ])
                    ->columns(2),
            ])
            ->modalHeading('Convert Order to Recurring Template')
            ->modalDescription(fn (Order $record) => 
                "This will convert Order #{$record->id} into a recurring order template that will automatically generate new orders."
            )
            ->action(function (Order $record, array $data) {
                try {
                    $recurringOrderService = app(\App\Services\RecurringOrderService::class);
                    $convertedOrder = $recurringOrderService->convertToRecurringTemplate($record, $data);
                    
                    Notification::make()
                        ->title('Order Converted Successfully')
                        ->body("Order #{$record->id} has been converted to a recurring template.")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('View Template')
                                ->url(route('filament.admin.resources.recurring-orders.edit', ['record' => $convertedOrder->id]))
                        ])
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Conversion Failed')
                        ->body('Failed to convert order to recurring: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getTransitionStatusAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('transition_status')
            ->label('Change Status')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->visible(fn (Order $record): bool => 
                !$record->isInFinalState() && 
                $record->status?->code !== 'template'
            )
            ->form(function (Order $record) {
                $validStatuses = app(\App\Services\StatusTransitionService::class)
                    ->getValidNextStatuses($record);
                
                if ($validStatuses->isEmpty()) {
                    return [
                        Forms\Components\Placeholder::make('no_transitions')
                            ->label('')
                            ->content('No valid status transitions available for this order.')
                    ];
                }
                
                return [
                    Forms\Components\Select::make('new_status')
                        ->label('New Status')
                        ->options($validStatuses->pluck('name', 'code'))
                        ->required()
                        ->helperText('Select the new status for this order'),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->placeholder('Optional notes about this status change')
                        ->rows(3),
                ];
            })
            ->action(function (Order $record, array $data) {
                $result = $record->transitionTo($data['new_status'], [
                    'manual' => true,
                    'notes' => $data['notes'] ?? null,
                    'user_id' => auth()->id()
                ]);
                
                if ($result['success']) {
                    Notification::make()
                        ->title('Status Updated')
                        ->body($result['message'])
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Status Update Failed')
                        ->body($result['message'])
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getCreateConsolidatedInvoiceAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('create_consolidated_invoice')
            ->label('Create Consolidated Invoice')
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Create Consolidated Invoice')
            ->modalDescription('This will create a single invoice for all selected orders.')
            ->form([
                Forms\Components\DatePicker::make('issue_date')
                    ->label('Issue Date')
                    ->default(now())
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Due Date')
                    ->default(now()->addDays(30))
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->label('Invoice Notes')
                    ->placeholder('Additional notes for the consolidated invoice...')
                    ->rows(3),
            ])
            ->action(function (Collection $records, array $data) {
                $errors = static::validateOrdersForConsolidation($records);
                
                if (!empty($errors)) {
                    Notification::make()
                        ->title('Cannot Create Consolidated Invoice')
                        ->body(implode(' ', $errors))
                        ->danger()
                        ->persistent()
                        ->send();
                    return;
                }
                
                try {
                    $invoice = static::createConsolidatedInvoice($records, $data);
                    
                    Notification::make()
                        ->title('Consolidated Invoice Created')
                        ->body("Invoice #{$invoice->invoice_number} created for {$records->count()} orders totaling $" . number_format($invoice->total_amount, 2) . ".")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('View Invoice')
                                ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id]))
                        ])
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error Creating Invoice')
                        ->body('Failed to create consolidated invoice: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->deselectRecordsAfterCompletion();
    }

    protected static function getBulkStatusUpdateAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('bulk_status_update')
            ->label('Update Status')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Bulk Status Update')
            ->modalDescription(function (Collection $records) {
                $finalOrders = $records->filter(fn($order) => $order->isInFinalState());
                $templateOrders = $records->filter(fn($order) => $order->status?->code === 'template');
                
                $warnings = [];
                if ($finalOrders->isNotEmpty()) {
                    $warnings[] = "{$finalOrders->count()} orders in final state will be skipped.";
                }
                if ($templateOrders->isNotEmpty()) {
                    $warnings[] = "{$templateOrders->count()} template orders will be skipped.";
                }
                
                $eligibleCount = $records->count() - $finalOrders->count() - $templateOrders->count();
                
                return "Update status for {$eligibleCount} orders." . 
                       (!empty($warnings) ? "\n\nWarnings:\n" . implode("\n", $warnings) : '');
            })
            ->form([
                Forms\Components\Select::make('new_status')
                    ->label('New Status')
                    ->options(OrderStatus::active()
                        ->notFinal()
                        ->where('code', '!=', 'template')
                        ->pluck('name', 'code'))
                    ->required()
                    ->helperText('Select the new status for all eligible orders'),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->placeholder('Optional notes about this bulk status change')
                    ->rows(3),
            ])
            ->action(function (Collection $records, array $data) {
                $statusService = app(\App\Services\StatusTransitionService::class);
                
                $eligibleOrders = $records->filter(function ($order) {
                    return !$order->isInFinalState() && 
                           $order->status?->code !== 'template';
                });
                
                if ($eligibleOrders->isEmpty()) {
                    Notification::make()
                        ->title('No Eligible Orders')
                        ->body('None of the selected orders can have their status updated.')
                        ->warning()
                        ->send();
                    return;
                }
                
                $result = $statusService->bulkTransition(
                    $eligibleOrders->pluck('id')->toArray(),
                    $data['new_status'],
                    [
                        'manual' => true,
                        'notes' => $data['notes'] ?? null,
                        'user_id' => auth()->id()
                    ]
                );
                
                $successCount = count($result['successful']);
                $failedCount = count($result['failed']);
                
                if ($successCount > 0) {
                    Notification::make()
                        ->title('Status Update Complete')
                        ->body("Successfully updated {$successCount} orders." . 
                               ($failedCount > 0 ? " {$failedCount} orders failed." : ''))
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Status Update Failed')
                        ->body("Failed to update any orders. Check the logs for details.")
                        ->danger()
                        ->send();
                }
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * Validate that orders can be consolidated into a single invoice
     */
    protected static function validateOrdersForConsolidation(Collection $orders): array
    {
        $errors = [];

        $templates = $orders->filter(function ($order) {
            return $order->status?->code === 'template';
        });
        if ($templates->isNotEmpty()) {
            $errors[] = 'Cannot create invoices for template orders.';
        }

        $noInvoiceNeeded = $orders->where('requires_invoice', false);
        if ($noInvoiceNeeded->isNotEmpty()) {
            $errors[] = 'Some selected orders do not require invoices.';
        }

        $alreadyInvoiced = $orders->whereNotNull('invoice_id');
        if ($alreadyInvoiced->isNotEmpty()) {
            $errors[] = 'Some orders already have invoices.';
        }

        $customerIds = $orders->pluck('user_id')->unique();
        if ($customerIds->count() > 1) {
            $errors[] = 'All orders must belong to the same customer for consolidated invoicing.';
        }

        if ($orders->count() < 2) {
            $errors[] = 'At least 2 orders are required for consolidated invoicing.';
        }

        return $errors;
    }

    /**
     * Create a consolidated invoice from multiple orders
     */
    protected static function createConsolidatedInvoice(Collection $orders, array $data): \App\Models\Invoice
    {
        $totalAmount = $orders->sum(function ($order) {
            return $order->totalAmount();
        });

        $deliveryDates = $orders->pluck('delivery_date')->map(fn($date) => \Carbon\Carbon::parse($date))->sort();
        $billingPeriodStart = $deliveryDates->first()->startOfMonth();
        $billingPeriodEnd = $deliveryDates->last()->endOfMonth();

        $invoiceNumber = \App\Models\Invoice::generateInvoiceNumber();

        $invoice = \App\Models\Invoice::create([
            'user_id' => $orders->first()->user_id,
            'invoice_number' => $invoiceNumber,
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'billing_period_start' => $billingPeriodStart,
            'billing_period_end' => $billingPeriodEnd,
            'is_consolidated' => true,
            'consolidated_order_count' => $orders->count(),
            'notes' => $data['notes'] ?? "Consolidated invoice for {$orders->count()} orders: " . $orders->pluck('id')->implode(', '),
        ]);

        $orders->each(function ($order) use ($invoice) {
            $order->update(['consolidated_invoice_id' => $invoice->id]);
        });

        return $invoice;
    }
}