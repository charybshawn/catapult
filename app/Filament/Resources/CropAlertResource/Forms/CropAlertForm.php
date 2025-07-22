<?php

namespace App\Filament\Resources\CropAlertResource\Forms;

use Filament\Forms;

class CropAlertForm
{
    /**
     * Get the complete form schema for CropAlertResource
     */
    public static function schema(): array
    {
        return [
            static::getBasicInformationSection(),
            static::getSchedulingSection(),
            static::getConditionsSection(),
        ];
    }

    /**
     * Basic alert information section
     */
    protected static function getBasicInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Alert Information')
            ->schema([
                Forms\Components\TextInput::make('task_name')
                    ->label('Alert Type')
                    ->formatStateUsing(fn ($state) => str_starts_with($state, 'advance_to_') 
                        ? 'Advance to ' . ucfirst(str_replace('advance_to_', '', $state))
                        : ucfirst(str_replace('_', ' ', $state)))
                    ->readOnly(),
                    
                Forms\Components\TextInput::make('resource_type')
                    ->label('Resource Type')
                    ->readOnly(),
                    
                Forms\Components\TextInput::make('frequency')
                    ->label('Frequency')
                    ->readOnly(),
            ])
            ->columns(3);
    }

    /**
     * Scheduling information section
     */
    protected static function getSchedulingSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Scheduling')
            ->schema([
                Forms\Components\DateTimePicker::make('next_run_at')
                    ->label('Scheduled For')
                    ->required(),
                    
                Forms\Components\DateTimePicker::make('last_run_at')
                    ->label('Last Executed')
                    ->disabled(),
                    
                Forms\Components\Toggle::make('is_active')
                    ->label('Is Active')
                    ->required(),
            ])
            ->columns(3);
    }

    /**
     * Conditions section
     */
    protected static function getConditionsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Conditions')
            ->schema([
                Forms\Components\KeyValue::make('conditions')
                    ->label('Alert Conditions')
                    ->disabled(),
            ]);
    }
}