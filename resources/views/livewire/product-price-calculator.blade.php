<div class="p-4 bg-white rounded-lg shadow">
    @php
        $record = $getRecord() ?? null;
    @endphp
    @if($record)
        <div class="space-y-4">
            <div>
                <label for="customerType" class="block text-sm font-medium text-gray-700">Customer Type</label>
                <select id="customerType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                    <option value="bulk">Bulk</option>
                    <option value="special">Special</option>
                </select>
            </div>
            
            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" id="quantity" min="1" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
            </div>
            
            <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Calculated Price:</span>
                    <span class="text-xl font-bold text-primary-600">
                        @if($record->base_price)
                            ${{ number_format($record->base_price, 2) }}
                        @else
                            $0.00
                        @endif
                    </span>
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    Based on customer type and quantity
                </div>
            </div>
        </div>
    @else
        <div class="p-4 text-gray-500 text-center">
            Save the product first to enable price calculations.
        </div>
    @endif
</div> 