<?php

namespace App\Filament\Resources\ProductMixResource\Forms;

use App\Actions\ProductMix\ValidateProductMixAction;
use App\Forms\Components\CompactRepeater;
use App\Filament\Resources\ProductMixResource\Actions\CreateRecipeAction;
use App\Filament\Resources\BaseResource;
use Filament\Forms;
use Filament\Forms\Components\Section;

class ProductMixForm
{
    /**
     * Get the complete form schema for ProductMixResource
     */
    public static function schema(): array
    {
        return [
            static::getBasicInformationSection(),
            static::getMixComponentsSection(),
        ];
    }

    /**
     * Basic product mix information section
     */
    protected static function getBasicInformationSection(): Section
    {
        return Forms\Components\Section::make('Basic Information')
            ->schema([
                BaseResource::getNameField('Mix Name'),
                    
                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(3),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ])
            ->columns(2);
    }

    /**
     * Mix components section with percentage tracking and variety selection
     */
    protected static function getMixComponentsSection(): Section
    {
        return Forms\Components\Section::make('Mix Components')
            ->schema([
                static::getPercentageTotalPlaceholder(),
                static::getMixComponentsRepeater(),
            ]);
    }

    /**
     * Percentage total display with real-time validation feedback
     */
    protected static function getPercentageTotalPlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('percentage_total')
            ->label('')
            ->content(function ($get) {
                $components = $get('masterSeedCatalogs') ?? [];
                $total = 0;
                
                foreach ($components as $component) {
                    if (isset($component['percentage']) && is_numeric($component['percentage'])) {
                        $total += floatval($component['percentage']);
                    }
                }
                
                // Round to 2 decimal places to match database precision
                $total = round($total, 2);
                // Format to show only needed decimal places
                $totalFormatted = number_format($total, 2);
                
                if ($total == 0) {
                    return new \Illuminate\Support\HtmlString('
                        <div class="text-center p-4 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Add varieties to see total percentage</p>
                        </div>
                    ');
                } elseif ($total == 100) {
                    return new \Illuminate\Support\HtmlString('
                        <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border-2 border-green-500">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">✓ ' . $totalFormatted . '%</p>
                            <p class="text-sm text-green-600 dark:text-green-400">Perfect mix!</p>
                        </div>
                    ');
                } else {
                    $difference = 100 - $total;
                    $differenceText = $difference > 0 
                        ? 'Add ' . number_format($difference, 2) . '% more'
                        : 'Remove ' . number_format(abs($difference), 2) . '%';
                    
                    return new \Illuminate\Support\HtmlString('
                        <div class="text-center p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border-2 border-amber-500">
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">⚠️ ' . $totalFormatted . '%</p>
                            <p class="text-sm text-amber-600 dark:text-amber-400">' . $differenceText . ' to reach 100%</p>
                        </div>
                    ');
                }
            })
            ->extraAttributes(['class' => 'w-full'])
            ->reactive();
    }

    /**
     * Mix components repeater with variety selection and percentage tracking
     */
    protected static function getMixComponentsRepeater(): CompactRepeater
    {
        return CompactRepeater::make('mixComponents')
            ->label('')
            ->statePath('masterSeedCatalogs')
            ->addActionLabel('Add Variety')
            ->defaultItems(1)
            ->minItems(1)
            ->reorderable()
            ->columnWidths([
                'variety_selection' => '50%',
                'percentage' => '20%',
                'recipe_id' => '30%',
            ])
            ->extraAttributes([
                'style' => 'overflow: visible;'
            ])
            ->schema(static::getMixComponentSchema())
            ->mutateRelationshipDataBeforeCreateUsing(static::mutateMixComponentDataBeforeCreate())
            ->mutateRelationshipDataBeforeSaveUsing(static::mutateMixComponentDataBeforeSave())
            ->mutateRelationshipDataBeforeFillUsing(static::mutateMixComponentDataBeforeFill());
    }

    /**
     * Schema for individual mix component within the repeater
     */
    protected static function getMixComponentSchema(): array
    {
        return [
            static::getVarietySelectionField(),
            static::getHiddenMasterSeedCatalogIdField(),
            static::getHiddenCultivarField(),
            static::getPercentageField(),
            static::getRecipeField(),
        ];
    }

    /**
     * Variety selection field with catalog and cultivar composite options
     */
    protected static function getVarietySelectionField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('variety_selection')
            ->label('Variety')
            ->options(function () {
                $options = [];
                
                // Get all active master seed catalogs with their cultivars
                $catalogs = \App\Models\MasterSeedCatalog::where('is_active', true)
                    ->with('activeCultivars')
                    ->orderBy('common_name')
                    ->get();
                
                foreach ($catalogs as $catalog) {
                    // Use the already loaded active cultivars
                    $cultivars = $catalog->activeCultivars;
                    
                    if ($cultivars->isNotEmpty()) {
                        // Add each cultivar as a separate option
                        foreach ($cultivars as $cultivar) {
                            $key = $catalog->id . '|' . $cultivar->cultivar_name;
                            $label = $catalog->common_name . ' (' . $cultivar->cultivar_name . ')';
                            $options[$key] = $label;
                        }
                    } else {
                        // If no cultivars, add the catalog with default cultivar
                        $key = $catalog->id . '|Default';
                        $label = $catalog->common_name . ' (Default)';
                        $options[$key] = $label;
                    }
                }
                
                return $options;
            })
            ->reactive()
            ->afterStateUpdated(function ($state, Forms\Set $set) {
                if ($state) {
                    // Parse the composite key
                    [$catalogId, $cultivar] = explode('|', $state);
                    $set('master_seed_catalog_id', $catalogId);
                    $set('cultivar', $cultivar);
                }
            })
            ->dehydrated(false) // Don't save this field directly
            ->searchable()
            ->required()
            ->extraAttributes([]);
    }

    /**
     * Hidden field to store master seed catalog ID
     */
    protected static function getHiddenMasterSeedCatalogIdField(): Forms\Components\Hidden
    {
        return Forms\Components\Hidden::make('master_seed_catalog_id');
    }

    /**
     * Hidden field to store cultivar name
     */
    protected static function getHiddenCultivarField(): Forms\Components\Hidden
    {
        return Forms\Components\Hidden::make('cultivar');
    }

    /**
     * Percentage input field with validation
     */
    protected static function getPercentageField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('percentage')
            ->label('Percentage (%)')
            ->numeric()
            ->minValue(0.01)
            ->maxValue(100)
            ->required()
            ->default(25)
            ->suffix('%')
            ->step(0.01)
            ->inputMode('decimal')
            ->reactive();
    }

    /**
     * Recipe selection field (optional)
     */
    protected static function getRecipeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('recipe_id')
            ->label('Recipe (Optional)')
            ->options(
                \App\Models\Recipe::where('is_active', true)
                    ->whereNull('lot_depleted_at')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray()
            )
            ->placeholder('Use default recipe')
            ->helperText('Leave empty to use the default recipe for this variety')
            ->native(true)
            ->suffixAction(
                CreateRecipeAction::make()
                    ->fillForm(function (callable $get) {
                        // Get the current component's variety and cultivar info
                        $varietySelection = $get('variety_selection');
                        if (!$varietySelection) {
                            return [];
                        }
                        
                        // Parse the composite key
                        [$catalogId, $cultivar] = explode('|', $varietySelection);
                        
                        // Get catalog info
                        $catalog = \App\Models\MasterSeedCatalog::find($catalogId);
                        if (!$catalog) {
                            return [];
                        }
                        
                        // Find cultivar record
                        $masterCultivar = \App\Models\MasterCultivar::where('master_seed_catalog_id', $catalogId)
                            ->where('cultivar_name', $cultivar)
                            ->first();
                        
                        // Generate recipe name - will be updated after form submission with DTM and seed density
                        $recipeName = $catalog->common_name . ' (' . $cultivar . ')';
                        
                        return [
                            'master_seed_catalog_id' => $catalogId,
                            'master_cultivar_id' => $masterCultivar ? $masterCultivar->id : null,
                            'common_name' => $catalog->common_name,
                            'cultivar_name' => $cultivar,
                            'name' => $recipeName,
                        ];
                    })
            );
    }

    /**
     * Data mutation before creating mix component relationships
     */
    protected static function mutateMixComponentDataBeforeCreate(): callable
    {
        return function (array $data) {
            return app(ValidateProductMixAction::class)->validateMixComponentMutation($data);
        };
    }

    /**
     * Data mutation before saving mix component relationships
     */
    protected static function mutateMixComponentDataBeforeSave(): callable
    {
        return function (array $data) {
            return app(ValidateProductMixAction::class)->validateMixComponentMutation($data);
        };
    }

    /**
     * Data mutation before filling form with existing data
     */
    protected static function mutateMixComponentDataBeforeFill(): callable
    {
        return function (array $data) {
            return app(ValidateProductMixAction::class)->prepareMixComponentForFill($data);
        };
    }
}