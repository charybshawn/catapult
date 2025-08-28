<?php

namespace App\Filament\Resources\OrderResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Closure;
use Filament\Tables\Columns\IconColumn;
use App\Models\Order;
use App\Models\OrderStatus;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

/**
 * Order table columns definitions
 * Extracted from OrderResource.php lines 379-558
 * 
 * This class follows the Filament Resource Architecture Guide by:
 * - Returning pure Filament table column components
 * - Organizing column logic into reusable methods
 * - Preserving all existing functionality and formatting
 * - Keeping business logic separate from presentation
 */
class OrderTableColumns
{
    /**
     * Get all table columns for the OrderResource
     * 
     * @return array Array of Filament table columns
     */
    public static function make(): array
    {
        return [
            static::getOrderIdColumn(),
            static::getCustomerColumn(),
            static::getOrderTypeColumn(),
            static::getStatusSelectColumn(),
            static::getStatusDisplayColumn(),
            static::getRequiresCropsColumn(),
            static::getPaymentStatusColumn(),
            static::getDeliveryTimelineColumn(),
            static::getParentTemplateColumn(),
            static::getTotalAmountColumn(),
            static::getHarvestDateColumn(),
            static::getDeliveryDateColumn(),
            static::getCreatedAtColumn(),
        ];
    }

    /**
     * Order ID column with sorting
     */
    protected static function getOrderIdColumn(): TextColumn
    {
        return TextColumn::make('id')
            ->label('Order ID')
            ->sortable();
    }

    /**
     * Customer column with business name and contact name formatting
     */
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

    /**
     * Order type column with badge styling
     */
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

    /**
     * Editable status select column with validation and notifications
     */
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
                
                // Log the status change
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

    /**
     * Read-only status display column with badge and stage info
     */
    protected static function getStatusDisplayColumn(): TextColumn
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

    /**
     * Icon column showing if order requires crop production
     */
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

    /**
     * Payment status badge column
     */
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

    /**
     * Delivery timeline column with color coding based on urgency
     */
    protected static function getDeliveryTimelineColumn(): TextColumn
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

    /**
     * Parent template column for recurring orders
     */
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

    /**
     * Total amount column with money formatting
     */
    protected static function getTotalAmountColumn(): TextColumn
    {
        return TextColumn::make('totalAmount')
            ->label('Total')
            ->money('USD')
            ->getStateUsing(fn (Order $record) => $record->totalAmount());
    }

    /**
     * Harvest date column
     */
    protected static function getHarvestDateColumn(): TextColumn
    {
        return TextColumn::make('harvest_date')
            ->dateTime()
            ->sortable();
    }

    /**
     * Delivery date column
     */
    protected static function getDeliveryDateColumn(): TextColumn
    {
        return TextColumn::make('delivery_date')
            ->dateTime()
            ->sortable();
    }

    /**
     * Created at column (toggleable, hidden by default)
     */
    protected static function getCreatedAtColumn(): TextColumn
    {
        return TextColumn::make('created_at')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    /**
     * Get status color mapping for consistent styling
     * 
     * @return array
     */
    public static function getStatusColors(): array
    {
        return [
            'pending' => 'warning',
            'confirmed' => 'info',
            'in_production' => 'primary',
            'ready' => 'success',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'template' => 'gray',
        ];
    }

    /**
     * Get order type color mapping
     * 
     * @return array
     */
    public static function getOrderTypeColors(): array
    {
        return [
            'website' => 'success',
            'farmers_market' => 'warning',
            'b2b' => 'info',
            'retail' => 'primary',
            'wholesale' => 'secondary',
        ];
    }

    /**
     * Get payment status configuration
     * 
     * @return array
     */
    public static function getPaymentStatusConfig(): array
    {
        return [
            'colors' => [
                'Paid' => 'success',
                'Unpaid' => 'danger',
                'Partial' => 'warning',
                'Refunded' => 'gray',
            ],
            'icons' => [
                'Paid' => 'heroicon-o-check-circle',
                'Unpaid' => 'heroicon-o-x-circle',
                'Partial' => 'heroicon-o-minus-circle',
                'Refunded' => 'heroicon-o-arrow-uturn-left',
            ],
        ];
    }

    /**
     * Get delivery timeline configuration
     * 
     * @return array
     */
    public static function getDeliveryTimelineConfig(): array
    {
        return [
            'colors' => [
                'overdue' => 'danger',
                'today' => 'warning',
                'tomorrow' => 'warning',
                'urgent' => 'warning',  // 1-3 days
                'upcoming' => 'info',   // 4-7 days
                'future' => 'gray',     // 8+ days
            ],
            'thresholds' => [
                'urgent' => 3,
                'upcoming' => 7,
            ],
        ];
    }
}