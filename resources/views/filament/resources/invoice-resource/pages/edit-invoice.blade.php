<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header with actions -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Edit Invoice {{ $this->record->invoice_number }}
                </h1>
                <p class="text-sm text-gray-600 mt-1">
                    @if($this->record->is_consolidated)
                        Consolidated invoice for {{ $this->record->consolidated_order_count }} orders
                    @else
                        Invoice for Order #{{ $this->record->order_id }}
                    @endif
                </p>
            </div>
        </div>

        <!-- Invoice Form -->
        <form wire:submit="save">
            {{ $this->form }}
            
            <div class="flex justify-end gap-3 mt-6">
                <x-filament::button
                    type="button"
                    color="gray"
                    tag="a"
                    :href="$this->getResource()::getUrl('index')"
                >
                    Cancel
                </x-filament::button>
                
                <x-filament::button type="submit">
                    Save Changes
                </x-filament::button>
            </div>
        </form>

        <!-- Status change actions -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                @if($this->record->status === 'draft')
                    <x-filament::button
                        color="primary"
                        icon="heroicon-o-paper-airplane"
                        wire:click="markAsSent"
                        wire:confirm="Mark this invoice as sent?"
                    >
                        Mark as Sent
                    </x-filament::button>
                @endif
                
                @if(in_array($this->record->status, ['sent', 'overdue']))
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-check-circle"
                        wire:click="markAsPaid"
                        wire:confirm="Mark this invoice as paid?"
                    >
                        Mark as Paid
                    </x-filament::button>
                @endif
                
                @if($this->record->status === 'sent' && $this->record->due_date < now())
                    <x-filament::button
                        color="danger"
                        icon="heroicon-o-exclamation-triangle"
                        wire:click="markAsOverdue"
                        wire:confirm="Mark this invoice as overdue?"
                    >
                        Mark as Overdue
                    </x-filament::button>
                @endif
                
                @if(in_array($this->record->status, ['draft', 'sent', 'overdue']))
                    <x-filament::button
                        color="gray"
                        icon="heroicon-o-x-mark"
                        wire:click="markAsCancelled"
                        wire:confirm="Cancel this invoice? This action cannot be undone."
                    >
                        Cancel Invoice
                    </x-filament::button>
                @endif
            </div>
        </div>
    </div>

    @script
    <script>
        // Add any custom JavaScript for the invoice edit page
        document.addEventListener('livewire:init', () => {
            Livewire.on('invoice-updated', (data) => {
                // Handle invoice updates if needed
                console.log('Invoice updated:', data);
            });
        });

        // Add methods for status changes
        window.markAsSent = function() {
            @this.call('markAsSent');
        };

        window.markAsPaid = function() {
            @this.call('markAsPaid');
        };

        window.markAsOverdue = function() {
            @this.call('markAsOverdue');
        };

        window.markAsCancelled = function() {
            @this.call('markAsCancelled');
        };
    </script>
    @endscript
</x-filament-panels::page>