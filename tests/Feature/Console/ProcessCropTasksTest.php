<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Recipe;
use App\Models\Crop;
use App\Models\CropTask;
use App\Models\User;
use App\Models\Consumable;
use App\Models\SeedVariety;
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
        User::factory()->create();
        Consumable::factory()->count(2)->create(['type' => 'seed']);
        Consumable::factory()->count(2)->create(['type' => 'soil']);
        SeedVariety::factory()->create();
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
            'planted_at' => Carbon::now()->subDays(5),
            'watering_suspended_at' => null,
        ]);
        $task = CropTask::factory()->create([
            'crop_id' => $crop->id,
            'recipe_id' => $recipe->id,
            'task_type' => 'suspend_watering',
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => 'pending',
        ]);
        $taskId = $task->id;

        // Assert initial state
        $this->assertDatabaseHas('crop_tasks', ['id' => $taskId, 'status' => 'pending']);
        $this->assertNull($crop->watering_suspended_at);

        // Run command
        Artisan::call('app:process-crop-tasks');

        // Assert task status is updated
        $this->assertDatabaseHas('crop_tasks', [
            'id' => $taskId,
            'status' => 'triggered',
            'triggered_at' => Carbon::now()->toDateTimeString(),
        ]);

        // Assert crop watering was suspended
        $crop->refresh();
        $this->assertNotNull($crop->watering_suspended_at);

        // Assert notification exists in the database for the user
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => \Filament\Notifications\DatabaseNotification::class,
        ]);
        $notification = DB::table('notifications')
                         ->where('notifiable_id', $user->id)
                         ->latest('created_at')
                         ->first();
        $this->assertNotNull($notification, 'Notification not found in database.');
        $notificationData = json_decode($notification->data, true);
        $this->assertEquals('Watering Suspended', $notificationData['title']);
        $this->assertStringContainsString($task->recipe->name, $notificationData['body']);
    }

    /** @test */
    public function it_processes_pending_stage_change_notification_task(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::first();
        $crop = Crop::factory()->create(['recipe_id' => $recipe->id, 'planted_at' => Carbon::now()->subDays(3)]);
        $task = CropTask::factory()->create([
            'crop_id' => $crop->id,
            'recipe_id' => $recipe->id,
            'task_type' => 'end_germination',
            'details' => ['target_stage' => 'blackout'],
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => 'pending',
        ]);
        $taskId = $task->id;

        Artisan::call('app:process-crop-tasks');

        // Assert task status is updated
         $this->assertDatabaseHas('crop_tasks', [
            'id' => $taskId,
            'status' => 'triggered', // Assume success for this test
            'triggered_at' => Carbon::now()->toDateTimeString(),
        ]);

         // Assert notification exists in the database
        $notification = DB::table('notifications')
                         ->where('notifiable_id', $user->id)
                         ->whereJsonContains('data->title', 'Stage Transition Ready')
                         ->latest('created_at')
                         ->first();
        $this->assertNotNull($notification, 'Stage Transition notification not found in database.');
        $notificationData = json_decode($notification->data, true);
        $this->assertStringContainsString('blackout', $notificationData['body']);
    }

     /** @test */
    public function it_processes_pending_harvest_notification_task(): void
    {
        $recipe = Recipe::factory()->create();
        $user = User::first();
        $crop = Crop::factory()->create(['recipe_id' => $recipe->id, 'planted_at' => Carbon::now()->subDays(10)]);
        $task = CropTask::factory()->create([
            'crop_id' => $crop->id,
            'recipe_id' => $recipe->id,
            'task_type' => 'expected_harvest',
            'scheduled_at' => Carbon::now()->subHour(),
            'status' => 'pending',
        ]);
        $taskId = $task->id;

        Artisan::call('app:process-crop-tasks');

        // Assert task status is updated
         $this->assertDatabaseHas('crop_tasks', [
            'id' => $taskId,
            'status' => 'triggered', // Assume success
            'triggered_at' => Carbon::now()->toDateTimeString(),
        ]);

         // Assert notification exists in the database
        $notification = DB::table('notifications')
                         ->where('notifiable_id', $user->id)
                         ->whereJsonContains('data->title', 'Harvest Ready')
                         ->latest('created_at')
                         ->first();
        $this->assertNotNull($notification, 'Harvest Ready notification not found in database.');
    }

    /** @test */
    public function it_does_not_process_tasks_scheduled_in_the_future(): void
    {
        $recipe = Recipe::factory()->create();
        $crop = Crop::factory()->create(['recipe_id' => $recipe->id]);
        $task = CropTask::factory()->create([
            'crop_id' => $crop->id,
            'recipe_id' => $recipe->id,
            'task_type' => 'expected_harvest',
            'scheduled_at' => Carbon::now()->addHour(), // Scheduled in the future
            'status' => 'pending',
        ]);

        Artisan::call('app:process-crop-tasks');
        $outputText = Artisan::output(); // Capture output to check message
        
        // Assert task status remains pending
        $this->assertDatabaseHas('crop_tasks', [
            'id' => $task->id,
            'status' => 'pending',
        ]);

        // Assert correct output message (optional but good)
        $this->assertStringContainsString('No pending crop tasks found.', $outputText);

        // Assert no notification was created in the database
        $this->assertDatabaseCount('notifications', 0);
    }
}
