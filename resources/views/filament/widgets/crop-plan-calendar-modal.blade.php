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

    {{-- Order Breakdown Section --}}
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Order Breakdown</h3>
        
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($individualPlans as $plan)
                    <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                        Order #{{ $plan['order_id'] ?? 'N/A' }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $plan['customer'] ?? 'Unknown Customer' }}
                                    </span>
                                </div>
                                
                                <div class="mt-2 flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                        </svg>
                                        {{ number_format($plan['grams_needed'] ?? 0, 1) }}g
                                    </span>
                                    
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                        {{ $plan['trays_needed'] ?? 0 }} {{ ($plan['trays_needed'] ?? 0) == 1 ? 'tray' : 'trays' }}
                                    </span>
                                    
                                    @if(isset($plan['days_to_maturity']) && $plan['days_to_maturity'])
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ number_format($plan['days_to_maturity'], 1) }} days DTM
                                        </span>
                                    @endif
                                </div>
                                
                                {{-- Timeline for individual plan --}}
                                @if(isset($plan['plant_date']) || isset($plan['harvest_date']))
                                    <div class="mt-2 flex items-center space-x-3 text-xs text-gray-500 dark:text-gray-500">
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
                                    </div>
                                @endif
                            </div>
                            
                            <div class="text-right">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if(strtolower($plan['status'] ?? '') === 'draft') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @elseif(strtolower($plan['status'] ?? '') === 'confirmed') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif(strtolower($plan['status'] ?? '') === 'completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                    @endif">
                                    {{ $plan['status'] ?? 'Unknown' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                        No individual plans found
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>