<div class="space-y-6">
    {{-- Summary Section --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Summary</h3>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Total Orders:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $totalOrders }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Total Grams:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($totalGrams, 1) }}g</span>
                </div>
            </div>
            
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Total Trays:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $totalTrays }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        @if(strtolower($status) === 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @elseif(strtolower($status) === 'confirmed') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @elseif(strtolower($status) === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                        @endif">
                        {{ $status }}
                    </span>
                </div>
            </div>
        </div>
        
        @if($isSeedSoak)
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    Seed Soak Required
                </span>
            </div>
        @endif
    </div>

    {{-- Timeline Overview Section --}}
    @php
        $firstPlan = collect($individualPlans)->first();
        $plantDate = $firstPlan['plant_date'] ?? null;
        $harvestDate = $firstPlan['harvest_date'] ?? null;
        $seedSoakDate = $firstPlan['seed_soak_date'] ?? null;
        $daysToMaturity = $firstPlan['days_to_maturity'] ?? null;
    @endphp
    
    @if($plantDate || $harvestDate || $daysToMaturity)
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Timeline Overview</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if($seedSoakDate)
                <div class="text-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Seed Soak Date</div>
                    <div class="font-medium text-blue-600 dark:text-blue-400">
                        {{ \Carbon\Carbon::parse($seedSoakDate)->format('M j, Y') }}
                    </div>
                </div>
            @endif
            
            @if($plantDate)
                <div class="text-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Plant Date</div>
                    <div class="font-medium text-green-600 dark:text-green-400">
                        {{ \Carbon\Carbon::parse($plantDate)->format('M j, Y') }}
                    </div>
                </div>
            @endif
            
            @if($harvestDate)
                <div class="text-center">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Expected Harvest</div>
                    <div class="font-medium text-orange-600 dark:text-orange-400">
                        {{ \Carbon\Carbon::parse($harvestDate)->format('M j, Y') }}
                    </div>
                </div>
            @endif
        </div>
        
        @if($daysToMaturity)
            <div class="mt-3 pt-3 border-t border-blue-200 dark:border-blue-700 text-center">
                <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">Days to Maturity</div>
                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200">
                    {{ number_format($daysToMaturity, 1) }} days
                </div>
            </div>
        @endif
    </div>
    @endif

    {{-- Consolidated Order Requirements Section --}}
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
            Consolidated Order Requirements
        </h3>
        
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($individualPlans as $plan)
                    @php
                        $sourceOrders = $plan['source_orders'] ?? [];
                        $planHasMultipleOrders = count($sourceOrders) > 1;
                    @endphp
                    
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="space-y-3">
                            {{-- Plan Summary Header --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ $variety }} Plan #{{ $plan['id'] ?? 'N/A' }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ number_format($plan['grams_needed'] ?? 0, 1) }}g total
                                        ({{ $plan['trays_needed'] ?? 0 }} {{ ($plan['trays_needed'] ?? 0) == 1 ? 'tray' : 'trays' }})
                                    </span>
                                </div>
                                
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if(strtolower($plan['status'] ?? '') === 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif(strtolower($plan['status'] ?? '') === 'confirmed') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif(strtolower($plan['status'] ?? '') === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                    @endif">
                                    {{ $plan['status'] ?? 'Unknown' }}
                                </span>
                            </div>

                            {{-- Source Orders Breakdown --}}
                            @if($planHasMultipleOrders)
                                <div class="pl-4 border-l-2 border-gray-200 dark:border-gray-700">
                                    <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-2">
                                        Breakdown by Order & Product:
                                    </h4>
                                    <div class="space-y-3">
                                        @php
                                            // Group source orders by order_id to show combined products per order
                                            $groupedByOrder = collect($sourceOrders)->groupBy('order_id');
                                        @endphp
                                        
                                        @foreach($groupedByOrder as $orderId => $orderProducts)
                                            @php
                                                $firstProduct = $orderProducts->first();
                                                $totalGrams = $orderProducts->sum('grams');
                                                $hasMultipleProducts = $orderProducts->count() > 1;
                                            @endphp
                                            
                                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                                {{-- Order Header --}}
                                                <div class="flex items-center justify-between mb-2">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-200">
                                                            Order #{{ $orderId }}
                                                        </span>
                                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                                            {{ $firstProduct['customer'] ?? 'Unknown' }}
                                                        </span>
                                                    </div>
                                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                        {{ number_format($totalGrams, 1) }}g total
                                                    </span>
                                                </div>
                                                
                                                {{-- Product Breakdown for this order --}}
                                                @if($hasMultipleProducts)
                                                    <div class="pl-3 space-y-1">
                                                        @foreach($orderProducts as $product)
                                                            <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                                                                <span class="flex items-center">
                                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                                    </svg>
                                                                    {{ $product['product'] ?? 'Unknown Product' }}
                                                                </span>
                                                                <span class="font-medium">{{ number_format($product['grams'] ?? 0, 1) }}g</span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="text-xs text-gray-500 dark:text-gray-500 pl-3">
                                                        via {{ $firstProduct['product'] ?? 'Unknown Product' }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                {{-- Single order - show simplified view --}}
                                @php $sourceOrder = $sourceOrders[0] ?? []; @endphp
                                <div class="flex items-center space-x-3 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        Order #{{ $sourceOrder['order_id'] ?? 'N/A' }}
                                    </span>
                                    <span>{{ $sourceOrder['customer'] ?? 'Unknown Customer' }}</span>
                                    <span class="text-gray-500 dark:text-gray-500">
                                        via {{ $sourceOrder['product'] ?? 'Unknown Product' }}
                                    </span>
                                </div>
                            @endif

                            {{-- Timeline for individual plan --}}
                            @if(isset($plan['plant_date']) || isset($plan['harvest_date']))
                                <div class="flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-500">
                                    @if(isset($plan['plant_date']) && $plan['plant_date'])
                                        <span class="flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Plant: {{ \Carbon\Carbon::parse($plan['plant_date'])->format('M j') }}
                                        </span>
                                    @endif
                                    
                                    @if(isset($plan['harvest_date']) && $plan['harvest_date'])
                                        <span class="flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            Harvest: {{ \Carbon\Carbon::parse($plan['harvest_date'])->format('M j') }}
                                        </span>
                                    @endif
                                    
                                    @if(isset($plan['days_to_maturity']) && $plan['days_to_maturity'])
                                        <span class="flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ number_format($plan['days_to_maturity'], 1) }} days DTM
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                        No consolidated orders found
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>