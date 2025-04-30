<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskScheduleResource\Pages;
use App\Models\Crop;
use App\Models\TaskSchedule;
use App\Services\CropTaskService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class TaskScheduleResource extends Resource
{
    protected static ?string $model = TaskSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Crop Alerts';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 3;
    
    protected static ?string $recordTitleAttribute = 'task_name';
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('resource_type', 'crops')
            ->where('is_active', true)
            ->whereNotNull('conditions');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('task_name')
                    ->label('Task Name')
                    ->readOnly(),
                    
                Forms\Components\TextInput::make('resource_type')
                    ->label('Resource Type')
                    ->readOnly(),
                    
                Forms\Components\TextInput::make('frequency')
                    ->label('Frequency')
                    ->readOnly(),
                    
                Forms\Components\DateTimePicker::make('next_run_at')
                    ->label('Next Run At')
                    ->required(),
                    
                Forms\Components\DateTimePicker::make('last_run_at')
                    ->label('Last Run At')
                    ->disabled(),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Is Active')
                    ->required(),
                    
                Forms\Components\KeyValue::make('conditions')
                    ->label('Conditions')
                    ->readOnly(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('next_run_at', 'asc')
            ->columns([
                TextColumn::make('task_name')
                    ->label('Action')
                    ->formatStateUsing(function ($state) {
                        return ucfirst(str_replace(['advance_to_', '_'], ['', ' '], $state));
                    })
                    ->sortable()
                    ->searchable(),
                    
                TextColumn::make('tray_number')
                    ->label('Tray')
                    ->getStateUsing(function (TaskSchedule $record) {
                        return $record->conditions['tray_number'] ?? 'Unknown';
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("json_extract(conditions, '$.tray_number') {$direction}");
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereRaw("json_extract(conditions, '$.tray_number') LIKE ?", ["%{$search}%"]);
                    }),
                    
                TextColumn::make('variety')
                    ->label('Variety')
                    ->getStateUsing(function (TaskSchedule $record) {
                        return $record->conditions['variety'] ?? 'Unknown';
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("json_extract(conditions, '$.variety') {$direction}");
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereRaw("json_extract(conditions, '$.variety') LIKE ?", ["%{$search}%"]);
                    }),
                    
                TextColumn::make('seed_variety')
                    ->label('Seed Variety')
                    ->getStateUsing(function (TaskSchedule $record) {
                        $cropId = $record->conditions['crop_id'] ?? null;
                        if (!$cropId) return 'Unknown';
                        
                        $crop = Crop::find($cropId);
                        if (!$crop || !$crop->recipe) return 'Unknown';
                        
                        return $crop->recipe->seedVariety->name ?? 'Unknown';
                    }),
                    
                TextColumn::make('target_stage')
                    ->label('Target Stage')
                    ->getStateUsing(function (TaskSchedule $record) {
                        return ucfirst($record->conditions['target_stage'] ?? 'unknown');
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("json_extract(conditions, '$.target_stage') {$direction}");
                    })
                    ->badge()
                    ->color(function (TaskSchedule $record) {
                        return match ($record->conditions['target_stage'] ?? '') {
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
            ])
            ->filters([
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
            ])
            ->actions([
                Tables\Actions\Action::make('debug')
                    ->label('')
                    ->icon('heroicon-o-code-bracket')
                    ->tooltip('Debug Info')
                    ->action(function (TaskSchedule $record) {
                        $crop = Crop::find($record->conditions['crop_id'] ?? null);
                        
                        $taskData = [
                            'ID' => $record->id,
                            'Task Name' => $record->task_name,
                            'Resource Type' => $record->resource_type,
                            'Frequency' => $record->frequency,
                            'Is Active' => $record->is_active ? 'Yes' : 'No',
                            'Next Run At' => $record->next_run_at->format('Y-m-d H:i:s'),
                            'Last Run At' => $record->last_run_at ? $record->last_run_at->format('Y-m-d H:i:s') : 'Never',
                            'Conditions' => json_encode($record->conditions, JSON_PRETTY_PRINT),
                        ];
                        
                        $cropData = [];
                        
                        if ($crop) {
                            $cropData = [
                                'ID' => $crop->id,
                                'Tray Number' => $crop->tray_number,
                                'Current Stage' => $crop->current_stage,
                                'Planted At' => $crop->planted_at->format('Y-m-d H:i:s'),
                                'Germination At' => $crop->germination_at ? $crop->germination_at->format('Y-m-d H:i:s') : 'N/A',
                                'Blackout At' => $crop->blackout_at ? $crop->blackout_at->format('Y-m-d H:i:s') : 'N/A',
                                'Light At' => $crop->light_at ? $crop->light_at->format('Y-m-d H:i:s') : 'N/A',
                                'Harvested At' => $crop->harvested_at ? $crop->harvested_at->format('Y-m-d H:i:s') : 'N/A',
                                'Recipe ID' => $crop->recipe_id,
                                'Recipe Name' => $crop->recipe?->name ?? 'N/A',
                                'Seed Variety ID' => $crop->recipe?->seed_variety_id ?? 'N/A',
                                'Seed Variety Name' => $crop->recipe?->seedVariety?->name ?? 'N/A',
                                'Germination Days' => $crop->recipe?->germination_days ?? 'N/A',
                                'Blackout Days' => $crop->recipe?->blackout_days ?? 'N/A',
                                'Light Days' => $crop->recipe?->light_days ?? 'N/A',
                            ];
                        }
                        
                        // Format the debug data for display in a modal
                        $taskDataHtml = '<div class="mb-4">';
                        $taskDataHtml .= '<h3 class="text-lg font-medium mb-2">Task Data</h3>';
                        $taskDataHtml .= '<div class="overflow-auto max-h-48 space-y-1">';
                        
                        foreach ($taskData as $key => $value) {
                            $taskDataHtml .= '<div class="flex">';
                            $taskDataHtml .= '<span class="font-medium w-32">' . $key . ':</span>';
                            $taskDataHtml .= '<span class="text-gray-600">' . $value . '</span>';
                            $taskDataHtml .= '</div>';
                        }
                        
                        $taskDataHtml .= '</div></div>';
                        
                        // Format crop data if available
                        $cropDataHtml = '';
                        if (!empty($cropData)) {
                            $cropDataHtml = '<div>';
                            $cropDataHtml .= '<h3 class="text-lg font-medium mb-2">Crop Data</h3>';
                            $cropDataHtml .= '<div class="overflow-auto max-h-48 space-y-1">';
                            
                            foreach ($cropData as $key => $value) {
                                $cropDataHtml .= '<div class="flex">';
                                $cropDataHtml .= '<span class="font-medium w-32">' . $key . ':</span>';
                                $cropDataHtml .= '<span class="text-gray-600">' . $value . '</span>';
                                $cropDataHtml .= '</div>';
                            }
                            
                            $cropDataHtml .= '</div></div>';
                        } else {
                            $cropDataHtml = '<div class="text-gray-500">Crop not found</div>';
                        }
                        
                        Notification::make()
                            ->title('Debug Information')
                            ->body($taskDataHtml . $cropDataHtml)
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('close')
                                    ->label('Close')
                                    ->color('gray')
                            ])
                            ->send();
                    }),
                
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
                
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Are you sure you want to delete this task? This will stop the automatic stage transition alerts for this crop.'),
            ])
            ->bulkActions([
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
            ])
            ->emptyStateHeading('No tasks scheduled')
            ->emptyStateDescription('Tasks will appear here when crops are scheduled for stage transitions.')
            ->emptyStateIcon('heroicon-o-bell-slash')
            ->groups([
                Group::make('tray_number')
                    ->label('Tray Number')
                    ->getTitleFromRecordUsing(function ($record) {
                        return $record->conditions['tray_number'] ?? 'Unknown';
                    })
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => 
                        $query->orderByRaw("json_extract(conditions, '$.tray_number') {$direction}")
                    ),
                Group::make('target_stage')
                    ->label('Target Stage')
                    ->getTitleFromRecordUsing(function ($record) {
                        return ucfirst($record->conditions['target_stage'] ?? 'Unknown');
                    })
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => 
                        $query->orderByRaw("json_extract(conditions, '$.target_stage') {$direction}")
                    ),
                Group::make('variety')
                    ->label('Variety')
                    ->getTitleFromRecordUsing(function ($record) {
                        return $record->conditions['variety'] ?? 'Unknown';
                    })
                    ->orderQueryUsing(fn (Builder $query, string $direction): Builder => 
                        $query->orderByRaw("json_extract(conditions, '$.variety') {$direction}")
                    )
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTaskSchedules::route('/'),
            'create' => Pages\CreateTaskSchedule::route('/create'),
            'edit' => Pages\EditTaskSchedule::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getEloquentQuery()->where('next_run_at', '<', now())->exists() 
            ? 'danger' 
            : 'primary';
    }
} 