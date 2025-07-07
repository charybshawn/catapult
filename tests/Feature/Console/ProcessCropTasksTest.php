<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Recipe;
use App\Models\Crop;
use App\Models\TaskSchedule;
use App\Models\User;
use App\Models\Consumable;
use App\Models\SeedEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Console\Commands\ProcessCropTasks;
use Illuminate\Support\Facades\Artisan;

class ProcessCropTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Restore time mocking
        Carbon::setTestNow(Carbon::parse('2024-05-10 12:00:00'));
        
        // Create Admin role if it doesn't exist
        if (!\Spatie\Permission\Models\Role::where('name', 'Admin')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'Admin']);
        }
        
        // Seed the CropStage lookup data
        $this->seed(\Database\Seeders\Lookup\CropStageSeeder::class);
        
        User::factory()->create();
        Consumable::factory()->count(2)->create(['type' => 'seed']);
        Consumable::factory()->count(2)->create(['type' => 'soil']);
        SeedEntry::factory()->create();
    }

    protected function tearDown(): void
    {
        // Restore time mocking reset
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function it_processes_pending_suspend_watering_task(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::first();
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planting_at' => Carbon::now()->subDays(5),
        ]);

        // Create a suspend watering task that's due
        $task = TaskSchedule::create([
            'name' => 'Test Suspend Watering Task',
            'resource_type' => 'crops',
            'task_name' => 'suspend_watering',
            'frequency' => 'once',
            'schedule_config' => [],
            'conditions' => [
                'crop_id' => $crop->id,
                'task_type' => 'suspend_watering'
            ],
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinute(), // Due 1 minute ago
        ]);

        // Set up admin role for the user
        $user->assignRole('Admin');

        // Mock notifications
        Notification::fake();

        // Run the command
        $this->artisan('app:process-task-schedules', ['--type' => 'crops'])
            ->assertExitCode(0);

        // Assert notification was sent
        Notification::assertSentTo(
            $user,
            \App\Notifications\CropTaskActionDue::class,
            function ($notification, $channels) use ($task, $crop) {
                return $notification->task->id === $task->id
                    && $notification->crop->id === $crop->id;
            }
        );

        // Task should still be active (waiting for manual action)
        $task->refresh();
        $this->assertTrue($task->is_active);
    }

    /** @test */
    public function it_skips_tasks_with_missing_crop(): void
    {
        // Create a task with non-existent crop_id
        $task = TaskSchedule::create([
            'name' => 'Test Missing Crop Task',
            'resource_type' => 'crops',
            'task_name' => 'advance_to_light',
            'frequency' => 'once',
            'schedule_config' => [],
            'conditions' => [
                'crop_id' => 999999, // Non-existent
                'task_type' => 'end_germination'
            ],
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinute(),
        ]);

        $user = User::first();
        $user->assignRole('Admin');

        Notification::fake();

        // Run the command
        $this->artisan('app:process-task-schedules', ['--type' => 'crops'])
            ->assertExitCode(0);

        // No notifications should be sent
        Notification::assertNothingSent();

        // Task should be marked inactive
        $task->refresh();
        $this->assertFalse($task->is_active);
    }

    /** @test */
    public function it_only_processes_due_tasks(): void
    {
        $recipe = Recipe::factory()->create();
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planting_at' => Carbon::now()->subDays(5),
        ]);

        // Create a task that's not due yet
        $futureTask = TaskSchedule::create([
            'name' => 'Test Future Task',
            'resource_type' => 'crops',
            'task_name' => 'advance_to_harvested',
            'frequency' => 'once',
            'schedule_config' => [],
            'conditions' => [
                'crop_id' => $crop->id,
                'task_type' => 'expected_harvest'
            ],
            'is_active' => true,
            'next_run_at' => Carbon::now()->addHour(), // Due in 1 hour
        ]);

        // Create a task that's due
        $dueTask = TaskSchedule::create([
            'name' => 'Test Due Task',
            'resource_type' => 'crops',
            'task_name' => 'advance_to_light',
            'frequency' => 'once',
            'schedule_config' => [],
            'conditions' => [
                'crop_id' => $crop->id,
                'task_type' => 'end_germination'
            ],
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinute(), // Due 1 minute ago
        ]);

        $user = User::first();
        $user->assignRole('Admin');

        Notification::fake();

        // Run the command
        $this->artisan('app:process-task-schedules', ['--type' => 'crops'])
            ->assertExitCode(0);

        // Only one notification should be sent
        Notification::assertCount(1);
        
        // Assert the due task was processed
        Notification::assertSentTo(
            $user,
            \App\Notifications\CropTaskActionDue::class,
            function ($notification) use ($dueTask) {
                return $notification->task->id === $dueTask->id;
            }
        );
    }

    /** @test */
    public function it_handles_no_admin_users_gracefully(): void
    {
        $recipe = Recipe::factory()->create();
        $crop = Crop::factory()->create([
            'recipe_id' => $recipe->id,
            'planting_at' => Carbon::now()->subDays(5),
        ]);

        // Create a due task
        $task = TaskSchedule::create([
            'name' => 'Test Handler Task',
            'resource_type' => 'crops',
            'task_name' => 'advance_to_light',
            'frequency' => 'once',
            'schedule_config' => [],
            'conditions' => [
                'crop_id' => $crop->id,
                'task_type' => 'end_germination'
            ],
            'is_active' => true,
            'next_run_at' => Carbon::now()->subMinute(),
        ]);

        // Don't assign admin role to any user
        Notification::fake();

        // Run the command - should not throw exception
        $this->artisan('app:process-task-schedules', ['--type' => 'crops'])
            ->assertExitCode(0);

        // No notifications should be sent
        Notification::assertNothingSent();
    }
}