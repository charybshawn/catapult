<!-- Crop Details Slideout Modal -->
<div x-show="showCropDetails" 
     class="fixed inset-0 z-50"
     x-transition.opacity
     style="display: none;">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" 
         @click="showCropDetails = false"></div>
    
    <!-- Slideout Panel -->
    <div class="fixed top-0 right-0 h-full w-96 bg-white dark:bg-gray-900 shadow-xl overflow-hidden z-60"
         style="transform: translateX(0);"
         x-transition:enter="transition-transform ease-in-out duration-300"
         x-transition:enter-start="transform translate-x-full"
         x-transition:enter-end="transform translate-x-0"
         x-transition:leave="transition-transform ease-in-out duration-300"
         x-transition:leave-start="transform translate-x-0"
         x-transition:leave-end="transform translate-x-full"
         @click.stop>
        <div class="p-6 h-full overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold">Crop Details</h3>
                <button @click="showCropDetails = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <template x-if="cropData">
                <div class="space-y-6">
                    <div>
                        <h4 class="font-semibold text-xl text-gray-900 dark:text-white mb-2" x-text="cropData.variety"></h4>
                        <p class="text-gray-600 dark:text-gray-400" x-text="cropData.recipe_name"></p>
                    </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Status</div>
                        <x-filament::badge x-bind:color="cropData.stage_color || 'gray'" x-text="cropData.current_stage_name"></x-filament::badge>
                    </div>
                    <div>
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Tray Count</div>
                        <div class="text-gray-900 dark:text-white font-medium" x-text="cropData.tray_count"></div>
                    </div>
                </div>
                
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Time in Stage</div>
                    <div class="text-gray-900 dark:text-white" x-text="cropData.stage_age_display"></div>
                </div>
                
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Time to Next Stage</div>
                    <div class="text-gray-900 dark:text-white" x-text="cropData.time_to_next_stage_display"></div>
                </div>
                
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Total Age</div>
                    <div class="text-gray-900 dark:text-white" x-text="cropData.total_age_display"></div>
                </div>
                
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Planted Date</div>
                    <div class="text-gray-900 dark:text-white" x-text="cropData.planting_at_formatted"></div>
                </div>
                
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Expected Harvest</div>
                    <div class="text-gray-900 dark:text-white" x-text="cropData.expected_harvest_at_formatted || 'Not calculated'"></div>
                </div>
                
                <!-- Stage Timeline -->
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Stage Timeline</div>
                    <div class="space-y-2">
                        <template x-for="(timing, stage) in cropData.stage_timings" :key="stage">
                            <div class="flex items-center justify-between py-1 px-2 rounded"
                                 :class="timing.status === 'current' ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-800'">
                                <div class="flex items-center gap-2">
                                    <x-filament::badge 
                                        x-bind:color="timing.status === 'current' ? 'primary' : 'success'" 
                                        size="xs"
                                        x-text="stage.charAt(0).toUpperCase() + stage.slice(1)">
                                    </x-filament::badge>
                                    <span x-show="timing.status === 'current'" class="text-xs text-blue-600 dark:text-blue-400 font-medium">Current</span>
                                </div>
                                <div class="text-xs text-gray-600 dark:text-gray-400" x-text="timing.duration"></div>
                            </div>
                        </template>
                    </div>
                </div>
                
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Tray Numbers</div>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="tray in cropData.tray_numbers_array" :key="tray">
                            <x-filament::badge color="gray" size="xs" x-text="tray"></x-filament::badge>
                        </template>
                    </div>
                </div>
                
                <div class="pt-4 border-t">
                    <div class="space-y-3">
                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <button type="button"
                                    x-data
                                    @click.prevent.stop="
                                        console.log('Advance stage clicked for crop:', cropData.id); 
                                        fetch('/admin/crops/' + cropData.id + '/advance-stage', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': $el.closest('[data-csrf]')?.dataset.csrf || document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                                                    'Content-Type': 'application/json',
                                                },
                                                body: JSON.stringify({})
                                            })
                                            .then(response => {
                                                if (!response.ok) {
                                                    throw new Error('Network response was not ok');
                                                }
                                                return response.json();
                                            })
                                            .then(data => {
                                                console.log('Response:', data);
                                                if (data.success) {
                                                    console.log('Stage advanced successfully:', data.message);
                                                    showCropDetails = false; // Close the modal
                                                    setTimeout(() => { 
                                                        window.location.reload(); // Refresh to show updated data
                                                    }, 500);
                                                } else {
                                                    console.log('Stage advancement failed:', data.message);
                                                    alert('Failed to advance stage: ' + data.message);
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error:', error);
                                                alert('Error advancing stage: ' + error.message);
                                            });
                                    "
                                    class="flex-1 text-center py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                                    x-show="cropData.can_advance">
                                Advance Stage
                            </button>
                            <button type="button"
                                    x-data
                                    @click.prevent.stop="
                                        console.log('Rollback stage clicked for crop:', cropData.id); 
                                        fetch('/admin/crops/' + cropData.id + '/rollback-stage', {
                                                method: 'POST',
                                                headers: {
                                                    'X-CSRF-TOKEN': $el.closest('[data-csrf]')?.dataset.csrf || document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}',
                                                    'Content-Type': 'application/json',
                                                },
                                                body: JSON.stringify({})
                                            })
                                            .then(response => {
                                                if (!response.ok) {
                                                    throw new Error('Network response was not ok');
                                                }
                                                return response.json();
                                            })
                                            .then(data => {
                                                console.log('Response:', data);
                                                if (data.success) {
                                                    console.log('Stage rolled back successfully:', data.message);
                                                    showCropDetails = false; // Close the modal
                                                    setTimeout(() => { 
                                                        window.location.reload(); // Refresh to show updated data
                                                    }, 500);
                                                } else {
                                                    console.log('Stage rollback failed:', data.message);
                                                    alert('Failed to rollback stage: ' + data.message);
                                                }
                                            })
                                            .catch(error => {
                                                console.error('Error:', error);
                                                alert('Error rolling back stage: ' + error.message);
                                            });
                                    "
                                    class="flex-1 text-center py-2 px-4 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors"
                                    x-show="cropData.can_rollback">
                                Rollback Stage
                            </button>
                        </div>
                        
                        <!-- Other Links -->
                        <div class="flex gap-3">
                            <a x-bind:href="'/admin/crops/' + cropData.id + '/edit'" 
                               class="flex-1 text-center py-2 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Edit Crop
                            </a>
                            <a href="/admin/crops" 
                               class="flex-1 text-center py-2 px-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                                View All Crops
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>