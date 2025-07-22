<?php

namespace App\Filament\Resources\OrderResource\Forms;

use App\Models\OrderStatus;
use App\Models\OrderType;
use Filament\Forms;

/**
 * Main Order Form - Orchestrates all order form sections
 * Extracted from OrderResource form method (lines 52-368)
 * Following Filament Resource Architecture Guide patterns
 * Max 300 lines as per requirements
 */
class OrderForm
{
    /**
     * Get the complete order form schema
     */
    public static function schema(): array
    {
        return [
            static::getOrderTypeSection(),
            static::getOrderInformationSection(),
            RecurringSettingsSection::make(),
            static::getBillingSection(),
            OrderItemsSection::make(),
            static::getAdditionalInformationSection(),
        ];
    }

    /**
     * Get order type section (recurring toggle)
     */
    protected static function getOrderTypeSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Order Type')
            ->schema([
                Forms\Components\Toggle::make('is_recurring')
                    ->label('Make this a recurring order')
                    ->helperText('When enabled, this order will generate new orders automatically')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state) {
                            $set('recurring_frequency', null);
                            $set('recurring_start_date', null);
                            $set('recurring_end_date', null);
                        }
                    }),
            ]);
    }

    /**
     * Get order information section
     */
    protected static function getOrderInformationSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Order Information')
            ->schema([
                CustomerSelectionField::make(),
                DeliveryDateField::make(),
                DeliveryDateField::getHarvestDateField(),
                static::getOrderTypeField(),
                static::getStatusField(),
            ])
            ->columns(2);
    }

    /**
     * Get order type field with status auto-setting
     * TODO: Consider extracting to OrderTypeSelectionAction for complex business logic
     */
    protected static function getOrderTypeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('order_type_id')
            ->label('Order Type')
            ->relationship('orderType', 'name')
            ->searchable()
            ->preload()
            ->required()
            ->default(function () {
                // Set default to 'website' order type
                $websiteType = OrderType::where('code', 'website')->first();
                return $websiteType?->id;
            })
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, $record) {
                // Auto-set status based on order type when creating
                if (!$record && $state) {
                    $orderType = OrderType::find($state);
                    if ($orderType) {
                        // Set appropriate default status based on order type
                        $defaultStatusCode = match($orderType->code) {
                            'website' => 'pending',
                            'farmers_market' => 'confirmed',
                            'b2b' => 'draft',
                            default => 'pending'
                        };
                        $defaultStatus = OrderStatus::where('code', $defaultStatusCode)->first();
                        if ($defaultStatus) {
                            $set('status_id', $defaultStatus->id);
                        }
                    }
                }
            });
    }

    /**
     * Get status field with dynamic helper text
     * TODO: Consider extracting to OrderStatusSelectionAction for complex business logic
     */
    protected static function getStatusField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('status_id')
            ->label('Order Status')
            ->options(function () {
                return OrderStatus::getOptionsForDropdown(false, true);
            })
            ->required()
            ->reactive()
            ->default(function () {
                $defaultStatus = OrderStatus::getDefaultStatus();
                return $defaultStatus?->id;
            })
            ->helperText(function ($state) {
                return static::getStatusHelperText($state);
            })
            ->disabled(fn ($record) => $record && $record->status && ($record->status->code === 'template' || $record->status->is_final));
    }

    /**
     * Get dynamic helper text for status field
     */
    protected static function getStatusHelperText($state): ?string
    {
        if (!$state) {
            return 'Select a status for this order';
        }
        
        $status = OrderStatus::find($state);
        if (!$status) {
            return null;
        }
        
        $help = "Stage: {$status->stage_display}";
        if ($status->description) {
            $help .= " - {$status->description}";
        }
        if ($status->requires_crops) {
            $help .= " (Requires crop production)";
        }
        if ($status->is_final) {
            $help .= " (Final status - cannot be changed)";
        }
        if (!$status->allows_modifications) {
            $help .= " (Order locked for modifications)";
        }
        
        return $help;
    }

    /**
     * Get billing & invoicing section
     */
    protected static function getBillingSection(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Billing & Invoicing')
            ->schema([
                Forms\Components\Select::make('billing_frequency')
                    ->label('Billing Frequency')
                    ->options([
                        'immediate' => 'Immediate',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                    ])
                    ->default('immediate')
                    ->required(),
                
                Forms\Components\Toggle::make('requires_invoice')
                    ->label('Requires Invoice')
                    ->default(true),
            ])
            ->columns(2)
            ->collapsible()
            ->collapsed();
    }

    /**
     * Get additional information section
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
}