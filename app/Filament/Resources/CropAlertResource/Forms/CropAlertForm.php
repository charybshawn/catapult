<?php

namespace App\Filament\Resources\CropAlertResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms;

/**
 * Crop alert form schema for agricultural monitoring system.
 * 
 * Provides comprehensive form interface for managing crop alerts including
 * alert configuration, scheduling parameters, and monitoring conditions.
 * Supports agricultural production workflow automation and monitoring.
 * 
 * @filament_form Crop alert management and monitoring configuration
 * @business_context Agricultural production monitoring and alerts
 * @monitoring_system Automated crop stage transitions and notifications
 */
class CropAlertForm
{
    /**
     * Get complete crop alert form schema with monitoring configuration.
     * 
     * Returns structured form sections for alert information, scheduling
     * parameters, and monitoring conditions. Supports agricultural production
     * workflow automation with comprehensive alert management.
     * 
     * @return array Complete crop alert form schema
     * @filament_usage Form schema for CropAlertResource
     * @business_logic Agricultural monitoring and alert configuration
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
     * Get basic alert information section with monitoring context.
     * 
     * Returns form section displaying alert type, resource information,
     * and frequency settings for agricultural monitoring system configuration.
     * 
     * @return Section Basic alert information form section
     * @agricultural_context Alert types for crop stage monitoring
     * @monitoring_config Alert frequency and resource type settings
     */
    protected static function getBasicInformationSection(): Section
    {
        return Section::make('Alert Information')
            ->schema([
                TextInput::make('task_name')
                    ->label('Alert Type')
                    ->formatStateUsing(fn ($state) => str_starts_with($state, 'advance_to_') 
                        ? 'Advance to ' . ucfirst(str_replace('advance_to_', '', $state))
                        : ucfirst(str_replace('_', ' ', $state)))
                    ->readOnly(),
                    
                TextInput::make('resource_type')
                    ->label('Resource Type')
                    ->readOnly(),
                    
                TextInput::make('frequency')
                    ->label('Frequency')
                    ->readOnly(),
            ])
            ->columns(3);
    }

    /**
     * Scheduling information section
     */
    protected static function getSchedulingSection(): Section
    {
        return Section::make('Scheduling')
            ->schema([
                DateTimePicker::make('next_run_at')
                    ->label('Scheduled For')
                    ->required(),
                    
                DateTimePicker::make('last_run_at')
                    ->label('Last Executed')
                    ->disabled(),
                    
                Toggle::make('is_active')
                    ->label('Is Active')
                    ->required(),
            ])
            ->columns(3);
    }

    /**
     * Conditions section
     */
    protected static function getConditionsSection(): Section
    {
        return Section::make('Conditions')
            ->schema([
                KeyValue::make('conditions')
                    ->label('Alert Conditions')
                    ->disabled(),
            ]);
    }
}