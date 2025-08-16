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
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Stage History Debug</h3>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-4">
            
            {{-- Stage History Query Result --}}
            <div>
                <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Stage History Query (crop_batch_id = {{ $record->id }})</h4>
                @if($stageHistory && $stageHistory->count() > 0)
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded p-3">
                        <p class="text-green-800 dark:text-green-200 text-sm mb-2">Found {{ $stageHistory->count() }} stage history records:</p>
                        <div class="space-y-2">
                            @foreach($stageHistory as $history)
                                <div class="text-xs bg-white dark:bg-gray-700 p-2 rounded border">
                                    <div class="grid grid-cols-2 gap-2">
                                        <span><strong>ID:</strong> {{ $history->id }}</span>
                                        <span><strong>Stage:</strong> {{ $history->stage->name ?? 'N/A' }}</span>
                                        <span><strong>Crop ID:</strong> {{ $history->crop_id }}</span>
                                        <span><strong>Batch ID:</strong> {{ $history->crop_batch_id }}</span>
                                        <span><strong>Entered:</strong> {{ $history->entered_at }}</span>
                                        <span><strong>Exited:</strong> {{ $history->exited_at ?? 'Current' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3">
                        <p class="text-red-800 dark:text-red-200 text-sm">❌ No stage history records found for crop_batch_id = {{ $record->id }}</p>
                    </div>
                @endif
            </div>

            {{-- Alternative Query by Crop ID --}}
            @if($firstCrop)
                <div>
                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Alternative Query (crop_id = {{ $firstCrop->id }})</h4>
                    @if($stageHistoryByCrop && $stageHistoryByCrop->count() > 0)
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded p-3">
                            <p class="text-green-800 dark:text-green-200 text-sm mb-2">Found {{ $stageHistoryByCrop->count() }} stage history records by crop_id:</p>
                            <div class="space-y-2">
                                @foreach($stageHistoryByCrop as $history)
                                    <div class="text-xs bg-white dark:bg-gray-700 p-2 rounded border">
                                        <div class="grid grid-cols-2 gap-2">
                                            <span><strong>ID:</strong> {{ $history->id }}</span>
                                            <span><strong>Stage:</strong> {{ $history->stage->name ?? 'N/A' }}</span>
                                            <span><strong>Crop ID:</strong> {{ $history->crop_id }}</span>
                                            <span><strong>Batch ID:</strong> {{ $history->crop_batch_id }}</span>
                                            <span><strong>Entered:</strong> {{ $history->entered_at }}</span>
                                            <span><strong>Exited:</strong> {{ $history->exited_at ?? 'Current' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3">
                            <p class="text-red-800 dark:text-red-200 text-sm">❌ No stage history records found for crop_id = {{ $firstCrop->id }}</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- All Stage History for All Crops in Batch --}}
            <div>
                <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">All Crops in Batch</h4>
                @if($allCropsInBatch && $allCropsInBatch->count() > 0)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-3">
                        <p class="text-blue-800 dark:text-blue-200 text-sm mb-2">Found {{ $allCropsInBatch->count() }} crops in batch:</p>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            @foreach($allCropsInBatch as $crop)
                                <div class="bg-white dark:bg-gray-700 p-2 rounded">
                                    <strong>Crop ID {{ $crop->id }}:</strong> Tray {{ $crop->tray_number }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Comprehensive Stage History Search --}}
            <div>
                <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">All Stage History for Batch Crops</h4>
                @if($allStageHistoryForBatch && $allStageHistoryForBatch->count() > 0)
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded p-3">
                        <p class="text-green-800 dark:text-green-200 text-sm mb-2">Found {{ $allStageHistoryForBatch->count() }} stage history records for crops in this batch:</p>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($allStageHistoryForBatch as $history)
                                <div class="text-xs bg-white dark:bg-gray-700 p-2 rounded border">
                                    <div class="grid grid-cols-3 gap-2">
                                        <span><strong>History ID:</strong> {{ $history->id }}</span>
                                        <span><strong>Stage:</strong> {{ $history->stage->name ?? 'N/A' }}</span>
                                        <span><strong>Active:</strong> {{ $history->is_active ? 'Yes' : 'No' }}</span>
                                        <span><strong>Crop ID:</strong> {{ $history->crop_id }}</span>
                                        <span><strong>Batch ID:</strong> {{ $history->crop_batch_id ?? 'NULL' }}</span>
                                        <span><strong>Created By:</strong> {{ $history->createdBy->name ?? 'N/A' }}</span>
                                        <span><strong>Entered:</strong> {{ $history->entered_at }}</span>
                                        <span><strong>Exited:</strong> {{ $history->exited_at ?? 'NULL' }}</span>
                                        <span><strong>Duration:</strong> {{ $history->duration_display ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded p-3">
                        <p class="text-red-800 dark:text-red-200 text-sm">❌ No stage history records found for any crops in this batch</p>
                        <p class="text-red-600 dark:text-red-300 text-xs mt-2">
                            This suggests that stage history records are either:
                            <br>• Not being created when crops advance stages
                            <br>• Being stored with incorrect crop_batch_id values
                            <br>• Being deleted or not persisted properly
                        </p>
                    </div>
                @endif
            </div>

            {{-- Original Stage History Display (if found) --}}
            @if($stageHistory && $stageHistory->count() > 0)
                <div>
                    <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Stage History Timeline</h4>
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
            @endif
        </div>
    </div>
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

    {{-- Database Queries for Troubleshooting --}}
    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Database Troubleshooting</h3>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="space-y-3 text-sm">
                @php
                    // Total stage history records in database
                    $totalStageHistory = \App\Models\CropStageHistory::count();
                    
                    // Stage history records with our batch ID
                    $batchStageHistory = \App\Models\CropStageHistory::where("crop_batch_id", $record->id)->count();
                    
                    // Null crop_batch_id records
                    $nullBatchIdHistory = \App\Models\CropStageHistory::whereNull("crop_batch_id")->count();
                    
                    // Most recent stage history records (top 5)
                    $recentHistory = \App\Models\CropStageHistory::with(["stage", "crop"])
                        ->orderBy("created_at", "desc")
                        ->limit(5)
                        ->get();
                        
                    // Check if recipe name contains "Peas"
                    $isTargetRecord = str_contains(strtolower($record->recipe_name ?? ""), "peas");
                @endphp
                
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Database Statistics</h4>
                        <ul class="space-y-1 text-sm">
                            <li>• Total stage history records: <strong>{{ $totalStageHistory }}</strong></li>
                            <li>• Records for this batch: <strong>{{ $batchStageHistory }}</strong></li>
                            <li>• Records with NULL batch_id: <strong>{{ $nullBatchIdHistory }}</strong></li>
                            <li class="{{ $isTargetRecord ? "text-red-600 font-bold" : "" }}">
                                • Target "Peas" record: <strong>{{ $isTargetRecord ? "YES - This is it!" : "No" }}</strong>
                            </li>
                        </ul>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-800 dark:text-gray-200 mb-2">Recent Stage History (Last 5)</h4>
                        <ul class="space-y-1 text-sm">
                            @forelse($recentHistory as $recent)
                                <li>• Crop {{ $recent->crop_id }} → {{ $recent->stage->name ?? "Unknown" }} 
                                    <span class="text-gray-500">({{ $recent->created_at->diffForHumans() }})</span>
                                </li>
                            @empty
                                <li>• No recent stage history found</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
                
                @if($isTargetRecord && $batchStageHistory === 0)
                    <div class="mt-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-300 dark:border-red-700 rounded">
                        <h5 class="font-bold text-red-800 dark:text-red-200">🎯 TARGET RECORD FOUND!</h5>
                        <p class="text-red-700 dark:text-red-300 mt-1">
                            This is the "Peas(Speckled) - 300g" record that has no stage history. 
                            The crops in this batch may have been created before stage history tracking was implemented,
                            or there was an issue during stage transitions.
                        </p>
                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">
                            <strong>Potential Solutions:</strong>
                            <ul class="list-disc list-inside mt-1">
                                <li>Check if crops have been advancing stages normally</li>
                                <li>Verify stage history is being created on stage transitions</li>
                                <li>Consider manually creating stage history records for this batch</li>
                                <li>Run a migration to backfill stage history for existing crops</li>
                            </ul>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>