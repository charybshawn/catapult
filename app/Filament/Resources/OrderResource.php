<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $navigationGroup = 'Sales & Products';
    protected static ?int $navigationSort = 4;

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
                            ->required(),
                        Forms\Components\Select::make('customer_type')
                            ->label('Customer Type')
                            ->options([
                                'retail' => 'Retail',
                                'wholesale' => 'Wholesale',
                            ])
                            ->default('retail')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'processing' => 'Processing',
                                'planted' => 'Planted',
                                'harvested' => 'Harvested',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\DatePicker::make('harvest_date')
                            ->label('Harvest Date')
                            ->required(),
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Delivery Date')
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Order Items')
                    ->schema([
                        Forms\Components\Repeater::make('orderItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('item_id')
                                    ->label('Product')
                                    ->relationship('item', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, Forms\Set $context) {
                                        if ($state) {
                                            $item = Item::find($state);
                                            $customer_type = $get('../../customer_type') ?? 'retail';
                                            $price = $item ? $item->getPriceForCustomerType($customer_type) : 0;
                                            $set('price', $price);
                                        }
                                    })
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required(),
                                Forms\Components\TextInput::make('price')
                                    ->label('Unit Price ($)')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required(),
                                Forms\Components\Textarea::make('notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->reorderable(false)
                            ->collapsible(),
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
                Tables\Columns\TextColumn::make('customer_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'retail' => 'success',
                        'wholesale' => 'info',
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
                        default => 'gray',
                    }),
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
                    ]),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_processing')
                    ->label('Mark as Processing')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'processing']);
                    })
                    ->visible(fn (Order $record): bool => $record->status === 'pending'),
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
            RelationManagers\OrderItemsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\CropsRelationManager::class,
            RelationManagers\OrderPackagingsRelationManager::class,
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