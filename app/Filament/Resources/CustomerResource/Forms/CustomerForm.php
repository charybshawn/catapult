<?php

namespace App\Filament\Resources\CustomerResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use App\Models\Customer;
use App\Models\CustomerType;
use Filament\Forms;

/**
 * Customer form schema for agricultural business relationship management.
 * 
 * Provides comprehensive customer onboarding and management interface
 * including business information, wholesale pricing configuration, delivery
 * addressing, and account integration for agricultural product sales.
 * 
 * @filament_form Customer management with agricultural business context
 * @business_context Agricultural customer relationships and pricing
 * @customer_types Retail and wholesale customer configuration
 */
class CustomerForm
{
    /**
     * Get complete customer form schema with agricultural business features.
     * 
     * Returns structured form sections for customer information, wholesale
     * settings, delivery coordination, and account management. Supports
     * agricultural business customer relationship and pricing management.
     * 
     * @return array Complete customer form schema
     * @filament_usage Form schema for CustomerResource
     * @business_logic Agricultural customer management with pricing tiers
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
     * Get customer information section with business relationship details.
     * 
     * Returns form section for basic customer details including business
     * information, contact details, and customer type selection for
     * agricultural business relationship management.
     * 
     * @return Section Customer information form section
     * @business_context Business relationships and contact management
     * @customer_management Agricultural customer onboarding and details
     */
    protected static function getCustomerInformationSection(): Section
    {
        return Section::make('Customer Information')
            ->description('Basic customer details')
            ->schema([
                Select::make('customer_type_id')
                    ->label('Customer Type')
                    ->relationship('customerType', 'name')
                    ->options(CustomerType::options())
                    ->default(function () {
                        return CustomerType::findByCode('retail')?->id;
                    })
                    ->required()
                    ->reactive()
                    ->columnSpan(1),
                TextInput::make('business_name')
                    ->maxLength(255)
                    ->placeholder('ABC Grocery Store')
                    ->columnSpan(1),
                TextInput::make('contact_name')
                    ->label('Contact Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Jane Smith'),
                TextInput::make('email')
                    ->label('Email 1')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('customer@example.com'),
                TextInput::make('cc_email')
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
    protected static function getPhoneField(): TextInput
    {
        return TextInput::make('phone')
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
    protected static function getWholesaleSettingsSection(): Section
    {
        return Section::make('Wholesale Settings')
            ->description('Discount settings for wholesale customers')
            ->schema([
                TextInput::make('wholesale_discount_percentage')
                    ->label('Wholesale Discount %')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->default(0)
                    ->helperText('Default discount percentage for wholesale orders'),
            ])
            ->visible(function (Get $get) {
                $customerTypeId = $get('customer_type_id');
                if (!$customerTypeId) return false;
                $customerType = CustomerType::find($customerTypeId);
                return $customerType?->qualifiesForWholesalePricing() ?? false;
            });
    }

    /**
     * Delivery Address section
     */
    protected static function getDeliveryAddressSection(): Section
    {
        return Section::make('Delivery Address')
            ->description('Where orders will be delivered')
            ->schema([
                TextInput::make('address')
                    ->label('Street Address')
                    ->maxLength(255)
                    ->placeholder('123 Main Street')
                    ->columnSpanFull(),
                TextInput::make('city')
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
    protected static function getProvinceSelect(): Select
    {
        return Select::make('province')
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
    protected static function getPostalCodeField(): TextInput
    {
        return TextInput::make('postal_code')
            ->label('Postal/ZIP Code')
            ->maxLength(20)
            ->placeholder('M5V 3A8')
            ->mask(fn (Get $get) => $get('country') === 'CA' ? 'A9A 9A9' : null);
    }

    /**
     * Country select field
     */
    protected static function getCountrySelect(): Select
    {
        return Select::make('country')
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
    protected static function getLoginAccountSection(): Section
    {
        return Section::make('Login Account')
            ->description('Optional: Link to a user account for online access')
            ->schema([
                Select::make('user_id')
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