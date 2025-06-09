<x-filament-panels::page>
    @if($this->table->getRecords()->count() > 0)
        {{ $this->table }}
    @else
        <div class="text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-success-100 dark:bg-success-900 mb-4">
                <x-heroicon-o-check-circle class="w-8 h-8 text-success-600 dark:text-success-400" />
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                No Failed Entries
            </h3>
            <p class="text-gray-500 dark:text-gray-400">
                All seed imports have been processed successfully.
            </p>
        </div>
    @endif
</x-filament-panels::page>