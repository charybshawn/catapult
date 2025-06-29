<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Smart Purchase Recommendations</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            @if($selectedCommonName)
                Recommendations for <strong>{{ $selectedCommonName }}</strong>
                @if(!empty($selectedCultivars))
                    - <strong>{{ implode(', ', $selectedCultivars) }}</strong>
                @endif
            @else
                Select filters below to see recommendations
            @endif
        </p>
    </div>
    
    <div class="p-3">
        @if($recommendations->isNotEmpty())
            <!-- 3 Column Horizontal Layout -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Column 1: Top Recommendation -->
                <div class="flex flex-col">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3">🏆 Top Choice</h4>
                    @php $topChoice = $recommendations->sortByDesc('intelligence_score')->first(); @endphp
                    @if($topChoice)
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3 flex-1">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                    {{ $topChoice->cultivar_name }}
                                </h5>
                                <span class="text-xs font-medium text-green-600">
                                    {{ number_format($topChoice->intelligence_score, 0) }}%
                                </span>
                            </div>
                            
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                <strong>{{ $topChoice->supplier_name }}</strong>
                            </div>
                            
                            <div class="space-y-1 text-xs">
                                <div>Weight: <span class="font-medium">{{ number_format($topChoice->weight_kg, 1) }}kg</span></div>
                                <div>Total: <span class="font-medium">{{ $topChoice->currency_symbol }}{{ number_format($topChoice->display_price, 0) }} {{ $topChoice->display_currency }}</span></div>
                                <div>Per KG: <span class="font-medium">{{ $topChoice->currency_symbol }}{{ number_format($topChoice->price_per_kg, 0) }} {{ $topChoice->display_currency }}</span></div>
                            </div>
                            
                            @if($topChoice->url)
                                <div class="mt-2">
                                    <a href="{{ $topChoice->url }}" target="_blank"
                                       class="inline-flex items-center px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded">
                                        View Product
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700 p-3 text-center flex-1">
                            <p class="text-xs text-gray-500">No recommendations available</p>
                        </div>
                    @endif
                </div>
                
                <!-- Column 2: Best Value -->
                <div class="flex flex-col">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3">💰 Best Value</h4>
                    @php 
                        // Find best value option - lowest price per kg that's different from top choice
                        $bestValue = $recommendations->reject(fn($r) => $r->id === $topChoice?->id)
                                                    ->sortBy('price_per_kg')->first();
                        
                        // If no different option, try best bulk buy
                        if (!$bestValue) {
                            $bestValue = $recommendations->where('recommendation_type', 'Best Bulk Buy')->first();
                        }
                    @endphp
                    @if($bestValue)
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3 flex-1">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                    {{ $bestValue->cultivar_name }}
                                </h5>
                                <span class="text-xs font-medium text-blue-600">
                                    {{ number_format($bestValue->intelligence_score, 0) }}%
                                </span>
                            </div>
                            
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                <strong>{{ $bestValue->supplier_name }}</strong>
                            </div>
                            
                            <div class="space-y-1 text-xs">
                                <div>Weight: <span class="font-medium">{{ number_format($bestValue->weight_kg, 1) }}kg</span></div>
                                <div>Total: <span class="font-medium">{{ $bestValue->currency_symbol }}{{ number_format($bestValue->display_price, 0) }} {{ $bestValue->display_currency }}</span></div>
                                <div>Per KG: <span class="font-medium">{{ $bestValue->currency_symbol }}{{ number_format($bestValue->price_per_kg, 0) }} {{ $bestValue->display_currency }}</span></div>
                            </div>
                            
                            @if($bestValue->url)
                                <div class="mt-2">
                                    <a href="{{ $bestValue->url }}" target="_blank"
                                       class="inline-flex items-center px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded">
                                        View Product
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700 p-3 text-center flex-1">
                            <p class="text-xs text-gray-500">No value options available</p>
                        </div>
                    @endif
                </div>
                
                <!-- Column 3: Alternative -->
                <div class="flex flex-col">
                    <h4 class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-3">🔄 Alternative</h4>
                    @php 
                        $alternative = $recommendations->reject(function($r) use ($topChoice, $bestValue) {
                            return $r->id === $topChoice?->id || $r->id === $bestValue?->id;
                        })->first();
                    @endphp
                    @if($alternative)
                        <div class="border border-gray-200 dark:border-gray-700 rounded p-3 flex-1">
                            <div class="flex justify-between items-start mb-2">
                                <h5 class="font-semibold text-gray-900 dark:text-gray-100 text-sm">
                                    {{ $alternative->cultivar_name }}
                                </h5>
                                <span class="text-xs font-medium text-orange-600">
                                    {{ number_format($alternative->intelligence_score, 0) }}%
                                </span>
                            </div>
                            
                            <div class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                <strong>{{ $alternative->supplier_name }}</strong>
                            </div>
                            
                            <div class="space-y-1 text-xs">
                                <div>Weight: <span class="font-medium">{{ number_format($alternative->weight_kg, 1) }}kg</span></div>
                                <div>Total: <span class="font-medium">{{ $alternative->currency_symbol }}{{ number_format($alternative->display_price, 0) }} {{ $alternative->display_currency }}</span></div>
                                <div>Per KG: <span class="font-medium">{{ $alternative->currency_symbol }}{{ number_format($alternative->price_per_kg, 0) }} {{ $alternative->display_currency }}</span></div>
                            </div>
                            
                            @if($alternative->url)
                                <div class="mt-2">
                                    <a href="{{ $alternative->url }}" target="_blank"
                                       class="inline-flex items-center px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded">
                                        View Product
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="bg-gray-50 dark:bg-gray-900 rounded border border-gray-200 dark:border-gray-700 p-3 text-center flex-1">
                            <p class="text-xs text-gray-500">No alternatives available</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="text-center py-4">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    @if($selectedCommonName)
                        No seed variations found for {{ $selectedCommonName }}
                        @if(!empty($selectedCultivars))
                            - {{ implode(', ', $selectedCultivars) }}
                        @endif
                        . Try selecting different cultivars or seed type.
                    @else
                        Select filters below to see intelligent purchase recommendations.
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>