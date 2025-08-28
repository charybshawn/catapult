<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;

/**
 * Represents automated agricultural task scheduling for microgreens production,
 * managing system automation, resource monitoring, and operational workflows.
 * Provides visibility into agricultural automation and manual command availability.
 *
 * @business_domain Agricultural Automation & Task Management
 * @workflow_context Used in system monitoring, automation oversight, and manual operations
 * @agricultural_process Automates resource monitoring, crop processing, and production workflows
 *
 * Virtual Model: Does not use database table, returns hardcoded agricultural tasks
 * @property int $id Task identifier for UI purposes
 * @property string $command Artisan command name for agricultural automation
 * @property string $full_command Complete command with artisan prefix
 * @property string $expression Cron expression or DISABLED/N/A status
 * @property string $description Human-readable task description
 * @property string $timezone Application timezone for scheduling
 * @property string $without_overlapping Whether task prevents concurrent execution
 * @property string $mutex Mutex type (always 'None' for these tasks)
 * @property string|null $next_due_date Next scheduled execution (not calculated)
 * @property string $task_type Task category (Scheduled Task, Manual Command)
 *
 * @business_rule Scheduled tasks run automatically via cron scheduling
 * @business_rule Manual commands require user initiation through interfaces
 * @business_rule Some tasks are temporarily disabled for manual control
 *
 * @agricultural_automation Resource level monitoring, crop time updates, inventory checks
 * @operational_support Recurring order processing, invoice generation, billing management
 */
class ScheduledTask extends Model
{
    protected $fillable = [
        'id',
        'command',
        'full_command',
        'expression',
        'description',
        'timezone',
        'without_overlapping',
        'mutex',
        'next_due_date',
        'task_type',
    ];
    
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    
    // Use a dummy table name to satisfy Eloquent
    protected $table = 'scheduled_tasks';
    
    public function newQuery()
    {
        $model = $this;
        
        // Create a custom builder that returns our static data
        return new class(new \Illuminate\Database\Query\Builder(
            app('db.connection'),
            app('db.connection')->getQueryGrammar(),
            app('db.connection')->getPostProcessor()
        ), $model) extends Builder {
            
            public function __construct($query, $model)
            {
                $this->query = $query;
                $this->model = $model;
            }
            
            public function get($columns = ['*'])
            {
                return new \Illuminate\Database\Eloquent\Collection(
                    \App\Models\ScheduledTask::getScheduledTasks()
                );
            }
            
            public function count()
            {
                return \App\Models\ScheduledTask::getScheduledTasks()->count();
            }
            
            public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
            {
                $tasks = \App\Models\ScheduledTask::getScheduledTasks();
                $perPage = $perPage ?: 15;
                $page = $page ?: request()->get($pageName, 1);
                
                $items = $tasks->forPage($page, $perPage);
                $totalCount = $total ?: $tasks->count();
                
                return new LengthAwarePaginator(
                    $items,
                    $totalCount,
                    $perPage,
                    $page,
                    [
                        'path' => request()->url(),
                        'pageName' => $pageName,
                    ]
                );
            }
            
            public function where($column, $operator = null, $value = null, $boolean = 'and')
            {
                return $this;
            }
            
            public function orderBy($column, $direction = 'asc')
            {
                return $this;
            }
            
            public function getModel()
            {
                return $this->model;
            }
        };
    }
    
    public static function all($columns = ['*'])
    {
        return static::getScheduledTasks();
    }
    
    public static function getScheduledTasks(): Collection
    {
        // Return scheduled tasks from your Console/Kernel.php that relate to project resources
        return collect([
            static::createTask(1, 'app:check-resource-levels', '0 * * * *', 'Check resource levels every hour', 'Scheduled Task'),
            static::createTask(2, 'app:update-crop-time-fields', '*/15 * * * *', 'Update crop time fields every 15 minutes', 'Scheduled Task', true),
            static::createTask(3, 'app:process-crop-tasks', '*/15 * * * *', 'Process crop tasks every 15 minutes', 'Scheduled Task'),
            static::createTask(4, 'orders:process-recurring', 'DISABLED', 'Process recurring orders daily at 6 AM (TEMPORARILY DISABLED - use manual action buttons)', 'Manual Command', true),
            static::createTask(5, 'invoices:generate-consolidated', 'DISABLED', 'Generate consolidated invoices daily at 7 AM (TEMPORARILY DISABLED - use manual action buttons)', 'Manual Command', true),
            static::createTask(6, 'app:check-inventory-levels', '0 8,16 * * *', 'Check inventory levels twice daily (8 AM, 4 PM)', 'Scheduled Task'),
            static::createTask(7, 'orders:backfill-billing-periods', '0 5 * * 1', 'Backfill billing periods for B2B orders weekly on Monday at 5 AM', 'Manual Command', true),
            static::createTask(8, 'orders:backfill-all-recurring-billing-periods', 'N/A', 'Backfill billing periods for all recurring order types (manual command)', 'Manual Command'),
            static::createTask(9, 'orders:backfill-recurring', 'N/A', 'Generate missing recurring orders from past dates to present (manual command)', 'Manual Command'),
            static::createTask(10, 'orders:generate-crops', 'N/A', 'Generate crop plans that need to be planted for upcoming orders (manual command)', 'Manual Command'),
            static::createTask(11, 'recipe:set-germination', 'N/A', 'Set the germination days for a recipe (manual command)', 'Manual Command'),
        ]);
    }
    
    private static function createTask(int $id, string $command, string $expression, string $description, string $taskType, bool $withoutOverlapping = false): self
    {
        $task = new static();
        $task->id = $id;
        $task->command = $command;
        $task->full_command = "artisan {$command}";
        $task->expression = $expression;
        $task->description = $description;
        $task->task_type = $taskType;
        $task->timezone = config('app.timezone');
        $task->without_overlapping = $withoutOverlapping ? 'Yes' : 'No';
        $task->mutex = 'None';
        $task->next_due_date = null;
        
        return $task;
    }
}