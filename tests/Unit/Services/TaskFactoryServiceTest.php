<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TaskFactoryService;
use App\Models\TaskSchedule;
use App\Models\Crop;
use App\Models\Recipe;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaskFactoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskFactoryService $taskFactoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taskFactoryService = new TaskFactoryService();
    }

    public function test_createTaskSchedule_creates_basic_task(): void
    {
        $crop = Crop::factory()->create();
        $scheduledAt = Carbon::now()->addHours(2);

        $task = $this->taskFactoryService->createTaskSchedule(
            $crop,
            'test_task',
            $scheduledAt,
            'Test task description'
        );

        $this->assertInstanceOf(TaskSchedule::class, $task);
        $this->assertEquals($crop->id, $task->crop_id);
        $this->assertEquals('test_task', $task->task_type);
        $this->assertEquals($scheduledAt->toDateTimeString(), $task->scheduled_at->toDateTimeString());
        $this->assertEquals('Test task description', $task->description);
        $this->assertEquals('pending', $task->status);
    }

    public function test_createStageTransitionTask_creates_proper_task(): void
    {
        $crop = Crop::factory()->create([
            'tray_number' => 'T123',
            'current_stage' => 'germination',
        ]);
        $scheduledAt = Carbon::now()->addHours(24);

        $task = $this->taskFactoryService->createStageTransitionTask(
            $crop,
            'blackout',
            $scheduledAt
        );

        $this->assertInstanceOf(TaskSchedule::class, $task);
        $this->assertEquals('crops', $task->resource_type);
        $this->assertEquals('once', $task->frequency);
        $this->assertTrue($task->is_active);
        $this->assertEquals($scheduledAt->toDateTimeString(), $task->next_run_at->toDateTimeString());
        $this->assertStringContains('T123', $task->description);
        $this->assertStringContains('germination', $task->description);
        $this->assertStringContains('blackout', $task->description);
    }

    public function test_createWateringSuspensionTask_creates_proper_task(): void
    {
        $crop = Crop::factory()->create([
            'tray_number' => 'T456',
        ]);
        $scheduledAt = Carbon::now()->addHours(12);

        $task = $this->taskFactoryService->createWateringSuspensionTask(
            $crop,
            $scheduledAt
        );

        $this->assertInstanceOf(TaskSchedule::class, $task);
        $this->assertEquals('crops', $task->resource_type);
        $this->assertEquals('once', $task->frequency);
        $this->assertTrue($task->is_active);
        $this->assertEquals($scheduledAt->toDateTimeString(), $task->next_run_at->toDateTimeString());
        $this->assertStringContains('T456', $task->description);
        $this->assertStringContains('Suspend watering', $task->description);
    }

    public function test_createHarvestReminderTask_creates_proper_task(): void
    {
        $crop = Crop::factory()->create([
            'tray_number' => 'T789',
        ]);
        $scheduledAt = Carbon::now()->addDays(7);

        $task = $this->taskFactoryService->createHarvestReminderTask(
            $crop,
            $scheduledAt
        );

        $this->assertInstanceOf(TaskSchedule::class, $task);
        $this->assertEquals('crops', $task->resource_type);
        $this->assertEquals('once', $task->frequency);
        $this->assertTrue($task->is_active);
        $this->assertEquals($scheduledAt->toDateTimeString(), $task->next_run_at->toDateTimeString());
        $this->assertStringContains('T789', $task->description);
        $this->assertStringContains('Harvest', $task->description);
    }

    public function test_createBatchStageTransitionTask_creates_complex_task(): void
    {
        $crop = Crop::factory()->create();
        $scheduledAt = Carbon::now()->addHours(6);
        $conditions = [
            'crop_id' => $crop->id,
            'target_stage' => 'light',
            'tray_numbers' => ['T1', 'T2', 'T3'],
            'variety' => 'Basil',
        ];

        $task = $this->taskFactoryService->createBatchStageTransitionTask(
            $crop,
            'light',
            $scheduledAt,
            $conditions
        );

        $this->assertInstanceOf(TaskSchedule::class, $task);
        $this->assertEquals('crops', $task->resource_type);
        $this->assertEquals('advance_to_light', $task->task_name);
        $this->assertEquals('once', $task->frequency);
        $this->assertEquals($conditions, $task->conditions);
        $this->assertTrue($task->is_active);
        $this->assertEquals($scheduledAt->toDateTimeString(), $task->next_run_at->toDateTimeString());
    }

    public function test_deleteTasksForCrop_removes_related_tasks(): void
    {
        $crop = Crop::factory()->create();
        
        // Create some tasks for this crop
        TaskSchedule::factory()->create(['crop_id' => $crop->id]);
        TaskSchedule::factory()->create([
            'resource_type' => 'crops',
            'conditions' => ['crop_id' => $crop->id]
        ]);
        
        // Create a task for a different crop
        $otherCrop = Crop::factory()->create();
        TaskSchedule::factory()->create(['crop_id' => $otherCrop->id]);

        $deletedCount = $this->taskFactoryService->deleteTasksForCrop($crop);

        $this->assertEquals(2, $deletedCount);
        
        // Verify the other crop's task still exists
        $this->assertEquals(1, TaskSchedule::where('crop_id', $otherCrop->id)->count());
    }

    public function test_deleteTasksForCrop_returns_zero_when_no_tasks(): void
    {
        $crop = Crop::factory()->create();

        $deletedCount = $this->taskFactoryService->deleteTasksForCrop($crop);

        $this->assertEquals(0, $deletedCount);
    }
}