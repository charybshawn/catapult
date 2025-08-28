<?php

namespace App\Filament\Resources\OrderResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Actions\Action;
use Exception;
use Filament\Forms\Components\Select;
use App\Services\StatusTransitionService;
use Filament\Actions\DeleteBulkAction;
use App\Models\Invoice;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\OrderStatus;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Collection;

/**
 * Order table bulk actions definitions
 * Extracted from OrderResource.php lines 975-1122
 * 
 * This class follows the Filament Resource Architecture Guide by:
 * - Returning pure Filament bulk action components
 * - Organizing bulk operation logic into reusable methods
 * - Preserving all existing functionality and validation
 * - Marking places where Action classes should be called with TODO comments
 */
class OrderTableBulkActions
{
    /**
     * Get all bulk actions for the OrderResource
     * 
     * @return array Array of Filament bulk actions
     */
    public static function make(): array
    {
        return [
            BulkActionGroup::make([
                static::getCreateConsolidatedInvoiceAction(),
                static::getBulkStatusUpdateAction(),
                static::getDeleteBulkAction(),
            ]),
        ];
    }

    /**
     * Create consolidated invoice bulk action
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
                // TODO: Extract to App\Actions\Order\CreateConsolidatedInvoiceAction
                
                // Validate that orders can be consolidated
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
     * Bulk status update action
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
                // TODO: Extract to App\Actions\Order\BulkStatusUpdateAction
                
                $statusService = app(StatusTransitionService::class);
                
                // Filter out ineligible orders
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
     * Standard delete bulk action
     */
    protected static function getDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make();
    }

    /**
     * Validate that orders can be consolidated into a single invoice
     * 
     * @param Collection $orders
     * @return array Array of validation errors
     */
    protected static function validateOrdersForConsolidation(Collection $orders): array
    {
        $errors = [];

        // Check if any orders are templates
        $templates = $orders->filter(function ($order) {
            return $order->status?->code === 'template';
        });
        if ($templates->isNotEmpty()) {
            $errors[] = 'Cannot create invoices for template orders.';
        }

        // Check if any orders don't require invoices
        $noInvoiceNeeded = $orders->where('requires_invoice', false);
        if ($noInvoiceNeeded->isNotEmpty()) {
            $errors[] = 'Some selected orders do not require invoices.';
        }

        // Check if any orders already have invoices
        $alreadyInvoiced = $orders->whereNotNull('invoice_id');
        if ($alreadyInvoiced->isNotEmpty()) {
            $errors[] = 'Some orders already have invoices.';
        }

        // Check if all orders belong to the same customer
        $customerIds = $orders->pluck('user_id')->unique();
        if ($customerIds->count() > 1) {
            $errors[] = 'All orders must belong to the same customer for consolidated invoicing.';
        }

        // Check minimum number of orders
        if ($orders->count() < 2) {
            $errors[] = 'At least 2 orders are required for consolidated invoicing.';
        }

        return $errors;
    }

    /**
     * Create a consolidated invoice from multiple orders
     *
     * @param Collection $orders
     * @param array $data
     * @return Invoice
     */
    protected static function createConsolidatedInvoice(Collection $orders, array $data): Invoice
    {
        // Calculate total amount
        $totalAmount = $orders->sum(function ($order) {
            return $order->totalAmount();
        });

        // Get billing period from order dates
        $deliveryDates = $orders->pluck('delivery_date')->map(fn($date) => Carbon::parse($date))->sort();
        $billingPeriodStart = $deliveryDates->first()->startOfMonth();
        $billingPeriodEnd = $deliveryDates->last()->endOfMonth();

        // Generate invoice number
        $invoiceNumber = Invoice::generateInvoiceNumber();

        // Create the consolidated invoice
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

        // Link all orders to this consolidated invoice
        $orders->each(function ($order) use ($invoice) {
            $order->update(['consolidated_invoice_id' => $invoice->id]);
        });

        return $invoice;
    }

    /**
     * Get bulk action configuration options
     * 
     * @return array
     */
    public static function getBulkActionConfiguration(): array
    {
        return [
            'consolidated_invoice' => [
                'validation_rules' => [
                    'same_customer' => true,
                    'requires_invoice' => true,
                    'not_already_invoiced' => true,
                    'not_template' => true,
                    'minimum_orders' => 2,
                ],
                'form_fields' => [
                    'issue_date',
                    'due_date',
                    'notes',
                ],
            ],
            'status_update' => [
                'eligible_orders_only' => true,
                'exclude_final_states' => true,
                'exclude_templates' => true,
                'form_fields' => [
                    'new_status',
                    'notes',
                ],
            ],
        ];
    }

    /**
     * Get eligible status options for bulk updates
     * 
     * @return array
     */
    public static function getEligibleStatusOptions(): array
    {
        return OrderStatus::active()
            ->notFinal()
            ->where('code', '!=', 'template')
            ->pluck('name', 'code')
            ->toArray();
    }

    /**
     * Check if orders are eligible for bulk operations
     * 
     * @param Collection $orders
     * @param string $operationType
     * @return array
     */
    public static function getEligibilityReport(Collection $orders, string $operationType): array
    {
        $report = [
            'total' => $orders->count(),
            'eligible' => 0,
            'ineligible' => 0,
            'reasons' => [],
        ];

        switch ($operationType) {
            case 'consolidated_invoice':
                $report = static::getInvoiceEligibilityReport($orders);
                break;
            case 'status_update':
                $report = static::getStatusUpdateEligibilityReport($orders);
                break;
        }

        return $report;
    }

    /**
     * Get eligibility report for consolidated invoice creation
     * 
     * @param Collection $orders
     * @return array
     */
    protected static function getInvoiceEligibilityReport(Collection $orders): array
    {
        $report = [
            'total' => $orders->count(),
            'eligible' => 0,
            'ineligible' => 0,
            'reasons' => [],
        ];

        $templates = $orders->filter(fn($order) => $order->status?->code === 'template');
        $noInvoiceNeeded = $orders->where('requires_invoice', false);
        $alreadyInvoiced = $orders->whereNotNull('invoice_id');
        $customerIds = $orders->pluck('user_id')->unique();

        if ($templates->isNotEmpty()) {
            $report['reasons'][] = "{$templates->count()} template orders";
            $report['ineligible'] += $templates->count();
        }

        if ($noInvoiceNeeded->isNotEmpty()) {
            $report['reasons'][] = "{$noInvoiceNeeded->count()} orders don't require invoices";
            $report['ineligible'] += $noInvoiceNeeded->count();
        }

        if ($alreadyInvoiced->isNotEmpty()) {
            $report['reasons'][] = "{$alreadyInvoiced->count()} orders already invoiced";
            $report['ineligible'] += $alreadyInvoiced->count();
        }

        if ($customerIds->count() > 1) {
            $report['reasons'][] = "Multiple customers ({$customerIds->count()})";
            $report['ineligible'] = $report['total']; // All become ineligible
        }

        $report['eligible'] = max(0, $report['total'] - $report['ineligible']);

        return $report;
    }

    /**
     * Get eligibility report for status updates
     * 
     * @param Collection $orders
     * @return array
     */
    protected static function getStatusUpdateEligibilityReport(Collection $orders): array
    {
        $report = [
            'total' => $orders->count(),
            'eligible' => 0,
            'ineligible' => 0,
            'reasons' => [],
        ];

        $finalOrders = $orders->filter(fn($order) => $order->isInFinalState());
        $templateOrders = $orders->filter(fn($order) => $order->status?->code === 'template');

        if ($finalOrders->isNotEmpty()) {
            $report['reasons'][] = "{$finalOrders->count()} orders in final state";
            $report['ineligible'] += $finalOrders->count();
        }

        if ($templateOrders->isNotEmpty()) {
            $report['reasons'][] = "{$templateOrders->count()} template orders";
            $report['ineligible'] += $templateOrders->count();
        }

        $report['eligible'] = $report['total'] - $report['ineligible'];

        return $report;
    }
}