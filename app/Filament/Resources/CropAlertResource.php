<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropAlertResource\Pages;
use App\Models\Crop;
use App\Models\CropAlert;
use App\Services\CropTaskManagementService;
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
use Filament\Tables\Actions\Action;
use App\Services\RecipeVarietyService;

class CropAlertResource extends Resource
{
    protected RecipeVarietyService $varietyService;

    public function __construct()
    {
        $this->varietyService = app(RecipeVarietyService::class);
    }
    protected static ?string $model = CropAlert::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Crop Alerts';
    protected static ?string $navigationGroup = 'Production';
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'task_name';
    protected static ?string $modelLabel = 'Crop Alert';
    protected static ?string $pluralModelLabel = 'Crop Alerts';
    
    // Note: We don't need the getEloquentQuery override since the CropAlert model has a global scope

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('task_name')
                    ->label('Alert Type')
                    ->formatStateUsing(fn ($state) => str_starts_with($state, 'advance_to_') 
                        ? 'Advance to ' . ucfirst(str_replace('advance_to_', '', $state))
                        : ucfirst(str_replace('_', ' ', $state)))
                    ->readOnly(),
                    
                Forms\Components\TextInput::make('resource_type')
                    ->label('Resource Type')
                    ->readOnly(),
                    
                Forms\Components\TextInput::make('frequency')
                    ->label('Frequency')
                    ->readOnly(),
                    
                Forms\Components\DateTimePicker::make('next_run_at')
                    ->label('Scheduled For')
                    ->required(),
                    
                Forms\Components\DateTimePicker::make('last_run_at')
                    ->label('Last Executed')
                    ->disabled(),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Is Active')
                    ->required(),
                    
                Forms\Components\KeyValue::make('conditions')
                    ->label('Conditions')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->defaultSort('next_run_at', 'asc')
            ->modifyQueryUsing(function (Builder $query) {
                // Since CropAlert uses JSON conditions to store crop_id, 
                // we can't use traditional eager loading
                return $query;
            })
            ->columns([
                TextColumn::make('alert_type')
                    ->label('Action')
                    ->getStateUsing(fn (CropAlert $record) => $record->alert_type)
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('task_name', $direction);
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('task_name', 'like', "%{$search}%");
                    })
                    ->toggleable(),
                    
                TextColumn::make('tray_number')
                    ->label('Batch Trays')
                    ->getStateUsing(function (CropAlert $record) {
                        // If we have tray_numbers array, it's already a batch operation
                        if (isset($record->conditions['tray_numbers']) && is_array($record->conditions['tray_numbers'])) {
                            $count = count($record->conditions['tray_numbers']);
                            return "{$count} trays";
                        }
                        
                        // For operations targeting single trays, find the batch info
                        if (isset($record->conditions['crop_id'])) {
                            $cropId = $record->conditions['crop_id'];
                            $crop = Crop::find($cropId);
                            
                            if ($crop) {
                                // Get all crops with the same batch identifier
                                $batchCount = Crop::where('recipe_id', $crop->recipe_id)
                                    ->where('planting_at', $crop->planting_at)
                                    ->where('current_stage_id', $crop->current_stage_id)
                                    ->count();
                                    
                                return "{$batchCount} trays";
                            }
                        }
                        
                        // Fallback to original behavior
                        return $record->conditions['tray_number'] ? 'Single tray' : 'Unknown';
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("json_extract(conditions, '$.tray_number') {$direction}");
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereRaw("json_extract(conditions, '$.tray_number') LIKE ?", ["%{$search}%"]);
                    })
                    ->toggleable(),
                    
                TextColumn::make('variety')
                    ->label('Recipe')
                    ->getStateUsing(function (CropAlert $record) {
                        // First try to get from conditions (for backward compatibility)
                        if (isset($record->conditions['variety'])) {
                            return $record->conditions['variety'];
                        }
                        
                        // If not in conditions, get from the crop's recipe relationship
                        $cropId = $record->conditions['crop_id'] ?? null;
                        if (!$cropId) return 'Unknown';
                        
                        $crop = Crop::with(['recipe'])->find($cropId);
                        if (!$crop) return 'Unknown - Crop Not Found';
                        
                        if (!$crop->recipe) return 'Unknown - No Recipe';
                        
                        return $crop->recipe->name ?? 'Unknown - No Recipe Name';
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("json_extract(conditions, '$.variety') {$direction}");
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereRaw("json_extract(conditions, '$.variety') LIKE ?", ["%{$search}%"]);
                    })
                    ->toggleable(),
                    
                TextColumn::make('seed_variety')
                    ->label('Seed Variety')
                    ->getStateUsing(function (CropAlert $record) {
                        $cropId = $record->conditions['crop_id'] ?? null;
                        if (!$cropId) return 'Unknown';
                        
                        $crop = Crop::with(['recipe.seedEntry'])->find($cropId);
                        if (!$crop) return 'Unknown - Crop Not Found';
                        
                        if (!$crop->recipe) return 'Unknown - No Recipe';
                        
                        // First try to get from recipe's own fields
                        if ($crop->recipe->common_name && $crop->recipe->cultivar_name) {
                            return $crop->recipe->common_name . ' - ' . $crop->recipe->cultivar_name;
                        }
                        
                        // Then try seedEntry relationship
                        if ($crop->recipe->seedEntry) {
                            return $crop->recipe->seedEntry->common_name . ' - ' . $crop->recipe->seedEntry->cultivar_name;
                        }
                        
                        // Fallback: use the recipe name since it contains the variety info
                        if ($crop->recipe->name) {
                            return $crop->recipe->name;
                        }
                        
                        return 'Unknown - No Seed Entry';
                    })
                    ->toggleable(),
                    
                TextColumn::make('target_stage')
                    ->label('Target Stage')
                    ->getStateUsing(function (CropAlert $record) {
                        // For suspend_watering and similar non-stage tasks, show N/A
                        if (in_array($record->task_name, ['suspend_watering', 'resume_watering'])) {
                            return 'N/A';
                        }
                        
                        return ucfirst($record->conditions['target_stage'] ?? 'unknown');
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("json_extract(conditions, '$.target_stage') {$direction}");
                    })
                    ->badge()
                    ->color(function (CropAlert $record) {
                        // For non-stage tasks, use a neutral color
                        if (in_array($record->task_name, ['suspend_watering', 'resume_watering'])) {
                            return 'gray';
                        }
                        
                        return match ($record->conditions['target_stage'] ?? '') {
                            'germination' => 'info',
                            'blackout' => 'warning',
                            'light' => 'success',
                            'harvested' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->toggleable(),
                    
                TextColumn::make('next_run_at')
                    ->label('Scheduled For')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('time_until')
                    ->label('Time Until')
                    ->getStateUsing(fn (CropAlert $record) => $record->time_until)
                    ->badge()
                    ->color(fn (CropAlert $record) => $record->next_run_at->isPast() ? 'danger' : 'success')
                    ->toggleable(),
                
                TextColumn::make('crop_id')
                    ->label('Crop')
                    ->getStateUsing(function (CropAlert $record): ?int {
                        return $record->conditions['crop_id'] ?? null;
                    })
                    ->formatStateUsing(fn (?int $state): string => $state ? "#{$state}" : 'Unknown')
                    ->url(function (CropAlert $record): ?string {
                        $cropId = $record->conditions['crop_id'] ?? null;
                        if ($cropId) {
                            return route('filament.admin.resources.crops.edit', ['record' => $cropId]);
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->toggleable(),
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
                    ->action(function (CropAlert $record) {
                        $crop = Crop::find($record->conditions['crop_id'] ?? null);
                        
                        $alertData = [
                            'ID' => $record->id,
                            'Alert Type' => $record->alert_type,
                            'Resource Type' => $record->resource_type,
                            'Frequency' => $record->frequency,
                            'Is Active' => $record->is_active ? 'Yes' : 'No',
                            'Scheduled For' => $record->next_run_at->format('Y-m-d H:i'),
                            'Last Executed' => $record->last_run_at ? $record->last_run_at->format('Y-m-d H:i') : 'Never',
                            'Conditions' => json_encode($record->conditions, JSON_PRETTY_PRINT),
                        ];
                        
                        $cropData = [];
                        
                        if ($crop) {
                            $cropData = [
                                'ID' => $crop->id,
                                'Tray Number' => $crop->tray_number,
                                'Current Stage' => $crop->current_stage,
                                'Planted At' => $crop->planting_at->format('Y-m-d H:i'),
                                'Germination At' => $crop->germination_at ? $crop->germination_at->format('Y-m-d H:i') : 'N/A',
                                'Blackout At' => $crop->blackout_at ? $crop->blackout_at->format('Y-m-d H:i') : 'N/A',
                                'Light At' => $crop->light_at ? $crop->light_at->format('Y-m-d H:i') : 'N/A',
                                'Harvested At' => $crop->harvested_at ? $crop->harvested_at->format('Y-m-d H:i') : 'N/A',
                                'Recipe ID' => $crop->recipe_id,
                                'Recipe Name' => $crop->recipe?->name ?? 'N/A',
                                'Seed Entry ID' => $crop->recipe?->seed_entry_id ?? 'N/A',
                                'Seed Cultivar Name' => $crop->recipe?->seedEntry 
                                    ? $crop->recipe->seedEntry->common_name . ' - ' . $crop->recipe->seedEntry->cultivar_name 
                                    : 'N/A',
                                'Germination Days' => $crop->recipe?->germination_days ?? 'N/A',
                                'Blackout Days' => $crop->recipe?->blackout_days ?? 'N/A',
                                'Light Days' => $crop->recipe?->light_days ?? 'N/A',
                            ];
                        }
                        
                        // Format the debug data for display in a modal
                        $alertDataHtml = '<div class="mb-4">';
                        $alertDataHtml .= '<h3 class="text-lg font-medium mb-2">Alert Data</h3>';
                        $alertDataHtml .= '<div class="overflow-auto max-h-48 space-y-1">';
                        
                        foreach ($alertData as $key => $value) {
                            $alertDataHtml .= '<div class="flex">';
                            $alertDataHtml .= '<span class="font-medium w-32">' . $key . ':</span>';
                            $alertDataHtml .= '<span class="text-gray-600">' . $value . '</span>';
                            $alertDataHtml .= '</div>';
                        }
                        
                        $alertDataHtml .= '</div></div>';
                        
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
                            ->body($alertDataHtml . $cropDataHtml)
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
                    ->action(function (CropAlert $record) {
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
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Execute Alert Now')
                    ->modalDescription('Are you sure you want to execute this alert now? This will advance the crop to the next stage immediately.'),
                
                Tables\Actions\Action::make('reschedule')
                    ->label('Reschedule')
                    ->icon('heroicon-o-calendar-days')
                    ->form([
                        Forms\Components\DateTimePicker::make('new_time')
                            ->label('New time')
                            ->required()
                            ->default(function (CropAlert $record) {
                                return $record->next_run_at;
                            }),
                    ])
                    ->action(function (CropAlert $record, array $data) {
                        $record->next_run_at = $data['new_time'];
                        $record->save();
                        
                        Notification::make()
                            ->title('Alert rescheduled')
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Reschedule Alert'),
                
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Are you sure you want to delete this alert? This will stop the automatic stage transition alerts for this crop.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('execute_selected')
                        ->label('Execute Selected')
                        ->action(function (CropTaskManagementService $cropTaskService, $records) {
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
                                ->title("Executed {$successCount} alerts")
                                ->body($failCount > 0 ? "{$failCount} alerts failed" : "Successfully advanced crops to their next stages.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Execute Selected Alerts')
                        ->modalDescription('Are you sure you want to execute all selected alerts now? This will advance crops to their next stages immediately.'),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalDescription('Are you sure you want to delete these alerts? This will stop the automatic stage transition alerts for these crops.'),
                ]),
            ])
            ->emptyStateHeading('No crop alerts')
            ->emptyStateDescription('Alerts will appear here when crops are scheduled for stage transitions.')
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
                    ->label('Recipe')
                    ->getTitleFromRecordUsing(function ($record) {
                        // First try to get from conditions (for backward compatibility)
                        if (isset($record->conditions['variety'])) {
                            return $record->conditions['variety'];
                        }
                        
                        // If not in conditions, get from the crop's recipe relationship
                        $cropId = $record->conditions['crop_id'] ?? null;
                        if (!$cropId) return 'Unknown';
                        
                        $crop = Crop::with(['recipe.seedEntry'])->find($cropId);
                        if (!$crop || !$crop->recipe) return 'Unknown';
                        
                        return $crop->recipe->name ?? 'Unknown';
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
            'index' => Pages\ListCropAlerts::route('/'),
            'create' => Pages\CreateCropAlert::route('/create'),
            'edit' => Pages\EditCropAlert::route('/{record}/edit'),
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