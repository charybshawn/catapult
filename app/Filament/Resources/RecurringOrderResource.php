<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecurringOrderResource\Pages;
use App\Filament\Resources\RecurringOrderResource\RelationManagers;
use App\Models\RecurringOrder;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\Action;
use Illuminate\Support\HtmlString;

class RecurringOrderResource extends Resource
{
    protected static ?string $model = RecurringOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationLabel = 'Recurring Orders';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Recurring Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Order Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('customer_type')
                            ->label('Customer Type')
                            ->options([
                                'retail' => 'Retail',
                                'wholesale' => 'Wholesale',
                            ])
                            ->required()
                            ->default('retail'),
                        Forms\Components\Select::make('frequency')
                            ->label('Frequency')
                            ->options([
                                'weekly' => 'Weekly',
                                'biweekly' => 'Biweekly (Every 2 Weeks)',
                                'monthly' => 'Monthly',
                                'custom' => 'Custom Interval',
                            ])
                            ->required()
                            ->default('weekly')
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state !== 'custom') {
                                    $set('interval', null);
                                    $set('interval_unit', null);
                                }
                            }),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(fn () => now()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->helperText('Leave blank for an ongoing order')
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Delivery Days & Custom Interval')
                    ->schema([
                        Forms\Components\CheckboxList::make('delivery_days')
                            ->label('Delivery Days')
                            ->options([
                                0 => 'Sunday',
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                            ])
                            ->required()
                            ->columns(4)
                            ->default([3]), // Default to Wednesday
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('interval')
                                    ->label('Repeat every')
                                    ->numeric()
                                    ->minValue(1)
                                    ->visible(fn (Forms\Get $get) => $get('frequency') === 'custom'),
                                Forms\Components\Select::make('interval_unit')
                                    ->label('Interval Unit')
                                    ->options([
                                        'days' => 'Days',
                                        'weeks' => 'Weeks',
                                        'months' => 'Months',
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('frequency') === 'custom'),
                            ]),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Order Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'retail' => 'info',
                        'wholesale' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_days')
                    ->label('Delivery Days')
                    ->formatStateUsing(function ($state) {
                        $days = [
                            0 => 'Sun',
                            1 => 'Mon',
                            2 => 'Tue',
                            3 => 'Wed',
                            4 => 'Thu',
                            5 => 'Fri',
                            6 => 'Sat',
                        ];
                        
                        return collect($state)->map(fn ($day) => $days[$day])->join(', ');
                    }),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Frequency')
                    ->badge()
                    ->formatStateUsing(fn (string $state, RecurringOrder $record): string => 
                        $state === 'custom' 
                            ? "Every {$record->interval} {$record->interval_unit}" 
                            : ucfirst($state)
                    ),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('End Date')
                    ->date()
                    ->sortable()
                    ->placeholder('Ongoing'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('upcoming_deliveries')
                    ->label('Next Deliveries')
                    ->getStateUsing(function (RecurringOrder $record): HtmlString {
                        $dates = $record->generateDeliveryDates(null, 3);
                        
                        if (empty($dates)) {
                            return new HtmlString('<span class="text-gray-400">No upcoming deliveries</span>');
                        }
                        
                        return new HtmlString(
                            collect($dates)
                                ->map(fn (Carbon $date) => $date->format('M j, Y'))
                                ->join('<br>')
                        );
                    })
                    ->html(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type')
                    ->options([
                        'retail' => 'Retail',
                        'wholesale' => 'Wholesale',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Orders')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_planting_schedule')
                    ->label('View Planting Schedule')
                    ->icon('heroicon-o-calendar')
                    ->url(fn (RecurringOrder $record): string => route('filament.admin.resources.planting-schedules.index', [
                        'tableFilters[related_recurring_order]' => $record->id,
                    ])),
                Tables\Actions\Action::make('generate_orders')
                    ->label('Generate Orders')
                    ->icon('heroicon-o-document-duplicate')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(fn () => now()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->default(fn () => now()->addWeeks(4)),
                        Forms\Components\Toggle::make('create_planting_schedules')
                            ->label('Create Planting Schedules')
                            ->helperText('Also create planting schedules for these orders')
                            ->default(true),
                    ])
                    ->action(function (RecurringOrder $record, array $data): void {
                        $startDate = Carbon::parse($data['start_date']);
                        $endDate = Carbon::parse($data['end_date']);
                        
                        // Generate delivery dates in the given range
                        $deliveryDates = $record->generateDeliveryDates($startDate, 100)
                            ->filter(fn ($date) => $date->lte($endDate))
                            ->values();
                        
                        // Generate orders for each date
                        $generatedOrders = [];
                        foreach ($deliveryDates as $deliveryDate) {
                            $order = $record->generateOrder($deliveryDate);
                            if ($order) {
                                $generatedOrders[] = $order;
                            }
                        }
                        
                        // Create planting schedules if needed
                        if ($data['create_planting_schedules'] && !empty($generatedOrders)) {
                            foreach ($generatedOrders as $order) {
                                app\Models\PlantingSchedule::createFromOrder($order);
                            }
                        }
                        
                        // Show notification
                        if (count($generatedOrders) > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Orders Generated')
                                ->body(count($generatedOrders) . ' orders were successfully generated.')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('No Orders Generated')
                                ->body('No delivery dates were found in the selected date range.')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->is_active = true;
                                $record->save();
                            }
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                $record->is_active = false;
                                $record->save();
                            }
                        }),
                ]),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\RecurringOrderItemsRelationManager::class,
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