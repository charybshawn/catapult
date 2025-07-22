<?php

namespace App\Filament\Resources\CropPlanResource\Forms;

use Filament\Forms;

/**
 * Form component for CropPlan resource following Filament Resource Architecture Guide
 * Returns Filament form schema - organized Filament components, not custom form system
 */
class CropPlanForm
{
    /**
     * Returns Filament form schema array
     */
    public static function schema(): array
    {
        return [
            Forms\Components\Section::make('Plan Details')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            static::getOrderField(),
                            static::getRecipeField(),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            static::getTraysNeededField(),
                            static::getGramsNeededField(),
                            static::getGramsPerTrayField(),
                        ]),
                ]),

            Forms\Components\Section::make('Timeline')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            static::getPlantByDateField(),
                            static::getExpectedHarvestDateField(),
                            static::getDeliveryDateField(),
                        ]),
                ]),

            Forms\Components\Section::make('Status & Approval')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            static::getStatusField(),
                            static::getApprovedByField(),
                        ]),

                    static::getApprovedAtField(),
                ]),

            Forms\Components\Section::make('Calculation Details')
                ->schema([
                    static::getNotesField(),
                    static::getAdminNotesField(),
                    static::getCalculationDetailsField(),
                    static::getOrderItemsIncludedField(),
                ])
                ->collapsible()
                ->collapsed(),
        ];
    }

    protected static function getOrderField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('order_id')
            ->label('Order')
            ->relationship('order', 'id', function ($query) {
                return $query->with('customer');
            })
            ->getOptionLabelFromRecordUsing(function ($record) {
                $customerName = $record->customer->contact_name ?? 'Unknown';
                return "Order #{$record->id} - {$customerName}";
            })
            ->searchable()
            ->preload()
            ->required();
    }

    protected static function getRecipeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('recipe_id')
            ->label('Recipe')
            ->relationship('recipe', 'name')
            ->searchable()
            ->preload()
            ->required();
    }

    protected static function getTraysNeededField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('trays_needed')
            ->label('Trays Needed')
            ->numeric()
            ->minValue(1)
            ->required();
    }

    protected static function getGramsNeededField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('grams_needed')
            ->label('Grams Needed')
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->required();
    }

    protected static function getGramsPerTrayField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('grams_per_tray')
            ->label('Grams per Tray')
            ->numeric()
            ->minValue(0)
            ->step(0.01);
    }

    protected static function getPlantByDateField(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('plant_by_date')
            ->label('Plant By Date')
            ->required();
    }

    protected static function getExpectedHarvestDateField(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('expected_harvest_date')
            ->label('Expected Harvest Date')
            ->required();
    }

    protected static function getDeliveryDateField(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('delivery_date')
            ->label('Delivery Date')
            ->required();
    }

    protected static function getStatusField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('status_id')
            ->label('Status')
            ->relationship('status', 'name')
            ->default(function () {
                return \App\Models\CropPlanStatus::findByCode('draft')->id;
            })
            ->required();
    }

    protected static function getApprovedByField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('approved_by')
            ->label('Approved By')
            ->relationship('approvedBy', 'name')
            ->searchable()
            ->preload()
            ->visible(fn ($record) => $record && $record->approved_by);
    }

    protected static function getApprovedAtField(): Forms\Components\DateTimePicker
    {
        return Forms\Components\DateTimePicker::make('approved_at')
            ->label('Approved At')
            ->disabled()
            ->visible(fn ($record) => $record && $record->approved_at);
    }

    protected static function getNotesField(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('notes')
            ->label('Notes')
            ->rows(3);
    }

    protected static function getAdminNotesField(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('admin_notes')
            ->label('Admin Notes')
            ->rows(3);
    }

    protected static function getCalculationDetailsField(): Forms\Components\KeyValue
    {
        return Forms\Components\KeyValue::make('calculation_details')
            ->label('Calculation Details')
            ->addActionLabel('Add Detail')
            ->columnSpanFull();
    }

    protected static function getOrderItemsIncludedField(): Forms\Components\KeyValue
    {
        return Forms\Components\KeyValue::make('order_items_included')
            ->label('Order Items Included')
            ->addActionLabel('Add Item')
            ->columnSpanFull();
    }
}