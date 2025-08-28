<?php

namespace App\Filament\Resources\OrderResource\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Closure;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\OrderResource\Actions\OrderActions;
use App\Models\Order;
use App\Models\OrderStatus;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class OrderTable
{
    /**
     * Configure the table with all columns, filters, and actions
     */
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['customer.customerType', 'orderItems', 'invoice', 'orderType', 'status']))
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->columns(static::getColumns())
            ->defaultSort('created_at', 'desc')
            ->filters(static::getFilters())
            ->recordActions(static::getActions())
            ->toolbarActions(static::getBulkActions());
    }

    /**
     * Get all table columns
     */
    public static function getColumns(): array
    {
        return [
            static::getOrderIdColumn(),
            static::getCustomerColumn(),
            static::getOrderTypeColumn(),
            static::getStatusSelectColumn(),
            static::getStatusBadgeColumn(),
            static::getRequiresCropsColumn(),
            static::getPaymentStatusColumn(),
            static::getDaysUntilDeliveryColumn(),
            static::getParentTemplateColumn(),
            static::getTotalAmountColumn(),
            static::getHarvestDateColumn(),
            static::getDeliveryDateColumn(),
            static::getCreatedAtColumn(),
        ];
    }

    protected static function getOrderIdColumn(): TextColumn
    {
        return TextColumn::make('id')
            ->label('Order ID')
            ->sortable();
    }

    protected static function getCustomerColumn(): TextColumn
    {
        return TextColumn::make('customer.contact_name')
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
            ->searchable(query: function ($query, string $search) {
                return $query->whereHas('customer', function ($q) use ($search) {
                    $q->where('contact_name', 'like', "%{$search}%")
                      ->orWhere('business_name', 'like', "%{$search}%");
                });
            });
    }

    protected static function getOrderTypeColumn(): TextColumn
    {
        return TextColumn::make('order_type_display')
            ->label('Type')
            ->badge()
            ->color(fn (Order $record): string => match ($record->orderType?->code) {
                'website' => 'success',
                'farmers_market' => 'warning',
                'b2b' => 'info',
                default => 'gray',
            });
    }

    protected static function getStatusSelectColumn(): SelectColumn
    {
        return SelectColumn::make('status_id')
            ->label('Status')
            ->options(function () {
                return OrderStatus::getOptionsForDropdown(false, false);
            })
            ->selectablePlaceholder(false)
            ->disabled(fn ($record): bool => 
                $record instanceof Order && ($record->status?->code === 'template' || $record->status?->is_final)
            )
            ->rules([
                fn ($record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                    if (!($record instanceof Order) || !$record->status) {
                        return;
                    }
                    
                    $newStatus = OrderStatus::find($value);
                    if (!$newStatus) {
                        $fail('Invalid status selected.');
                        return;
                    }
                    
                    if (!OrderStatus::isValidTransition($record->status->code, $newStatus->code)) {
                        $fail("Cannot transition from {$record->status->name} to {$newStatus->name}.");
                    }
                },
            ])
            ->afterStateUpdated(function ($record, $state) {
                if (!($record instanceof Order)) {
                    return;
                }
                $oldStatus = $record->status;
                $newStatus = OrderStatus::find($state);
                
                if (!$newStatus) {
                    return;
                }
                
                activity()
                    ->performedOn($record)
                    ->withProperties([
                        'old_status' => $oldStatus?->name ?? 'Unknown',
                        'old_status_code' => $oldStatus?->code ?? 'unknown',
                        'old_stage' => $oldStatus?->stage ?? 'unknown',
                        'new_status' => $newStatus->name,
                        'new_status_code' => $newStatus->code,
                        'new_stage' => $newStatus->stage,
                        'changed_by' => auth()->user()->name ?? 'System'
                    ])
                    ->log('Unified order status changed');
                    
                Notification::make()
                    ->title('Order Status Updated')
                    ->body("Order #{$record->id} status changed to: {$newStatus->name} ({$newStatus->stage_display})")
                    ->success()
                    ->send();
            });
    }

    protected static function getStatusBadgeColumn(): TextColumn
    {
        return TextColumn::make('status.name')
            ->label('Status')
            ->badge()
            ->color(fn (Order $record): string => $record->status?->badge_color ?? 'gray')
            ->formatStateUsing(fn (string $state, Order $record): string => 
                $state . ' (' . $record->status?->stage_display . ')'
            )
            ->visible(false); // Hidden by default, can be toggled
    }

    protected static function getRequiresCropsColumn(): IconColumn
    {
        return IconColumn::make('requiresCrops')
            ->label('Needs Growing')
            ->boolean()
            ->getStateUsing(fn (Order $record) => $record->requiresCropProduction())
            ->trueIcon('heroicon-o-sun')
            ->falseIcon('heroicon-o-x-mark')
            ->trueColor('success')
            ->falseColor('gray')
            ->tooltip(fn (Order $record) => $record->requiresCropProduction() ? 'This order requires crop production' : 'No crops needed');
    }

    protected static function getPaymentStatusColumn(): TextColumn
    {
        return TextColumn::make('paymentStatus')
            ->label('Payment')
            ->badge()
            ->getStateUsing(fn (Order $record) => $record->isPaid() ? 'Paid' : 'Unpaid')
            ->color(fn (string $state): string => match ($state) {
                'Paid' => 'success',
                'Unpaid' => 'danger',
                default => 'gray',
            })
            ->icon(fn (string $state): string => match ($state) {
                'Paid' => 'heroicon-o-check-circle',
                'Unpaid' => 'heroicon-o-x-circle',
                default => 'heroicon-o-question-mark-circle',
            });
    }

    protected static function getDaysUntilDeliveryColumn(): TextColumn
    {
        return TextColumn::make('daysUntilDelivery')
            ->label('Delivery In')
            ->getStateUsing(function (Order $record) {
                if (!$record->delivery_date) {
                    return null;
                }
                $days = now()->diffInDays($record->delivery_date, false);
                if ($days < 0) {
                    return 'Overdue';
                } elseif ($days == 0) {
                    return 'Today';
                } elseif ($days == 1) {
                    return 'Tomorrow';
                } else {
                    return $days . ' days';
                }
            })
            ->badge()
            ->color(function ($state): string {
                if ($state === 'Overdue') {
                    return 'danger';
                } elseif ($state === 'Today' || $state === 'Tomorrow') {
                    return 'warning';
                } elseif ($state && str_contains($state, 'days')) {
                    $days = (int) $state;
                    if ($days <= 3) {
                        return 'warning';
                    } elseif ($days <= 7) {
                        return 'info';
                    }
                }
                return 'gray';
            })
            ->sortable(query: function (Builder $query, string $direction): Builder {
                return $query->orderBy('delivery_date', $direction);
            });
    }

    protected static function getParentTemplateColumn(): TextColumn
    {
        return TextColumn::make('parent_template')
            ->label('Template')
            ->getStateUsing(fn (Order $record) => $record->parent_recurring_order_id ? "Template #{$record->parent_recurring_order_id}" : null)
            ->placeholder('Regular Order')
            ->badge()
            ->color('info')
            ->toggleable(isToggledHiddenByDefault: true);
    }

    protected static function getTotalAmountColumn(): TextColumn
    {
        return TextColumn::make('totalAmount')
            ->label('Total')
            ->money('USD')
            ->getStateUsing(fn (Order $record) => $record->totalAmount());
    }

    protected static function getHarvestDateColumn(): TextColumn
    {
        return TextColumn::make('harvest_date')
            ->dateTime()
            ->sortable();
    }

    protected static function getDeliveryDateColumn(): TextColumn
    {
        return TextColumn::make('delivery_date')
            ->dateTime()
            ->sortable();
    }

    protected static function getCreatedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Get all table filters
     */
    public static function getFilters(): array
    {
        return [
            static::getStatusFilter(),
            static::getStageFilter(),
            static::getRequiresCropsFilter(),
            static::getPaymentStatusFilter(),
            static::getParentRecurringFilter(),
            static::getCustomerTypeFilter(),
            static::getHarvestDateFilter(),
        ];
    }

    protected static function getStatusFilter(): SelectFilter
    {
        return SelectFilter::make('status_id')
            ->label('Status')
            ->options(function () {
                return OrderStatus::getOptionsForDropdown(false, true);
            })
            ->searchable();
    }

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

    protected static function getParentRecurringFilter(): TernaryFilter
    {
        return TernaryFilter::make('parent_recurring_order_id')
            ->label('Order Source')
            ->nullable()
            ->placeholder('All orders')
            ->trueLabel('Generated from template')
            ->falseLabel('Manual orders only');
    }

    protected static function getCustomerTypeFilter(): SelectFilter
    {
        return SelectFilter::make('customer_type')
            ->options([
                'retail' => 'Retail',
                'wholesale' => 'Wholesale',
            ]);
    }

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
     * Get table actions
     */
    public static function getActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->tooltip('View order details'),
                EditAction::make()
                    ->tooltip('Edit order'),
                ...OrderActions::getRowActions(),
                DeleteAction::make()
                    ->tooltip('Delete order'),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Get bulk actions
     */
    public static function getBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                ...OrderActions::getBulkActions(),
                DeleteBulkAction::make(),
            ]),
        ];
    }
}