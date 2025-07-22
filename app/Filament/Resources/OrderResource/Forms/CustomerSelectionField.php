<?php

namespace App\Filament\Resources\OrderResource\Forms;

use App\Models\Customer;
use Filament\Forms;

/**
 * Customer Selection Field for Orders - Handles customer selection with inline creation
 * Extracted from OrderResource lines 73-145
 * Following Filament Resource Architecture Guide patterns
 */
class CustomerSelectionField
{
    /**
     * Get the customer selection field with inline creation form
     * Max 100 lines per method requirement
     */
    public static function make(): Forms\Components\Select
    {
        return Forms\Components\Select::make('customer_id')
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
     * Get formatted customer options for dropdown
     */
    protected static function getCustomerOptions(): \Illuminate\Support\Collection
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
     * Get customer creation form schema
     * TODO: Consider extracting to CustomerActions for complex business logic
     */
    protected static function getCustomerCreationForm(): array
    {
        return [
            Forms\Components\TextInput::make('contact_name')
                ->label('Contact Name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('business_name')
                ->label('Business Name')
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('Email Address')
                ->email()
                ->required()
                ->unique(Customer::class, 'email'),
            Forms\Components\TextInput::make('cc_email')
                ->label('CC Email Address')
                ->email()
                ->maxLength(255),
            Forms\Components\TextInput::make('phone')
                ->label('Phone Number')
                ->tel()
                ->maxLength(20),
            Forms\Components\Select::make('customer_type')
                ->label('Customer Type')
                ->options([
                    'retail' => 'Retail',
                    'wholesale' => 'Wholesale',
                ])
                ->default('retail')
                ->required()
                ->reactive(),
            Forms\Components\TextInput::make('wholesale_discount_percentage')
                ->label('Wholesale Discount %')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->step(0.01)
                ->suffix('%')
                ->visible(fn (Forms\Get $get) => $get('customer_type') === 'wholesale'),
            Forms\Components\Textarea::make('address')
                ->label('Address')
                ->rows(3),
            Forms\Components\TextInput::make('city')
                ->label('City')
                ->maxLength(255),
            Forms\Components\TextInput::make('province')
                ->label('Province')
                ->maxLength(255),
            Forms\Components\TextInput::make('postal_code')
                ->label('Postal Code')
                ->maxLength(20),
            Forms\Components\TextInput::make('country')
                ->label('Country')
                ->maxLength(255)
                ->default('Canada'),
        ];
    }
}