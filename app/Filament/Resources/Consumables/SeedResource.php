<?php

namespace App\Filament\Resources\Consumables;

use App\Filament\Forms\Components\Common as FormCommon;
use App\Filament\Resources\ConsumableResourceBase;
use App\Models\MasterSeedCatalog;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Illuminate\Support\Facades\Log;

class SeedResource extends ConsumableResourceBase
{
    protected static ?string $navigationLabel = 'Seeds';

    protected static ?string $pluralLabel = 'Seeds';

    protected static ?string $modelLabel = 'Seed';

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 2;

    public static function getConsumableTypeCode(): string
    {
        return 'seed';
    }

    protected static function getTypeSpecificFormSchema(bool $isEditMode): array
    {
        return [
            // Supplier field for seeds
            FormCommon::supplierSelect(),

            // Seed catalog and cultivar field - using proper relationships
            Forms\Components\Select::make('master_cultivar_id')
                ->label('Seed Catalog & Cultivar')
                ->options(function () {
                    return \App\Models\MasterCultivar::query()
                        ->with('masterSeedCatalog')
                        ->where('is_active', true)
                        ->whereHas('masterSeedCatalog', function ($query) {
                            $query->where('is_active', true);
                        })
                        ->get()
                        ->mapWithKeys(function ($cultivar) {
                            $commonName = ucwords(strtolower($cultivar->masterSeedCatalog->common_name));
                            $cultivarName = ucwords(strtolower($cultivar->cultivar_name));
                            $label = $commonName . ' (' . $cultivarName . ')';
                            return [$cultivar->id => $label];
                        });
                })
                ->searchable()
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state) {
                        $masterCultivar = \App\Models\MasterCultivar::with('masterSeedCatalog')->find($state);
                        if ($masterCultivar && $masterCultivar->masterSeedCatalog) {
                            $commonName = ucwords(strtolower($masterCultivar->masterSeedCatalog->common_name));
                            $cultivarName = ucwords(strtolower($masterCultivar->cultivar_name));

                            $set('name', $commonName . ' (' . $cultivarName . ')');
                            $set('cultivar', $cultivarName);
                            $set('master_seed_catalog_id', $masterCultivar->masterSeedCatalog->id);
                        }
                    }
                }),

            // Hidden fields - will be set from the master cultivar selection
            Forms\Components\Hidden::make('name'),
            Forms\Components\Hidden::make('cultivar'),
        ];
    }

    protected static function getInventoryDetailsSchema(bool $isEditMode): array
    {
        return [
            // Grid for initial quantity and unit
            Forms\Components\Grid::make(2)
                ->schema([
                    // Direct total quantity input for seeds
                    Forms\Components\TextInput::make('total_quantity')
                        ->label('Initial Quantity')
                        ->helperText('Total amount purchased/received')
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->default(0)
                        ->step(0.001)
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            // When initial quantity changes, update remaining if it hasn't been manually set
                            if (! $get('remaining_quantity') || $get('remaining_quantity') == 0) {
                                $set('remaining_quantity', $state);
                            }
                        }),

                    // Unit of measurement for seeds
                    Forms\Components\Select::make('quantity_unit')
                        ->label('Unit')
                        ->options([
                            'g' => 'Grams (g)',
                            'kg' => 'Kilograms (kg)',
                            'oz' => 'Ounces (oz)',
                            'lb' => 'Pounds (lb)',
                        ])
                        ->required()
                        ->default('g')
                        ->reactive(),
                ])
                ->columnSpan(2),

            // Remaining quantity for existing inventory
            Forms\Components\TextInput::make('remaining_quantity')
                ->label('Current Remaining')
                ->helperText('Actual weight remaining (e.g., weighed out 498g from 1000g)')
                ->numeric()
                ->minValue(0)
                ->default(function (Get $get) {
                    return (float) $get('total_quantity');
                })
                ->step(0.001)
                ->reactive()
                ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                    $total = (float) $get('total_quantity');
                    $remaining = (float) $state;
                    $consumed = max(0, $total - $remaining);
                    $set('consumed_quantity', $consumed);

                    // Log the calculation for debugging
                    Log::info('Remaining quantity updated:', [
                        'total' => $total,
                        'remaining' => $remaining,
                        'consumed' => $consumed,
                    ]);
                }),

            // Consumed quantity display
            Forms\Components\Placeholder::make('consumed_display')
                ->label('Amount Used')
                ->content(function (Get $get) {
                    $total = (float) $get('total_quantity');
                    $remaining = (float) $get('remaining_quantity');
                    $consumed = max(0, $total - $remaining);
                    $unit = $get('quantity_unit') ?: 'g';

                    return number_format($consumed, 3).' '.$unit.' used';
                }),

            // Lot/batch number for seeds
            Forms\Components\TextInput::make('lot_no')
                ->label('Lot/Batch Number')
                ->helperText('Optional: Batch identifier')
                ->maxLength(100),

            // Hidden fields for compatibility
            Forms\Components\Hidden::make('consumed_quantity')
                ->default(0)
                ->dehydrated(),
            Forms\Components\Hidden::make('initial_stock')
                ->default(1),
            Forms\Components\Hidden::make('quantity_per_unit')
                ->default(1),
            Forms\Components\Hidden::make('restock_threshold')
                ->default(0),
            Forms\Components\Hidden::make('restock_quantity')
                ->default(0),
        ];
    }

    protected static function getTypeSpecificTableColumns(): array
    {
        return static::getSeedSpecificColumns();
    }

    protected static function getTypeSpecificFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('master_seed_catalog_id')
                ->label('Seed Catalog')
                ->options(function () {
                    return MasterSeedCatalog::query()
                        ->where('is_active', true)
                        ->pluck('common_name', 'id')
                        ->toArray();
                })
                ->searchable(),
        ];
    }
}
