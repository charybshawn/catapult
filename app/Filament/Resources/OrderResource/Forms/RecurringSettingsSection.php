<?php

namespace App\Filament\Resources\OrderResource\Forms;

use Filament\Forms;

/**
 * Recurring Settings Section for Orders - Configuration for recurring orders
 * Extracted from OrderResource lines 261-284
 * Following Filament Resource Architecture Guide patterns
 */
class RecurringSettingsSection
{
    /**
     * Get the recurring settings section schema
     */
    public static function make(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Recurring Settings')
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema(static::getRecurringFields()),
            ])
            ->visible(fn ($get) => $get('is_recurring'));
    }

    /**
     * Get recurring configuration fields
     */
    protected static function getRecurringFields(): array
    {
        return [
            Forms\Components\Select::make('recurring_frequency')
                ->label('Frequency')
                ->options([
                    'weekly' => 'Weekly',
                    'biweekly' => 'Biweekly',
                    'monthly' => 'Monthly',
                ])
                ->required(),
            
            Forms\Components\DatePicker::make('recurring_start_date')
                ->label('Start Date')
                ->helperText('First occurrence date')
                ->required(),
            
            Forms\Components\DatePicker::make('recurring_end_date')
                ->label('End Date (Optional)')
                ->helperText('Leave empty for indefinite'),
        ];
    }
}