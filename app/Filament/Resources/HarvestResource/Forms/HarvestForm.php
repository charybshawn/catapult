<?php

namespace App\Filament\Resources\HarvestResource\Forms;

use App\Actions\Harvest\GetAvailableCropsAction;
use App\Models\MasterCultivar;
use App\Models\Crop;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;

class HarvestForm
{
    /**
     * Returns Filament form schema for Harvest resources
     */
    public static function schema(): array
    {
        return [
            Forms\Components\Section::make('Harvest Details')
                ->schema([
                    static::getCultivarSelect(),
                    static::getHarvestDatePicker(),
                    static::getUserIdField(),
                ])
                ->columns(2),
            Forms\Components\Section::make('Tray Selection')
                ->schema([
                    static::getCropsRepeater(),
                    static::getGeneralNotesField(),
                ])
                ->columns(1),
        ];
    }

    /**
     * Master Cultivar selection with active filtering
     */
    protected static function getCultivarSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('master_cultivar_id')
            ->label('Crop Variety')
            ->options(function () {
                return MasterCultivar::with('masterSeedCatalog')
                    ->where('is_active', true)
                    ->whereHas('masterSeedCatalog', function ($query) {
                        $query->where('is_active', true);
                    })
                    ->get()
                    ->mapWithKeys(function ($cultivar) {
                        return [$cultivar->id => $cultivar->full_name];
                    });
            })
            ->required()
            ->searchable()
            ->reactive()
            ->afterStateUpdated(function ($state, Set $set) {
                // Clear crops when variety changes
                $set('crops', []);
            });
    }

    /**
     * Harvest date picker with validation
     */
    protected static function getHarvestDatePicker(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('harvest_date')
            ->label('Harvest Date')
            ->required()
            ->default(now())
            ->maxDate(now())
            ->reactive();
    }

    /**
     * Hidden user ID field
     */
    protected static function getUserIdField(): Forms\Components\Hidden
    {
        return Forms\Components\Hidden::make('user_id')
            ->default(auth()->id());
    }

    /**
     * Crops repeater with complex tray selection logic
     */
    protected static function getCropsRepeater(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('crops')
            ->label('Select Trays to Harvest')
            ->schema([
                Forms\Components\Grid::make(4)
                    ->schema([
                        static::getTraySelect(),
                        static::getWeightInput(),
                        static::getPercentageInput(),
                        static::getTrayNotesInput(),
                    ]),
            ])
            ->addActionLabel('Add Another Tray')
            ->collapsible()
            ->itemLabel(function (array $state): ?string {
                if (!$state['crop_id']) {
                    return 'New Tray';
                }
                
                $crop = Crop::find($state['crop_id']);
                if (!$crop) {
                    return 'Unknown Tray';
                }
                
                $weight = $state['harvested_weight_grams'] ?? 0;
                $percentage = $state['percentage_harvested'] ?? 100;
                
                return "Tray {$crop->tray_number} - {$weight}g ({$percentage}%)";
            });
    }

    /**
     * Tray selection with complex crop filtering
     */
    protected static function getTraySelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('crop_id')
            ->label('Tray')
            ->options(function (Get $get) {
                $cultivarId = $get('../../master_cultivar_id');
                if (!$cultivarId) {
                    return [];
                }
                
                return app(GetAvailableCropsAction::class)->execute($cultivarId);
            })
            ->required()
            ->searchable()
            ->reactive();
    }

    /**
     * Harvested weight input
     */
    protected static function getWeightInput(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('harvested_weight_grams')
            ->label('Weight (g)')
            ->required()
            ->numeric()
            ->minValue(0)
            ->step(0.01);
    }

    /**
     * Percentage harvested input
     */
    protected static function getPercentageInput(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('percentage_harvested')
            ->label('% Harvested')
            ->required()
            ->numeric()
            ->minValue(0)
            ->maxValue(100)
            ->default(100)
            ->step(0.1)
            ->suffix('%');
    }

    /**
     * Tray-specific notes input
     */
    protected static function getTrayNotesInput(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('notes')
            ->label('Tray Notes')
            ->placeholder('Optional notes for this tray');
    }

    /**
     * General harvest notes
     */
    protected static function getGeneralNotesField(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('notes')
            ->label('General Notes')
            ->rows(3)
            ->columnSpanFull();
    }
}