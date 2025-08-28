<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Notifications\Notification;
use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Livewire\Attributes\Computed;

/**
 * Filament page for editing agricultural invoice records with comprehensive order management.
 *
 * Provides sophisticated invoice editing capabilities supporting both single and consolidated
 * invoices with real-time order item calculations. Includes agricultural business workflow
 * management with status transitions, PDF generation, and comprehensive financial tracking
 * for microgreens production sales operations.
 *
 * @filament_page
 * @business_domain Agricultural invoice management and financial operations
 * @related_models Invoice, Order, OrderItem, Product
 * @workflow_support Invoice editing, status management, PDF generation, consolidated billing
 * @financial_operations Real-time calculations, status transitions, payment tracking
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class EditInvoice extends BaseEditRecord
{
    protected static string $resource = InvoiceResource::class;
    protected string $view = 'filament.resources.invoice-resource.pages.edit-invoice';
    
    public $orderItems = [];

    /**
     * Initialize invoice editing page with comprehensive order item data structure.
     *
     * Loads and structures order items for both single and consolidated invoices,
     * creating editable data structure for real-time financial calculations.
     * Handles complex invoice types with proper relationship loading for agricultural
     * sales operations.
     *
     * @param mixed $record Invoice record being edited
     * @return void
     * @filament_hook Page initialization with order data loading
     * @business_context Supports both single order and consolidated invoice workflows
     * @financial_tracking Initializes order items for real-time calculation updates
     */
    public function mount($record): void
    {
        parent::mount($record);
        
        // Initialize orderItems data structure
        $orders = $this->record->is_consolidated 
            ? $this->record->consolidatedOrders()->with(['orderItems.product'])->get()
            : ($this->record->order ? collect([$this->record->order->load(['orderItems.product'])]) : collect());
        
        foreach($orders as $order) {
            foreach($order->orderItems as $index => $item) {
                $this->orderItems[$order->id][$index] = [
                    'product_name' => $item->product->name ?? 'Unknown Product',
                    'quantity' => $item->quantity,
                    'price' => number_format($item->price, 2),
                ];
            }
        }
    }

    /**
     * Calculate real-time invoice total from editable order items.
     *
     * Provides live calculation of invoice total as order items are modified,
     * supporting both single and consolidated invoice workflows. Essential for
     * agricultural sales operations requiring accurate financial calculations.
     *
     * @return float Calculated total from all order items
     * @livewire_computed Real-time calculation triggered by order item updates
     * @financial_calculation Handles quantity × price calculations across all items
     * @agricultural_context Supports microgreens sales with dynamic pricing
     */
    #[Computed]
    public function calculatedTotal()
    {
        $total = 0;
        foreach($this->orderItems as $orderId => $items) {
            foreach($items as $item) {
                $quantity = (float)($item['quantity'] ?? 0);
                $price = (float)str_replace(',', '', $item['price'] ?? 0);
                $total += $quantity * $price;
            }
        }
        return $total;
    }

    /**
     * Handle order items updates with real-time total recalculation.
     *
     * Triggered automatically when order items are modified, dispatches update
     * events for real-time UI synchronization. Essential for live financial
     * calculations in agricultural invoice management workflows.
     *
     * @return void
     * @livewire_hook Automatic trigger on orderItems property updates
     * @real_time_updates Dispatches events for UI synchronization
     * @financial_workflow Maintains calculation accuracy during editing
     */
    public function updatedOrderItems()
    {
        // This will trigger when any order item is updated
        $this->dispatch('order-items-updated', total: $this->calculatedTotal);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview Invoice')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('filament.admin.resources.invoices.view', ['record' => $this->record->id]))
                ->openUrlInNewTab(),
            Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('invoices.download', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => in_array($this->record->status, ['sent', 'paid', 'overdue', 'pending'])),
            DeleteAction::make(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        View::make('components.invoice-preview')
                            ->viewData([
                                'invoice' => $this->record,
                                'editable' => true
                            ]),
                    ]),
            ]);
    }

    /**
     * Mark invoice as sent with status update and notification.
     *
     * Updates invoice status to 'sent' with timestamp recording and provides
     * user feedback through notification system. Essential for agricultural
     * sales workflow tracking and customer communication management.
     *
     * @return void
     * @business_workflow Invoice status transition to 'sent' state
     * @agricultural_context Tracks outbound invoices for microgreens sales
     * @ui_feedback Success notification confirms status change
     * @timestamp_tracking Records sent_at for audit trail
     */
    public function markAsSent()
    {
        $this->record->markAsSent();
        $this->refreshFormData(['status', 'sent_at']);
        
        Notification::make()
            ->title('Invoice marked as sent')
            ->success()
            ->send();
    }

    /**
     * Mark invoice as paid with status update and notification.
     *
     * Updates invoice status to 'paid' with payment timestamp and provides
     * user feedback. Critical for agricultural financial tracking and accounts
     * receivable management in microgreens business operations.
     *
     * @return void
     * @business_workflow Invoice status transition to 'paid' state
     * @financial_tracking Records payment completion for revenue tracking
     * @agricultural_context Completes sales cycle for microgreens orders
     * @timestamp_tracking Records paid_at for financial reporting
     */
    public function markAsPaid()
    {
        $this->record->markAsPaid();
        $this->refreshFormData(['status', 'paid_at']);
        
        Notification::make()
            ->title('Invoice marked as paid')
            ->success()
            ->send();
    }

    /**
     * Mark invoice as overdue with status update and warning notification.
     *
     * Updates invoice status to 'overdue' for accounts receivable management.
     * Essential for agricultural business cash flow management and customer
     * follow-up workflows in microgreens sales operations.
     *
     * @return void
     * @business_workflow Invoice status transition to 'overdue' state
     * @financial_management Triggers accounts receivable follow-up processes
     * @agricultural_context Manages payment delays for microgreens sales
     * @ui_feedback Warning notification indicates potential collection issue
     */
    public function markAsOverdue()
    {
        $this->record->markAsOverdue();
        $this->refreshFormData(['status']);
        
        Notification::make()
            ->title('Invoice marked as overdue')
            ->warning()
            ->send();
    }

    /**
     * Mark invoice as cancelled with status update and warning notification.
     *
     * Updates invoice status to 'cancelled' for order cancellation workflows.
     * Important for agricultural business operations where orders may be
     * cancelled due to crop failures or customer changes in microgreens sales.
     *
     * @return void
     * @business_workflow Invoice status transition to 'cancelled' state
     * @agricultural_context Handles order cancellations in microgreens production
     * @financial_tracking Removes from accounts receivable for accurate reporting
     * @ui_feedback Warning notification confirms cancellation action
     */
    public function markAsCancelled()
    {
        $this->record->markAsCancelled();
        $this->refreshFormData(['status']);
        
        Notification::make()
            ->title('Invoice cancelled')
            ->warning()
            ->send();
    }
}
