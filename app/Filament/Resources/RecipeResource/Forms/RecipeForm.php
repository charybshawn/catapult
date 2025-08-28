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
            Section::make('Recipe Information')
                ->schema([
                    static::getVarietyField(),
                    static::getCultivarField(),
                    ...static::getHiddenFields(),
                    static::getLotNumberField(),
                    static::getSeedConsumableField(),
                    static::getLotStatusPlaceholder(),
                    static::getActiveToggle(),
                ])
                ->columns(2),

            Section::make('Growing Parameters')
                ->schema([
                    static::getDaysToMaturityField(),
                    static::getSeedSoakHoursField(),
                    static::getGerminationDaysField(),
                    static::getBlackoutDaysField(),
                    static::getLightDaysField(),
                    static::getSeedDensityField(),
                    static::getExpectedYieldField(),
                ])
                ->columns(2),
        ];
    }

    protected static function getVarietyField(): Select
    {
        return Select::make('master_seed_catalog_id')
            ->label('Variety')
            ->options(function () {
                return MasterSeedCatalog::query()
                    ->where('is_active', true)
                    ->orderBy('common_name')
                    ->pluck('common_name', 'id');
            })
            ->searchable()
            ->preload()
            ->native(false)
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, ?Recipe $record) {
                if ($state && ! $record) {
                    // When creating new recipe, set common_name based on selection
                    $catalog = MasterSeedCatalog::find($state);
                    if ($catalog) {
                        $set('common_name', $catalog->common_name);
                    }
                }
                // Reset cultivar when variety changes
                $set('master_cultivar_id', null);
            });
    }

    protected static function getCultivarField(): Select
    {
        return Select::make('master_cultivar_id')
            ->label('Cultivar')
            ->options(function (callable $get) {
                $catalogId = $get('master_seed_catalog_id');
                if (! $catalogId) {
                    return [];
                }

                return MasterCultivar::where('master_seed_catalog_id', $catalogId)
                    ->where('is_active', true)
                    ->pluck('cultivar_name', 'id');
            })
            ->searchable()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                if ($state) {
                    $cultivar = MasterCultivar::find($state);
                    if ($cultivar) {
                        $set('cultivar_name', $cultivar->cultivar_name);
                        // Update recipe name
                        $catalog = MasterSeedCatalog::find($get('master_seed_catalog_id'));
                        if ($catalog && $cultivar) {
                            $name = $catalog->common_name.' ('.$cultivar->cultivar_name.')';
                            $set('name', $name);
                        }
                    }
                }
            });
    }

    protected static function getHiddenFields(): array
    {
        return [
            Hidden::make('common_name'),
            Hidden::make('cultivar_name'),
        ];
    }

    protected static function getLotNumberField(): Select
    {
        return Select::make('lot_number')
            ->label('Seed Lot')
            ->options(fn () => static::getAvailableLotsForSelection())
            ->searchable()
            ->nullable()
            ->helperText('Shows only lots with available stock')
            ->rules([
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $lotInventoryService = app(InventoryManagementService::class);

                        // Check if lot exists
                        if (! $lotInventoryService->lotExists($value)) {
                            $fail("The selected lot '{$value}' does not exist.");

                            return;
                        }

                        // Check if lot has available stock
                        if ($lotInventoryService->isLotDepleted($value)) {
                            $fail("The selected lot '{$value}' is depleted and cannot be used.");

                            return;
                        }

                        // Check if lot has sufficient quantity for typical recipe requirements
                        $availableQuantity = $lotInventoryService->getLotQuantity($value);
                        if ($availableQuantity < 10) { // Minimum 10g threshold
                            $fail("The selected lot '{$value}' has insufficient stock (".round($availableQuantity, 1).'g available). Minimum 10g required.');
                        }
                    }
                },
            ])
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                // Clear lot_depleted_at when lot is changed
                if ($state && $state !== $get('lot_number')) {
                    $set('lot_depleted_at', null);
                }
            })
            ->suffixAction(
                Action::make('refresh_lots')
                    ->icon('heroicon-o-arrow-path')
                    ->tooltip('Refresh available lots')
                    ->action(function ($livewire) {
                        $livewire->dispatch('refresh-form');
                    })
            );
    }

    protected static function getSeedConsumableField(): Select
    {
        return Select::make('seed_consumable_id')
            ->label('Seed Consumable')
            ->options(function () {
                return Consumable::query()
                    ->whereHas('consumableType', function ($q) {
                        $q->where('name', 'like', '%seed%');
                    })
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id');
            })
            ->searchable()
            ->preload()
            ->helperText('Select the seed consumable for this recipe');
    }

    protected static function getLotStatusPlaceholder(): Placeholder
    {
        return Placeholder::make('lot_status')
            ->label('Current Lot Status')
            ->content(function (Get $get, ?Recipe $record) {
                if (! $record || ! $record->lot_number) {
                    return 'No lot assigned';
                }

                $lotInventoryService = app(InventoryManagementService::class);
                $summary = $lotInventoryService->getLotSummary($record->lot_number);

                if ($summary['available'] <= 0) {
                    return new HtmlString(
                        '<div class="flex items-center gap-2 text-sm text-red-600">'.
                        '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">'.
                        '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>'.
                        '</svg>'.
                        "Lot {$record->lot_number} is depleted (0 available)".
                        '</div>'
                    );
                }

                $seedTypeId = $lotInventoryService->getSeedTypeId();
                $consumable = null;
                if ($seedTypeId) {
                    $consumable = Consumable::where('consumable_type_id', $seedTypeId)
                        ->where('lot_no', $record->lot_number)
                        ->where('is_active', true)
                        ->first();
                }

                if (! $consumable) {
                    return new HtmlString(
                        '<div class="flex items-center gap-2 text-sm text-red-600">'.
                        '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">'.
                        '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>'.
                        '</svg>'.
                        "Lot {$record->lot_number} not found".
                        '</div>'
                    );
                }

                $unit = $consumable->quantity_unit ?? 'g';
                $available = $summary['available'];

                // Check if lot is running low (less than 20% remaining)
                $totalOriginal = $summary['total'];
                $percentRemaining = $totalOriginal > 0 ? ($available / $totalOriginal) * 100 : 0;

                if ($percentRemaining < 20) {
                    return new HtmlString(
                        '<div class="flex items-center gap-2 text-sm text-yellow-600">'.
                        '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">'.
                        '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>'.
                        '</svg>'.
                        "Lot {$record->lot_number}: {$available}{$unit} available (Low stock: ".round($percentRemaining, 1).'% remaining)'.
                        '</div>'
                    );
                }

                return new HtmlString(
                    '<div class="flex items-center gap-2 text-sm text-green-600">'.
                    '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">'.
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>'.
                    '</svg>'.
                    "Lot {$record->lot_number}: {$available}{$unit} available (".round($percentRemaining, 1).'% remaining)'.
                    '</div>'
                );
            })
            ->visible(fn (?Recipe $record) => $record && $record->lot_number);
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


    /**
     * Get available lots for selection with formatted display names.
     */
    protected static function getAvailableLotsForSelection(): array
    {
        $lotInventoryService = app(InventoryManagementService::class);
        $lotNumbers = $lotInventoryService->getAllLotNumbers();
        $options = [];

        foreach ($lotNumbers as $lotNumber) {
            $summary = $lotInventoryService->getLotSummary($lotNumber);

            // Skip depleted lots
            if ($summary['available'] <= 0) {
                continue;
            }

            // Get the first consumable entry for this lot to get seed info
            $seedTypeId = $lotInventoryService->getSeedTypeId();
            if (! $seedTypeId) {
                continue;
            }

            $consumable = Consumable::where('consumable_type_id', $seedTypeId)
                ->where('lot_no', $lotNumber)
                ->where('is_active', true)
                ->first();

            if ($consumable) {
                $unit = $consumable->quantity_unit ?? 'g';
                $seedName = $consumable->name ?? 'Unknown Seed';
                $available = $summary['available'];

                // Format: "LOT123 (1500g available) - Broccoli (Broccoli)"
                $label = "{$lotNumber} ({$available}{$unit} available) - {$seedName}";
                $options[$lotNumber] = $label;
            }
        }

        return $options;
    }
}
