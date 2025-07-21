<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Pages;
use App\Models\Crop;
use App\Models\CropBatch;
use App\Models\CropBatchListView;
use App\Models\Recipe;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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
use App\Filament\Resources\CropResource\Actions\StageTransitionActions;
use App\Services\CropStageCache;
use App\Models\RecipeOptimizedView;

class CropResource extends BaseResource
{
    use CsvExportAction;
    use HasTimestamps;
    use HasStandardActions;
    
    protected static ?string $model = CropBatchListView::class;
    
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
                            ->options(RecipeOptimizedView::getOptions())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->createOptionForm(RecipeResource::getFormSchema())
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                // Update soaking information when recipe changes
                                if ($state) {
                                    $recipe = RecipeOptimizedView::find($state);
                                    if ($recipe && $recipe->requiresSoaking()) {
                                        $set('soaking_duration_display', $recipe->seed_soak_hours . ' hours');
                                        
                                        // Only set soaking_at if it's not already set by the user
                                        if (!$get('soaking_at')) {
                                            $set('soaking_at', now());
                                        }
                                        
                                        static::updatePlantingDate($set, $get);
                                        static::updateSeedQuantityCalculation($set, $get);
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
                                Forms\Components\TextInput::make('soaking_tray_count')
                                    ->label('Number of Trays to Soak')
                                    ->numeric()
                                    ->required(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                                    ->default(1)
                                    ->minValue(1)
                                    ->maxValue(50)
                                    ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get))
                                    ->reactive()
                                    ->helperText('How many trays worth of seed will be soaked?')
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        static::updateSeedQuantityCalculation($set, $get);
                                    }),
                                Forms\Components\Placeholder::make('seed_quantity_display')
                                    ->label('Seed Quantity Required')
                                    ->content(fn (Get $get) => static::getSeedQuantityDisplay($get))
                                    ->visible(fn (Get $get) => static::checkRecipeRequiresSoaking($get)),
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
                            ->required(fn (Get $get) => !static::checkRecipeRequiresSoaking($get))
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
                                    $recipe = RecipeOptimizedView::find($recipeId);
                                    if ($recipe && $recipe->requiresSoaking()) {
                                        $soakingStage = CropStageCache::findByCode('soaking');
                                        if ($soakingStage) {
                                            return $soakingStage->id;
                                        }
                                    }
                                }
                                $germination = CropStageCache::findByCode('germination');
                                return $germination ? $germination->id : null;
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
                                    if ($record->recipe_id) {
                                        $recipe = RecipeOptimizedView::find($record->recipe_id);
                                        if ($recipe) {
                                            // Extract just the variety part (before the lot number)
                                            $parts = explode(' - ', $recipe->name);
                                            if (count($parts) >= 2) {
                                                return $parts[0] . ' - ' . $parts[1];
                                            }
                                            return $recipe->name;
                                        }
                                    }
                                    return 'Unknown';
                                }),
                            Infolists\Components\TextEntry::make('recipe.name')
                                ->label('')
                                ->color('gray')
                                ->getStateUsing(fn ($record) => $record->recipe_name ?? 'Unknown Recipe'),
                        ])->columns(1),
                        
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('current_stage_name')
                                ->label('Status')
                                ->badge()
                                ->color(fn ($record) => $record->current_stage_color ?? 'gray'),
                            Infolists\Components\TextEntry::make('crop_count')
                                ->label('Tray Count'),
                        ])->columns(2),
                        
                        Infolists\Components\TextEntry::make('stage_age_display')
                            ->label('Time in Stage'),
                            
                        Infolists\Components\TextEntry::make('time_to_next_stage_display')
                            ->label('Time to Next Stage'),
                            
                        Infolists\Components\TextEntry::make('total_age_display')
                            ->label('Total Age'),
                            
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
                                // Get the first crop from the batch to get stage timings
                                $firstCrop = $record->crops()->first();
                                    
                                if (!$firstCrop) {
                                    return '<div class="text-gray-500 dark:text-gray-400">No crop data available</div>';
                                }
                                
                                // Get current stage info from the crop/batch 
                                $currentStageName = $firstCrop->currentStage?->name ?? 'Unknown';
                                $currentStageCode = $firstCrop->currentStage?->code ?? 'unknown';
                                
                                // Use existing stage age calculation from the model
                                $stageAge = $record->stage_age_display ?? '';
                                
                                // Build simple timeline based on current stage
                                $timeline = [
                                    'soaking' => ['name' => 'Soaking', 'status' => 'n/a'],
                                    'germination' => ['name' => 'Germination', 'status' => 'TBD'],
                                    'blackout' => ['name' => 'Blackout', 'status' => 'n/a'],
                                    'light' => ['name' => 'Light', 'status' => 'TBD'],
                                    'harvested' => ['name' => 'Harvested', 'status' => 'TBD']
                                ];
                                
                                // Update based on current stage
                                switch($currentStageCode) {
                                    case 'germination':
                                        $timeline['germination']['status'] = 'current (' . $stageAge . ' elapsed)';
                                        break;
                                    case 'blackout':
                                        $timeline['germination']['status'] = 'completed';
                                        $timeline['blackout']['status'] = 'current (' . $stageAge . ' elapsed)';
                                        break;
                                    case 'light':
                                        $timeline['germination']['status'] = 'completed';
                                        $timeline['blackout']['status'] = 'completed';
                                        $timeline['light']['status'] = 'current (' . $stageAge . ' elapsed)';
                                        break;
                                    case 'harvested':
                                        $timeline['germination']['status'] = 'completed';
                                        $timeline['blackout']['status'] = 'completed';
                                        $timeline['light']['status'] = 'completed';
                                        $timeline['harvested']['status'] = 'current (' . $stageAge . ' elapsed)';
                                        break;
                                }
                                
                                $html = '<div class="space-y-1 text-sm">';
                                
                                foreach ($timeline as $stageCode => $stage) {
                                    $html .= '<div class="flex items-center gap-2">';
                                    
                                    // Stage name
                                    $html .= '<span class="font-medium text-gray-900 dark:text-gray-100 w-20">' . htmlspecialchars($stage['name']) . ':</span>';
                                    
                                    // Status with proper coloring
                                    $statusClass = 'text-gray-500 dark:text-gray-400'; // Default for n/a and TBD
                                    if (strpos($stage['status'], 'current') !== false) {
                                        $statusClass = 'text-blue-600 dark:text-blue-400 font-medium';
                                    } elseif ($stage['status'] === 'completed') {
                                        $statusClass = 'text-green-600 dark:text-green-400';
                                    }
                                    
                                    $html .= '<span class="' . $statusClass . '">' . htmlspecialchars($stage['status']) . '</span>';
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
                                $trayNumbers = $record->tray_numbers_array;
                                
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
            ->defaultSort('id', 'desc')
            ->deferLoading()
            ->recordAction('view')
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('recipe_name')
                    ->label('Recipe')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ViewColumn::make('tray_numbers')
                    ->label('Trays')
                    ->view('components.tray-badges')
                    ->searchable()
                    ->sortable(false)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('planting_at')
                    ->label('Planted')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('current_stage_name')
                    ->label('Current Stage')
                    ->badge()
                    ->color(fn ($record) => $record->current_stage_color ?? 'gray')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stage_age_display')
                    ->label('Time in Stage')
                    ->sortable(false)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('time_to_next_stage_display')
                    ->label('Time to Next Stage')
                    ->sortable(false)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_age_display')
                    ->label('Total Age')
                    ->sortable(false)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expected_harvest_at')
                    ->label('Expected Harvest')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->groups([
                Tables\Grouping\Group::make('recipe.name')
                    ->label('Recipe'),
                Tables\Grouping\Group::make('planting_at')
                    ->label('Plant Date')
                    ->date(),
                Tables\Grouping\Group::make('current_stage_name')
                    ->label('Growth Stage'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('current_stage_id')
                    ->label('Stage')
                    ->options(CropStageCache::all()->pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('active_crops')
                    ->label('Active Crops')
                    ->placeholder('All Crops')
                    ->trueLabel('Active Only')
                    ->falseLabel('Harvested Only')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->active(),
                        false: fn (Builder $query): Builder => $query->harvested(),
                        blank: fn (Builder $query): Builder => $query,
                    )
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
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
                                $stage = CropStageCache::find($record->current_stage_id);
                                return $stage?->code !== 'harvested';
                            })
                            ->action(function ($record, $livewire) {
                                // Close the view modal and trigger the main advance stage action
                                $livewire->mountTableAction('advanceStage', $record->id);
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
                    ->action(function ($record) {
                        // Get current time for debugging
                        $now = now();
                        
                        // Get first crop for detailed information
                        $firstCrop = $record->crops()->first();
                        
                        // Prepare batch data with modern features
                        $batchData = [
                            'Batch ID' => $record->id,
                            'Crop Count' => $record->crop_count,
                            'Tray Numbers' => implode(', ', $record->tray_numbers_array ?? []),
                            'Recipe ID' => $record->recipe_id,
                            'Recipe Name' => $record->recipe_name ?? 'Unknown',
                            'Current Stage' => $record->current_stage_name . ' (ID: ' . ($record->current_stage_id ?? 'N/A') . ')',
                            'Stage Color' => $record->current_stage_color ?? 'N/A',
                            'Created At' => $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : 'N/A',
                            'Current Time' => $now->format('Y-m-d H:i:s'),
                        ];
                        
                        // Stage timestamps - more detailed
                        $stageData = [
                            'Planted At' => $record->planting_at ? $record->planting_at->format('Y-m-d H:i:s') : 'N/A',
                            'Soaking At' => $record->soaking_at ? $record->soaking_at->format('Y-m-d H:i:s') : 'N/A',
                            'Germination At' => $record->germination_at ? $record->germination_at->format('Y-m-d H:i:s') : 'N/A',
                            'Blackout At' => $record->blackout_at ? $record->blackout_at->format('Y-m-d H:i:s') : 'N/A',
                            'Light At' => $record->light_at ? $record->light_at->format('Y-m-d H:i:s') : 'N/A',
                            'Harvested At' => $record->harvested_at ? $record->harvested_at->format('Y-m-d H:i:s') : 'N/A',
                            'Expected Harvest' => $record->expected_harvest_at ? $record->expected_harvest_at->format('Y-m-d H:i:s') : 'N/A',
                        ];
                        
                        // Get recipe data using optimized view
                        $recipe = \App\Models\RecipeOptimizedView::find($record->recipe_id);
                        
                        // TEMP DEBUG: Also get the regular recipe to compare
                        $regularRecipe = \App\Models\Recipe::find($record->recipe_id);
                        
                        $recipeData = [];
                        if ($recipe) {
                            $cultivarName = 'N/A';
                            if ($recipe->common_name && $recipe->cultivar_name) {
                                $cultivarName = $recipe->common_name . ' - ' . $recipe->cultivar_name;
                            } elseif ($recipe->common_name) {
                                $cultivarName = $recipe->common_name;
                            }
                            
                            $recipeData = [
                                'Recipe ID' => $recipe->id,
                                'Recipe Name' => $recipe->name,
                                'Variety' => $cultivarName,
                                'Lot Number' => $recipe->lot_number ?? 'N/A',
                                'Common Name' => $recipe->common_name ?? 'N/A',
                                'Cultivar' => $recipe->cultivar_name ?? 'N/A',
                                'Category' => $recipe->category ?? 'N/A',
                                'Master Seed Cat ID' => $recipe->master_seed_catalog_id ?? 'N/A',
                                'Master Cultivar ID' => $recipe->master_cultivar_id ?? 'N/A',
                                'Germination Days' => $recipe->germination_days ?? 'N/A',
                                'Blackout Days' => $recipe->blackout_days ?? 'N/A',
                                'Light Days' => $recipe->light_days ?? 'N/A',
                                'Days to Maturity' => $recipe->days_to_maturity ?? 'N/A',
                                'Seed Soak Hours' => $recipe->seed_soak_hours ?? 'N/A',
                                'Requires Soaking' => $recipe->requires_soaking ? 'Yes' : 'No',
                                'Seed Density (g/tray)' => $recipe->seed_density_grams_per_tray ?? 'N/A',
                                'Expected Yield (g)' => $recipe->expected_yield_grams ?? 'N/A',
                                'Buffer %' => $recipe->buffer_percentage ?? 'N/A',
                                'Is Active' => $recipe->is_active ? 'Yes' : 'No',
                            ];
                            
                            // TEMP DEBUG: Add comparison with regular recipe
                            if ($regularRecipe) {
                                $recipeData['--- REGULAR RECIPE ---'] = '---';
                                $recipeData['Regular Name'] = $regularRecipe->name ?? 'N/A';
                                $recipeData['Regular Master Seed Cat ID'] = $regularRecipe->master_seed_catalog_id ?? 'N/A';
                                $recipeData['Regular Master Cultivar ID'] = $regularRecipe->master_cultivar_id ?? 'N/A';
                                $recipeData['Regular Lot Number'] = $regularRecipe->lot_number ?? 'N/A';
                            }
                        }
                        
                        // Add modern time calculations and stage timeline
                        $timeCalculations = [];
                        
                        // Current stage age
                        $timeCalculations['Current Stage Age'] = [
                            'Display Value' => $record->stage_age_display ?? 'Unknown',
                            'Minutes' => $record->stage_age_minutes ?? 'N/A',
                        ];
                        
                        // Time to next stage
                        $timeCalculations['Time to Next Stage'] = [
                            'Display Value' => $record->time_to_next_stage_display ?? 'Unknown',
                            'Minutes' => $record->time_to_next_stage_minutes ?? 'N/A',
                        ];
                        
                        // Total crop age
                        $timeCalculations['Total Crop Age'] = [
                            'Display Value' => $record->total_age_display ?? 'Unknown', 
                            'Minutes' => $record->total_age_minutes ?? 'N/A',
                        ];
                        
                        // Add stage timeline using our new service
                        if ($firstCrop) {
                            $timelineService = app(\App\Services\CropStageTimelineService::class);
                            $timeline = $timelineService->generateTimeline($firstCrop);
                            
                            $timeCalculations['Stage Timeline'] = [];
                            foreach ($timeline as $stageCode => $stage) {
                                $status = $stage['status'] ?? 'unknown';
                                $duration = $stage['duration'] ?? 'N/A';
                                $timeCalculations['Stage Timeline'][$stage['name']] = ucfirst($status) . 
                                    ($duration !== 'N/A' && $duration ? " ({$duration})" : '');
                            }
                            
                            // TEMP DEBUG: Add crop details for timeline debugging
                            $timeCalculations['--- CROP DEBUG ---'] = [];
                            $timeCalculations['--- CROP DEBUG ---']['Crop ID'] = $firstCrop->id;
                            $timeCalculations['--- CROP DEBUG ---']['Current Stage ID'] = $firstCrop->current_stage_id;
                            $timeCalculations['--- CROP DEBUG ---']['Current Stage Code'] = $firstCrop->currentStage?->code ?? 'NULL';
                            $timeCalculations['--- CROP DEBUG ---']['Planting At'] = $firstCrop->planting_at?->format('Y-m-d H:i:s') ?? 'NULL';
                            $timeCalculations['--- CROP DEBUG ---']['Germination At'] = $firstCrop->germination_at?->format('Y-m-d H:i:s') ?? 'NULL';
                            $timeCalculations['--- CROP DEBUG ---']['Stage Age Minutes'] = $firstCrop->stage_age_minutes ?? 'NULL';
                            $timeCalculations['--- CROP DEBUG ---']['Stage Age Display'] = $firstCrop->stage_age_display ?? 'NULL';
                        }
                        
                        // Next stage info
                        $currentStageCode = $record->currentStage?->code;
                        if ($recipe && $currentStageCode !== 'harvested') {
                            $nextStage = match($currentStageCode) {
                                'soaking' => 'germination',
                                'germination' => 'blackout',
                                'blackout' => 'light',
                                'light' => 'harvested',
                                default => null
                            };
                            
                            if ($nextStage) {
                                $timeCalculations['Next Stage Info'] = [
                                    'Current Stage' => $record->current_stage_name,
                                    'Next Stage' => $nextStage,
                                ];
                            }
                        }
                        
                        // Format the debug data for display in a notification
                        $batchDataHtml = '<div class="mb-4">';
                        $batchDataHtml .= '<h3 class="text-lg font-medium mb-2">Batch Information</h3>';
                        $batchDataHtml .= '<div class="overflow-auto max-h-48 space-y-1">';
                        
                        foreach ($batchData as $key => $value) {
                            $batchDataHtml .= '<div class="flex">';
                            $batchDataHtml .= '<span class="font-medium w-32">' . $key . ':</span>';
                            $batchDataHtml .= '<span class="text-gray-600 dark:text-gray-400">' . $value . '</span>';
                            $batchDataHtml .= '</div>';
                        }
                        
                        $batchDataHtml .= '</div></div>';
                        
                        // Format stage timestamps
                        $stageDataHtml = '<div class="mb-4">';
                        $stageDataHtml .= '<h3 class="text-lg font-medium mb-2">Stage Timestamps</h3>';
                        $stageDataHtml .= '<div class="overflow-auto max-h-48 space-y-1">';
                        
                        foreach ($stageData as $key => $value) {
                            $stageDataHtml .= '<div class="flex">';
                            $stageDataHtml .= '<span class="font-medium w-32">' . $key . ':</span>';
                            $stageDataHtml .= '<span class="text-gray-600 dark:text-gray-400">' . $value . '</span>';
                            $stageDataHtml .= '</div>';
                        }
                        
                        $stageDataHtml .= '</div></div>';
                        
                        // Format recipe data if available
                        $recipeDataHtml = '';
                        if (!empty($recipeData)) {
                            $recipeDataHtml = '<div class="mb-4">';
                            $recipeDataHtml .= '<h3 class="text-lg font-medium mb-2">Recipe Data</h3>';
                            $recipeDataHtml .= '<div class="overflow-auto max-h-48 space-y-1">';
                            
                            foreach ($recipeData as $key => $value) {
                                $recipeDataHtml .= '<div class="flex">';
                                $recipeDataHtml .= '<span class="font-medium w-32">' . $key . ':</span>';
                                $recipeDataHtml .= '<span class="text-gray-600 dark:text-gray-400">' . $value . '</span>';
                                $recipeDataHtml .= '</div>';
                            }
                            
                            $recipeDataHtml .= '</div></div>';
                        } else {
                            $recipeDataHtml = '<div class="text-gray-500 dark:text-gray-400 mb-4">Recipe not found</div>';
                        }
                        
                        // Format time calculations
                        $timeCalcHtml = '<div class="mb-4">';
                        $timeCalcHtml .= '<h3 class="text-lg font-medium mb-2">Time Calculations</h3>';
                        $timeCalcHtml .= '<div class="overflow-auto max-h-80 space-y-4">';
                        
                        foreach ($timeCalculations as $section => $data) {
                            $timeCalcHtml .= '<div class="border-t pt-2">';
                            $timeCalcHtml .= '<h4 class="font-medium text-blue-600 dark:text-blue-400 mb-1">' . $section . '</h4>';
                            
                            foreach ($data as $key => $value) {
                                $timeCalcHtml .= '<div class="flex">';
                                $timeCalcHtml .= '<span class="font-medium w-40 text-sm">' . $key . ':</span>';
                                $timeCalcHtml .= '<span class="text-gray-600 dark:text-gray-400 text-sm">' . $value . '</span>';
                                $timeCalcHtml .= '</div>';
                            }
                            
                            $timeCalcHtml .= '</div>';
                        }
                        
                        $timeCalcHtml .= '</div></div>';
                        
                        Notification::make()
                            ->title('Crop Batch Debug Information')
                            ->body($batchDataHtml . $stageDataHtml . $recipeDataHtml . $timeCalcHtml)
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('close')
                                    ->label('Close')
                                    ->color('gray')
                            ])
                            ->send();
                    }),
                Tables\Actions\Action::make('fix_timestamps')
                    ->label('')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->tooltip('Fix Missing Timestamps')
                    ->action(function ($record) {
                        $fixedCount = 0;
                        $crops = $record->crops;
                        
                        foreach ($crops as $crop) {
                            $fixed = app(\App\Services\CropStageTransitionService::class)->fixMissingStageTimestamps($crop);
                            if ($fixed) {
                                $fixedCount++;
                            }
                        }
                        
                        Notification::make()
                            ->title('Timestamp Fix Complete')
                            ->body("Fixed timestamps for {$fixedCount} crops in this batch.")
                            ->success()
                            ->send();
                    }),
                
                StageTransitionActions::advanceStage(),
                StageTransitionActions::harvest(),
                StageTransitionActions::rollbackStage(),
                Action::make('suspendWatering')
                    ->label('Suspend Watering')
                    ->icon('heroicon-o-no-symbol')
                    ->color('warning')
                    ->visible(function ($record): bool {
                        return $record->current_stage_code === 'light' && !$record->watering_suspended_at;
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
                    ->action(function ($record, array $data) {
                        // Begin transaction for safety
                        DB::beginTransaction();
                        
                        try {
                            // Get all crops in this batch
                            $crops = \App\Models\Crop::where('crop_batch_id', $record->id)->get();
                            
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
                    ->modalDescription(fn ($record) => "This will delete all {$record->crop_count} trays in this batch.")
                    ->modalSubmitActionLabel('Yes, Delete All Trays')
                    ->action(function ($record) {
                        // Begin transaction for safety
                        DB::beginTransaction();
                        
                        try {
                            // Get all tray numbers and delete crops in this batch
                            $trayNumbers = $record->tray_numbers_array;
                            $count = \App\Models\Crop::where('crop_batch_id', $record->id)->delete();
                            
                            // Also delete the batch itself
                            \App\Models\CropBatch::destroy($record->id);
                            
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
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('advance_stage_bulk')
                        ->label('Advance Stage')
                        ->icon('heroicon-o-arrow-right')
                        ->before(function ($records, $action) {
                            // Check if any of the selected batches are in soaking stage
                            foreach ($records as $record) {
                                $stage = CropStageCache::find($record->current_stage_id);
                                if ($stage?->code === 'soaking') {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Cannot Bulk Advance Soaking Crops')
                                        ->body('Crops in the soaking stage require individual tray number assignment. Please use the individual "Advance Stage" action for each soaking batch.')
                                        ->warning()
                                        ->send();
                                    $action->cancel();
                                    return;
                                }
                            }
                        })
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
                            $transitionService = app(\App\Services\CropStageTransitionService::class);
                            $totalCount = 0;
                            $batchCount = 0;
                            $successfulBatches = 0;
                            $failedBatches = 0;
                            $warnings = [];
                            
                            $transitionTime = \Carbon\Carbon::parse($data['advancement_timestamp']);
                            
                            foreach ($records as $record) {
                                try {
                                    // Use the first crop from the batch as the transition target
                                    // The service will automatically find all crops in the batch
                                    $firstCrop = $record->crops()->first();
                                    if (!$firstCrop) {
                                        throw new \Exception('No crops found in batch');
                                    }
                                    $result = $transitionService->advanceStage($firstCrop, $transitionTime);
                                    
                                    $totalCount += $result['affected_count'];
                                    $batchCount++;
                                    $successfulBatches++;
                                    
                                    if (!empty($result['warnings'])) {
                                        $warnings = array_merge($warnings, $result['warnings']);
                                    }
                                } catch (\Illuminate\Validation\ValidationException $e) {
                                    $failedBatches++;
                                    $warnings[] = "Batch {$record->batch_number}: " . implode(', ', $e->errors()['stage'] ?? $e->errors()['target'] ?? ['Unknown error']);
                                } catch (\Exception $e) {
                                    $failedBatches++;
                                    $warnings[] = "Batch {$record->batch_number}: " . $e->getMessage();
                                }
                            }
                            
                            // Build notification message
                            $message = "Successfully advanced {$successfulBatches} batch(es) containing {$totalCount} tray(s).";
                            if ($failedBatches > 0) {
                                $message .= " Failed to advance {$failedBatches} batch(es).";
                            }
                            
                            if ($successfulBatches > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Batches Advanced')
                                    ->body($message)
                                    ->success()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Batches Advanced')
                                    ->body($message)
                                    ->danger()
                                    ->send();
                            }
                            
                            // Show warnings if any
                            if (!empty($warnings)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Warnings')
                                    ->body(implode("\n", array_slice($warnings, 0, 5)) . (count($warnings) > 5 ? "\n...and " . (count($warnings) - 5) . " more" : ''))
                                    ->warning()
                                    ->persistent()
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
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason for rollback (optional)')
                                ->rows(3)
                                ->helperText('Provide a reason for rolling back these batches'),
                        ])
                        ->action(function ($records, array $data) {
                            $transitionService = app(\App\Services\CropStageTransitionService::class);
                            $totalCount = 0;
                            $batchCount = 0;
                            $successfulBatches = 0;
                            $failedBatches = 0;
                            $skippedCount = 0;
                            $warnings = [];
                            
                            $reason = $data['reason'] ?? null;
                            
                            foreach ($records as $record) {
                                try {
                                    // Use the first crop from the batch as the transition target
                                    // The service will automatically find all crops in the batch
                                    $firstCrop = $record->crops()->first();
                                    if (!$firstCrop) {
                                        throw new \Exception('No crops found in batch');
                                    }
                                    $result = $transitionService->revertStage($firstCrop, $reason);
                                    
                                    $totalCount += $result['affected_count'];
                                    $batchCount++;
                                    $successfulBatches++;
                                    
                                    if (!empty($result['warnings'])) {
                                        $warnings = array_merge($warnings, $result['warnings']);
                                    }
                                } catch (\Illuminate\Validation\ValidationException $e) {
                                    $errors = $e->errors();
                                    if (isset($errors['stage']) && str_contains($errors['stage'][0], 'already at first stage')) {
                                        $skippedCount++;
                                    } else {
                                        $failedBatches++;
                                        $warnings[] = "Batch {$record->batch_number}: " . implode(', ', $errors['stage'] ?? $errors['target'] ?? ['Unknown error']);
                                    }
                                } catch (\Exception $e) {
                                    $failedBatches++;
                                    $warnings[] = "Batch {$record->batch_number}: " . $e->getMessage();
                                }
                            }
                            
                            // Build notification message
                            $message = "Successfully rolled back {$successfulBatches} batch(es) containing {$totalCount} tray(s).";
                            if ($skippedCount > 0) {
                                $message .= " Skipped {$skippedCount} batch(es) already at first stage.";
                            }
                            if ($failedBatches > 0) {
                                $message .= " Failed to rollback {$failedBatches} batch(es).";
                            }
                            
                            if ($successfulBatches > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Batches Rolled Back')
                                    ->body($message)
                                    ->success()
                                    ->send();
                            } else if ($skippedCount > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title('No Changes Made')
                                    ->body($message)
                                    ->warning()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Rollback Failed')
                                    ->body($message)
                                    ->danger()
                                    ->send();
                            }
                            
                            // Show warnings if any
                            if (!empty($warnings)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Warnings')
                                    ->body(implode("\n", array_slice($warnings, 0, 5)) . (count($warnings) > 5 ? "\n...and " . (count($warnings) - 5) . " more" : ''))
                                    ->warning()
                                    ->persistent()
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
        // Get the view record for display data
        $viewRecord = CropBatchListView::find($recordId);
        
        if (!$viewRecord) {
            throw new \Exception('Batch not found');
        }
        
        // For actions, we'll load the actual batch when needed
        $trayNumbers = $viewRecord->tray_numbers_array;
        
        // Get variety name from optimized view
        $varietyName = 'Unknown';
        if ($viewRecord->recipe_id) {
            $recipe = RecipeOptimizedView::find($viewRecord->recipe_id);
            if ($recipe) {
                $varietyName = $recipe->getFullVarietyName();
            }
        }
        
        // Get stage timings from the first crop in the batch
        $batch = CropBatch::find($recordId);
        $stageTimings = [];
        if ($batch) {
            $firstCrop = $batch->crops()->first();
            if ($firstCrop) {
                $dashboard = new \App\Filament\Pages\Dashboard();
                $reflection = new \ReflectionClass($dashboard);
                $method = $reflection->getMethod('getStageTimings');
                $method->setAccessible(true);
                $stageTimings = $method->invoke($dashboard, $firstCrop);
            }
        }
        
        // Check if can advance/rollback
        $currentStageCode = $viewRecord->current_stage_code;
        $canAdvance = $currentStageCode !== 'harvested';
        $canRollback = $currentStageCode !== 'germination' && $currentStageCode !== 'soaking';
        
        return [
            'id' => $viewRecord->id,
            'variety' => $varietyName,
            'recipe_name' => $viewRecord->recipe_name ?? 'Unknown Recipe',
            'current_stage_name' => $viewRecord->current_stage_name,
            'stage_color' => $viewRecord->current_stage_color ?? 'gray',
            'tray_count' => $viewRecord->crop_count,
            'tray_numbers_array' => $trayNumbers,
            'stage_age_display' => $viewRecord->stage_age_display,
            'time_to_next_stage_display' => $viewRecord->time_to_next_stage_display,
            'total_age_display' => $viewRecord->total_age_display,
            'planting_at_formatted' => $viewRecord->planting_at ? $viewRecord->planting_at->format('M j, Y g:i A') : 'Unknown',
            'expected_harvest_at_formatted' => $viewRecord->expected_harvest_at ? $viewRecord->expected_harvest_at->format('M j, Y') : null,
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
            $recipe = RecipeOptimizedView::find($recipeId);
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
        
        $recipe = RecipeOptimizedView::find($recipeId);
        return $recipe?->requiresSoaking() ?? false;
    }

    public static function getSoakingRequiredInfo(Get $get): string
    {
        $recipeId = $get('recipe_id');
        if (!$recipeId) {
            return '';
        }
        
        $recipe = RecipeOptimizedView::find($recipeId);
        if (!$recipe || !$recipe->requiresSoaking()) {
            return '';
        }
        
        return "⚠️ This recipe requires soaking for {$recipe->seed_soak_hours} hours before planting.";
    }

    /**
     * Calculate and update seed quantity based on recipe and tray count
     */
    protected static function updateSeedQuantityCalculation(Set $set, Get $get): void
    {
        $recipeId = $get('recipe_id');
        $trayCount = $get('soaking_tray_count');
        
        if ($recipeId && $trayCount) {
            $recipe = RecipeOptimizedView::find($recipeId);
            if ($recipe && $recipe->seed_density_grams_per_tray) {
                $totalSeed = $recipe->seed_density_grams_per_tray * $trayCount;
                $set('calculated_seed_quantity', $totalSeed);
            }
        }
    }

    /**
     * Get formatted seed quantity display
     */
    public static function getSeedQuantityDisplay(Get $get): string
    {
        $recipeId = $get('recipe_id');
        $trayCount = $get('soaking_tray_count');
        
        if (!$recipeId || !$trayCount) {
            return 'Select recipe and enter tray count to calculate seed quantity';
        }
        
        $recipe = RecipeOptimizedView::find($recipeId);
        if (!$recipe) {
            return 'Recipe not found';
        }
        
        if (!$recipe->seed_density_grams_per_tray) {
            return 'Recipe does not specify seed density per tray';
        }
        
        $totalSeed = $recipe->seed_density_grams_per_tray * $trayCount;
        $perTray = $recipe->seed_density_grams_per_tray;
        
        return "**{$totalSeed}g total** ({$perTray}g per tray × {$trayCount} trays)";
    }
} 