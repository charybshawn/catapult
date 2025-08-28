<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;

/**
 * Enhanced Numeric Input Component
 * 
 * Specialized Filament text input component optimized for agricultural
 * numeric data entry with improved user experience patterns. Provides
 * enhanced typing experience for agricultural measurements and calculations.
 * 
 * @filament_component Enhanced numeric input with agricultural UX optimizations
 * @agricultural_use Optimized for agricultural measurements (weights, quantities, prices, percentages)
 * @ux_improvements Longer debounce and lazy evaluation for better typing experience
 * @use_cases Product weights, seed quantities, pricing, recipe percentages, crop measurements
 * 
 * Key improvements:
 * - Extended debounce timing for agricultural data entry workflows
 * - Lazy evaluation to reduce server requests during typing
 * - Optimized for precision measurements common in agriculture
 * 
 * @package App\Filament\Forms\Components
 * @author Shawn
 * @since 2024
 */
class NumericInput extends TextInput
{
    /**
     * Configure numeric input with agricultural UX optimizations.
     * 
     * @agricultural_context Optimized for agricultural measurement entry workflows
     * @return void
     * @ux_pattern Lazy evaluation with extended debounce for precision data entry
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->numeric()
            ->lazy() // Use lazy evaluation by default
            ->debounce(600); // Longer debounce for better typing experience
    }
    
    /**
     * Create enhanced numeric input instance.
     * 
     * @agricultural_context Factory method for agricultural numeric input creation
     * @param string $name Field name for the numeric input
     * @return static Configured NumericInput instance with agricultural optimizations
     */
    public static function make(string $name): static
    {
        $static = app(static::class, ['name' => $name]);
        $static->configure();

        return $static;
    }
}