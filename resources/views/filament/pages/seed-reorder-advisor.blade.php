<x-filament-panels::page>
    {{-- Filters Section --}}
    <div class="mb-6">
        {{ $this->form }}
    </div>
    
    {{-- Smart Recommendations Widget --}}
    <div class="mb-6">
        @livewire(\App\Filament\Widgets\SmartSeedRecommendationsWidget::class)
    </div>
    
    {{-- Table Section --}}
    <div>
        {{ $this->table }}
    </div>
</x-filament-panels::page> 