<div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
    @if($itemId)
        <div class="space-y-4">
            <div>
                <label for="customerType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Customer Type</label>
                <select id="customerType" wire:model="customerType" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md">
                    <option value="retail">Retail</option>
                    <option value="wholesale">Wholesale</option>
                    <option value="bulk">Bulk</option>
                    <option value="special">Special</option>
                </select>
            </div>
            
            <div>
                <label for="quantity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                <input type="number" id="quantity" wire:model="quantity" min="1" class="mt-1 focus:ring-primary-500 focus:border-primary-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md">
            </div>
            
            <div class="border-t border-gray-200 dark:border-gray-600 pt-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Calculated Price:</span>
                    <span class="text-xl font-bold text-primary-600 dark:text-primary-400">${{ number_format($calculatedPrice, 2) }}</span>
                </div>
                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Based on customer type and quantity
                </div>
            </div>
        </div>
    @else
        <div class="p-4 text-gray-500 dark:text-gray-400 text-center">
            Save the product first to enable price calculations.
        </div>
    @endif
</div> 