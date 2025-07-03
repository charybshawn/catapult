@php
    $statePath = $getStatePath();
    $defaultVariation = $getDefaultVariation();
    $variations = $getState() ?: [];
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <!-- SEED VARIATIONS COMPONENT - SINGLE LINE INTERFACE -->
    <div class="seed-variations" x-data="seedVariations(@js($variations), @js($defaultVariation), '{{ $statePath }}')" wire:ignore>
        <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm overflow-hidden">
            <!-- Header Row - Table Style -->
            <div class="bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-600 px-4 py-3">
                <div class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                    <div class="flex-1 min-w-0">Size Description</div>
                    <div class="w-24">SKU</div>
                    <div class="w-20 text-center">Weight (kg)</div>
                    <div class="w-20 text-center">Price</div>
                    <div class="w-16 text-center">Currency</div>
                    <div class="w-16 text-center">In Stock</div>
                    <div class="w-16 text-center">Actions</div>
                </div>
            </div>

            <!-- Variations Container -->
            <div class="divide-y divide-gray-200 dark:divide-gray-600">
                <template x-for="(variation, index) in variations" :key="index">
                    <div class="variation-row px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <div class="flex items-center gap-3 w-full">
                            <!-- Size Description -->
                            <div class="flex-1 min-w-0">
                                <input
                                    type="text"
                                    x-model="variation.size"
                                    @input="syncWithLivewire()"
                                    placeholder="e.g., 25g, 1 oz, Large packet"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2"
                                />
                            </div>

                            <!-- SKU -->
                            <div class="w-24">
                                <input
                                    type="text"
                                    x-model="variation.sku"
                                    @input="syncWithLivewire()"
                                    placeholder="SKU-001"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2"
                                />
                            </div>

                            <!-- Weight (kg) -->
                            <div class="w-20">
                                <input
                                    type="number"
                                    x-model.number="variation.weight_kg"
                                    @input="syncWithLivewire()"
                                    step="0.0001"
                                    min="0"
                                    placeholder="0.025"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 text-center"
                                />
                            </div>

                            <!-- Price -->
                            <div class="w-20">
                                <input
                                    type="number"
                                    x-model.number="variation.current_price"
                                    @input="syncWithLivewire()"
                                    step="0.01"
                                    min="0"
                                    placeholder="4.99"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 text-center"
                                />
                            </div>

                            <!-- Currency -->
                            <div class="w-16">
                                <select 
                                    x-model="variation.currency"
                                    @change="syncWithLivewire()"
                                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2"
                                >
                                    <option value="USD">USD</option>
                                    <option value="CAD">CAD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                </select>
                            </div>

                            <!-- In Stock Toggle -->
                            <div class="w-16 text-center">
                                <input
                                    type="checkbox"
                                    x-model="variation.is_available"
                                    @change="syncWithLivewire()"
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                />
                            </div>

                            <!-- Actions -->
                            <div class="w-16 flex items-center justify-center">
                                <!-- Remove Button -->
                                <button
                                    type="button"
                                    @click="removeVariation(index)"
                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-200 focus:outline-none"
                                    x-show="variations.length > 1"
                                    title="Remove variation"
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
                        @click="addVariation()"
                        class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-white bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Variation
                    </button>

                    <div class="text-right">
                        <div class="text-sm text-gray-500 dark:text-gray-400" x-text="variations.length + ' variation' + (variations.length !== 1 ? 's' : '')"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('seedVariations', (initialVariations, defaultVariation, statePath) => ({
        variations: initialVariations.length > 0 ? initialVariations : [{ ...defaultVariation }],
        defaultVariation: defaultVariation,
        statePath: statePath,

        addVariation() {
            this.variations.push({ ...this.defaultVariation });
            this.syncWithLivewire();
        },

        removeVariation(index) {
            if (this.variations.length > 1) {
                this.variations.splice(index, 1);
                this.syncWithLivewire();
            }
        },

        syncWithLivewire() {
            // Sync data with Livewire
            this.$wire.set(this.statePath, this.variations);
        },

        async init() {
            this.$watch('variations', () => {
                this.syncWithLivewire();
            }, { deep: true });
        }
    }));
});
</script>

<style>
.seed-variations input::-webkit-outer-spin-button,
.seed-variations input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.seed-variations input[type=number] {
    -moz-appearance: textfield;
}

/* Override global table row hover styles */
.seed-variations .variation-row:hover {
    background-color: rgb(249 250 251) !important; /* gray-50 */
}

.dark .seed-variations .variation-row:hover {
    background-color: rgb(31 41 55) !important; /* gray-800 */
}

/* Ensure proper column alignment */
.seed-variations .flex {
    align-items: center;
}

.seed-variations .w-16,
.seed-variations .w-20,
.seed-variations .w-24 {
    flex-shrink: 0;
}

.seed-variations .flex-1 {
    min-width: 0;
    flex: 1 1 0%;
}
</style>