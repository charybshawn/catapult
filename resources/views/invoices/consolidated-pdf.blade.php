<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consolidated Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            font-size: 14px;
            line-height: 1.4;
        }
        .container {
            width: 100%;
            padding: 20px;
        }
        .header {
            margin-bottom: 30px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #336633;
        }
        .invoice-details {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
        }
        .customer-details {
            margin-bottom: 20px;
        }
        .billing-period {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #2563eb;
            background-color: #eff6ff;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f2f2f2;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
            border-top: 2px solid #333;
        }
        .order-group {
            margin-bottom: 30px;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
        }
        .order-header {
            background-color: #f8f9fa;
            padding: 10px;
            font-weight: bold;
            border-bottom: 1px solid #e5e5e5;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            font-size: 12px;
            text-align: center;
        }
        .payment-info {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .consolidated-badge {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">Catapult Microgreens</div>
            <div>123 Farm Lane, Growville, CA 12345</div>
            <div>Phone: (123) 456-7890</div>
            <div>Email: info@catapultmicrogreens.com</div>
        </div>
        
        <div class="invoice-details">
            <table>
                <tr>
                    <td><strong>Invoice #:</strong></td>
                    <td>{{ $invoice->invoice_number }} <span class="consolidated-badge">CONSOLIDATED</span></td>
                    <td><strong>Invoice Date:</strong></td>
                    <td>{{ $invoice->issue_date->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Order Count:</strong></td>
                    <td>{{ $invoice->consolidated_order_count }} orders</td>
                    <td><strong>Due Date:</strong></td>
                    <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>{{ ucfirst($invoice->status) }}</td>
                    <td><strong>Total Amount:</strong></td>
                    <td><strong>${{ number_format($invoice->total_amount, 2) }}</strong></td>
                </tr>
            </table>
        </div>

        <div class="billing-period">
            <h3 style="margin: 0 0 10px 0;">📅 Billing Period</h3>
            <p style="margin: 0;">
                <strong>{{ $invoice->billing_period_start->format('F d, Y') }}</strong> to 
                <strong>{{ $invoice->billing_period_end->format('F d, Y') }}</strong>
            </p>
        </div>
        
        <div class="customer-details">
            <h3>Bill To:</h3>
            <div><strong>{{ $customer->name }}</strong></div>
            @if($customer->company_name)
            <div>{{ $customer->company_name }}</div>
            @endif
            <div>{{ $customer->address ?? 'No address provided' }}</div>
            <div>Email: {{ $customer->email }}</div>
            <div>Phone: {{ $customer->phone ?? 'No phone provided' }}</div>
        </div>
        
        <h2>Consolidated Order Summary</h2>
        
        @foreach($orders as $order)
        <div class="order-group">
            <div class="order-header">
                Order #{{ $order->id }} - Delivered {{ $order->delivery_date->format('M d, Y') }}
            </div>
            
            <table style="margin: 0;">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->orderItems as $orderItem)
                    <tr>
                        <td>{{ $orderItem->product->name ?? $orderItem->name ?? 'Unknown Item' }}</td>
                        <td class="text-center">{{ $orderItem->quantity }}</td>
                        <td class="text-right">${{ number_format($orderItem->price, 2) }}</td>
                        <td class="text-right">${{ number_format($orderItem->quantity * $orderItem->price, 2) }}</td>
                    </tr>
                    @endforeach
                    
                    @if($order->orderPackagings && $order->orderPackagings->count() > 0)
                    <tr>
                        <td colspan="4" style="padding-top: 15px; font-weight: bold;">Packaging</td>
                    </tr>
                    @foreach($order->orderPackagings as $packaging)
                    <tr>
                        <td>{{ $packaging->packagingType->name }}</td>
                        <td class="text-center">{{ $packaging->quantity }}</td>
                        <td class="text-right">${{ number_format($packaging->packagingType->cost_per_unit, 2) }}</td>
                        <td class="text-right">${{ number_format($packaging->quantity * $packaging->packagingType->cost_per_unit, 2) }}</td>
                    </tr>
                    @endforeach
                    @endif
                    
                    <tr style="background-color: #f8f9fa;">
                        <td colspan="3" class="text-right"><strong>Order Subtotal:</strong></td>
                        <td class="text-right"><strong>${{ number_format($order->totalAmount(), 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endforeach
        
        <table style="border-top: 2px solid #333;">
            <tr class="total-row">
                <td colspan="3" class="text-right" style="font-size: 16px;"><strong>TOTAL AMOUNT DUE:</strong></td>
                <td class="text-right" style="font-size: 16px;"><strong>${{ number_format($invoice->total_amount, 2) }}</strong></td>
            </tr>
        </table>
        
        <div class="payment-info">
            <h3>Payment Information</h3>
            <p>Please include the invoice number <strong>{{ $invoice->invoice_number }}</strong> with your payment.</p>
            <p><strong>Payment Methods:</strong></p>
            <ul>
                <li>E-Transfer: payments@catapultmicrogreens.com</li>
                <li>Check: Make payable to "Catapult Microgreens"</li>
            </ul>
            <p><strong>Payment Terms:</strong> Net 30 days</p>
        </div>
        
        <div class="footer">
            <p><strong>Thank you for your business!</strong></p>
            <p>This consolidated invoice covers {{ $invoice->consolidated_order_count }} orders delivered during the billing period.</p>
            <p>If you have any questions about this invoice, please contact us at info@catapultmicrogreens.com</p>
        </div>
    </div>
</body>
</html>