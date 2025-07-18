<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Pages;
use App\Models\Crop;
use App\Models\Recipe;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
use App\Filament\Traits\HasTimestamps;
use App\Filament\Traits\HasStandardActions;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class CropResource extends BaseResource
{
    use CsvExportAction;
    use HasTimestamps;
    use HasStandardActions;
    
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
                            ->reactive()
                            ->createOptionForm(RecipeResource::getFormSchema())
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                // Update soaking information when recipe changes
                                if ($state) {
                                    $recipe = \App\Models\Recipe::find($state);
                                    if ($recipe && $recipe->requiresSoaking()) {
                                        $set('soaking_duration_display', $recipe->seed_soak_hours . ' hours');
                                        $set('soaking_at', now());
                                        static::updatePlantingDate($set, $get);
                                    }
                                }
                            }),

                        Forms\Components\Section::make('Soaking Information')
                            ->schema([
                                Forms\Components\Placeholder::make('soaking_required_info')
                                    ->label('')
                                    ->content(fn (Get $get) => static::getSoakingRequiredInfo($get))
                                    ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get)),
                                Forms\Components\TextInput::make('soaking_duration_display')
                                    ->label('Soaking Duration')
                                    ->disabled()
                                    ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                                    ->dehydrated(false),
                            ])
                            ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                            ->compact(),

                        Forms\Components\DateTimePicker::make('soaking_at')
                            ->label('Soaking Started At')
                            ->seconds(false)
                            ->default(now())
                            ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                            ->required(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                static::updatePlantingDate($set, $get);
                            }),

                        Forms\Components\DateTimePicker::make('planting_at')
                            ->label('Planting Date')
                            ->required()
                            ->default(now())
                            ->seconds(false)
                            ->helperText(fn (Get $get) => static::checkRecipeRequiresSoaking($get)
                                ? 'Auto-calculated from soaking start time + duration. You can override if needed.'
                                : 'When the crop will be planted'),
                        Forms\Components\Select::make('current_stage_id')
                            ->label('Current Stage')
                            ->relationship('currentStage', 'name')
                            ->required()
                            ->default(function (Get $get) {
                                $recipeId = $get('recipe_id');
                                if ($recipeId) {
                                    $recipe = \App\Models\Recipe::find($recipeId);
                                    if ($recipe && $recipe->requiresSoaking()) {
                                        $soakingStage = \App\Models\CropStage::findByCode('soaking');
                                        if ($soakingStage) {
                                            return $soakingStage->id;
                                        }
                                    }
                                }
                                return \App\Models\CropStage::findByCode('germination')->id;
                            })
                            ->visible(fn ($livewire) => !($livewire instanceof Pages\CreateCrop)),
                        Forms\Components\TextInput::make('harvest_weight_grams')
                            ->label('Harvest Weight Per Tray (grams)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10000)
                            ->helperText('Can be added at any stage, but required when harvested')
                            ->required(function (Forms\Get $get) {
                                $stageId = $get('current_stage_id');
                                if (!$stageId) return false;
                                $stage = \App\Models\CropStage::find($stageId);
                                return $stage?->code === 'harvested';
                            })
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
                            ->helperText(fn (Get $get) => static::checkRecipeRequiresSoaking($get) 
                                ? 'Optional for soaking crops - tray numbers can be assigned later'
                                : 'Enter tray numbers or IDs for this grow batch (alphanumeric supported)')
                            ->rules(fn (Get $get) => static::checkRecipeRequiresSoaking($get) 
                                ? ['array'] 
                                : ['array', 'min:1'])
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
                                Forms\Components\DateTimePicker::make('soaking_at')
                                    ->label('Soaking')
                                    ->helperText('When soaking stage began')
                                    ->seconds(false),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Crop Details')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('variety')
                                ->label('')
                                ->weight('bold')
                                ->size('xl')
                                ->getStateUsing(function ($record) {
                                    if ($record->recipe) {
                                        $varietyService = app(\App\Services\RecipeVarietyService::class);
                                        return $varietyService->getFullVarietyName($record->recipe);
                                    }
                                    return 'Unknown';
                                }),
                            Infolists\Components\TextEntry::make('recipe.name')
                                ->label('')
                                ->color('gray')
                                ->getStateUsing(fn ($record) => $record->recipe?->name ?? 'Unknown Recipe'),
                        ])->columns(1),
                        
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('current_stage')
                                ->label('Status')
                                ->badge()
                                ->getStateUsing(fn ($record) => $record->currentStage?->name ?? 'Unknown')
                                ->color(fn ($record) => $record->currentStage?->color ?? 'gray'),
                            Infolists\Components\TextEntry::make('tray_count')
                                ->label('Tray Count')
                                ->getStateUsing(function ($record) {
                                    $batchCrops = \App\Models\Crop::where('recipe_id', $record->recipe_id)
                                        ->where('planting_at', $record->planting_at)
                                        ->where('current_stage_id', $record->current_stage_id)
                                        ->count();
                                    return $batchCrops;
                                }),
                        ])->columns(2),
                        
                        Infolists\Components\TextEntry::make('stage_age_display')
                            ->label('Time in Stage')
                            ->getStateUsing(function ($record) {
                                // Get a real crop record and force refresh time calculations
                                $realCrop = \App\Models\Crop::with(['currentStage', 'recipe'])
                                    ->where('recipe_id', $record->recipe_id)
                                    ->where('planting_at', $record->planting_at)
                                    ->where('current_stage_id', $record->current_stage_id)
                                    ->first();
                                    
                                if (!$realCrop) {
                                    return 'N/A';
                                }
                                
                                // Force refresh the time calculations
                                $calculator = app(\App\Services\CropTimeCalculator::class);
                                $calculator->updateTimeCalculations($realCrop);
                                
                                return $realCrop->stage_age_display ?? 'N/A';
                            }),
                            
                        Infolists\Components\TextEntry::make('time_to_next_stage_display')
                            ->label('Time to Next Stage')
                            ->getStateUsing(function ($record) {
                                // Get a real crop record and force refresh time calculations
                                $realCrop = \App\Models\Crop::with(['currentStage', 'recipe'])
                                    ->where('recipe_id', $record->recipe_id)
                                    ->where('planting_at', $record->planting_at)
                                    ->where('current_stage_id', $record->current_stage_id)
                                    ->first();
                                    
                                if (!$realCrop) {
                                    return 'N/A';
                                }
                                
                                // Force refresh the time calculations
                                $calculator = app(\App\Services\CropTimeCalculator::class);
                                $calculator->updateTimeCalculations($realCrop);
                                
                                return $realCrop->time_to_next_stage_display ?? 'N/A';
                            }),
                            
                        Infolists\Components\TextEntry::make('total_age_display')
                            ->label('Total Age')
                            ->getStateUsing(function ($record) {
                                // Get a real crop record and force refresh time calculations
                                $realCrop = \App\Models\Crop::with(['currentStage', 'recipe'])
                                    ->where('recipe_id', $record->recipe_id)
                                    ->where('planting_at', $record->planting_at)
                                    ->where('current_stage_id', $record->current_stage_id)
                                    ->first();
                                    
                                if (!$realCrop) {
                                    return 'N/A';
                                }
                                
                                // Force refresh the time calculations
                                $calculator = app(\App\Services\CropTimeCalculator::class);
                                $calculator->updateTimeCalculations($realCrop);
                                
                                return $realCrop->total_age_display ?? 'N/A';
                            }),
                            
                        Infolists\Components\TextEntry::make('planting_at')
                            ->label('Planted Date')
                            ->getStateUsing(function ($record) {
                                if ($record->planting_at) {
                                    $date = is_string($record->planting_at) ? \Carbon\Carbon::parse($record->planting_at) : $record->planting_at;
                                    return $date->format('M j, Y g:i A');
                                }
                                return 'Unknown';
                            }),
                            
                        Infolists\Components\TextEntry::make('expected_harvest_at')
                            ->label('Expected Harvest')
                            ->getStateUsing(function ($record) {
                                if ($record->expected_harvest_at) {
                                    $date = is_string($record->expected_harvest_at) ? \Carbon\Carbon::parse($record->expected_harvest_at) : $record->expected_harvest_at;
                                    return $date->format('M j, Y');
                                }
                                return 'Not calculated';
                            }),
                    ]),
                    
                Infolists\Components\Section::make('Stage Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('stage_timeline')
                            ->label('')
                            ->html()
                            ->getStateUsing(function ($record) {
                                // Get a real crop record to get accurate stage timings
                                $realCrop = \App\Models\Crop::where('recipe_id', $record->recipe_id)
                                    ->where('planting_at', $record->planting_at)
                                    ->where('current_stage_id', $record->current_stage_id)
                                    ->first();
                                    
                                if (!$realCrop) {
                                    return '<div class="text-gray-500">No crop data available</div>';
                                }
                                
                                $dashboard = new \App\Filament\Pages\Dashboard();
                                $reflection = new \ReflectionClass($dashboard);
                                $method = $reflection->getMethod('getStageTimings');
                                $method->setAccessible(true);
                                $stageTimings = $method->invoke($dashboard, $realCrop);
                                
                                $html = '<div class="space-y-2">';
                                foreach ($stageTimings as $stage => $timing) {
                                    $badgeColor = $timing['status'] === 'current' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
                                    $currentLabel = $timing['status'] === 'current' ? '<span class="text-xs text-blue-600 dark:text-blue-400 font-medium ml-2">Current</span>' : '';
                                    
                                    $html .= '<div class="flex items-center justify-between py-1 px-2 rounded ' . ($timing['status'] === 'current' ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-800') . '">';
                                    $html .= '<div class="flex items-center gap-2">';
                                    $html .= '<span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ' . $badgeColor . '">' . ucfirst($stage) . '</span>';
                                    $html .= $currentLabel;
                                    $html .= '</div>';
                                    $html .= '<div class="text-xs text-gray-600 dark:text-gray-400">' . $timing['duration'] . '</div>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';
                                
                                return $html;
                            }),
                    ]),
                    
                Infolists\Components\Section::make('Tray Numbers')
                    ->schema([
                        Infolists\Components\TextEntry::make('tray_numbers')
                            ->label('')
                            ->html()
                            ->getStateUsing(function ($record) {
                                $batchCrops = \App\Models\Crop::where('recipe_id', $record->recipe_id)
                                    ->where('planting_at', $record->planting_at)
                                    ->where('current_stage_id', $record->current_stage_id)
                                    ->get();
                                $trayNumbers = $batchCrops->pluck('tray_number')->sort()->values()->toArray();
                                
                                $html = '<div class="flex flex-wrap gap-1">';
                                foreach ($trayNumbers as $tray) {
                                    $html .= '<span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-200">' . htmlspecialchars($tray) . '</span>';
                                }
                                $html .= '</div>';
                                
                                return $html;
                            }),
                    ]),
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
                        'crops.current_stage_id',
                        DB::raw('MIN(crops.id) as id'),
                        DB::raw('MIN(crops.created_at) as created_at'),
                        DB::raw('MIN(crops.updated_at) as updated_at'),
                        DB::raw('MIN(crops.soaking_at) as soaking_at'),
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
                        DB::raw('MIN(crops.requires_soaking) as requires_soaking'),
                        DB::raw('MIN(crops.notes) as notes'),
                        DB::raw('COUNT(crops.id) as tray_count'),
                        DB::raw('GROUP_CONCAT(DISTINCT crops.tray_number ORDER BY crops.tray_number SEPARATOR ", ") as tray_numbers'),
                        DB::raw('(SELECT recipes.name FROM recipes WHERE recipes.id = crops.recipe_id) as recipe_name')
                    ])
                    ->from('crops')
                    ->groupBy(['crops.recipe_id', 'crops.planting_at', 'crops.current_stage_id']);
            })
            ->recordAction('view')
            ->recordUrl(null)
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
                Tables\Columns\TextColumn::make('current_stage_name')
                    ->label('Current Stage')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (isset($record->currentStage) && is_object($record->currentStage)) {
                            return $record->currentStage->name;
                        }
                        // Fallback: load the stage directly
                        $stage = \App\Models\CropStage::find($record->current_stage_id);
                        return $stage?->name ?? 'Unknown';
                    })
                    ->color(function ($record) {
                        if (isset($record->currentStage) && is_object($record->currentStage)) {
                            return $record->currentStage->color ?? 'gray';
                        }
                        // Fallback: load the stage directly
                        $stage = \App\Models\CropStage::find($record->current_stage_id);
                        return $stage?->color ?? 'gray';
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('stage_age_display')
                    ->label('Time in Stage')
                    ->getStateUsing(function ($record): string {
                        // Get a real crop record and force refresh time calculations
                        $realCrop = \App\Models\Crop::with(['currentStage', 'recipe'])
                            ->where('recipe_id', $record->recipe_id)
                            ->where('planting_at', $record->planting_at)
                            ->where('current_stage_id', $record->current_stage_id)
                            ->first();
                            
                        if (!$realCrop) {
                            return 'N/A';
                        }
                        
                        // Force refresh the time calculations
                        $calculator = app(\App\Services\CropTimeCalculator::class);
                        $calculator->updateTimeCalculations($realCrop);
                        
                        return $realCrop->stage_age_display ?? 'N/A';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('stage_age_minutes', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('time_to_next_stage_display')
                    ->label('Time to Next Stage')
                    ->getStateUsing(function ($record): string {
                        // Get a real crop record and force refresh time calculations
                        $realCrop = \App\Models\Crop::with(['currentStage', 'recipe'])
                            ->where('recipe_id', $record->recipe_id)
                            ->where('planting_at', $record->planting_at)
                            ->where('current_stage_id', $record->current_stage_id)
                            ->first();
                            
                        if (!$realCrop) {
                            return 'N/A';
                        }
                        
                        // Force refresh the time calculations
                        $calculator = app(\App\Services\CropTimeCalculator::class);
                        $calculator->updateTimeCalculations($realCrop);
                        
                        return $realCrop->time_to_next_stage_display ?? 'N/A';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('time_to_next_stage_minutes', $direction);
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_age_display')
                    ->label('Total Age')
                    ->getStateUsing(function ($record): string {
                        // Get a real crop record and force refresh time calculations
                        $realCrop = \App\Models\Crop::with(['currentStage', 'recipe'])
                            ->where('recipe_id', $record->recipe_id)
                            ->where('planting_at', $record->planting_at)
                            ->where('current_stage_id', $record->current_stage_id)
                            ->first();
                            
                        if (!$realCrop) {
                            return 'N/A';
                        }
                        
                        // Force refresh the time calculations
                        $calculator = app(\App\Services\CropTimeCalculator::class);
                        $calculator->updateTimeCalculations($realCrop);
                        
                        return $realCrop->total_age_display ?? 'N/A';
                    })
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
                Tables\Filters\SelectFilter::make('current_stage_id')
                    ->label('Stage')
                    ->relationship('currentStage', 'name'),
                Tables\Filters\TernaryFilter::make('active_crops')
                    ->label('Active Crops')
                    ->placeholder('All Crops')
                    ->trueLabel('Active Only')
                    ->falseLabel('Harvested Only')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('currentStage', fn ($q) => $q->where('code', '!=', 'harvested')),
                        false: fn (Builder $query): Builder => $query->whereHas('currentStage', fn ($q) => $q->where('code', '=', 'harvested')),
                        blank: fn (Builder $query): Builder => $query,
                    )
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('View crop details')
                    ->modalHeading('Crop Details')
                    ->modalWidth('sm')
                    ->slideOver()
                    ->modalIcon('heroicon-o-eye')
                    ->extraModalFooterActions([
                        Tables\Actions\Action::make('advance_stage')
                            ->label('Advance Stage')
                            ->icon('heroicon-o-chevron-double-right')
                            ->color('success')
                            ->visible(function ($record) {
                                $stage = \App\Models\CropStage::find($record->current_stage_id);
                                return $stage?->code !== 'harvested';
                            })
                            ->action(function ($record) {
                                // Get the real crop record for stage logic
                                $realCrop = \App\Models\Crop::where('recipe_id', $record->recipe_id)
                                    ->where('planting_at', $record->planting_at)
                                    ->where('current_stage_id', $record->current_stage_id)
                                    ->first();
                                    
                                if (!$realCrop) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Error')
                                        ->body('Could not find crop record')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                $currentStage = \App\Models\CropStage::find($realCrop->current_stage_id);
                                $nextStage = $currentStage ? \App\Models\CropStage::where('sort_order', '>', $currentStage->sort_order)
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->first() : null;
                                
                                if (!$nextStage) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Already Harvested')
                                        ->body('This crop has already reached its final stage.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                // Find all crops in this batch
                                $crops = \App\Models\Crop::with('recipe')
                                    ->where('recipe_id', $realCrop->recipe_id)
                                    ->where('planting_at', $realCrop->planting_at)
                                    ->where('current_stage_id', $realCrop->current_stage_id)
                                    ->get();
                                
                                $count = $crops->count();
                                
                                // Update all crops in this batch
                                foreach ($crops as $crop) {
                                    $timestampField = "{$nextStage->code}_at";
                                    $crop->current_stage_id = $nextStage->id;
                                    $crop->$timestampField = now();
                                    $crop->save();
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Batch Advanced')
                                    ->body("Successfully advanced {$count} tray(s) to {$nextStage->name}.")
                                    ->success()
                                    ->send();
                            }),
                        Tables\Actions\Action::make('rollback_stage')
                            ->label('Rollback Stage')
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->color('warning')
                            ->visible(function ($record) {
                                $stage = \App\Models\CropStage::find($record->current_stage_id);
                                return $stage?->code !== 'germination';
                            })
                            ->action(function ($record) {
                                // Get the real crop record for stage logic
                                $realCrop = \App\Models\Crop::where('recipe_id', $record->recipe_id)
                                    ->where('planting_at', $record->planting_at)
                                    ->where('current_stage_id', $record->current_stage_id)
                                    ->first();
                                    
                                if (!$realCrop) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Error')
                                        ->body('Could not find crop record')
                                        ->danger()
                                        ->send();
                                    return;
                                }
                                
                                $currentStage = \App\Models\CropStage::find($realCrop->current_stage_id);
                                $previousStage = $currentStage ? \App\Models\CropStage::where('sort_order', '<', $currentStage->sort_order)
                                    ->where('is_active', true)
                                    ->orderBy('sort_order', 'desc')
                                    ->first() : null;
                                
                                if (!$previousStage) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Cannot Rollback')
                                        ->body('This crop is already at the first stage.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                // Find all crops in this batch
                                $crops = \App\Models\Crop::with('recipe')
                                    ->where('recipe_id', $realCrop->recipe_id)
                                    ->where('planting_at', $realCrop->planting_at)
                                    ->where('current_stage_id', $realCrop->current_stage_id)
                                    ->get();
                                
                                $count = $crops->count();
                                
                                // Update all crops in this batch
                                foreach ($crops as $crop) {
                                    // Clear the timestamp for the current stage
                                    $timestampField = "{$currentStage->code}_at";
                                    $crop->$timestampField = null;
                                    
                                    // Set the previous stage
                                    $crop->current_stage_id = $previousStage->id;
                                    $crop->save();
                                }
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Batch Rolled Back')
                                    ->body("Successfully rolled back {$count} tray(s) to {$previousStage->name}.")
                                    ->success()
                                    ->send();
                            }),
                        Tables\Actions\Action::make('edit_crop')
                            ->label('Edit Crop')
                            ->icon('heroicon-o-pencil-square')
                            ->color('primary')
                            ->url(fn ($record) => static::getUrl('edit', ['record' => $record])),
                        Tables\Actions\Action::make('view_all_crops')
                            ->label('View All Crops')
                            ->icon('heroicon-o-list-bullet')
                            ->color('gray')
                            ->url(fn ($record) => static::getUrl('index')),
                    ]),
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
                                'Seed Cultivar' => $recipe->masterSeedCatalog ? $recipe->masterSeedCatalog->common_name . ' - ' . ($recipe->masterCultivar ? $recipe->masterCultivar->cultivar_name : '') : 'N/A',
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
                    ->label(function ($record): string {
                        $currentStage = \App\Models\CropStage::find($record->current_stage_id);
                        $nextStage = $currentStage ? \App\Models\CropStage::where('sort_order', '>', $currentStage->sort_order)
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->first() : null;
                        return $nextStage ? 'Advance to ' . ucfirst($nextStage->name) : 'Harvested';
                    })
                    ->icon('heroicon-o-chevron-double-right')
                    ->color('success')
                    ->visible(function ($record): bool {
                        $stage = \App\Models\CropStage::find($record->current_stage_id);
                        return $stage?->code !== 'harvested';
                    })
                    ->requiresConfirmation()
                    ->modalHeading(function ($record): string {
                        $currentStage = \App\Models\CropStage::find($record->current_stage_id);
                        $nextStage = $currentStage ? \App\Models\CropStage::where('sort_order', '>', $currentStage->sort_order)
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->first() : null;
                        return 'Advance to ' . ucfirst($nextStage?->name ?? 'Unknown') . '?';
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
                    ->action(function ($record, array $data) {
                        $currentStage = \App\Models\CropStage::find($record->current_stage_id);
                        $nextStage = $currentStage ? \App\Models\CropStage::where('sort_order', '>', $currentStage->sort_order)
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->first() : null;
                        
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
                                ->where('current_stage_id', $record->current_stage_id)
                                ->get();
                            
                            $count = $crops->count();
                            $trayNumbers = $crops->pluck('tray_number')->toArray();
                            
                            // Update all crops in this batch
                            $advancementTime = $data['advancement_timestamp'];
                            foreach ($crops as $crop) {
                                $timestampField = "{$nextStage->code}_at";
                                $crop->current_stage_id = $nextStage->id;
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
                    ->visible(function ($record): bool {
                        $stage = \App\Models\CropStage::find($record->current_stage_id);
                        return $stage?->code === 'light';
                    })
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
                                ->where('current_stage_id', $record->current_stage_id)
                                ->get();
                            
                            $count = $crops->count();
                            $trayNumbers = $crops->pluck('tray_number')->toArray();
                            
                            // Update all crops in this batch
                            $harvestTime = $data['harvest_timestamp'];
                            $harvestedStage = \App\Models\CropStage::findByCode('harvested');
                            foreach ($crops as $crop) {
                                $crop->current_stage_id = $harvestedStage->id;
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
                    ->label(function ($record): string {
                        $currentStage = \App\Models\CropStage::find($record->current_stage_id);
                        $previousStage = $currentStage ? \App\Models\CropStage::where('sort_order', '<', $currentStage->sort_order)
                            ->where('is_active', true)
                            ->orderBy('sort_order', 'desc')
                            ->first() : null;
                        return $previousStage ? 'Rollback to ' . ucfirst($previousStage->name) : 'Cannot Rollback';
                    })
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(function ($record): bool {
                        $stage = \App\Models\CropStage::find($record->current_stage_id);
                        return $stage?->code !== 'germination';
                    })
                    ->requiresConfirmation()
                    ->modalHeading(function ($record): string {
                        $currentStage = \App\Models\CropStage::find($record->current_stage_id);
                        $previousStage = $currentStage ? \App\Models\CropStage::where('sort_order', '<', $currentStage->sort_order)
                            ->where('is_active', true)
                            ->orderBy('sort_order', 'desc')
                            ->first() : null;
                        return 'Rollback to ' . ucfirst($previousStage?->name ?? 'Unknown') . '?';
                    })
                    ->modalDescription('This will revert all crops in this batch to the previous stage by removing the current stage timestamp.')
                    ->action(function ($record) {
                        $currentStage = \App\Models\CropStage::find($record->current_stage_id);
                        $previousStage = $currentStage ? \App\Models\CropStage::where('sort_order', '<', $currentStage->sort_order)
                            ->where('is_active', true)
                            ->orderBy('sort_order', 'desc')
                            ->first() : null;
                        
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
                                ->where('current_stage_id', $record->current_stage_id)
                                ->get();
                            
                            $count = $crops->count();
                            $trayNumbers = $crops->pluck('tray_number')->toArray();
                            
                            // Update all crops in this batch
                            foreach ($crops as $crop) {
                                // Clear the timestamp for the current stage
                                $stage = \App\Models\CropStage::find($record->current_stage_id);
                                $currentTimestampField = "{$stage->code}_at";
                                $crop->$currentTimestampField = null;
                                
                                // Set the previous stage
                                $crop->current_stage_id = $previousStage->id;
                                
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
                    ->visible(function ($record): bool {
                        $stage = \App\Models\CropStage::find($record->current_stage_id);
                        return $stage?->code === 'light' && !$record->isWateringSuspended();
                    })
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
                                ->where('current_stage_id', $record->current_stage_id)
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
                                ->where('current_stage_id', $record->current_stage_id)
                                ->pluck('tray_number')
                                ->toArray();
                            
                            // Delete all crops in this batch
                            $count = Crop::where('recipe_id', $record->recipe_id)
                                ->where('planting_at', $record->planting_at)
                                ->where('current_stage_id', $record->current_stage_id)
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
                                    $stage = \App\Models\CropStage::find($record->current_stage_id);
                                    if ($stage?->code !== 'harvested') {
                                        // Find ALL crops in this batch
                                        $crops = Crop::with('recipe')
                                            ->where('recipe_id', $record->recipe_id)
                                            ->where('planting_at', $record->planting_at)
                                            ->where('current_stage_id', $record->current_stage_id)
                                            ->get();
                                        
                                        $currentStage = \App\Models\CropStage::find($record->current_stage_id);
                                        $nextStage = $currentStage ? \App\Models\CropStage::where('sort_order', '>', $currentStage->sort_order)
                                            ->where('is_active', true)
                                            ->orderBy('sort_order')
                                            ->first() : null;
                                        if ($nextStage) {
                                            $advancementTime = $data['advancement_timestamp'];
                                            foreach ($crops as $crop) {
                                                $timestampField = "{$nextStage->code}_at";
                                                $crop->current_stage_id = $nextStage->id;
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
                                    $currentStage = \App\Models\CropStage::find($record->current_stage_id);
                                    $previousStage = $currentStage ? \App\Models\CropStage::where('sort_order', '<', $currentStage->sort_order)
                                        ->where('is_active', true)
                                        ->orderBy('sort_order', 'desc')
                                        ->first() : null;
                                    if (!$previousStage) {
                                        $skippedCount++;
                                        continue;
                                    }
                                    
                                    // Find ALL crops in this batch
                                    $crops = Crop::with('recipe')
                                        ->where('recipe_id', $record->recipe_id)
                                        ->where('planting_at', $record->planting_at)
                                        ->where('current_stage_id', $record->current_stage_id)
                                        ->get();
                                    
                                    if ($crops->isNotEmpty()) {
                                        foreach ($crops as $crop) {
                                            // Clear the timestamp for the current stage
                                            $currentTimestampField = "{$crop->currentStage?->code}_at";
                                            $crop->$currentTimestampField = null;
                                            
                                            // Set the previous stage
                                            $crop->current_stage_id = $previousStage->id;
                                            
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
     * Get crop details for the modal
     */
    public static function getCropDetails($recordId): array
    {
        $crop = Crop::with(['recipe.masterSeedCatalog', 'recipe.masterCultivar', 'currentStage'])
            ->where('id', $recordId)
            ->first();
            
        if (!$crop) {
            throw new \Exception('Crop not found');
        }
        
        // Get all crops in this batch
        $batchCrops = Crop::where('recipe_id', $crop->recipe_id)
            ->where('planting_at', $crop->planting_at)
            ->where('current_stage_id', $crop->current_stage_id)
            ->get();
            
        $trayNumbers = $batchCrops->pluck('tray_number')->sort()->values()->toArray();
        
        // Get variety name
        $varietyName = 'Unknown';
        if ($crop->recipe) {
            $varietyService = app(\App\Services\RecipeVarietyService::class);
            $varietyName = $varietyService->getFullVarietyName($crop->recipe);
        }
        
        // Get stage timings
        $dashboard = new \App\Filament\Pages\Dashboard();
        $reflection = new \ReflectionClass($dashboard);
        $method = $reflection->getMethod('getStageTimings');
        $method->setAccessible(true);
        $stageTimings = $method->invoke($dashboard, $crop);
        
        // Check if can advance/rollback
        $currentStage = $crop->getRelationValue('currentStage');
        $canAdvance = $currentStage?->code !== 'harvested';
        $canRollback = $currentStage?->code !== 'germination';
        
        return [
            'id' => $crop->id,
            'variety' => $varietyName,
            'recipe_name' => $crop->recipe?->name ?? 'Unknown Recipe',
            'current_stage_name' => $currentStage?->name ?? 'Unknown',
            'stage_color' => $currentStage?->color ?? 'gray',
            'tray_count' => count($trayNumbers),
            'tray_numbers_array' => $trayNumbers,
            'stage_age_display' => $crop->stage_age_display ?? 'N/A',
            'time_to_next_stage_display' => $crop->time_to_next_stage_display ?? 'N/A',
            'total_age_display' => $crop->total_age_display ?? 'N/A',
            'planting_at_formatted' => $crop->planting_at ? $crop->planting_at->format('M j, Y g:i A') : 'Unknown',
            'expected_harvest_at_formatted' => $crop->expected_harvest_at ? $crop->expected_harvest_at->format('M j, Y') : null,
            'stage_timings' => $stageTimings,
            'can_advance' => $canAdvance,
            'can_rollback' => $canRollback,
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
            'current_stage_id' => 'Current Stage ID',
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
            'recipe.masterSeedCatalog' => ['common_name'], 'recipe.masterCultivar' => ['cultivar_name'],
            'order' => ['customer_name'],
        ]);
    }
    
    /**
     * Define relationships to include in CSV export
     */
    protected static function getCsvExportRelationships(): array
    {
        return ['recipe', 'recipe.masterSeedCatalog', 'recipe.masterCultivar', 'order'];
    }

    /**
     * Update planting date based on soaking start time and duration
     */
    protected static function updatePlantingDate(Set $set, Get $get): void
    {
        $soakingAt = $get('soaking_at');
        $recipeId = $get('recipe_id');
        
        if ($soakingAt && $recipeId) {
            $recipe = \App\Models\Recipe::find($recipeId);
            if ($recipe && $recipe->seed_soak_hours > 0) {
                $soakingStart = \Carbon\Carbon::parse($soakingAt);
                $plantingDate = $soakingStart->copy()->addHours($recipe->seed_soak_hours);
                $set('planting_at', $plantingDate);
            }
        }
    }

    protected function recipeRequiresSoaking(Get $get): bool
    {
        return static::checkRecipeRequiresSoaking($get);
    }

    public static function checkRecipeRequiresSoaking(Get $get): bool
    {
        $recipeId = $get('recipe_id');
        if (!$recipeId) {
            return false;
        }
        
        $recipe = \App\Models\Recipe::find($recipeId);
        return $recipe?->requiresSoaking() ?? false;
    }

    public static function getSoakingRequiredInfo(Get $get): string
    {
        $recipeId = $get('recipe_id');
        if (!$recipeId) {
            return '';
        }
        
        $recipe = \App\Models\Recipe::find($recipeId);
        if (!$recipe || !$recipe->requiresSoaking()) {
            return '';
        }
        
        return "⚠️ This recipe requires soaking for {$recipe->seed_soak_hours} hours before planting.";
    }
} 