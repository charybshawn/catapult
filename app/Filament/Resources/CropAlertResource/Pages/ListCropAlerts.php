<?php

namespace App\Filament\Resources\CropAlertResource\Pages;

use App\Filament\Resources\CropAlertResource;
use App\Models\Crop;
use App\Models\TaskSchedule;
use App\Services\CropTaskManagementService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Actions\ActionGroup;

class ListCropAlerts extends ListRecords
{
    protected static string $resource = CropAlertResource::class;

    protected function getHeaderTitle(): string
    {
        return 'Crop Alerts';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_all_alerts')
                ->label('Rebuild All Alerts')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    DB::transaction(function () {
                        // Clear all existing crop alerts
                        TaskSchedule::where('resource_type', 'crops')->delete();
                        
                        // Regenerate alerts for all active crops
                        $crops = Crop::whereNotIn('current_stage', ['harvested'])->get();
                        $cropTaskService = app(CropTaskManagementService::class);
                        
                        foreach ($crops as $crop) {
                            $cropTaskService->scheduleAllStageTasks($crop);
                        }
                    });
                    
                    Notification::make()
                        ->title('All crop alerts refreshed')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Rebuild All Alerts')
                ->modalDescription('This will delete all current crop stage alerts and rebuild them based on the current state of all crops. Are you sure you want to continue?'),
            Actions\CreateAction::make(),
        ];
    }
} 