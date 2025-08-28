<?php

namespace App\Filament\Resources\OrderResource\Forms;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms;

/**
 * Crop plans form schema for agricultural production planning.
 * 
 * Provides read-only form fields for viewing and editing crop plan details
 * within order context. Displays essential production information including
 * recipe requirements, variety specifications, and planting schedules.
 * 
 * @filament_form Crop plan form components for OrderResource
 * @business_context Agricultural production planning within order workflow
 * @agricultural_data Recipe requirements, variety specs, production timing
 */
class CropPlansForm
{
    /**
     * Get crop plan form schema with agricultural context.
     * 
     * Returns Filament form components for crop plan display and editing
     * within order management interface. Fields are primarily read-only
     * to maintain production plan integrity while allowing notes updates.
     * 
     * @return array Filament form schema for crop plan management
     * @filament_usage Form components for crop plan relation in orders
     * @business_logic Production data display with controlled editing
     */
    public static function schema(): array
    {
        return [
            TextInput::make('recipe.name')
                ->label('Recipe')
                ->disabled(),
            TextInput::make('variety.name')
                ->label('Variety')
                ->disabled(),
            TextInput::make('trays_needed')
                ->label('Trays')
                ->numeric()
                ->disabled(),
            DatePicker::make('plant_by_date')
                ->label('Plant By')
                ->disabled(),
            Textarea::make('notes')
                ->label('Notes')
                ->rows(3),
        ];
    }
}