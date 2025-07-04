<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\CropTask;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CropTask>
 */
class CropTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CropTask::class;
    
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Need Crop and Recipe first
        $crop = Crop::factory()->create(); 
        $recipe = $crop->recipe; // Assuming Crop factory sets up recipe
        
        // Get task type and status from lookup tables
        $taskTypes = \App\Models\CropTaskType::pluck('id')->toArray();
        $pendingStatus = \App\Models\CropTaskStatus::where('code', 'pending')->first();
        
        $details = $this->faker->randomElement([
            null,
            ['notes' => $this->faker->sentence()],
            ['target_stage' => $this->faker->randomElement(['blackout', 'light', 'harvest'])],
        ]);

        return [
            'crop_id' => $crop->id,
            'recipe_id' => $recipe->id,
            'crop_task_type_id' => $this->faker->randomElement($taskTypes),
            'crop_task_status_id' => $pendingStatus?->id ?? 1,
            'details' => $details, // Automatically encodes to JSON
            'scheduled_at' => Carbon::instance($this->faker->dateTimeBetween('-1 week', '+1 week')),
            'triggered_at' => null,
        ];
    }
}
