<x-filament-panels::page>
    <div class="space-y-8">
        <!-- Header Section -->
        <div class="text-center py-4">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Daily Operations Center</h2>
            <p class="text-gray-600 dark:text-gray-400">Quick access to your most common daily tasks</p>
        </div>

        <!-- Quick Stats Bar -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-orange-600">{{ $stats['crops_to_advance'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Crops to Advance</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['todays_alerts'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Today's Alerts</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-red-600">{{ $stats['low_stock_items'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Low Stock Items</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['pending_orders'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Pending Orders</div>
            </div>
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-4 text-center">
                <div class="text-2xl font-bold text-green-600">{{ $stats['crops_ready_to_harvest'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">Ready to Harvest</div>
            </div>
        </div>

        <!-- Task Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
            @foreach($quickActions as $sectionKey => $section)
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <!-- Section Header -->
                    <div class="bg-{{ $section['color'] }}-50 dark:bg-{{ $section['color'] }}-900/20 border-b border-{{ $section['color'] }}-200 dark:border-{{ $section['color'] }}-800 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <x-dynamic-component :component="'heroicon-o-' . str_replace('heroicon-o-', '', $section['icon'])" 
                                class="w-6 h-6 text-{{ $section['color'] }}-600 dark:text-{{ $section['color'] }}-400" />
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $section['title'] }}</h3>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="p-6 space-y-3">
                        @foreach($section['actions'] as $action)
                            <a href="{{ $action['url'] }}" 
                               class="group block w-full bg-gray-50 dark:bg-gray-800 hover:bg-{{ $action['color'] }}-50 dark:hover:bg-{{ $action['color'] }}-900/20 
                                      border border-gray-200 dark:border-gray-700 hover:border-{{ $action['color'] }}-300 dark:hover:border-{{ $action['color'] }}-700 
                                      rounded-lg p-4 transition-all duration-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900/30 
                                                    rounded-lg flex items-center justify-center group-hover:bg-{{ $action['color'] }}-200 
                                                    dark:group-hover:bg-{{ $action['color'] }}-800/40 transition-colors">
                                            <x-dynamic-component :component="'heroicon-o-' . str_replace('heroicon-o-', '', $action['icon'])" 
                                                class="w-5 h-5 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400" />
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white group-hover:text-{{ $action['color'] }}-600 
                                                        dark:group-hover:text-{{ $action['color'] }}-400 transition-colors">
                                                {{ $action['label'] }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $action['description'] }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if(isset($action['badge']) && $action['badge'] > 0)
                                            <span class="inline-flex items-center justify-center min-w-[1.5rem] h-6 px-2 
                                                       bg-{{ $action['color'] }}-100 dark:bg-{{ $action['color'] }}-900/30 
                                                       text-{{ $action['color'] }}-700 dark:text-{{ $action['color'] }}-300 
                                                       text-xs font-semibold rounded-full">
                                                {{ $action['badge'] }}
                                            </span>
                                        @endif
                                        <x-heroicon-o-chevron-right class="w-5 h-5 text-gray-400 group-hover:text-{{ $action['color'] }}-600 
                                                                          dark:group-hover:text-{{ $action['color'] }}-400 transition-colors" />
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Time-based Quick Actions -->
        <div class="bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 
                    rounded-xl border border-primary-200 dark:border-primary-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                Time-Sensitive Actions
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('filament.admin.resources.crop-alerts.index', ['tableFilters[priority][value]' => 'overdue']) }}" 
                   class="bg-white dark:bg-gray-900 rounded-lg border border-primary-200 dark:border-primary-700 p-4 
                          hover:shadow-md transition-all text-center group">
                    <x-heroicon-o-exclamation-circle class="w-8 h-8 text-red-600 mx-auto mb-2" />
                    <div class="font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        Check Overdue Tasks
                    </div>
                </a>
                <a href="{{ route('filament.admin.resources.crops.index', ['tableFilters[ready_to_advance][value]' => '1']) }}" 
                   class="bg-white dark:bg-gray-900 rounded-lg border border-primary-200 dark:border-primary-700 p-4 
                          hover:shadow-md transition-all text-center group">
                    <x-heroicon-o-arrow-right-circle class="w-8 h-8 text-orange-600 mx-auto mb-2" />
                    <div class="font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        Advance Crop Stages
                    </div>
                </a>
                <a href="{{ route('filament.admin.resources.orders.index', ['tableFilters[harvest_date][value]' => 'today']) }}" 
                   class="bg-white dark:bg-gray-900 rounded-lg border border-primary-200 dark:border-primary-700 p-4 
                          hover:shadow-md transition-all text-center group">
                    <x-heroicon-o-calendar class="w-8 h-8 text-green-600 mx-auto mb-2" />
                    <div class="font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        Today's Deliveries
                    </div>
                </a>
                <a href="{{ route('filament.admin.resources.consumables.index', ['tableFilters[critical_stock][value]' => '1']) }}" 
                   class="bg-white dark:bg-gray-900 rounded-lg border border-primary-200 dark:border-primary-700 p-4 
                          hover:shadow-md transition-all text-center group">
                    <x-heroicon-o-shield-exclamation class="w-8 h-8 text-purple-600 mx-auto mb-2" />
                    <div class="font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                        Critical Stock Levels
                    </div>
                </a>
            </div>
        </div>

        <!-- Footer Tips -->
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 py-4">
            <p>💡 Tip: Use keyboard shortcuts for faster navigation. Press <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">?</kbd> to see available shortcuts.</p>
        </div>
    </div>
</x-filament-panels::page>