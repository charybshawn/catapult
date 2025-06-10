<?php

namespace App\Filament\Resources\ScheduledTaskResource\Pages;

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
    
    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        // Create a minimal builder that won't actually query the database
        $model = new \App\Models\ScheduledTask();
        return $model->newQuery();
    }
    
    public function getTableRecords(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator
    {
        $tasks = \App\Models\ScheduledTask::getScheduledTasks();
        
        // Convert to Eloquent Collection to match expected return type
        return new \Illuminate\Database\Eloquent\Collection($tasks);
    }
}
