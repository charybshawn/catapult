<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="space-y-4">
                <div class="text-xl font-semibold">
                    Seed Price Trends
                </div>
                
                <p class="text-gray-500 dark:text-gray-400">
                    Track how seed prices change over time. Select cultivars and date ranges to visualize price trends.
                </p>
                
                {{ $this->form }}
            </div>
        </x-filament::section>
    </div>
    
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
</x-filament-panels::page> 