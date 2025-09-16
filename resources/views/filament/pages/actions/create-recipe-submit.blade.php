<x-filament::button
    type="submit"
    size="sm"
    :disabled="false"
    wire:loading.attr="disabled"
    wire:target="create"
>
    <span wire:loading.remove wire:target="create">Create Recipe</span>
    <span wire:loading wire:target="create">Creating...</span>
</x-filament::button>