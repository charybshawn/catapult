<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\RecipeResource;
use App\Models\Consumable;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class CreateRecipe extends BaseCreateRecord
{
    use CreateRecord\Concerns\HasWizard;
    protected static string $resource = RecipeResource::class;



    public function form(Form $form): Form
    {
        return $form->schema([
            // Wizard with steps
            Wizard::make($this->getSteps())
                ->columnSpanFull()
                ->submitAction(new \Illuminate\Support\HtmlString('<button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded">Create</button>')),
        ]);
    }

    protected function getSteps(): array
    {
        return [
            Step::make('Recipe Details')
                ->description('Configure the basic recipe information and ingredients')
                ->schema([
                    Select::make('variety_cultivar_selection')
                        ->label('Variety & Cultivar (Available Stock Only)')
                        ->options(function () {
                            return \App\Models\Consumable::getAvailableSeedSelectOptionsWithStock();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            // Clear lot number when variety changes
                            $set('lot_number', null);

                            if ($state) {
                                $parsed = \App\Models\MasterSeedCatalog::parseCombinedValue($state);

                                // Set the catalog and cultivar information
                                $set('master_seed_catalog_id', $parsed['catalog_id']);
                                $set('cultivar_name', $parsed['cultivar_name']);

                                if ($parsed['catalog']) {
                                    $set('common_name', $parsed['catalog']->common_name);
                                }
                            }
                            
                        })
                        ->helperText('Only varieties with available seed stock are shown')
                        ->columnSpan(1),

                    Select::make('lot_number')
                        ->label('Available Lots (Required)')
                        ->options(function (callable $get) {
                            $varietyCultivarSelection = $get('variety_cultivar_selection');
                            if (!$varietyCultivarSelection) {
                                return [];
                            }

                            $parsed = \App\Models\MasterSeedCatalog::parseCombinedValue($varietyCultivarSelection);

                            $consumables = Consumable::whereHas('consumableType', function ($query) {
                                    $query->where('code', 'seed');
                                })
                                ->where('is_active', true)
                                ->where('master_seed_catalog_id', $parsed['catalog_id'])
                                ->whereNotNull('lot_no')
                                ->where(function ($query) use ($parsed) {
                                    if ($parsed['cultivar_name']) {
                                        $query->where('cultivar', $parsed['cultivar_name'])
                                              ->orWhereHas('masterCultivar', function ($q) use ($parsed) {
                                                  $q->where('cultivar_name', $parsed['cultivar_name']);
                                              });
                                    }
                                })
                                ->orderBy('created_at', 'asc') // FIFO ordering
                                ->get()
                                ->filter(function ($consumable) {
                                    // Use the model's built-in current_stock accessor for robust calculation
                                    return $consumable->current_stock > 0;
                                })
                                ->mapWithKeys(function ($consumable) {
                                    // Use the model's built-in current_stock accessor
                                    $available = $consumable->current_stock;
                                    $unit = $consumable->quantity_unit ?? 'g';
                                    $createdDate = $consumable->created_at->format('M j, Y');
                                    $ageIndicator = $consumable->created_at->diffInDays(now()) > 30 ? 'Old' : 'New';

                                    $display = "Lot {$consumable->lot_no} - {$available} {$unit} ({$ageIndicator}, Added: {$createdDate})";

                                    return [$consumable->lot_no => $display];
                                });

                            return $consumables->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                        })
                        ->helperText('Select specific lot (oldest shown first for FIFO)')

                        ->required()
                        ->columnSpan(1),

                    Select::make('soil_consumable_id')
                        ->label('Soil')
                        ->relationship('soilConsumable', 'name')
                        ->options(function () {
                            return Consumable::whereHas('consumableType', function ($query) {
                                $query->where('code', 'soil');
                            })
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(function ($soil) {
                                    $quantityInfo = '';
                                    if ($soil->total_quantity && $soil->quantity_unit) {
                                        $quantityInfo = ' * '.number_format($soil->total_quantity, 1)." {$soil->quantity_unit} available";
                                    }

                                    return [$soil->id => $soil->name.$quantityInfo];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Select a soil from inventory or add a new one')
                        ->required()
                        ->createOptionForm(Consumable::getSoilFormSchema())
                        ->createOptionUsing(function (array $data) {
                            $soilTypeId = \App\Models\ConsumableType::where('code', 'soil')->value('id');
                            $data['consumable_type_id'] = $soilTypeId;
                            $data['consumed_quantity'] = 0;

                            return Consumable::create($data)->id;
                        })
                        ->columnSpan(1),

                    TextInput::make('seed_density_grams_per_tray')
                        ->label('Planting Density (g/tray)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->dehydrated(true)
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                        })

                        ->columnSpan(1),

                    TextInput::make('expected_yield_grams')
                        ->label('Expected Yield (g/tray)')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->columnSpan(1),

                    TextInput::make('buffer_percentage')
                        ->label('Planning Buffer (%)')
                        ->helperText('Safety buffer for crop planning (e.g., 10 = 10% extra trays)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->step(0.01)
                        ->default(10.00)
                        ->suffix('%')
                        ->columnSpan(1),

                    MarkdownEditor::make('notes')
                        ->label('Recipe Notes')
                        ->default("# Planting Method
* 

# Growth Cycle
* 

# Other
* ")
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull(),

                    // Hidden fields for form processing
                    Forms\Components\Hidden::make('common_name'),
                    Forms\Components\Hidden::make('cultivar_name'),
                    Forms\Components\Hidden::make('master_seed_catalog_id'),
                    Forms\Components\Hidden::make('seed_consumable_id'),
                    Forms\Components\Hidden::make('variety_cultivar_selection')
                        ->dehydrated(false), // Don't save to database, it's just a form helper
                ])
                ->columns(2),

            Step::make('Growth Schedule')
                ->description('Define the timing for each growth stage and watering plan')
                ->schema([
                    TextInput::make('days_to_maturity')
                        ->label('Days to Maturity (DTM)')
                        ->helperText('Total days from planting to harvest')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(config('crops.stage_durations.germination', 2) + config('crops.stage_durations.blackout', 3) + config('crops.stage_durations.light', 7))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($get('germination_days') ?? 0);
                            $blackout = floatval($get('blackout_days') ?? 0);
                            $dtm = floatval($state ?? 0);

                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);

                        }),

                    TextInput::make('seed_soak_hours')
                        ->label('Seed Soak Duration (hours)')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)

                        ->columnSpan(1),

                    TextInput::make('germination_days')
                        ->label('Germination Duration (days)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(config('crops.stage_durations.germination', 2))
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($state ?? 0);
                            $blackout = floatval($get('blackout_days') ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);

                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);

                        })

                        ->columnSpan(1),

                    TextInput::make('blackout_days')
                        ->label('Blackout Duration (days)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(config('crops.stage_durations.blackout', 3))
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($get('germination_days') ?? 0);
                            $blackout = floatval($state ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);

                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);

                        })

                        ->columnSpan(1),

                    TextInput::make('light_days')
                        ->label('Light Duration (days)')
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->disabled()
                        ->dehydrated(true)
                        ->helperText('Automatically calculated from DTM - (germination + blackout)')
                        ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, callable $set, Forms\Get $get) {
                            // Calculate initial value when form loads
                            if ($get('days_to_maturity')) {
                                $germ = floatval($get('germination_days') ?? 0);
                                $blackout = floatval($get('blackout_days') ?? 0);
                                $dtm = floatval($get('days_to_maturity') ?? 0);

                                $lightDays = max(0, $dtm - ($germ + $blackout));
                                $set('light_days', $lightDays);
                            }
                        })

                        ->columnSpan(1),

                    TextInput::make('suspend_water_hours')
                        ->label('Suspend Watering (Hours Before Harvest)')
                        ->numeric()
                        ->minValue(0)
                        ->default(config('crops.watering.default_suspension_hours', 24))
                        ->helperText('Stop watering this many hours before the calculated harvest time.')

                        ->columnSpan(1),
                ])
                ->columns(2),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If lot_number is selected, find the corresponding consumable and set seed_consumable_id
        if (isset($data['lot_number']) && $data['lot_number'] &&
            isset($data['master_seed_catalog_id']) && isset($data['cultivar_name'])) {

            $consumable = Consumable::whereHas('consumableType', function ($query) {
                    $query->where('code', 'seed');
                })
                ->where('is_active', true)
                ->where('master_seed_catalog_id', $data['master_seed_catalog_id'])
                ->where('lot_no', $data['lot_number'])
                ->where(function ($query) use ($data) {
                    if ($data['cultivar_name']) {
                        $query->where('cultivar', $data['cultivar_name'])
                              ->orWhereHas('masterCultivar', function ($q) use ($data) {
                                  $q->where('cultivar_name', $data['cultivar_name']);
                              });
                    }
                })
                ->first();

            if ($consumable) {
                $data['seed_consumable_id'] = $consumable->id;
            }
        }



        // Generate recipe name automatically
        $data['name'] = $this->generateRecipeNameFromData($data);

        // Remove the helper field as it's not a database column
        unset($data['variety_cultivar_selection']);

        return $data;
    }


    protected function generateRecipeNameFromData(array $data): string
    {
        if (!isset($data['master_seed_catalog_id']) || !isset($data['cultivar_name'])) {
            return 'Unnamed Recipe';
        }

        $catalog = \App\Models\MasterSeedCatalog::find($data['master_seed_catalog_id']);
        if (!$catalog) {
            return 'Unnamed Recipe';
        }

        $variety = $catalog->common_name;
        $cultivarName = $data['cultivar_name'];
        $nameComponents = [$variety . ' (' . $cultivarName . ')'];

        if (isset($data['lot_number']) && $data['lot_number']) {
            $nameComponents[] = "[Lot# {$data['lot_number']}]";
        }

        if (isset($data['seed_density_grams_per_tray']) && $data['seed_density_grams_per_tray']) {
            $formattedDensity = number_format((float)$data['seed_density_grams_per_tray'], 1);
            $nameComponents[] = "[Plant {$formattedDensity}g]";
        }

        $stages = [];
        if (isset($data['days_to_maturity']) && $data['days_to_maturity']) {
            $stages[] = number_format((float)$data['days_to_maturity'], 1) . 'DTM';
        }
        if (isset($data['germination_days']) && $data['germination_days']) {
            $stages[] = number_format((float)$data['germination_days'], 1) . 'G';
        }
        if (isset($data['blackout_days']) && $data['blackout_days']) {
            $stages[] = number_format((float)$data['blackout_days'], 1) . 'B';
        }
        if (isset($data['light_days']) && $data['light_days']) {
            $stages[] = number_format((float)$data['light_days'], 1) . 'L';
        }

        if (!empty($stages)) {
            $nameComponents[] = '[' . implode(', ', $stages) . ']';
        }

        return implode(' ', $nameComponents);
    }

}
