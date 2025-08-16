<div class="space-y-6">
    {{-- Header --}}
    <div class="text-center">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $varietyName }}</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Batch #{{ $record->id }}</p>
    </div>

    {{-- Status and Details --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Current Stage</div>
            <div class="font-medium text-gray-900 dark:text-white">{{ $currentStage }}</div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Time in Stage</div>
            <div class="font-medium text-gray-900 dark:text-white">{{ $stageAge }}</div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Age</div>
            <div class="font-medium text-gray-900 dark:text-white">{{ $totalAge }}</div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tray Count</div>
            <div class="font-medium text-gray-900 dark:text-white">{{ $record->crops->count() }}</div>
        </div>
    </div>

    {{-- Recipe Information --}}
    @if($record->crops->first()?->recipe)
        @php
            $recipe = $record->crops->first()->recipe;
            $masterSeed = $recipe->masterSeedCatalog;
            $cultivar = $recipe->masterCultivar;
        @endphp
        <div class="space-y-3">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Recipe Details</div>
            
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Recipe</div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $recipe->name }}</div>
                </div>
                
                @if($masterSeed)
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Seed Type</div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $masterSeed->common_name }}</div>
                </div>
                @endif
                
                @if($cultivar)
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Cultivar</div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $cultivar->cultivar_name }}</div>
                </div>
                @endif
                
                <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                    <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Maturity Days</div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $recipe->days_to_maturity ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    @endif

    {{-- Soil Information --}}
    @if($record->crops->first()?->recipe?->soilConsumable)
        <div>
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Soil</div>
            <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                <div class="font-medium text-gray-900 dark:text-white">{{ $record->crops->first()->recipe->soilConsumable->item_name ?? 'Unknown Soil' }}</div>
            </div>
        </div>
    @endif

    {{-- Dates --}}
    <div class="space-y-2">
        @if($record->crops->first()?->germination_at)
            <div class="flex justify-between">
                <span class="text-sm text-gray-500 dark:text-gray-400">Started:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ \Carbon\Carbon::parse($record->crops->first()->germination_at)->format('M j, Y g:i A') }}
                </span>
            </div>
        @endif
        
        @if($record->recipe?->days_to_maturity)
            <div class="flex justify-between">
                <span class="text-sm text-gray-500 dark:text-gray-400">Expected Harvest:</span>
                <span class="text-sm font-medium text-gray-900 dark:text-white">
                    @if($record->crops->first()?->germination_at)
                        {{ \Carbon\Carbon::parse($record->crops->first()->germination_at)->addDays($record->recipe->days_to_maturity)->format('M j, Y') }}
                    @else
                        Not calculated
                    @endif
                </span>
            </div>
        @endif
    </div>

    {{-- Stage History --}}
    <div>
        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Stage History</div>
        @php
            $stageHistory = \App\Models\CropStageHistory::where('crop_batch_id', $record->id)
                ->with(['stage', 'createdBy'])
                ->orderBy('entered_at', 'asc')
                ->get()
                ->filter(function ($history) {
                    // Skip cancelled stages (entered and exited at the same time)
                    if ($history->exited_at && $history->entered_at->equalTo($history->exited_at)) {
                        return false;
                    }
                    return true;
                });
        @endphp
        
        @if($stageHistory->isEmpty())
            <div class="text-sm text-gray-500 dark:text-gray-400">No stage history available</div>
        @else
            <div class="space-y-3">
                @foreach($stageHistory as $history)
                    @php
                        $isActive = $history->is_active;
                        $bgColor = $isActive ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-800/50';
                    @endphp
                    <div class="p-3 rounded-lg {{ $bgColor }}">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $history->stage->name }}
                                    @if($isActive)
                                        <span class="text-sm text-blue-600 dark:text-blue-400">(Current)</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Entered: {{ $history->entered_at->format('M j, Y g:i A') }}
                                    @if($history->exited_at)
                                        <br>Exited: {{ $history->exited_at->format('M j, Y g:i A') }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $history->duration_display ?? 'In Progress' }}
                                </span>
                            </div>
                        </div>
                        
                        {{-- Notes (skip backfilled entries) --}}
                        @if($history->notes && !str_contains($history->notes, 'Batch-level stage history'))
                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                {{ $history->notes }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Debug Data (only show if debug mode is enabled) --}}
    @if(\App\Models\Setting::getValue('debug_mode_enabled', false))
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <div class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">🔧 Debug Information</div>
            
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg font-mono text-xs space-y-2">
                <div><strong>Crop Batch ID:</strong> {{ $record->id }}</div>
                <div><strong>Recipe ID:</strong> {{ $record->recipe_id ?? 'NULL' }}</div>
                <div><strong>Order ID:</strong> {{ $record->order_id ?? 'NULL' }}</div>
                <div><strong>Crop Plan ID:</strong> {{ $record->crop_plan_id ?? 'NULL' }}</div>
                <div><strong>Created:</strong> {{ $record->created_at }}</div>
                <div><strong>Updated:</strong> {{ $record->updated_at }}</div>
                
                @if($record->order)
                <div class="pt-2 border-t border-gray-300 dark:border-gray-600">
                    <strong>Order:</strong> ID {{ $record->order->id }}, Status: {{ $record->order->status ?? 'N/A' }}
                </div>
                @endif
                
                @if($record->cropPlan)
                <div class="pt-2 border-t border-gray-300 dark:border-gray-600">
                    <strong>Crop Plan:</strong> ID {{ $record->cropPlan->id }}, Name: {{ $record->cropPlan->name ?? 'Unnamed Plan' }}
                </div>
                @endif
                
                <div class="pt-2 border-t border-gray-300 dark:border-gray-600">
                    <strong>Crops Count:</strong> {{ $record->crops->count() }}
                </div>
            </div>
        </div>
    @endif
</div>