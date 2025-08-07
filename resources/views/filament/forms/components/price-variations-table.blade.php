<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        // Get the Livewire component and product record
        // Try multiple ways to get the product
        $livewire = $this->getLivewire();
        $product = null;
        
        // Method 1: Try getRecord() method
        if ($livewire && method_exists($livewire, 'getRecord')) {
            try {
                $product = $livewire->getRecord();
            } catch (\Exception $e) {
                // getRecord() failed, try other methods
            }
        }
        
        // Method 2: Try record property 
        if (!$product && $livewire && property_exists($livewire, 'record')) {
            $product = $livewire->record;
        }
        
        // Method 3: Try to get from the form state or component context
        if (!$product && method_exists($this, 'getRecord')) {
            try {
                $product = $this->getRecord();
            } catch (\Exception $e) {
                // Failed
            }
        }
        
        // Method 4: Try to get from Livewire directly as 'this'
        if (!$product && property_exists($this, 'record')) {
            $product = $this->record;
        }
        
        // Get variations if we have a product - always fetch fresh from database
        $variations = collect();
        if ($product && $product->exists) {
            $variations = \App\Models\PriceVariation::where('product_id', $product->id)
                ->with('packagingType')
                ->get();
        }
    @endphp

    <div class="space-y-4" wire:key="price-variations-table-{{ $product?->id }}">
        
        @if($variations->isNotEmpty())
            <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-600">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Type
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Unit
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Packaging
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                SKU
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Weight/Qty
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
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800" x-data="{ 
                                editing: false,
                                saving: false,
                                name: '{{ $variation->name ?: 'Default' }}',
                                pricing_type: '{{ $variation->pricing_type ?: 'retail' }}',
                                pricing_unit: '{{ $variation->pricing_unit ?: 'per_item' }}',
                                packaging_type_id: '{{ $variation->packaging_type_id }}',
                                sku: '{{ $variation->sku }}',
                                fill_weight_grams: '{{ $variation->fill_weight_grams }}',
                                price: '{{ $variation->price }}',
                                is_name_manual: {{ $variation->is_name_manual ? 'true' : 'false' }},
                                
                                // Save function to handle Enter key and save button
                                async saveVariation() {
                                    if (this.saving) return; // Prevent double-saves
                                    
                                    this.saving = true;
                                    try {
                                        await Livewire.find('{{ $livewire->getId() }}').updateVariation({{ $variation->id }}, {
                                            name: this.name,
                                            pricing_type: this.pricing_type,
                                            pricing_unit: this.pricing_unit,
                                            packaging_type_id: this.packaging_type_id,
                                            sku: this.sku,
                                            fill_weight_grams: this.fill_weight_grams,
                                            price: this.price,
                                            is_name_manual: this.is_name_manual
                                        });
                                        // Visual feedback could be added here
                                    } catch (error) {
                                        console.error('Error saving variation:', error);
                                    } finally {
                                        this.saving = false;
                                        this.editing = false;
                                    }
                                },
                                
                                // Auto-generate name when fields change (only for new records)
                                generateName() {
                                    if (this.is_name_manual || {{ $variation->id ? 'true' : 'false' }}) return;
                                    
                                    let parts = [];
                                    
                                    // Add pricing type
                                    const pricingTypes = {
                                        'retail': 'Retail',
                                        'wholesale': 'Wholesale',
                                        'bulk': 'Bulk',
                                        'special': 'Special',
                                        'custom': 'Custom'
                                    };
                                    parts.push(pricingTypes[this.pricing_type] || 'Retail');
                                    
                                    // Add packaging
                                    if (this.packaging_type_id) {
                                        const packaging = @json(\App\Models\PackagingType::all()->keyBy('id')->map(fn($p) => $p->name));
                                        parts.push(packaging[this.packaging_type_id] || 'Package');
                                    } else {
                                        parts.push('Package-Free');
                                    }
                                    
                                    // Add price
                                    if (this.price) {
                                        parts.push('$' + parseFloat(this.price).toFixed(2));
                                    }
                                    
                                    this.name = parts.join(' - ');
                                },
                                
                                // Mark name as manually edited
                                markNameAsManual() {
                                    this.is_name_manual = true;
                                }
                            }">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="flex space-x-1">
                                        <input x-model="name" 
                                               @input="markNameAsManual()"
                                               @keydown.enter.prevent.stop="saveVariation()"
                                               type="text" 
                                               class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                        <button type="button" 
                                                @click="is_name_manual = false; generateName()"
                                                class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded border"
                                                title="Reset to auto-generated">
                                            ↻
                                        </button>
                                        @if($variation->is_name_manual)
                                            <span class="ml-1 text-xs text-blue-600" title="Manually set name">✓</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <select x-model="pricing_type"
                                            @change="generateName()"
                                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                        <option value="retail">Retail</option>
                                        <option value="wholesale">Wholesale</option>
                                        <option value="bulk">Bulk</option>
                                        <option value="special">Special</option>
                                        <option value="custom">Custom</option>
                                    </select>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <select x-model="pricing_unit"
                                            @change="generateName()"
                                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                        <option value="per_item">Per Item</option>
                                        <option value="per_tray">Per Tray</option>
                                        <option value="per_g">Per Gram</option>
                                        <option value="per_kg">Per Kg</option>
                                        <option value="per_lb">Per Lb</option>
                                        <option value="per_oz">Per Oz</option>
                                    </select>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <select x-model="packaging_type_id"
                                            @change="generateName()"
                                            class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                        <option value="">No packaging</option>
                                        @foreach(\App\Models\PackagingType::all() as $packaging)
                                            <option value="{{ $packaging->id }}">{{ $packaging->display_name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <input x-model="sku" 
                                           @keydown.enter.prevent="saveVariation()"
                                           type="text" 
                                           placeholder="SKU"
                                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <input x-model="fill_weight_grams" 
                                           @keydown.enter.prevent="saveVariation()"
                                           type="number" 
                                           step="0.01"
                                           placeholder="Weight (g)"
                                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <input x-model="price" 
                                           @input="generateName()"
                                           @keydown.enter.prevent="saveVariation()"
                                           type="number" 
                                           step="0.01"
                                           min="0"
                                           placeholder="Price"
                                           class="block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-100">
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
                                    <div class="flex items-center justify-end space-x-2">
                                        <button type="button"
                                                @click="saveVariation()"
                                                :disabled="saving"
                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                                title="Save changes">
                                            <span x-show="!saving">Save</span>
                                            <span x-show="saving">Saving...</span>
                                        </button>
                                        <button type="button"
                                                @click="if (confirm('Are you sure you want to delete this price variation?')) { Livewire.find('{{ $livewire->getId() }}').deleteVariation({{ $variation->id }}) }"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            Delete
                                        </button>
                                    </div>
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
</x-dynamic-component>