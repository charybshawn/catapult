<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Pages;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\RecipeResource;
use Filament\Forms\Components\Actions\Action as FilamentAction;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use App\Filament\Resources\BaseResource;
use App\Filament\Forms\Components\Common as FormCommon;
use App\Filament\Traits\CsvExportAction;

class CropResource extends BaseResource
{
    use CsvExportAction;
    
    protected static ?string $model = Crop::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationLabel = 'Grows';
    protected static ?string $navigationGroup = 'Production';
    protected static ?int $navigationSort = 2;
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Grow Details')
                    ->schema([
                        Forms\Components\Select::make('recipe_id')
                            ->label('Recipe')
                            ->relationship('recipe', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm(RecipeResource::getFormSchema()),

                        Forms\Components\DateTimePicker::make('planting_at')
                            ->label('Planted At')
                            ->required()
                            ->default(now())
                            ->seconds(false),
                        Forms\Components\Select::make('current_stage')
                            ->label('Current Stage')
                            ->options([
                                'germination' => 'Germination',
                                'blackout' => 'Blackout',
                                'light' => 'Light',
                                'harvested' => 'Harvested',
                            ])
                            ->required()
                            ->default('germination')
                            ->visible(fn ($livewire) => !($livewire instanceof Pages\CreateCrop)),
                        Forms\Components\TextInput::make('harvest_weight_grams')
                            ->label('Harvest Weight Per Tray (grams)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10000)
                            ->helperText('Can be added at any stage, but required when harvested')
                            ->required(fn (Forms\Get $get) => $get('current_stage') === 'harvested')
                            ->visible(fn ($livewire) => !($livewire instanceof Pages\CreateCrop)),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Tray Management')
                    ->schema([
                        Forms\Components\TagsInput::make('tray_numbers')
                            ->label('Tray Numbers')
                            ->placeholder('Add tray numbers')
                            ->separator(',')
                            ->helperText('Enter tray numbers or IDs for this grow batch (alphanumeric supported)')
                            ->rules(['array', 'min:1'])
                            ->nestedRecursiveRules(['string', 'max:20'])
                            ->visible(fn ($livewire) => $livewire instanceof Pages\CreateCrop),
                        
                        Forms\Components\TagsInput::make('tray_numbers')
                            ->label('Tray Numbers')
                            ->placeholder('Edit tray numbers')
                            ->separator(',')
                            ->helperText('Edit the tray numbers or IDs for this grow batch (alphanumeric supported)')
                            ->rules(['array', 'min:1'])
                            ->nestedRecursiveRules(['string', 'max:20'])
                            ->visible(fn ($livewire) => !($livewire instanceof Pages\CreateCrop))
                            ->afterStateHydrated(function ($component, $state) {
                                if (is_array($state)) {
                                    $component->state(array_values($state));
                                }
                            }),
                    ]),
                
                Forms\Components\Section::make('Growth Stage Timestamps')
                    ->description('Record of when each growth stage began')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\DateTimePicker::make('planting_at')
                                    ->label('Planting')
                                    ->helperText('Changes to planting date will adjust all stage timestamps proportionally')
                                    ->seconds(false),
                                Forms\Components\DateTimePicker::make('germination_at')
                                    ->label('Germination')
                                    ->helperText('When germination stage began')
                                    ->seconds(false),
                                Forms\Components\DateTimePicker::make('blackout_at')
                                    ->label('Blackout')
                                    ->helperText('When blackout stage began')
                                    ->seconds(false),
                                Forms\Components\DateTimePicker::make('light_at')
                                    ->label('Light')
                                    ->helperText('When light stage began')
                                    ->seconds(false),
                                Forms\Components\DateTimePicker::make('harvested_at')
                                    ->label('Harvested')
                                    ->helperText('When crop was harvested')
                                    ->seconds(false),
                            ])
                            ->columns(3),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visible(fn ($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::configureTableDefaults($table)
            ->defaultSort('planting_at', 'desc')
            ->deferLoading()
            ->modifyQueryUsing(function (Builder $query): Builder {
                // Build the query
                return $query->select([
                        'crops.recipe_id',
                        'crops.planting_at',
                        'crops.current_stage',
                        DB::raw('MIN(crops.id) as id'),
                        DB::raw('MIN(crops.created_at) as created_at'),
                        DB::raw('MIN(crops.updated_at) as updated_at'),
                        DB::raw('MIN(crops.germination_at) as germination_at'),
                        DB::raw('MIN(crops.blackout_at) as blackout_at'),
                        DB::raw('MIN(crops.light_at) as light_at'),
                        DB::raw('MIN(crops.harvested_at) as harvested_at'),
                        DB::raw('AVG(crops.harvest_weight_grams) as harvest_weight_grams'),
                        DB::raw('MIN(crops.time_to_next_stage_minutes) as time_to_next_stage_minutes'),
                        DB::raw('MIN(crops.time_to_next_stage_display) as time_to_next_stage_display'),
                        DB::raw('MIN(crops.stage_age_minutes) as stage_age_minutes'),
                        DB::raw('MIN(crops.stage_age_display) as stage_age_display'),
                        DB::raw('MIN(crops.total_age_minutes) as total_age_minutes'),
                        DB::raw('MIN(crops.total_age_display) as total_age_display'),
                        DB::raw('MIN(crops.expected_harvest_at) as expected_harvest_at'),
                        DB::raw('MIN(crops.watering_suspended_at) as watering_suspended_at'),
                        DB::raw('MIN(crops.notes) as notes'),
                        DB::raw('COUNT(crops.id) as tray_count'),
                        DB::raw('GROUP_CONCAT(DISTINCT crops.tray_number ORDER BY crops.tray_number SEPARATOR ", ") as tray_numbers'),
                        DB::raw('(SELECT recipes.name FROM recipes WHERE recipes.id = crops.recipe_id) as recipe_name')
                    ])
                    ->from('crops')
                    ->groupBy(['crops.recipe_id', 'crops.planting_at', 'crops.current_stage']);
            })
            ->recordUrl(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('recipe_name')
                    ->label('Recipe')
                    ->weight('bold')
                    ->getStateUsing(function ($record) {
                        return $record->recipe_name ?? "Recipe #{$record->recipe_id}";
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('recipe', function (Builder $query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(false),
                Tables\Columns\ViewColumn::make('tray_numbers')
                    ->label('Trays')
                    ->view('components.tray-badges')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('tray_number', 'like', "%{$search}%");
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('tray_count', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('planting_at')
                    ->label('Planted')
                    ->date()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('crops.planting_at', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('current_stage')
                    ->label('Current Stage')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'germination' => 'info',
                        'blackout' => 'warning',
                        'light' => 'success',
                        'harvested' => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stage_age_display')
                    ->label('Time in Stage')
                    ->getStateUsing(fn ($record): string => $record->getStageAgeStatus())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('stage_age_minutes', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('time_to_next_stage_display')
                    ->label('Time to Next Stage')
                    ->getStateUsing(fn ($record): string => $record->timeToNextStage())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('time_to_next_stage_minutes', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_age_display')
                    ->label('Total Age')
                    ->getStateUsing(fn ($record): string => $record->getTotalAgeStatus())
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('total_age_minutes', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expected_harvest_at')
                    ->label('Expected Harvest')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('harvest_weight_grams')
                    ->label('Harvest Weight')
                    ->formatStateUsing(fn ($state, $record) => 
                        $state && isset($record->tray_count) 
                            ? ($state * $record->tray_count) . "g total / " . $state . "g per tray" 
                            : ($state ? $state . "g" : '-')
                    )
                    ->sortable()
                    ->toggleable(),
            ])
            ->groups([
                Tables\Grouping\Group::make('recipe_name')
                    ->label('Recipe'),
                Tables\Grouping\Group::make('planting_at')
                    ->label('Plant Date')
                    ->date(),
                Tables\Grouping\Group::make('current_stage')
                    ->label('Growth Stage'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('current_stage')
                    ->label('Stage')
                    ->options([
                        'germination' => 'Germination',
                        'blackout' => 'Blackout',
                        'light' => 'Light',
                        'harvested' => 'Harvested',
                    ]),
                Tables\Filters\TernaryFilter::make('active_crops')
                    ->label('Active Crops')
                    ->placeholder('All Crops')
                    ->trueLabel('Active Only')
                    ->falseLabel('Harvested Only')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('current_stage', '!=', 'harvested'),
                        false: fn (Builder $query): Builder => $query->where('current_stage', '=', 'harvested'),
                        blank: fn (Builder $query): Builder => $query,
                    )
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\Action::make('debug')
                    ->label('')
                    ->icon('heroicon-o-code-bracket')
                    ->tooltip('Debug Info')
                    ->action(function (Crop $record) {
                        // Get the recipe information
                        $recipe = $record->recipe;
                        
                        // Calculate current times for debugging
                        $now = now();
                        
                        // Prepare crop data
                        $cropData = [
                            'ID' => $record->id,
                            'Tray Number' => $record->tray_number,
                            'Current Stage' => $record->current_stage,
                            'Planted At' => $record->planting_at ? $record->planting_at->format('Y-m-d H:i') : 'N/A',
                            'Germination At' => $record->germination_at ? $record->germination_at->format('Y-m-d H:i') : 'N/A',
                            'Blackout At' => $record->blackout_at ? $record->blackout_at->format('Y-m-d H:i') : 'N/A',
                            'Light At' => $record->light_at ? $record->light_at->format('Y-m-d H:i') : 'N/A',
                            'Harvested At' => $record->harvested_at ? $record->harvested_at->format('Y-m-d H:i') : 'N/A',
                            'Stage Updated At' => $record->stage_updated_at ? $record->stage_updated_at->format('Y-m-d H:i') : 'N/A',
                            'Current Time' => $now->format('Y-m-d H:i'),
                        ];
                        
                        // Prepare recipe data if available
                        $recipeData = [];
                        if ($recipe) {
                            $recipeData = [
                                'ID' => $recipe->id,
                                'Name' => $recipe->name,
                                'Seed Cultivar' => $recipe->seedEntry ? $recipe->seedEntry->common_name . ' - ' . $recipe->seedEntry->cultivar_name : 'N/A',
                                'Germination Days' => $recipe->germination_days,
                                'Blackout Days' => $recipe->blackout_days,
                                'Light Days' => $recipe->light_days,
                                'Days to Maturity' => $recipe->days_to_maturity,
                            ];
                        }
                        
                        // Add time calculations
                        $timeCalculations = [];
                        
                        // Stage age calculation
                        $stageField = "{$record->current_stage}_at";
                        if ($record->$stageField) {
                            $stageStart = Carbon::parse($record->$stageField);
                            $stageAgeMinutes = $now->diffInMinutes($stageStart);
                            $stageAgeDuration = $now->diff($stageStart);
                            
                            $timeCalculations['Stage Age Calculation'] = [
                                'Stage Start Time' => $stageStart->format('Y-m-d H:i:s'),
                                'Current Time' => $now->format('Y-m-d H:i:s'),
                                'Difference (minutes)' => $stageAgeMinutes,
                                'Human Format' => $stageAgeDuration->days . 'd ' . $stageAgeDuration->h . 'h ' . $stageAgeDuration->i . 'm',
                                'DB Stored Value' => $record->stage_age_minutes . ' minutes',
                                'DB Display Value' => $record->stage_age_display,
                            ];
                        }
                        
                        // Time to next stage calculation
                        if ($recipe && $record->current_stage !== 'harvested') {
                            $nextStage = match($record->current_stage) {
                                'planted' => 'germination',
                                'germination' => 'blackout',
                                'blackout' => 'light',
                                'light' => 'harvested',
                                default => null
                            };
                            
                            if ($nextStage) {
                                $phaseDurationField = match($record->current_stage) {
                                    'planted' => 'germination_days',
                                    'germination' => 'blackout_days',
                                    'blackout' => 'light_days',
                                    default => null
                                };
                                
                                if ($phaseDurationField) {
                                    $phaseDuration = $recipe->$phaseDurationField * 1440; // Convert days to minutes
                                    $timeElapsed = $stageAgeMinutes ?? 0;
                                    $timeRemaining = max(0, $phaseDuration - $timeElapsed);
                                    
                                    $daysRemaining = floor($timeRemaining / 1440);
                                    $hoursRemaining = floor(($timeRemaining % 1440) / 60);
                                    $minutesRemaining = $timeRemaining % 60;
                                    
                                    $timeCalculations['Time to Next Stage Calculation'] = [
                                        'Current Stage' => $record->current_stage,
                                        'Next Stage' => $nextStage,
                                        'Phase Duration' => $recipe->$phaseDurationField . ' days (' . $phaseDuration . ' minutes)',
                                        'Time Elapsed' => $timeElapsed . ' minutes',
                                        'Time Remaining' => $timeRemaining . ' minutes',
                                        'Human Format' => $daysRemaining . 'd ' . $hoursRemaining . 'h ' . $minutesRemaining . 'm',
                                        'DB Stored Value' => $record->time_to_next_stage_minutes . ' minutes',
                                        'DB Display Value' => $record->time_to_next_stage_display,
                                    ];
                                }
                            }
                        }
                        
                        // Format the debug data for display in a notification
                        $cropDataHtml = '<div class="mb-4">';
                        $cropDataHtml .= '<h3 class="text-lg font-medium mb-2">Crop Data</h3>';
                        $cropDataHtml .= '<div class="overflow-auto max-h-48 space-y-1">';
                        
                        foreach ($cropData as $key => $value) {
                            $cropDataHtml .= '<div class="flex">';
                            $cropDataHtml .= '<span class="font-medium w-32">' . $key . ':</span>';
                            $cropDataHtml .= '<span class="text-gray-600">' . $value . '</span>';
                            $cropDataHtml .= '</div>';
                        }
                        
                        $cropDataHtml .= '</div></div>';
                        
                        // Format recipe data if available
                        $recipeDataHtml = '';
                        if (!empty($recipeData)) {
                            $recipeDataHtml = '<div class="mb-4">';
                            $recipeDataHtml .= '<h3 class="text-lg font-medium mb-2">Recipe Data</h3>';
                            $recipeDataHtml .= '<div class="overflow-auto max-h-48 space-y-1">';
                            
                            foreach ($recipeData as $key => $value) {
                                $recipeDataHtml .= '<div class="flex">';
                                $recipeDataHtml .= '<span class="font-medium w-32">' . $key . ':</span>';
                                $recipeDataHtml .= '<span class="text-gray-600">' . $value . '</span>';
                                $recipeDataHtml .= '</div>';
                            }
                            
                            $recipeDataHtml .= '</div></div>';
                        } else {
                            $recipeDataHtml = '<div class="text-gray-500 mb-4">Recipe not found</div>';
                        }
                        
                        // Format time calculations
                        $timeCalcHtml = '<div class="mb-4">';
                        $timeCalcHtml .= '<h3 class="text-lg font-medium mb-2">Time Calculations</h3>';
                        $timeCalcHtml .= '<div class="overflow-auto max-h-80 space-y-4">';
                        
                        foreach ($timeCalculations as $section => $data) {
                            $timeCalcHtml .= '<div class="border-t pt-2">';
                            $timeCalcHtml .= '<h4 class="font-medium text-blue-600 mb-1">' . $section . '</h4>';
                            
                            foreach ($data as $key => $value) {
                                $timeCalcHtml .= '<div class="flex">';
                                $timeCalcHtml .= '<span class="font-medium w-40 text-sm">' . $key . ':</span>';
                                $timeCalcHtml .= '<span class="text-gray-600 text-sm">' . $value . '</span>';
                                $timeCalcHtml .= '</div>';
                            }
                            
                            $timeCalcHtml .= '</div>';
                        }
                        
                        $timeCalcHtml .= '</div></div>';
                        
                        Notification::make()
                            ->title('Debug Information')
                            ->body($cropDataHtml . $recipeDataHtml . $timeCalcHtml)
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('close')
                                    ->label('Close')
                                    ->color('gray')
                            ])
                            ->send();
                    }),
                
                Action::make('advanceStage')
                    ->label(function (Crop $record): string {
                        $nextStage = $record->getNextStage();
                        return $nextStage ? 'Advance to ' . ucfirst($nextStage) : 'Harvested';
                    })
                    ->icon('heroicon-o-chevron-double-right')
                    ->color('success')
                    ->visible(fn (Crop $record): bool => $record->current_stage !== 'harvested')
                    ->requiresConfirmation()
                    ->modalHeading(function (Crop $record): string {
                        $nextStage = $record->getNextStage();
                        return 'Advance to ' . ucfirst($nextStage) . '?';
                    })
                    ->modalDescription('This will update the current stage of all crops in this batch.')
                    ->form([
                        Forms\Components\DateTimePicker::make('advancement_timestamp')
                            ->label('When did this advancement occur?')
                            ->default(now())
                            ->seconds(false)
                            ->required()
                            ->maxDate(now())
                            ->helperText('Specify the actual time when the stage advancement happened'),
                    ])
                    ->action(function (Crop $record, array $data) {
                        $nextStage = $record->getNextStage();
                        
                        if (!$nextStage) {
                            \Filament\Notifications\Notification::make()
                                ->title('Already Harvested')
                                ->body('This crop has already reached its final stage.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Begin transaction for safety
                        DB::beginTransaction();
                        
                        try {
                            // Find all crops in this batch with eager loading to avoid lazy loading violations
                            $crops = Crop::with('recipe')
                                ->where('recipe_id', $record->recipe_id)
                                ->where('planting_at', $record->planting_at)
                                ->where('current_stage', $record->current_stage)
                                ->get();
                            
                            $count = $crops->count();
                            $trayNumbers = $crops->pluck('tray_number')->toArray();
                            
                            // Update all crops in this batch
                            $advancementTime = $data['advancement_timestamp'];
                            foreach ($crops as $crop) {
                                $timestampField = "{$nextStage}_at";
                                $crop->current_stage = $nextStage;
                                $crop->$timestampField = $advancementTime;
                                $crop->save();
                                
                                // Deactivate the corresponding TaskSchedule
                                $task = \App\Models\TaskSchedule::where('resource_type', 'crops')
                                    ->where('conditions->crop_id', $crop->id)
                                    ->where('conditions->target_stage', $nextStage)
                                    ->where('is_active', true)
                                    ->first();
                                    
                                if ($task) {
                                    $task->update([
                                        'is_active' => false,
                                        'last_run_at' => now(),
                                    ]);
                                }
                            }
                            
                            DB::commit();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Batch Advanced')
                                ->body("Successfully advanced {$count} tray(s) to {$nextStage}.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Failed to advance stage: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('harvest')
                    ->label('Harvest')
                    ->icon('heroicon-o-scissors')
                    ->color('success')
                    ->visible(fn (Crop $record): bool => $record->current_stage === 'light')
                    ->requiresConfirmation()
                    ->modalHeading('Harvest Crop?')
                    ->modalDescription('This will mark all crops in this batch as harvested.')
                    ->form([
                        Forms\Components\TextInput::make('harvest_weight_grams')
                            ->label('Harvest Weight Per Tray (grams)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10000),
                        Forms\Components\DateTimePicker::make('harvest_timestamp')
                            ->label('When was this harvested?')
                            ->default(now())
                            ->seconds(false)
                            ->required()
                            ->maxDate(now())
                            ->helperText('Specify the actual time when the harvest occurred'),
                    ])
                    ->action(function (Crop $record, array $data) {
                        // Begin transaction for safety
                        DB::beginTransaction();
                        
                        try {
                            // Find all crops in this batch with eager loading to avoid lazy loading violations
                            $crops = Crop::with('recipe')
                                ->where('recipe_id', $record->recipe_id)
                                ->where('planting_at', $record->planting_at)
                                ->where('current_stage', $record->current_stage)
                                ->get();
                            
                            $count = $crops->count();
                            $trayNumbers = $crops->pluck('tray_number')->toArray();
                            
                            // Update all crops in this batch
                            $harvestTime = $data['harvest_timestamp'];
                            foreach ($crops as $crop) {
                                $crop->current_stage = 'harvested';
                                $crop->harvested_at = $harvestTime;
                                $crop->harvest_weight_grams = $data['harvest_weight_grams'];
                                $crop->save();
                                
                                // Deactivate any active task schedules for this crop
                                \App\Models\TaskSchedule::where('resource_type', 'crops')
                                    ->where('conditions->crop_id', $crop->id)
                                    ->where('is_active', true)
                                    ->update([
                                        'is_active' => false,
                                        'last_run_at' => now(),
                                    ]);
                            }
                            
                            DB::commit();
                            
                            // Calculate total harvest weight
                            $totalWeight = $data['harvest_weight_grams'] * $count;
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Batch Harvested')
                                ->body("Successfully harvested {$count} tray(s) with a total weight of {$totalWeight}g.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Failed to harvest batch: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('rollbackStage')
                    ->label(function (Crop $record): string {
                        $previousStage = $record->getPreviousStage();
                        return $previousStage ? 'Rollback to ' . ucfirst($previousStage) : 'Cannot Rollback';
                    })
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (Crop $record): bool => $record->current_stage !== 'germination')
                    ->requiresConfirmation()
                    ->modalHeading(function (Crop $record): string {
                        $previousStage = $record->getPreviousStage();
                        return 'Rollback to ' . ucfirst($previousStage) . '?';
                    })
                    ->modalDescription('This will revert all crops in this batch to the previous stage by removing the current stage timestamp.')
                    ->action(function (Crop $record) {
                        $previousStage = $record->getPreviousStage();
                        
                        if (!$previousStage) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cannot Rollback')
                                ->body('This crop is already at the first stage.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Begin transaction for safety
                        DB::beginTransaction();
                        
                        try {
                            // Find all crops in this batch with eager loading to avoid lazy loading violations
                            $crops = Crop::with('recipe')
                                ->where('recipe_id', $record->recipe_id)
                                ->where('planting_at', $record->planting_at)
                                ->where('current_stage', $record->current_stage)
                                ->get();
                            
                            $count = $crops->count();
                            $trayNumbers = $crops->pluck('tray_number')->toArray();
                            
                            // Update all crops in this batch
                            foreach ($crops as $crop) {
                                // Clear the timestamp for the current stage
                                $currentTimestampField = "{$record->current_stage}_at";
                                $crop->$currentTimestampField = null;
                                
                                // Set the previous stage
                                $crop->current_stage = $previousStage;
                                
                                $crop->save();
                            }
                            
                            DB::commit();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Batch Rolled Back')
                                ->body("Successfully rolled back {$count} tray(s) to {$previousStage}.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Failed to rollback stage: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('suspendWatering')
                    ->label('Suspend Watering')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->visible(fn (Crop $record): bool => $record->current_stage === 'light' && !$record->isWateringSuspended())
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Watering?')
                    ->modalDescription('This will mark watering as suspended for all crops in this batch.')
                    ->form([
                        Forms\Components\DateTimePicker::make('suspension_timestamp')
                            ->label('When was watering suspended?')
                            ->default(now())
                            ->seconds(false)
                            ->required()
                            ->maxDate(now())
                            ->helperText('Specify the actual time when watering was suspended'),
                    ])
                    ->action(function (Crop $record, array $data) {
                        // Begin transaction for safety
                        DB::beginTransaction();
                        
                        try {
                            // Find all crops in this batch with eager loading to avoid lazy loading violations
                            $crops = Crop::with('recipe')
                                ->where('recipe_id', $record->recipe_id)
                                ->where('planting_at', $record->planting_at)
                                ->where('current_stage', $record->current_stage)
                                ->get();
                            
                            $count = $crops->count();
                            $trayNumbers = $crops->pluck('tray_number')->toArray();
                            
                            // Update all crops in this batch
                            $suspensionTime = $data['suspension_timestamp'];
                            foreach ($crops as $crop) {
                                // Suspend watering on the Crop model with custom timestamp
                                $crop->suspendWatering($suspensionTime);
                                
                                // Deactivate the corresponding TaskSchedule
                                $task = \App\Models\TaskSchedule::where('resource_type', 'crops')
                                    ->where('conditions->crop_id', $crop->id)
                                    ->where('task_name', 'suspend_watering') // Match the task name
                                    ->where('is_active', true)
                                    ->first();
                                    
                                if ($task) {
                                    $task->update([
                                        'is_active' => false,
                                        'last_run_at' => now(),
                                    ]);
                                }
                            }
                            
                            DB::commit();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Watering Suspended for Batch')
                                ->body("Successfully suspended watering for {$count} tray(s).")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Failed to suspend watering: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Entire Grow Batch?')
                    ->modalDescription(fn (Crop $record) => "This will delete all trays with the same planting date and stage.")
                    ->modalSubmitActionLabel('Yes, Delete All Trays')
                    ->action(function (Crop $record) {
                        // Begin transaction for safety
                        DB::beginTransaction();
                        
                        try {
                            // Find all tray numbers in this batch
                            $trayNumbers = Crop::where('recipe_id', $record->recipe_id)
                                ->where('planting_at', $record->planting_at)
                                ->where('current_stage', $record->current_stage)
                                ->pluck('tray_number')
                                ->toArray();
                            
                            // Delete all crops in this batch
                            $count = Crop::where('recipe_id', $record->recipe_id)
                                ->where('planting_at', $record->planting_at)
                                ->where('current_stage', $record->current_stage)
                                ->delete();
                            
                            DB::commit();
                            
                            // Show a detailed notification
                            \Filament\Notifications\Notification::make()
                                ->title('Grow Batch Deleted')
                                ->body("Successfully deleted {$count} tray(s): " . implode(', ', $trayNumbers))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Failed to delete grow batch: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('advance_stage_bulk')
                        ->label('Advance Stage')
                        ->icon('heroicon-o-arrow-right')
                        ->form([
                            Forms\Components\DateTimePicker::make('advancement_timestamp')
                                ->label('When did this advancement occur?')
                                ->default(now())
                                ->seconds(false)
                                ->required()
                                ->maxDate(now())
                                ->helperText('Specify the actual time when the stage advancement happened'),
                        ])
                        ->action(function ($records, array $data) {
                            $totalCount = 0;
                            $batchCount = 0;
                            
                            DB::beginTransaction();
                            try {
                                foreach ($records as $record) {
                                    if ($record->current_stage !== 'harvested') {
                                        // Find ALL crops in this batch
                                        $crops = Crop::with('recipe')
                                            ->where('recipe_id', $record->recipe_id)
                                            ->where('planting_at', $record->planting_at)
                                            ->where('current_stage', $record->current_stage)
                                            ->get();
                                        
                                        $nextStage = $crops->first()->getNextStage();
                                        if ($nextStage) {
                                            $advancementTime = $data['advancement_timestamp'];
                                            foreach ($crops as $crop) {
                                                $timestampField = "{$nextStage}_at";
                                                $crop->current_stage = $nextStage;
                                                $crop->$timestampField = $advancementTime;
                                                $crop->save();
                                                
                                                // Deactivate the corresponding TaskSchedule
                                                $task = \App\Models\TaskSchedule::where('resource_type', 'crops')
                                                    ->where('conditions->crop_id', $crop->id)
                                                    ->where('conditions->target_stage', $nextStage)
                                                    ->where('is_active', true)
                                                    ->first();
                                                    
                                                if ($task) {
                                                    $task->update([
                                                        'is_active' => false,
                                                        'last_run_at' => now(),
                                                    ]);
                                                }
                                            }
                                            $totalCount += $crops->count();
                                            $batchCount++;
                                        }
                                    }
                                }
                                
                                DB::commit();
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Batches Advanced')
                                    ->body("Successfully advanced {$batchCount} batch(es) containing {$totalCount} tray(s) to the next stage.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')
                                    ->body('Failed to advance batches: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Advance Selected Batches?')
                        ->modalDescription('This will advance all trays in the selected batches to their next stage.'),
                    Tables\Actions\BulkAction::make('rollback_stage_bulk')
                        ->label('Rollback Stage')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->action(function ($records) {
                            $totalCount = 0;
                            $batchCount = 0;
                            $skippedCount = 0;
                            
                            DB::beginTransaction();
                            try {
                                foreach ($records as $record) {
                                    $previousStage = $record->getPreviousStage();
                                    if (!$previousStage) {
                                        $skippedCount++;
                                        continue;
                                    }
                                    
                                    // Find ALL crops in this batch
                                    $crops = Crop::with('recipe')
                                        ->where('recipe_id', $record->recipe_id)
                                        ->where('planting_at', $record->planting_at)
                                        ->where('current_stage', $record->current_stage)
                                        ->get();
                                    
                                    if ($crops->isNotEmpty()) {
                                        foreach ($crops as $crop) {
                                            // Clear the timestamp for the current stage
                                            $currentTimestampField = "{$crop->current_stage}_at";
                                            $crop->$currentTimestampField = null;
                                            
                                            // Set the previous stage
                                            $crop->current_stage = $previousStage;
                                            
                                            $crop->save();
                                        }
                                        $totalCount += $crops->count();
                                        $batchCount++;
                                    }
                                }
                                
                                DB::commit();
                                
                                $message = "Successfully rolled back {$batchCount} batch(es) containing {$totalCount} tray(s).";
                                if ($skippedCount > 0) {
                                    $message .= " Skipped {$skippedCount} batch(es) already at first stage.";
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Batches Rolled Back')
                                    ->body($message)
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                DB::rollBack();
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')
                                    ->body('Failed to rollback batches: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Rollback Selected Batches?')
                        ->modalDescription('This will revert all trays in the selected batches to their previous stage by removing the current stage timestamp.'),
                ]),
            ])
            ->headerActions([
                static::getCsvExportAction(),
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
            'index' => Pages\ListCrops::route('/'),
            'create' => Pages\CreateCrop::route('/create'),
            'edit' => Pages\EditCrop::route('/{record}/edit'),
        ];
    }
    
    /**
     * Define CSV export columns for Crops
     */
    protected static function getCsvExportColumns(): array
    {
        // Get core columns but exclude some redundant/confusing ones
        $coreColumns = [
            'id' => 'ID',
            'recipe_id' => 'Recipe ID',
            'order_id' => 'Order ID', 
            'tray_number' => 'Tray Number',
            'current_stage' => 'Current Stage',
            'planting_at' => 'Planted Date',
            'germination_at' => 'Germination Date',
            'blackout_at' => 'Blackout Date',
            'light_at' => 'Light Date',
            'harvested_at' => 'Harvested Date',
            'harvest_weight_grams' => 'Harvest Weight (g)',
            'notes' => 'Notes',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
        
        return static::addRelationshipColumns($coreColumns, [
            'recipe' => ['name', 'common_name', 'cultivar_name'],
            'recipe.seedEntry' => ['common_name', 'cultivar_name'],
            'order' => ['customer_name'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['recipe', 'recipe.seedEntry', 'order'];
    }
} 