<?php

namespace App\Filament\Resources\TaskScheduleResource\Pages;

use App\Filament\Resources\TaskScheduleResource;
use App\Services\CropTaskService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTaskSchedule extends EditRecord
{
    protected static string $resource = TaskScheduleResource::class;

    protected function getHeaderTitle(): string 
    {
        return 'Edit Crop Alert';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('execute_now')
                ->label('Execute Now')
                ->icon('heroicon-o-bolt')
                ->action(function () {
                    $record = $this->getRecord();
                    $cropTaskService = new CropTaskService();
                    $result = $cropTaskService->processCropStageTask($record);
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Task executed successfully')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Failed to execute task')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                    
                    $this->refreshFormData(['is_active', 'last_run_at']);
                })
                ->requiresConfirmation()
                ->modalHeading('Execute Task Now')
                ->modalDescription('Are you sure you want to execute this task now? This will advance the crop to the next stage immediately.'),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 