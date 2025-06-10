<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringOrderResource\Pages;
use App\Filament\Resources\RecurringOrderResource\RelationManagers;
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
    protected static ?string $navigationGroup = 'Order Management';
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
                            ])
                            ->createOptionUsing(function (array $data): int {
                                $data['password'] = bcrypt(\Illuminate\Support\Str::random(12));
                                return User::create($data)->getKey();
                            }),
                            
                        Forms\Components\Select::make('order_type')
                            ->label('Order Type')
                            ->options([
                                'website_immediate' => 'Website Order Template',
                                'farmers_market' => 'Farmer\'s Market Template',
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
                            ->visible(fn ($get) => $get('order_type') === 'b2b_recurring'),
                        
                        Forms\Components\Toggle::make('requires_invoice')
                            ->label('Requires Invoice')
                            ->helperText('Uncheck for farmer\'s market orders that don\'t need invoicing')
                            ->default(true),
                    ])
                    ->visible(fn ($get) => in_array($get('order_type'), ['b2b_recurring', 'farmers_market']))
                    ->columns(2),
                
                Forms\Components\Section::make('Recurring Schedule')
                    ->schema([
                        Forms\Components\Select::make('recurring_frequency')
                            ->label(fn ($get) => $get('order_type') === 'b2b_recurring' 
                                ? 'Delivery Frequency' 
                                : 'Generation Frequency')
                            ->helperText(fn ($get) => $get('order_type') === 'b2b_recurring'
                                ? 'How often to create new delivery orders (independent of billing frequency)'
                                : 'How often to generate new orders')
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
                            
                        Forms\Components\Toggle::make('is_recurring_active')
                            ->label('Active')
                            ->helperText('Uncheck to pause recurring order generation')
                            ->default(true),
                    ])
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
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Template ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('order_type_display')
                    ->label('Type')
                    ->badge()
                    ->color(fn (?Order $record): string => match ($record?->order_type) {
                        'website_immediate' => 'success',
                        'farmers_market' => 'warning', 
                        'b2b_recurring' => 'info',
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
                Tables\Filters\SelectFilter::make('order_type')
                    ->options([
                        'website_immediate' => 'Website Template',
                        'farmers_market' => 'Farmer\'s Market Template',
                        'b2b_recurring' => 'B2B Recurring',
                    ]),
                    
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
                    ->label('Generate Next')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->action(function (?Order $record): void {
                        if (!$record) return;
                        $newOrder = $record->generateNextRecurringOrder();
                        if ($newOrder) {
                            Notification::make()
                                ->title('Order generated successfully')
                                ->body("Order #{$newOrder->id} created for {$newOrder->delivery_date->format('M d, Y')}")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Unable to generate order')
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
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
