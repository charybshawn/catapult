<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Models\ConsumableType;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Pages\Base\BaseEditRecord;
use App\Filament\Resources\RecipeResource;
use App\Models\Consumable;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class EditRecipe extends BaseEditRecord
{
    protected static string $resource = RecipeResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic Information')
                ->description('Edit the basic recipe information')
                ->schema([
                    TextInput::make('name')
                        ->label('Recipe Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Enter a descriptive name for this recipe')
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
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
                        ->afterStateHydrated(function (callable $set, callable $get, $state, $record) {
                            // Pre-populate variety helper based on existing lot_number
                            if (! $state && $record && $record->lot_number) {
                                $consumable = Consumable::whereHas('consumableType', function ($query) {
                                    $query->where('code', 'seed');
                                })
                                    ->where('lot_no', $record->lot_number)
                                    ->where('is_active', true)
                                    ->first();

                                if ($consumable) {
                                    $set('seed_variety_helper', $consumable->name);
                                }
                            }
                        })
                        ->searchable()
                        ->preload()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            // Don't clear lot_number if we're just hydrating from existing data
                            if ($state !== $get('seed_variety_helper')) {
                                $set('lot_number', null);
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
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (callable $set, $state) {
                            // Sync with legacy seed_density field
                            $set('seed_density', $state);
                        })
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpan(1),

                    // Hidden fields for legacy compatibility
                    Hidden::make('seed_density')
                        ->default(0),


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
                        ->default(12)
                        ->helperText('Stop watering this many hours before the calculated harvest time.')
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->collapsible(),

        ])->columns(null);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
