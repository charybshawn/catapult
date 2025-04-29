<?php

namespace App\Filament\Resources\TaskScheduleResource\Pages;

use App\Filament\Resources\TaskScheduleResource;
use App\Models\Crop;
use App\Models\TaskSchedule;
use App\Services\CropTaskService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class ListTaskSchedules extends ListRecords
{
    protected static string $resource = TaskScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_all_tasks')
                ->label('Rebuild All Tasks')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    DB::transaction(function () {
                        // Clear all existing crop tasks
                        TaskSchedule::where('resource_type', 'crops')->delete();
                        
                        // Regenerate tasks for all active crops
                        $crops = Crop::whereNotIn('current_stage', ['harvested'])->get();
                        $cropTaskService = new CropTaskService();
                        
                        foreach ($crops as $crop) {
                            $cropTaskService->scheduleAllStageTasks($crop);
                        }
                    });
                    
                    Notification::make()
                        ->title('All crop tasks refreshed')
                        ->success()
                        ->send();
                    
                    $this->refreshData();
                })
                ->requiresConfirmation()
                ->modalHeading('Rebuild All Tasks')
                ->modalDescription('This will delete all current crop stage tasks and rebuild them based on the current state of all crops. Are you sure you want to continue?'),
            Actions\CreateAction::make(),
        ];
    }
} 