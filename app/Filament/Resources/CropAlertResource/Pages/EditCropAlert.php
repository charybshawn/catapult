<?php

namespace App\Filament\Resources\CropAlertResource\Pages;

use App\Filament\Resources\CropAlertResource;
use App\Services\CropTaskManagementService;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Filament\Notifications\Notification;

class EditCropAlert extends BaseEditRecord
{
    protected static string $resource = CropAlertResource::class;

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
                    $cropTaskService = app(CropTaskManagementService::class);
                    $result = $cropTaskService->processCropStageTask($record);
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Alert executed successfully')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Failed to execute alert')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                    
                    $this->refreshFormData(['is_active', 'last_run_at']);
                })
                ->requiresConfirmation()
                ->modalHeading('Execute Alert Now')
                ->modalDescription('Are you sure you want to execute this alert now? This will advance the crop to the next stage immediately.'),
            Actions\DeleteAction::make(),
        ];
    }
} 