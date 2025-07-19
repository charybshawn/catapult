<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 
                    rounded-xl border border-blue-200 dark:border-blue-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Active Soaking Monitor</h2>
                    <p class="text-gray-600 dark:text-gray-400">
                        Track crops currently in the soaking stage
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Last updated:</div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $last_updated }}</div>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-beaker class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $total_crops }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Trays</div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $on_time_crops }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">On Time</div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                    <div class="ml-4">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $overdue_crops }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Overdue</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Crops Section -->
        @if($crops->isEmpty())
            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 p-12 text-center">
                <x-heroicon-o-beaker class="w-16 h-16 text-gray-400 mx-auto mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">No Active Soaking</h3>
                <p class="text-gray-600 dark:text-gray-400">No crops are currently in the soaking stage.</p>
            </div>
        @else
            <!-- Overdue Batches Section -->
            @if($overdue_batches->isNotEmpty())
                <div class="space-y-4">
                    <div class="flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" />
                        <h3 class="text-lg font-semibold text-red-600 dark:text-red-400">Overdue Batches ({{ $overdue_batches->count() }})</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($overdue_batches as $batch)
                            <div class="bg-white dark:bg-gray-900 rounded-lg border-2 border-red-300 dark:border-red-700 overflow-hidden bg-red-50 dark:bg-red-900/10">
                                <!-- Card Header -->
                        <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $batch->recipe_name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $batch->variety_name }}</p>
                                </div>
                                <div class="text-right">
                                    <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                @if($batch->is_overdue) 
                                                    bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                                @else 
                                                    bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                                @endif">
                                        @if($batch->is_overdue)
                                            <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1" />
                                            Overdue
                                        @else
                                            <x-heroicon-o-check-circle class="w-3 h-3 mr-1" />
                                            On Time
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="px-6 py-4 space-y-4">
                            <!-- Batch Information -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <x-heroicon-o-rectangle-stack class="w-4 h-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Batch Size:</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $batch->tray_count }} tray{{ $batch->tray_count !== 1 ? 's' : '' }}
                                </span>
                            </div>

                            <!-- Tray Numbers -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <x-heroicon-o-list-bullet class="w-4 h-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Trays:</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white text-right">
                                    {{ $batch->tray_numbers_formatted }}
                                </span>
                            </div>

                            <!-- Seed Quantity -->
                            @if($batch->total_seed_quantity > 0)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <x-heroicon-o-beaker class="w-4 h-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Seed Quantity:</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $batch->total_seed_quantity }}g total
                                    @if($batch->seed_quantity_per_tray > 0)
                                        <br><span class="text-xs text-gray-500">({{ $batch->seed_quantity_per_tray }}g per tray)</span>
                                    @endif
                                </span>
                            </div>
                            @endif

                            <!-- Soaking Start Time -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Started:</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $batch->formatted_start_time }}</span>
                            </div>

                            <!-- Elapsed Time -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Elapsed:</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $batch->formatted_elapsed_time }}</span>
                            </div>

                            <!-- Time Remaining -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Remaining:</span>
                                </div>
                                <span class="text-sm font-medium 
                                           @if($batch->is_overdue) text-red-600 dark:text-red-400 @else text-gray-900 dark:text-white @endif">
                                    {{ $batch->formatted_remaining_time }}
                                </span>
                            </div>

                            <!-- Total Duration -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <x-heroicon-o-circle-stack class="w-4 h-4 text-gray-400 mr-2" />
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Total Duration:</span>
                                </div>
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $batch->formatted_total_duration }}</span>
                            </div>

                            <!-- Progress Bar -->
                            <div class="pt-2">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Progress</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($batch->progress_percentage, 1) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="h-2 rounded-full transition-all duration-300 
                                               @if($batch->is_overdue) bg-red-500 @else bg-blue-500 @endif" 
                                         style="width: {{ min(100, $batch->progress_percentage) }}%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="bg-gray-50 dark:bg-gray-800 px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Batch ID: {{ $batch->batch_id }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Auto-refresh: 5 min
                                </div>
                            </div>
                        </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- On-Time Batches Section -->
            @if($on_time_batches->isNotEmpty())
                <div class="space-y-4">
                    <div class="flex items-center">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" />
                        <h3 class="text-lg font-semibold text-green-600 dark:text-green-400">On-Time Batches ({{ $on_time_batches->count() }})</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($on_time_batches as $batch)
                            <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                                <!-- Card Header -->
                                <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $batch->recipe_name }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $batch->variety_name }}</p>
                                        </div>
                                        <div class="text-right">
                                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                <x-heroicon-o-check-circle class="w-3 h-3 mr-1" />
                                                On Time
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Body -->
                                <div class="px-6 py-4 space-y-4">
                                    <!-- Batch Information -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <x-heroicon-o-rectangle-stack class="w-4 h-4 text-gray-400 mr-2" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Batch Size:</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $batch->tray_count }} tray{{ $batch->tray_count !== 1 ? 's' : '' }}
                                        </span>
                                    </div>

                                    <!-- Tray Numbers -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <x-heroicon-o-list-bullet class="w-4 h-4 text-gray-400 mr-2" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Trays:</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white text-right">
                                            {{ $batch->tray_numbers_formatted }}
                                        </span>
                                    </div>

                                    <!-- Seed Quantity -->
                                    @if($batch->total_seed_quantity > 0)
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <x-heroicon-o-beaker class="w-4 h-4 text-gray-400 mr-2" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Seed Quantity:</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $batch->total_seed_quantity }}g total
                                            @if($batch->seed_quantity_per_tray > 0)
                                                <br><span class="text-xs text-gray-500">({{ $batch->seed_quantity_per_tray }}g per tray)</span>
                                            @endif
                                        </span>
                                    </div>
                                    @endif

                                    <!-- Soaking Start Time -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Started:</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $batch->formatted_start_time }}</span>
                                    </div>

                                    <!-- Elapsed Time -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-400 mr-2" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Elapsed:</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $batch->formatted_elapsed_time }}</span>
                                    </div>

                                    <!-- Time Remaining -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Remaining:</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $batch->formatted_remaining_time }}
                                        </span>
                                    </div>

                                    <!-- Total Duration -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <x-heroicon-o-circle-stack class="w-4 h-4 text-gray-400 mr-2" />
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Duration:</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $batch->formatted_total_duration }}</span>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div class="pt-2">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm text-gray-600 dark:text-gray-400">Progress</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ number_format($batch->progress_percentage, 1) }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-300 bg-blue-500" 
                                                 style="width: {{ min(100, $batch->progress_percentage) }}%"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Footer -->
                                <div class="bg-gray-50 dark:bg-gray-800 px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Batch ID: {{ $batch->batch_id }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            Auto-refresh: 5 min
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif

        <!-- Help Section -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
                <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" />
                About Soaking Stage
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600 dark:text-gray-400">
                <div>
                    <p><strong>Purpose:</strong> The soaking stage hydrates seeds to initiate germination, improving sprouting rates and uniformity.</p>
                </div>
                <div>
                    <p><strong>Duration:</strong> Varies by recipe, typically 4-12 hours depending on seed type and variety.</p>
                </div>
                <div>
                    <p><strong>Overdue Action:</strong> Seeds soaked too long may start germinating prematurely or develop issues.</p>
                </div>
                <div>
                    <p><strong>Next Step:</strong> After soaking, seeds should be planted in trays to begin the germination stage.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Auto-refresh using Livewire polling -->
    <div wire:poll.300s></div>
</x-filament-panels::page>