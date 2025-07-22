<?php

namespace App\Filament\Resources\OrderResource\Forms;

use Filament\Forms;

/**
 * Form component for Crop Plans - organized Filament form schema
 * Following Filament Resource Architecture Guide patterns
 */
class CropPlansForm
{
    /**
     * Returns Filament form schema - NOT a custom form system
     */
    public static function schema(): array
    {
        return [
            Forms\Components\TextInput::make('recipe.name')
                ->label('Recipe')
                ->disabled(),
            Forms\Components\TextInput::make('variety.name')
                ->label('Variety')
                ->disabled(),
            Forms\Components\TextInput::make('trays_needed')
                ->label('Trays')
                ->numeric()
                ->disabled(),
            Forms\Components\DatePicker::make('plant_by_date')
                ->label('Plant By')
                ->disabled(),
            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->rows(3),
        ];
    }
}