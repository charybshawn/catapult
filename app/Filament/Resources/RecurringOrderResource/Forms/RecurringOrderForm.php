<?php

namespace App\Filament\Resources\RecurringOrderResource\Forms;

use App\Models\Customer;
use App\Models\Product;
use Filament\Forms;

/**
 * Recurring Order Form - Extracted from RecurringOrderResource
 * Originally lines 40-270 in main resource (230+ lines)
 * Organized according to Filament Resource Architecture Guide
 * Max 300 lines as per requirements
 */
class RecurringOrderForm
{
    /**
     * Get the complete recurring order form schema
     */
    public static function schema(): array
    {
        return [
            static::getCustomerTypeSection(),
            static::getBillingInvoicingSection(),
            static::getRecurringScheduleSection(),
            static::getScheduleDaysSection(),
            static::getOrderItemsSection(),
            static::getAdditionalInformationSection(),
            ...static::getHiddenFields(),
        ];
    }

    /**
     * Customer & Type section with customer creation capability
     */
    protected static function getCustomerTypeSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Customer & Type')
            ->schema([
                static::getCustomerSelectField(),
                static::getOrderTypeField(),
                static::getRecurringStartDateField(),
                static::getRecurringEndDateField(),
            ])
            ->columns(2);
    }

    /**
     * Customer selection field with inline creation
     */
    protected static function getCustomerSelectField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('customer_id')
            ->label('Customer')
            ->options(function () {
                return Customer::all()
                    ->mapWithKeys(function ($customer) {
                        $display = $customer->business_name 
                            ? $customer->business_name . ' (' . $customer->contact_name . ')'
                            : $customer->contact_name;
                        return [$customer->id => $display];
                    });
            })
            ->searchable()
            ->preload()
            ->required()
            ->createOptionForm(static::getCustomerCreationForm())
            ->createOptionUsing(function (array $data): int {
                // Map customer_type string to customer_type_id
                if (isset($data['customer_type'])) {
                    $customerType = \App\Models\CustomerType::where('code', $data['customer_type'])->first();
                    if ($customerType) {
                        $data['customer_type_id'] = $customerType->id;
                    }
                    unset($data['customer_type']); // Remove the string field
                }
                
                return Customer::create($data)->getKey();
            });
    }

    /**
     * Customer creation form fields
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
                ->required(),
        ];
    }

    /**
     * Order type field with automatic billing frequency setting
     */
    protected static function getOrderTypeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('order_type_id')
            ->label('Order Type')
            ->relationship('orderType', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set) {
                // Auto-set billing frequency based on order type
                $orderType = \App\Models\OrderType::find($state);
                if ($orderType) {
                    if ($orderType->code === 'farmers_market') {
                        $set('billing_frequency', 'immediate');
                        $set('requires_invoice', false);
                    } elseif ($orderType->code === 'website_order') {
                        $set('billing_frequency', 'immediate');
                        $set('requires_invoice', true);
                    } elseif ($orderType->code === 'b2b') {
                        $set('billing_frequency', 'monthly');
                        $set('requires_invoice', true);
                    }
                }
            });
    }

    /**
     * Recurring start date field
     */
    protected static function getRecurringStartDateField(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('recurring_start_date')
            ->label('Start Date')
            ->default(now())
            ->required();
    }

    /**
     * Recurring end date field
     */
    protected static function getRecurringEndDateField(): Forms\Components\DatePicker
    {
        return Forms\Components\DatePicker::make('recurring_end_date')
            ->label('End Date (Optional)')
            ->helperText('Leave blank for indefinite recurring');
    }

    /**
     * Billing & Invoicing section with conditional visibility
     */
    protected static function getBillingInvoicingSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Billing & Invoicing')
            ->schema([
                static::getBillingFrequencyField(),
                static::getRequiresInvoiceField(),
            ])
            ->visible(function ($get) {
                $orderTypeId = $get('order_type_id');
                if (!$orderTypeId) return false;
                $orderType = \App\Models\OrderType::find($orderTypeId);
                return in_array($orderType?->code, ['b2b', 'farmers_market']);
            })
            ->columns(2);
    }

    /**
     * Billing frequency field for B2B orders
     */
    protected static function getBillingFrequencyField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('billing_frequency')
            ->label('Billing Frequency')
            ->options([
                'immediate' => 'Immediate',
                'weekly' => 'Weekly',
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
            ])
            ->default('immediate')
            ->required()
            ->visible(function ($get) {
                $orderTypeId = $get('order_type_id');
                if (!$orderTypeId) return false;
                $orderType = \App\Models\OrderType::find($orderTypeId);
                return $orderType?->code === 'b2b';
            });
    }

    /**
     * Requires invoice toggle
     */
    protected static function getRequiresInvoiceField(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('requires_invoice')
            ->label('Requires Invoice')
            ->helperText('Uncheck for farmer\'s market orders that don\'t need invoicing')
            ->default(true);
    }

    /**
     * Recurring Schedule section
     */
    protected static function getRecurringScheduleSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Recurring Schedule')
            ->schema([
                static::getRecurringFrequencyField(),
                static::getRecurringIntervalField(),
                static::getStartDelayField(),
                static::getIsRecurringActiveField(),
            ])
            ->columns(2);
    }

    /**
     * Recurring frequency field with dynamic label
     */
    protected static function getRecurringFrequencyField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('recurring_frequency')
            ->label(function ($get) {
                $orderTypeId = $get('order_type_id');
                if (!$orderTypeId) return 'Generation Frequency';
                $orderType = \App\Models\OrderType::find($orderTypeId);
                return $orderType?->code === 'b2b' ? 'Delivery Frequency' : 'Generation Frequency';
            })
            ->helperText(function ($get) {
                $orderTypeId = $get('order_type_id');
                if (!$orderTypeId) return 'How often to generate new orders';
                $orderType = \App\Models\OrderType::find($orderTypeId);
                return $orderType?->code === 'b2b'
                    ? 'How often to create new delivery orders (independent of billing frequency)'
                    : 'How often to generate new orders';
            })
            ->options([
                'weekly' => 'Weekly',
                'biweekly' => 'Bi-weekly',
                'monthly' => 'Monthly',
            ])
            ->default('weekly')
            ->reactive()
            ->required();
    }

    /**
     * Recurring interval field for bi-weekly frequency
     */
    protected static function getRecurringIntervalField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('recurring_interval')
            ->label('Interval (weeks)')
            ->helperText('For bi-weekly: enter 2 for every 2 weeks')
            ->numeric()
            ->default(2)
            ->minValue(1)
            ->maxValue(12)
            ->visible(fn ($get) => $get('recurring_frequency') === 'biweekly');
    }

    /**
     * Start delay field
     */
    protected static function getStartDelayField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('start_delay_weeks')
            ->label('Start Delay (weeks)')
            ->helperText('Number of weeks to wait before generating the first order')
            ->numeric()
            ->default(2)
            ->minValue(0)
            ->maxValue(12)
            ->suffix('weeks');
    }

    /**
     * Is recurring active toggle
     */
    protected static function getIsRecurringActiveField(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('is_recurring_active')
            ->label('Active')
            ->helperText('Uncheck to pause recurring order generation')
            ->default(true);
    }

    /**
     * Schedule Days section
     */
    protected static function getScheduleDaysSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Schedule Days')
            ->schema([
                static::getHarvestDayField(),
                static::getDeliveryDayField(),
            ])
            ->description('Set the day of week for harvest and delivery')
            ->columns(2);
    }

    /**
     * Harvest day field
     */
    protected static function getHarvestDayField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('harvest_day')
            ->label('Harvest Day')
            ->options(static::getDayOptions())
            ->default('monday')
            ->required();
    }

    /**
     * Delivery day field
     */
    protected static function getDeliveryDayField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('delivery_day')
            ->label('Delivery Day')
            ->options(static::getDayOptions())
            ->default('tuesday')
            ->required();
    }

    /**
     * Get day options for select fields
     */
    protected static function getDayOptions(): array
    {
        return [
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday', 
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
        ];
    }

    /**
     * Order Items section using custom component
     */
    protected static function getOrderItemsSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Order Items')
            ->schema([
                \App\Forms\Components\InvoiceOrderItems::make('orderItems')
                    ->label('Items')
                    ->productOptions(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->required(),
            ]);
    }

    /**
     * Additional Information section
     */
    protected static function getAdditionalInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Additional Information')
            ->schema([
                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->collapsible();
    }

    /**
     * Hidden fields to set defaults for recurring orders
     */
    protected static function getHiddenFields(): array
    {
        return [
            Forms\Components\Hidden::make('is_recurring')->default(true),
            Forms\Components\Hidden::make('status')->default('template'),
        ];
    }
}