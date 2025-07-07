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
                    <div class="w-20 text-center">Quantity</div>
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
                                    <template x-for="[id, name] in Object.entries(productOptions)" :key="id">
                                        <option :value="id" x-text="name" :selected="id === item.item_id"></option>
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
                                        <option :value="variation.id" x-text="formatVariationOption(variation)" :selected="variation.id.toString() === item.price_variation_id.toString()"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Quantity Input -->
                            <div class="w-20">
                                <input
                                    type="number"
                                    x-model.number="item.quantity"
                                    @input="updateTotal(index)"
                                    @focus="console.log('Focus on quantity input', index, 'step:', getQuantityStep(index), 'min:', getMinQuantity(index))"
                                    :min="getMinQuantity(index)"
                                    :step="getQuantityStep(index)"
                                    :placeholder="getQuantityPlaceholder(index)"
                                    pattern="[0-9]*\.?[0-9]*"
                                    inputmode="decimal"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 text-center"
                                />
                                <div class="text-xs text-gray-500 text-center mt-1" x-text="getQuantityUnit(index)"></div>
                            </div>

                            <!-- Unit Price Display -->
                            <div class="w-24 text-center">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="formatCurrency(parseFloat(item.price) || 0)"></span>
                            </div>

                            <!-- Total Display -->
                            <div class="w-20 text-center">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="formatCurrency((parseFloat(item.quantity) || 0) * (parseFloat(item.price) || 0))"></span>
                            </div>

                            <!-- Actions -->
                            <div class="w-16 flex items-center justify-center">
                                <!-- Remove Button -->
                                <button
                                    type="button"
                                    @click="removeItem(index)"
                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200 focus:outline-none"
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
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-white bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
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
                // Load price variations if not already loaded
                if (!this.priceVariations[item.item_id]) {
                    await this.loadPriceVariationsForProduct(item.item_id);
                }
                
                // Reset price variation selection for new product selection
                item.price_variation_id = null;
                item.price = 0;
                
                // Auto-select default variation if available
                const variations = this.priceVariations[item.item_id] || [];
                const defaultVariation = variations.find(v => v.is_default);
                if (defaultVariation) {
                    item.price_variation_id = defaultVariation.id.toString();
                    item.price = defaultVariation.price;
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
                const variation = this.priceVariations[item.item_id].find(v => v.id.toString() === item.price_variation_id.toString());
                if (variation) {
                    // Always use the current price from the loaded variation (includes wholesale discount)
                    const newPrice = parseFloat(variation.price);
                    if (item.price !== newPrice) {
                        item.price = newPrice;
                        console.log(`Updated price for item ${index}: ${newPrice}`);
                    }
                    
                    // Debug: Log variation details
                    console.log('Selected variation:', variation);
                    console.log('Pricing unit:', variation.pricing_unit);
                    
                    // Reset quantity to appropriate default for the pricing unit
                    const pricingUnit = this.getPricingUnit(index);
                    console.log('Detected pricing unit:', pricingUnit);
                    console.log('Is sold by weight:', this.isSoldByWeight(index));
                    
                    if (this.isSoldByWeight(index)) {
                        // For weight-based items, set sensible defaults
                        if (!item.quantity || item.quantity <= 0) {
                            if (['per_lb', 'lb', 'lbs'].includes(pricingUnit)) {
                                item.quantity = 0.25; // Default to 1/4 lb
                            } else if (['per_kg', 'kg'].includes(pricingUnit)) {
                                item.quantity = 0.1; // Default to 100g
                            } else if (['per_oz', 'oz'].includes(pricingUnit)) {
                                item.quantity = 4; // Default to 4 oz
                            } else if (['per_g', 'g'].includes(pricingUnit)) {
                                item.quantity = 100; // Default to 100 grams
                            }
                        }
                    } else {
                        // For units, ensure quantity is at least 1 and is a whole number
                        if (!item.quantity || item.quantity <= 0) {
                            item.quantity = 1;
                        } else {
                            // Round to whole number but preserve the existing valid quantity
                            item.quantity = Math.round(item.quantity);
                        }
                    }
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
                const quantity = parseFloat(item.quantity) || 0;
                const price = parseFloat(item.price) || 0;
                return total + (quantity * price);
            }, 0);
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount || 0);
        },
        
        formatVariationOption(variation) {
            let text = variation.name;
            if (variation.pricing_unit && !['each', 'per_item'].includes(variation.pricing_unit)) {
                // Clean up the unit display
                let displayUnit = variation.pricing_unit;
                if (displayUnit.startsWith('per_')) {
                    displayUnit = displayUnit.substring(4); // Remove 'per_' prefix
                }
                text += ' - $' + variation.price + '/' + displayUnit;
            } else {
                text += ' - $' + variation.price;
            }
            return text;
        },

        syncWithLivewire() {
            // Sync data with Livewire
            this.$wire.set(this.statePath, this.items);
        },

        async init() {
            // Load price variations for existing items
            await this.loadExistingPriceVariations();
            
            // Debug: Log initial state
            console.log('Initial items:', this.items);
            
            this.$watch('items', () => {
                this.syncWithLivewire();
            }, { deep: true });
            
            // Watch for customer changes and reload price variations
            this.watchForCustomerChanges();
        },

        watchForCustomerChanges() {
            // Watch for changes to the user_id field
            const userIdField = document.querySelector('select[name="user_id"]');
            if (userIdField) {
                userIdField.addEventListener('change', async () => {
                    console.log('Customer changed, reloading prices...');
                    
                    // Clear existing price variations cache
                    this.priceVariations = {};
                    
                    // Reload price variations for all products in current items
                    const productIds = [...new Set(this.items.map(item => item.item_id).filter(id => id))];
                    console.log('Reloading prices for products:', productIds);
                    
                    for (const productId of productIds) {
                        await this.loadPriceVariationsForProduct(productId);
                        console.log('Loaded variations for product', productId, this.priceVariations[productId]);
                        
                        // Update prices for items using this product
                        this.items.forEach((item, index) => {
                            if (item.item_id === productId && item.price_variation_id) {
                                console.log(`Updating price for item ${index}, variation ${item.price_variation_id}`);
                                this.updatePriceFromVariation(index);
                            }
                        });
                    }
                    
                    // Force Alpine to update the display
                    this.$nextTick(() => {
                        // Trigger reactivity by creating a new array reference
                        this.items = [...this.items];
                        this.syncWithLivewire();
                        console.log('Prices updated, items:', this.items);
                    });
                });
            }
        },

        async loadExistingPriceVariations() {
            // Load price variations for products that already have items
            const productIds = [...new Set(this.items.map(item => item.item_id).filter(id => id))];
            
            for (const productId of productIds) {
                if (productId && !this.priceVariations[productId]) {
                    await this.loadPriceVariationsForProduct(productId);
                }
            }
        },

        async loadPriceVariationsForProduct(productId) {
            try {
                // Get customer ID from the form if available
                const customerId = this.getCustomerId();
                const url = new URL(`/api/products/${productId}/price-variations`, window.location.origin);
                if (customerId) {
                    url.searchParams.append('customer_id', customerId);
                }
                
                const response = await fetch(url, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                    }
                });
                if (response.ok) {
                    const variations = await response.json();
                    this.priceVariations[productId] = variations;
                }
            } catch (error) {
                console.warn('Could not fetch price variations for product:', productId, error);
            }
        },

        getCustomerId() {
            // Try to get customer ID from Livewire data
            if (this.$wire && this.$wire.data && this.$wire.data.user_id) {
                return this.$wire.data.user_id;
            }
            
            // Try to get from form field
            const userIdField = document.querySelector('select[name="user_id"], input[name="user_id"]');
            if (userIdField && userIdField.value) {
                return userIdField.value;
            }
            
            return null;
        },

        // Check if the selected price variation is sold by weight
        isSoldByWeight(index) {
            const item = this.items[index];
            if (!item.price_variation_id || !this.priceVariations[item.item_id]) {
                return false;
            }
            
            const variation = this.priceVariations[item.item_id].find(v => 
                v.id.toString() === item.price_variation_id.toString()
            );
            
            return variation && variation.pricing_unit && 
                   ['per_lb', 'per_kg', 'per_g', 'per_oz', 'lb', 'lbs', 'kg', 'g', 'oz'].includes(variation.pricing_unit);
        },
        
        // Get the pricing unit for the selected variation
        getPricingUnit(index) {
            const item = this.items[index];
            if (!item.price_variation_id || !this.priceVariations[item.item_id]) {
                return 'each';
            }
            
            const variation = this.priceVariations[item.item_id].find(v => 
                v.id.toString() === item.price_variation_id.toString()
            );
            
            return variation?.pricing_unit || 'each';
        },

        getMinQuantity(index) {
            const unit = this.getPricingUnit(index);
            return ['per_lb', 'per_kg', 'per_oz', 'lb', 'lbs', 'kg', 'oz'].includes(unit) ? 0.01 : 
                   ['per_g', 'g'].includes(unit) ? 1 : 1;
        },

        getQuantityStep(index) {
            const unit = this.getPricingUnit(index);
            return ['per_lb', 'per_kg', 'lb', 'lbs', 'kg'].includes(unit) ? 0.01 : 
                   ['per_oz', 'oz'].includes(unit) ? 0.1 :
                   ['per_g', 'g'].includes(unit) ? 1 : 1;
        },

        getQuantityPlaceholder(index) {
            const unit = this.getPricingUnit(index);
            return ['per_item', 'each'].includes(unit) ? 'Qty' : 'Amount';
        },

        getQuantityUnit(index) {
            const unit = this.getPricingUnit(index);
            return ['per_lb', 'lb', 'lbs'].includes(unit) ? 'lbs' :
                   ['per_kg', 'kg'].includes(unit) ? 'kg' :
                   ['per_g', 'g'].includes(unit) ? 'grams' :
                   ['per_oz', 'oz'].includes(unit) ? 'oz' :
                   'units';
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