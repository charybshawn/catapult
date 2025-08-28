<?php

namespace App\Filament\Resources\ActivityResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms;

/**
 * Activity log form builder for agricultural system monitoring and audit trails.
 *
 * Provides comprehensive form structure for viewing and analyzing system activity
 * logs including user actions, model events, API requests, and agricultural
 * operations. Features organized sections for basic information, activity details,
 * and properties with JSON formatting for detailed audit trail inspection.
 *
 * @filament_form Form builder for activity log display and analysis
 * @business_domain Agricultural system activity monitoring and audit compliance
 * @security_context User action tracking and system event audit trails
 * @form_sections Basic info, activity details, and formatted properties display
 * @operational_monitoring Supports agricultural operations tracking and analysis
 */
class ActivityForm
{
    /**
     * Generate complete form schema for comprehensive activity log display.
     *
     * Assembles organized form sections covering basic information, activity
     * details, and properties for thorough activity log inspection and
     * agricultural system monitoring analysis.
     *
     * @return array Complete Filament form schema for activity log display
     * @form_structure Three sections: basic info, activity details, properties
     * @audit_context Supports comprehensive system activity review and analysis
     */
    public static function schema(): array
    {
        return [
            static::getBasicInformationSection(),
            static::getActivityDetailsSection(),
            static::getPropertiesSection(),
        ];
    }

    /**
     * Generate basic information section for core activity log details.
     *
     * Creates form section with essential activity log fields including log name,
     * description, event type, and creation timestamp. All fields are disabled
     * for view-only audit trail inspection in agricultural system monitoring.
     *
     * @return Section Filament form section with core activity information
     * @audit_fields Log name, description, event type, creation timestamp
     * @security_context Read-only access for audit trail integrity
     */
    protected static function getBasicInformationSection(): Section
    {
        return Section::make('Basic Information')
            ->description('Core activity log details')
            ->schema([
                TextInput::make('log_name')
                    ->label('Log Name')
                    ->disabled(),
                TextInput::make('description')
                    ->label('Description')
                    ->disabled(),
                TextInput::make('event')
                    ->label('Event')
                    ->disabled(),
                DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->disabled(),
            ])
            ->columns(2);
    }

    /**
     * Generate activity details section for subject and causer information.
     *
     * Creates form section displaying the entities involved in the activity
     * including subject (what was affected) and causer (who/what triggered
     * the activity). Essential for agricultural system audit trails and
     * operational accountability tracking.
     *
     * @return Section Filament form section with activity relationship details
     * @audit_relationships Subject type/ID and causer type/ID for activity context
     * @agricultural_context Tracks user actions on crops, orders, inventory, etc.
     */
    protected static function getActivityDetailsSection(): Section
    {
        return Section::make('Activity Details')
            ->description('Subject and causer information')
            ->schema([
                TextInput::make('subject_type')
                    ->label('Subject Type')
                    ->disabled(),
                TextInput::make('subject_id')
                    ->label('Subject ID')
                    ->disabled(),
                TextInput::make('causer_type')
                    ->label('Causer Type')
                    ->disabled(),
                TextInput::make('causer_id')
                    ->label('Causer ID')
                    ->disabled(),
            ])
            ->columns(2);
    }

    /**
     * Generate properties section with formatted JSON display for detailed data.
     *
     * Creates comprehensive properties section that formats JSON data for
     * readable display of additional activity information, model changes,
     * and contextual data. Supports detailed agricultural operations analysis
     * and system event inspection.
     *
     * @return Section Filament form section with formatted properties display
     * @data_formatting JSON pretty-printing for readable audit trail inspection
     * @agricultural_context Detailed data for crop changes, inventory movements, etc.
     */
    protected static function getPropertiesSection(): Section
    {
        return Section::make('Properties')
            ->description('Additional data associated with this activity')
            ->schema([
                Textarea::make('properties')
                    ->label('Properties')
                    ->rows(10)
                    ->disabled()
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '';
                        }
                        
                        // If it's already a string, try to decode and pretty print
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            if ($decoded !== null) {
                                return json_encode($decoded, JSON_PRETTY_PRINT);
                            }
                            return $state;
                        }
                        
                        // If it's an array or object, pretty print it
                        return json_encode($state, JSON_PRETTY_PRINT);
                    })
                    ->columnSpanFull(),
            ]);
    }
}