@php
    // Get the Livewire component and product record
    $livewire = $this ?? null;
    $product = null;
    
    if ($livewire && method_exists($livewire, 'getRecord')) {
        $product = $livewire->getRecord();
    } elseif ($livewire && property_exists($livewire, 'record')) {
        $product = $livewire->record;
    }
    
    // Get variations if we have a product - always fetch fresh from database
    $variations = collect();
    if ($product && $product->exists) {
        $variations = \App\Models\PriceVariation::where('product_id', $product->id)->get();
    }
@endphp

<div class="space-y-4" wire:key="price-variations-table-{{ $product?->id }}">
    
    @if($variations->isNotEmpty())
        <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Name
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Packaging
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            SKU
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Weight (g)
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Price
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Default
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Active
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($variations as $variation)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800" x-data="{ editing: false }">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div x-show="!editing" class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $variation->name }}
                                </div>
                                <input x-show="editing" 
                                       x-model="name" 
                                       type="text" 
                                       class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                       x-init="name = '{{ $variation->name }}'"
                                       x-cloak>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div x-show="!editing" class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $variation->packagingType?->display_name ?? 'No packaging' }}
                                </div>
                                <select x-show="editing" 
                                        x-model="packaging_type_id" 
                                        class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                        x-init="packaging_type_id = '{{ $variation->packaging_type_id }}'"
                                        x-cloak>
                                    <option value="">No packaging</option>
                                    @foreach(\App\Models\PackagingType::all() as $packaging)
                                        <option value="{{ $packaging->id }}">{{ $packaging->display_name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div x-show="!editing" class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $variation->sku ?? '-' }}
                                </div>
                                <input x-show="editing" 
                                       x-model="sku" 
                                       type="text" 
                                       class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                       x-init="sku = '{{ $variation->sku }}'"
                                       x-cloak>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div x-show="!editing" class="text-sm text-gray-900 dark:text-gray-100">
                                    {{ $variation->fill_weight_grams ? number_format($variation->fill_weight_grams, 2) . 'g' : '-' }}
                                </div>
                                <input x-show="editing" 
                                       x-model="fill_weight_grams" 
                                       type="number" 
                                       step="0.01"
                                       class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                       x-init="fill_weight_grams = '{{ $variation->fill_weight_grams }}'"
                                       x-cloak>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div x-show="!editing" class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    ${{ number_format($variation->price, 2) }}
                                </div>
                                <input x-show="editing" 
                                       x-model="price" 
                                       type="number" 
                                       step="0.01"
                                       min="0"
                                       class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100"
                                       x-init="price = '{{ $variation->price }}'"
                                       x-cloak>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($variation->is_default)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Default
                                        </span>
                                    @else
                                        <button type="button" 
                                                wire:click="setAsDefault({{ $variation->id }})"
                                                class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            Set as default
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($variation->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <button x-show="!editing" 
                                            @click="editing = true"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        Edit
                                    </button>
                                    <button x-show="editing" 
                                            @click="$wire.updateVariation({{ $variation->id }}, { 
                                                name: name, 
                                                packaging_type_id: packaging_type_id, 
                                                sku: sku, 
                                                fill_weight_grams: fill_weight_grams, 
                                                price: price 
                                            }).then(() => editing = false)"
                                            class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                            x-cloak>
                                        Save
                                    </button>
                                    <button x-show="editing" 
                                            @click="editing = false"
                                            class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                                            x-cloak>
                                        Cancel
                                    </button>
                                    <button @click="if (confirm('Are you sure you want to delete this price variation?')) { $wire.deleteVariation({{ $variation->id }}) }"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No price variations</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a price variation for this product.</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">You can use templates above or create custom variations below.</p>
        </div>
    @endif
    
    <div class="flex justify-between items-center">
        <button type="button" 
                wire:click="addCustomVariation"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Custom Variation
        </button>
        
        @if($variations->isNotEmpty())
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $variations->where('is_active', true)->count() }} active / {{ $variations->count() }} total variations
            </div>
        @endif
    </div>
</div>