<x-filament-panels::page>
    <form wire:submit="adjustStock">
        {{ $this->form }}
        
        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button 
                type="button" 
                color="gray" 
                href="{{ \App\Filament\Resources\ConsumableResource::getUrl() }}"
            >
                Cancel
            </x-filament::button>
            
            <x-filament::button type="submit">
                Save
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page> 