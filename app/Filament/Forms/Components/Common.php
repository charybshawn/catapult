<?php

namespace App\Filament\Forms\Components;

use Filament\Forms;

class Common
{
    /**
     * Create a basic information section with name and active toggle
     */
    public static function basicInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Basic Information')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Create a supplier selection field with inline creation form
     */
    public static function supplierSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('supplier_id')
            ->label('Supplier')
            ->relationship('supplier', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->createOptionForm([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'seed' => 'Seed Supplier',
                        'soil' => 'Soil Supplier', 
                        'packaging' => 'Packaging Supplier',
                        'other' => 'Other',
                    ])
                    ->default('other'),
                Forms\Components\Textarea::make('contact_info')
                    ->label('Contact Information')
                    ->rows(3),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    /**
     * Create a price input field with currency
     */
    public static function priceInput(string $field = 'price', string $label = 'Price'): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($field)
            ->label($label)
            ->numeric()
            ->prefix('$')
            ->required()
            ->minValue(0)
            ->step(0.01);
    }

    /**
     * Create a currency selection field
     */
    public static function currencySelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('currency')
            ->options([
                'USD' => 'USD',
                'CAD' => 'CAD', 
                'EUR' => 'EUR',
                'GBP' => 'GBP',
            ])
            ->default('USD')
            ->required();
    }

    /**
     * Create a contact information section
     */
    public static function contactInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Contact Information')
            ->schema([
                Forms\Components\TextInput::make('contact_name')
                    ->label('Contact Name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_email')
                    ->label('Contact Email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')
                    ->label('Contact Phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->label('Address')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Create a weight measurement field with unit selection
     */
    public static function weightMeasurementField(
        string $weightField = 'weight',
        string $unitField = 'weight_unit',
        string $label = 'Weight'
    ): Forms\Components\Group {
        return Forms\Components\Group::make([
            Forms\Components\TextInput::make($weightField)
                ->label($label)
                ->numeric()
                ->step(0.001)
                ->minValue(0)
                ->required(),
            Forms\Components\Select::make($unitField)
                ->label('Unit')
                ->options([
                    'g' => 'Grams',
                    'kg' => 'Kilograms', 
                    'oz' => 'Ounces',
                    'lb' => 'Pounds',
                ])
                ->default('g')
                ->required(),
        ])->columns(2);
    }

    /**
     * Create a quantity measurement field with unit selection
     */
    public static function quantityMeasurementField(
        string $quantityField = 'quantity',
        string $unitField = 'quantity_unit',
        string $label = 'Quantity'
    ): Forms\Components\Group {
        return Forms\Components\Group::make([
            Forms\Components\TextInput::make($quantityField)
                ->label($label)
                ->numeric()
                ->step(0.1)
                ->minValue(0)
                ->required(),
            Forms\Components\Select::make($unitField)
                ->label('Unit')
                ->options([
                    'g' => 'Grams',
                    'kg' => 'Kilograms',
                    'l' => 'Liters',
                    'ml' => 'Milliliters', 
                    'oz' => 'Ounces',
                    'lb' => 'Pounds',
                    'pieces' => 'Pieces',
                    'units' => 'Units',
                ])
                ->default('g')
                ->required(),
        ])->columns(2);
    }

    /**
     * Create a customer type selection field
     */
    public static function customerTypeSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('customer_type')
            ->label('Customer Type')
            ->options([
                'retail' => 'Retail',
                'wholesale' => 'Wholesale',
            ])
            ->default('retail')
            ->required();
    }

    /**
     * Create an active toggle field
     */
    public static function activeToggle(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('is_active')
            ->label('Active')
            ->default(true)
            ->inline(false);
    }

    /**
     * Create a notes/description textarea
     */
    public static function notesTextarea(string $field = 'notes', string $label = 'Notes'): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make($field)
            ->label($label)
            ->rows(3)
            ->columnSpanFull();
    }

    /**
     * Create a numeric input with validation
     */
    public static function numericInput(
        string $field,
        string $label,
        float $min = 0,
        float $step = 1,
        ?string $suffix = null,
        ?string $prefix = null
    ): Forms\Components\TextInput {
        $input = Forms\Components\TextInput::make($field)
            ->label($label)
            ->numeric()
            ->minValue($min)
            ->step($step);
            
        if ($suffix) {
            $input->suffix($suffix);
        }
        
        if ($prefix) {
            $input->prefix($prefix);
        }
        
        return $input;
    }

    /**
     * Create a date field
     */
    public static function dateField(string $field, string $label): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make($field)
            ->label($label)
            ->required();
    }

    /**
     * Create a datetime field
     */
    public static function datetimeField(string $field, string $label): Forms\Components\DateTimePicker
    {
        return Forms\Components\DateTimePicker::make($field)
            ->label($label)
            ->required();
    }
}