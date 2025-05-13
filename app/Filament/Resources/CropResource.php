<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Pages;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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

class CropResource extends Resource
{
    protected static ?string $model = Crop::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationLabel = 'Grows';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 2;
    
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

                        Forms\Components\DateTimePicker::make('planted_at')
                            ->label('Planted At')
                            ->required()
                            ->default(now()),
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
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('germination_at')
                                    ->label('Germination')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('blackout_at')
                                    ->label('Blackout')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('light_at')
                                    ->label('Light')
                                    ->disabled(),
                                Forms\Components\DateTimePicker::make('harvested_at')
                                    ->label('Harvested')
                                    ->disabled(),
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
        return $table
            ->persistSortInSession()
            ->defaultSort('planted_at', 'desc')
            ->modifyQueryUsing(function (Builder $query): Builder {
                // Check if recipes table exists
                $recipesExists = \Illuminate\Support\Facades\Schema::hasTable('recipes');
                \Illuminate\Support\Facades\Log::debug('Checking recipes table', [
                    'exists' => $recipesExists
                ]);
                
                // Get a sample recipe
                if ($recipesExists) {
                    $sampleRecipe = \Illuminate\Support\Facades\DB::table('recipes')->first();
                    \Illuminate\Support\Facades\Log::debug('Sample recipe', [
                        'recipe' => $sampleRecipe
                    ]);
                }
                
                // Build the query
                $result = $query->select([
                        'crops.recipe_id',
                        'crops.planted_at',
                        'crops.current_stage',
                        DB::raw('MIN(crops.id) as id'),
                        DB::raw('MIN(crops.created_at) as created_at'),
                        DB::raw('MIN(crops.updated_at) as updated_at'),
                        DB::raw('MIN(crops.planting_at) as planting_at'),
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
                    ->groupBy(['crops.recipe_id', 'crops.planted_at', 'crops.current_stage']);
                
                // Log the query
                \Illuminate\Support\Facades\Log::debug('CropResource query', [
                    'sql' => $result->toSql(),
                    'bindings' => $result->getBindings()
                ]);
                
                return $result;
            })
            ->recordUrl(fn ($record) => static::getUrl('edit', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('recipe_name')
                    ->label('Recipe')
                    ->weight('bold')
                    ->getStateUsing(function ($record) {
                        // Log what we're getting for debugging
                        \Illuminate\Support\Facades\Log::debug('Recipe name from record:', [
                            'record_id' => $record->id ?? 'unknown',
                            'recipe_id' => $record->recipe_id ?? null,
                            'recipe_name' => $record->recipe_name ?? 'not found',
                            'record_attrs' => (array) $record,
                        ]);
                        
                        // Return the recipe name from the query-generated field
                        return $record->recipe_name ?? "Recipe #{$record->recipe_id}";
                    })
                    ->searchable(false)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('debug_data')
                    ->label('Debug Data')
                    ->getStateUsing(function ($record) {
                        $data = (array) $record;
                        // Log the full record data
                        \Illuminate\Support\Facades\Log::debug('Record data:', [
                            'data' => $data,
                            'recipe_id' => $record->recipe_id ?? null,
                            'recipe_name' => $record->recipe_name ?? null
                        ]);
                        return json_encode($data, JSON_PRETTY_PRINT);
                    })
                    ->extraAttributes(['class' => 'prose'])
                    ->toggleable()
                    ->wrap()
                    ->size('xs'),
                Tables\Columns\TextColumn::make('tray_count')
                    ->label('# of Trays')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tray_numbers')
                    ->label('Tray Numbers')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
                Tables\Columns\TextColumn::make('planted_at')
                    ->label('Planted')
                    ->date()
                    ->sortable()
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
                Tables\Grouping\Group::make('planted_at')
                    ->label('Plant Date')
                    ->date(),
                Tables\Grouping\Group::make('current_stage')
                    ->label('Growth Stage'),
            ])
            ->defaultGroup('recipe_name')
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
                    ->modalDescription('This will update the current stage of the crop and mark the corresponding task as completed.')
                    ->action(function (Crop $record) {
                        $nextStage = $record->getNextStage();
                        
                        if (!$nextStage) {
                            \Filament\Notifications\Notification::make()
                                ->title('Already Harvested')
                                ->body('This crop has already reached its final stage.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Update Crop stage and timestamp
                        $timestampField = "{$nextStage}_at";
                        $record->current_stage = $nextStage;
                        $record->$timestampField = now();
                        $record->save();
                        
                        // Deactivate the corresponding TaskSchedule
                        $task = \App\Models\TaskSchedule::where('resource_type', 'crops')
                            ->where('conditions->crop_id', $record->id)
                            ->where('conditions->target_stage', $nextStage)
                            ->where('is_active', true)
                            ->first();
                            
                        if ($task) {
                            $task->update([
                                'is_active' => false,
                                'last_run_at' => now(),
                            ]);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Stage Advanced')
                            ->body("Crop successfully advanced to {$nextStage}.")
                            ->success()
                            ->send();
                    }),
                Action::make('harvest')
                    ->label('Harvest')
                    ->icon('heroicon-o-scissors')
                    ->color('success')
                    ->visible(fn (Crop $record): bool => $record->current_stage === 'light')
                    ->requiresConfirmation()
                    ->modalHeading('Harvest Crop?')
                    ->modalDescription('This will mark the crop as harvested and record the harvest weight.')
                    ->form([
                        Forms\Components\TextInput::make('harvest_weight_grams')
                            ->label('Harvest Weight Per Tray (grams)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(10000),
                    ])
                    ->action(function (Crop $record, array $data) {
                        $record->current_stage = 'harvested';
                        $record->harvested_at = now();
                        $record->harvest_weight_grams = $data['harvest_weight_grams'];
                        $record->save();
                        
                        // Deactivate any active task schedules for this crop
                        \App\Models\TaskSchedule::where('resource_type', 'crops')
                            ->where('conditions->crop_id', $record->id)
                            ->where('is_active', true)
                            ->update([
                                'is_active' => false,
                                'last_run_at' => now(),
                            ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Crop Harvested')
                            ->body('The crop has been successfully harvested.')
                            ->success()
                            ->send();
                    }),
                Action::make('suspendWatering')
                    ->label('Suspend Watering')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->visible(fn (Crop $record): bool => $record->current_stage === 'light' && !$record->isWateringSuspended())
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Watering?')
                    ->modalDescription('This will mark watering as suspended for this crop and complete the corresponding task.')
                    ->action(function (Crop $record) {
                        // Suspend watering on the Crop model
                        $record->suspendWatering(); // This method should set the timestamp and save

                        // Deactivate the corresponding TaskSchedule
                        $task = \App\Models\TaskSchedule::where('resource_type', 'crops')
                            ->where('conditions->crop_id', $record->id)
                            ->where('task_name', 'suspend_watering') // Match the task name
                            ->where('is_active', true)
                            ->first();
                            
                        if ($task) {
                            $task->update([
                                'is_active' => false,
                                'last_run_at' => now(),
                            ]);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Watering Suspended')
                            ->body('Watering has been successfully suspended for this crop.')
                            ->success()
                            ->send();
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
                                ->where('planted_at', $record->planted_at)
                                ->where('current_stage', $record->current_stage)
                                ->pluck('tray_number')
                                ->toArray();
                            
                            // Delete all crops in this batch
                            $count = Crop::where('recipe_id', $record->recipe_id)
                                ->where('planted_at', $record->planted_at)
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
                ]),
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
} 