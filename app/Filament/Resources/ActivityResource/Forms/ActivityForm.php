<?php

namespace App\Filament\Resources\ActivityResource\Forms;

use Filament\Forms;

class ActivityForm
{
    /**
     * Get the complete form schema for ActivityResource
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
     * Basic information section
     */
    protected static function getBasicInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Basic Information')
            ->description('Core activity log details')
            ->schema([
                Forms\Components\TextInput::make('log_name')
                    ->label('Log Name')
                    ->disabled(),
                Forms\Components\TextInput::make('description')
                    ->label('Description')
                    ->disabled(),
                Forms\Components\TextInput::make('event')
                    ->label('Event')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->disabled(),
            ])
            ->columns(2);
    }

    /**
     * Activity details section
     */
    protected static function getActivityDetailsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Activity Details')
            ->description('Subject and causer information')
            ->schema([
                Forms\Components\TextInput::make('subject_type')
                    ->label('Subject Type')
                    ->disabled(),
                Forms\Components\TextInput::make('subject_id')
                    ->label('Subject ID')
                    ->disabled(),
                Forms\Components\TextInput::make('causer_type')
                    ->label('Causer Type')
                    ->disabled(),
                Forms\Components\TextInput::make('causer_id')
                    ->label('Causer ID')
                    ->disabled(),
            ])
            ->columns(2);
    }

    /**
     * Properties section with JSON formatting
     */
    protected static function getPropertiesSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Properties')
            ->description('Additional data associated with this activity')
            ->schema([
                Forms\Components\Textarea::make('properties')
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