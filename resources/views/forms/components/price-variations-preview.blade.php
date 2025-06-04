@php
    $variations = $getState() ?: [];
@endphp

<div class="space-y-4" x-data="{ variations: @js($variations) }" x-init="$watch('variations', () => console.log('Variations updated:', variations))">
    @if(empty($variations))
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            <p class="text-sm">No price variations created yet</p>
            <p class="text-xs mt-1">Add variations in the form above to see live pricing</p>
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Price Variations Preview</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Live preview of your pricing structure</p>
            </div>
            
            <div class="divide-y divide-gray-200 dark:divide-gray-600">
                @foreach($variations as $index => $variation)
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $variation['name'] ?? 'Unnamed Variation' }}
                                    </h4>
                                    @if(isset($variation['is_default']) && $variation['is_default'])
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            Default
                                        </span>
                                    @endif
                                    @if(isset($variation['is_active']) && !$variation['is_active'])
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="mt-1 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                    @if(!empty($variation['packaging_type_id']))
                                        @php
                                            $packaging = \App\Models\PackagingType::find($variation['packaging_type_id']);
                                        @endphp
                                        @if($packaging)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                📦 {{ $packaging->display_name }}
                                            </span>
                                        @endif
                                    @endif
                                    @if(!empty($variation['fill_weight_grams']) && $variation['fill_weight_grams'] > 0)
                                        <span>Fill: {{ $variation['fill_weight_grams'] }}g</span>
                                    @endif
                                    @if(!empty($variation['sku']))
                                        <span>SKU: {{ $variation['sku'] }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    ${{ number_format($variation['price'] ?? 0, 2) }}
                                </div>
                                @if(!empty($variation['unit']))
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        per {{ $variation['unit'] }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600 dark:text-gray-300">
                        {{ count($variations) }} variation{{ count($variations) === 1 ? '' : 's' }} total
                    </span>
                    <div class="text-gray-600 dark:text-gray-300">
                        Price range: 
                        <span class="font-medium">
                            ${{ number_format(min(array_column($variations, 'price')), 2) }} - 
                            ${{ number_format(max(array_column($variations, 'price')), 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>