<?php

namespace App\Filament\Resources\TimeCardResource\Pages;

use App\Filament\Resources\TimeCardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTimeCard extends CreateRecord
{
    protected static string $resource = TimeCardResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // Get the task names from the form
        $taskNames = $this->data['taskNames'] ?? [];
        
        // Create the record normally (excluding tasks)
        $record = static::getModel()::create($data);
        
        // Add tasks if any were specified
        if (!empty($taskNames)) {
            $record->addTasks($taskNames);
        }
        
        return $record;
    }
}
