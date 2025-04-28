<?php

namespace App\Filament\Pages;

use App\Models\Crop;
use App\Models\TaskSchedule;
use App\Services\CropTaskService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class ManageCropTasks extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Crop Alerts';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.manage-crop-tasks';
    
    public function getTitle(): string
    {
        return 'Manage Crop Growth Alerts';
    }
    
    protected function getTableQuery()
    {
        return TaskSchedule::query()
            ->where('resource_type', 'crops')
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('conditions');
            })
            ->orderBy('next_run_at');
    }
    
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('task_name')
                ->label('Action')
                ->formatStateUsing(function ($state) {
                    return ucfirst(str_replace(['advance_to_', '_'], ['', ' '], $state));
                })
                ->sortable(),
                
            TextColumn::make('conditions')
                ->label('Tray')
                ->formatStateUsing(function ($state) {
                    return $state['tray_number'] ?? 'Unknown';
                })
                ->searchable(query: function ($query, $search) {
                    return $query->whereRaw("json_extract(conditions, '$.tray_number') LIKE ?", ["%{$search}%"]);
                }),
                
            TextColumn::make('conditions')
                ->label('Variety')
                ->formatStateUsing(function ($state) {
                    return $state['variety'] ?? 'Unknown';
                })
                ->searchable(query: function ($query, $search) {
                    return $query->whereRaw("json_extract(conditions, '$.variety') LIKE ?", ["%{$search}%"]);
                }),
                
            TextColumn::make('conditions')
                ->label('Target Stage')
                ->formatStateUsing(function ($state) {
                    return ucfirst($state['target_stage'] ?? 'unknown');
                })
                ->badge()
                ->color(function ($state) {
                    return match ($state['target_stage'] ?? '') {
                        'germination' => 'info',
                        'blackout' => 'warning',
                        'light' => 'success',
                        'harvested' => 'danger',
                        default => 'gray',
                    };
                }),
                
            TextColumn::make('next_run_at')
                ->label('Scheduled For')
                ->dateTime()
                ->sortable(),
                
            TextColumn::make('relative_time')
                ->label('Time Until')
                ->getStateUsing(function (TaskSchedule $record): string {
                    $now = Carbon::now();
                    $nextRun = $record->next_run_at;
                    
                    if ($nextRun->isPast()) {
                        return 'Overdue';
                    }
                    
                    // Get precise time measurements
                    $diff = $now->diff($nextRun);
                    $days = $diff->d;
                    $hours = $diff->h;
                    $minutes = $diff->i;
                    
                    // Format the time display
                    $timeUntil = '';
                    if ($days > 0) {
                        $timeUntil .= $days . 'd ';
                    }
                    if ($hours > 0 || $days > 0) {
                        $timeUntil .= $hours . 'h ';
                    }
                    $timeUntil .= $minutes . 'm';
                    
                    return trim($timeUntil);
                })
                ->badge()
                ->color(fn (TaskSchedule $record) => $record->next_run_at->isPast() ? 'danger' : 'success'),
            
            // Fix crop ID display with direct attribute access
            TextColumn::make('crop_id')
                ->label('Crop')
                ->getStateUsing(function (TaskSchedule $record): ?int {
                    return $record->conditions['crop_id'] ?? null;
                })
                ->formatStateUsing(fn (?int $state): string => $state ? "#{$state}" : 'Unknown')
                ->url(function (TaskSchedule $record): ?string {
                    $cropId = $record->conditions['crop_id'] ?? null;
                    if ($cropId) {
                        return route('filament.admin.resources.crops.edit', ['record' => $cropId]);
                    }
                    return null;
                })
                ->openUrlInNewTab(),
        ];
    }
    
    protected function getTableFilters(): array
    {
        return [
            SelectFilter::make('target_stage')
                ->label('Target Stage')
                ->options([
                    'germination' => 'Germination',
                    'blackout' => 'Blackout',
                    'light' => 'Light',
                    'harvested' => 'Harvested',
                ])
                ->query(function ($query, array $data) {
                    if (isset($data['value'])) {
                        return $query->whereRaw("json_extract(conditions, '$.target_stage') = ?", [$data['value']]);
                    }
                    return $query;
                }),
        ];
    }
    
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('execute_now')
                ->label('Execute Now')
                ->icon('heroicon-o-bolt')
                ->action(function (TaskSchedule $record) {
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
                })
                ->requiresConfirmation()
                ->modalHeading('Execute Task Now')
                ->modalDescription('Are you sure you want to execute this task now? This will advance the crop to the next stage immediately.'),
            
            Tables\Actions\Action::make('reschedule')
                ->label('Reschedule')
                ->icon('heroicon-o-calendar-days')
                ->form([
                    Forms\Components\DateTimePicker::make('new_time')
                        ->label('New time')
                        ->required()
                        ->default(function (TaskSchedule $record) {
                            return $record->next_run_at;
                        }),
                ])
                ->action(function (TaskSchedule $record, array $data) {
                    $record->next_run_at = $data['new_time'];
                    $record->save();
                    
                    Notification::make()
                        ->title('Task rescheduled')
                        ->success()
                        ->send();
                })
                ->modalHeading('Reschedule Task'),
            
            Tables\Actions\DeleteAction::make()
                ->modalDescription('Are you sure you want to delete this task? This will stop the automatic stage transition alerts for this crop.'),
        ];
    }
    
    protected function getTableBulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\BulkAction::make('execute_selected')
                    ->label('Execute Selected')
                    ->action(function (CropTaskService $cropTaskService, array $records) {
                        $successCount = 0;
                        $failCount = 0;
                        
                        foreach ($records as $record) {
                            $result = $cropTaskService->processCropStageTask($record);
                            
                            if ($result['success']) {
                                $successCount++;
                            } else {
                                $failCount++;
                            }
                        }
                        
                        Notification::make()
                            ->title("Executed {$successCount} tasks")
                            ->body($failCount > 0 ? "{$failCount} tasks failed" : null)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Execute Selected Tasks')
                    ->modalDescription('Are you sure you want to execute all selected tasks now? This will advance crops to their next stages immediately.'),
                
                Tables\Actions\DeleteBulkAction::make()
                    ->modalDescription('Are you sure you want to delete these tasks? This will stop the automatic stage transition alerts for these crops.'),
            ]),
        ];
    }
    
    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No tasks scheduled';
    }
    
    protected function getTableEmptyStateDescription(): ?string
    {
        return 'Tasks will appear here when crops are scheduled for stage transitions.';
    }
    
    protected function getTableEmptyStateIcon(): ?string
    {
        return 'heroicon-o-bell-slash';
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh_all_tasks')
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
                })
                ->requiresConfirmation()
                ->modalHeading('Rebuild All Tasks')
                ->modalDescription('This will delete all current crop stage tasks and rebuild them based on the current state of all crops. Are you sure you want to continue?'),
        ];
    }
} 