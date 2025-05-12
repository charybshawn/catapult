<?php

namespace Tests\Unit\Notifications;

// Change namespace if moved to Feature tests
// namespace Tests\Feature\Notifications;

use App\Console\Commands\ProcessCropTasks; // Command being tested
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\SeedVariety;
use App\Models\TaskSchedule;
use App\Models\User;
use App\Notifications\CropTaskActionDue; // Notification being sent
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CropTaskActionDueNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure roles are created (adjust if you have a seeder)
        Role::findOrCreate('Admin');
        Notification::fake(); // Mock notifications
    }

    /** @test */
    public function it_sends_database_notification_to_admin_for_due_crop_task()
    {
        // Arrange: Create necessary data
        $adminUser = User::factory()->create()->assignRole('Admin');
        $variety = SeedVariety::factory()->create();
        $recipe = Recipe::factory()->create(['seed_variety_id' => $variety->id]);
        $crop = Crop::factory()->create(['recipe_id' => $recipe->id]);
        
        $task = TaskSchedule::factory()->create([
            'resource_type' => 'crops',
            'task_name' => 'advance_to_blackout',
            'conditions' => [
                'crop_id' => $crop->id,
                'target_stage' => 'blackout',
                'tray_number' => $crop->tray_number,
                'variety' => $variety->name,
            ],
            'is_active' => true,
            'next_run_at' => now()->subHour(), // Ensure task is due
        ]);

        // Act: Run the command
        Artisan::call('app:process-task-schedules', ['--type' => 'crops']);

        // Assert: Notification was sent
        Notification::assertSentTo(
            $adminUser, // Assert sent to the correct user
            CropTaskActionDue::class, // Assert the correct notification class was used
            function ($notification, $channels) use ($task, $crop) {
                // Check notification content
                $this->assertEquals($task->id, $notification->task->id);
                $this->assertEquals($crop->id, $notification->crop->id);
                // Check the channel
                $this->assertContains('database', $channels);
                $this->assertNotContains('mail', $channels); // Ensure mail wasn't sent (if configured)
                return true;
            }
        );
        
        // Assert: TaskSchedule remains active
        $task->refresh();
        $this->assertTrue($task->is_active);
        $this->assertNull($task->last_run_at); // last_run_at should not be set by the notification command
    }
    
    /** @test */
    public function it_does_not_send_notification_for_future_task()
    {
        // Arrange
        $adminUser = User::factory()->create()->assignRole('Admin');
        $variety = SeedVariety::factory()->create();
        $recipe = Recipe::factory()->create(['seed_variety_id' => $variety->id]);
        $crop = Crop::factory()->create(['recipe_id' => $recipe->id]);
        
        TaskSchedule::factory()->create([
            'resource_type' => 'crops',
            'conditions' => ['crop_id' => $crop->id],
            'is_active' => true,
            'next_run_at' => now()->addHour(), // Task is NOT due yet
        ]);

        // Act
        Artisan::call('app:process-task-schedules', ['--type' => 'crops']);

        // Assert
        Notification::assertNothingSent();
    }
    
    /** @test */
    public function it_does_not_send_notification_for_inactive_task()
    {
        // Arrange
        $adminUser = User::factory()->create()->assignRole('Admin');
        $variety = SeedVariety::factory()->create();
        $recipe = Recipe::factory()->create(['seed_variety_id' => $variety->id]);
        $crop = Crop::factory()->create(['recipe_id' => $recipe->id]);
        
        TaskSchedule::factory()->create([
            'resource_type' => 'crops',
            'conditions' => ['crop_id' => $crop->id],
            'is_active' => false, // Task is inactive
            'next_run_at' => now()->subHour(), // Task is due, but inactive
        ]);

        // Act
        Artisan::call('app:process-task-schedules', ['--type' => 'crops']);

        // Assert
        Notification::assertNothingSent();
    }

    /** @test */
    public function it_deactivates_task_if_crop_is_missing()
    {
        // Arrange
        $adminUser = User::factory()->create()->assignRole('Admin');
        $missingCropId = 999;
        
        $task = TaskSchedule::factory()->create([
            'resource_type' => 'crops',
            'conditions' => ['crop_id' => $missingCropId], // Non-existent crop ID
            'is_active' => true,
            'next_run_at' => now()->subHour(),
        ]);

        // Act
        Artisan::call('app:process-task-schedules', ['--type' => 'crops']);

        // Assert: Notification not sent
        Notification::assertNothingSent();

        // Assert: Task is deactivated
        $task->refresh();
        $this->assertFalse($task->is_active);
        $this->assertNotNull($task->last_run_at); // last_run_at is set when deactivated due to missing crop
    }
}
