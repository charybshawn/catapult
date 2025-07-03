@php
    $record = $getRecord();
    $variations = $record ? $record->variations()->get() : collect();
@endphp

<div class="space-y-4">
    @if($variations->count() > 0)
        <!-- Variations Table -->
        <div class="overflow-hidden bg-white border border-gray-300 rounded-lg shadow-sm dark:bg-gray-900 dark:border-gray-600">
            <!-- Header -->
            <div class="bg-gray-50 border-b border-gray-200 px-4 py-3 dark:bg-gray-800 dark:border-gray-600">
                <div class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <div class="flex-1 min-w-0">Size Description</div>
                    <div class="w-20 text-center">Weight (kg)</div>
                    <div class="w-24 text-center">Price</div>
                    <div class="w-20 text-center">Currency</div>
                    <div class="w-16 text-center">Available</div>
                    <div class="w-24 text-center">Price/kg</div>
                    <div class="w-20 text-center">Actions</div>
                </div>
            </div>

            <!-- Variations Rows -->
            <div class="divide-y divide-gray-200 dark:divide-gray-600">
                @foreach($variations as $variation)
                    <div class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center gap-3 text-sm">
                            <!-- Size Description -->
                            <div class="flex-1 min-w-0">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $variation->size }}
                                </span>
                                @if($variation->sku)
                                    <span class="text-gray-500 text-xs block">SKU: {{ $variation->sku }}</span>
                                @endif
                            </div>

                            <!-- Weight -->
                            <div class="w-20 text-center text-gray-700 dark:text-gray-300">
                                {{ $variation->weight_kg ? number_format($variation->weight_kg, 4) : 'N/A' }}
                            </div>

                            <!-- Price -->
                            <div class="w-24 text-center">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    ${{ number_format($variation->current_price, 2) }}
                                </span>
                            </div>

                            <!-- Currency -->
                            <div class="w-20 text-center text-gray-600 dark:text-gray-400">
                                {{ $variation->currency }}
                            </div>

                            <!-- Availability -->
                            <div class="w-16 text-center">
                                @if($variation->is_available)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Available
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Out of Stock
                                    </span>
                                @endif
                            </div>

                            <!-- Price per kg -->
                            <div class="w-24 text-center text-gray-600 dark:text-gray-400">
                                @if($variation->weight_kg && $variation->weight_kg > 0)
                                    ${{ number_format($variation->current_price / $variation->weight_kg, 2) }}/kg
                                @else
                                    N/A
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="w-20 text-center">
                                <button 
                                    wire:click="editVariation({{ $variation->id }})"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200"
                                    title="Edit variation"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Footer with summary -->
            <div class="bg-gray-50 border-t border-gray-200 px-4 py-3 dark:bg-gray-800 dark:border-gray-600">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">
                        {{ $variations->count() }} variation{{ $variations->count() !== 1 ? 's' : '' }} total
                    </span>
                    <div class="flex gap-4 text-xs text-gray-500 dark:text-gray-500">
                        <span>{{ $variations->where('is_available', true)->count() }} available</span>
                        <span>{{ $variations->where('is_available', false)->count() }} out of stock</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex gap-3">
            <button 
                wire:click="addVariation"
                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Add Variation
            </button>
            
            @if($variations->count() > 0)
                <button 
                    wire:click="bulkEditVariations"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:bg-gray-600"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Bulk Edit
                </button>
            @endif
        </div>

    @else
        <!-- Empty State -->
        <div class="text-center py-12 bg-gray-50 border border-gray-200 rounded-lg dark:bg-gray-800 dark:border-gray-600">
            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No variations yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Get started by creating your first size and pricing variation.
            </p>
            <div class="mt-6">
                <button 
                    wire:click="addVariation"
                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create First Variation
                </button>
            </div>
        </div>
    @endif
</div>

<script>
// Listen for scroll to variations event
document.addEventListener('livewire:init', function () {
    Livewire.on('scrollToVariations', function () {
        // Find the variations section and scroll to it
        const variationsSection = document.querySelector('[data-field-wrapper="seed_variations_panel"]');
        if (variationsSection) {
            variationsSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
            
            // Add a subtle highlight effect
            variationsSection.style.backgroundColor = '#fef3c7';
            setTimeout(() => {
                variationsSection.style.backgroundColor = '';
            }, 2000);
        }
    });
});
</script>

<style>
/* Ensure proper column alignment */
.seed-variations-table .flex {
    align-items: center;
}

.seed-variations-table .w-16,
.seed-variations-table .w-20,
.seed-variations-table .w-24 {
    flex-shrink: 0;
}

.seed-variations-table .flex-1 {
    min-width: 0;
    flex: 1 1 0%;
}
</style>