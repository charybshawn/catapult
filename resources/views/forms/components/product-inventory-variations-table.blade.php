@php
    $variations = $getVariations();
    $batchNumber = $getBatchNumber();
    $isDisabled = $isDisabled();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="space-y-4" x-data="inventoryVariations">
        @if(empty($variations))
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Please select a product first to see its price variations.
                </p>
            </div>
        @else
            <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <h3 class="text-lg font-medium text-blue-900 dark:text-blue-100 mb-2">
                    Batch Information
                </h3>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Batch Number
                        </label>
                        <input 
                            type="text" 
                            x-model="batchNumber"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm"
                            value="{{ $batchNumber }}"
                            placeholder="Auto-generated"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Production Date
                        </label>
                        <input 
                            type="date" 
                            x-model="productionDate"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm"
                            value="{{ now()->toDateString() }}"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Storage Location
                        </label>
                        <input 
                            type="text" 
                            x-model="location"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm"
                            placeholder="e.g., Warehouse A, Shelf 3"
                        />
                    </div>
                </div>
            </div>

            <div class="overflow-hidden bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Price Variations Inventory
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Enter inventory quantities for each price variation. Only variations with quantities > 0 will be saved.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Variation
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Packaging
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Weight/Qty
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Price
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Quantity *
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Cost/Unit
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Lot #
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Expiry Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            @foreach($variations as $index => $variation)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $variation['name'] }}
                                        </div>
                                        @if($variation['sku'])
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                SKU: {{ $variation['sku'] }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ $variation['packaging_name'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        @if($variation['fill_weight'])
                                            {{ $variation['fill_weight'] }}g
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        ${{ number_format($variation['price'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input 
                                            type="number" 
                                            x-model="variations[{{ $index }}].quantity"
                                            class="w-20 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="0"
                                            min="0"
                                            step="0.01"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <span class="text-gray-500 dark:text-gray-400 text-sm">$</span>
                                            </div>
                                            <input 
                                                type="number" 
                                                x-model="variations[{{ $index }}].cost_per_unit"
                                                class="w-24 pl-7 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                                placeholder="0.00"
                                                min="0"
                                                step="0.01"
                                            />
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input 
                                            type="text" 
                                            x-model="variations[{{ $index }}].lot_number"
                                            class="w-24 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                            placeholder="Optional"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input 
                                            type="date" 
                                            x-model="variations[{{ $index }}].expiration_date"
                                            class="w-36 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-sm"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                <strong>Note:</strong> Only price variations with a quantity greater than 0 will create inventory entries. 
                Each variation will create a separate inventory record with the shared batch information above.
            </div>
        @endif
    </div>

    <!-- Alpine.js component -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('inventoryVariations', () => ({
                variations: @js($variations),
                batchNumber: '{{ $batchNumber }}',
                productionDate: '{{ now()->toDateString() }}',
                location: '',
                
                init() {
                    // Watch for changes and sync with Livewire
                    this.$watch('variations', (value) => {
                        @this.set('{{ $statePath }}', {
                            batch_number: this.batchNumber,
                            production_date: this.productionDate,
                            location: this.location,
                            variations: value
                        });
                    }, { deep: true });
                    
                    this.$watch('batchNumber', (value) => {
                        @this.set('{{ $statePath }}.batch_number', value);
                    });
                    
                    this.$watch('productionDate', (value) => {
                        @this.set('{{ $statePath }}.production_date', value);
                    });
                    
                    this.$watch('location', (value) => {
                        @this.set('{{ $statePath }}.location', value);
                    });
                }
            }))
        })
    </script>
</x-dynamic-component>