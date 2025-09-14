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

class CreateRecipe extends BaseCreateRecord
{
    use CreateRecord\Concerns\HasWizard;
    protected static string $resource = RecipeResource::class;

    /**
     * Generate comprehensive recipe name from form data
     * Format: "Variety (Cultivar) [Lot# XXXX] [Plant Xg] [XDTM, XG, XB, XL]"
     */
    protected function generateRecipeName(callable $get): ?string
    {
        $varietyCultivarSelection = $get('variety_cultivar_selection');
        $lotNumber = $get('lot_number');
        $plantingDensity = $get('seed_density_grams_per_tray');
        $dtm = $get('days_to_maturity');
        $germinationDays = $get('germination_days');
        $blackoutDays = $get('blackout_days');
        $lightDays = $get('light_days');

        if (!$varietyCultivarSelection) {
            return null;
        }

        $parsed = \App\Models\MasterSeedCatalog::parseCombinedValue($varietyCultivarSelection);
        if (!$parsed['catalog']) {
            return null;
        }

        // Build variety name with cultivar
        $varietyName = $parsed['cultivar_name']
            ? $parsed['catalog']->common_name . ' (' . $parsed['cultivar_name'] . ')'
            : $parsed['catalog']->common_name;

        // Start with variety name
        $nameComponents = [$varietyName];

        // Add lot number in brackets
        if ($lotNumber) {
            $nameComponents[] = "[Lot# {$lotNumber}]";
        }

        // Add planting density in brackets
        if ($plantingDensity) {
            $density = number_format((float)$plantingDensity, 1);
            $nameComponents[] = "[Plant {$density}g]";
        }

        // Add DTM and stage breakdown in brackets
        if ($dtm) {
            $dtmFormatted = number_format((float)$dtm, 0);
            $stageParts = ["{$dtmFormatted}DTM"];

            if ($germinationDays) {
                $germFormatted = number_format((float)$germinationDays, 0);
                $stageParts[] = "{$germFormatted}G";
            }

            if ($blackoutDays) {
                $blackoutFormatted = number_format((float)$blackoutDays, 0);
                $stageParts[] = "{$blackoutFormatted}B";
            }

            if ($lightDays) {
                $lightFormatted = number_format((float)$lightDays, 0);
                $stageParts[] = "{$lightFormatted}L";
            }

            $nameComponents[] = "[" . implode(', ', $stageParts) . "]";
        }

        return implode(' ', $nameComponents);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            // Persistent Recipe Name field at the top
            TextInput::make('name')
                ->label('Recipe Name')
                ->required()
                ->maxLength(255)
                ->helperText('Auto-generated format: "Variety (Cultivar) [Lot# XXXX] [Plant Xg] [XDTM, XG, XB, XL]"')
                ->columnSpanFull(),

            // Wizard with steps
            Wizard::make($this->getSteps())
                ->columnSpanFull()
                ->submitAction(
                    new HtmlString(Blade::render(<<<BLADE
                        <x-filament::button
                            type="submit"
                            size="sm"
                        >
                            Create Recipe
                        </x-filament::button>
                    BLADE))
                ),
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
                        ->live(onBlur: true)
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

                                    // Auto-generate comprehensive recipe name
                                    $generatedName = $this->generateRecipeName($get);
                                    if ($generatedName && !$get('name')) {
                                        $set('name', $generatedName);
                                    }
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
                                ->whereRaw('(total_quantity * consumed_quantity) > 0')
                                ->orderBy('created_at', 'asc') // FIFO ordering
                                ->get()
                                ->mapWithKeys(function ($consumable) {
                                    $available = max(0, $consumable->total_quantity * $consumable->consumed_quantity);
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
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            // Auto-generate comprehensive recipe name
                            $generatedName = $this->generateRecipeName($get);
                            if ($generatedName) {
                                $set('name', $generatedName);
                            }
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
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            // Auto-generate comprehensive recipe name
                            $generatedName = $this->generateRecipeName($get);
                            if ($generatedName) {
                                $set('name', $generatedName);
                            }
                        })
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
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
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($get('germination_days') ?? 0);
                            $blackout = floatval($get('blackout_days') ?? 0);
                            $dtm = floatval($state ?? 0);

                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);

                            // Auto-generate comprehensive recipe name
                            $generatedName = $this->generateRecipeName($get);
                            if ($generatedName) {
                                $set('name', $generatedName);
                            }
                        }),

                    TextInput::make('seed_soak_hours')
                        ->label('Seed Soak Duration (hours)')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpan(1),

                    TextInput::make('germination_days')
                        ->label('Germination Duration (days)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(config('crops.stage_durations.germination', 2))
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($state ?? 0);
                            $blackout = floatval($get('blackout_days') ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);

                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);

                            // Auto-generate comprehensive recipe name
                            $generatedName = $this->generateRecipeName($get);
                            if ($generatedName) {
                                $set('name', $generatedName);
                            }
                        })
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpan(1),

                    TextInput::make('blackout_days')
                        ->label('Blackout Duration (days)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(config('crops.stage_durations.blackout', 3))
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                            $germ = floatval($get('germination_days') ?? 0);
                            $blackout = floatval($state ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);

                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);

                            // Auto-generate comprehensive recipe name
                            $generatedName = $this->generateRecipeName($get);
                            if ($generatedName) {
                                $set('name', $generatedName);
                            }
                        })
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
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
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpan(1),

                    TextInput::make('suspend_water_hours')
                        ->label('Suspend Watering (Hours Before Harvest)')
                        ->numeric()
                        ->minValue(0)
                        ->default(config('crops.watering.default_suspension_hours', 24))
                        ->helperText('Stop watering this many hours before the calculated harvest time.')
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
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

        // Remove the helper field as it's not a database column
        unset($data['variety_cultivar_selection']);

        return $data;
    }
}
