<?php

namespace App\Filament\Resources\RecipeResource\Forms;

use App\Models\Consumable;
use App\Models\Recipe;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

class RecipeForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\Section::make('Recipe Information')
                ->schema([
                    static::getVarietyCultivarField(),
                    ...static::getHiddenFields(),
                    static::getLotNumberField(),
                    static::getLotStatusPlaceholder(),
                    static::getActiveToggle(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Growing Parameters')
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

    protected static function getVarietyCultivarField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('variety_cultivar_selection')
            ->label('Variety & Cultivar (Available Stock Only)')
            ->options(function () {
                return \App\Models\Consumable::getAvailableSeedSelectOptionsWithStock();
            })
            ->searchable()
            ->preload()
            ->native(false)
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, ?Recipe $record) {
                if ($state) {
                    $parsed = \App\Models\MasterSeedCatalog::parseCombinedValue($state);

                    // Set the catalog and cultivar information
                    $set('master_seed_catalog_id', $parsed['catalog_id']);
                    $set('cultivar_name', $parsed['cultivar_name']);

                    if ($parsed['catalog']) {
                        $set('common_name', $parsed['catalog']->common_name);

                        // Auto-generate recipe name if not set
                        if (! $record || ! $record->name) {
                            $name = $parsed['cultivar_name']
                                ? $parsed['catalog']->common_name.' ('.$parsed['cultivar_name'].')'
                                : $parsed['catalog']->common_name;
                            $set('name', $name);
                        }
                    }
                }

                // Reset lot when variety changes
                $set('lot_number', null);
            })
            ->helperText('Only varieties with available seed stock are shown');
    }

    protected static function getHiddenFields(): array
    {
        return [
            Forms\Components\Hidden::make('common_name'),
            Forms\Components\Hidden::make('cultivar_name'),
            Forms\Components\Hidden::make('master_seed_catalog_id'),
            Forms\Components\Hidden::make('seed_consumable_id'),
            Forms\Components\Hidden::make('variety_cultivar_selection')
                ->dehydrated(false), // Don't save to database, it's just a form helper
        ];
    }

    protected static function getLotNumberField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('lot_number')
            ->label('Seed Lot')
            ->options(function (callable $get) {
                $varietyCultivarSelection = $get('variety_cultivar_selection');
                if (! $varietyCultivarSelection) {
                    return [];
                }

                $parsed = \App\Models\MasterSeedCatalog::parseCombinedValue($varietyCultivarSelection);

                return static::getAvailableLotsForCatalogCultivar($parsed['catalog_id'], $parsed['cultivar_name']);
            })
            ->searchable()
            ->nullable()
            ->helperText('Shows only lots with available stock for selected variety')
            ->disabled(fn (callable $get) => ! $get('variety_cultivar_selection'))
            ->reactive()
            ->rules([
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $lotInventoryService = app(\App\Services\InventoryManagementService::class);

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
            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                // Clear lot_depleted_at when lot is changed
                if ($state && $state !== $get('lot_number')) {
                    $set('lot_depleted_at', null);
                }

                // Set seed_consumable_id based on selected lot
                if ($state && $get('variety_cultivar_selection')) {
                    $parsed = \App\Models\MasterSeedCatalog::parseCombinedValue($get('variety_cultivar_selection'));
                    $consumable = \App\Models\Consumable::whereHas('consumableType', function ($query) {
                        $query->where('code', 'seed');
                    })
                        ->where('is_active', true)
                        ->where('master_seed_catalog_id', $parsed['catalog_id'])
                        ->where('lot_no', $state)
                        ->where(function ($query) use ($parsed) {
                            if ($parsed['cultivar_name']) {
                                $query->where('cultivar', $parsed['cultivar_name'])
                                    ->orWhereHas('masterCultivar', function ($q) use ($parsed) {
                                        $q->where('cultivar_name', $parsed['cultivar_name']);
                                    });
                            }
                        })
                        ->first();

                    if ($consumable) {
                        $set('seed_consumable_id', $consumable->id);
                    }
                }
            })
            ->suffixAction(
                Forms\Components\Actions\Action::make('refresh_lots')
                    ->icon('heroicon-o-arrow-path')
                    ->tooltip('Refresh available lots')
                    ->action(function ($livewire) {
                        $livewire->dispatch('refresh-form');
                    })
            );
    }

    protected static function getLotStatusPlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('lot_status')
            ->label('Current Lot Status')
            ->content(function (Forms\Get $get, ?Recipe $record) {
                if (! $record || ! $record->lot_number) {
                    return 'No lot assigned';
                }

                $lotInventoryService = app(\App\Services\InventoryManagementService::class);
                $summary = $lotInventoryService->getLotSummary($record->lot_number);

                if ($summary['available'] <= 0) {
                    return new \Illuminate\Support\HtmlString(
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
                    return new \Illuminate\Support\HtmlString(
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
                    return new \Illuminate\Support\HtmlString(
                        '<div class="flex items-center gap-2 text-sm text-yellow-600">'.
                        '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">'.
                        '<path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>'.
                        '</svg>'.
                        "Lot {$record->lot_number}: {$available}{$unit} available (Low stock: ".round($percentRemaining, 1).'% remaining)'.
                        '</div>'
                    );
                }

                return new \Illuminate\Support\HtmlString(
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

    protected static function getActiveToggle(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('is_active')
            ->label('Active')
            ->default(true);
    }

    public static function getDaysToMaturityField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('days_to_maturity')
            ->label('Days to Maturity (DTM)')
            ->helperText('Total days from planting to harvest')
            ->numeric()
            ->minValue(0)
            ->step(0.1)
            ->default(12)
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                // Calculate light days
                $germ = floatval($get('germination_days') ?? 0);
                $blackout = floatval($get('blackout_days') ?? 0);
                $dtm = floatval($state ?? 0);

                $lightDays = max(0, $dtm - ($germ + $blackout));
                $set('light_days', $lightDays);
            });
    }

    public static function getSeedSoakHoursField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('seed_soak_hours')
            ->label('Seed Soak Hours')
            ->numeric()
            ->integer()
            ->minValue(0)
            ->default(0);
    }

    public static function getGerminationDaysField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('germination_days')
            ->label('Germination Days')
            ->helperText('Days in germination stage')
            ->numeric()
            ->minValue(0)
            ->step(0.1)
            ->default(3)
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                $germ = floatval($state ?? 0);
                $blackout = floatval($get('blackout_days') ?? 0);
                $dtm = floatval($get('days_to_maturity') ?? 0);

                $lightDays = max(0, $dtm - ($germ + $blackout));
                $set('light_days', $lightDays);
            });
    }

    public static function getBlackoutDaysField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('blackout_days')
            ->label('Blackout Days')
            ->helperText('Days in blackout stage')
            ->numeric()
            ->minValue(0)
            ->step(0.1)
            ->default(2)
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, callable $set, Forms\Get $get) {
                $germ = floatval($get('germination_days') ?? 0);
                $blackout = floatval($state ?? 0);
                $dtm = floatval($get('days_to_maturity') ?? 0);

                $lightDays = max(0, $dtm - ($germ + $blackout));
                $set('light_days', $lightDays);
            });
    }

    public static function getLightDaysField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('light_days')
            ->label('Light Days')
            ->helperText('Automatically calculated from DTM - (germination + blackout)')
            ->numeric()
            ->disabled()
            ->dehydrated(true)
            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, callable $set, Forms\Get $get) {
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

    public static function getSeedDensityField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('seed_density_grams_per_tray')
            ->label('Seed Density (g/tray)')
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->default(25)
            ->required();
    }

    public static function getExpectedYieldField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('expected_yield_grams')
            ->label('Expected Yield (g/tray)')
            ->numeric()
            ->minValue(0)
            ->step(0.01);
    }

    /**
     * Get available lots for a specific catalog ID and cultivar name.
     */
    protected static function getAvailableLotsForCatalogCultivar(int $catalogId, ?string $cultivarName = null): array
    {
        $options = [];

        $consumables = \App\Models\Consumable::whereHas('consumableType', function ($query) {
            $query->where('code', 'seed');
        })
            ->where('is_active', true)
            ->where('master_seed_catalog_id', $catalogId)
            ->whereNotNull('lot_no')
            ->where(function ($query) use ($cultivarName) {
                if ($cultivarName) {
                    $query->where('cultivar', $cultivarName)
                        ->orWhereHas('masterCultivar', function ($q) use ($cultivarName) {
                            $q->where('cultivar_name', $cultivarName);
                        });
                }
            })
            ->orderBy('created_at', 'asc') // FIFO ordering
            ->get()
            ->filter(function ($consumable) {
                // Use the model's built-in current_stock accessor for robust calculation
                return $consumable->current_stock > 0;
            });

        foreach ($consumables as $consumable) {
            // Use the model's built-in current_stock accessor
            $available = $consumable->current_stock;
            $unit = $consumable->quantity_unit ?? 'g';
            $createdDate = $consumable->created_at->format('M j, Y');
            $ageIndicator = $consumable->created_at->diffInDays(now()) > 30 ? 'Old' : 'New';

            $display = "Lot {$consumable->lot_no} - {$available} {$unit} ({$ageIndicator}, Added: {$createdDate})";
            $options[$consumable->lot_no] = $display;
        }

        return $options;
    }
}
