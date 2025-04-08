<x-filament::section>
    <div class="space-y-4">
        <div class="flex items-center gap-3">
            <div class="flex-shrink-0">
                <x-filament::icon
                    alias="filament::actions.delete.modal.icon"
                    icon="heroicon-o-exclamation-triangle"
                    class="h-6 w-6 text-danger-500 dark:text-danger-400"
                />
            </div>
            
            <div class="flex-1">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Warning: Recipes with {{ $hasActiveCrops ? 'active' : '' }} crops selected
                </h3>
                
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if($hasActiveCrops)
                        You're about to delete <strong>{{ $totalRecipesWithCrops }}</strong> {{ \Illuminate\Support\Str::plural('recipe', $totalRecipesWithCrops) }} that have a total of <strong>{{ $totalCropsCount }}</strong> associated {{ \Illuminate\Support\Str::plural('crop', $totalCropsCount) }}.
                        <br><strong>{{ $recipesCount }}</strong> of these {{ \Illuminate\Support\Str::plural('recipe', $recipesCount) }} {{ $recipesCount === 1 ? 'has' : 'have' }} <strong>{{ $activeCropsCount }}</strong> active {{ \Illuminate\Support\Str::plural('crop', $activeCropsCount) }}.
                    @else
                        You're about to delete <strong>{{ $totalRecipesWithCrops }}</strong> {{ \Illuminate\Support\Str::plural('recipe', $totalRecipesWithCrops) }} that {{ $totalRecipesWithCrops === 1 ? 'has' : 'have' }} a total of <strong>{{ $totalCropsCount }}</strong> completed {{ \Illuminate\Support\Str::plural('crop', $totalCropsCount) }}.
                    @endif
                </p>
                
                <div class="mt-4 border-l-4 border-danger-500 bg-danger-50 p-4 dark:border-danger-600 dark:bg-danger-500/20">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <x-filament::icon
                                icon="heroicon-m-exclamation-triangle"
                                class="h-5 w-5 text-danger-400"
                            />
                        </div>
                        
                        <div class="ml-3">
                            @if($hasActiveCrops)
                                <p class="text-sm text-danger-700 dark:text-danger-200">
                                    <strong>Caution: Active crops will be deleted!</strong> This operation will permanently delete all selected recipes and their associated crops, including <strong>{{ $activeCropsCount }} active {{ \Illuminate\Support\Str::plural('crop', $activeCropsCount) }}</strong> that are currently being grown. This action cannot be undone.
                                </p>
                            @else
                                <p class="text-sm text-danger-700 dark:text-danger-200">
                                    <strong>Note:</strong> Deleting these recipes will also delete all {{ $totalCropsCount }} associated completed {{ \Illuminate\Support\Str::plural('crop', $totalCropsCount) }}. This action cannot be undone.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament::section> 