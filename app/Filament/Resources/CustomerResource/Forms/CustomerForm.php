<?php

namespace App\Filament\Resources\CustomerResource\Forms;

use App\Models\Customer;
use App\Models\CustomerType;
use Filament\Forms;

class CustomerForm
{
    /**
     * Get the complete form schema for CustomerResource
     */
    public static function schema(): array
    {
        return [
            static::getCustomerInformationSection(),
            static::getWholesaleSettingsSection(),
            static::getDeliveryAddressSection(),
            static::getLoginAccountSection(),
        ];
    }

    /**
     * Customer Information section
     */
    protected static function getCustomerInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Customer Information')
            ->description('Basic customer details')
            ->schema([
                Forms\Components\Select::make('customer_type_id')
                    ->label('Customer Type')
                    ->relationship('customerType', 'name')
                    ->options(CustomerType::options())
                    ->default(function () {
                        return CustomerType::findByCode('retail')?->id;
                    })
                    ->required()
                    ->reactive()
                    ->columnSpan(1),
                Forms\Components\TextInput::make('business_name')
                    ->maxLength(255)
                    ->placeholder('ABC Grocery Store')
                    ->columnSpan(1),
                Forms\Components\TextInput::make('contact_name')
                    ->label('Contact Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Jane Smith'),
                Forms\Components\TextInput::make('email')
                    ->label('Email 1')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('customer@example.com'),
                Forms\Components\TextInput::make('cc_email')
                    ->label('CC Email')
                    ->email()
                    ->maxLength(255)
                    ->placeholder('secondary@example.com'),
                static::getPhoneField(),
            ])->columns(2);
    }

    /**
     * Phone field with formatting logic
     */
    protected static function getPhoneField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('phone')
            ->tel()
            ->maxLength(20)
            ->placeholder('(416) 555-1234')
            ->mask('(999) 999-9999')
            ->afterStateUpdated(function ($state, $set) {
                // Clean and format phone number
                if ($state) {
                    $cleaned = preg_replace('/[^0-9]/', '', $state);
                    if (strlen($cleaned) === 10) {
                        $formatted = sprintf('(%s) %s-%s', 
                            substr($cleaned, 0, 3),
                            substr($cleaned, 3, 3),
                            substr($cleaned, 6)
                        );
                        $set('phone', $formatted);
                    }
                }
            });
    }

    /**
     * Wholesale Settings section
     */
    protected static function getWholesaleSettingsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Wholesale Settings')
            ->description('Discount settings for wholesale customers')
            ->schema([
                Forms\Components\TextInput::make('wholesale_discount_percentage')
                    ->label('Wholesale Discount %')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->default(0)
                    ->helperText('Default discount percentage for wholesale orders'),
            ])
            ->visible(function (Forms\Get $get) {
                $customerTypeId = $get('customer_type_id');
                if (!$customerTypeId) return false;
                $customerType = CustomerType::find($customerTypeId);
                return $customerType?->qualifiesForWholesalePricing() ?? false;
            });
    }

    /**
     * Delivery Address section
     */
    protected static function getDeliveryAddressSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Delivery Address')
            ->description('Where orders will be delivered')
            ->schema([
                Forms\Components\TextInput::make('address')
                    ->label('Street Address')
                    ->maxLength(255)
                    ->placeholder('123 Main Street')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('city')
                    ->maxLength(100)
                    ->placeholder('Toronto'),
                static::getProvinceSelect(),
                static::getPostalCodeField(),
                static::getCountrySelect(),
            ])->columns(3);
    }

    /**
     * Province/State select with comprehensive options
     */
    protected static function getProvinceSelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('province')
            ->label('Province/State')
            ->searchable()
            ->options([
                // Canadian Provinces
                'AB' => 'Alberta',
                'BC' => 'British Columbia',
                'MB' => 'Manitoba',
                'NB' => 'New Brunswick',
                'NL' => 'Newfoundland and Labrador',
                'NS' => 'Nova Scotia',
                'NT' => 'Northwest Territories',
                'NU' => 'Nunavut',
                'ON' => 'Ontario',
                'PE' => 'Prince Edward Island',
                'QC' => 'Quebec',
                'SK' => 'Saskatchewan',
                'YT' => 'Yukon',
                // US States (abbreviated list - can be expanded)
                'AL' => 'Alabama',
                'AK' => 'Alaska',
                'AZ' => 'Arizona',
                'AR' => 'Arkansas',
                'CA' => 'California',
                'CO' => 'Colorado',
                'CT' => 'Connecticut',
                'DE' => 'Delaware',
                'FL' => 'Florida',
                'GA' => 'Georgia',
                'HI' => 'Hawaii',
                'ID' => 'Idaho',
                'IL' => 'Illinois',
                'IN' => 'Indiana',
                'IA' => 'Iowa',
                'KS' => 'Kansas',
                'KY' => 'Kentucky',
                'LA' => 'Louisiana',
                'ME' => 'Maine',
                'MD' => 'Maryland',
                'MA' => 'Massachusetts',
                'MI' => 'Michigan',
                'MN' => 'Minnesota',
                'MS' => 'Mississippi',
                'MO' => 'Missouri',
                'MT' => 'Montana',
                'NE' => 'Nebraska',
                'NV' => 'Nevada',
                'NH' => 'New Hampshire',
                'NJ' => 'New Jersey',
                'NM' => 'New Mexico',
                'NY' => 'New York',
                'NC' => 'North Carolina',
                'ND' => 'North Dakota',
                'OH' => 'Ohio',
                'OK' => 'Oklahoma',
                'OR' => 'Oregon',
                'PA' => 'Pennsylvania',
                'RI' => 'Rhode Island',
                'SC' => 'South Carolina',
                'SD' => 'South Dakota',
                'TN' => 'Tennessee',
                'TX' => 'Texas',
                'UT' => 'Utah',
                'VT' => 'Vermont',
                'VA' => 'Virginia',
                'WA' => 'Washington',
                'WV' => 'West Virginia',
                'WI' => 'Wisconsin',
                'WY' => 'Wyoming',
            ])
            ->default('BC');
    }

    /**
     * Postal/ZIP code field with conditional masking
     */
    protected static function getPostalCodeField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('postal_code')
            ->label('Postal/ZIP Code')
            ->maxLength(20)
            ->placeholder('M5V 3A8')
            ->mask(fn (Forms\Get $get) => $get('country') === 'CA' ? 'A9A 9A9' : null);
    }

    /**
     * Country select field
     */
    protected static function getCountrySelect(): Forms\Components\Select
    {
        return Forms\Components\Select::make('country')
            ->options([
                'CA' => 'Canada',
                'US' => 'United States',
                'MX' => 'Mexico',
                // Add more countries as needed
            ])
            ->default('CA')
            ->required()
            ->reactive();
    }

    /**
     * Login Account section
     */
    protected static function getLoginAccountSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Login Account')
            ->description('Optional: Link to a user account for online access')
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Linked User Account')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->placeholder('No login account')
                    ->helperText('Link to existing user or use action to create new')
                    ->columnSpanFull(),
            ])
            ->collapsible()
            ->collapsed(fn (?Customer $record) => $record === null || !$record->hasUserAccount());
    }
}