<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $navigationGroup = 'Order Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(User::class, 'email')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),
                                Forms\Components\Select::make('customer_type')
                                    ->label('Customer Type')
                                    ->options([
                                        'retail' => 'Retail',
                                        'wholesale' => 'Wholesale',
                                    ])
                                    ->default('retail')
                                    ->required(),
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Company Name')
                                    ->maxLength(255)
                                    ->visible(fn (Forms\Get $get) => $get('customer_type') === 'wholesale'),
                                Forms\Components\Group::make([
                                    Forms\Components\Textarea::make('address')
                                        ->rows(2)
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('city')
                                        ->maxLength(100),
                                    Forms\Components\TextInput::make('state')
                                        ->maxLength(50),
                                    Forms\Components\TextInput::make('zip')
                                        ->label('ZIP Code')
                                        ->maxLength(20),
                                ])->columns(3),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $data['password'] = bcrypt(Str::random(12)); // Generate random password
                                return User::create($data)->getKey();
                            }),
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Delivery Date')
                            ->required(),
                        Forms\Components\Select::make('order_type')
                            ->label('Order Type')
                            ->options([
                                'website_immediate' => 'Website Order',
                                'farmers_market' => 'Farmer\'s Market',
                                'b2b_recurring' => 'B2B Recurring',
                            ])
                            ->default('website_immediate')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Auto-set billing frequency based on order type
                                if ($state === 'farmers_market') {
                                    $set('billing_frequency', 'immediate');
                                    $set('requires_invoice', false);
                                } elseif ($state === 'website_immediate') {
                                    $set('billing_frequency', 'immediate');
                                    $set('requires_invoice', true);
                                } elseif ($state === 'b2b_recurring') {
                                    $set('billing_frequency', 'monthly');
                                    $set('requires_invoice', true);
                                }
                            }),
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Make this a recurring order')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) {
                                    // Clear recurring fields when toggled off
                                    $set('recurring_frequency', null);
                                    $set('recurring_interval', null);
                                }
                            }),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Billing & Invoicing')
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
                            ->required()
                            ->visible(fn ($get) => $get('order_type') === 'b2b_recurring'),
                        
                        Forms\Components\Toggle::make('requires_invoice')
                            ->label('Requires Invoice')
                            ->helperText('Uncheck for farmer\'s market orders that don\'t need invoicing')
                            ->default(true),
                    ])
                    ->visible(fn ($get) => in_array($get('order_type'), ['b2b_recurring', 'farmers_market']))
                    ->columns(2),
                
                Forms\Components\Section::make('Recurring Order Settings')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('recurring_frequency')
                                ->label('Frequency')
                                ->options([
                                    'weekly' => 'Weekly',
                                    'biweekly' => 'Bi-weekly',
                                    'monthly' => 'Monthly',
                                ])
                                ->reactive()
                                ->required()
                                ->visible(fn ($get) => $get('is_recurring')),
                            
                            Forms\Components\TextInput::make('recurring_interval')
                                ->label('Interval (weeks)')
                                ->helperText('For bi-weekly: enter 2 for every 2 weeks')
                                ->numeric()
                                ->default(2)
                                ->minValue(1)
                                ->maxValue(12)
                                ->visible(fn ($get) => $get('is_recurring') && $get('recurring_frequency') === 'biweekly'),
                        ])->columns(2),
                        
                        Forms\Components\Toggle::make('is_recurring_active')
                            ->label('Active')
                            ->helperText('Uncheck to pause recurring order generation')
                            ->default(true)
                            ->visible(fn ($get) => $get('is_recurring')),
                    ])
                    ->visible(fn ($get) => $get('is_recurring'))
                    ->collapsible()
                    ->collapsed(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord),
                
                Forms\Components\Section::make('Order Items')
                    ->schema([
                        \App\Forms\Components\InvoiceOrderItems::make('orderItems')
                            ->label('Items')
                            ->productOptions(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->required(),
                    ]),
                
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer_type_display')
                    ->label('Customer Type')
                    ->badge()
                    ->color(fn (Order $record): string => match ($record->customer_type) {
                        'retail' => 'success',
                        'wholesale' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('order_type_display')
                    ->label('Order Type')
                    ->badge()
                    ->color(fn (Order $record): string => match ($record->order_type) {
                        'website_immediate' => 'success',
                        'farmers_market' => 'warning',
                        'b2b_recurring' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('billing_frequency_display')
                    ->label('Billing')
                    ->badge()
                    ->color(fn (Order $record): string => match ($record->billing_frequency) {
                        'immediate' => 'success',
                        'weekly' => 'info',
                        'monthly' => 'warning',
                        'quarterly' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'processing' => 'info',
                        'planted' => 'warning',
                        'harvested' => 'success',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'success',
                        'template' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_recurring')
                    ->label('Recurring')
                    ->boolean()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('recurring_frequency_display')
                    ->label('Frequency')
                    ->toggleable()
                    ->visible(fn () => request()->get('tableFilters.is_recurring.value') === true),
                Tables\Columns\TextColumn::make('generated_orders_count')
                    ->label('Generated')
                    ->numeric()
                    ->toggleable()
                    ->visible(fn () => request()->get('tableFilters.is_recurring.value') === true),
                Tables\Columns\TextColumn::make('totalAmount')
                    ->label('Total')
                    ->money('USD')
                    ->getStateUsing(fn (Order $record) => $record->totalAmount()),
                Tables\Columns\TextColumn::make('harvest_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('isPaid')
                    ->label('Paid')
                    ->boolean()
                    ->getStateUsing(fn (Order $record) => $record->isPaid()),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'planted' => 'Planted',
                        'harvested' => 'Harvested',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                        'template' => 'Template (Recurring)',
                    ]),
                Tables\Filters\TernaryFilter::make('is_recurring')
                    ->label('Recurring Orders')
                    ->nullable()
                    ->placeholder('All orders')
                    ->trueLabel('Recurring only')
                    ->falseLabel('Non-recurring only'),
                Tables\Filters\SelectFilter::make('recurring_frequency')
                    ->label('Frequency')
                    ->options([
                        'weekly' => 'Weekly',
                        'biweekly' => 'Bi-weekly',
                        'monthly' => 'Monthly',
                    ])
                    ->query(function (Builder $query, $data) {
                        if ($data['value']) {
                            return $query->where('is_recurring', true)
                                        ->where('recurring_frequency', $data['value']);
                        }
                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('customer_type')
                    ->options([
                        'retail' => 'Retail',
                        'wholesale' => 'Wholesale',
                    ]),
                Tables\Filters\Filter::make('harvest_date')
                    ->form([
                        Forms\Components\DatePicker::make('harvest_from'),
                        Forms\Components\DatePicker::make('harvest_until'),
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
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->tooltip('View order details'),
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit order'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete order'),
                Tables\Actions\Action::make('mark_processing')
                    ->label('Mark as Processing')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'processing']);
                    })
                    ->visible(fn (Order $record): bool => $record->status === 'pending' && !$record->is_recurring),
                Tables\Actions\Action::make('mark_planted')
                    ->label('Mark as Planted')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'planted']);
                    })
                    ->visible(fn (Order $record): bool => $record->status === 'processing'),
                Tables\Actions\Action::make('mark_harvested')
                    ->label('Mark as Harvested')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'harvested']);
                    })
                    ->visible(fn (Order $record): bool => $record->status === 'planted'),
                Tables\Actions\Action::make('mark_delivered')
                    ->label('Mark as Delivered')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'delivered']);
                    })
                    ->visible(fn (Order $record): bool => $record->status === 'harvested'),
                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'completed']);
                    })
                    ->visible(fn (Order $record): bool => $record->status === 'delivered'),
                Tables\Actions\Action::make('mark_cancelled')
                    ->label('Cancel Order')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'cancelled']);
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => !in_array($record->status, ['completed', 'cancelled'])),
                
                // Recurring Order Actions
                Tables\Actions\Action::make('generate_next')
                    ->label('Generate Next Order')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->action(function (Order $record): void {
                        $newOrder = $record->generateNextRecurringOrder();
                        if ($newOrder) {
                            Notification::make()
                                ->title('Recurring order generated')
                                ->body("Order #{$newOrder->id} created for {$newOrder->harvest_date->format('M d, Y')}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Unable to generate order')
                                ->body('Check recurring order settings and end date')
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn (Order $record): bool => $record->isRecurringTemplate() && $record->is_recurring_active),
                
                Tables\Actions\Action::make('pause_recurring')
                    ->label('Pause Recurring')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->action(function (Order $record): void {
                        $record->update(['is_recurring_active' => false]);
                        Notification::make()
                            ->title('Recurring order paused')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => $record->isRecurringTemplate() && $record->is_recurring_active),
                
                Tables\Actions\Action::make('resume_recurring')
                    ->label('Resume Recurring')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function (Order $record): void {
                        app(\App\Services\RecurringOrderService::class)->resumeRecurringOrder($record);
                        Notification::make()
                            ->title('Recurring order resumed')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Order $record): bool => $record->isRecurringTemplate() && !$record->is_recurring_active),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Removed all relation managers for cleaner edit interface
            // Order items are now handled inline in the main form
            // Other relationships can be managed through dedicated pages if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
} 