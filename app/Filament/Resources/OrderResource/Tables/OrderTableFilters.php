<?php

namespace App\Filament\Resources\OrderResource\Tables;

use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use App\Models\OrderStatus;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Order table filters definitions
 * Extracted from OrderResource.php lines 560-648
 * 
 * This class follows the Filament Resource Architecture Guide by:
 * - Returning pure Filament table filter components
 * - Organizing filter logic into reusable methods
 * - Preserving all existing functionality and query logic
 * - Providing consistent filter options across the application
 */
class OrderTableFilters
{
    /**
     * Get all table filters for the OrderResource
     * 
     * @return array Array of Filament table filters
     */
    public static function make(): array
    {
        return [
            static::getStatusFilter(),
            static::getStageFilter(),
            static::getRequiresCropsFilter(),
            static::getPaymentStatusFilter(),
            static::getOrderSourceFilter(),
            static::getCustomerTypeFilter(),
            static::getHarvestDateFilter(),
        ];
    }

    /**
     * Status filter with searchable dropdown
     */
    protected static function getStatusFilter(): SelectFilter
    {
        return SelectFilter::make('status_id')
            ->label('Status')
            ->options(function () {
                return OrderStatus::getOptionsForDropdown(false, true);
            })
            ->searchable();
    }

    /**
     * Stage filter for high-level order phases
     */
    protected static function getStageFilter(): SelectFilter
    {
        return SelectFilter::make('stage')
            ->label('Stage')
            ->options([
                OrderStatus::STAGE_PRE_PRODUCTION => 'Pre-Production',
                OrderStatus::STAGE_PRODUCTION => 'Production',
                OrderStatus::STAGE_FULFILLMENT => 'Fulfillment',
                OrderStatus::STAGE_FINAL => 'Final',
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (!empty($data['value'])) {
                    return $query->whereHas('status', function ($q) use ($data) {
                        $q->where('stage', $data['value']);
                    });
                }
                return $query;
            });
    }

    /**
     * Ternary filter for orders requiring crop production
     */
    protected static function getRequiresCropsFilter(): TernaryFilter
    {
        return TernaryFilter::make('requires_crops')
            ->label('Requires Crops')
            ->placeholder('All orders')
            ->trueLabel('Orders needing crops')
            ->falseLabel('Orders without crops')
            ->queries(
                true: fn (Builder $query) => $query->whereHas('orderItems.product', function ($q) {
                    $q->where(function ($subQ) {
                        $subQ->whereNotNull('master_seed_catalog_id')
                             ->orWhereNotNull('product_mix_id');
                    });
                }),
                false: fn (Builder $query) => $query->whereDoesntHave('orderItems.product', function ($q) {
                    $q->where(function ($subQ) {
                        $subQ->whereNotNull('master_seed_catalog_id')
                             ->orWhereNotNull('product_mix_id');
                    });
                }),
            );
    }

    /**
     * Payment status ternary filter
     */
    protected static function getPaymentStatusFilter(): TernaryFilter
    {
        return TernaryFilter::make('payment_status')
            ->label('Payment Status')
            ->placeholder('All orders')
            ->trueLabel('Paid orders')
            ->falseLabel('Unpaid orders')
            ->queries(
                true: fn (Builder $query) => $query->whereHas('payments', function ($q) {
                    $q->where('status', 'completed')
                      ->havingRaw('SUM(payments.amount) >= (SELECT SUM(order_items.quantity * order_items.price) FROM order_items WHERE order_items.order_id = orders.id)');
                }),
                false: fn (Builder $query) => $query->where(function ($q) {
                    $q->whereDoesntHave('payments', function ($subQ) {
                        $subQ->where('status', 'completed');
                    })->orWhereHas('payments', function ($subQ) {
                        $subQ->where('status', 'completed')
                             ->havingRaw('SUM(payments.amount) < (SELECT SUM(order_items.quantity * order_items.price) FROM order_items WHERE order_items.order_id = orders.id)');
                    });
                }),
            );
    }

    /**
     * Order source filter (recurring vs manual)
     */
    protected static function getOrderSourceFilter(): TernaryFilter
    {
        return TernaryFilter::make('parent_recurring_order_id')
            ->label('Order Source')
            ->nullable()
            ->placeholder('All orders')
            ->trueLabel('Generated from template')
            ->falseLabel('Manual orders only');
    }

    /**
     * Customer type filter
     */
    protected static function getCustomerTypeFilter(): SelectFilter
    {
        return SelectFilter::make('customer_type')
            ->options([
                'retail' => 'Retail',
                'wholesale' => 'Wholesale',
            ]);
    }

    /**
     * Harvest date range filter
     */
    protected static function getHarvestDateFilter(): Filter
    {
        return Filter::make('harvest_date')
            ->schema([
                DatePicker::make('harvest_from'),
                DatePicker::make('harvest_until'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['harvest_from'],
                        fn (Builder $query, $date): Builder => $query->whereDate('harvest_date', '>=', $date),
                    )
                    ->when(
                        $data['harvest_until'],
                        fn (Builder $query, $date): Builder => $query->whereDate('harvest_date', '<=', $date),
                    );
            });
    }

    /**
     * Get additional delivery date filter (optional)
     * Can be added to the filters array if needed
     */
    protected static function getDeliveryDateFilter(): Filter
    {
        return Filter::make('delivery_date')
            ->schema([
                DatePicker::make('delivery_from')
                    ->label('Delivery From'),
                DatePicker::make('delivery_until')
                    ->label('Delivery Until'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['delivery_from'],
                        fn (Builder $query, $date): Builder => $query->whereDate('delivery_date', '>=', $date),
                    )
                    ->when(
                        $data['delivery_until'],
                        fn (Builder $query, $date): Builder => $query->whereDate('delivery_date', '<=', $date),
                    );
            });
    }

    /**
     * Get order value range filter (optional)
     * Can be added to the filters array if needed
     */
    protected static function getOrderValueFilter(): Filter
    {
        return Filter::make('order_value')
            ->schema([
                TextInput::make('min_value')
                    ->label('Minimum Value')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('max_value')
                    ->label('Maximum Value')
                    ->numeric()
                    ->prefix('$'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when($data['min_value'], function (Builder $query, $value) {
                        return $query->whereHas('orderItems', function ($q) use ($value) {
                            $q->havingRaw('SUM(quantity * price) >= ?', [$value]);
                        });
                    })
                    ->when($data['max_value'], function (Builder $query, $value) {
                        return $query->whereHas('orderItems', function ($q) use ($value) {
                            $q->havingRaw('SUM(quantity * price) <= ?', [$value]);
                        });
                    });
            });
    }

    /**
     * Get customer filter (optional)
     * Can be added to the filters array if needed
     */
    protected static function getCustomerFilter(): SelectFilter
    {
        return SelectFilter::make('customer_id')
            ->label('Customer')
            ->relationship('customer', 'contact_name')
            ->searchable()
            ->preload()
            ->getOptionLabelFromRecordUsing(fn ($record) => 
                $record->business_name 
                    ? $record->business_name . ' (' . $record->contact_name . ')'
                    : $record->contact_name
            );
    }

    /**
     * Get order type filter (optional)
     * Can be added to the filters array if needed
     */
    protected static function getOrderTypeFilter(): SelectFilter
    {
        return SelectFilter::make('order_type_id')
            ->label('Order Type')
            ->relationship('orderType', 'name')
            ->searchable()
            ->preload();
    }

    /**
     * Get invoiced status filter (optional)
     * Can be added to the filters array if needed
     */
    protected static function getInvoicedStatusFilter(): TernaryFilter
    {
        return TernaryFilter::make('invoiced')
            ->label('Invoiced Status')
            ->placeholder('All orders')
            ->trueLabel('Has invoice')
            ->falseLabel('No invoice')
            ->queries(
                true: fn (Builder $query) => $query->whereNotNull('invoice_id'),
                false: fn (Builder $query) => $query->whereNull('invoice_id'),
            );
    }

    /**
     * Get common filter options used across multiple components
     * 
     * @return array
     */
    public static function getFilterOptions(): array
    {
        return [
            'stages' => [
                OrderStatus::STAGE_PRE_PRODUCTION => 'Pre-Production',
                OrderStatus::STAGE_PRODUCTION => 'Production',
                OrderStatus::STAGE_FULFILLMENT => 'Fulfillment',
                OrderStatus::STAGE_FINAL => 'Final',
            ],
            'customer_types' => [
                'retail' => 'Retail',
                'wholesale' => 'Wholesale',
            ],
            'payment_statuses' => [
                'paid' => 'Paid',
                'unpaid' => 'Unpaid',
                'partial' => 'Partial',
                'refunded' => 'Refunded',
            ],
            'order_sources' => [
                'manual' => 'Manual Orders',
                'recurring' => 'From Templates',
                'website' => 'Website Orders',
                'admin' => 'Admin Created',
            ],
        ];
    }

    /**
     * Get query builders for complex filters
     * Provides reusable query logic
     * 
     * @return array
     */
    public static function getQueryBuilders(): array
    {
        return [
            'requires_crops' => function (Builder $query) {
                return $query->whereHas('orderItems.product', function ($q) {
                    $q->where(function ($subQ) {
                        $subQ->whereNotNull('master_seed_catalog_id')
                             ->orWhereNotNull('product_mix_id');
                    });
                });
            },
            'no_crops_needed' => function (Builder $query) {
                return $query->whereDoesntHave('orderItems.product', function ($q) {
                    $q->where(function ($subQ) {
                        $subQ->whereNotNull('master_seed_catalog_id')
                             ->orWhereNotNull('product_mix_id');
                    });
                });
            },
            'paid_orders' => function (Builder $query) {
                return $query->whereHas('payments', function ($q) {
                    $q->where('status', 'completed')
                      ->havingRaw('SUM(payments.amount) >= (SELECT SUM(order_items.quantity * order_items.price) FROM order_items WHERE order_items.order_id = orders.id)');
                });
            },
            'unpaid_orders' => function (Builder $query) {
                return $query->where(function ($q) {
                    $q->whereDoesntHave('payments', function ($subQ) {
                        $subQ->where('status', 'completed');
                    })->orWhereHas('payments', function ($subQ) {
                        $subQ->where('status', 'completed')
                             ->havingRaw('SUM(payments.amount) < (SELECT SUM(order_items.quantity * order_items.price) FROM order_items WHERE order_items.order_id = orders.id)');
                    });
                });
            },
        ];
    }
}