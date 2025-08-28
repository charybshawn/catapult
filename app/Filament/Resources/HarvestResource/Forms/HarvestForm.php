<?php

namespace App\Filament\Resources\HarvestResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Models\MasterCultivar;
use Filament\Forms;

/**
 * Filament form schema builder for simplified agricultural harvest management.
 *
 * Provides streamlined form components for recording microgreens harvest operations
 * with cultivar-based harvest entry, weight tracking per cultivar, and harvest notes.
 * Simplified approach eliminates tray complexity and focuses on cultivar-weight pairs
 * for efficient harvest data collection.
 *
 * @filament_form
 * @business_domain Agricultural harvest tracking and production recording
 * @related_models Harvest, MasterCultivar, MasterSeedCatalog
 * @workflow_support Multi-cultivar harvest recording, agricultural data collection
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class HarvestForm
{
    /**
     * Generate complete Filament form schema for simplified agricultural harvest recording.
     *
     * Creates structured form sections for harvest details and cultivar-based harvest entry.
     * Simplified approach focuses on harvest date, cultivar selection, and weight tracking
     * without complex tray relationships for efficient harvest data collection.
     * 
     * Supports both create mode (with repeater for multiple cultivars) and edit mode
     * (with direct fields for single harvest record editing).
     *
     * @param bool $isEdit Whether this is for editing an existing record
     * @return array Complete Filament form schema array with sections and cultivar fields
     * @filament_method Primary form schema generator for simplified harvest workflow
     * @agricultural_workflow Harvest recording with multi-cultivar selection support
     * @business_rules Validates weight minimums, enforces cultivar selection requirements
     * @simple_design Eliminates tray complexity for streamlined harvest data entry
     */
    public static function schema(bool $isEdit = false): array
    {
        if ($isEdit) {
            return [
                Section::make('Harvest Details')
                    ->schema([
                        static::getHarvestDatePicker(),
                        static::getUserIdField(),
                    ])
                    ->columns(1),
                Section::make('Harvest Information')
                    ->schema([
                        static::getCultivarSelectDirect(),
                        static::getWeightInput(),
                        static::getGeneralNotesField(),
                    ])
                    ->columns(2),
            ];
        }
        
        return [
            Section::make('Harvest Details')
                ->schema([
                    static::getHarvestDatePicker(),
                    static::getUserIdField(),
                ])
                ->columns(1),
            Section::make('Cultivar Harvests')
                ->schema([
                    static::getCultivarHarvestsRepeater(),
                    static::getGeneralNotesField(),
                ])
                ->columns(1),
        ];
    }


    /**
     * Generate harvest date picker with agricultural business validation.
     *
     * Creates date input with validation to prevent future-dated harvests.
     * Defaults to current date for immediate harvest recording workflow.
     * Implements reactive behavior for dependent field updates.
     *
     * @return DatePicker Configured Filament DatePicker with agricultural validation
     * @agricultural_context Harvest date recording for production tracking
     * @business_rule Prevents future-dated harvests (maxDate: now())
     * @workflow_default Defaults to today for typical same-day harvest recording
     * @reactive_behavior Triggers dependent field validations on date changes
     */
    protected static function getHarvestDatePicker(): DatePicker
    {
        return DatePicker::make('harvest_date')
            ->label('Harvest Date')
            ->required()
            ->default(now())
            ->maxDate(now())
            ->reactive();
    }

    /**
     * Generate hidden user ID field for harvest tracking.
     *
     * Automatically captures the authenticated user for harvest attribution.
     * Essential for agricultural traceability and production accountability.
     *
     * @return Hidden Configured Filament Hidden component with user ID
     * @agricultural_context Production worker identification for harvest accountability
     * @business_requirement Required for food safety traceability and quality control
     * @authentication Uses auth()->id() for current user capture
     */
    protected static function getUserIdField(): Hidden
    {
        return Hidden::make('user_id')
            ->default(auth()->id());
    }

    /**
     * Generate cultivar-based repeater for multi-cultivar harvest recording.
     *
     * Creates dynamic repeater allowing selection of multiple cultivars for harvest
     * with weight tracking per cultivar. Implements intelligent item labeling showing
     * cultivar full name and harvest weight for easy identification during
     * agricultural operations.
     *
     * @return Repeater Configured Filament Repeater for cultivar-based harvest entry
     * @agricultural_workflow Multi-cultivar harvest recording with individual cultivar metrics
     * @business_context Supports harvesting multiple cultivars in single session
     * @ui_behavior Dynamic item labels show "Cultivar Name - weight(g)"
     * @collapsible Allows collapse/expand for better form organization during data entry
     */
    protected static function getCultivarHarvestsRepeater(): Repeater
    {
        return Repeater::make('cultivar_harvests')
            ->label('Select Cultivars to Harvest')
            ->schema([
                Grid::make(2)
                    ->schema([
                        static::getCultivarSelectForRepeater(),
                        static::getWeightInput(),
                    ]),
            ])
            ->addActionLabel('Add Another Cultivar')
            ->collapsible()
            ->itemLabel(function (array $state): ?string {
                if (!$state['master_cultivar_id']) {
                    return 'New Cultivar';
                }
                
                $cultivar = MasterCultivar::find($state['master_cultivar_id']);
                if (!$cultivar) {
                    return 'Unknown Cultivar';
                }
                
                $weight = $state['total_weight_grams'] ?? 0;
                
                return "{$cultivar->full_name} - {$weight}g";
            });
    }

    /**
     * Generate cultivar selection field for repeater with full name display.
     *
     * Creates searchable dropdown of active cultivars showing full names including
     * seed type and cultivar name. Filters to show only active cultivars with
     * active seed catalog relationships for agricultural harvest operations.
     *
     * @return Select Configured Filament Select component for cultivar selection
     * @agricultural_context Cultivar selection for microgreens harvest operations
     * @business_logic Only shows active cultivars with active seed catalog entries
     * @display_format Uses MasterCultivar::full_name for comprehensive variety identification
     * @searchable Enables efficient cultivar lookup during harvest recording
     */
    protected static function getCultivarSelectForRepeater(): Select
    {
        return Select::make('master_cultivar_id')
            ->label('Cultivar')
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
            ->reactive();
    }

    /**
     * Generate direct cultivar selection field for single harvest editing.
     *
     * Creates searchable dropdown of active cultivars for editing individual harvest
     * records. Uses same options as repeater but configured for direct field use
     * without repeater context.
     *
     * @return Select Configured Filament Select component for direct cultivar selection
     * @agricultural_context Cultivar selection for individual harvest record editing
     * @business_logic Only shows active cultivars with active seed catalog entries
     * @display_format Uses MasterCultivar::full_name for comprehensive variety identification
     * @edit_context Used in EditHarvest page for single record modification
     */
    protected static function getCultivarSelectDirect(): Select
    {
        return Select::make('master_cultivar_id')
            ->label('Cultivar')
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
            ->searchable();
    }

    /**
     * Generate harvested weight input with agricultural measurement validation.
     *
     * Creates numeric input for recording harvest weight in grams with precision
     * support for accurate agricultural production tracking. Validates minimum
     * weight requirements and supports decimal precision for precise measurements.
     *
     * @return TextInput Configured Filament TextInput for weight measurement
     * @agricultural_context Harvest weight recording in grams for production metrics
     * @measurement_unit Grams (standard microgreens industry measurement)
     * @precision Supports 0.01 gram increments for accurate weight recording
     * @validation Minimum value 0, required field for harvest completion
     */
    protected static function getWeightInput(): TextInput
    {
        return TextInput::make('total_weight_grams')
            ->label('Weight (g)')
            ->required()
            ->numeric()
            ->minValue(0)
            ->step(0.01);
    }


    /**
     * Generate general harvest notes field for overall harvest observations.
     *
     * Creates multi-line textarea for recording general harvest session notes.
     * Supports documentation of overall harvest conditions, environmental factors,
     * or general observations applicable to the entire harvest operation.
     *
     * @return Textarea Configured Filament Textarea for general harvest notes
     * @agricultural_context Overall harvest session documentation
     * @production_tracking Captures environmental conditions, quality observations
     * @column_span Full width for comprehensive note recording
     * @rows Multiple rows (3) for detailed observation entry
     */
    protected static function getGeneralNotesField(): Textarea
    {
        return Textarea::make('notes')
            ->label('General Notes')
            ->rows(3)
            ->columnSpanFull();
    }
}