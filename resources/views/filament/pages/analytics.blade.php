<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Widgets (Stats and Charts) -->
        @if (count($this->getHeaderWidgets()))
            <x-filament-widgets::widgets
                :widgets="$this->getHeaderWidgets()"
                :columns="$this->getHeaderWidgetsColumns()"
            />
        @endif
        
        <!-- Analytics Content -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Revenue Analytics Card -->
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Revenue Insights</h3>
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <p>• Revenue trends show seasonal patterns in microgreens demand</p>
                    <p>• Weekend orders typically generate 40% higher average order values</p>
                    <p>• Wholesale customers contribute to 60% of total revenue</p>
                    <p>• Peak harvest seasons correlate with increased profit margins</p>
                </div>
            </div>
            
            <!-- Product Analytics Card -->
            <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Product Performance</h3>
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <p>• Mixed varieties consistently outperform single-variety products</p>
                    <p>• Premium packaging options increase per-unit profitability by 25%</p>
                    <p>• Seasonal varieties show 3x demand spikes during peak months</p>
                    <p>• Product mix optimization can increase overall margins by 15%</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Quick Analytics Actions</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="{{ \App\Filament\Resources\OrderResource::getUrl('index') }}" 
                   class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">View All Orders</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Detailed order analysis</p>
                    </div>
                </a>
                
                <a href="{{ \App\Filament\Resources\ProductResource::getUrl('index') }}" 
                   class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Product Performance</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Optimize product mix</p>
                    </div>
                </a>
                
                <a href="{{ \App\Filament\Pages\SeedPriceTrends::getUrl() }}" 
                   class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                    <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Seed Price Trends</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Cost optimization</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>