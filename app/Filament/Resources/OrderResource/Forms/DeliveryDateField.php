<?php

namespace App\Filament\Resources\OrderResource\Forms;

use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Utilities\Get;
use Exception;
use Carbon\Carbon;
use Filament\Forms;
use Illuminate\Support\Facades\Log;

/**
 * Delivery date field with automatic harvest date calculation.
 * 
 * Provides intelligent delivery date selection with automatic harvest timing
 * calculation based on agricultural production requirements. Validates delivery
 * timing against crop growing cycles and provides production warnings.
 * 
 * @filament_field Smart delivery date with harvest calculation
 * @business_context Agricultural production timing and delivery coordination
 * @agricultural_logic Harvest scheduling based on crop growing requirements
 */
class DeliveryDateField
{
    /**
     * Create delivery date field with automatic harvest calculation.
     * 
     * Returns configured DateTimePicker with live updates that automatically
     * calculates optimal harvest timing based on delivery requirements.
     * Includes agricultural production warnings for timing validation.
     * 
     * @return DateTimePicker Delivery date field with harvest calculation
     * @filament_usage Order form delivery date with agricultural context
     * @business_logic Automatic harvest timing calculation
     */
    public static function make(): DateTimePicker
    {
        return DateTimePicker::make('delivery_date')
            ->label('Delivery Date')
            ->required()
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                static::updateHarvestDate($state, $set);
            })
            ->helperText(function (callable $get) {
                return static::getHelperText($get);
            })
            ->visible(fn (Get $get) => ! $get('is_recurring'));
    }

    /**
     * Create harvest date field as companion to delivery date.
     * 
     * Provides harvest date selection with automatic population from delivery
     * date calculation. Allows manual override while maintaining agricultural
     * business logic for production timing.
     * 
     * @return DateTimePicker Harvest date field with automatic calculation
     * @agricultural_context Production timing based on delivery requirements
     * @business_override Manual harvest date adjustment capability
     */
    public static function getHarvestDateField(): DateTimePicker
    {
        return DateTimePicker::make('harvest_date')
            ->label('Harvest Date')
            ->helperText('When this order should be harvested (automatically set to evening before delivery, but can be overridden)')
            ->required(fn (Get $get) => ! $get('is_recurring'))
            ->visible(fn (Get $get) => ! $get('is_recurring'));
    }

    /**
     * Calculate and update harvest date based on delivery requirements.
     * 
     * Automatically sets harvest date to 4:00 PM the day before delivery
     * to ensure fresh product availability. Handles date parsing errors
     * gracefully to maintain form stability.
     * 
     * @param mixed $deliveryDate Selected delivery date
     * @param callable $set Form state setter function
     * @agricultural_logic Harvest timing for optimal product freshness
     * @error_handling Graceful date parsing error management
     */
    protected static function updateHarvestDate($deliveryDate, callable $set): void
    {
        if ($deliveryDate) {
            try {
                // Calculate harvest date as the evening before delivery date
                $deliveryDateTime = Carbon::parse($deliveryDate);
                $harvestDateTime = $deliveryDateTime->copy()->subDay()->setTime(16, 0); // 4:00 PM day before
                $set('harvest_date', $harvestDateTime->toDateTimeString());
            } catch (Exception $e) {
                // If parsing fails, don't update harvest_date
                Log::error('Failed to parse delivery date: '.$e->getMessage());
            }
        }
    }

    /**
     * Generate dynamic helper text with agricultural production warnings.
     * 
     * Provides contextual guidance for delivery date selection including
     * automatic harvest calculation explanation and production timing warnings
     * based on crop growing requirements.
     * 
     * @param callable $get Form state getter function
     * @return string Dynamic helper text with production warnings
     * @agricultural_validation Crop production timing warnings
     * @user_guidance Production timing education and warnings
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
            } catch (Exception $e) {
                // Ignore parse errors
            }
        }

        return $helperText;
    }
}
