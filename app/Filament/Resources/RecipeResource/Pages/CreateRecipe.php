<?php

namespace App\Filament\Resources\RecipeResource\Pages;

use App\Filament\Pages\BaseCreateRecord;
use App\Filament\Resources\RecipeResource;
use App\Models\Consumable;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;

class CreateRecipe extends BaseCreateRecord
{
    protected static string $resource = RecipeResource::class;

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Basic Information')
                ->description('Enter the basic recipe information')
                ->schema([
                    TextInput::make('name')
                        ->label('Recipe Name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Enter a descriptive name for this recipe')
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpanFull(),

                    Select::make('seed_consumable_id')
                        ->label('Seed (Legacy)')
                        ->relationship('seedConsumable', 'name')
                        ->options(function () {
                            return Consumable::where('type', 'seed')
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(function ($seed) {
                                    $lotInfo = $seed->lot_no ? " (Lot: {$seed->lot_no})" : '';
                                    $totalAvailable = $seed->current_stock * ($seed->quantity_per_unit ?? 0);
                                    $displayUnit = $seed->quantity_unit ?? 'units';
                                    $stockInfo = ' - '.number_format($totalAvailable, 1)." {$displayUnit} available";

                                    return [$seed->id => $seed->name.$lotInfo.$stockInfo];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('DEPRECATED: For backward compatibility only. Use lot_number field instead.')
                        ->createOptionForm(Consumable::getSeedFormSchema())
                        ->createOptionUsing(function (array $data) {
                            $data['type'] = 'seed';
                            $data['consumed_quantity'] = 0;

                            return Consumable::create($data)->id;
                        })
                        ->columnSpan(1),

                    TextInput::make('lot_number')
                        ->label('Lot Number')
                        ->helperText('Enter the lot number for seed tracking and inventory management')
                        ->maxLength(50)
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpan(1),

                    Select::make('soil_consumable_id')
                        ->label('Soil')
                        ->relationship('soilConsumable', 'name')
                        ->options(function () {
                            return Consumable::where('type', 'soil')
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
                            $data['type'] = 'soil';
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
                        ->default(12)
                        ->required()
                        ->live()
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
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpan(1),

                    TextInput::make('germination_days')
                        ->label('Germination Duration (days)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->step(0.1)
                        ->default(3)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
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
                        ->default(2)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
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
                        ->default(12)
                        ->helperText('Stop watering this many hours before the calculated harvest time.')
                        ->extraInputAttributes(['onkeydown' => 'if(event.key === "Enter") { event.preventDefault(); }'])
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->collapsible(),

        ])->columns(null);
    }
}
