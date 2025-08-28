<?php

namespace App\Filament\Resources\CropResource\Utilities;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms;

/**
 * Shared form field builders for crop forms
 * Eliminates duplicate DateTimePicker definitions
 */
class CropFormFields
{
    /**
     * Standard soaking datetime field with common configuration
     */
    public static function soakingDateTimeField(string $label = 'Soaking Date', bool $required = false): DateTimePicker
    {
        return DateTimePicker::make('soaking_at')
            ->label($label)
            ->default(now())
            ->seconds(false)
            ->required($required)
            ->visible(fn (Get $get) => CropFormHelpers::checkRecipeRequiresSoaking($get))
            ->reactive();
    }

    /**
     * Standard germination datetime field
     */
    public static function germinationDateTimeField(string $label = 'Germination Date'): DateTimePicker
    {
        return DateTimePicker::make('germination_at')
            ->label($label)
            ->seconds(false);
    }

    /**
     * Standard blackout datetime field
     */
    public static function blackoutDateTimeField(string $label = 'Blackout Date'): DateTimePicker
    {
        return DateTimePicker::make('blackout_at')
            ->label($label)
            ->seconds(false);
    }

    /**
     * Standard light datetime field
     */
    public static function lightDateTimeField(string $label = 'Light Date'): DateTimePicker
    {
        return DateTimePicker::make('light_at')
            ->label($label)
            ->seconds(false);
    }

    /**
     * Standard harvest datetime field
     */
    public static function harvestDateTimeField(string $label = 'Harvested Date'): DateTimePicker
    {
        return DateTimePicker::make('harvested_at')
            ->label($label)
            ->seconds(false);
    }

    /**
     * Complete timeline fields set - all stage datetime fields
     */
    public static function timelineFields(): array
    {
        return [
            self::soakingDateTimeField('Soaking Date'),
            self::germinationDateTimeField(),
            self::blackoutDateTimeField(),
            self::lightDateTimeField(),
            self::harvestDateTimeField(),
        ];
    }

    /**
     * Advanced timeline field with helper text
     */
    public static function advancedTimelineField(string $field, string $label, ?string $helperText = null): DateTimePicker
    {
        $field = DateTimePicker::make($field)
            ->label($label)
            ->seconds(false);
            
        if ($helperText) {
            $field->helperText($helperText);
        }
        
        return $field;
    }
}