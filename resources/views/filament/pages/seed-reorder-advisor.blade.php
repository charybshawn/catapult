<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="space-y-4">
                <div class="text-xl font-semibold">
                    Seed Reorder Advisor
                </div>
                
                <p class="text-gray-500 dark:text-gray-400">
                    This page helps you find the best prices for seeds across suppliers.
                    Compare prices for various seed cultivars to make informed purchasing decisions.
                </p>
                
                {{ $this->form }}
            </div>
        </x-filament::section>
        
        <x-filament::section>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page> 