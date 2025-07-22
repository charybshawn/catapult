<?php

namespace App\Filament\Resources\RecurringOrderResource\Tables;

use App\Models\Order;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Recurring Order Table - Extracted from RecurringOrderResource
 * Originally lines 272-390 in main resource (columns, filters, query modifications)
 * Organized according to Filament Resource Architecture Guide
 * Max 300 lines as per requirements
 */
class RecurringOrderTable
{
    /**
     * Modify the base query for recurring orders
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'customer',
            'orderType',
            'generatedOrders'
        ]);
    }

    /**
     * Get table columns for recurring orders
     */
    public static function columns(): array
    {
        return [
            static::getTemplateIdColumn(),
            static::getCustomerColumn(),
            static::getOrderTypeColumn(),
            static::getRecurringFrequencyColumn(),
            static::getBillingFrequencyColumn(),
            static::getIsActiveColumn(),
            static::getGeneratedCountColumn(),
            static::getNextGenerationColumn(),
            static::getStartDateColumn(),
            static::getEndDateColumn(),
            static::getCreatedAtColumn(),
        ];
    }

    /**
     * Template ID column
     */
    protected static function getTemplateIdColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('id')
            ->label('Template ID')
            ->sortable();
    }

    /**
     * Customer column with business name and contact name formatting
     */
    protected static function getCustomerColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('customer.contact_name')
            ->label('Customer')
            ->formatStateUsing(function ($state, Order $record) {
                if (!$record->customer) {
                    return '—';
                }
                
                $contactName = $record->customer->contact_name ?: 'No name';
                
                if ($record->customer->business_name) {
                    return $record->customer->business_name . ' (' . $contactName . ')';
                }
                
                return $contactName;
            })
            ->searchable()
            ->sortable();
    }

    /**
     * Order type column with badge colors
     */
    protected static function getOrderTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('order_type_display')
            ->label('Type')
            ->badge()
            ->color(fn (?Order $record): string => match ($record?->orderType?->code) {
                'website_order' => 'success',
                'farmers_market' => 'warning', 
                'b2b' => 'info',
                default => 'gray',
            });
    }

    /**
     * Recurring frequency column
     */
    protected static function getRecurringFrequencyColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('recurring_frequency_display')
            ->label('Delivery Frequency')
            ->badge()
            ->color('primary');
    }

    /**
     * Billing frequency column
     */
    protected static function getBillingFrequencyColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('billing_frequency_display')
            ->label('Billing')
            ->badge()
            ->color(fn (?Order $record): string => match ($record?->billing_frequency) {
                'immediate' => 'success',
                'weekly' => 'info',
                'monthly' => 'warning',
                'quarterly' => 'danger',
                default => 'gray',
            })
            ->toggleable();
    }

    /**
     * Is active status column
     */
    protected static function getIsActiveColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('is_recurring_active')
            ->label('Active')
            ->boolean()
            ->trueIcon('heroicon-o-play')
            ->falseIcon('heroicon-o-pause')
            ->trueColor('success')
            ->falseColor('warning');
    }

    /**
     * Generated orders count column
     */
    protected static function getGeneratedCountColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('generated_orders_count')
            ->label('Generated')
            ->numeric()
            ->tooltip('Number of orders generated from this template');
    }

    /**
     * Next generation date column
     */
    protected static function getNextGenerationColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('next_generation_date')
            ->label('Next Generation')
            ->dateTime()
            ->placeholder('Not scheduled')
            ->sortable();
    }

    /**
     * Start date column
     */
    protected static function getStartDateColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('recurring_start_date')
            ->label('Start Date')
            ->date()
            ->sortable()
            ->toggleable();
    }

    /**
     * End date column
     */
    protected static function getEndDateColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('recurring_end_date')
            ->label('End Date')
            ->date()
            ->placeholder('Indefinite')
            ->sortable()
            ->toggleable();
    }

    /**
     * Created at column
     */
    protected static function getCreatedAtColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Get table filters for recurring orders
     */
    public static function filters(): array
    {
        return [
            static::getOrderTypeFilter(),
            static::getStatusFilter(),
            static::getRecurringFrequencyFilter(),
        ];
    }

    /**
     * Order type filter
     */
    protected static function getOrderTypeFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('order_type_id')
            ->label('Order Type')
            ->relationship('orderType', 'name');
    }

    /**
     * Active status filter
     */
    protected static function getStatusFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('is_recurring_active')
            ->label('Status')
            ->placeholder('All templates')
            ->trueLabel('Active templates')
            ->falseLabel('Paused templates');
    }

    /**
     * Recurring frequency filter
     */
    protected static function getRecurringFrequencyFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('recurring_frequency')
            ->label('Delivery Frequency')
            ->options([
                'weekly' => 'Weekly',
                'biweekly' => 'Bi-weekly',
                'monthly' => 'Monthly',
            ]);
    }
}