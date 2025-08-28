<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Models\ConsumableType;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\RecipeResource;
use App\Models\Consumable;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class CreateRecipe extends BaseCreateRecord
{
    protected static string $resource = RecipeResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->description('Enter the basic recipe information')
                ->schema([
                    TextInput::make('name')
                        ->label('Recipe Name')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Recipe name will be auto-generated from variety and lot if left blank')
                        ->columnSpanFull(),

                    Select::make('seed_variety_helper')
                        ->label('Seed Variety')
                        ->options(function () {
                            // Get unique seed varieties with lot information
                            $varieties = Consumable::whereHas('consumableType', function ($query) {
                                $query->where('code', 'seed');
                            })
                                ->where('is_active', true)
                                ->whereNotNull('lot_no')
                                ->whereNotNull('master_seed_catalog_id')
                                ->whereNotNull('master_cultivar_id')
                                ->whereRaw('(total_quantity - consumed_quantity) > 0')
                                ->with(['consumableType', 'masterSeedCatalog', 'masterCultivar'])
                                ->get()
                                ->groupBy('name')
                                ->map(function ($group, $name) {
                                    return $name;
                                })
                                ->unique();

                            return $varieties->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            // Clear lot number when variety changes
                            $set('lot_number', null);
                            
                            // Auto-generate recipe name if not manually set
                            if ($state && !$get('name')) {
                                $set('name', $state);
                            }
                        })
                        ->helperText('Choose seed variety to see available lots')
                        ->columnSpan(1),

                    Select::make('lot_number')
                        ->label('Available Lots (Required)')
                        ->options(function (callable $get) {
                            $selectedVariety = $get('seed_variety_helper');
                            if (! $selectedVariety) {
                                return [];
                            }

                            $lots = Consumable::whereHas('consumableType', function ($query) {
                                $query->where('code', 'seed');
                            })
                                ->where('is_active', true)
                                ->whereNotNull('lot_no')
                                ->whereNotNull('master_seed_catalog_id')
                                ->whereNotNull('master_cultivar_id')
                                ->whereRaw('(total_quantity - consumed_quantity) > 0')
                                ->with(['consumableType', 'masterSeedCatalog', 'masterCultivar'])
                                ->orderBy('created_at', 'asc') // FIFO ordering
                                ->get()
                                ->filter(function ($consumable) use ($selectedVariety) {
                                    return $consumable->name === $selectedVariety;
                                })
                                ->mapWithKeys(function ($consumable) {
                                    $available = max(0, $consumable->total_quantity - $consumable->consumed_quantity);
                                    $unit = $consumable->quantity_unit ?? 'g';
                                    $createdDate = $consumable->created_at->format('M j, Y');
                                    $ageIndicator = $consumable->created_at->diffInDays(now()) > 30 ? 'Old' : 'New';

                                    $display = "Lot {$consumable->lot_no} - {$available} {$unit} ({$ageIndicator}, Added: {$createdDate})";

                                    return [$consumable->lot_no => $display];
                                });

                            return $lots->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            // Auto-generate recipe name if not manually set
                            $variety = $get('seed_variety_helper');
                            if ($variety && $state && !$get('name')) {
                                $set('name', "{$variety} - Lot {$state}");
                            }
                        })
                        ->helperText('Select specific lot (oldest shown first for FIFO)')
                        ->disabled(fn (callable $get) => ! $get('seed_variety_helper'))
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
                                        $quantityInfo = ' - '.number_format($soil->total_quantity, 1)." {$soil->quantity_unit} available";
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
                            $soilTypeId = ConsumableType::where('code', 'soil')->value('id');
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
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpan(1),

                    // Hidden fields for legacy compatibility
                    Hidden::make('seed_consumable_id')
                        ->default(null),

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

                    Textarea::make('notes')
                        ->label('Recipe Notes')
                        ->rows(5)
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),

            Section::make('Grow Plan')
                ->description('Specify the duration for each growth stage and watering plan')
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
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
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
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                            $germ = floatval($state ?? 0);
                            $blackout = floatval($get('blackout_days') ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);

                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);
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
                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                            $germ = floatval($get('germination_days') ?? 0);
                            $blackout = floatval($state ?? 0);
                            $dtm = floatval($get('days_to_maturity') ?? 0);

                            $lightDays = max(0, $dtm - ($germ + $blackout));
                            $set('light_days', $lightDays);
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
                        ->afterStateHydrated(function (TextInput $component, $state, callable $set, Get $get) {
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
                ->columns(2)
                ->collapsible(),

        ])->columns(null);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If seed_variety_helper is selected, find the corresponding master_seed_catalog_id and master_cultivar_id
        if (isset($data['seed_variety_helper']) && $data['seed_variety_helper']) {
            // Find the consumable for the selected variety and lot
            // We need to use the raw name from database since the computed name attribute causes issues
            $consumable = Consumable::with(['masterSeedCatalog', 'masterCultivar', 'consumableType'])
                ->whereHas('consumableType', function ($query) {
                    $query->where('code', 'seed');
                })
                ->whereRaw('name = ?', [$data['seed_variety_helper']])
                ->where('lot_no', $data['lot_number'] ?? null)
                ->first();
            
            if ($consumable) {
                $data['master_seed_catalog_id'] = $consumable->master_seed_catalog_id;
                $data['master_cultivar_id'] = $consumable->master_cultivar_id;
                $data['common_name'] = $consumable->masterSeedCatalog?->common_name;
                $data['cultivar_name'] = $consumable->masterCultivar?->cultivar_name;
                
                // Set seed_consumable_id to the consumable that matches the lot
                $data['seed_consumable_id'] = $consumable->id;
            }
        }
        
        // Remove the helper field as it's not a database column
        unset($data['seed_variety_helper']);
        
        return $data;
    }
}
