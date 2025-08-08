<div>
    <input 
        type="number"
        wire:model.live="quantities.{{ $getRecord()->variation_id }}"
        class="block w-full transition duration-75 rounded-lg shadow-sm focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-50 dark:bg-gray-700 dark:text-white dark:focus:ring-primary-500 border-gray-300 dark:border-gray-600"
        min="0"
        step="1"
        placeholder="0"
    />
</div>