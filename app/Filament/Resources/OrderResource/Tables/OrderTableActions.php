<?php

namespace App\Filament\Resources\OrderResource\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
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
use Filament\Actions\DeleteAction;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Services\OrderPlanningService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;

/**
 * Order table individual actions definitions
 * Extracted from OrderResource.php lines 649-973
 * 
 * This class follows the Filament Resource Architecture Guide by:
 * - Returning pure Filament table action components
 * - Organizing action logic into reusable methods
 * - Preserving all existing functionality and business logic
 * - Marking places where Action classes should be called with TODO comments
 */
class OrderTableActions
{
    /**
     * Get all table actions for the OrderResource
     * 
     * @return array Array of Filament table actions
     */
    public static function make(): array
    {
        return [
            ActionGroup::make([
                static::getViewAction(),
                static::getEditAction(),
                static::getGenerateNextRecurringAction(),
                static::getRecalculatePricesAction(),
                static::getGenerateCropPlansAction(),
                static::getConvertToInvoiceAction(),
                static::getConvertToRecurringAction(),
                static::getTransitionStatusAction(),
                static::getDeleteAction(),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Standard view action
     */
    protected static function getViewAction(): ViewAction
    {
        return ViewAction::make()
            ->tooltip('View order details');
    }

    /**
     * Standard edit action
     */
    protected static function getEditAction(): EditAction
    {
        return EditAction::make()
            ->tooltip('Edit order');
    }

    /**
     * Generate next recurring order action
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
                // TODO: Extract to App\Actions\Order\GenerateNextRecurringOrderAction
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
     * Recalculate wholesale prices action
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
                // TODO: Extract to App\Actions\Order\RecalculateOrderPricesAction
                try {
                    $oldTotal = $record->totalAmount();
                    $updatedItems = 0;
                    
                    foreach ($record->orderItems as $item) {
                        if (!$item->product || !$item->price_variation_id) {
                            continue;
                        }
                        
                        // Get current price for this customer
                        $currentPrice = $item->product->getPriceForSpecificCustomer(
                            $record->customer,
                            $item->price_variation_id
                        );
                        
                        // Check if price has changed
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
     * Generate crop plans action
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
                // TODO: Extract to App\Actions\Order\GenerateCropPlansAction
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
     * Convert order to invoice action
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
                !$record->invoice // Only show if no invoice exists yet
            )
            ->requiresConfirmation()
            ->modalHeading('Create Invoice')
            ->modalDescription(fn (Order $record) => 
                "This will create an invoice for Order #{$record->id} totaling $" . number_format($record->totalAmount(), 2) . "."
            )
            ->action(function (Order $record) {
                // TODO: Extract to App\Actions\Order\CreateInvoiceFromOrderAction
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
     * Convert order to recurring template action
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
                $record->parent_recurring_order_id === null && // Not generated from recurring
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
                // TODO: Extract to App\Actions\Order\ConvertToRecurringTemplateAction
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
     * Manual status transition action
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
                // TODO: Extract to App\Actions\Order\TransitionOrderStatusAction
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
     * Standard delete action
     */
    protected static function getDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->tooltip('Delete order');
    }

    /**
     * Get action configuration for consistent styling
     * 
     * @return array
     */
    public static function getActionGroupConfiguration(): array
    {
        return [
            'label' => 'Actions',
            'icon' => 'heroicon-m-ellipsis-vertical',
            'size' => 'sm',
            'color' => 'gray',
            'button' => true,
        ];
    }

    /**
     * Get recurring frequency options
     * Used by convert to recurring action
     * 
     * @return array
     */
    public static function getRecurringFrequencyOptions(): array
    {
        return [
            'weekly' => 'Weekly',
            'biweekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
        ];
    }

    /**
     * Get status transition validation rules
     * Used by status transition action
     * 
     * @param Order $record
     * @return array
     */
    public static function getValidStatusTransitions(Order $record): array
    {
        if (!$record->status) {
            return [];
        }

        return app(StatusTransitionService::class)
            ->getValidNextStatuses($record)
            ->pluck('name', 'code')
            ->toArray();
    }

    /**
     * Check if an order can be converted to recurring
     * 
     * @param Order $record
     * @return bool
     */
    public static function canConvertToRecurring(Order $record): bool
    {
        return !$record->is_recurring && 
               $record->status?->code !== 'template' &&
               $record->parent_recurring_order_id === null &&
               $record->customer &&
               $record->orderItems()->count() > 0;
    }

    /**
     * Check if an order can be invoiced
     * 
     * @param Order $record
     * @return bool
     */
    public static function canBeInvoiced(Order $record): bool
    {
        return $record->status?->code !== 'template' && 
               $record->requires_invoice &&
               !$record->invoice;
    }

    /**
     * Check if crop plans can be generated for an order
     * 
     * @param Order $record
     * @return bool
     */
    public static function canGenerateCropPlans(Order $record): bool
    {
        return $record->requiresCropProduction() &&
               !$record->isInFinalState() &&
               !$record->cropPlans()->exists();
    }
}