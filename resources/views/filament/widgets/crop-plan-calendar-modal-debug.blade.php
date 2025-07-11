<div class="space-y-4">
    <div class="bg-red-50 dark:bg-red-900 border border-red-200 dark:border-red-700 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-red-800 dark:text-red-200 mb-3">Debug Information</h3>
        
        <div class="space-y-2 text-sm">
            <div>
                <span class="font-medium text-red-700 dark:text-red-300">Type:</span>
                <span class="text-red-600 dark:text-red-400">{{ $props['type'] ?? 'NONE' }}</span>
            </div>
            
            <div>
                <span class="font-medium text-red-700 dark:text-red-300">Available keys:</span>
                <span class="text-red-600 dark:text-red-400">{{ implode(', ', array_keys($props)) }}</span>
            </div>
            
            @if(isset($props['variety']))
                <div>
                    <span class="font-medium text-red-700 dark:text-red-300">Variety:</span>
                    <span class="text-red-600 dark:text-red-400">{{ $props['variety'] }}</span>
                </div>
            @endif
            
            @if(isset($props['total_grams']))
                <div>
                    <span class="font-medium text-red-700 dark:text-red-300">Total Grams:</span>
                    <span class="text-red-600 dark:text-red-400">{{ $props['total_grams'] }}</span>
                </div>
            @endif
            
            @if(isset($props['total_trays']))
                <div>
                    <span class="font-medium text-red-700 dark:text-red-300">Total Trays:</span>
                    <span class="text-red-600 dark:text-red-400">{{ $props['total_trays'] }}</span>
                </div>
            @endif
        </div>
    </div>
</div>