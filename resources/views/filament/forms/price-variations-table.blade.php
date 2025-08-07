@php
    // Get the Livewire component and product record
    $livewire = $this;
    $product = null;
    
    if ($livewire && method_exists($livewire, 'getRecord')) {
        $product = $livewire->getRecord();
    } elseif ($livewire && property_exists($livewire, 'record')) {
        $product = $livewire->record;
    }
    
    // Get variations if we have a product - always fetch fresh from database
    $variations = collect();
    if ($product && $product->exists) {
        $variations = \App\Models\PriceVariation::where('product_id', $product->id)
            ->with('packagingType')
            ->get();
    }
@endphp

<div class="space-y-4" 
     wire:key="price-variations-table-{{ $product?->id }}"
     x-data="priceVariationsTable({{ $variations->toJson() }})"
     x-init="initializeVariations()">
    
    @if($variations->isNotEmpty())
        <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Name
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Packaging
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Pricing Unit
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Weight (g)
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Price
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Default
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Active
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($variations as $variation)
                        @php
                            // Use the stored variation name or fallback to 'Default'
                            $displayName = $variation->name ?: 'Default';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="relative">
                                    <input x-model="localVariations[{{ $loop->index }}].name"
                                           @input="markFieldDirty({{ $loop->index }}, 'name')"
                                           @blur="saveVariation({{ $loop->index }}, 'name')"
                                           @keydown.enter.prevent.stop="saveVariation({{ $loop->index }}, 'name')"
                                           type="text" 
                                           placeholder="{{ $displayName }}"
                                           x-bind:disabled="saving"
                                           x-bind:class="{
                                               'border-yellow-300': isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['name'],
                                               'border-red-300': hasError[{{ $loop->index }}] && hasError[{{ $loop->index }}]['name'],
                                               'block w-full text-sm rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100': true,
                                               'border-gray-300 dark:border-gray-600': !isDirty[{{ $loop->index }}] || !isDirty[{{ $loop->index }}]['name']
                                           }"
                                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                    
                                    <!-- Loading indicator -->
                                    <div x-show="saving" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <svg class="animate-spin h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Dirty indicator -->
                                    <div x-show="isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['name'] && !saving" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <div class="h-2 w-2 bg-orange-400 rounded-full"></div>
                                    </div>
                                </div>
                                @error('variations.'.$loop->index.'.name')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="relative">
                                    <select x-model="localVariations[{{ $loop->index }}].packaging_type_id"
                                            @change="markFieldDirty({{ $loop->index }}, 'packaging_type_id')"
                                            @blur="saveVariation({{ $loop->index }}, 'packaging_type_id')"
                                            @keydown.enter.prevent.stop="saveVariation({{ $loop->index }}, 'packaging_type_id')"
                                            x-bind:disabled="saving"
                                            x-bind:class="{
                                                'border-yellow-300': isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['packaging_type_id'],
                                                'border-red-300': hasError[{{ $loop->index }}] && hasError[{{ $loop->index }}]['packaging_type_id'],
                                                'block w-full text-sm rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100': true,
                                                'border-gray-300 dark:border-gray-600': !isDirty[{{ $loop->index }}] || !isDirty[{{ $loop->index }}]['packaging_type_id']
                                            }"
                                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                        <option value="" @if(empty($variation->packaging_type_id)) selected @endif>No packaging</option>
                                        @foreach(\App\Models\PackagingType::all() as $packaging)
                                            <option value="{{ $packaging->id }}" @if($variation->packaging_type_id == $packaging->id) selected @endif>{{ $packaging->display_name }}</option>
                                        @endforeach
                                    </select>
                                    
                                    <!-- Loading indicator -->
                                    <div x-show="saving" class="absolute inset-y-0 right-0 flex items-center pr-8">
                                        <svg class="animate-spin h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 814 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Dirty indicator -->
                                    <div x-show="isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['packaging_type_id'] && !saving" class="absolute inset-y-0 right-0 flex items-center pr-8">
                                        <div class="h-2 w-2 bg-orange-400 rounded-full"></div>
                                    </div>
                                </div>
                                @error('variations.'.$loop->index.'.packaging_type_id')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="relative">
                                    <select x-model="localVariations[{{ $loop->index }}].pricing_unit"
                                            @change="markFieldDirty({{ $loop->index }}, 'pricing_unit')"
                                            @blur="saveVariation({{ $loop->index }}, 'pricing_unit')"
                                            @keydown.enter.prevent.stop="saveVariation({{ $loop->index }}, 'pricing_unit')"
                                            x-bind:disabled="saving"
                                            x-bind:class="{
                                                'border-yellow-300': isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['pricing_unit'],
                                                'border-red-300': hasError[{{ $loop->index }}] && hasError[{{ $loop->index }}]['pricing_unit'],
                                                'block w-full text-sm rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100': true,
                                                'border-gray-300 dark:border-gray-600': !isDirty[{{ $loop->index }}] || !isDirty[{{ $loop->index }}]['pricing_unit']
                                            }"
                                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                        <option value="per_item">Per Item</option>
                                        <option value="per_tray">Per Tray</option>
                                        <option value="per_g">Per Gram</option>
                                        <option value="per_kg">Per Kg</option>
                                        <option value="per_lb">Per Lb</option>
                                        <option value="per_oz">Per Oz</option>
                                    </select>
                                    
                                    <!-- Loading indicator -->
                                    <div x-show="saving" class="absolute inset-y-0 right-0 flex items-center pr-8">
                                        <svg class="animate-spin h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Dirty indicator -->
                                    <div x-show="isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['pricing_unit'] && !saving" class="absolute inset-y-0 right-0 flex items-center pr-8">
                                        <div class="h-2 w-2 bg-orange-400 rounded-full"></div>
                                    </div>
                                </div>
                                @error('variations.'.$loop->index.'.pricing_unit')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="relative">
                                    <input x-model="localVariations[{{ $loop->index }}].fill_weight_grams"
                                           @input="markFieldDirty({{ $loop->index }}, 'fill_weight_grams')"
                                           @blur="saveVariation({{ $loop->index }}, 'fill_weight_grams')"
                                           @keydown.enter.prevent.stop="saveVariation({{ $loop->index }}, 'fill_weight_grams')"
                                           type="number" 
                                           step="0.01"
                                           placeholder="{{ $variation->fill_weight ? number_format($variation->fill_weight, 2) : '0' }}"
                                           x-bind:disabled="saving"
                                           x-bind:class="{
                                               'border-yellow-300': isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['fill_weight_grams'],
                                               'border-red-300': hasError[{{ $loop->index }}] && hasError[{{ $loop->index }}]['fill_weight_grams'],
                                               'block w-full text-sm rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100': true,
                                               'border-gray-300 dark:border-gray-600': !isDirty[{{ $loop->index }}] || !isDirty[{{ $loop->index }}]['fill_weight_grams']
                                           }"
                                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                    
                                    <!-- Loading indicator -->
                                    <div x-show="saving" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <svg class="animate-spin h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Dirty indicator -->
                                    <div x-show="isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['fill_weight_grams'] && !saving" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <div class="h-2 w-2 bg-orange-400 rounded-full"></div>
                                    </div>
                                </div>
                                @error('variations.'.$loop->index.'.fill_weight_grams')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="relative">
                                    <input x-model="localVariations[{{ $loop->index }}].price"
                                           @input="markFieldDirty({{ $loop->index }}, 'price')"
                                           @blur="saveVariation({{ $loop->index }}, 'price')"
                                           @keydown.enter.prevent.stop="saveVariation({{ $loop->index }}, 'price')"
                                           type="number" 
                                           step="0.001"
                                           min="0"
                                           placeholder="{{ number_format($variation->price, 3) }}"
                                           x-bind:disabled="saving"
                                           x-bind:class="{
                                               'border-yellow-300': isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['price'],
                                               'border-red-300': hasError[{{ $loop->index }}] && hasError[{{ $loop->index }}]['price'],
                                               'block w-full text-sm rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100': true,
                                               'border-gray-300 dark:border-gray-600': !isDirty[{{ $loop->index }}] || !isDirty[{{ $loop->index }}]['price']
                                           }"
                                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                    
                                    <!-- Loading indicator -->
                                    <div x-show="saving" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <svg class="animate-spin h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- Dirty indicator -->
                                    <div x-show="isDirty[{{ $loop->index }}] && isDirty[{{ $loop->index }}]['price'] && !saving" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <div class="h-2 w-2 bg-orange-400 rounded-full"></div>
                                    </div>
                                </div>
                                @error('variations.'.$loop->index.'.price')
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($variation->is_default)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Default
                                        </span>
                                    @else
                                        <button type="button" 
                                                @click="Livewire.find('{{ $livewire->getId() }}').setAsDefault({{ $variation->id }})"
                                                class="text-sm text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            Set as default
                                        </button>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($variation->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button type="button"
                                        @click="if (confirm('Are you sure you want to delete this price variation?')) { Livewire.find('{{ $livewire->getId() }}').deleteVariation({{ $variation->id }}) }"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No price variations</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a price variation for this product.</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">You can use templates above or create custom variations below.</p>
        </div>
    @endif
    
    <div class="flex justify-between items-center">
        <button type="button" 
                @click="Livewire.find('{{ $livewire->getId() }}').addCustomVariation()"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-600">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Custom Variation
        </button>
        
        @if($variations->isNotEmpty())
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $variations->where('is_active', true)->count() }} active / {{ $variations->count() }} total variations
            </div>
        @endif
    </div>
</div>

<script>
function priceVariationsTable(serverVariations) {
    return {
        // Local state for immediate UI updates
        localVariations: [],
        originalVariations: [],
        isDirty: {},
        hasError: {},
        saving: false,
        
        // Initialize local variations from server data
        initializeVariations() {
            this.localVariations = JSON.parse(JSON.stringify(serverVariations));
            this.originalVariations = JSON.parse(JSON.stringify(serverVariations));
            
            // Ensure packaging_type_id is properly set for each variation
            this.localVariations.forEach((variation, index) => {
                // Make sure packaging_type_id is set correctly for the select field
                if (variation.packaging_type_id === null || variation.packaging_type_id === undefined) {
                    variation.packaging_type_id = '';
                } else {
                    // Ensure it's a string for select binding
                    variation.packaging_type_id = String(variation.packaging_type_id);
                }
                
            });
            
            // Also fix the original variations for comparison
            this.originalVariations.forEach((variation, index) => {
                if (variation.packaging_type_id === null || variation.packaging_type_id === undefined) {
                    variation.packaging_type_id = '';
                } else {
                    variation.packaging_type_id = String(variation.packaging_type_id);
                }
            });
            
            // Initialize tracking objects
            this.isDirty = {};
            this.hasError = {};
            
            serverVariations.forEach((variation, index) => {
                this.isDirty[index] = {};
                this.hasError[index] = {};
            });
        },
        
        // Save a specific field for a variation
        async saveVariation(variationIndex, fieldName) {
            // Don't save if already saving
            if (this.saving) return;
            
            const currentValue = this.localVariations[variationIndex][fieldName];
            const originalValue = this.originalVariations[variationIndex][fieldName];
            
            // Skip saving if value hasn't changed
            if (currentValue === originalValue) {
                this.isDirty[variationIndex][fieldName] = false;
                return;
            }
            
            // Clear any previous errors for this field
            this.hasError[variationIndex][fieldName] = false;
            
            try {
                this.saving = true;
                
                // Call the Livewire updateVariation method
                const livewireComponent = Livewire.find(this.$el.closest('[wire\\:id]').getAttribute('wire:id'));
                
                // Create the update data
                const updateData = {
                    variationIndex: variationIndex,
                    fieldName: fieldName,
                    value: currentValue,
                    variation: this.localVariations[variationIndex]
                };
                
                // Call the Livewire method
                await livewireComponent.call('updateVariation', updateData);
                
                // Update original value if successful
                this.originalVariations[variationIndex][fieldName] = currentValue;
                this.isDirty[variationIndex][fieldName] = false;
                
            } catch (error) {
                console.error('Error saving variation:', error);
                
                // Mark field as having an error
                this.hasError[variationIndex][fieldName] = true;
                
                // Optionally revert to original value
                // this.localVariations[variationIndex][fieldName] = this.originalVariations[variationIndex][fieldName];
                // this.isDirty[variationIndex][fieldName] = false;
                
            } finally {
                this.saving = false;
            }
        },
        
        // Watch for changes and mark as dirty
        markFieldDirty(variationIndex, fieldName) {
            const currentValue = this.localVariations[variationIndex][fieldName];
            const originalValue = this.originalVariations[variationIndex][fieldName];
            
            this.isDirty[variationIndex][fieldName] = (currentValue !== originalValue);
            this.hasError[variationIndex][fieldName] = false;
        }
    }
}
</script>