<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
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
        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
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
                    <td>{{ $invoice->invoice_number }}</td>
                    <td><strong>Invoice Date:</strong></td>
                    <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Order #:</strong></td>
                    <td>{{ $order->id }}</td>
                    <td><strong>Due Date:</strong></td>
                    <td>{{ $invoice->due_date->format('M d, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>{{ ucfirst($invoice->status) }}</td>
                    <td><strong>Delivery Date:</strong></td>
                    <td>{{ $order->delivery_date->format('M d, Y') }}</td>
                </tr>
            </table>
        </div>
        
        <div class="customer-details">
            <h3>Bill To:</h3>
            <div>{{ $customer->name }}</div>
            <div>{{ $customer->address ?? 'No address provided' }}</div>
            <div>Email: {{ $customer->email }}</div>
            <div>Phone: {{ $customer->phone ?? 'No phone provided' }}</div>
        </div>
        
        <h3>Order Items</h3>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $orderItem)
                <tr>
                    <td>{{ $orderItem->item->name }}</td>
                    <td>{{ $orderItem->quantity }}</td>
                    <td>${{ number_format($orderItem->price, 2) }}</td>
                    <td class="text-right">${{ number_format($orderItem->quantity * $orderItem->price, 2) }}</td>
                </tr>
                @endforeach
                
                @if($packagings->count() > 0)
                <tr>
                    <td colspan="4"><strong>Packaging</strong></td>
                </tr>
                
                @foreach($packagings as $packaging)
                <tr>
                    <td>{{ $packaging->packagingType->name }}</td>
                    <td>{{ $packaging->quantity }}</td>
                    <td>${{ number_format($packaging->packagingType->cost_per_unit, 2) }}</td>
                    <td class="text-right">${{ number_format($packaging->quantity * $packaging->packagingType->cost_per_unit, 2) }}</td>
                </tr>
                @endforeach
                @endif
                
                <tr class="total-row">
                    <td colspan="3" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right">${{ number_format($invoice->amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="payment-info">
            <h3>Payment Information</h3>
            <p>Please include the invoice number with your payment.</p>
            <p><strong>Payment Methods:</strong></p>
            <ul>
                <li>E-Transfer: payments@catapultmicrogreens.com</li>
                <li>Check: Make payable to "Catapult Microgreens"</li>
            </ul>
            <p><strong>Payment Terms:</strong> Net 30 days</p>
        </div>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact us.</p>
        </div>
    </div>
</body>
</html> 