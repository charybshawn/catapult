<div class="space-y-4">
    <div class="border-b border-gray-200 pb-4">
        <h3 class="text-lg font-medium text-gray-900">
            Order #{{ $orderId }} Details
        </h3>
        <p class="text-sm text-gray-600">{{ $customerName }} ({{ ucfirst($customerType) }})</p>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <h4 class="font-medium text-gray-900">Order Summary</h4>
            <div class="mt-2 space-y-1 text-sm">
                <div><span class="font-medium">Status:</span> 
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                        {{ $isOverdue ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ $status }}{{ $isOverdue ? ' (OVERDUE)' : '' }}
                    </span>
                </div>
                <div><span class="font-medium">Total Items:</span> {{ $totalItems }}</div>
                <div><span class="font-medium">Total Quantity:</span> {{ number_format($totalQuantity, 1) }}</div>
            </div>
        </div>

        <div>
            <h4 class="font-medium text-gray-900">Important Dates</h4>
            <div class="mt-2 space-y-1 text-sm">
                @if($harvestDate)
                    <div><span class="font-medium">Harvest Date:</span> {{ \Carbon\Carbon::parse($harvestDate)->format('M j, Y') }}</div>
                @endif
                @if($deliveryDate)
                    <div><span class="font-medium">Delivery Date:</span> {{ \Carbon\Carbon::parse($deliveryDate)->format('M j, Y') }}</div>
                @endif
            </div>
        </div>
    </div>

    @if(count($orderItems) > 0)
        <div>
            <h4 class="font-medium text-gray-900 mb-2">Order Items</h4>
            <div class="bg-gray-50 rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($orderItems as $item)
                            <tr>
                                <td class="px-3 py-2 text-sm text-gray-900">{{ $item['product_name'] }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900">
                                    {{ number_format($item['quantity'], 1) }} {{ $item['quantity_unit'] ?? '' }}
                                </td>
                                <td class="px-3 py-2 text-sm text-gray-900">
                                    ${{ number_format($item['price'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="pt-4 border-t border-gray-200">
        <p class="text-xs text-gray-500">
            Click "View Order" to see full order details and make changes.
        </p>
    </div>
</div>