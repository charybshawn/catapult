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
                                'b2b' => 'B2B',
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
                                } elseif ($state === 'b2b') {
                                    $set('billing_frequency', 'monthly');
                                    $set('requires_invoice', true);
                                }
                            }),
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
                            ->required()
                            ->visible(fn ($get) => $get('order_type') === 'b2b'),
                        
                        Forms\Components\Toggle::make('requires_invoice')
                            ->label('Requires Invoice')
                            ->helperText('Uncheck for farmer\'s market orders that don\'t need invoicing')
                            ->default(true),
                    ])
                    ->visible(fn ($get) => in_array($get('order_type'), ['b2b', 'farmers_market']))
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
                        'b2b' => 'info',
                        'b2b_recurring' => 'info', // Legacy support
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