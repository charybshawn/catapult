<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Crop;
use App\Models\Recipe;
use App\Services\CropStageTimelineService;
use Carbon\Carbon;

echo "=== CROP TIME CALCULATION DEBUG ===\n\n";

// Get a recent crop
$crop = Crop::orderBy('id', 'desc')->first();

if (!$crop) {
    echo "No crops found in database\n";
    exit;
}

echo "Crop ID: {$crop->id}\n";
echo "Tray Number: {$crop->tray_number}\n";
echo "Recipe ID: {$crop->recipe_id}\n";
echo "Current Stage ID: {$crop->current_stage_id}\n";
echo "Planting At: " . ($crop->planting_at ? $crop->planting_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "Germination At: " . ($crop->germination_at ? $crop->germination_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "Blackout At: " . ($crop->blackout_at ? $crop->blackout_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "Light At: " . ($crop->light_at ? $crop->light_at->format('Y-m-d H:i:s') : 'NULL') . "\n";
echo "Harvested At: " . ($crop->harvested_at ? $crop->harvested_at->format('Y-m-d H:i:s') : 'NULL') . "\n";

echo "\n=== STORED TIME CALCULATIONS ===\n";
echo "Stage Age Minutes: " . ($crop->stage_age_minutes ?: 'NULL') . "\n";
echo "Stage Age Display: " . ($crop->stage_age_display ?: 'NULL') . "\n";
echo "Time to Next Stage Minutes: " . ($crop->time_to_next_stage_minutes ?: 'NULL') . "\n";
echo "Time to Next Stage Display: " . ($crop->time_to_next_stage_display ?: 'NULL') . "\n";
echo "Total Age Minutes: " . ($crop->total_age_minutes ?: 'NULL') . "\n";
echo "Total Age Display: " . ($crop->total_age_display ?: 'NULL') . "\n";

echo "\n=== MANUAL TIME CALCULATIONS ===\n";
$now = Carbon::now();
echo "Current Time: " . $now->format('Y-m-d H:i:s') . "\n";

// Load current stage
$crop->load('currentStage');
if ($crop->currentStage) {
    echo "Current Stage: {$crop->currentStage->name} ({$crop->currentStage->code})\n";
    
    // Calculate stage age based on the appropriate timestamp
    $stageStartTime = null;
    switch ($crop->currentStage->code) {
        case 'soaking':
            $stageStartTime = $crop->soaking_at ?: $crop->planting_at;
            break;
        case 'germination':
            $stageStartTime = $crop->germination_at ?: $crop->planting_at;
            break;
        case 'blackout':
            $stageStartTime = $crop->blackout_at;
            break;
        case 'light':
            $stageStartTime = $crop->light_at;
            break;
        case 'harvested':
            $stageStartTime = $crop->harvested_at;
            break;
        default:
            $stageStartTime = $crop->planting_at;
    }
    
    if ($stageStartTime) {
        $stageAgeMinutes = $stageStartTime->diffInMinutes($now);
        $stageAgeHours = floor($stageAgeMinutes / 60);
        $stageAgeRemainingMinutes = $stageAgeMinutes % 60;
        echo "Calculated Stage Age: {$stageAgeMinutes} minutes ({$stageAgeHours}h {$stageAgeRemainingMinutes}m)\n";
    } else {
        echo "No start time found for current stage\n";
    }
}

// Calculate total age
if ($crop->planting_at) {
    $totalAgeMinutes = $crop->planting_at->diffInMinutes($now);
    $totalAgeDays = floor($totalAgeMinutes / (60 * 24));
    $totalAgeHours = floor(($totalAgeMinutes % (60 * 24)) / 60);
    $totalAgeRemainingMinutes = $totalAgeMinutes % 60;
    echo "Calculated Total Age: {$totalAgeMinutes} minutes ({$totalAgeDays}d {$totalAgeHours}h {$totalAgeRemainingMinutes}m)\n";
}

// Test the timeline service
echo "\n=== TIMELINE SERVICE TEST ===\n";
$timelineService = app(CropStageTimelineService::class);
$timeline = $timelineService->generateTimeline($crop);

foreach ($timeline as $stageCode => $stage) {
    echo "{$stage['name']}: {$stage['status']}";
    if (isset($stage['duration']) && $stage['duration']) {
        echo " ({$stage['duration']})";
    }
    echo "\n";
}

// Test recipe data
echo "\n=== RECIPE DATA ===\n";
$recipe = Recipe::find($crop->recipe_id);
if ($recipe) {
    echo "Recipe Name: {$recipe->name}\n";
    echo "Germination Days: {$recipe->germination_days}\n";
    echo "Blackout Days: {$recipe->blackout_days}\n";
    echo "Light Days: {$recipe->light_days}\n";
    echo "Days to Maturity: {$recipe->days_to_maturity}\n";
} else {
    echo "Recipe not found\n";
}