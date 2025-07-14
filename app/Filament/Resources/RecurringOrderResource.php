<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringOrderResource\Pages;
use App\Filament\Resources\RecurringOrderResource\RelationManagers;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RecurringOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Recurring Orders';
    protected static ?string $modelLabel = 'Recurring Order';
    protected static ?string $pluralModelLabel = 'Recurring Orders';
    protected static ?string $navigationGroup = 'Orders & Sales';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'id';
    
    // Only show recurring order templates
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_recurring', true)
            ->whereNull('parent_recurring_order_id'); // Only templates, not generated orders
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer & Type')
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
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                // Map customer_type string to customer_type_id
                                if (isset($data['customer_type'])) {
                                    $customerType = \App\Models\CustomerType::where('code', $data['customer_type'])->first();
                                    if ($customerType) {
                                        $data['customer_type_id'] = $customerType->id;
                                    }
                                    unset($data['customer_type']); // Remove the string field
                                }
                                
                                return Customer::create($data)->getKey();
                            }),
                            
                        Forms\Components\Select::make('order_type_id')
                            ->label('Order Type')
                            ->relationship('orderType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Auto-set billing frequency based on order type
                                $orderType = \App\Models\OrderType::find($state);
                                if ($orderType) {
                                    if ($orderType->code === 'farmers_market') {
                                        $set('billing_frequency', 'immediate');
                                        $set('requires_invoice', false);
                                    } elseif ($orderType->code === 'website_order') {
                                        $set('billing_frequency', 'immediate');
                                        $set('requires_invoice', true);
                                    } elseif ($orderType->code === 'b2b') {
                                        $set('billing_frequency', 'monthly');
                                        $set('requires_invoice', true);
                                    }
                                }
                            }),
                            
                        Forms\Components\DatePicker::make('recurring_start_date')
                            ->label('Start Date')
                            ->default(now())
                            ->required(),
                            
                        Forms\Components\DatePicker::make('recurring_end_date')
                            ->label('End Date (Optional)')
                            ->helperText('Leave blank for indefinite recurring'),
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
                            ->visible(function ($get) {
                                $orderTypeId = $get('order_type_id');
                                if (!$orderTypeId) return false;
                                $orderType = \App\Models\OrderType::find($orderTypeId);
                                return $orderType?->code === 'b2b';
                            }),
                        
                        Forms\Components\Toggle::make('requires_invoice')
                            ->label('Requires Invoice')
                            ->helperText('Uncheck for farmer\'s market orders that don\'t need invoicing')
                            ->default(true),
                    ])
                    ->visible(function ($get) {
                        $orderTypeId = $get('order_type_id');
                        if (!$orderTypeId) return false;
                        $orderType = \App\Models\OrderType::find($orderTypeId);
                        return in_array($orderType?->code, ['b2b', 'farmers_market']);
                    })
                    ->columns(2),
                
                Forms\Components\Section::make('Recurring Schedule')
                    ->schema([
                        Forms\Components\Select::make('recurring_frequency')
                            ->label(function ($get) {
                                $orderTypeId = $get('order_type_id');
                                if (!$orderTypeId) return 'Generation Frequency';
                                $orderType = \App\Models\OrderType::find($orderTypeId);
                                return $orderType?->code === 'b2b' ? 'Delivery Frequency' : 'Generation Frequency';
                            })
                            ->helperText(function ($get) {
                                $orderTypeId = $get('order_type_id');
                                if (!$orderTypeId) return 'How often to generate new orders';
                                $orderType = \App\Models\OrderType::find($orderTypeId);
                                return $orderType?->code === 'b2b'
                                    ? 'How often to create new delivery orders (independent of billing frequency)'
                                    : 'How often to generate new orders';
                            })
                            ->options([
                                'weekly' => 'Weekly',
                                'biweekly' => 'Bi-weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('weekly')
                            ->reactive()
                            ->required(),
                        
                        Forms\Components\TextInput::make('recurring_interval')
                            ->label('Interval (weeks)')
                            ->helperText('For bi-weekly: enter 2 for every 2 weeks')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->maxValue(12)
                            ->visible(fn ($get) => $get('recurring_frequency') === 'biweekly'),
                            
                        Forms\Components\TextInput::make('start_delay_weeks')
                            ->label('Start Delay (weeks)')
                            ->helperText('Number of weeks to wait before generating the first order')
                            ->numeric()
                            ->default(2)
                            ->minValue(0)
                            ->maxValue(12)
                            ->suffix('weeks'),
                            
                        Forms\Components\Toggle::make('is_recurring_active')
                            ->label('Active')
                            ->helperText('Uncheck to pause recurring order generation')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Schedule Days')
                    ->schema([
                        Forms\Components\Select::make('harvest_day')
                            ->label('Harvest Day')
                            ->options([
                                'sunday' => 'Sunday',
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday', 
                                'wednesday' => 'Wednesday',
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                            ])
                            ->default('monday')
                            ->required(),
                            
                        Forms\Components\Select::make('delivery_day')
                            ->label('Delivery Day')
                            ->options([
                                'sunday' => 'Sunday',
                                'monday' => 'Monday',
                                'tuesday' => 'Tuesday',
                                'wednesday' => 'Wednesday', 
                                'thursday' => 'Thursday',
                                'friday' => 'Friday',
                                'saturday' => 'Saturday',
                            ])
                            ->default('tuesday')
                            ->required(),
                    ])
                    ->description('Set the day of week for harvest and delivery')
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
                
                // Hidden fields to set defaults for recurring orders
                Forms\Components\Hidden::make('is_recurring')->default(true),
                Forms\Components\Hidden::make('status')->default('template'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'customer',
                'orderType',
                'generatedOrders'
            ]))
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Template ID')
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
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('order_type_display')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?Order $record): string => match ($record?->orderType?->code) {
                        'website_order' => 'success',
                        'farmers_market' => 'warning', 
                        'b2b' => 'info',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('recurring_frequency_display')
                    ->label('Delivery Frequency')
                    ->badge()
                    ->color('primary'),
                    
                Tables\Columns\TextColumn::make('billing_frequency_display')
                    ->label('Billing')
                    ->badge()
                    ->color(fn (?Order $record): string => match ($record?->billing_frequency) {
                        'immediate' => 'success',
                        'weekly' => 'info',
                        'monthly' => 'warning',
                        'quarterly' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                    
                Tables\Columns\IconColumn::make('is_recurring_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-play')
                    ->falseIcon('heroicon-o-pause')
                    ->trueColor('success')
                    ->falseColor('warning'),
                    
                Tables\Columns\TextColumn::make('generated_orders_count')
                    ->label('Generated')
                    ->numeric()
                    ->tooltip('Number of orders generated from this template'),
                    
                Tables\Columns\TextColumn::make('next_generation_date')
                    ->label('Next Generation')
                    ->dateTime()
                    ->placeholder('Not scheduled')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('recurring_start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('recurring_end_date')
                    ->label('End Date')
                    ->date()
                    ->placeholder('Indefinite')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_type_id')
                    ->label('Order Type')
                    ->relationship('orderType', 'name'),
                    
                Tables\Filters\TernaryFilter::make('is_recurring_active')
                    ->label('Status')
                    ->placeholder('All templates')
                    ->trueLabel('Active templates')
                    ->falseLabel('Paused templates'),
                    
                Tables\Filters\SelectFilter::make('recurring_frequency')
                    ->label('Delivery Frequency')
                    ->options([
                        'weekly' => 'Weekly',
                        'biweekly' => 'Bi-weekly',
                        'monthly' => 'Monthly',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->tooltip('Edit recurring order template'),
                    
                Tables\Actions\Action::make('generate_next')
                    ->label('Generate Orders')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->action(function (?Order $record): void {
                        if (!$record) return;
                        $generatedOrders = $record->generateRecurringOrdersCatchUp();
                        if (!empty($generatedOrders)) {
                            $count = count($generatedOrders);
                            $latestOrder = end($generatedOrders);
                            Notification::make()
                                ->title("Generated {$count} order(s) successfully")
                                ->body("Latest order #{$latestOrder->id} for {$latestOrder->delivery_date->format('M d, Y')}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Unable to generate orders')
                                ->body('Check template settings and end date')
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn (?Order $record): bool => $record?->is_recurring_active ?? false),
                    
                Tables\Actions\Action::make('pause')
                    ->label('Pause')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->action(fn (?Order $record) => $record?->update(['is_recurring_active' => false]))
                    ->requiresConfirmation()
                    ->visible(fn (?Order $record): bool => $record?->is_recurring_active ?? false),
                    
                Tables\Actions\Action::make('resume')
                    ->label('Resume')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(fn (?Order $record) => $record?->update(['is_recurring_active' => true]))
                    ->visible(fn (?Order $record): bool => !($record?->is_recurring_active ?? true)),
                    
                Tables\Actions\DeleteAction::make()
                    ->tooltip('Delete recurring order template'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_all_due')
                    ->label('Generate All Due Orders')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->action(function () {
                        $templates = Order::with(['customer', 'orderType', 'orderItems.product', 'orderItems.priceVariation', 'packagingTypes'])
                            ->where('is_recurring', true)
                            ->where('is_recurring_active', true)
                            ->get();
                        
                        $successCount = 0;
                        $errorCount = 0;
                        $skippedCount = 0;
                        $errors = [];
                        $generatedOrders = [];
                        
                        foreach ($templates as $template) {
                            try {
                                // Use the new catch-up method that properly advances dates
                                $newOrders = $template->generateRecurringOrdersCatchUp();
                                if (!empty($newOrders)) {
                                    foreach ($newOrders as $newOrder) {
                                        $successCount++;
                                        $generatedOrders[] = [
                                            'order_id' => $newOrder->id,
                                            'customer' => $template->customer->contact_name ?? 'Unknown',
                                            'delivery_date' => $newOrder->delivery_date->format('M d, Y')
                                        ];
                                    }
                                } else {
                                    $errorCount++;
                                    $customerName = $template->customer->contact_name ?? 'Unknown';
                                    $errors[] = "Template #{$template->id} ({$customerName}): Generation returned null";
                                }
                            } catch (\Exception $e) {
                                $errorCount++;
                                $customerName = $template->customer->contact_name ?? 'Unknown';
                                $errors[] = "Template #{$template->id} ({$customerName}): {$e->getMessage()}";
                            }
                        }
                        
                        // Build detailed feedback message
                        $title = "Recurring Order Generation Complete";
                        $body = "Processed {$templates->count()} templates:\n";
                        $body .= "✅ Successfully generated: {$successCount} orders\n";
                        $body .= "⏭️ Skipped (not due): {$skippedCount} templates\n";
                        $body .= "❌ Errors: {$errorCount} templates";
                        
                        if (count($generatedOrders) > 0) {
                            $body .= "\n\nGenerated Orders:\n";
                            foreach ($generatedOrders as $order) {
                                $body .= "• Order #{$order['order_id']} - {$order['customer']} (Delivery: {$order['delivery_date']})\n";
                            }
                        }
                        
                        if (count($errors) > 0) {
                            $body .= "\n\nErrors:\n";
                            foreach ($errors as $error) {
                                $body .= "• {$error}\n";
                            }
                        }
                        
                        $notificationType = $errorCount > 0 ? 'warning' : ($successCount > 0 ? 'success' : 'info');
                        
                        Notification::make()
                            ->title($title)
                            ->body($body)
                            ->{$notificationType}()
                            ->persistent()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generate All Due Orders')
                    ->modalDescription('This will generate orders for all active recurring templates that are due for generation.')
                    ->modalSubmitActionLabel('Generate Orders'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('generate_selected')
                        ->label('Generate Orders from Selected')
                        ->icon('heroicon-o-plus-circle')
                        ->color('primary')
                        ->action(function (Collection $records) {
                            $successCount = 0;
                            $errorCount = 0;
                            $errors = [];
                            $generatedOrders = [];
                            
                            foreach ($records as $template) {
                                try {
                                    $newOrders = $template->generateRecurringOrdersCatchUp();
                                    if (!empty($newOrders)) {
                                        foreach ($newOrders as $newOrder) {
                                            $successCount++;
                                            $generatedOrders[] = [
                                                'order_id' => $newOrder->id,
                                                'customer' => $template->customer->contact_name ?? 'Unknown',
                                                'delivery_date' => $newOrder->delivery_date->format('M d, Y')
                                            ];
                                        }
                                    } else {
                                        $errorCount++;
                                        $errors[] = "Template #{$template->id}: Generation returned null";
                                    }
                                } catch (\Exception $e) {
                                    $errorCount++;
                                    $errors[] = "Template #{$template->id}: {$e->getMessage()}";
                                }
                            }
                            
                            $title = "Bulk Order Generation Complete";
                            $body = "Processed {$records->count()} templates:\n";
                            $body .= "✅ Successfully generated: {$successCount} orders\n";
                            $body .= "❌ Errors: {$errorCount} templates";
                            
                            if (count($generatedOrders) > 0) {
                                $body .= "\n\nGenerated Orders:\n";
                                foreach ($generatedOrders as $order) {
                                    $body .= "• Order #{$order['order_id']} - {$order['customer']} (Delivery: {$order['delivery_date']})\n";
                                }
                            }
                            
                            if (count($errors) > 0) {
                                $body .= "\n\nErrors:\n";
                                foreach ($errors as $error) {
                                    $body .= "• {$error}\n";
                                }
                            }
                            
                            $notificationType = $errorCount > 0 ? 'warning' : 'success';
                            
                            Notification::make()
                                ->title($title)
                                ->body($body)
                                ->{$notificationType}()
                                ->persistent()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    
                    Tables\Actions\BulkAction::make('pause_all')
                        ->label('Pause All')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(fn (Collection $records) => $records->each->update(['is_recurring_active' => false]))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                        
                    Tables\Actions\BulkAction::make('resume_all')
                        ->label('Resume All')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_recurring_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Generated orders relation will be added after creating the relation manager
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecurringOrders::route('/'),
            'create' => Pages\CreateRecurringOrder::route('/create'),
            'edit' => Pages\EditRecurringOrder::route('/{record}/edit'),
        ];
    }
}
