<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crop Planting Schedule - {{ $delivery_date->format('F j, Y') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .header .date {
            font-size: 18px;
            color: #666;
            margin: 5px 0;
        }
        
        .header .generated {
            font-size: 10px;
            color: #888;
            margin-top: 10px;
        }
        
        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .summary h2 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 16px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-item .number {
            font-size: 24px;
            font-weight: bold;
            color: #27ae60;
        }
        
        .summary-item .label {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        
        .planting-requirements {
            margin-bottom: 30px;
        }
        
        .planting-requirements h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 18px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .variety-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            background-color: #fff;
        }
        
        .variety-header {
            background-color: #27ae60;
            color: white;
            padding: 10px 15px;
            border-radius: 5px 5px 0 0;
        }
        
        .variety-name {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }
        
        .variety-scientific {
            font-size: 12px;
            font-style: italic;
            margin: 2px 0 0 0;
            opacity: 0.9;
        }
        
        .variety-body {
            padding: 15px;
        }
        
        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .requirement-item {
            text-align: center;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
        }
        
        .requirement-number {
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
        }
        
        .requirement-label {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }
        
        .orders-breakdown {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .orders-breakdown h4 {
            margin: 0 0 10px 0;
            font-size: 12px;
            color: #666;
        }
        
        .order-list {
            font-size: 10px;
            color: #777;
        }
        
        .order-details {
            margin-top: 30px;
        }
        
        .order-details h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 18px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .order-card {
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 15px;
            background-color: #fff;
        }
        
        .order-header {
            background-color: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 5px 5px 0 0;
            font-size: 14px;
            font-weight: bold;
        }
        
        .order-items {
            padding: 15px;
        }
        
        .order-items table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .order-items th,
        .order-items td {
            text-align: left;
            padding: 5px 8px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }
        
        .order-items th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Crop Planting Schedule</h1>
        <div class="date">Delivery Date: {{ $delivery_date->format('l, F j, Y') }}</div>
        <div class="generated">Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</div>
    </div>

    <div class="summary">
        <h2>Summary</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="number">{{ count($planting_plan) }}</div>
                <div class="label">{{ Str::plural('Variety', count($planting_plan)) }} to Plant</div>
            </div>
            <div class="summary-item">
                <div class="number">{{ array_sum(array_column($planting_plan, 'total_trays_needed')) }}</div>
                <div class="label">Total Trays</div>
            </div>
            <div class="summary-item">
                <div class="number">{{ number_format(array_sum(array_column($planting_plan, 'total_grams_needed')), 1) }}g</div>
                <div class="label">Total Seed Weight</div>
            </div>
        </div>
    </div>

    <div class="planting-requirements">
        <h2>Planting Requirements by Variety</h2>
        @foreach($planting_plan as $seedEntryId => $requirement)
            <div class="variety-card">
                <div class="variety-header">
                    <div class="variety-name">{{ $requirement['seed_entry']->common_name }}</div>
                    @if($requirement['seed_entry']->scientific_name)
                        <div class="variety-scientific">{{ $requirement['seed_entry']->scientific_name }}</div>
                    @endif
                </div>
                <div class="variety-body">
                    <div class="requirements-grid">
                        <div class="requirement-item">
                            <div class="requirement-number">{{ $requirement['total_trays_needed'] }}</div>
                            <div class="requirement-label">Trays Needed</div>
                        </div>
                        <div class="requirement-item">
                            <div class="requirement-number">{{ number_format($requirement['total_grams_needed'], 1) }}g</div>
                            <div class="requirement-label">Seed Weight</div>
                        </div>
                    </div>
                    <div class="orders-breakdown">
                        <h4>Orders Requiring This Variety:</h4>
                        <div class="order-list">
                            @foreach($requirement['orders'] as $orderInfo)
                                Order #{{ $orderInfo['order_id'] }} ({{ $orderInfo['customer'] }}) - 
                                {{ $orderInfo['trays'] }} {{ Str::plural('tray', $orderInfo['trays']) }}, 
                                {{ number_format($orderInfo['grams'], 1) }}g<br>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="page-break"></div>

    <div class="order-details">
        <h2>Order Details</h2>
        @foreach($orders as $order)
            <div class="order-card">
                <div class="order-header">
                    Order #{{ $order->id }} - {{ $order->user->name }}
                </div>
                <div class="order-items">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Packaging</th>
                                <th>Fill Weight</th>
                                <th>Total Weight</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->orderItems as $item)
                                <tr>
                                    <td>{{ $item->product->name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ $item->priceVariation?->packagingType?->name ?? 'Unknown' }}</td>
                                    <td>{{ $item->priceVariation?->fill_weight ?? 100 }}g</td>
                                    <td>{{ ($item->quantity * ($item->priceVariation?->fill_weight ?? 100)) }}g</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>

    <div class="footer">
        This report was generated automatically from your order management system.<br>
        Please verify all calculations before proceeding with planting.
    </div>
</body>
</html>