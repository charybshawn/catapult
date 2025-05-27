<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="space-y-4">
                <div class="text-xl font-semibold">
                    Upload Seed Data
                </div>
                
                <p class="text-gray-500 dark:text-gray-400">
                    Upload JSON files containing seed data scraped from supplier websites. The data will be processed
                    and integrated into the seed inventory system.
                </p>
                
                {{ $this->form }}
            </div>
        </x-filament::section>
        
        <x-filament::section>
            <div class="space-y-4">
                <div class="text-lg font-medium">
                    Recent Uploads
                </div>
                
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page> 