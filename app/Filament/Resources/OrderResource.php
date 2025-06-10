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
    
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    
    // Only show regular orders, not recurring templates
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('is_recurring', false)
                      ->orWhere(function ($subQuery) {
                          $subQuery->where('is_recurring', true)
                                   ->whereNotNull('parent_recurring_order_id'); // Generated orders
                      });
            });
    }

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
                Tables\Columns\SelectColumn::make('status')
                    ->options(function (Order $record): array {
                        return self::getAvailableStatusOptions($record);
                    })
                    ->disabled(fn (Order $record): bool => $record->status === 'template')
                    ->selectablePlaceholder(false)
                    ->afterStateUpdated(function (Order $record, $state) {
                        // Log the status change
                        activity()
                            ->performedOn($record)
                            ->withProperties([
                                'old_status' => $record->getOriginal('status'),
                                'new_status' => $state,
                                'changed_by' => auth()->user()->name ?? 'System'
                            ])
                            ->log('Status changed');
                            
                        Notification::make()
                            ->title('Status Updated')
                            ->body("Order #{$record->id} status changed to: " . ucfirst($state))
                            ->success()
                            ->send();
                    }),
                Tables\Columns\TextColumn::make('parent_template')
                    ->label('Template')
                    ->getStateUsing(fn (Order $record) => $record->parent_recurring_order_id ? "Template #{$record->parent_recurring_order_id}" : null)
                    ->placeholder('Regular Order')
                    ->toggleable(),
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
                        'pending' => 'Queued',
                        'processing' => 'Preparing',
                        'planted' => 'Growing',
                        'harvested' => 'Harvested',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                        'completed' => 'Completed',
                        'template' => 'Template (Recurring)',
                    ]),
                Tables\Filters\TernaryFilter::make('parent_recurring_order_id')
                    ->label('Order Source')
                    ->nullable()
                    ->placeholder('All orders')
                    ->trueLabel('Generated from template')
                    ->falseLabel('Manual orders only'),
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

    /**
     * Get available status options based on current status and business logic
     */
    public static function getAvailableStatusOptions(Order $record): array
    {
        $allStatuses = [
            'pending' => 'Queued',
            'processing' => 'Preparing',
            'planted' => 'Growing',
            'harvested' => 'Harvested',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'template' => 'Template',
        ];
        
        // Templates can't change status
        if ($record->status === 'template') {
            return ['template' => 'Template'];
        }
        
        // Define logical status transitions
        $allowedTransitions = match($record->status) {
            'pending' => ['pending', 'processing', 'cancelled'], // Can start preparing or cancel
            'processing' => ['processing', 'planted', 'cancelled'], // Can start growing or cancel
            'planted' => ['planted', 'harvested', 'cancelled'], // Can harvest or cancel (in case of crop failure)
            'harvested' => ['harvested', 'delivered', 'cancelled'], // Can deliver or cancel
            'delivered' => ['delivered', 'completed'], // Can only complete (rarely cancel after delivery)
            'completed' => ['completed'], // Final state - no changes allowed
            'cancelled' => ['cancelled', 'pending'], // Can reactivate cancelled orders
            default => array_keys($allStatuses), // Fallback: allow all
        };
        
        // For admin users (check by email or add your own admin logic), allow more flexibility
        $isAdmin = auth()->user()?->email === 'admin@example.com' || 
                   str_contains(auth()->user()?->email ?? '', 'admin') ||
                   auth()->user()?->is_admin ?? false;
                   
        if ($isAdmin) {
            $adminExtraOptions = match($record->status) {
                'processing', 'planted', 'harvested' => ['cancelled'],
                'delivered' => ['cancelled'], // Admin can cancel even delivered orders
                'completed' => ['delivered'], // Admin can step back completed orders
                default => [],
            };
            $allowedTransitions = array_unique(array_merge($allowedTransitions, $adminExtraOptions));
        }
        
        return array_intersect_key($allStatuses, array_flip($allowedTransitions));
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