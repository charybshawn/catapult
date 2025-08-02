<?php

namespace App\Filament\Resources\OrderResource\Forms;

use Carbon\Carbon;
use Filament\Forms;
use Illuminate\Support\Facades\Log;

/**
 * Delivery Date Field for Orders - Handles delivery date with automatic harvest calculation
 * Extracted from OrderResource lines 146-189
 * Following Filament Resource Architecture Guide patterns
 */
class DeliveryDateField
{
    /**
     * Get the delivery date field with harvest date calculation
     */
    public static function make(): Forms\Components\DateTimePicker
    {
        return Forms\Components\DateTimePicker::make('delivery_date')
            ->label('Delivery Date')
            ->required()
            ->live(onBlur: true)(onBlur: true)
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                static::updateHarvestDate($state, $set);
            })
            ->helperText(function (callable $get) {
                return static::getHelperText($get);
            })
            ->visible(fn (Forms\Get $get) => ! $get('is_recurring'));
    }

    /**
     * Get the harvest date field (companion to delivery date)
     */
    public static function getHarvestDateField(): Forms\Components\DateTimePicker
    {
        return Forms\Components\DateTimePicker::make('harvest_date')
            ->label('Harvest Date')
            ->helperText('When this order should be harvested (automatically set to evening before delivery, but can be overridden)')
            ->required(fn (Forms\Get $get) => ! $get('is_recurring'))
            ->visible(fn (Forms\Get $get) => ! $get('is_recurring'));
    }

    /**
     * Update harvest date based on delivery date
     * TODO: Consider extracting to OrderDateCalculationAction for complex business logic
     */
    protected static function updateHarvestDate($deliveryDate, callable $set): void
    {
        if ($deliveryDate) {
            try {
                // Calculate harvest date as the evening before delivery date
                $deliveryDateTime = Carbon::parse($deliveryDate);
                $harvestDateTime = $deliveryDateTime->copy()->subDay()->setTime(16, 0); // 4:00 PM day before
                $set('harvest_date', $harvestDateTime->toDateTimeString());
            } catch (\Exception $e) {
                // If parsing fails, don't update harvest_date
                Log::error('Failed to parse delivery date: '.$e->getMessage());
            }
        }
    }

    /**
     * Get dynamic helper text with delivery warnings
     * TODO: Consider extracting delivery validation to OrderValidationAction
     */
    protected static function getHelperText(callable $get): string
    {
        $helperText = 'Select the date and time for delivery - harvest date will be automatically set to 4:00 PM the day before';

        // Check if delivery date is too soon
        $deliveryDate = $get('delivery_date');
        if ($deliveryDate) {
            try {
                $delivery = Carbon::parse($deliveryDate);
                $daysUntilDelivery = now()->diffInDays($delivery, false);

                // Most crops need at least 5-21 days to grow
                if ($daysUntilDelivery < 5) {
                    $helperText .= ' ⚠️ WARNING: This delivery date may be too soon for crop production!';
                }
            } catch (\Exception $e) {
                // Ignore parse errors
            }
        }

        return $helperText;
    }
}
