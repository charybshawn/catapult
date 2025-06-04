@php
    $statePath = $getStatePath();
    $productOptions = $getProductOptions();
    $defaultItem = $getDefaultItem();
    $items = $getState() ?: [];
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <!-- UPDATED INVOICE COMPONENT - SINGLE LINE INTERFACE -->
    <div class="invoice-order-items" x-data="invoiceOrderItems(@js($items), @js($productOptions), @js($defaultItem), '{{ $statePath }}')" wire:ignore>
        <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm overflow-hidden">
            <!-- Header Row - Table Style -->
            <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600 px-4 py-3">
                <div class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <div class="flex-1 min-w-0">Product</div>
                    <div class="flex-1 min-w-0">Price Variation</div>
                    <div class="w-20 text-center">Qty</div>
                    <div class="w-24 text-center">Unit Price</div>
                    <div class="w-20 text-center">Total</div>
                    <div class="w-16 text-center">Actions</div>
                </div>
            </div>

            <!-- Items Container -->
            <div class="divide-y divide-gray-200 dark:divide-gray-600">
                <template x-for="(item, index) in items" :key="index">
                    <div class="invoice-item-row px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center gap-3 w-full">
                            <!-- Product Select -->
                            <div class="flex-1 min-w-0">
                                <select 
                                    x-model="item.item_id"
                                    @change="updatePriceVariations(index)"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2"
                                >
                                    <option value="">Select product...</option>
                                    <template x-for="(name, id) in productOptions" :key="id">
                                        <option :value="id" x-text="name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Price Variation Select with Add Button -->
                            <div class="flex-1 min-w-0 flex gap-2">
                                <select 
                                    x-model="item.price_variation_id"
                                    @change="updatePriceFromVariation(index)"
                                    :disabled="!item.item_id"
                                    class="flex-1 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                                >
                                    <option value="">Select variation...</option>
                                    <template x-for="variation in getPriceVariationsForProduct(item.item_id)" :key="variation.id">
                                        <option :value="variation.id" x-text="variation.name + ' - $' + variation.price"></option>
                                    </template>
                                </select>
                                <button
                                    type="button"
                                    @click="openCreateVariationModal(index)"
                                    :disabled="!item.item_id"
                                    class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:cursor-not-allowed"
                                    title="Add new price variation"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Quantity Input -->
                            <div class="w-20">
                                <input
                                    type="number"
                                    x-model.number="item.quantity"
                                    @input="updateTotal(index)"
                                    min="1"
                                    step="1"
                                    placeholder="Qty"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 text-center"
                                />
                            </div>

                            <!-- Unit Price Display -->
                            <div class="w-24 text-center">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="formatCurrency(item.price)"></span>
                            </div>

                            <!-- Total Display -->
                            <div class="w-20 text-center">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="formatCurrency(item.quantity * item.price)"></span>
                            </div>

                            <!-- Actions -->
                            <div class="w-16 flex items-center justify-center">
                                <!-- Remove Button -->
                                <button
                                    type="button"
                                    @click="removeItem(index)"
                                    class="text-red-400 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 focus:outline-none"
                                    x-show="items.length > 1"
                                    title="Remove item"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600 px-4 py-3">
                <div class="flex justify-between items-center">
                    <button
                        type="button"
                        @click="addItem()"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Item
                    </button>

                    <div class="text-right">
                        <div class="text-sm text-gray-500 dark:text-gray-400">Subtotal</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-gray-100" x-text="formatCurrency(calculateSubtotal())"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Price Variation Modal -->
        <div x-show="showCreateVariationModal" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             x-cloak
             style="display: none;">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                     @click="closeCreateVariationModal()"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Create New Price Variation
                        </h3>
                        
                        <div class="space-y-4">
                            <!-- Variation Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Variation Name
                                </label>
                                <input
                                    type="text"
                                    x-model="newVariation.name"
                                    placeholder="e.g., Small Container, Large Bag"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                            </div>

                            <!-- SKU -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    SKU (Optional)
                                </label>
                                <input
                                    type="text"
                                    x-model="newVariation.sku"
                                    placeholder="e.g., PROD-SM, PROD-LG"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                            </div>

                            <!-- Price -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Price
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 dark:text-gray-400 text-sm">$</span>
                                    </div>
                                    <input
                                        type="number"
                                        x-model.number="newVariation.price"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                        class="block w-full pl-7 pr-3 rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                    />
                                </div>
                            </div>

                            <!-- Fill Weight -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Fill Weight (grams, optional)
                                </label>
                                <input
                                    type="number"
                                    x-model.number="newVariation.fill_weight_grams"
                                    min="0"
                                    step="0.01"
                                    placeholder="e.g., 100"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                            </div>

                            <!-- Packaging Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Packaging Type (Optional)
                                </label>
                                <select
                                    x-model="newVariation.packaging_type_id"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">Select packaging type...</option>
                                    <template x-for="packaging in packagingTypes" :key="packaging.id">
                                        <option :value="packaging.id" x-text="packaging.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Make Default -->
                            <div class="flex items-center">
                                <input
                                    type="checkbox"
                                    x-model="newVariation.is_default"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                />
                                <label class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Make this the default variation
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            @click="createPriceVariation()"
                            :disabled="!newVariation.name || !newVariation.price"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:bg-gray-400 disabled:cursor-not-allowed"
                        >
                            Create Variation
                        </button>
                        <button
                            type="button"
                            @click="closeCreateVariationModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('invoiceOrderItems', (initialItems, productOptions, defaultItem, statePath) => ({
        items: initialItems.length > 0 ? initialItems : [{ ...defaultItem }],
        productOptions: productOptions,
        defaultItem: defaultItem,
        statePath: statePath,
        priceVariations: {},
        packagingTypes: [],
        showCreateVariationModal: false,
        currentItemIndex: null,
        newVariation: {
            name: '',
            sku: '',
            price: null,
            fill_weight_grams: null,
            packaging_type_id: '',
            is_default: false
        },

        addItem() {
            this.items.push({ ...this.defaultItem });
            this.syncWithLivewire();
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.syncWithLivewire();
            }
        },

        async updatePriceVariations(index) {
            const item = this.items[index];
            if (item.item_id) {
                try {
                    // Fetch price variations for the selected product
                    const response = await fetch(`/api/products/${item.item_id}/price-variations`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                        }
                    });
                    if (response.ok) {
                        const variations = await response.json();
                        this.priceVariations[item.item_id] = variations;
                        
                        // Reset price variation selection
                        item.price_variation_id = null;
                        item.price = 0;
                        
                        // Auto-select default variation if available
                        const defaultVariation = variations.find(v => v.is_default);
                        if (defaultVariation) {
                            item.price_variation_id = defaultVariation.id;
                            item.price = defaultVariation.price;
                        }
                    }
                } catch (error) {
                    console.warn('Could not fetch price variations:', error);
                }
            } else {
                // Clear price variation data if no product selected
                item.price_variation_id = null;
                item.price = 0;
            }
            this.syncWithLivewire();
        },

        updatePriceFromVariation(index) {
            const item = this.items[index];
            if (item.price_variation_id && this.priceVariations[item.item_id]) {
                const variation = this.priceVariations[item.item_id].find(v => v.id == item.price_variation_id);
                if (variation) {
                    item.price = variation.price;
                }
            } else {
                item.price = 0;
            }
            this.syncWithLivewire();
        },

        getPriceVariationsForProduct(productId) {
            return this.priceVariations[productId] || [];
        },

        openCreateVariationModal(index) {
            this.currentItemIndex = index;
            this.showCreateVariationModal = true;
            this.resetNewVariationForm();
        },

        closeCreateVariationModal() {
            this.showCreateVariationModal = false;
            this.currentItemIndex = null;
            this.resetNewVariationForm();
        },

        resetNewVariationForm() {
            this.newVariation = {
                name: '',
                sku: '',
                price: null,
                fill_weight_grams: null,
                packaging_type_id: '',
                is_default: false
            };
        },

        async createPriceVariation() {
            if (!this.newVariation.name || !this.newVariation.price || this.currentItemIndex === null) {
                return;
            }

            const item = this.items[this.currentItemIndex];
            if (!item.item_id) {
                return;
            }

            try {
                const response = await fetch('/api/price-variations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: item.item_id,
                        name: this.newVariation.name,
                        sku: this.newVariation.sku || null,
                        price: this.newVariation.price,
                        fill_weight_grams: this.newVariation.fill_weight_grams || null,
                        packaging_type_id: this.newVariation.packaging_type_id || null,
                        is_default: this.newVariation.is_default,
                        is_active: true
                    })
                });

                if (response.ok) {
                    const createdVariation = await response.json();
                    
                    // Add the new variation to the local list
                    if (!this.priceVariations[item.item_id]) {
                        this.priceVariations[item.item_id] = [];
                    }
                    this.priceVariations[item.item_id].push(createdVariation);
                    
                    // Sort variations (default first, then by name)
                    this.priceVariations[item.item_id].sort((a, b) => {
                        if (a.is_default && !b.is_default) return -1;
                        if (!a.is_default && b.is_default) return 1;
                        return a.name.localeCompare(b.name);
                    });
                    
                    // Auto-select the new variation
                    item.price_variation_id = createdVariation.id;
                    item.price = createdVariation.price;
                    
                    this.closeCreateVariationModal();
                    this.syncWithLivewire();
                } else {
                    const errorData = await response.json();
                    alert('Error creating price variation: ' + (errorData.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error creating price variation:', error);
                alert('Error creating price variation. Please try again.');
            }
        },

        async loadPackagingTypes() {
            try {
                const response = await fetch('/api/packaging-types', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                });
                if (response.ok) {
                    this.packagingTypes = await response.json();
                }
            } catch (error) {
                console.warn('Could not load packaging types:', error);
            }
        },

        updateTotal(index) {
            this.syncWithLivewire();
        },

        calculateSubtotal() {
            return this.items.reduce((total, item) => {
                return total + (item.quantity * item.price);
            }, 0);
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },

        syncWithLivewire() {
            // Sync data with Livewire
            this.$wire.set(this.statePath, this.items);
        },

        init() {
            // Load packaging types for the create variation modal
            this.loadPackagingTypes();
            
            this.$watch('items', () => {
                this.syncWithLivewire();
            }, { deep: true });
        }
    }));
});
</script>

<style>
.invoice-order-items input::-webkit-outer-spin-button,
.invoice-order-items input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.invoice-order-items input[type=number] {
    -moz-appearance: textfield;
}

/* Override global table row hover styles */
.invoice-order-items .invoice-item-row:hover {
    background-color: rgb(249 250 251) !important; /* gray-50 */
}

.dark .invoice-order-items .invoice-item-row:hover {
    background-color: rgb(31 41 55) !important; /* gray-800 */
}

/* Ensure proper column alignment */
.invoice-order-items .flex {
    align-items: center;
}

.invoice-order-items .w-20,
.invoice-order-items .w-24,
.invoice-order-items .w-16 {
    flex-shrink: 0;
}

.invoice-order-items .flex-1 {
    min-width: 0;
    flex: 1 1 0%;
}
</style>