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
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->unique(User::class, 'email'),
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
                                Forms\Components\TextInput::make('company_name')
                                    ->label('Company Name')
                                    ->maxLength(255)
                                    ->visible(fn (Forms\Get $get) => $get('customer_type') === 'wholesale'),
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
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $data['password'] = bcrypt(Str::random(12));
                                $data['email_verified_at'] = now();
                                return User::create($data)->getKey();
                            })
                            ->helperText('Select existing customer or create a new one'),
                        Forms\Components\DatePicker::make('harvest_date')
                            ->label('Harvest Date')
                            ->helperText('When this order should be harvested (used by crop planner)')
                            ->required(),
                        Forms\Components\DatePicker::make('delivery_date')
                            ->label('Delivery Date')
                            ->required(),
                        Forms\Components\Select::make('order_type')
                            ->label('Order Type')
                            ->options([
                                'website_immediate' => 'Website Order',
                                'farmers_market' => 'Farmer\'s Market',
                                'b2b_recurring' => 'B2B',
                            ])
                            ->default('website_immediate')
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Recurring Settings')
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
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('recurring_frequency')
                                    ->label('Frequency')
                                    ->options([
                                        'weekly' => 'Weekly',
                                        'biweekly' => 'Biweekly',
                                        'monthly' => 'Monthly',
                                    ])
                                    ->required()
                                    ->visible(fn ($get) => $get('is_recurring')),
                                
                                Forms\Components\DatePicker::make('recurring_start_date')
                                    ->label('Start Date')
                                    ->helperText('First occurrence date')
                                    ->required()
                                    ->visible(fn ($get) => $get('is_recurring')),
                                
                                Forms\Components\DatePicker::make('recurring_end_date')
                                    ->label('End Date (Optional)')
                                    ->helperText('Leave empty for indefinite')
                                    ->visible(fn ($get) => $get('is_recurring')),
                            ])
                            ->visible(fn ($get) => $get('is_recurring')),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
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
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Order ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_type_display')
                    ->label('Type')
                    ->badge()
                    ->color(fn (Order $record): string => match ($record->order_type) {
                        'website_immediate' => 'success',
                        'farmers_market' => 'warning',
                        'b2b' => 'info',
                        'b2b_recurring' => 'info', // Legacy support
                        default => 'gray',
                    }),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Order Status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'template' => 'Template',
                    ])
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
                            ->log('Order status changed');
                            
                        Notification::make()
                            ->title('Order Status Updated')
                            ->body("Order #{$record->id} status changed to: " . ucfirst($state))
                            ->success()
                            ->send();
                    }),
                Tables\Columns\SelectColumn::make('crop_status')
                    ->label('Crop Status')
                    ->options([
                        'not_started' => 'Not Started',
                        'planted' => 'Planted',
                        'growing' => 'Growing',
                        'ready_to_harvest' => 'Ready to Harvest',
                        'harvested' => 'Harvested',
                        'na' => 'N/A',
                    ])
                    ->disabled(fn (Order $record): bool => $record->crop_status === 'na')
                    ->selectablePlaceholder(false)
                    ->afterStateUpdated(function (Order $record, $state) {
                        activity()
                            ->performedOn($record)
                            ->withProperties([
                                'old_status' => $record->getOriginal('crop_status'),
                                'new_status' => $state,
                                'changed_by' => auth()->user()->name ?? 'System'
                            ])
                            ->log('Crop status changed');
                            
                        Notification::make()
                            ->title('Crop Status Updated')
                            ->body("Order #{$record->id} crop status changed to: " . str_replace('_', ' ', ucfirst($state)))
                            ->success()
                            ->send();
                    })
                    ->toggleable(),
                Tables\Columns\SelectColumn::make('fulfillment_status')
                    ->label('Fulfillment')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'packing' => 'Packing',
                        'packed' => 'Packed',
                        'ready_for_delivery' => 'Ready for Delivery',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
                    ])
                    ->selectablePlaceholder(false)
                    ->afterStateUpdated(function (Order $record, $state) {
                        activity()
                            ->performedOn($record)
                            ->withProperties([
                                'old_status' => $record->getOriginal('fulfillment_status'),
                                'new_status' => $state,
                                'changed_by' => auth()->user()->name ?? 'System'
                            ])
                            ->log('Fulfillment status changed');
                            
                        Notification::make()
                            ->title('Fulfillment Status Updated')
                            ->body("Order #{$record->id} fulfillment status changed to: " . str_replace('_', ' ', ucfirst($state)))
                            ->success()
                            ->send();
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('parent_template')
                    ->label('Template')
                    ->getStateUsing(fn (Order $record) => $record->parent_recurring_order_id ? "Template #{$record->parent_recurring_order_id}" : null)
                    ->placeholder('Regular Order')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->getStateUsing(fn (Order $record) => $record->isPaid())
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Order Status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'template' => 'Template',
                    ]),
                Tables\Filters\SelectFilter::make('crop_status')
                    ->label('Crop Status')
                    ->options([
                        'not_started' => 'Not Started',
                        'planted' => 'Planted',
                        'growing' => 'Growing',
                        'ready_to_harvest' => 'Ready to Harvest',
                        'harvested' => 'Harvested',
                        'na' => 'N/A',
                    ]),
                Tables\Filters\SelectFilter::make('fulfillment_status')
                    ->label('Fulfillment Status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'packing' => 'Packing',
                        'packed' => 'Packed',
                        'ready_for_delivery' => 'Ready for Delivery',
                        'out_for_delivery' => 'Out for Delivery',
                        'delivered' => 'Delivered',
                        'cancelled' => 'Cancelled',
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
                Tables\Actions\Action::make('generate_next_recurring')
                    ->label('Generate Next Order')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (Order $record): bool => 
                        $record->status === 'template' && 
                        $record->is_recurring
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Generate Next Recurring Order')
                    ->modalDescription(fn (Order $record) => 
                        "This will create the next order in the recurring series for {$record->user->name}."
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
                        $record->status !== 'template' && 
                        $record->status !== 'cancelled' &&
                        $record->user->isWholesaleCustomer() &&
                        $record->orderItems->isNotEmpty()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Recalculate Order Prices')
                    ->modalDescription(function (Order $record) {
                        $currentTotal = $record->totalAmount();
                        $discount = $record->user->wholesale_discount_percentage ?? 0;
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
                                    $record->user,
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
                
                Tables\Actions\Action::make('convert_to_invoice')
                    ->label('Create Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(fn (Order $record): bool => 
                        $record->status !== 'template' && 
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
     * Validate that orders can be consolidated into a single invoice
     */
    protected static function validateOrdersForConsolidation(\Illuminate\Database\Eloquent\Collection $orders): array
    {
        $errors = [];

        // Check if any orders are templates
        $templates = $orders->where('status', 'template');
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
        ];
    }
} 