<?php

namespace App\Filament\Resources\RecipeResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use App\Models\MasterSeedCatalog;
use App\Models\MasterCultivar;
use Filament\Forms\Components\Hidden;
use App\Services\InventoryManagementService;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use App\Models\Consumable;
use App\Models\ConsumableType;
use App\Models\Recipe;
use Filament\Forms;
use Filament\Forms\Set;

/**
 * RecipeForm for Agricultural Growing Recipe Management
 * 
 * Provides comprehensive form functionality for creating and managing agricultural
 * growing recipes with variety selection, growing parameters, and stage-specific
 * configurations. Essential for standardizing microgreens production with precise
 * timing, density, and environmental parameter control.
 * 
 * @filament_component Form schema builder for RecipeResource
 * @business_domain Agricultural recipe management with growing parameter standardization
 * @recipe_management Variety-specific growing recipes with precise parameter control
 * 
 * @agricultural_parameters Days to maturity, seed density, germination timing, environmental controls
 * @variety_integration MasterSeedCatalog and cultivar selection with inventory awareness
 * @growing_standardization Consistent parameter definitions for reproducible agricultural results
 * 
 * @business_workflow Variety selection -> parameter configuration -> stage definition -> validation
 * @related_models Recipe, MasterSeedCatalog, MasterCultivar, Consumable for complete context
 * @form_sections Recipe information, growing parameters, stage configurations
 */
class RecipeForm
{
    /**
     * Get the complete form schema for agricultural recipe management.
     * 
     * Assembles comprehensive form sections including variety selection,
     * growing parameters, and configuration options essential for
     * standardized agricultural production recipes.
     * 
     * @return array Complete Filament form schema for recipe management
     * @form_sections Recipe information with variety selection and growing parameters
     * @agricultural_workflow Supports complete recipe creation and parameter management
     */
    public static function schema(): array
    {
        return [
            Section::make('Recipe')
                ->schema([
                    static::getSeedConsumableField(),
                    static::getSoilConsumableField(),
                    ...static::getHiddenFields(),
                    static::getActiveToggle(),
                ]),

            Section::make('Growing Parameters')
                ->schema([
                    static::getDaysToMaturityField(),
                    static::getSeedSoakHoursField(),
                    static::getGerminationDaysField(),
                    static::getBlackoutDaysField(),
                    static::getLightDaysField(),
                    static::getSeedDensityField(),
                    static::getExpectedYieldField(),
                ]),
        ];
    }


    protected static function getHiddenFields(): array
    {
        return [
            Hidden::make('name'),
            Hidden::make('lot_number'),
            Hidden::make('common_name'),
            Hidden::make('cultivar_name'),
        ];
    }


    protected static function getSeedConsumableField(): Select
    {
        return Select::make('seed_consumable_id')
            ->label('Seed Consumable')
            ->options(function () {
                $consumables = \Illuminate\Support\Facades\DB::table('consumables')
                    ->where('consumable_type_id', 8)
                    ->where('is_active', true)
                    ->select('id', 'name', 'lot_no', 'total_quantity', 'consumed_quantity', 'quantity_unit')
                    ->orderBy('name')
                    ->get();
                    
                return $consumables->mapWithKeys(function ($consumable) {
                    $totalQty = $consumable->total_quantity ?? 0;
                    $consumedQty = $consumable->consumed_quantity ?? 0;
                    $availableQty = $totalQty - $consumedQty;
                    $unit = $consumable->quantity_unit ?? 'g';
                    $lotInfo = $consumable->lot_no ? " - Lot {$consumable->lot_no}" : '';
                    $label = "{$consumable->name}{$lotInfo} [Available QTY: {$availableQty}{$unit}]";
                    return [$consumable->id => $label];
                });
            })
            ->searchable()
            ->required()
            ->live()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                if ($state) {
                    $consumable = Consumable::find($state);
                    if ($consumable) {
                        // Set the lot number from the consumable
                        if ($consumable->lot_no) {
                            $set('lot_number', $consumable->lot_no);
                        }
                        
                        // Auto-generate recipe name from consumable name and lot
                        if ($consumable->lot_no) {
                            $name = $consumable->name . ' - Lot ' . $consumable->lot_no;
                        } else {
                            $name = $consumable->name;
                        }
                        $set('name', $name);
                    }
                }
            })
            ->helperText('Select the seed consumable for this recipe or create a new one')
            ->createOptionForm(Consumable::getSeedFormSchema())
            ->createOptionUsing(function (array $data) {
                $seedTypeId = ConsumableType::where('code', 'seed')->value('id');
                $data['consumable_type_id'] = $seedTypeId;
                $data['consumed_quantity'] = 0;

                return Consumable::create($data)->id;
            });
    }

    protected static function getSoilConsumableField(): Select
    {
        return Select::make('soil_consumable_id')
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
            });
    }

    protected static function getActiveToggle(): Toggle
    {
        return Toggle::make('is_active')
            ->label('Active')
            ->default(true);
    }

    public static function getDaysToMaturityField(): TextInput
    {
        return TextInput::make('days_to_maturity')
            ->label('Days to Maturity (DTM)')
            ->helperText('Total days from planting to harvest')
            ->numeric()
            ->minValue(0)
            ->step(0.1)
            ->default(12)
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                // Calculate light days
                $germ = floatval($get('germination_days') ?? 0);
                $blackout = floatval($get('blackout_days') ?? 0);
                $dtm = floatval($state ?? 0);

                $lightDays = max(0, $dtm - ($germ + $blackout));
                $set('light_days', $lightDays);
            });
    }

    public static function getSeedSoakHoursField(): TextInput
    {
        return TextInput::make('seed_soak_hours')
            ->label('Seed Soak Hours')
            ->numeric()
            ->integer()
            ->minValue(0)
            ->default(0);
    }

    public static function getGerminationDaysField(): TextInput
    {
        return TextInput::make('germination_days')
            ->label('Germination Days')
            ->helperText('Days in germination stage')
            ->numeric()
            ->minValue(0)
            ->step(0.1)
            ->default(3)
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                $germ = floatval($state ?? 0);
                $blackout = floatval($get('blackout_days') ?? 0);
                $dtm = floatval($get('days_to_maturity') ?? 0);

                $lightDays = max(0, $dtm - ($germ + $blackout));
                $set('light_days', $lightDays);
            });
    }

    public static function getBlackoutDaysField(): TextInput
    {
        return TextInput::make('blackout_days')
            ->label('Blackout Days')
            ->helperText('Days in blackout stage')
            ->numeric()
            ->minValue(0)
            ->step(0.1)
            ->default(2)
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                $germ = floatval($get('germination_days') ?? 0);
                $blackout = floatval($state ?? 0);
                $dtm = floatval($get('days_to_maturity') ?? 0);

                $lightDays = max(0, $dtm - ($germ + $blackout));
                $set('light_days', $lightDays);
            });
    }

    public static function getLightDaysField(): TextInput
    {
        return TextInput::make('light_days')
            ->label('Light Days')
            ->helperText('Automatically calculated from DTM - (germination + blackout)')
            ->numeric()
            ->disabled()
            ->dehydrated(true)
            ->afterStateHydrated(function (TextInput $component, $state, callable $set, Get $get) {
                // Calculate initial value when form loads
                if ($get('days_to_maturity')) {
                    $germ = floatval($get('germination_days') ?? 0);
                    $blackout = floatval($get('blackout_days') ?? 0);
                    $dtm = floatval($get('days_to_maturity') ?? 0);

                    $lightDays = max(0, $dtm - ($germ + $blackout));
                    $set('light_days', $lightDays);
                }
            });
    }

    public static function getSeedDensityField(): TextInput
    {
        return TextInput::make('seed_density_grams_per_tray')
            ->label('Seed Density (g/tray)')
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->default(25)
            ->required();
    }

    public static function getExpectedYieldField(): TextInput
    {
        return TextInput::make('expected_yield_grams')
            ->label('Expected Yield (g/tray)')
            ->numeric()
            ->minValue(0)
            ->step(0.01);
    }

}
