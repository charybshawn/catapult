<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Invoice {{ $this->record->invoice_number }}
                </h1>
                <p class="text-sm text-gray-600 mt-1">
                    @if($this->record->is_consolidated)
                        Consolidated invoice for {{ $this->record->consolidated_order_count }} orders
                    @else
                        Invoice for Order #{{ $this->record->order_id }}
                    @endif
                </p>
            </div>
            
            <!-- Print button -->
            <x-filament::button
                color="gray"
                icon="heroicon-o-printer"
                onclick="window.print()"
            >
                Print Invoice
            </x-filament::button>
        </div>

        <!-- Invoice Preview -->
        {{ $this->infolist }}
    </div>

    <style>
        @media print {
            /* Hide everything except the invoice preview */
            .fi-topbar,
            .fi-sidebar,
            .fi-main-ctn > .fi-header,
            .fi-breadcrumbs,
            .fi-page-header,
            .fi-section-header {
                display: none !important;
            }
            
            .fi-main-ctn {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .fi-main {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            .fi-page {
                padding: 0 !important;
                margin: 0 !important;
                background: white !important;
            }
            
            /* Show only the invoice content */
            .invoice-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                max-width: none !important;
                margin: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</x-filament-panels::page>