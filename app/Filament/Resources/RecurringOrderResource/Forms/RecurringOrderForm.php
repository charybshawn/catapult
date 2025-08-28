<?php

namespace App\Filament\Resources\RecurringOrderResource\Forms;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use App\Models\CustomerType;
use Filament\Forms\Components\TextInput;
use App\Models\OrderType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use App\Forms\Components\InvoiceOrderItems;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use App\Models\Customer;
use App\Models\Product;
use Filament\Forms;

/**
 * Recurring order form schema for automated agricultural order generation.
 * Provides comprehensive form configuration for setting up recurring delivery schedules,
 * customer management, billing frequencies, and order item specifications tailored
 * for continuous microgreens production and distribution workflows.
 *
 * @business_domain Agricultural recurring orders and automated delivery scheduling
 * @form_architecture Extracted from RecurringOrderResource following Filament guidelines
 * @customer_integration Inline customer creation with business/contact name handling
 * @scheduling_features Recurring frequencies, start delays, harvest/delivery day coordination
 * @billing_automation Dynamic billing frequency based on order type (B2B, retail, farmers market)
 * @order_management Automated order generation with customizable item specifications
 */
class RecurringOrderForm
{
    /**
     * Get the complete recurring order form schema for agricultural delivery automation.
     * Provides comprehensive form sections for customer management, scheduling configuration,
     * billing settings, and order item specifications supporting continuous farm operations.
     *
     * @agricultural_scheduling Harvest and delivery day coordination for production planning
     * @customer_management Integrated customer creation with business context
     * @billing_flexibility Dynamic billing frequencies for different customer types
     * @order_automation Recurring order generation with customizable intervals and delays
     * @return array Complete form schema array for recurring order configuration
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
     * Customer and type section with inline customer creation for agricultural orders.
     * Provides comprehensive customer management including business context, order type
     * configuration, and recurring schedule dates for continuous farm deliveries.
     *
     * @customer_creation Inline customer creation with agricultural business context
     * @order_type_integration Dynamic billing frequency based on selected order type
     * @recurring_dates Start and optional end dates for automated order generation
     * @return Section Customer and type configuration section
     */
    protected static function getCustomerTypeSection(): Section
    {
        return Section::make('Customer & Type')
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
    protected static function getCustomerSelectField(): Select
    {
        return Select::make('customer_id')
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
                    $customerType = CustomerType::where('code', $data['customer_type'])->first();
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
                ->required(),
        ];
    }

    /**
     * Order type field with automatic billing frequency setting
     */
    protected static function getOrderTypeField(): Select
    {
        return Select::make('order_type_id')
            ->label('Order Type')
            ->relationship('orderType', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set) {
                // Auto-set billing frequency based on order type
                $orderType = OrderType::find($state);
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
    protected static function getRecurringStartDateField(): DatePicker
    {
        return DatePicker::make('recurring_start_date')
            ->label('Start Date')
            ->default(now())
            ->required();
    }

    /**
     * Recurring end date field
     */
    protected static function getRecurringEndDateField(): DatePicker
    {
        return DatePicker::make('recurring_end_date')
            ->label('End Date (Optional)')
            ->helperText('Leave blank for indefinite recurring');
    }

    /**
     * Billing & Invoicing section with conditional visibility
     */
    protected static function getBillingInvoicingSection(): Section
    {
        return Section::make('Billing & Invoicing')
            ->schema([
                static::getBillingFrequencyField(),
                static::getRequiresInvoiceField(),
            ])
            ->visible(function ($get) {
                $orderTypeId = $get('order_type_id');
                if (!$orderTypeId) return false;
                $orderType = OrderType::find($orderTypeId);
                return in_array($orderType?->code, ['b2b', 'farmers_market']);
            })
            ->columns(2);
    }

    /**
     * Billing frequency field for B2B orders
     */
    protected static function getBillingFrequencyField(): Select
    {
        return Select::make('billing_frequency')
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
                $orderType = OrderType::find($orderTypeId);
                return $orderType?->code === 'b2b';
            });
    }

    /**
     * Requires invoice toggle
     */
    protected static function getRequiresInvoiceField(): Toggle
    {
        return Toggle::make('requires_invoice')
            ->label('Requires Invoice')
            ->helperText('Uncheck for farmer\'s market orders that don\'t need invoicing')
            ->default(true);
    }

    /**
     * Recurring schedule section for automated agricultural order generation.
     * Configures frequency, intervals, delays, and active status for continuous
     * microgreens delivery schedules supporting farm production workflows.
     *
     * @delivery_frequency Weekly, bi-weekly, or monthly generation for consistent supply
     * @start_delays Configurable delay weeks for production planning alignment
     * @active_control Toggle for pausing/resuming recurring order generation
     * @return Section Recurring schedule configuration section
     */
    protected static function getRecurringScheduleSection(): Section
    {
        return Section::make('Recurring Schedule')
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
    protected static function getRecurringFrequencyField(): Select
    {
        return Select::make('recurring_frequency')
            ->label(function ($get) {
                $orderTypeId = $get('order_type_id');
                if (!$orderTypeId) return 'Generation Frequency';
                $orderType = OrderType::find($orderTypeId);
                return $orderType?->code === 'b2b' ? 'Delivery Frequency' : 'Generation Frequency';
            })
            ->helperText(function ($get) {
                $orderTypeId = $get('order_type_id');
                if (!$orderTypeId) return 'How often to generate new orders';
                $orderType = OrderType::find($orderTypeId);
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
    protected static function getRecurringIntervalField(): TextInput
    {
        return TextInput::make('recurring_interval')
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
    protected static function getStartDelayField(): TextInput
    {
        return TextInput::make('start_delay_weeks')
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
    protected static function getIsRecurringActiveField(): Toggle
    {
        return Toggle::make('is_recurring_active')
            ->label('Active')
            ->helperText('Uncheck to pause recurring order generation')
            ->default(true);
    }

    /**
     * Schedule days section for agricultural harvest and delivery coordination.
     * Defines specific weekdays for harvest and delivery operations to align
     * with farm production schedules and customer delivery requirements.
     *
     * @harvest_scheduling Specific harvest day selection for production planning
     * @delivery_coordination Delivery day alignment with customer availability
     * @agricultural_workflow Ensures proper timing between harvest and delivery
     * @return Section Schedule days configuration section
     */
    protected static function getScheduleDaysSection(): Section
    {
        return Section::make('Schedule Days')
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
    protected static function getHarvestDayField(): Select
    {
        return Select::make('harvest_day')
            ->label('Harvest Day')
            ->options(static::getDayOptions())
            ->default('monday')
            ->required();
    }

    /**
     * Delivery day field
     */
    protected static function getDeliveryDayField(): Select
    {
        return Select::make('delivery_day')
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
    protected static function getOrderItemsSection(): Section
    {
        return Section::make('Order Items')
            ->schema([
                InvoiceOrderItems::make('orderItems')
                    ->label('Items')
                    ->productOptions(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->toArray())
                    ->required(),
            ]);
    }

    /**
     * Additional Information section
     */
    protected static function getAdditionalInformationSection(): Section
    {
        return Section::make('Additional Information')
            ->schema([
                Textarea::make('notes')
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
            Hidden::make('is_recurring')->default(true),
            Hidden::make('status')->default('template'),
        ];
    }
}