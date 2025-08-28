<?php

namespace App\Filament\Resources\OrderResource\Forms;

use Filament\Forms\Components\Select;
use Illuminate\Support\Collection;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Textarea;
use App\Models\Customer;
use Filament\Forms;

/**
 * Customer selection field with inline creation for order management.
 * 
 * Provides comprehensive customer selection interface with ability to create
 * new customers directly within order form. Handles both retail and wholesale
 * customer types with appropriate pricing and business relationship setup.
 * 
 * @filament_field Advanced customer selection with inline creation
 * @business_context Agricultural customer management with retail/wholesale distinction
 * @customer_types Retail and wholesale customers with different pricing structures
 */
class CustomerSelectionField
{
    /**
     * Create customer selection field with inline creation capability.
     * 
     * Returns configured Select component with searchable customer options,
     * inline customer creation form, and proper business/contact name display.
     * Supports immediate customer creation within order workflow.
     * 
     * @return Select Customer selection field with creation form
     * @filament_usage Order form customer selection with inline creation
     * @business_workflow Streamlined customer creation within order process
     */
    public static function make(): Select
    {
        return Select::make('customer_id')
            ->label('Customer')
            ->options(function () {
                return static::getCustomerOptions();
            })
            ->searchable()
            ->preload()
            ->required()
            ->createOptionForm(static::getCustomerCreationForm())
            ->createOptionUsing(function (array $data): int {
                return Customer::create($data)->getKey();
            })
            ->helperText('Select existing customer or create a new one');
    }

    /**
     * Get formatted customer options for selection dropdown.
     * 
     * Creates user-friendly display format combining business name and contact
     * name for clear customer identification. Prioritizes business name when
     * available for professional customer recognition.
     * 
     * @return Collection Formatted customer options for dropdown display
     * @business_logic Business name prioritization with contact fallback
     * @user_experience Clear customer identification in selection interface
     */
    protected static function getCustomerOptions(): Collection
    {
        return Customer::all()
            ->mapWithKeys(function ($customer) {
                $display = $customer->business_name 
                    ? $customer->business_name . ' (' . $customer->contact_name . ')'
                    : $customer->contact_name;
                return [$customer->id => $display];
            });
    }

    /**
     * Get comprehensive customer creation form schema.
     * 
     * Provides complete customer onboarding form with business information,
     * contact details, addressing, and customer type configuration. Handles
     * wholesale discount setup and agricultural business relationship data.
     * 
     * @return array Complete customer creation form schema
     * @business_onboarding Comprehensive customer setup with business context
     * @customer_types Retail and wholesale customer configuration
     * @agricultural_context Business relationships for agricultural products
     */
    protected static function getCustomerCreationForm(): array
    {
        return [
            TextInput::make('contact_name')
                ->label('Contact Name')
                ->required()
                ->maxLength(255),
            TextInput::make('business_name')
                ->label('Business Name')
                ->maxLength(255),
            TextInput::make('email')
                ->label('Email Address')
                ->email()
                ->required()
                ->unique(Customer::class, 'email'),
            TextInput::make('cc_email')
                ->label('CC Email Address')
                ->email()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Phone Number')
                ->tel()
                ->maxLength(20),
            Select::make('customer_type')
                ->label('Customer Type')
                ->options([
                    'retail' => 'Retail',
                    'wholesale' => 'Wholesale',
                ])
                ->default('retail')
                ->required()
                ->reactive(),
            TextInput::make('wholesale_discount_percentage')
                ->label('Wholesale Discount %')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->step(0.01)
                ->suffix('%')
                ->visible(fn (Get $get) => $get('customer_type') === 'wholesale'),
            Textarea::make('address')
                ->label('Address')
                ->rows(3),
            TextInput::make('city')
                ->label('City')
                ->maxLength(255),
            TextInput::make('province')
                ->label('Province')
                ->maxLength(255),
            TextInput::make('postal_code')
                ->label('Postal Code')
                ->maxLength(20),
            TextInput::make('country')
                ->label('Country')
                ->maxLength(255)
                ->default('Canada'),
        ];
    }
}