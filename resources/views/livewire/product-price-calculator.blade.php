<div class="p-4 bg-white rounded-lg shadow">
    @php
        $record = $getRecord() ?? null;
    @endphp
    @if($record)
        <div class="space-y-4">
            <div>
                <label for="customerType" class="block text-sm font-medium text-gray-700">Customer Type</label>
                <select id="customerType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md" onchange="updateCalculatedPrice()">
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                    <option value="bulk">Bulk</option>
                    <option value="special">Special</option>
                </select>
            </div>
            
            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                <input type="number" id="quantity" min="1" value="1" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" onchange="updateCalculatedPrice()">
            </div>
            
            <div class="border-t border-gray-200 pt-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700">Calculated Price:</span>
                    <span id="calculatedPrice" class="text-xl font-bold text-primary-600">
                        @php
                            $defaultPrice = $record->defaultPriceVariation() ? $record->defaultPriceVariation()->price : ($record->base_price ?? 0);
                        @endphp
                        ${{ number_format($defaultPrice, 2) }}
                    </span>
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    Based on customer type and quantity
                </div>
            </div>
            
            <div class="pt-2 text-xs text-gray-500">
                <strong>Available Price Variations:</strong>
                <ul class="mt-1 space-y-1">
                    @foreach($record->activePriceVariations() as $variation)
                        <li>
                            <span class="font-medium">{{ $variation->name }}:</span> 
                            ${{ number_format($variation->price, 2) }}
                            @if($variation->unit !== 'item')
                                per {{ $variation->unit }}
                            @endif
                            @if($variation->is_default)
                                <span class="text-xs text-green-600">(Default)</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <script>
            function updateCalculatedPrice() {
                const customerType = document.getElementById('customerType').value;
                const quantity = document.getElementById('quantity').value || 1;
                
                // Get prices from variations
                @php
                    $defaultVariation = $record->defaultPriceVariation();
                    $defaultPrice = $defaultVariation ? $defaultVariation->price : ($record->base_price ?? 0);
                    
                    $wholesaleVariation = $record->getPriceVariationByName('Wholesale');
                    $wholesalePrice = $wholesaleVariation ? $wholesaleVariation->price : ($record->wholesale_price ?? $defaultPrice);
                    
                    $bulkVariation = $record->getPriceVariationByName('Bulk');
                    $bulkPrice = $bulkVariation ? $bulkVariation->price : ($record->bulk_price ?? $defaultPrice);
                    
                    $specialVariation = $record->getPriceVariationByName('Special');
                    $specialPrice = $specialVariation ? $specialVariation->price : ($record->special_price ?? $defaultPrice);
                @endphp
                
                let price = {{ $defaultPrice }};
                
                switch(customerType) {
                    case 'wholesale':
                        price = {{ $wholesalePrice }};
                        break;
                    case 'bulk':
                        price = {{ $bulkPrice }};
                        break;
                    case 'special':
                        price = {{ $specialPrice }};
                        break;
                    default:
                        price = {{ $defaultPrice }};
                }
                
                // Simple calculation (multiply by quantity)
                const totalPrice = (price * quantity).toFixed(2);
                
                // Update the displayed price
                document.getElementById('calculatedPrice').textContent = '$' + totalPrice;
            }
        </script>
    @else
        <div class="p-4 text-gray-500 text-center">
            Save the product first to enable price calculations.
        </div>
    @endif
</div> 