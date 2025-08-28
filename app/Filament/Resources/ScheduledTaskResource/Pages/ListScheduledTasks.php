<?php

namespace App\Filament\Resources\ScheduledTaskResource\Pages;

use Illuminate\Database\Eloquent\Builder;
use App\Models\ScheduledTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Pagination\CursorPaginator;
use App\Filament\Resources\ScheduledTaskResource;
use Filament\Resources\Pages\ListRecords;

class ListScheduledTasks extends ListRecords
{
    protected static string $resource = ScheduledTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action needed - these are system tasks
        ];
    }
    
    protected function getTableQuery(): Builder
    {
        // Create a minimal builder that won't actually query the database
        $model = new ScheduledTask();
        return $model->newQuery();
    }
    
    public function getTableRecords(): Collection|Paginator|CursorPaginator
    {
        $tasks = ScheduledTask::getScheduledTasks();
        
        // Convert to Eloquent Collection to match expected return type
        return new Collection($tasks);
    }
}
