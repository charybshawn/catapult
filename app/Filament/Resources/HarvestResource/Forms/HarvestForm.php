<?php

namespace App\Filament\Resources\HarvestResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Actions\Harvest\GetAvailableCropsAction;
use App\Models\MasterCultivar;
use App\Models\Crop;
use Filament\Forms;

/**
 * Filament form schema builder for agricultural harvest management.
 *
 * Provides comprehensive form components for recording microgreens harvest operations
 * including cultivar selection, tray management, weight tracking, and harvest notes.
 * Implements complex reactive forms with automated crop filtering based on
 * agricultural growth stage validation.
 *
 * @filament_form
 * @business_domain Agricultural harvest tracking and production recording
 * @related_models Harvest, Crop, MasterCultivar, MasterSeedCatalog
 * @workflow_support Multi-tray harvest recording, agricultural data collection
 * @author Catapult Development Team
 * @since Laravel 12.x + Filament v4
 */
class HarvestForm
{
    /**
     * Generate complete Filament form schema for agricultural harvest recording.
     *
     * Creates structured form sections for harvest details and tray selection with
     * complex reactive behaviors. Includes automated crop filtering, weight validation,
     * and agricultural business rule enforcement for microgreens production workflows.
     *
     * @return array Complete Filament form schema array with sections and reactive components
     * @filament_method Primary form schema generator
     * @agricultural_workflow Harvest recording with multi-tray selection support
     * @business_rules Validates crop readiness, enforces weight minimums, tracks harvest percentages
     * @reactive_behavior Cultivar selection triggers crop list updates, maintains form state consistency
     */
    public static function schema(): array
    {
        return [
            Section::make('Harvest Details')
                ->schema([
                    static::getCultivarSelect(),
                    static::getHarvestDatePicker(),
                    static::getUserIdField(),
                ])
                ->columns(2),
            Section::make('Tray Selection')
                ->schema([
                    static::getCropsRepeater(),
                    static::getGeneralNotesField(),
                ])
                ->columns(1),
        ];
    }

    /**
     * Generate Master Cultivar selection field with comprehensive agricultural filtering.
     *
     * Creates searchable dropdown of active microgreens cultivars with full name display.
     * Implements reactive behavior to clear dependent crop selections when cultivar changes.
     * Filters to show only active cultivars with active seed catalog relationships.
     *
     * @return Select Configured Filament Select component for cultivar selection
     * @agricultural_context Microgreens cultivar selection for variety-specific harvest tracking
     * @business_logic Only shows active cultivars with active seed catalog entries
     * @reactive_behavior Triggers crop list clearing when selection changes
     * @display_format Uses MasterCultivar::full_name for comprehensive variety identification
     */
    protected static function getCultivarSelect(): Select
    {
        return Select::make('master_cultivar_id')
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
     * Generate complex crops repeater for multi-tray harvest selection.
     *
     * Creates dynamic repeater allowing selection of multiple trays for harvest
     * with individual weight, percentage, and notes tracking. Implements intelligent
     * item labeling showing tray number, weight, and harvest percentage for easy
     * identification during agricultural operations.
     *
     * @return Repeater Configured Filament Repeater with complex tray selection grid
     * @agricultural_workflow Multi-tray harvest recording with individual tray metrics
     * @business_context Supports partial harvesting with percentage tracking
     * @ui_behavior Dynamic item labels show "Tray {number} - {weight}g ({percentage}%)"
     * @collapsible Allows collapse/expand for better form organization during data entry
     */
    protected static function getCropsRepeater(): Repeater
    {
        return Repeater::make('crops')
            ->label('Select Trays to Harvest')
            ->schema([
                Grid::make(4)
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
     * Generate tray selection field with complex agricultural crop filtering.
     *
     * Creates searchable dropdown of available crops filtered by cultivar selection.
     * Implements complex filtering logic through GetAvailableCropsAction to show
     * only harvestable trays based on agricultural growth stage validation.
     *
     * @return Select Configured Filament Select component for tray/crop selection
     * @agricultural_context Tray selection for microgreens harvest operations
     * @business_logic Filters crops by cultivar and harvest readiness status
     * @dependency Requires cultivar selection (../../master_cultivar_id) to populate options
     * @action_integration Uses GetAvailableCropsAction for complex filtering logic
     */
    protected static function getTraySelect(): Select
    {
        return Select::make('crop_id')
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
        return TextInput::make('harvested_weight_grams')
            ->label('Weight (g)')
            ->required()
            ->numeric()
            ->minValue(0)
            ->step(0.01);
    }

    /**
     * Generate percentage harvested input for partial harvest support.
     *
     * Creates numeric input for recording what percentage of a tray was harvested.
     * Supports partial harvesting workflows common in microgreens production
     * where trays may be harvested in multiple sessions for optimal quality.
     *
     * @return TextInput Configured Filament TextInput for harvest percentage
     * @agricultural_context Partial harvest percentage tracking for production optimization
     * @business_workflow Supports multiple harvest sessions from single tray
     * @validation Range 0-100%, defaults to 100% for complete harvest
     * @precision 0.1% increments for precise partial harvest recording
     * @display_format Includes % suffix for clear unit indication
     */
    protected static function getPercentageInput(): TextInput
    {
        return TextInput::make('percentage_harvested')
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
     * Generate tray-specific notes input for individual tray observations.
     *
     * Creates optional text input for recording tray-specific notes during harvest.
     * Supports agricultural quality observations, growth anomalies, or special
     * handling requirements for individual trays in the harvest operation.
     *
     * @return TextInput Configured Filament TextInput for tray notes
     * @agricultural_context Individual tray observation recording during harvest
     * @quality_control Supports documentation of tray-specific quality issues
     * @optional_field Not required, placeholder guides user input
     * @production_notes Captures valuable data for harvest quality improvement
     */
    protected static function getTrayNotesInput(): TextInput
    {
        return TextInput::make('notes')
            ->label('Tray Notes')
            ->placeholder('Optional notes for this tray');
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