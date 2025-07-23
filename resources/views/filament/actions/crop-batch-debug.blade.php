<div class="space-y-6">
    {{-- Batch Information --}}
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Batch Information</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Batch ID:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->id }}</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Crop Count:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->crop_count }}</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Tray Numbers:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ implode(', ', $record->tray_numbers_array ?? []) }}</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Recipe:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->recipe_name ?? 'Unknown' }}</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Current Stage:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->current_stage_name }} (ID: {{ $record->current_stage_id ?? 'N/A' }})</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Created At:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->created_at ? (is_string($record->created_at) ? $record->created_at : $record->created_at->format('Y-m-d H:i:s')) : 'N/A' }}</span>
            </div>
        </div>
    </div>

    {{-- Stage Timestamps --}}
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Stage Timestamps</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Soaking At:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->soaking_at ? (is_string($record->soaking_at) ? $record->soaking_at : $record->soaking_at->format('Y-m-d H:i:s')) : 'N/A' }}</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Germination At:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->germination_at ? (is_string($record->germination_at) ? $record->germination_at : $record->germination_at->format('Y-m-d H:i:s')) : 'N/A' }}</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Blackout At:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->blackout_at ? (is_string($record->blackout_at) ? $record->blackout_at : $record->blackout_at->format('Y-m-d H:i:s')) : 'N/A' }}</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Light At:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->light_at ? (is_string($record->light_at) ? $record->light_at : $record->light_at->format('Y-m-d H:i:s')) : 'N/A' }}</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Harvested At:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->harvested_at ? (is_string($record->harvested_at) ? $record->harvested_at : $record->harvested_at->format('Y-m-d H:i:s')) : 'N/A' }}</span>
            </div>
            <div class="flex items-start">
                <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Expected Harvest:</span>
                <span class="text-gray-600 dark:text-gray-400">{{ $record->expected_harvest_at ? (is_string($record->expected_harvest_at) ? $record->expected_harvest_at : $record->expected_harvest_at->format('Y-m-d H:i:s')) : 'N/A' }}</span>
            </div>
        </div>
    </div>

    {{-- Recipe Data --}}
    @if($recipe)
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Recipe Data</h3>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2 max-h-64 overflow-y-auto">
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Recipe Name:</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->name }}</span>
                </div>
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Variety:</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->common_name }} - {{ $recipe->cultivar_name ?? 'N/A' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Lot Number:</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->lot_number ?? 'N/A' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Germination Days:</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->germination_days ?? 'N/A' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Blackout Days:</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->blackout_days ?? 'N/A' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Light Days:</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->light_days ?? 'N/A' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Days to Maturity:</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->days_to_maturity ?? 'N/A' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Seed Soak Hours:</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->seed_soak_hours ?? 'N/A' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Requires Soaking:</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->requires_soaking ? 'Yes' : 'No' }}</span>
                </div>
                <div class="flex items-start">
                    <span class="font-medium text-gray-700 dark:text-gray-300 w-40 flex-shrink-0">Expected Yield (g):</span>
                    <span class="text-gray-600 dark:text-gray-400">{{ $recipe->expected_yield_grams ?? 'N/A' }}</span>
                </div>
            </div>
        </div>
    @else
        <div class="text-gray-500 dark:text-gray-400">Recipe not found</div>
    @endif

    {{-- Stage History Timeline --}}
    @if($stageHistory && $stageHistory->count() > 0)
        <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Stage History</h3>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="space-y-3">
                    @foreach($stageHistory as $history)
                        <div class="flex items-start pb-3 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                            <div class="flex-shrink-0 mr-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium
                                    {{ $history->is_active ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                    {{ $loop->iteration }}
                                </div>
                            </div>
                            <div class="flex-grow">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900 dark:text-gray-100">{{ $history->stage->name }}</h4>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            <span>Entered: {{ $history->entered_at->format('M j, Y g:i A') }}</span>
                                            @if($history->exited_at)
                                                <span class="ml-3">Exited: {{ $history->exited_at->format('M j, Y g:i A') }}</span>
                                            @else
                                                <span class="ml-3 text-blue-600 dark:text-blue-400">Current Stage</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ $history->duration_display ?? 'In Progress' }}
                                        </span>
                                    </div>
                                </div>
                                @if($history->notes)
                                    <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                        {{ $history->notes }}
                                    </div>
                                @endif
                                @if($history->createdBy)
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        By: {{ $history->createdBy->name }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Time Calculations --}}
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Time Calculations</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-4">
            {{-- Current Stage Age --}}
            <div>
                <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Current Stage Age</h4>
                <div class="pl-4 space-y-1">
                    <div class="flex items-start">
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Display Value:</span>
                        <span class="text-sm text-gray-500 dark:text-gray-500">{{ $record->stage_age_display ?? 'Unknown' }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Minutes:</span>
                        <span class="text-sm text-gray-500 dark:text-gray-500">{{ $record->stage_age_minutes ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            {{-- Time to Next Stage --}}
            <div>
                <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Time to Next Stage</h4>
                <div class="pl-4 space-y-1">
                    <div class="flex items-start">
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Display Value:</span>
                        <span class="text-sm text-gray-500 dark:text-gray-500">{{ $record->time_to_next_stage_display ?? 'Unknown' }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Minutes:</span>
                        <span class="text-sm text-gray-500 dark:text-gray-500">{{ $record->time_to_next_stage_minutes ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            {{-- Total Crop Age --}}
            <div>
                <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Total Crop Age</h4>
                <div class="pl-4 space-y-1">
                    <div class="flex items-start">
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Display Value:</span>
                        <span class="text-sm text-gray-500 dark:text-gray-500">{{ $record->total_age_display ?? 'Unknown' }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Minutes:</span>
                        <span class="text-sm text-gray-500 dark:text-gray-500">{{ $record->total_age_minutes ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            {{-- Stage Timeline --}}
            @if($firstCrop)
                @php
                    $timelineService = app(\App\Services\CropStageTimelineService::class);
                    $timeline = $timelineService->generateTimeline($firstCrop);
                @endphp
                <div>
                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Stage Timeline</h4>
                    <div class="pl-4 space-y-1">
                        @foreach($timeline as $stageCode => $stage)
                            <div class="flex items-start">
                                <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">{{ $stage['name'] }}:</span>
                                <span class="text-sm text-gray-500 dark:text-gray-500">
                                    {{ ucfirst($stage['status'] ?? 'unknown') }}
                                    @if(($stage['duration'] ?? 'N/A') !== 'N/A' && $stage['duration'])
                                        ({{ $stage['duration'] }})
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Crop Debug Details --}}
                <div>
                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">--- CROP DEBUG ---</h4>
                    <div class="pl-4 space-y-1">
                        <div class="flex items-start">
                            <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Crop ID:</span>
                            <span class="text-sm text-gray-500 dark:text-gray-500">{{ $firstCrop->id }}</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Current Stage ID:</span>
                            <span class="text-sm text-gray-500 dark:text-gray-500">{{ $firstCrop->current_stage_id }}</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Current Stage Code:</span>
                            <span class="text-sm text-gray-500 dark:text-gray-500">{{ $firstCrop->currentStage?->code ?? 'NULL' }}</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Germination At:</span>
                            <span class="text-sm text-gray-500 dark:text-gray-500">{{ $firstCrop->germination_at ? (is_string($firstCrop->germination_at) ? $firstCrop->germination_at : $firstCrop->germination_at->format('Y-m-d H:i:s')) : 'NULL' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Next Stage Info --}}
            @if($recipe && $record->current_stage_code !== 'harvested')
                @php
                    $nextStage = match($record->current_stage_code) {
                        'soaking' => 'germination',
                        'germination' => 'blackout',
                        'blackout' => 'light',
                        'light' => 'harvested',
                        default => null
                    };
                @endphp
                @if($nextStage)
                    <div>
                        <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Next Stage Info</h4>
                        <div class="pl-4 space-y-1">
                            <div class="flex items-start">
                                <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Current Stage:</span>
                                <span class="text-sm text-gray-500 dark:text-gray-500">{{ $record->current_stage_name }}</span>
                            </div>
                            <div class="flex items-start">
                                <span class="text-sm text-gray-600 dark:text-gray-400 w-32 flex-shrink-0">Next Stage:</span>
                                <span class="text-sm text-gray-500 dark:text-gray-500">{{ $nextStage }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>