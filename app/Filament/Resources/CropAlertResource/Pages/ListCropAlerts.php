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
                    \Illuminate\Support\Facades\Log::info('Starting alert refresh process');
                    
                    DB::transaction(function () {
                        // Clear all existing crop alerts
                        $deletedCount = TaskSchedule::where('resource_type', 'crops')->delete();
                        \Illuminate\Support\Facades\Log::info("Deleted {$deletedCount} existing crop alerts");
                        
                        // Regenerate alerts for all active crops
                        $crops = Crop::with(['recipe', 'currentStage'])
                            ->whereHas('currentStage', function($query) {
                                $query->where('code', '!=', 'harvested');
                            })->get();
                        
                        \Illuminate\Support\Facades\Log::info("Found {$crops->count()} active crops to process");
                        
                        $cropTaskService = app(CropTaskManagementService::class);
                        
                        foreach ($crops as $crop) {
                            \Illuminate\Support\Facades\Log::info("Processing crop {$crop->id}");
                            $cropTaskService->scheduleAllStageTasks($crop);
                        }
                    });
                    
                    \Illuminate\Support\Facades\Log::info('Alert refresh process completed');
                    
                    // Debug: Check what tasks were actually created
                    $allTasks = \App\Models\TaskSchedule::where('resource_type', 'crops')->get();
                    \Illuminate\Support\Facades\Log::info("Created {$allTasks->count()} total crop tasks:", $allTasks->pluck('task_name', 'id')->toArray());
                    
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