<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                    Bulk Inventory Creation
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Create inventory entries for all price variations of a product in one streamlined form. 
                    Select a product and enter quantities for each variation you want to stock.
                </p>
            </div>

            <x-filament-panels::form
                :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
                wire:submit="save"
            >
                {{ $this->form }}
            </x-filament-panels::form>
        </div>

        @if(!empty($data['product_id']))
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            Bulk Creation Tips
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Only variations with quantities > 0 will create inventory entries</li>
                                <li>Each variation will get its own inventory record</li>
                                <li>Batch numbers will be auto-generated with variation identifiers if multiple entries are created</li>
                                <li>All entries will share the same production date and storage location</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>