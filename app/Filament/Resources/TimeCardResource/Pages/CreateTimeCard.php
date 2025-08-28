<?php

namespace App\Filament\Resources\TimeCardResource\Pages;

use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\TimeCardResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseCreateRecord;

class CreateTimeCard extends BaseCreateRecord
{
    protected static string $resource = TimeCardResource::class;

    protected function handleRecordCreation(array $data): Model
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
