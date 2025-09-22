<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use App\Filament\Resources\HarvestResource;
use App\Forms\Components\CompactRepeater;
use App\Models\Crop;
use App\Models\Harvest;
use App\Models\MasterCultivar;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ListHarvests extends ListRecords
{
    protected static string $resource = HarvestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Harvest')
                ->icon('heroicon-o-plus')
                ->modal()
                ->form([
                    Forms\Components\Section::make('Harvest Details')
                        ->schema([
                            Forms\Components\Select::make('recipe_id')
                                ->label('Recipe')
                                ->options(function () {
                                    return \App\Models\Recipe::with(['masterCultivar', 'masterSeedCatalog'])
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(function ($recipe) {
                                            // Get common name from masterSeedCatalog relationship
                                            $commonName = $recipe->masterSeedCatalog?->common_name ?: 'Unknown';

                                            // Get cultivar name from masterCultivar relationship
                                            $cultivarName = $recipe->masterCultivar?->cultivar_name ?: '';

                                            // Build display in "Common Name (Cultivar Name)" format
                                            $display = $commonName;
                                            if ($cultivarName) {
                                                $display .= ' ('.$cultivarName.')';
                                            }

                                            // Fallback to recipe name if display is still empty
                                            if (empty(trim($display)) || $display === 'Unknown') {
                                                $display = $recipe->name ?: "Recipe #{$recipe->id}";
                                            }

                                            return [$recipe->id => $display];
                                        });
                                })
                                ->required()
                                ->searchable()
                                ->reactive()
                                ->extraAttributes([
                                    'style' => 'position: relative; z-index: 9999;',
                                ])
                                ->afterStateUpdated(function ($state, $set) {
                                    // Clear crops and add one empty row when recipe changes
                                    if ($state) {
                                        $set('crops', [['crop_id' => null, 'harvested_weight_grams' => null, 'percentage_harvested' => 100]]);
                                    } else {
                                        $set('crops', []);
                                    }
                                }),
                            Forms\Components\DatePicker::make('harvest_date')
                                ->label('Harvest Date')
                                ->required()
                                ->default(now())
                                ->maxDate(now())
                                ->reactive(),
                            Forms\Components\Hidden::make('user_id')
                                ->default(auth()->id()),
                        ])
                        ->columns(2),
                    Forms\Components\Section::make('Tray Selection')
                        ->schema([
                            CompactRepeater::make('crops')
                                ->label('')
                                ->addActionLabel('Add Tray')
                                ->defaultItems(0)
                                ->minItems(0)
                                ->reorderable()
                                ->live(onBlur: true)
                                ->columnWidths([
                                    'crop_id' => 'auto',
                                    'harvested_weight_grams' => '120px',
                                    'percentage_harvested' => '120px',
                                ])
                                ->extraAttributes([
                                    'style' => 'overflow: visible;',
                                    'class' => 'relative z-10',
                                ])
                                ->default([])
                                ->schema([
                                    Forms\Components\Select::make('crop_id')
                                        ->label('Tray')
                                        ->options(function (callable $get) {
                                            $recipeId = $get('../../recipe_id');
                                            if (! $recipeId) {
                                                return [];
                                            }

                                            return Crop::with(['recipe', 'currentStage'])
                                                ->where('recipe_id', $recipeId)
                                                ->whereHas('currentStage', function ($query) {
                                                    $query->whereNotIn('code', ['harvested', 'cancelled']);
                                                })
                                                ->get()
                                                ->mapWithKeys(function ($crop) {
                                                    $plantedDate = $crop->planting_at ? $crop->planting_at->format('M j') : 'Not planted';

                                                    return [$crop->id => "Tray {$crop->tray_number} - {$plantedDate}"];
                                                });
                                        })
                                        ->required()
                                        ->searchable()
                                        ->live(onBlur: true)
                                        ->dehydrated()
                                        ->extraAttributes([
                                            'style' => 'position: relative; z-index: 9998;',
                                        ]),
                                    Forms\Components\TextInput::make('harvested_weight_grams')
                                        ->label('Weight (g)')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(0.01),
                                    Forms\Components\TextInput::make('percentage_harvested')
                                        ->label('% Harvested')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->default(100)
                                        ->step(0.1)
                                        ->suffix('%'),
                                ]),
                            Forms\Components\Textarea::make('notes')
                                ->label('General Notes')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columns(1),
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    // Debug: Log the form data to see what's being submitted
                    Log::info('Harvest form data (full):', $data);
                    Log::info('Harvest form data (crops only):', ['crops' => $data['crops'] ?? 'NOT SET']);

                    // Validate that we have crops selected
                    if (empty($data['crops']) || ! is_array($data['crops']) || count($data['crops']) === 0) {
                        throw new \Exception('At least one tray must be selected for harvest.');
                    }

                    // Validate each crop has required fields
                    foreach ($data['crops'] as $index => $crop) {
                        Log::info("Crop data for index {$index}:", [
                            'raw_data' => $crop,
                            'is_array' => is_array($crop),
                            'keys' => is_array($crop) ? array_keys($crop) : 'NOT_ARRAY',
                            'crop_id_present' => isset($crop['crop_id']),
                            'crop_id_value' => $crop['crop_id'] ?? 'NOT_SET',
                        ]);

                        if (! is_array($crop) || empty($crop['crop_id'])) {
                            throw new \Exception('Tray selection is required for item '.($index + 1));
                        }
                        if (empty($crop['harvested_weight_grams']) || $crop['harvested_weight_grams'] <= 0) {
                            throw new \Exception('Weight is required and must be greater than 0 for item '.($index + 1));
                        }
                    }

                    // Get master_cultivar_id from the selected recipe
                    if (isset($data['recipe_id'])) {
                        $recipe = \App\Models\Recipe::find($data['recipe_id']);
                        if ($recipe && $recipe->master_cultivar_id) {
                            $data['master_cultivar_id'] = $recipe->master_cultivar_id;
                        }
                    }

                    // Calculate total weight and tray count from selected crops
                    $totalWeight = 0;
                    $totalTrays = 0;

                    foreach ($data['crops'] as $crop) {
                        $totalWeight += $crop['harvested_weight_grams'] ?? 0;
                        $totalTrays += ($crop['percentage_harvested'] ?? 100) / 100;
                    }

                    $data['total_weight_grams'] = $totalWeight;
                    $data['tray_count'] = round($totalTrays, 2);

                    // Remove recipe_id as it's not stored in harvest table
                    unset($data['recipe_id']);

                    return $data;
                })
                ->using(function (array $data): Model {
                    $crops = $data['crops'] ?? [];
                    unset($data['crops']);

                    // Create the harvest record
                    $harvest = Harvest::create($data);

                    // Attach crops with pivot data and update their status
                    if (! empty($crops)) {
                        $harvestedStage = \App\Models\CropStage::where('code', 'harvested')->first();

                        foreach ($crops as $crop) {
                            $harvest->crops()->attach($crop['crop_id'], [
                                'harvested_weight_grams' => $crop['harvested_weight_grams'],
                                'percentage_harvested' => $crop['percentage_harvested'] ?? 100,
                                'notes' => $crop['notes'] ?? null,
                            ]);

                            // Update the crop status to harvested with relevant timestamps
                            if ($harvestedStage) {
                                \App\Models\Crop::where('id', $crop['crop_id'])
                                    ->update([
                                        'current_stage_id' => $harvestedStage->id,
                                        'harvested_at' => $data['harvest_date'] ?? now(),
                                        'harvest_weight_grams' => $crop['harvested_weight_grams'],
                                    ]);
                            }
                        }
                    }

                    return $harvest;
                })
                ->successNotificationTitle('Harvest created successfully'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            HarvestResource\Widgets\WeeklyHarvestStats::class,
            HarvestResource\Widgets\WeeklyVarietyComparison::class,
            HarvestResource\Widgets\HarvestTrendsChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 1,
            'lg' => 2,
            'xl' => 2,
        ];
    }
}
