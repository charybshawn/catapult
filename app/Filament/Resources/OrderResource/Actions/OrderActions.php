<?php

namespace App\Filament\Resources\OrderResource\Actions;

use Filament\Actions\Action;
use App\Services\RecurringOrderService;
use Exception;
use App\Models\Invoice;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\DatePicker;
use App\Services\StatusTransitionService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Actions\BulkAction;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\OrderPlanningService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

/**
 * Order action collection for agricultural order management system.
 * 
 * Provides comprehensive order management actions including recurring order generation,
 * price recalculation for wholesale customers, crop plan generation for production
 * orders, invoice creation, and status management. Handles complex agricultural
 * business workflows including bulk operations and consolidated billing.
 * 
 * @filament_actions Complete order lifecycle management
 * @business_domain Agricultural microgreens order processing and fulfillment
 * @workflow_support Order-to-crop-to-delivery automation
 * @customer_support Wholesale pricing, recurring orders, consolidated invoicing
 */
class OrderActions
{
    /**
     * Get all row actions for order management table.
     * 
     * Provides comprehensive action set for individual order management including
     * recurring order generation, price updates for wholesale customers, production
     * planning, invoicing, and status transitions. Actions are contextually shown
     * based on order state and customer type.
     * 
     * @return array Complete set of order row actions with agricultural context
     * @filament_usage Table row actions for OrderResource
     * @business_logic Actions adapt to order status, customer type, and production needs
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
     * Get all bulk actions for order management operations.
     * 
     * Provides efficient bulk operations for order processing including consolidated
     * invoice creation for wholesale customers and batch status updates. Handles
     * complex business validation for multi-order operations.
     * 
     * @return array Bulk actions for efficient order processing
     * @filament_usage Table bulk actions for OrderResource
     * @business_logic Consolidated billing, batch status management
     */
    public static function getBulkActions(): array
    {
        return [
            static::getCreateConsolidatedInvoiceAction(),
            static::getBulkStatusUpdateAction(),
        ];
    }

    /**
     * Generate next recurring order action for template orders.
     * 
     * Creates the next order in a recurring series based on configured frequency
     * and schedule. Handles agricultural production timing, validates generation
     * conditions, and maintains customer relationship continuity.
     * 
     * @return Action Action for generating next recurring order
     * @business_process Recurring order automation for regular customers
     * @agricultural_context Maintains consistent production schedules
     */
    protected static function getGenerateNextRecurringAction(): Action
    {
        return Action::make('generate_next_recurring')
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
                    $recurringOrderService = app(RecurringOrderService::class);
                    $newOrder = $recurringOrderService->generateNextOrder($record);
                    
                    if ($newOrder) {
                        Notification::make()
                            ->title('Recurring Order Generated')
                            ->body("Order #{$newOrder->id} has been created successfully.")
                            ->success()
                            ->actions([
                                Action::make('view')
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
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Error Generating Order')
                        ->body('Failed to generate recurring order: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Recalculate prices action for wholesale customers.
     * 
     * Updates all order item prices using current wholesale discount rates and
     * product pricing. Essential for maintaining accurate pricing when wholesale
     * terms change or product costs are updated.
     * 
     * @return Action Action for recalculating wholesale prices
     * @business_logic Wholesale discount application, price synchronization
     * @customer_type Wholesale customers with dynamic discount rates
     */
    protected static function getRecalculatePricesAction(): Action
    {
        return Action::make('recalculate_prices')
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
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Error Recalculating Prices')
                        ->body('Failed to recalculate prices: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Generate crop plans action for production orders.
     * 
     * Analyzes order requirements and creates detailed crop production plans
     * based on delivery dates, variety requirements, and growing schedules.
     * Integrates with agricultural planning service for optimal production timing.
     * 
     * @return Action Action for generating crop production plans
     * @agricultural_process Order-to-production planning automation
     * @business_logic Production schedule optimization, delivery date alignment
     */
    protected static function getGenerateCropPlansAction(): Action
    {
        return Action::make('generate_crop_plans')
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

    /**
     * Convert order to invoice action for billing workflow.
     * 
     * Creates formal invoice from completed or ready orders, transitioning
     * from order management to accounts receivable. Handles agricultural
     * business billing cycles and customer payment terms.
     * 
     * @return Action Action for creating invoice from order
     * @business_process Order-to-billing workflow transition
     * @financial_management Invoice creation, payment tracking
     */
    protected static function getConvertToInvoiceAction(): Action
    {
        return Action::make('convert_to_invoice')
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
                    $invoice = Invoice::createFromOrder($record);
                    
                    Notification::make()
                        ->title('Invoice Created')
                        ->body("Invoice #{$invoice->id} has been created successfully.")
                        ->success()
                        ->actions([
                            Action::make('view')
                                ->label('View Invoice')
                                ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id]))
                        ])
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Error Creating Invoice')
                        ->body('Failed to create invoice: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Convert order to recurring template action.
     * 
     * Transforms one-time order into recurring order template with configurable
     * frequency and schedule. Supports agricultural business patterns of regular
     * deliveries for restaurants and wholesale customers.
     * 
     * @return Action Action for converting to recurring order template
     * @business_pattern Regular delivery automation for consistent customers
     * @agricultural_context Predictable production planning for recurring needs
     */
    protected static function getConvertToRecurringAction(): Action
    {
        return Action::make('convert_to_recurring')
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
            ->schema([
                Section::make('Recurring Settings')
                    ->schema([
                        Select::make('frequency')
                            ->label('Frequency')
                            ->options([
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('weekly')
                            ->required()
                            ->reactive(),
                            
                        TextInput::make('interval')
                            ->label('Interval (weeks)')
                            ->helperText('For bi-weekly: enter 2 for every 2 weeks')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->maxValue(12)
                            ->visible(fn (Get $get) => $get('frequency') === 'biweekly'),
                            
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now()->addWeek())
                            ->required()
                            ->minDate(now()),
                            
                        DatePicker::make('end_date')
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
                    $recurringOrderService = app(RecurringOrderService::class);
                    $convertedOrder = $recurringOrderService->convertToRecurringTemplate($record, $data);
                    
                    Notification::make()
                        ->title('Order Converted Successfully')
                        ->body("Order #{$record->id} has been converted to a recurring template.")
                        ->success()
                        ->actions([
                            Action::make('view')
                                ->label('View Template')
                                ->url(route('filament.admin.resources.recurring-orders.edit', ['record' => $convertedOrder->id]))
                        ])
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Conversion Failed')
                        ->body('Failed to convert order to recurring: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Order status transition action with business validation.
     * 
     * Manages order lifecycle transitions through valid status changes,
     * enforcing agricultural business rules and workflow constraints.
     * Provides audit trail for order progression tracking.
     * 
     * @return Action Action for managing order status transitions
     * @workflow_management Order lifecycle progression with validation
     * @business_rules Status transition constraints and audit logging
     */
    protected static function getTransitionStatusAction(): Action
    {
        return Action::make('transition_status')
            ->label('Change Status')
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->visible(fn (Order $record): bool => 
                !$record->isInFinalState() && 
                $record->status?->code !== 'template'
            )
            ->schema(function (Order $record) {
                $validStatuses = app(StatusTransitionService::class)
                    ->getValidNextStatuses($record);
                
                if ($validStatuses->isEmpty()) {
                    return [
                        Placeholder::make('no_transitions')
                            ->label('')
                            ->content('No valid status transitions available for this order.')
                    ];
                }
                
                return [
                    Select::make('new_status')
                        ->label('New Status')
                        ->options($validStatuses->pluck('name', 'code'))
                        ->required()
                        ->helperText('Select the new status for this order'),
                    Textarea::make('notes')
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

    /**
     * Create consolidated invoice bulk action for wholesale billing.
     * 
     * Combines multiple orders from same customer into single invoice,
     * streamlining billing for wholesale accounts with multiple deliveries.
     * Validates order compatibility and customer billing preferences.
     * 
     * @return BulkAction Bulk action for consolidated invoice creation
     * @business_process Wholesale billing efficiency, multi-order invoicing
     * @customer_type Wholesale accounts with multiple concurrent orders
     */
    protected static function getCreateConsolidatedInvoiceAction(): BulkAction
    {
        return BulkAction::make('create_consolidated_invoice')
            ->label('Create Consolidated Invoice')
            ->icon('heroicon-o-document-text')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Create Consolidated Invoice')
            ->modalDescription('This will create a single invoice for all selected orders.')
            ->form([
                DatePicker::make('issue_date')
                    ->label('Issue Date')
                    ->default(now())
                    ->required(),
                DatePicker::make('due_date')
                    ->label('Due Date')
                    ->default(now()->addDays(30))
                    ->required(),
                Textarea::make('notes')
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
                            Action::make('view')
                                ->label('View Invoice')
                                ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id]))
                        ])
                        ->send();
                } catch (Exception $e) {
                    Notification::make()
                        ->title('Error Creating Invoice')
                        ->body('Failed to create consolidated invoice: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * Bulk status update action for efficient order processing.
     * 
     * Updates multiple orders to same status simultaneously with business
     * validation and transition rules. Supports batch processing of order
     * lifecycle changes for operational efficiency.
     * 
     * @return BulkAction Bulk action for status updates
     * @operational_efficiency Batch order processing, status synchronization
     * @workflow_management Bulk lifecycle transitions with validation
     */
    protected static function getBulkStatusUpdateAction(): BulkAction
    {
        return BulkAction::make('bulk_status_update')
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
                Select::make('new_status')
                    ->label('New Status')
                    ->options(OrderStatus::active()
                        ->notFinal()
                        ->where('code', '!=', 'template')
                        ->pluck('name', 'code'))
                    ->required()
                    ->helperText('Select the new status for all eligible orders'),
                Textarea::make('notes')
                    ->label('Notes')
                    ->placeholder('Optional notes about this bulk status change')
                    ->rows(3),
            ])
            ->action(function (Collection $records, array $data) {
                $statusService = app(StatusTransitionService::class);
                
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
     * Validate orders for consolidated invoice creation.
     * 
     * Performs comprehensive validation to ensure selected orders can be
     * legally and logically combined into single consolidated invoice.
     * Checks customer matching, invoice requirements, and order states.
     * 
     * @param Collection $orders Orders to validate for consolidation
     * @return array Array of validation error messages, empty if valid
     * @business_validation Customer matching, billing requirements
     * @financial_rules Invoice consolidation business constraints
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
     * Create consolidated invoice from validated order collection.
     * 
     * Generates single invoice combining multiple orders with proper
     * billing period calculation, amount aggregation, and order linking.
     * Handles agricultural business billing cycles and payment terms.
     * 
     * @param Collection $orders Validated orders for consolidation
     * @param array $data Invoice configuration data (dates, notes)
     * @return Invoice Created consolidated invoice with linked orders
     * @business_process Multi-order billing consolidation
     * @financial_management Invoice generation, order relationship tracking
     */
    protected static function createConsolidatedInvoice(Collection $orders, array $data): Invoice
    {
        $totalAmount = $orders->sum(function ($order) {
            return $order->totalAmount();
        });

        $deliveryDates = $orders->pluck('delivery_date')->map(fn($date) => Carbon::parse($date))->sort();
        $billingPeriodStart = $deliveryDates->first()->startOfMonth();
        $billingPeriodEnd = $deliveryDates->last()->endOfMonth();

        $invoiceNumber = Invoice::generateInvoiceNumber();

        $invoice = Invoice::create([
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