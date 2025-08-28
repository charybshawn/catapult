<?php

namespace App\Filament\Resources\OrderResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms;

/**
 * Recurring order settings section for agricultural delivery automation.
 * 
 * Provides configuration interface for recurring order setup including
 * frequency settings, start/end dates, and delivery scheduling. Supports
 * agricultural business patterns for regular customer deliveries.
 * 
 * @filament_section Recurring order configuration interface
 * @business_context Agricultural delivery automation for regular customers
 * @workflow_automation Recurring order generation and scheduling
 */
class RecurringSettingsSection
{
    /**
     * Create recurring settings section for order automation.
     * 
     * Returns configured section with recurring order settings including
     * frequency selection, date range configuration, and delivery scheduling
     * for agricultural business automation.
     * 
     * @return Section Recurring settings section with automation options
     * @filament_usage Order form section for recurring configuration
     * @business_automation Regular delivery setup for consistent customers
     */
    public static function make(): Section
    {
        return Section::make('Recurring Settings')
            ->schema([
                Grid::make(3)
                    ->schema(static::getRecurringFields()),
            ])
            ->visible(fn ($get) => $get('is_recurring'));
    }

    /**
     * Get recurring order configuration fields.
     * 
     * Returns field collection for recurring order setup including frequency
     * options (weekly, biweekly, monthly), date range selection, and scheduling
     * parameters for agricultural delivery automation.
     * 
     * @return array Recurring order configuration fields
     * @business_patterns Weekly, biweekly, monthly delivery frequencies
     * @agricultural_context Regular delivery scheduling for restaurants and wholesale
     */
    protected static function getRecurringFields(): array
    {
        return [
            Select::make('recurring_frequency')
                ->label('Frequency')
                ->options([
                    'weekly' => 'Weekly',
                    'biweekly' => 'Biweekly',
                    'monthly' => 'Monthly',
                ])
                ->required(),
            
            DatePicker::make('recurring_start_date')
                ->label('Start Date')
                ->helperText('First occurrence date')
                ->required(),
            
            DatePicker::make('recurring_end_date')
                ->label('End Date (Optional)')
                ->helperText('Leave empty for indefinite'),
        ];
    }
}