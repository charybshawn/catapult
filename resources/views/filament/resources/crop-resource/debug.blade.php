<div class="space-y-6">
    <div class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/20">
        <h3 class="text-lg font-bold text-blue-800 dark:text-blue-400 mb-3">Time-to-Next-Stage Calculation Algorithm</h3>
        <div class="mb-4">
            <h4 class="text-md font-medium text-blue-700 dark:text-blue-300 mb-2">Step-by-step calculation:</h4>
            <ol class="list-decimal list-inside space-y-2 text-sm text-blue-700 dark:text-blue-300">
                <li>Get current stage: <code class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded">{{ $crop->current_stage }}</code></li>
                <li>Get timestamp when stage started: <code class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded">{{ $stageStartTime ? $stageStartTime->format('Y-m-d H:i:s') : 'null' }}</code></li>
                <li>Get recipe's duration for this stage: <code class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded">{{ $stageDuration }} days</code></li>
                <li>Convert to hours: <code class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded">{{ $stageDuration }} days × 24 = {{ $hourDuration }} hours</code></li>
                <li>Calculate expected end date: <code class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded">{{ $stageStartTime ? $stageStartTime->format('Y-m-d H:i:s') : 'null' }} + {{ $hourDuration }} hours = {{ $expectedEndDate ? $expectedEndDate->format('Y-m-d H:i:s') : 'null' }}</code></li>
                <li>Calculate total stage time: <code class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded">{{ $totalStageDiff ? $totalStageDiff->format('%d days, %h hours, %i minutes') : 'null' }}</code></li>
                <li>Calculate progress: <code class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 rounded">{{ $elapsedHours }} hours elapsed out of {{ $totalHours }} hours = {{ $elapsedPercent }}%</code></li>
                <li>Display format: Total stage time with progress percentage</li>
            </ol>
        </div>
        
        <h4 class="text-md font-medium text-blue-700 dark:text-blue-300 mb-2">The algorithm as code:</h4>
        <pre class="p-4 bg-blue-100 dark:bg-blue-900/30 overflow-auto rounded-lg text-xs font-mono leading-5">
// 1. Skip if already harvested
if ($crop->current_stage === 'harvested') return '-';

// 2. Get recipe data for stage duration
$recipe = $crop->recipe;
if (!$recipe) return 'No recipe';

// 3. Get timestamp for when current stage started
$stageField = "{$crop->current_stage}_at";  // e.g. "germination_at"
$stageStartTime = $crop->$stageField;
if (!$stageStartTime) return 'Unknown';

// 4. Get duration for current stage from recipe 
$stageDuration = match ($crop->current_stage) {
    'germination' => $recipe->germination_days,  // Value: {{ $stageDuration ?? 'unknown' }}
    'blackout' => $recipe->blackout_days,
    'light' => $recipe->light_days,
    default => 0,
};

// 5. Skip calculation for blackout if duration is 0
if ($crop->current_stage === 'blackout' && $stageDuration === 0) return 'Skip blackout';

// 6. Calculate expected end date using hours for precision
$expectedEndDate = $stageStartTime->copy()->addHours($stageDuration * 24);

// 7. CHANGED: Calculate total stage duration (not remaining time)
$diff = $stageStartTime->diff($expectedEndDate);
$days = $diff->days;
$hours = $diff->h;
$minutes = $diff->i;

// 8. Calculate progress percentage
$now = now();
$elapsedPercent = 0;

if ($now->gt($stageStartTime)) {
    $totalDuration = $stageStartTime->diffInSeconds($expectedEndDate);
    $elapsed = $stageStartTime->diffInSeconds($now);
    $elapsedPercent = min(100, round(($elapsed / $totalDuration) * 100));
}

// 9. Format based on total stage time with progress
$timeDisplay = '';
if ($days > 0) {
    $timeDisplay = "{$days}d {$hours}h";
} elseif ($hours > 0) {
    $timeDisplay = "{$hours}h {$minutes}m";
} else {
    $timeDisplay = "{$minutes}m";
}

// 10. Add progress percentage
return $timeDisplay . " ({$elapsedPercent}%)";
        </pre>
    </div>

    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Stage Calculation Data</h3>
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($stage_data as $key => $value)
                        <tr>
                            <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                            <td class="whitespace-nowrap py-2 px-3 text-sm text-gray-500 dark:text-gray-400">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Recipe Data</h3>
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($recipe_data as $key => $value)
                        <tr>
                            <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                            <td class="whitespace-nowrap py-2 px-3 text-sm text-gray-500 dark:text-gray-400">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Crop Timestamps</h3>
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($timestamps as $key => $value)
                        <tr>
                            <td class="whitespace-nowrap py-2 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-white">{{ str_replace('_', ' ', ucfirst($key)) }}</td>
                            <td class="whitespace-nowrap py-2 px-3 text-sm text-gray-500 dark:text-gray-400">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-lg bg-amber-50 p-4 dark:bg-amber-900/20">
        <h3 class="text-lg font-bold text-amber-800 dark:text-amber-400 mb-1">Tip</h3>
        <p class="text-sm text-amber-700 dark:text-amber-300">
            If your "Time to Next Stage" isn't showing the expected number of days, use the command:<br>
            <code class="px-2 py-1 mt-1 inline-block bg-amber-100 dark:bg-amber-900/30 rounded font-mono text-xs">php artisan recipe:set-germination {{ $recipe->id ?? '1' }} 3.0</code><br>
            to adjust your recipe's germination period to 3 days.
        </p>
    </div>

    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3">Raw Data</h3>
        <pre class="p-4 bg-gray-100 dark:bg-gray-800 overflow-auto rounded-lg text-xs">
ID: {{ $crop->id }}
Tray Number: {{ $crop->tray_number }}
Current Stage: {{ $crop->current_stage }}

Recipe:
{{ print_r($recipe ? $recipe->toArray() : 'No recipe', true) }}
        </pre>
    </div>
</div>

<h2>Debug Information</h2>

<h3>Query Information</h3>
<div style="background: #f5f5f5; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; overflow: auto;">
    <strong>SQL:</strong> {{ $query['sql'] ?? 'No SQL' }}<br>
    <strong>Bindings:</strong> {{ json_encode($query['bindings'] ?? []) }}
</div>

<h3>Records</h3>
<div style="background: #f5f5f5; padding: 1rem; border-radius: 0.5rem; overflow: auto;">
    <pre>{{ json_encode($records ?? [], JSON_PRETTY_PRINT) }}</pre>
</div> 