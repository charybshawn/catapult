<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropResource\Pages;
use App\Filament\Resources\CropResource\Forms\CropForm;
use App\Filament\Resources\CropResource\Tables\CropTable;
use App\Models\Crop;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists;

class CropResource extends BaseResource
{
    protected static ?string $model = Crop::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-fire';
    protected static ?string $navigationLabel = 'Grows';
    protected static ?string $navigationGroup = 'Production';
    protected static ?int $navigationSort = 2;
    
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form->schema(CropForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(CropTable::columns())
            ->filters(CropTable::filters())
            ->actions(CropTable::actions())
            ->bulkActions(CropTable::bulkActions())
            ->defaultSort('created_at', 'desc');
    }
    
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Crop Details')
                    ->schema([
<<<<<<< Updated upstream
                        Infolists\Components\TextEntry::make('recipe.name')
                            ->label('Recipe'),
                        Infolists\Components\TextEntry::make('currentStage.name')
                            ->label('Current Stage')
                            ->badge(),
                        Infolists\Components\TextEntry::make('tray_number')
                            ->label('Tray Number'),
                        Infolists\Components\TextEntry::make('tray_count')
                            ->label('Tray Count'),
=======
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
                        'crops.germination_at',
                        'crops.current_stage_id',
                        DB::raw('MIN(crops.id) as id'),
                        DB::raw('MIN(crops.created_at) as created_at'),
                        DB::raw('MIN(crops.updated_at) as updated_at'),
                        DB::raw('MIN(crops.soaking_at) as soaking_at'),
                        DB::raw('MIN(crops.germination_at) as germination_at'),
                        DB::raw('MIN(crops.blackout_at) as blackout_at'),
                        DB::raw('MIN(crops.light_at) as light_at'),
                        DB::raw('MIN(crops.harvested_at) as harvested_at'),
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
>>>>>>> Stashed changes
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Timeline')
                    ->schema([
                        Infolists\Components\TextEntry::make('soaking_at')
                            ->label('Soaking Started')
                            ->dateTime()
                            ->visible(fn ($record) => $record->requires_soaking),
                        Infolists\Components\TextEntry::make('germination_at')
                            ->label('Germination')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('blackout_at')
                            ->label('Blackout')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('light_at')
                            ->label('Light')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('harvested_at')
                            ->label('Harvested')
                            ->dateTime(),
                    ])
                    ->columns(3),
                
                Infolists\Components\Section::make('Progress')
                    ->schema([
                        Infolists\Components\TextEntry::make('time_to_next_stage_display')
                            ->label('Time to Next Stage'),
                        Infolists\Components\TextEntry::make('stage_age_display')
                            ->label('Time in Current Stage'),
                        Infolists\Components\TextEntry::make('total_age_display')
                            ->label('Total Age'),
                    ])
                    ->columns(3),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrops::route('/'),
            'create' => Pages\CreateCrop::route('/create'),
            'view' => Pages\ViewCrop::route('/{record}'),
            'edit' => Pages\EditCrop::route('/{record}/edit'),
        ];
    }
}