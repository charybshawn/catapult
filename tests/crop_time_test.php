<?php

require_once __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get a list of all active crops
$crops = \App\Models\Crop::whereNull('harvested_at')
    ->with('recipe')
    ->get();

echo "====================\n";
echo "CROP TIME CALCULATIONS TEST\n";
echo "====================\n\n";

echo 'Current time: '.now()->format('Y-m-d H:i:s')."\n\n";

foreach ($crops as $crop) {
    echo "CROP #{$crop->id} - Tray {$crop->tray_number}\n";
    echo "Recipe: {$crop->recipe->name}\n";
    echo "Current Stage: {$crop->current_stage}\n";
    echo "Planted at: {$crop->germinated_at->format('Y-m-d H:i:s')}\n";
    echo 'Time since planting: '.formatTimeDiff($crop->germinated_at, now())."\n";

    // Show stage timestamps
    echo 'Germination at: '.($crop->germination_at ? $crop->germination_at->format('Y-m-d H:i:s') : 'N/A')."\n";
    echo 'Blackout at: '.($crop->blackout_at ? $crop->blackout_at->format('Y-m-d H:i:s') : 'N/A')."\n";
    echo 'Light at: '.($crop->light_at ? $crop->light_at->format('Y-m-d H:i:s') : 'N/A')."\n";

    // Calculate current stage duration
    $stageField = "{$crop->current_stage}_at";
    if ($crop->$stageField) {
        echo 'Time in current stage: '.formatTimeDiff($crop->$stageField, now())."\n";
    }

    // Recipe stage durations
    echo "Recipe Stage Durations:\n";
    echo "- Germination: {$crop->recipe->germination_days} days\n";
    echo "- Blackout: {$crop->recipe->blackout_days} days\n";
    echo "- Light: {$crop->recipe->light_days} days\n";
    echo '- Total: '.($crop->recipe->germination_days + $crop->recipe->blackout_days + $crop->recipe->light_days)." days\n";

    // Get expected harvest date
    $expectedHarvestDate = $crop->expectedHarvestDate();
    echo 'Expected harvest date: '.($expectedHarvestDate ? $expectedHarvestDate->format('Y-m-d H:i:s') : 'N/A')."\n";

    // Calculate time from plant to harvest
    if ($expectedHarvestDate) {
        $plantToHarvest = $crop->germinated_at->diffInDays($expectedHarvestDate);
        echo "Days from plant to harvest: {$plantToHarvest} days\n";
    }

    // Detailed calculations
    echo "\nDETAILED CALCULATIONS:\n";

    // Time to next stage (system)
    echo "System time to next stage: {$crop->timeToNextStage()}\n";

    // Manual time to next stage (stage-based)
    $manualStage = calculateStageTimeRemaining($crop);
    echo "Manual time to stage end: {$manualStage}\n";

    // Manual time to harvest
    $manualHarvest = calculateTimeToHarvest($crop);
    echo "Manual time to harvest: {$manualHarvest}\n";

    // Look at system code
    $codeResult = inspectTimeToNextStageCode($crop);
    echo "System code inspection: {$codeResult}\n";

    echo "\n------------------------------\n\n";
}

function formatTimeDiff($start, $end)
{
    $diff = $start->diff($end);

    return "{$diff->days}d {$diff->h}h {$diff->i}m";
}

function calculateStageTimeRemaining($crop)
{
    // Skip if already harvested
    if ($crop->current_stage === 'harvested') {
        return '-';
    }

    if (! $crop->recipe) {
        return 'No recipe';
    }

    // Get stage start time
    $stageField = "{$crop->current_stage}_at";
    $stageStartTime = $crop->$stageField;
    if (! $stageStartTime) {
        return 'Unknown';
    }

    // Get stage duration
    $stageDuration = match ($crop->current_stage) {
        'germination' => $crop->recipe->germination_days,
        'blackout' => $crop->recipe->blackout_days,
        'light' => $crop->recipe->light_days,
        default => 0
    };

    // Calculate expected stage end
    $stageEndDate = $stageStartTime->copy()->addSeconds($stageDuration * 86400);

    // Calculate time difference
    $now = now();
    $diff = $now->diff($stageEndDate);

    // Format output
    $days = (int) $diff->format('%a');
    $hours = $diff->h;
    $minutes = $diff->i;

    return "{$days}d {$hours}h {$minutes}m";
}

function calculateTimeToHarvest($crop)
{
    $expectedHarvestDate = $crop->expectedHarvestDate();
    if (! $expectedHarvestDate) {
        return 'N/A';
    }

    $now = now();
    $diff = $now->diff($expectedHarvestDate);

    // Format output
    $days = (int) $diff->format('%a');
    $hours = $diff->h;
    $minutes = $diff->i;

    return "{$days}d {$hours}h {$minutes}m";
}

function inspectTimeToNextStageCode($crop)
{
    // Get expected harvest date from recipe method
    $expectedHarvestDate = $crop->expectedHarvestDate();

    if (! $expectedHarvestDate) {
        return 'Unknown';
    }

    $now = now();
    if ($now->gt($expectedHarvestDate)) {
        return 'Ready to advance';
    }

    // Calculate using diff
    $diff = $now->diff($expectedHarvestDate);
    $days = (int) $diff->format('%a');
    $hours = $diff->h;
    $minutes = $diff->i;

    // Format based on remaining time
    if ($days > 0) {
        return "{$days}d {$hours}h";
    } elseif ($hours > 0) {
        return "{$hours}h {$minutes}m";
    } else {
        return "{$minutes}m";
    }
}
