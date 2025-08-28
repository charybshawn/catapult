<?php

namespace App\Filament\Resources\TimeCardResource\Pages;

use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\TimeCardResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditTimeCard extends BaseEditRecord
{
    protected static string $resource = TimeCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing tasks into the form
        $data['taskNames'] = $this->record->task_names;
        
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Get the task names from the form
        $taskNames = $this->data['taskNames'] ?? [];
        
        // Update the record normally (excluding tasks)
        $record->update($data);
        
        // Clear existing tasks and add new ones
        $record->tasks()->delete();
        
        if (!empty($taskNames)) {
            $record->addTasks($taskNames);
        }
        
        return $record;
    }
}
