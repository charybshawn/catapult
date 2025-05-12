<?php

namespace Database\Factories;

use App\Models\TaskSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskSchedule>
 */
class TaskScheduleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaskSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'resource_type' => 'crops', // Default to crops for our tests
            'task_name' => $this->faker->randomElement(['advance_to_blackout', 'advance_to_light', 'advance_to_harvested']), 
            'frequency' => 'once',
            'conditions' => function (array $attributes) { // Lazy state generation
                // Default conditions, can be overridden in tests
                return json_encode([
                    'crop_id' => $attributes['crop_id'] ?? null, // Use provided crop_id if available
                    'target_stage' => str_replace('advance_to_', '', $attributes['task_name'] ?? 'unknown'),
                    'tray_number' => $attributes['tray_number'] ?? $this->faker->randomNumber(3),
                    'variety' => $attributes['variety'] ?? $this->faker->word,
                ]);
            },
            'is_active' => true,
            'next_run_at' => Carbon::now()->addDays($this->faker->numberBetween(1, 10)),
            'last_run_at' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
