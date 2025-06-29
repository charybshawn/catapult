<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Forms\Form;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Livewire\Attributes\Computed;

class EditInvoice extends BaseEditRecord
{
    protected static string $resource = InvoiceResource::class;
    protected static string $view = 'filament.resources.invoice-resource.pages.edit-invoice';
    
    public $orderItems = [];

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

    public function updatedOrderItems()
    {
        // This will trigger when any order item is updated
        $this->dispatch('order-items-updated', total: $this->calculatedTotal);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Preview Invoice')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(fn () => route('filament.admin.resources.invoices.view', ['record' => $this->record->id]))
                ->openUrlInNewTab(),
            Actions\Action::make('download_pdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(fn () => route('invoices.download', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => in_array($this->record->status, ['sent', 'paid', 'overdue', 'pending'])),
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\View::make('components.invoice-preview')
                            ->viewData([
                                'invoice' => $this->record,
                                'editable' => true
                            ]),
                    ]),
            ]);
    }

    public function markAsSent()
    {
        $this->record->markAsSent();
        $this->refreshFormData(['status', 'sent_at']);
        
        \Filament\Notifications\Notification::make()
            ->title('Invoice marked as sent')
            ->success()
            ->send();
    }

    public function markAsPaid()
    {
        $this->record->markAsPaid();
        $this->refreshFormData(['status', 'paid_at']);
        
        \Filament\Notifications\Notification::make()
            ->title('Invoice marked as paid')
            ->success()
            ->send();
    }

    public function markAsOverdue()
    {
        $this->record->markAsOverdue();
        $this->refreshFormData(['status']);
        
        \Filament\Notifications\Notification::make()
            ->title('Invoice marked as overdue')
            ->warning()
            ->send();
    }

    public function markAsCancelled()
    {
        $this->record->markAsCancelled();
        $this->refreshFormData(['status']);
        
        \Filament\Notifications\Notification::make()
            ->title('Invoice cancelled')
            ->warning()
            ->send();
    }
}
