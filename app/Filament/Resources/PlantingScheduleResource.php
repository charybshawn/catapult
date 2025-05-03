<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlantingScheduleResource\Pages;
use App\Filament\Resources\PlantingScheduleResource\RelationManagers;
use App\Models\PlantingSchedule;
use App\Models\Recipe;
use App\Models\RecurringOrder;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlantingScheduleResource extends Resource
{
    protected static ?string $model = PlantingSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Planting Schedule';
    protected static ?string $navigationGroup = 'Farm Operations';
    protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule Information')
                    ->schema([
                        Forms\Components\DatePicker::make('planting_date')
                            ->label('Planting Date')
                            ->required(),
                        Forms\Components\DatePicker::make('target_harvest_date')
                            ->label('Target Harvest Date')
                            ->required(),
                        Forms\Components\Select::make('recipe_id')
                            ->label('Recipe')
                            ->relationship('recipe', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('trays_required')
                            ->label('Trays Required')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        Forms\Components\TextInput::make('trays_planted')
                            ->label('Trays Planted')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'partially_planted' => 'Partially Planted',
                                'fully_planted' => 'Fully Planted',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Related Orders')
                    ->schema([
                        Forms\Components\Repeater::make('related_orders')
                            ->label('Related Orders')
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->label('Order')
                                    ->relationship('orders', 'id', function (Builder $query) {
                                        return $query->orderBy('harvest_date', 'desc');
                                    })
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "#{$record->id} - {$record->user->name} - {$record->harvest_date->format('M j, Y')}")
                                    ->searchable()
                                    ->preload(),
                            ])
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['order_id']) 
                                    ? "Order #{$state['order_id']}" 
                                    : null
                            )
                            ->columnSpanFull(),
                            
                        Forms\Components\Repeater::make('related_recurring_orders')
                            ->label('Related Recurring Orders')
                            ->schema([
                                Forms\Components\Select::make('recurring_order_id')
                                    ->label('Recurring Order')
                                    ->options(
                                        RecurringOrder::query()
                                            ->where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(function ($order) {
                                                return [$order->id => "#{$order->id} - {$order->name} - {$order->user->name}"];
                                            })
                                    )
                                    ->searchable(),
                            ])
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['recurring_order_id']) 
                                    ? "Recurring Order #{$state['recurring_order_id']}" 
                                    : null
                            )
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('planting_date', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('planting_date')
                    ->label('Planting Date')
                    ->date()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => 
                        Carbon::parse($state)->isPast() 
                            ? (Carbon::parse($state)->isToday() ? 'warning' : 'danger') 
                            : 'success'
                    ),
                Tables\Columns\TextColumn::make('target_harvest_date')
                    ->label('Harvest Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipe.name')
                    ->label('Recipe')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trays_required')
                    ->label('Trays Required')
                    ->sortable(),
                Tables\Columns\TextColumn::make('trays_planted')
                    ->label('Trays Planted')
                    ->sortable(),
                Tables\Columns\TextColumn::make('planting_progress')
                    ->label('Progress')
                    ->getStateUsing(function (PlantingSchedule $record): string {
                        if ($record->trays_required <= 0) return '0%';
                        $percent = round(($record->trays_planted / $record->trays_required) * 100);
                        return "{$percent}%";
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'partially_planted' => 'warning',
                        'fully_planted' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('related_orders_count')
                    ->label('Orders')
                    ->getStateUsing(function (PlantingSchedule $record): int {
                        return is_array($record->related_orders) ? count($record->related_orders) : 0;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('crops_count')
                    ->label('Crops')
                    ->getStateUsing(function (PlantingSchedule $record): int {
                        return $record->crops()->count();
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('planting_from'),
                        Forms\Components\DatePicker::make('planting_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['planting_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('planting_date', '>=', $date),
                            )
                            ->when(
                                $data['planting_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('planting_date', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('recipe_id')
                    ->label('Recipe')
                    ->relationship('recipe', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'partially_planted' => 'Partially Planted',
                        'fully_planted' => 'Fully Planted',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('related_order')
                    ->form([
                        Forms\Components\TextInput::make('order_id')
                            ->label('Order ID')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['order_id'],
                                fn (Builder $query, $orderId): Builder => 
                                    $query->whereJsonContains('related_orders', $orderId),
                            );
                    }),
                Tables\Filters\Filter::make('related_recurring_order')
                    ->form([
                        Forms\Components\TextInput::make('recurring_order_id')
                            ->label('Recurring Order ID')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['recurring_order_id'],
                                fn (Builder $query, $recurringOrderId): Builder => 
                                    $query->whereJsonContains('related_recurring_orders', $recurringOrderId),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('plant_trays')
                    ->label('Plant Trays')
                    ->icon('heroicon-o-play')
                    ->form([
                        Forms\Components\TextInput::make('tray_count')
                            ->label('Number of Trays')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function (PlantingSchedule $record, array $data): void {
                        $count = $data['tray_count'] ?? 1;
                        $crops = $record->generateTrays($count);
                        
                        if (count($crops) > 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Trays Planted')
                                ->body("Successfully planted {$count} trays for {$record->recipe->name}.")
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Failed to plant trays. Please try again.')
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('view_crops')
                    ->label('View Crops')
                    ->icon('heroicon-o-eye')
                    ->url(fn (PlantingSchedule $record): string => route('filament.admin.resources.crops.index', [
                        'tableFilters[recipe]' => $record->recipe_id,
                        'tableFilters[planting_date][planting_from]' => $record->planting_date->format('Y-m-d'),
                        'tableFilters[planting_date][planting_until]' => $record->planting_date->format('Y-m-d'),
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('New Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'partially_planted' => 'Partially Planted',
                                    'fully_planted' => 'Fully Planted', 
                                    'completed' => 'Completed',
                                    'cancelled' => 'Cancelled',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                $record->status = $data['status'];
                                $record->save();
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Status Updated')
                                ->body('Successfully updated status for selected planting schedules.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlantingSchedules::route('/'),
            'create' => Pages\CreatePlantingSchedule::route('/create'),
            'edit' => Pages\EditPlantingSchedule::route('/{record}/edit'),
        ];
    }
} 