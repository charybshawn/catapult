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
        
        $taskType = $this->faker->randomElement(['end_germination', 'end_blackout', 'suspend_watering', 'expected_harvest']);
        $details = null;
        if ($taskType === 'end_germination') {
            $details = ['target_stage' => 'blackout'];
        }
        if ($taskType === 'end_blackout') {
            $details = ['target_stage' => 'light'];
        }

        return [
            'crop_id' => $crop->id,
            'recipe_id' => $recipe->id,
            'task_type' => $taskType,
            'details' => $details, // Automatically encodes to JSON
            'scheduled_at' => Carbon::instance($this->faker->dateTimeBetween('-1 week', '+1 week')),
            'triggered_at' => null,
            'status' => 'pending',
        ];
    }
}
