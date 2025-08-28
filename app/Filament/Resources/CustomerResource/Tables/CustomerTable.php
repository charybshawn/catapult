<?php

namespace App\Filament\Resources\CustomerResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use App\Actions\Customer\CreateCustomerLoginAction;
use Filament\Actions\DeleteAction;
use App\Models\Customer;
use App\Models\CustomerType;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class CustomerTable
{
    /**
     * Get table columns for CustomerResource
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('display_name')
                ->label('Customer')
                ->searchable(['contact_name', 'business_name'])
                ->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query->orderBy('business_name', $direction)->orderBy('contact_name', $direction);
                }),
            TextColumn::make('email')
                ->searchable()
                ->sortable(),
            static::getPhoneColumn(),
            static::getCustomerTypeColumn(),
            TextColumn::make('city')
                ->searchable()
                ->toggleable(),
            TextColumn::make('province')
                ->toggleable(),
            static::getWholesaleDiscountColumn(),
            static::getLoginAccountColumn(),
            static::getOrdersCountColumn(),
        ];
    }

    /**
     * Get table filters for CustomerResource
     */
    public static function filters(): array
    {
        return [
            static::getCustomerTypeFilter(),
            static::getLoginAccountFilter(),
        ];
    }

    /**
     * Get table actions for CustomerResource
     */
    public static function actions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()->tooltip('View record'),
                EditAction::make()->tooltip('Edit record'),
                static::getCreateLoginAction(),
                static::getDeleteAction(),
            ])
            ->label('Actions')
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button(),
        ];
    }

    /**
     * Get bulk actions for CustomerResource
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()->hasRole('admin')),
            ]),
        ];
    }

    /**
     * Phone column with formatting
     */
    protected static function getPhoneColumn(): TextColumn
    {
        return TextColumn::make('phone')
            ->searchable()
            ->formatStateUsing(fn ($state) => $state ? (new Customer(['phone' => $state]))->formatted_phone : null);
    }

    /**
     * Customer type badge column
     */
    protected static function getCustomerTypeColumn(): BadgeColumn
    {
        return BadgeColumn::make('customerType.name')
            ->label('Type')
            ->colors([
                'success' => fn ($state) => $state === 'Retail',
                'info' => fn ($state) => $state === 'Wholesale',
                'warning' => fn ($state) => $state === 'Farmers Market',
            ]);
    }

    /**
     * Wholesale discount percentage column
     */
    protected static function getWholesaleDiscountColumn(): TextColumn
    {
        return TextColumn::make('wholesale_discount_percentage')
            ->label('Discount %')
            ->suffix('%')
            ->toggleable()
            ->visible(fn () => Customer::whereHas('customerType', function ($q) {
                $q->whereIn('code', ['wholesale', 'farmers_market']);
            })->exists());
    }

    /**
     * Login account status column
     */
    protected static function getLoginAccountColumn(): IconColumn
    {
        return IconColumn::make('has_user_account')
            ->label('Login')
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('gray');
    }

    /**
     * Orders count column
     */
    protected static function getOrdersCountColumn(): TextColumn
    {
        return TextColumn::make('orders_count')
            ->label('Orders')
            ->counts('orders')
            ->sortable();
    }

    /**
     * Customer type filter
     */
    protected static function getCustomerTypeFilter(): SelectFilter
    {
        return SelectFilter::make('customer_type_id')
            ->label('Customer Type')
            ->relationship('customerType', 'name')
            ->options(CustomerType::options());
    }

    /**
     * Login account filter
     */
    protected static function getLoginAccountFilter(): TernaryFilter
    {
        return TernaryFilter::make('has_user_account')
            ->label('Has Login')
            ->placeholder('All customers')
            ->trueLabel('With login')
            ->falseLabel('Without login')
            ->queries(
                true: fn (Builder $query) => $query->whereNotNull('user_id'),
                false: fn (Builder $query) => $query->whereNull('user_id'),
            );
    }

    /**
     * Create login action - delegates to Action class
     */
    protected static function getCreateLoginAction(): Action
    {
        return Action::make('create_login')
            ->label('Create Login')
            ->icon('heroicon-o-key')
            ->color('success')
            ->visible(fn (Customer $record) => !$record->hasUserAccount())
            ->schema([
                TextInput::make('password')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->helperText('Minimum 8 characters'),
                Toggle::make('send_credentials')
                    ->label('Email credentials to customer')
                    ->default(true),
            ])
            ->action(function (Customer $record, array $data): void {
                app(CreateCustomerLoginAction::class)->execute($record, $data);
            })
            ->successNotificationTitle('Login created successfully');
    }

    /**
     * Delete action with order count validation
     */
    protected static function getDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->tooltip('Delete record')
            ->visible(fn (Customer $record) => $record->orders()->count() === 0);
    }
}