<div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
    <!-- Header -->
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Lot Status Overview</h3>
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Last updated: {{ $last_updated->format('M j, Y g:i A') }}
            </div>
        </div>
    </div>
    
    <!-- Statistics Overview -->
    <div class="p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <!-- Total Lots -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $statistics['total_lots'] }}</div>
                <div class="text-xs text-blue-600 dark:text-blue-400 font-medium">Total Lots</div>
            </div>
            
            <!-- Active Lots -->
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $statistics['active_lots'] }}</div>
                <div class="text-xs text-green-600 dark:text-green-400 font-medium">Active Lots</div>
            </div>
            
            <!-- Low Stock Lots -->
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ $statistics['low_stock_lots'] }}</div>
                <div class="text-xs text-yellow-600 dark:text-yellow-400 font-medium">Low Stock</div>
            </div>
            
            <!-- Depleted Lots -->
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-3 text-center">
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $statistics['depleted_lots'] }}</div>
                <div class="text-xs text-red-600 dark:text-red-400 font-medium">Depleted</div>
            </div>
        </div>
        
        @if(!empty($critical_alerts))
            <!-- Critical Alerts Section -->
            <div class="mb-6">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Critical Alerts</h4>
                <div class="space-y-2">
                    @foreach($critical_alerts as $alert)
                        <div class="flex items-start space-x-3 p-3 rounded-lg border 
                            @if($alert['type'] === 'critical') 
                                bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 
                            @else 
                                bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 
                            @endif">
                            <div class="flex-shrink-0 pt-0.5">
                                @if($alert['type'] === 'critical')
                                    <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                @else
                                    <div class="w-2 h-2 bg-yellow-500 rounded-full"></div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-medium 
                                        @if($alert['type'] === 'critical') 
                                            text-red-800 dark:text-red-200 
                                        @else 
                                            text-yellow-800 dark:text-yellow-200 
                                        @endif">
                                        {{ $alert['title'] }}
                                    </p>
                                    <span class="text-xs 
                                        @if($alert['type'] === 'critical') 
                                            text-red-600 dark:text-red-400 
                                        @else 
                                            text-yellow-600 dark:text-yellow-400 
                                        @endif">
                                        {{ $alert['lot_number'] }}
                                    </span>
                                </div>
                                <p class="text-xs mt-1 
                                    @if($alert['type'] === 'critical') 
                                        text-red-700 dark:text-red-300 
                                    @else 
                                        text-yellow-700 dark:text-yellow-300 
                                    @endif">
                                    {{ $alert['message'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        @if(!empty($low_stock_lots))
            <!-- Low Stock Details -->
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Low Stock Details</h4>
                <div class="space-y-3">
                    @foreach($low_stock_lots as $lot)
                        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="font-medium text-gray-900 dark:text-gray-100">Lot {{ $lot['lot_number'] }}</h5>
                                <span class="text-xs font-medium px-2 py-1 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200">
                                    {{ number_format($lot['available_percentage'], 1) }}% left
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Total:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($lot['total'], 1) }}g</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Available:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($lot['available'], 1) }}g</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Used:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($lot['consumed'], 1) }}g</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Entries:</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $lot['entry_count'] }}</span>
                                </div>
                            </div>
                            
                            <!-- Progress bar -->
                            <div class="mt-2">
                                <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-yellow-500 h-2 rounded-full" 
                                         style="width: {{ $lot['available_percentage'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        @if(empty($critical_alerts) && empty($low_stock_lots))
            <!-- All Good Message -->
            <div class="text-center py-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-full mb-4">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">All Lots Are In Good Shape</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">No critical alerts or low stock warnings at this time.</p>
            </div>
        @endif
        
        <!-- Action Buttons -->
        <div class="flex justify-center space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="/admin/recipes" 
               class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-md transition-colors">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                View Recipes
            </a>
            
            <a href="/admin/consumables" 
               class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded-md transition-colors">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                View Inventory
            </a>
        </div>
    </div>
</div>