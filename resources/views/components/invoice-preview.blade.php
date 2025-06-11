@php
    $invoice = $invoice ?? $this->record ?? null;
    if (!$invoice) return;
    
    // Get orders associated with this invoice
    $orders = $invoice->is_consolidated 
        ? $invoice->consolidatedOrders()->with(['orderItems.product', 'user'])->get()
        : ($invoice->order ? collect([$invoice->order->load(['orderItems.product', 'user'])]) : collect());
    
    $customer = $invoice->user ?? $orders->first()?->user;
@endphp

<div class="invoice-container bg-white dark:bg-gray-900 shadow-lg rounded-lg overflow-hidden print:shadow-none print:rounded-none" style="max-width: 8.5in; margin: 0 auto;">
    <!-- Invoice Header -->
    <div class="invoice-header bg-gray-50 dark:bg-gray-800 px-8 py-6 border-b border-gray-200 dark:border-gray-700 print:bg-gray-50 print:border-gray-200">
        <div class="flex justify-between items-start">
            <!-- Company Info -->
            <div class="company-info">
                <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2 print:text-gray-800">Your Company Name</h1>
                <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1 print:text-gray-600">
                    <p>123 Farm Road</p>
                    <p>City, State 12345</p>
                    <p>Phone: (555) 123-4567</p>
                    <p>Email: info@yourcompany.com</p>
                </div>
            </div>
            
            <!-- Invoice Title & Number -->
            <div class="invoice-title text-right">
                <h2 class="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-2 print:text-blue-600">INVOICE</h2>
                <div class="text-lg font-semibold text-gray-700 dark:text-gray-200 print:text-gray-700">
                    @if(isset($editable) && $editable)
                        <input type="text" 
                               wire:model.live="form.invoice_number"
                               class="bg-transparent border-none text-lg font-semibold text-gray-700 dark:text-gray-200 p-0 focus:ring-0 focus:border-0 text-right w-full"
                               style="background: transparent !important;">
                    @else
                        {{ $invoice->invoice_number }}
                    @endif
                </div>
                @if($invoice->is_consolidated)
                    <div class="text-sm text-blue-600 dark:text-blue-400 mt-1 print:text-blue-600">
                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 print:bg-blue-100 print:text-blue-800">
                            Consolidated Invoice
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="invoice-details px-8 py-6">
        <div class="grid grid-cols-2 gap-8">
            <!-- Bill To -->
            <div class="bill-to">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-3 print:text-gray-800">Bill To:</h3>
                <div class="text-gray-700 dark:text-gray-300 space-y-1 print:text-gray-700">
                    <p class="font-semibold">{{ $customer->name ?? 'Unknown Customer' }}</p>
                    @if($customer?->company_name)
                        <p>{{ $customer->company_name }}</p>
                    @endif
                    @if($customer?->address)
                        <p>{{ $customer->address }}</p>
                    @endif
                    @if($customer?->city || $customer?->state || $customer?->zip)
                        <p>
                            {{ $customer->city }}@if($customer->city && ($customer->state || $customer->zip)), @endif
                            {{ $customer->state }} {{ $customer->zip }}
                        </p>
                    @endif
                    @if($customer?->email)
                        <p>{{ $customer->email }}</p>
                    @endif
                    @if($customer?->phone)
                        <p>{{ $customer->phone }}</p>
                    @endif
                </div>
            </div>

            <!-- Invoice Info -->
            <div class="invoice-info">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-3 print:text-gray-800">Invoice Details:</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Issue Date:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100 print:text-gray-900">
                            @if(isset($editable) && $editable)
                                <input type="date" 
                                       wire:model.live="form.issue_date"
                                       class="bg-transparent border-none text-sm font-semibold text-gray-900 dark:text-gray-100 p-0 focus:ring-0 focus:border-0 text-right"
                                       style="background: transparent !important;">
                            @else
                                {{ $invoice->issue_date?->format('M d, Y') ?? $invoice->created_at->format('M d, Y') }}
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Due Date:</span>
                        <span class="font-semibold {{ $invoice->due_date < now() && $invoice->status !== 'paid' ? 'text-red-600 dark:text-red-400 print:text-red-600' : 'text-gray-900 dark:text-gray-100 print:text-gray-900' }}">
                            @if(isset($editable) && $editable)
                                <input type="date" 
                                       wire:model.live="form.due_date"
                                       class="bg-transparent border-none text-sm font-semibold {{ $invoice->due_date < now() && $invoice->status !== 'paid' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }} p-0 focus:ring-0 focus:border-0 text-right"
                                       style="background: transparent !important;">
                            @else
                                {{ $invoice->due_date?->format('M d, Y') ?? 'Not set' }}
                            @endif
                        </span>
                    </div>
                    @if($invoice->billing_period_start && $invoice->billing_period_end)
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Billing Period:</span>
                            <span class="font-semibold text-gray-900 dark:text-gray-100 print:text-gray-900">
                                {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                            </span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Status:</span>
                        @if(isset($editable) && $editable)
                            <select wire:model.live="form.status" class="bg-transparent border-none text-xs font-medium rounded-full px-2 py-1 focus:ring-0 focus:border-0 
                                @switch($invoice->status)
                                    @case('draft') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 @break
                                    @case('sent') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 @break
                                    @case('paid') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 @break
                                    @case('overdue') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 @break
                                    @case('cancelled') bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300 @break
                                    @default bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300
                                @endswitch" style="background: transparent !important;">
                                <option value="draft">Draft</option>
                                <option value="sent">Sent</option>
                                <option value="paid">Paid</option>
                                <option value="overdue">Overdue</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                @switch($invoice->status)
                                    @case('draft') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 print:bg-yellow-100 print:text-yellow-800 @break
                                    @case('sent') bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 print:bg-blue-100 print:text-blue-800 @break
                                    @case('paid') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 print:bg-green-100 print:text-green-800 @break
                                    @case('overdue') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 print:bg-red-100 print:text-red-800 @break
                                    @case('cancelled') bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300 print:bg-gray-100 print:text-gray-800 @break
                                    @default bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300 print:bg-gray-100 print:text-gray-800
                                @endswitch
                            ">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Line Items -->
    <div class="line-items px-8">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 print:text-gray-800">Items:</h3>
        
        @if($orders->isNotEmpty())
            <!-- Flatten all line items -->
            @php
                $allItems = collect();
                foreach($orders as $order) {
                    foreach($order->orderItems as $itemIndex => $item) {
                        $allItems->push([
                            'product_name' => $item->product->name ?? 'Unknown Product',
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->price * $item->quantity,
                            'delivery_date' => $order->delivery_date,
                            'delivery_date_formatted' => $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : null,
                            'order_id' => $order->id,
                            'item_index' => $itemIndex
                        ]);
                    }
                }
            @endphp
            
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-200 dark:border-gray-700 rounded-lg print:border-gray-200">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-800 print:bg-gray-50">
                            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300 print:text-gray-700">Product</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300 print:text-gray-700">Quantity</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300 print:text-gray-700">Unit Price</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300 print:text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $groupedByDelivery = $allItems->groupBy('delivery_date_formatted');
                        @endphp
                        
                        @foreach($allItems as $globalIndex => $item)
                            <tr class="border-b border-gray-100 dark:border-gray-700 print:border-gray-100">
                                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 print:text-gray-700">
                                    @if(isset($editable) && $editable)
                                        <input type="text" 
                                               value="{{ $item['product_name'] }}"
                                               wire:model.live="orderItems.{{ $item['order_id'] }}.{{ $item['item_index'] }}.product_name"
                                               class="bg-transparent border-none text-sm text-gray-700 dark:text-gray-300 p-0 focus:ring-0 focus:border-0 w-full"
                                               style="background: transparent !important;">
                                    @else
                                        {{ $item['product_name'] }}
                                    @endif
                                    @if($invoice->is_consolidated && $item['delivery_date'])
                                        <div class="text-xs italic text-gray-500 dark:text-gray-400 mt-1 print:text-gray-500">
                                            Delivery: {{ \Carbon\Carbon::parse($item['delivery_date'])->format('M d, Y') }}
                                        </div>
                                    @endif
                                </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 print:text-gray-700 text-right">
                                        @if(isset($editable) && $editable)
                                            <input type="number" 
                                                   value="{{ $item['quantity'] }}"
                                                   wire:model.live="orderItems.{{ $item['order_id'] }}.{{ $item['item_index'] }}.quantity"
                                                   class="bg-transparent border-none text-sm text-gray-700 dark:text-gray-300 p-0 focus:ring-0 focus:border-0 text-right w-16"
                                                   style="background: transparent !important;">
                                        @else
                                            {{ $item['quantity'] }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 print:text-gray-700 text-right">
                                        @if(isset($editable) && $editable)
                                            <input type="text" 
                                                   value="{{ number_format($item['price'], 2) }}"
                                                   wire:model.live="orderItems.{{ $item['order_id'] }}.{{ $item['item_index'] }}.price"
                                                   class="bg-transparent border-none text-sm text-gray-700 dark:text-gray-300 p-0 focus:ring-0 focus:border-0 text-right w-20"
                                                   style="background: transparent !important;">
                                        @else
                                            ${{ number_format($item['price'], 2) }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 print:text-gray-700 text-right font-semibold">
                                        @if(isset($editable) && $editable)
                                            @php
                                                $quantity = $this->orderItems[$item['order_id']][$item['item_index']]['quantity'] ?? $item['quantity'];
                                                $price = $this->orderItems[$item['order_id']][$item['item_index']]['price'] ?? $item['price'];
                                                $total = $quantity * (float)str_replace(',', '', $price);
                                            @endphp
                                            ${{ number_format($total, 2) }}
                                        @else
                                            ${{ number_format($item['total'], 2) }}
                                        @endif
                                    </td>
                                </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <!-- No order data available -->
            <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4 text-center print:bg-yellow-50 print:border-yellow-200">
                <p class="text-yellow-800 dark:text-yellow-300 print:text-yellow-800">No order items available for this invoice.</p>
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mt-1 print:text-yellow-600">This may be a manually created invoice or the order data is not accessible.</p>
            </div>
        @endif
    </div>

    <!-- Invoice Total -->
    <div class="invoice-total px-8 py-6 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 print:bg-gray-50 print:border-gray-200">
        <div class="flex justify-end">
            <div class="w-64">
                @if($invoice->is_consolidated)
                    <div class="flex justify-between py-2">
                        <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Subtotal ({{ $invoice->consolidated_order_count }} orders):</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100 print:text-gray-900">
                            @if(isset($editable) && $editable && isset($this))
                                ${{ number_format($this->calculatedTotal, 2) }}
                            @else
                                ${{ number_format($invoice->total_amount, 2) }}
                            @endif
                        </span>
                    </div>
                @else
                    <div class="flex justify-between py-2">
                        <span class="text-gray-600 dark:text-gray-400 print:text-gray-600">Subtotal:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100 print:text-gray-900">
                            @if(isset($editable) && $editable && isset($this))
                                ${{ number_format($this->calculatedTotal, 2) }}
                            @else
                                ${{ number_format($invoice->amount, 2) }}
                            @endif
                        </span>
                    </div>
                @endif
                
                <!-- You can add tax calculations here if needed -->
                <!-- <div class="flex justify-between py-2">
                    <span class="text-gray-600 dark:text-gray-400">Tax (8.5%):</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">${{ number_format($invoice->amount * 0.085, 2) }}</span>
                </div> -->
                
                <div class="border-t border-gray-300 dark:border-gray-600 pt-2 mt-2 print:border-gray-300">
                    <div class="flex justify-between py-2">
                        <span class="text-lg font-bold text-gray-800 dark:text-gray-100 print:text-gray-800">Total Amount:</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100 print:text-gray-900">
                            @if(isset($editable) && $editable && isset($this))
                                ${{ number_format($this->calculatedTotal, 2) }}
                            @else
                                ${{ number_format($invoice->total_amount ?? $invoice->amount, 2) }}
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes and Payment Terms -->
    @if($invoice->notes || $invoice->due_date || (isset($editable) && $editable))
        <div class="invoice-footer px-8 py-6 border-t border-gray-200 dark:border-gray-700 print:border-gray-200">
            @if($invoice->notes || (isset($editable) && $editable))
                <div class="mb-4">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2 print:text-gray-800">Notes:</h4>
                    @if(isset($editable) && $editable)
                        <textarea wire:model.live="form.notes"
                                  placeholder="Add invoice notes..."
                                  rows="3"
                                  class="bg-transparent border-none text-sm text-gray-600 dark:text-gray-300 leading-relaxed p-0 focus:ring-0 focus:border-0 w-full resize-none"
                                  style="background: transparent !important;">{{ $invoice->notes }}</textarea>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed print:text-gray-600">{{ $invoice->notes }}</p>
                    @endif
                </div>
            @endif
            
            <div class="payment-terms">
                <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-100 mb-2 print:text-gray-800">Payment Terms:</h4>
                <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1 print:text-gray-600">
                    @if($invoice->due_date)
                        <p>Payment is due by {{ $invoice->due_date->format('F j, Y') }}.</p>
                    @endif
                    <p>Please remit payment to the address above or contact us for electronic payment options.</p>
                    <p>Thank you for your business!</p>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Print Styles -->
<style>
    @media print {
        .invoice-container {
            box-shadow: none !important;
            border-radius: 0 !important;
            max-width: none !important;
            margin: 0 !important;
            background: white !important;
        }
        
        .no-print {
            display: none !important;
        }
        
        /* Ensure proper page breaks */
        .order-section {
            page-break-inside: avoid;
        }
        
        /* Force all text to be black for printing while preserving structure */
        .invoice-container * {
            color: black !important;
        }
        
        /* Preserve table borders and structure for printing */
        table, th, td {
            border-color: black !important;
        }
        
        /* Ensure backgrounds are white for printing */
        .invoice-container,
        .invoice-container * {
            background: white !important;
        }
        
        /* Preserve important styling for print headers */
        .invoice-header {
            background: #f9fafb !important;
            border-bottom: 1px solid #e5e7eb !important;
        }
        
        .invoice-total {
            background: #f9fafb !important;
            border-top: 1px solid #e5e7eb !important;
        }
        
        /* Table headers */
        thead tr {
            background: #f9fafb !important;
        }
        
        /* Status badges - keep some styling for print */
        .inline-flex {
            background: #f3f4f6 !important;
            color: black !important;
        }
    }
    
    /* Custom invoice styles */
    .invoice-container {
        font-family: 'Inter', 'Segoe UI', 'Roboto', sans-serif;
        line-height: 1.5;
    }
    
    /* Dark mode transitions */
    .invoice-container * {
        transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
    }
</style>