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

                            <!-- Price Variation Select -->
                            <div class="flex-1 min-w-0">
                                <select 
                                    x-model="item.price_variation_id"
                                    @change="updatePriceFromVariation(index)"
                                    :disabled="!item.item_id"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 disabled:bg-gray-100 dark:disabled:bg-gray-700 disabled:cursor-not-allowed"
                                >
                                    <option value="">Select variation...</option>
                                    <template x-for="variation in getPriceVariationsForProduct(item.item_id)" :key="variation.id">
                                        <option :value="variation.id" x-text="variation.name + ' - $' + variation.price"></option>
                                    </template>
                                </select>
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