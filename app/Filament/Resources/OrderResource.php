<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderStatus;
use App\Models\User;
use App\Services\OrderPlanningService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Orders';
    protected static ?string $navigationGroup = 'Orders & Sales';
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
                Forms\Components\Section::make('Order Type')
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
                    ]),
                
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Customer')
                            ->options(function () {
                                return \App\Models\Customer::all()
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
                            ->createOptionForm([
                                Forms\Components\TextInput::make('contact_name')
                                    ->label('Contact Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('business_name')
                                    ->label('Business Name')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->unique(Customer::class, 'email'),
                                Forms\Components\TextInput::make('cc_email')
                                    ->label('CC Email Address')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(20),
                                Forms\Components\Select::make('customer_type')
                                    ->label('Customer Type')
                                    ->options([
                                        'retail' => 'Retail',
                                        'wholesale' => 'Wholesale',
                                    ])
                                    ->default('retail')
                                    ->required()
                                    ->reactive(),
                                Forms\Components\TextInput::make('wholesale_discount_percentage')
                                    ->label('Wholesale Discount %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->suffix('%')
                                    ->visible(fn (Forms\Get $get) => $get('customer_type') === 'wholesale'),
                                Forms\Components\Textarea::make('address')
                                    ->label('Address')
                                    ->rows(3),
                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('province')
                                    ->label('Province')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('postal_code')
                                    ->label('Postal Code')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('country')
                                    ->label('Country')
                                    ->maxLength(255)
                                    ->default('Canada'),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return Customer::create($data)->getKey();
                            })
                            ->helperText('Select existing customer or create a new one'),
                        Forms\Components\DateTimePicker::make('delivery_date')
                            ->label('Delivery Date')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state) {
                                    try {
                                        // Calculate harvest date as the evening before delivery date
                                        $deliveryDateTime = Carbon::parse($state);
                                        $harvestDateTime = $deliveryDateTime->copy()->subDay()->setTime(16, 0); // 4:00 PM day before
                                        $set('harvest_date', $harvestDateTime->toDateTimeString());
                                    } catch (\Exception $e) {
                                        // If parsing fails, don't update harvest_date
                                        Log::error('Failed to parse delivery date: ' . $e->getMessage());
                                    }
                                }
                            })
                            ->helperText(function (callable $get) {
                                $helperText = 'Select the date and time for delivery - harvest date will be automatically set to 4:00 PM the day before';
                                
                                // Check if delivery date is too soon
                                $deliveryDate = $get('delivery_date');
                                if ($deliveryDate) {
                                    try {
                                        $delivery = Carbon::parse($deliveryDate);
                                        $daysUntilDelivery = now()->diffInDays($delivery, false);
                                        
                                        // Most crops need at least 5-21 days to grow
                                        if ($daysUntilDelivery < 5) {
                                            $helperText .= ' ⚠️ WARNING: This delivery date may be too soon for crop production!';
                                        }
                                    } catch (\Exception $e) {
                                        // Ignore parse errors
                                    }
                                }
                                
                                return $helperText;
                            })
                            ->visible(fn (Forms\Get $get) => !$get('is_recurring')),
                        Forms\Components\DateTimePicker::make('harvest_date')
                            ->label('Harvest Date')
                            ->helperText('When this order should be harvested (automatically set to evening before delivery, but can be overridden)')
                            ->required(fn (Forms\Get $get) => !$get('is_recurring'))
                            ->visible(fn (Forms\Get $get) => !$get('is_recurring')),
                        Forms\Components\Select::make('order_type_id')
                            ->label('Order Type')
                            ->relationship('orderType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(function () {
                                // Set default to 'website' order type
                                $websiteType = \App\Models\OrderType::where('code', 'website')->first();
                                return $websiteType?->id;
                            })
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, $record) {
                                // Auto-set status based on order type when creating
                                if (!$record && $state) {
                                    $orderType = \App\Models\OrderType::find($state);
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
                            }),
                        Forms\Components\Select::make('status_id')
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
                            })
                            ->disabled(fn ($record) => $record && $record->status && ($record->status->code === 'template' || $record->status->is_final)),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Recurring Settings')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('recurring_frequency')
                                    ->label('Frequency')
                                    ->options([
                                        'weekly' => 'Weekly',
                                        'biweekly' => 'Biweekly',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->required(),
                                
                                Forms\Components\DatePicker::make('recurring_start_date')
                                    ->label('Start Date')
                                    ->helperText('First occurrence date')
                                    ->required(),
                                
                                Forms\Components\DatePicker::make('recurring_end_date')
                                    ->label('End Date (Optional)')
                                    ->helperText('Leave empty for indefinite'),
                            ]),
                    ])
                    ->visible(fn ($get) => $get('is_recurring')),
                
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
                            ->required(),
                        
                        Forms\Components\Toggle::make('requires_invoice')
                            ->label('Requires Invoice')
                            ->default(true),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                
                
                Forms\Components\Section::make('Order Items')
                    ->schema([
                        \App\Forms\Components\InvoiceOrderItems::make('orderItems')
                            ->label('Items')
                            ->productOptions(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->toArray())
                            ->required()
                            ->rules([
                                'array',
                                'min:1',
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (!is_array($value)) {
                                            $fail('Order must have at least one item.');
                                            return;
                                        }
                                        
                                        $hasValidItems = false;
                                        foreach ($value as $index => $item) {
                                            // Check if item has required fields
                                            if (empty($item['item_id'])) {
                                                continue; // Skip empty rows
                                            }
                                            
                                            // If item has a product, validate other fields
                                            if (!isset($item['quantity']) || $item['quantity'] === null || $item['quantity'] === '') {
                                                $fail("Item " . ($index + 1) . ": Quantity is required.");
                                            } else {
                                                // Handle both string and numeric values
                                                $qty = is_string($item['quantity']) ? trim($item['quantity']) : $item['quantity'];
                                                if (!is_numeric($qty) || floatval($qty) <= 0) {
                                                    $fail("Item " . ($index + 1) . ": Quantity must be a number greater than 0.");
                                                }
                                            }
                                            
                                            if (!isset($item['price']) || $item['price'] === null || $item['price'] === '') {
                                                $fail("Item " . ($index + 1) . ": Price is required.");
                                            } elseif (!is_numeric($item['price']) || floatval($item['price']) < 0) {
                                                $fail("Item " . ($index + 1) . ": Price must be 0 or greater.");
                                            }
                                            
                                            if (!empty($item['item_id'])) {
                                                $hasValidItems = true;
                                            }
                                        }
                                        
                                        if (!$hasValidItems) {
                                            $fail('Order must have at least one valid item.');
                                        }
                                    };
                                }
                            ]),
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
            ->modifyQueryUsing(fn ($query) => $query->with(['customer.customerType', 'orderItems', 'invoice', 'orderType', 'status']))
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.contact_name')
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
                    }),
                Tables\Columns\TextColumn::make('order_type_display')
                    ->label('Type')
                    ->badge()
                    ->color(fn (Order $record): string => match ($record->orderType?->code) {
                        'website' => 'success',
                        'farmers_market' => 'warning',
                        'b2b' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\SelectColumn::make('status_id')
                    ->label('Status')
                    ->options(function () {
                        return OrderStatus::getOptionsForDropdown(false, false);
                    })
                    ->selectablePlaceholder(false)
                    ->disabled(fn ($record): bool => 
                        $record instanceof Order && ($record->status?->code === 'template' || $record->status?->is_final)
                    )
                    ->rules([
                        fn ($record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
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
                    }),
                Tables\Columns\TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Order $record): string => $record->status?->badge_color ?? 'gray')
                    ->formatStateUsing(fn (string $state, Order $record): string => 
                        $state . ' (' . $record->status?->stage_display . ')'
                    )
                    ->visible(false), // Hidden by default, can be toggled
                Tables\Columns\IconColumn::make('requiresCrops')
                    ->label('Needs Growing')
                    ->boolean()
                    ->getStateUsing(fn (Order $record) => $record->requiresCropProduction())
                    ->trueIcon('heroicon-o-sun')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (Order $record) => $record->requiresCropProduction() ? 'This order requires crop production' : 'No crops needed'),
                Tables\Columns\TextColumn::make('paymentStatus')
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
                    }),
                Tables\Columns\TextColumn::make('daysUntilDelivery')
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
                    }),
                Tables\Columns\TextColumn::make('parent_template')
                    ->label('Template')
                    ->getStateUsing(fn (Order $record) => $record->parent_recurring_order_id ? "Template #{$record->parent_recurring_order_id}" : null)
                    ->placeholder('Regular Order')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('totalAmount')
                    ->label('Total')
                    ->money('USD')
                    ->getStateUsing(fn (Order $record) => $record->totalAmount()),
                Tables\Columns\TextColumn::make('harvest_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status_id')
                    ->label('Status')
                    ->options(function () {
                        return OrderStatus::getOptionsForDropdown(false, true);
                    })
                    ->searchable(),
                Tables\Filters\SelectFilter::make('stage')
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
                    }),
                Tables\Filters\TernaryFilter::make('requires_crops')
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
                    ),
                Tables\Filters\TernaryFilter::make('payment_status')
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
                    ),
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
                Tables\Actions\Action::make('generate_next_recurring')
                    ->label('Generate Next Order')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => 
                        $record->status?->code === 'template' && 
                        $record->is_recurring
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Generate Next Recurring Order')
                    ->modalDescription(fn (Order $record) => 
                        "This will create the next order in the recurring series for {$record->customer->contact_name}."
                    )
                    ->action(function (Order $record) {
                        try {
                            $recurringOrderService = app(\App\Services\RecurringOrderService::class);
                            $newOrder = $recurringOrderService->generateNextOrder($record);
                            
                            if ($newOrder) {
                                Notification::make()
                                    ->title('Recurring Order Generated')
                                    ->body("Order #{$newOrder->id} has been created successfully.")
                                    ->success()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('view')
                                            ->label('View Order')
                                            ->url(route('filament.admin.resources.orders.edit', ['record' => $newOrder->id]))
                                    ])
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No Order Generated')
                                    ->body('No new order was generated. It may not be time for the next recurring order yet.')
                                    ->warning()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error Generating Order')
                                ->body('Failed to generate recurring order: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('recalculate_prices')
                    ->label('Recalculate Prices')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->visible(fn (Order $record): bool => 
                        $record->status?->code !== 'template' && 
                        $record->status?->code !== 'cancelled' &&
                        !$record->status?->is_final &&
                        $record->customer->isWholesaleCustomer() &&
                        $record->orderItems->isNotEmpty()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Recalculate Order Prices')
                    ->modalDescription(function (Order $record) {
                        $currentTotal = $record->totalAmount();
                        $discount = $record->customer->wholesale_discount_percentage ?? 0;
                        return "This will recalculate all item prices using the current wholesale discount ({$discount}%). Current total: $" . number_format($currentTotal, 2);
                    })
                    ->action(function (Order $record) {
                        try {
                            $oldTotal = $record->totalAmount();
                            $updatedItems = 0;
                            
                            foreach ($record->orderItems as $item) {
                                if (!$item->product || !$item->price_variation_id) {
                                    continue;
                                }
                                
                                // Get current price for this customer
                                $currentPrice = $item->product->getPriceForSpecificCustomer(
                                    $record->customer,
                                    $item->price_variation_id
                                );
                                
                                // Check if price has changed
                                if (abs($currentPrice - $item->price) > 0.001) {
                                    $item->price = $currentPrice;
                                    $item->save();
                                    $updatedItems++;
                                }
                            }
                            
                            $newTotal = $record->totalAmount();
                            $difference = $oldTotal - $newTotal;
                            
                            if ($updatedItems > 0) {
                                Notification::make()
                                    ->title('Prices Recalculated')
                                    ->body("Updated {$updatedItems} items. New total: $" . number_format($newTotal, 2) . " (saved $" . number_format($difference, 2) . ")")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No Changes Needed')
                                    ->body('All prices are already up to date.')
                                    ->info()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error Recalculating Prices')
                                ->body('Failed to recalculate prices: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('generate_crop_plans')
                    ->label('Generate Crop Plans')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->visible(fn (Order $record): bool => 
                        $record->requiresCropProduction() &&
                        !$record->isInFinalState() &&
                        !$record->cropPlans()->exists()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Generate Crop Plans')
                    ->modalDescription(fn (Order $record) => 
                        "This will analyze the order items and generate crop plans based on the delivery date."
                    )
                    ->action(function (Order $record) {
                        $orderPlanningService = app(OrderPlanningService::class);
                        $result = $orderPlanningService->generatePlansForOrder($record);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Crop Plans Generated')
                                ->body("Successfully generated {$result['plans']->count()} crop plans.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Generation Failed')
                                ->body(implode(' ', $result['issues']))
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('convert_to_invoice')
                    ->label('Create Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(fn (Order $record): bool => 
                        $record->status?->code !== 'template' && 
                        $record->requires_invoice &&
                        !$record->invoice // Only show if no invoice exists yet
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Create Invoice')
                    ->modalDescription(fn (Order $record) => 
                        "This will create an invoice for Order #{$record->id} totaling $" . number_format($record->totalAmount(), 2) . "."
                    )
                    ->action(function (Order $record) {
                        try {
                            $invoice = \App\Models\Invoice::createFromOrder($record);
                            
                            Notification::make()
                                ->title('Invoice Created')
                                ->body("Invoice #{$invoice->id} has been created successfully.")
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('View Invoice')
                                        ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id]))
                                ])
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error Creating Invoice')
                                ->body('Failed to create invoice: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('convert_to_recurring')
                    ->label('Convert to Recurring')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->visible(fn (Order $record): bool => 
                        !$record->is_recurring && 
                        $record->status?->code !== 'template' &&
                        $record->parent_recurring_order_id === null && // Not generated from recurring
                        $record->customer &&
                        $record->orderItems()->count() > 0
                    )
                    ->form([
                        Forms\Components\Section::make('Recurring Settings')
                            ->schema([
                                Forms\Components\Select::make('frequency')
                                    ->label('Frequency')
                                    ->options([
                                        'weekly' => 'Weekly',
                                        'biweekly' => 'Bi-weekly',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->default('weekly')
                                    ->required()
                                    ->reactive(),
                                    
                                Forms\Components\TextInput::make('interval')
                                    ->label('Interval (weeks)')
                                    ->helperText('For bi-weekly: enter 2 for every 2 weeks')
                                    ->numeric()
                                    ->default(2)
                                    ->minValue(1)
                                    ->maxValue(12)
                                    ->visible(fn (Get $get) => $get('frequency') === 'biweekly'),
                                    
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->default(now()->addWeek())
                                    ->required()
                                    ->minDate(now()),
                                    
                                Forms\Components\DatePicker::make('end_date')
                                    ->label('End Date (Optional)')
                                    ->helperText('Leave blank for indefinite recurring')
                                    ->minDate(fn (Get $get) => $get('start_date')),
                            ])
                            ->columns(2),
                    ])
                    ->modalHeading('Convert Order to Recurring Template')
                    ->modalDescription(fn (Order $record) => 
                        "This will convert Order #{$record->id} into a recurring order template that will automatically generate new orders."
                    )
                    ->action(function (Order $record, array $data) {
                        try {
                            $recurringOrderService = app(\App\Services\RecurringOrderService::class);
                            $convertedOrder = $recurringOrderService->convertToRecurringTemplate($record, $data);
                            
                            Notification::make()
                                ->title('Order Converted Successfully')
                                ->body("Order #{$record->id} has been converted to a recurring template.")
                                ->success()
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('View Template')
                                        ->url(route('filament.admin.resources.recurring-orders.edit', ['record' => $convertedOrder->id]))
                                ])
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Conversion Failed')
                                ->body('Failed to convert order to recurring: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('transition_status')
                    ->label('Change Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Order $record): bool => 
                        !$record->isInFinalState() && 
                        $record->status?->code !== 'template'
                    )
                    ->form(function (Order $record) {
                        $validStatuses = app(\App\Services\StatusTransitionService::class)
                            ->getValidNextStatuses($record);
                        
                        if ($validStatuses->isEmpty()) {
                            return [
                                Forms\Components\Placeholder::make('no_transitions')
                                    ->label('')
                                    ->content('No valid status transitions available for this order.')
                            ];
                        }
                        
                        return [
                            Forms\Components\Select::make('new_status')
                                ->label('New Status')
                                ->options($validStatuses->pluck('name', 'code'))
                                ->required()
                                ->helperText('Select the new status for this order'),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('Optional notes about this status change')
                                ->rows(3),
                        ];
                    })
                    ->action(function (Order $record, array $data) {
                        $result = $record->transitionTo($data['new_status'], [
                            'manual' => true,
                            'notes' => $data['notes'] ?? null,
                            'user_id' => auth()->id()
                        ]);
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Status Updated')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Status Update Failed')
                                ->body($result['message'])
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\ViewAction::make()
                    ->tooltip('View order details'),
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit order'),
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete order'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('create_consolidated_invoice')
                        ->label('Create Consolidated Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Create Consolidated Invoice')
                        ->modalDescription('This will create a single invoice for all selected orders.')
                        ->form([
                            Forms\Components\DatePicker::make('issue_date')
                                ->label('Issue Date')
                                ->default(now())
                                ->required(),
                            Forms\Components\DatePicker::make('due_date')
                                ->label('Due Date')
                                ->default(now()->addDays(30))
                                ->required(),
                            Forms\Components\Textarea::make('notes')
                                ->label('Invoice Notes')
                                ->placeholder('Additional notes for the consolidated invoice...')
                                ->rows(3),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            // Validate that orders can be consolidated
                            $errors = self::validateOrdersForConsolidation($records);
                            
                            if (!empty($errors)) {
                                Notification::make()
                                    ->title('Cannot Create Consolidated Invoice')
                                    ->body(implode(' ', $errors))
                                    ->danger()
                                    ->persistent()
                                    ->send();
                                return;
                            }
                            
                            try {
                                $invoice = self::createConsolidatedInvoice($records, $data);
                                
                                Notification::make()
                                    ->title('Consolidated Invoice Created')
                                    ->body("Invoice #{$invoice->invoice_number} created for {$records->count()} orders totaling $" . number_format($invoice->total_amount, 2) . ".")
                                    ->success()
                                    ->actions([
                                        \Filament\Notifications\Actions\Action::make('view')
                                            ->label('View Invoice')
                                            ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id]))
                                    ])
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error Creating Invoice')
                                    ->body('Failed to create consolidated invoice: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('bulk_status_update')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Status Update')
                        ->modalDescription(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $finalOrders = $records->filter(fn($order) => $order->isInFinalState());
                            $templateOrders = $records->filter(fn($order) => $order->status?->code === 'template');
                            
                            $warnings = [];
                            if ($finalOrders->isNotEmpty()) {
                                $warnings[] = "{$finalOrders->count()} orders in final state will be skipped.";
                            }
                            if ($templateOrders->isNotEmpty()) {
                                $warnings[] = "{$templateOrders->count()} template orders will be skipped.";
                            }
                            
                            $eligibleCount = $records->count() - $finalOrders->count() - $templateOrders->count();
                            
                            return "Update status for {$eligibleCount} orders." . 
                                   (!empty($warnings) ? "\n\nWarnings:\n" . implode("\n", $warnings) : '');
                        })
                        ->form([
                            Forms\Components\Select::make('new_status')
                                ->label('New Status')
                                ->options(OrderStatus::active()
                                    ->notFinal()
                                    ->where('code', '!=', 'template')
                                    ->pluck('name', 'code'))
                                ->required()
                                ->helperText('Select the new status for all eligible orders'),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('Optional notes about this bulk status change')
                                ->rows(3),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $statusService = app(\App\Services\StatusTransitionService::class);
                            
                            // Filter out ineligible orders
                            $eligibleOrders = $records->filter(function ($order) {
                                return !$order->isInFinalState() && 
                                       $order->status?->code !== 'template';
                            });
                            
                            if ($eligibleOrders->isEmpty()) {
                                Notification::make()
                                    ->title('No Eligible Orders')
                                    ->body('None of the selected orders can have their status updated.')
                                    ->warning()
                                    ->send();
                                return;
                            }
                            
                            $result = $statusService->bulkTransition(
                                $eligibleOrders->pluck('id')->toArray(),
                                $data['new_status'],
                                [
                                    'manual' => true,
                                    'notes' => $data['notes'] ?? null,
                                    'user_id' => auth()->id()
                                ]
                            );
                            
                            $successCount = count($result['successful']);
                            $failedCount = count($result['failed']);
                            
                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Status Update Complete')
                                    ->body("Successfully updated {$successCount} orders." . 
                                           ($failedCount > 0 ? " {$failedCount} orders failed." : ''))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Status Update Failed')
                                    ->body("Failed to update any orders. Check the logs for details.")
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\CropPlansRelationManager::class,
            // Other relationships can be managed through dedicated pages if needed
        ];
    }


    /**
     * Validate that orders can be consolidated into a single invoice
     */
    protected static function validateOrdersForConsolidation(\Illuminate\Database\Eloquent\Collection $orders): array
    {
        $errors = [];

        // Check if any orders are templates
        $templates = $orders->filter(function ($order) {
            return $order->status?->code === 'template';
        });
        if ($templates->isNotEmpty()) {
            $errors[] = 'Cannot create invoices for template orders.';
        }

        // Check if any orders don't require invoices
        $noInvoiceNeeded = $orders->where('requires_invoice', false);
        if ($noInvoiceNeeded->isNotEmpty()) {
            $errors[] = 'Some selected orders do not require invoices.';
        }

        // Check if any orders already have invoices
        $alreadyInvoiced = $orders->whereNotNull('invoice_id');
        if ($alreadyInvoiced->isNotEmpty()) {
            $errors[] = 'Some orders already have invoices.';
        }

        // Check if all orders belong to the same customer
        $customerIds = $orders->pluck('user_id')->unique();
        if ($customerIds->count() > 1) {
            $errors[] = 'All orders must belong to the same customer for consolidated invoicing.';
        }

        // Check minimum number of orders
        if ($orders->count() < 2) {
            $errors[] = 'At least 2 orders are required for consolidated invoicing.';
        }

        return $errors;
    }

    /**
     * Create a consolidated invoice from multiple orders
     */
    protected static function createConsolidatedInvoice(\Illuminate\Database\Eloquent\Collection $orders, array $data): \App\Models\Invoice
    {
        // Calculate total amount
        $totalAmount = $orders->sum(function ($order) {
            return $order->totalAmount();
        });

        // Get billing period from order dates
        $deliveryDates = $orders->pluck('delivery_date')->map(fn($date) => \Carbon\Carbon::parse($date))->sort();
        $billingPeriodStart = $deliveryDates->first()->startOfMonth();
        $billingPeriodEnd = $deliveryDates->last()->endOfMonth();

        // Generate invoice number
        $invoiceNumber = \App\Models\Invoice::generateInvoiceNumber();

        // Create the consolidated invoice
        $invoice = \App\Models\Invoice::create([
            'user_id' => $orders->first()->user_id,
            'invoice_number' => $invoiceNumber,
            'amount' => $totalAmount,
            'total_amount' => $totalAmount,
            'status' => 'draft',
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'billing_period_start' => $billingPeriodStart,
            'billing_period_end' => $billingPeriodEnd,
            'is_consolidated' => true,
            'consolidated_order_count' => $orders->count(),
            'notes' => $data['notes'] ?? "Consolidated invoice for {$orders->count()} orders: " . $orders->pluck('id')->implode(', '),
        ]);

        // Link all orders to this consolidated invoice
        $orders->each(function ($order) use ($invoice) {
            $order->update(['consolidated_invoice_id' => $invoice->id]);
        });

        return $invoice;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
            'calendar' => Pages\CalendarOrders::route('/calendar'),
        ];
    }
} 