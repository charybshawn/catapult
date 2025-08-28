<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Exception;
use App\Models\OrderType;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DatePicker;
use App\Forms\Components\InvoiceOrderItems;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Services\RecurringOrderService;
use App\Models\Invoice;
use App\Services\StatusTransitionService;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\CalendarOrders;
use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Services\OrderPlanningService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive Filament resource for agricultural order management with complex
 * workflow integration, crop planning automation, and multi-tiered business operations.
 *
 * This resource manages the complete agricultural order lifecycle from initial customer
 * requests through crop production, harvest scheduling, delivery coordination, and
 * billing operations. It integrates deeply with agricultural workflows including
 * crop planning, recurring order automation, and invoice generation.
 *
 * @filament_resource Manages Order entities with full agricultural workflow integration
 * @business_domain Agricultural order processing and production planning
 * @related_models Order, Customer, OrderItem, Product, CropPlan, Invoice, OrderStatus
 * @workflow_support Complete order-to-delivery agricultural production chain
 * 
 * @agricultural_concepts
 * - Order lifecycle: Draft → Confirmed → Production → Harvest → Delivery → Completed
 * - Crop production integration: Orders drive crop planning and planting schedules
 * - Delivery timing: Harvest dates automatically calculated from delivery requirements
 * - Recurring orders: Template-based automatic order generation for regular customers
 * 
 * @complex_features
 * - Intelligent delivery date validation with crop production timing warnings
 * - Automatic harvest date calculation (4:00 PM day before delivery)
 * - Crop plan generation based on order items and delivery dates
 * - Recurring order template creation and automatic generation
 * - Wholesale price recalculation for customer-specific discounts
 * - Consolidated invoicing for multiple orders to same customer
 * - Status transition validation with agricultural workflow protection
 * 
 * @business_workflows
 * 1. Order Creation: Customer selection, delivery scheduling, item configuration
 * 2. Production Planning: Crop plan generation, planting schedule coordination
 * 3. Status Management: Workflow-validated status transitions with business rules
 * 4. Harvest Coordination: Timing calculations, crop readiness tracking
 * 5. Delivery Management: Customer notifications, fulfillment tracking
 * 6. Billing Operations: Invoice creation, consolidated billing, payment tracking
 * 
 * @filament_advanced_features
 * - Dynamic form sections with conditional visibility (recurring settings)
 * - Reactive field updates with agricultural context validation
 * - Complex table filters for production and payment status
 * - Sophisticated bulk operations with business rule validation
 * - Modal-based workflow actions with agricultural context
 * - Real-time status updates with agricultural workflow integration
 * 
 * @performance_considerations
 * - Eager loading of complex relationships (customer, items, status, plans)
 * - Optimized queries for large order datasets with session persistence
 * - Efficient bulk operations with batched database updates
 * - Cached calculations for order totals and production requirements
 * 
 * @business_intelligence
 * - Days until delivery calculation with urgency indicators
 * - Payment status tracking with automated reconciliation
 * - Crop production requirement analysis and visualization
 * - Recurring order pattern analysis and template optimization
 */
class OrderResource extends BaseResource
{
    protected static ?string $model = Order::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Orders';

    protected static string | \UnitEnum | null $navigationGroup = 'Orders & Sales';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    /**
     * Configure base query to show only executable orders, excluding recurring templates.
     *
     * Filters the order listing to show regular orders and orders generated from
     * recurring templates, but excludes the template records themselves from the
     * main order management interface to prevent confusion between templates
     * and actual production orders.
     *
     * @return Builder Query filtered for executable agricultural orders
     * @business_logic Separates recurring templates from production order management
     * @agricultural_workflow Templates are managed separately from production orders
     * @ui_clarity Prevents template confusion in main order management interface
     */
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

    /**
     * Create comprehensive order form with agricultural workflow integration.
     *
     * Builds a sophisticated form supporting the complete agricultural order
     * lifecycle from customer selection through delivery scheduling, with
     * intelligent field interactions, automatic calculations, and business
     * rule validation tailored for agricultural production workflows.
     *
     * @param Schema $schema Filament schema builder instance
     * @return Schema Complete order form with agricultural workflow integration
     * 
     * @form_sections
     * - Order Type: Recurring order configuration and template settings
     * - Order Information: Customer, delivery dates, status management
     * - Recurring Settings: Frequency, dates for automated order generation
     * - Billing & Invoicing: Payment terms and invoice requirements
     * - Order Items: Product selection with agricultural context
     * - Additional Information: Notes and special instructions
     * 
     * @agricultural_intelligence
     * - Delivery date validation against crop production timelines
     * - Automatic harvest date calculation (4:00 PM day before delivery)
     * - Crop production timeline warnings for unrealistic delivery dates
     * - Order type-specific status defaults for workflow optimization
     * 
     * @reactive_behavior
     * - Recurring settings visibility based on order type selection
     * - Customer type-specific discount field display
     * - Delivery/harvest date automatic calculation and validation
     * - Status helper text with agricultural workflow context
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order Type')
                ->schema([
                    Toggle::make('is_recurring')
                        ->label('Make this a recurring order')
                        ->helperText('When enabled, this order will generate new orders automatically')
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (! $state) {
                                $set('recurring_frequency', null);
                                $set('recurring_start_date', null);
                                $set('recurring_end_date', null);
                            }
                        }),
                ]),

            Section::make('Order Information')
                ->schema([
                    Select::make('customer_id')
                        ->label('Customer')
                        ->options(function () {
                            return Customer::all()
                                ->mapWithKeys(function ($customer) {
                                    $display = $customer->business_name
                                        ? $customer->business_name.' ('.$customer->contact_name.')'
                                        : $customer->contact_name;

                                    return [$customer->id => $display];
                                });
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('contact_name')
                                ->label('Contact Name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('business_name')
                                ->label('Business Name')
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label('Email Address')
                                ->email()
                                ->required()
                                ->unique(Customer::class, 'email'),
                            TextInput::make('cc_email')
                                ->label('CC Email Address')
                                ->email()
                                ->maxLength(255),
                            TextInput::make('phone')
                                ->label('Phone Number')
                                ->tel()
                                ->maxLength(20),
                            Select::make('customer_type')
                                ->label('Customer Type')
                                ->options([
                                    'retail' => 'Retail',
                                    'wholesale' => 'Wholesale',
                                ])
                                ->default('retail')
                                ->required()
                                ->reactive(),
                            TextInput::make('wholesale_discount_percentage')
                                ->label('Wholesale Discount %')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01)
                                ->suffix('%')
                                ->visible(fn (Get $get) => $get('customer_type') === 'wholesale'),
                            Textarea::make('address')
                                ->label('Address')
                                ->rows(3),
                            TextInput::make('city')
                                ->label('City')
                                ->maxLength(255),
                            TextInput::make('province')
                                ->label('Province')
                                ->maxLength(255),
                            TextInput::make('postal_code')
                                ->label('Postal Code')
                                ->maxLength(20),
                            TextInput::make('country')
                                ->label('Country')
                                ->maxLength(255)
                                ->default('Canada'),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            return Customer::create($data)->getKey();
                        })
                        ->helperText('Select existing customer or create a new one'),
                    DateTimePicker::make('delivery_date')
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
                                } catch (Exception $e) {
                                    // If parsing fails, don't update harvest_date
                                    Log::error('Failed to parse delivery date: '.$e->getMessage());
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
                                } catch (Exception $e) {
                                    // Ignore parse errors
                                }
                            }

                            return $helperText;
                        })
                        ->visible(fn (Get $get) => ! $get('is_recurring')),
                    DateTimePicker::make('harvest_date')
                        ->label('Harvest Date')
                        ->helperText('When this order should be harvested (automatically set to evening before delivery, but can be overridden)')
                        ->required(fn (Get $get) => ! $get('is_recurring'))
                        ->visible(fn (Get $get) => ! $get('is_recurring')),
                    Select::make('order_type_id')
                        ->label('Order Type')
                        ->relationship('orderType', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(function () {
                            // Set default to 'website' order type
                            $websiteType = OrderType::where('code', 'website')->first();

                            return $websiteType?->id;
                        })
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, $record) {
                            // Auto-set status based on order type when creating
                            if (! $record && $state) {
                                $orderType = OrderType::find($state);
                                if ($orderType) {
                                    // Set appropriate default status based on order type
                                    $defaultStatusCode = match ($orderType->code) {
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
                    Select::make('status_id')
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
                            if (! $state) {
                                return 'Select a status for this order';
                            }
                            $status = OrderStatus::find($state);
                            if (! $status) {
                                return null;
                            }

                            $help = "Stage: {$status->stage_display}";
                            if ($status->description) {
                                $help .= " - {$status->description}";
                            }
                            if ($status->requires_crops) {
                                $help .= ' (Requires crop production)';
                            }
                            if ($status->is_final) {
                                $help .= ' (Final status - cannot be changed)';
                            }
                            if (! $status->allows_modifications) {
                                $help .= ' (Order locked for modifications)';
                            }

                            return $help;
                        })
                        ->disabled(fn ($record) => $record && $record->status && ($record->status->code === 'template' || $record->status->is_final)),
                ])
                ->columns(2),

            Section::make('Recurring Settings')
                ->schema([
                    Grid::make(3)
                        ->schema([
                            Select::make('recurring_frequency')
                                ->label('Frequency')
                                ->options([
                                    'weekly' => 'Weekly',
                                    'biweekly' => 'Biweekly',
                                    'monthly' => 'Monthly',
                                ])
                                ->required(),

                            DatePicker::make('recurring_start_date')
                                ->label('Start Date')
                                ->helperText('First occurrence date')
                                ->required(),

                            DatePicker::make('recurring_end_date')
                                ->label('End Date (Optional)')
                                ->helperText('Leave empty for indefinite'),
                        ]),
                ])
                ->visible(fn ($get) => $get('is_recurring')),

            Section::make('Billing & Invoicing')
                ->schema([
                    Select::make('billing_frequency')
                        ->label('Billing Frequency')
                        ->options([
                            'immediate' => 'Immediate',
                            'weekly' => 'Weekly',
                            'biweekly' => 'Bi-weekly',
                            'monthly' => 'Monthly',
                            'quarterly' => 'Quarterly',
                        ])
                        ->default('immediate')
                        ->required(),

                    Toggle::make('requires_invoice')
                        ->label('Requires Invoice')
                        ->default(true),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            Section::make('Order Items')
                ->schema([
                    InvoiceOrderItems::make('orderItems')
                        ->label('Items')
                        ->productOptions(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->toArray())
                        ->required()
                        ->rules([
                            'array',
                            'min:1',
                            function () {
                                return function (string $attribute, $value, Closure $fail) {
                                    if (! is_array($value)) {
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
                                        if (! isset($item['quantity']) || $item['quantity'] === null || $item['quantity'] === '') {
                                            $fail('Item '.($index + 1).': Quantity is required.');
                                        } else {
                                            // Handle both string and numeric values
                                            $qty = is_string($item['quantity']) ? trim($item['quantity']) : $item['quantity'];
                                            if (! is_numeric($qty) || floatval($qty) <= 0) {
                                                $fail('Item '.($index + 1).': Quantity must be a number greater than 0.');
                                            }
                                        }

                                        if (! isset($item['price']) || $item['price'] === null || $item['price'] === '') {
                                            $fail('Item '.($index + 1).': Price is required.');
                                        } elseif (! is_numeric($item['price']) || floatval($item['price']) < 0) {
                                            $fail('Item '.($index + 1).': Price must be 0 or greater.');
                                        }

                                        if (! empty($item['item_id'])) {
                                            $hasValidItems = true;
                                        }
                                    }

                                    if (! $hasValidItems) {
                                        $fail('Order must have at least one valid item.');
                                    }
                                };
                            },
                        ]),
                ]),

            Section::make('Additional Information')
                ->schema([
                    Textarea::make('notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    /**
     * Configure comprehensive order table with agricultural workflow visualization.
     *
     * Creates a sophisticated table interface for agricultural order management
     * with visual indicators for crop production requirements, payment status,
     * delivery timing, and workflow state progression. Includes advanced filtering
     * and bulk operations tailored for agricultural business operations.
     *
     * @param Table $table Filament table builder instance
     * @return Table Complete order table with agricultural workflow features
     * 
     * @table_features
     * - Customer display with business/contact name intelligence
     * - Order type badges with agricultural workflow color coding
     * - Inline status updates with transition validation
     * - Days until delivery with urgency color indicators
     * - Payment status visualization with reconciliation data
     * - Crop production requirement indicators
     * - Recurring order template identification
     * 
     * @agricultural_visualization
     * - "Needs Growing" icon column for crop production requirements
     * - Delivery timing with color-coded urgency levels
     * - Harvest date display for production planning
     * - Order total calculation with dynamic pricing
     * 
     * @advanced_filtering
     * - Status and stage-based filtering for workflow management
     * - Crop production requirement filtering
     * - Payment status filtering with complex payment reconciliation
     * - Customer type and order source filtering
     * - Date range filtering for harvest and delivery planning
     * 
     * @performance_optimization
     * - Session-persistent filters and searches for workflow efficiency
     * - Eager loading of complex relationships prevents N+1 queries
     * - Optimized column calculations for large order datasets
     */
    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['customer.customerType', 'orderItems', 'invoice', 'orderType', 'status']))
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->columns([
                TextColumn::make('id')
                    ->label('Order ID')
                    ->sortable(),
                TextColumn::make('customer.contact_name')
                    ->label('Customer')
                    ->formatStateUsing(function ($state, Order $record) {
                        if (! $record->customer) {
                            return '—';
                        }

                        $contactName = $record->customer->contact_name ?: 'No name';

                        if ($record->customer->business_name) {
                            return $record->customer->business_name.' ('.$contactName.')';
                        }

                        return $contactName;
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('customer', function ($q) use ($search) {
                            $q->where('contact_name', 'like', "%{$search}%")
                                ->orWhere('business_name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('order_type_display')
                    ->label('Type')
                    ->badge()
                    ->color(fn (Order $record): string => match ($record->orderType?->code) {
                        'website' => 'success',
                        'farmers_market' => 'warning',
                        'b2b' => 'info',
                        default => 'gray',
                    }),
                SelectColumn::make('status_id')
                    ->label('Status')
                    ->options(function () {
                        return OrderStatus::getOptionsForDropdown(false, false);
                    })
                    ->selectablePlaceholder(false)
                    ->disabled(fn ($record): bool => $record instanceof Order && ($record->status?->code === 'template' || $record->status?->is_final)
                    )
                    ->rules([
                        fn ($record): Closure => function (string $attribute, $value, Closure $fail) use ($record) {
                            if (! ($record instanceof Order) || ! $record->status) {
                                return;
                            }

                            $newStatus = OrderStatus::find($value);
                            if (! $newStatus) {
                                $fail('Invalid status selected.');

                                return;
                            }

                            if (! OrderStatus::isValidTransition($record->status->code, $newStatus->code)) {
                                $fail("Cannot transition from {$record->status->name} to {$newStatus->name}.");
                            }
                        },
                    ])
                    ->afterStateUpdated(function ($record, $state) {
                        if (! ($record instanceof Order)) {
                            return;
                        }
                        $oldStatus = $record->status;
                        $newStatus = OrderStatus::find($state);

                        if (! $newStatus) {
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
                                'changed_by' => auth()->user()->name ?? 'System',
                            ])
                            ->log('Unified order status changed');

                        Notification::make()
                            ->title('Order Status Updated')
                            ->body("Order #{$record->id} status changed to: {$newStatus->name} ({$newStatus->stage_display})")
                            ->success()
                            ->send();
                    }),
                TextColumn::make('status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Order $record): string => $record->status?->badge_color ?? 'gray')
                    ->formatStateUsing(fn (string $state, Order $record): string => $state.' ('.$record->status?->stage_display.')'
                    )
                    ->visible(false), // Hidden by default, can be toggled
                IconColumn::make('requiresCrops')
                    ->label('Needs Growing')
                    ->boolean()
                    ->getStateUsing(fn (Order $record) => $record->requiresCropProduction())
                    ->trueIcon('heroicon-o-sun')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn (Order $record) => $record->requiresCropProduction() ? 'This order requires crop production' : 'No crops needed'),
                TextColumn::make('paymentStatus')
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
                TextColumn::make('daysUntilDelivery')
                    ->label('Delivery In')
                    ->getStateUsing(function (Order $record) {
                        if (! $record->delivery_date) {
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
                            return $days.' days';
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
                TextColumn::make('parent_template')
                    ->label('Template')
                    ->getStateUsing(fn (Order $record) => $record->parent_recurring_order_id ? "Template #{$record->parent_recurring_order_id}" : null)
                    ->placeholder('Regular Order')
                    ->badge()
                    ->color('info')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('totalAmount')
                    ->label('Total')
                    ->money('USD')
                    ->getStateUsing(fn (Order $record) => $record->totalAmount()),
                TextColumn::make('harvest_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delivery_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status_id')
                    ->label('Status')
                    ->options(function () {
                        return OrderStatus::getOptionsForDropdown(false, true);
                    })
                    ->searchable(),
                SelectFilter::make('stage')
                    ->label('Stage')
                    ->options([
                        OrderStatus::STAGE_PRE_PRODUCTION => 'Pre-Production',
                        OrderStatus::STAGE_PRODUCTION => 'Production',
                        OrderStatus::STAGE_FULFILLMENT => 'Fulfillment',
                        OrderStatus::STAGE_FINAL => 'Final',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['value'])) {
                            return $query->whereHas('status', function ($q) use ($data) {
                                $q->where('stage', $data['value']);
                            });
                        }

                        return $query;
                    }),
                TernaryFilter::make('requires_crops')
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
                TernaryFilter::make('payment_status')
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
                TernaryFilter::make('parent_recurring_order_id')
                    ->label('Order Source')
                    ->nullable()
                    ->placeholder('All orders')
                    ->trueLabel('Generated from template')
                    ->falseLabel('Manual orders only'),
                SelectFilter::make('customer_type')
                    ->options([
                        'retail' => 'Retail',
                        'wholesale' => 'Wholesale',
                    ]),
                Filter::make('harvest_date')
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
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View order details'),
                    EditAction::make()
                        ->tooltip('Edit order'),
                    Action::make('generate_next_recurring')
                        ->label('Generate Next Order')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->visible(fn (Order $record): bool => $record->status?->code === 'template' &&
                            $record->is_recurring
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Generate Next Recurring Order')
                        ->modalDescription(fn (Order $record) => "This will create the next order in the recurring series for {$record->customer->contact_name}."
                        )
                        ->action(function (Order $record) {
                            try {
                                $recurringOrderService = app(RecurringOrderService::class);
                                $newOrder = $recurringOrderService->generateNextOrder($record);

                                if ($newOrder) {
                                    Notification::make()
                                        ->title('Recurring Order Generated')
                                        ->body("Order #{$newOrder->id} has been created successfully.")
                                        ->success()
                                        ->actions([
                                            Action::make('view')
                                                ->label('View Order')
                                                ->url(route('filament.admin.resources.orders.edit', ['record' => $newOrder->id])),
                                        ])
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('No Order Generated')
                                        ->body('No new order was generated. It may not be time for the next recurring order yet.')
                                        ->warning()
                                        ->send();
                                }
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Error Generating Order')
                                    ->body('Failed to generate recurring order: '.$e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('recalculate_prices')
                        ->label('Recalculate Prices')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->visible(fn (Order $record): bool => $record->status?->code !== 'template' &&
                            $record->status?->code !== 'cancelled' &&
                            ! $record->status?->is_final &&
                            $record->customer->isWholesaleCustomer() &&
                            $record->orderItems->isNotEmpty()
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Recalculate Order Prices')
                        ->modalDescription(function (Order $record) {
                            $currentTotal = $record->totalAmount();
                            $discount = $record->customer->wholesale_discount_percentage ?? 0;

                            return "This will recalculate all item prices using the current wholesale discount ({$discount}%). Current total: $".number_format($currentTotal, 2);
                        })
                        ->action(function (Order $record) {
                            try {
                                $oldTotal = $record->totalAmount();
                                $updatedItems = 0;

                                foreach ($record->orderItems as $item) {
                                    if (! $item->product || ! $item->price_variation_id) {
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
                                        ->body("Updated {$updatedItems} items. New total: $".number_format($newTotal, 2).' (saved $'.number_format($difference, 2).')')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('No Changes Needed')
                                        ->body('All prices are already up to date.')
                                        ->info()
                                        ->send();
                                }
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Error Recalculating Prices')
                                    ->body('Failed to recalculate prices: '.$e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('generate_crop_plans')
                        ->label('Generate Crop Plans')
                        ->icon('heroicon-o-sparkles')
                        ->color('success')
                        ->visible(fn (Order $record): bool => $record->requiresCropProduction() &&
                            ! $record->isInFinalState() &&
                            ! $record->cropPlans()->exists()
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Generate Crop Plans')
                        ->modalDescription(fn (Order $record) => 'This will analyze the order items and generate crop plans based on the delivery date.'
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

                    Action::make('convert_to_invoice')
                        ->label('Create Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->visible(fn (Order $record): bool => $record->status?->code !== 'template' &&
                            $record->requires_invoice &&
                            ! $record->invoice // Only show if no invoice exists yet
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Create Invoice')
                        ->modalDescription(fn (Order $record) => "This will create an invoice for Order #{$record->id} totaling $".number_format($record->totalAmount(), 2).'.'
                        )
                        ->action(function (Order $record) {
                            try {
                                $invoice = Invoice::createFromOrder($record);

                                Notification::make()
                                    ->title('Invoice Created')
                                    ->body("Invoice #{$invoice->id} has been created successfully.")
                                    ->success()
                                    ->actions([
                                        Action::make('view')
                                            ->label('View Invoice')
                                            ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id])),
                                    ])
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Error Creating Invoice')
                                    ->body('Failed to create invoice: '.$e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('convert_to_recurring')
                        ->label('Convert to Recurring')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->visible(fn (Order $record): bool => ! $record->is_recurring &&
                            $record->status?->code !== 'template' &&
                            $record->parent_recurring_order_id === null && // Not generated from recurring
                            $record->customer &&
                            $record->orderItems()->count() > 0
                        )
                        ->schema([
                            Section::make('Recurring Settings')
                                ->schema([
                                    Select::make('frequency')
                                        ->label('Frequency')
                                        ->options([
                                            'weekly' => 'Weekly',
                                            'biweekly' => 'Bi-weekly',
                                            'monthly' => 'Monthly',
                                        ])
                                        ->default('weekly')
                                        ->required()
                                        ->reactive(),

                                    TextInput::make('interval')
                                        ->label('Interval (weeks)')
                                        ->helperText('For bi-weekly: enter 2 for every 2 weeks')
                                        ->numeric()
                                        ->default(2)
                                        ->minValue(1)
                                        ->maxValue(12)
                                        ->visible(fn (Get $get) => $get('frequency') === 'biweekly'),

                                    DatePicker::make('start_date')
                                        ->label('Start Date')
                                        ->default(now()->addWeek())
                                        ->required()
                                        ->minDate(now()),

                                    DatePicker::make('end_date')
                                        ->label('End Date (Optional)')
                                        ->helperText('Leave blank for indefinite recurring')
                                        ->minDate(fn (Get $get) => $get('start_date')),
                                ])
                                ->columns(2),
                        ])
                        ->modalHeading('Convert Order to Recurring Template')
                        ->modalDescription(fn (Order $record) => "This will convert Order #{$record->id} into a recurring order template that will automatically generate new orders."
                        )
                        ->action(function (Order $record, array $data) {
                            try {
                                $recurringOrderService = app(RecurringOrderService::class);
                                $convertedOrder = $recurringOrderService->convertToRecurringTemplate($record, $data);

                                Notification::make()
                                    ->title('Order Converted Successfully')
                                    ->body("Order #{$record->id} has been converted to a recurring template.")
                                    ->success()
                                    ->actions([
                                        Action::make('view')
                                            ->label('View Template')
                                            ->url(route('filament.admin.resources.recurring-orders.edit', ['record' => $convertedOrder->id])),
                                    ])
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Conversion Failed')
                                    ->body('Failed to convert order to recurring: '.$e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('transition_status')
                        ->label('Change Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->visible(fn (Order $record): bool => ! $record->isInFinalState() &&
                            $record->status?->code !== 'template'
                        )
                        ->schema(function (Order $record) {
                            $validStatuses = app(StatusTransitionService::class)
                                ->getValidNextStatuses($record);

                            if ($validStatuses->isEmpty()) {
                                return [
                                    Placeholder::make('no_transitions')
                                        ->label('')
                                        ->content('No valid status transitions available for this order.'),
                                ];
                            }

                            return [
                                Select::make('new_status')
                                    ->label('New Status')
                                    ->options($validStatuses->pluck('name', 'code'))
                                    ->required()
                                    ->helperText('Select the new status for this order'),
                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->placeholder('Optional notes about this status change')
                                    ->rows(3),
                            ];
                        })
                        ->action(function (Order $record, array $data) {
                            $result = $record->transitionTo($data['new_status'], [
                                'manual' => true,
                                'notes' => $data['notes'] ?? null,
                                'user_id' => auth()->id(),
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

                    DeleteAction::make()
                        ->tooltip('Delete order'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('create_consolidated_invoice')
                        ->label('Create Consolidated Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Create Consolidated Invoice')
                        ->modalDescription('This will create a single invoice for all selected orders.')
                        ->form([
                            DatePicker::make('issue_date')
                                ->label('Issue Date')
                                ->default(now())
                                ->required(),
                            DatePicker::make('due_date')
                                ->label('Due Date')
                                ->default(now()->addDays(30))
                                ->required(),
                            Textarea::make('notes')
                                ->label('Invoice Notes')
                                ->placeholder('Additional notes for the consolidated invoice...')
                                ->rows(3),
                        ])
                        ->action(function (Collection $records, array $data) {
                            // Validate that orders can be consolidated
                            $errors = self::validateOrdersForConsolidation($records);

                            if (! empty($errors)) {
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
                                    ->body("Invoice #{$invoice->invoice_number} created for {$records->count()} orders totaling $".number_format($invoice->total_amount, 2).'.')
                                    ->success()
                                    ->actions([
                                        Action::make('view')
                                            ->label('View Invoice')
                                            ->url(route('filament.admin.resources.invoices.edit', ['record' => $invoice->id])),
                                    ])
                                    ->send();
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Error Creating Invoice')
                                    ->body('Failed to create consolidated invoice: '.$e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('bulk_status_update')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Status Update')
                        ->modalDescription(function (Collection $records) {
                            $finalOrders = $records->filter(fn ($order) => $order->isInFinalState());
                            $templateOrders = $records->filter(fn ($order) => $order->status?->code === 'template');

                            $warnings = [];
                            if ($finalOrders->isNotEmpty()) {
                                $warnings[] = "{$finalOrders->count()} orders in final state will be skipped.";
                            }
                            if ($templateOrders->isNotEmpty()) {
                                $warnings[] = "{$templateOrders->count()} template orders will be skipped.";
                            }

                            $eligibleCount = $records->count() - $finalOrders->count() - $templateOrders->count();

                            return "Update status for {$eligibleCount} orders.".
                                   (! empty($warnings) ? "\n\nWarnings:\n".implode("\n", $warnings) : '');
                        })
                        ->form([
                            Select::make('new_status')
                                ->label('New Status')
                                ->options(OrderStatus::active()
                                    ->notFinal()
                                    ->where('code', '!=', 'template')
                                    ->pluck('name', 'code'))
                                ->required()
                                ->helperText('Select the new status for all eligible orders'),
                            Textarea::make('notes')
                                ->label('Notes')
                                ->placeholder('Optional notes about this bulk status change')
                                ->rows(3),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $statusService = app(StatusTransitionService::class);

                            // Filter out ineligible orders
                            $eligibleOrders = $records->filter(function ($order) {
                                return ! $order->isInFinalState() &&
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
                                    'user_id' => auth()->id(),
                                ]
                            );

                            $successCount = count($result['successful']);
                            $failedCount = count($result['failed']);

                            if ($successCount > 0) {
                                Notification::make()
                                    ->title('Status Update Complete')
                                    ->body("Successfully updated {$successCount} orders.".
                                           ($failedCount > 0 ? " {$failedCount} orders failed." : ''))
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Status Update Failed')
                                    ->body('Failed to update any orders. Check the logs for details.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
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
     * Validate agricultural orders for consolidated invoice creation.
     *
     * Performs comprehensive validation to ensure selected orders can be
     * consolidated into a single invoice, checking for agricultural business
     * rule compliance, customer consistency, and billing requirement alignment.
     * Essential for maintaining billing integrity in agricultural operations.
     *
     * @param Collection $orders Collection of orders to validate for consolidation
     * @return array Array of validation errors, empty if consolidation is valid
     * 
     * @validation_rules
     * - No recurring order templates (only actual production orders)
     * - All orders must require invoices (billing configuration consistency)
     * - No orders with existing invoices (prevents duplicate billing)
     * - Single customer requirement (consolidated billing constraint)
     * - Minimum 2 orders for consolidation efficiency
     * 
     * @agricultural_business_logic
     * - Template orders excluded from billing workflows
     * - Customer consolidation supports bulk agricultural sales
     * - Prevents billing confusion in complex agricultural order systems
     */
    protected static function validateOrdersForConsolidation(Collection $orders): array
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
     * Create consolidated invoice for multiple agricultural orders.
     *
     * Generates a single invoice encompassing multiple orders for the same
     * customer, calculating total amounts, determining billing periods from
     * delivery dates, and linking all orders to the consolidated invoice.
     * Supports efficient billing for regular agricultural customers.
     *
     * @param Collection $orders Validated orders for consolidation
     * @param array $data Invoice configuration data (dates, notes)
     * @return Invoice Created consolidated invoice with order linkage
     * 
     * @consolidation_logic
     * - Calculates total amount across all orders
     * - Determines billing period from delivery date range
     * - Generates unique invoice number for consolidated billing
     * - Links all orders to consolidated invoice record
     * 
     * @agricultural_context
     * - Billing periods based on agricultural delivery schedules
     * - Order count tracking for agricultural business intelligence
     * - Supports seasonal billing patterns in agricultural sales
     * - Maintains order traceability within consolidated billing
     */
    protected static function createConsolidatedInvoice(Collection $orders, array $data): Invoice
    {
        // Calculate total amount
        $totalAmount = $orders->sum(function ($order) {
            return $order->totalAmount();
        });

        // Get billing period from order dates
        $deliveryDates = $orders->pluck('delivery_date')->map(fn ($date) => Carbon::parse($date))->sort();
        $billingPeriodStart = $deliveryDates->first()->startOfMonth();
        $billingPeriodEnd = $deliveryDates->last()->endOfMonth();

        // Generate invoice number
        $invoiceNumber = Invoice::generateInvoiceNumber();

        // Create the consolidated invoice
        $invoice = Invoice::create([
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
            'notes' => $data['notes'] ?? "Consolidated invoice for {$orders->count()} orders: ".$orders->pluck('id')->implode(', '),
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
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
            'calendar' => CalendarOrders::route('/calendar'),
        ];
    }
}
