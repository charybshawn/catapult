<?php

namespace App\Filament\Forms\Components;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms;

/**
 * Common Form Components
 * 
 * Reusable Filament form component library optimized for agricultural
 * business workflows. Provides standardized form patterns for agricultural
 * entities including suppliers, products, inventory, and measurements.
 * 
 * @filament_support Reusable form component patterns
 * @agricultural_use Standardized form components for agricultural business entities
 * @consistency Ensures uniform UI patterns across agricultural resources
 * @business_context Agricultural measurements, supplier relationships, pricing patterns
 * 
 * Key features:
 * - Agricultural measurement fields (weight, quantity with units)
 * - Supplier relationship management with inline creation
 * - Price and currency handling for agricultural products
 * - Contact information patterns for agricultural business entities
 * - Standardized basic information sections
 * 
 * @package App\Filament\Forms\Components
 * @author Shawn
 * @since 2024
 */
class Common
{
    /**
     * Create a basic information section with name and active toggle.
     * 
     * @agricultural_context Standard pattern for agricultural entities (products, suppliers, customers)
     * @return Section Form section with name, active toggle, and description fields
     * @ui_pattern Consistent basic information layout across agricultural resources
     */
    public static function basicInformationSection(): Section
    {
        return Section::make('Basic Information')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->inline(false),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Create a supplier selection field with inline creation form.
     * 
     * @agricultural_context Supplier selection for seeds, soil, packaging, and other agricultural supplies
     * @return Select Relationship select with inline supplier creation capability
     * @supplier_types Supports seed, soil, packaging, and other agricultural supplier types
     * @inline_creation Allows creating new suppliers without leaving current form
     */
    public static function supplierSelect(): Select
    {
        return Select::make('supplier_id')
            ->label('Supplier')
            ->relationship('supplier', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->dehydrated()
            ->createOptionForm([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('type')
                    ->options([
                        'seed' => 'Seed Supplier',
                        'soil' => 'Soil Supplier', 
                        'packaging' => 'Packaging Supplier',
                        'other' => 'Other',
                    ])
                    ->default('other'),
                Textarea::make('contact_info')
                    ->label('Contact Information')
                    ->rows(3),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    /**
     * Create a price input field with currency
     */
    public static function priceInput(string $field = 'price', string $label = 'Price'): TextInput
    {
        return TextInput::make($field)
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
    public static function currencySelect(): Select
    {
        return Select::make('currency')
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
    public static function contactInformationSection(): Section
    {
        return Section::make('Contact Information')
            ->schema([
                TextInput::make('contact_name')
                    ->label('Contact Name')
                    ->maxLength(255),
                TextInput::make('contact_email')
                    ->label('Contact Email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('contact_phone')
                    ->label('Contact Phone')
                    ->tel()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label('Address')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    /**
     * Create a weight measurement field with unit selection.
     * 
     * @agricultural_context Weight measurements for seeds, harvest yields, packaging
     * @param string $weightField Field name for weight value
     * @param string $unitField Field name for weight unit
     * @param string $label Display label for weight field group
     * @return Group Grouped weight and unit fields with agricultural units (grams, kg, oz, lb)
     * @units Supports grams, kilograms, ounces, pounds for agricultural measurements
     */
    public static function weightMeasurementField(
        string $weightField = 'weight',
        string $unitField = 'weight_unit',
        string $label = 'Weight'
    ): Group {
        return Group::make([
            TextInput::make($weightField)
                ->label($label)
                ->numeric()
                ->step(0.001)
                ->minValue(0)
                ->required(),
            Select::make($unitField)
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
     * Create a quantity measurement field with unit selection.
     * 
     * @agricultural_context Quantity measurements for consumables, soil, liquids, packaging
     * @param string $quantityField Field name for quantity value
     * @param string $unitField Field name for quantity unit
     * @param string $label Display label for quantity field group
     * @return Group Grouped quantity and unit fields with diverse agricultural units
     * @units Supports weight, volume, and count units for agricultural inventory management
     */
    public static function quantityMeasurementField(
        string $quantityField = 'quantity',
        string $unitField = 'quantity_unit',
        string $label = 'Quantity'
    ): Group {
        return Group::make([
            TextInput::make($quantityField)
                ->label($label)
                ->numeric()
                ->step(0.1)
                ->minValue(0)
                ->required(),
            Select::make($unitField)
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
     * Create a customer type selection field.
     * 
     * @agricultural_context Customer type selection for agricultural product pricing (retail vs wholesale)
     * @return Select Customer type dropdown with retail/wholesale options
     * @pricing_context Used for applying appropriate price variations to agricultural orders
     */
    public static function customerTypeSelect(): Select
    {
        return Select::make('customer_type')
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
    public static function activeToggle(): Toggle
    {
        return Toggle::make('is_active')
            ->label('Active')
            ->helperText('Toggle right for active, left for inactive')
            ->default(true)
            ->inline(false);
    }

    /**
     * Create a notes/description textarea
     */
    public static function notesTextarea(string $field = 'notes', string $label = 'Notes'): Textarea
    {
        return Textarea::make($field)
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
    ): TextInput {
        $input = TextInput::make($field)
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
    public static function dateField(string $field, string $label): DatePicker
    {
        return DatePicker::make($field)
            ->label($label)
            ->required();
    }

    /**
     * Create a datetime field
     */
    public static function datetimeField(string $field, string $label): DateTimePicker
    {
        return DateTimePicker::make($field)
            ->label($label)
            ->required();
    }
}