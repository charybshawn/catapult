<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CropPlanResource\Pages;
use App\Models\CropPlan;
use App\Services\HarvestYieldCalculator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CropPlanResource extends Resource
{
    protected static ?string $model = CropPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Crop Plans';

    protected static ?string $navigationGroup = 'Production';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->label('Order')
                                    ->relationship('order', 'id', function ($query) {
                                        return $query->with('customer');
                                    })
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        $customerName = $record->customer->contact_name ?? 'Unknown';

                                        return "Order #{$record->id} - {$customerName}";
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('recipe_id')
                                    ->label('Recipe')
                                    ->relationship('recipe', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('trays_needed')
                                    ->label('Trays Needed')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),

                                Forms\Components\TextInput::make('grams_needed')
                                    ->label('Grams Needed')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->required(),

                                Forms\Components\TextInput::make('grams_per_tray')
                                    ->label('Grams per Tray')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01),
                            ]),
                    ]),

                Forms\Components\Section::make('Timeline')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DatePicker::make('plant_by_date')
                                    ->label('Plant By Date')
                                    ->required(),

                                Forms\Components\DatePicker::make('expected_harvest_date')
                                    ->label('Expected Harvest Date')
                                    ->required(),

                                Forms\Components\DatePicker::make('delivery_date')
                                    ->label('Delivery Date')
                                    ->required(),
                            ]),
                    ]),

                Forms\Components\Section::make('Status & Approval')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'approved' => 'Approved',
                                        'generating' => 'Generating Crops',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->required(),

                                Forms\Components\Select::make('approved_by')
                                    ->label('Approved By')
                                    ->relationship('approvedBy', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn ($record) => $record && $record->approved_by),
                            ]),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approved At')
                            ->disabled()
                            ->visible(fn ($record) => $record && $record->approved_at),
                    ]),

                Forms\Components\Section::make('Calculation Details')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),

                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Admin Notes')
                            ->rows(3),

                        Forms\Components\KeyValue::make('calculation_details')
                            ->label('Calculation Details')
                            ->addActionLabel('Add Detail')
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('order_items_included')
                            ->label('Order Items Included')
                            ->addActionLabel('Add Item')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Plan #')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.id')
                    ->label('Order')
                    ->formatStateUsing(fn ($record) => "#{$record->order->id}")
                    ->url(fn ($record) => route('filament.admin.resources.orders.edit', $record->order_id))
                    ->sortable(),

                Tables\Columns\TextColumn::make('order.customer.contact_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('recipe.name')
                    ->label('Recipe')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => 'draft',
                        'success' => 'approved',
                        'warning' => 'generating',
                        'primary' => 'completed',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('trays_needed')
                    ->label('Trays')
                    ->sortable(),

                Tables\Columns\TextColumn::make('plant_by_date')
                    ->label('Plant By')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_until_planting')
                    ->label('Days Until')
                    ->getStateUsing(fn ($record) => $record->days_until_planting)
                    ->color(fn ($state) => match (true) {
                        $state < 0 => 'danger',
                        $state <= 2 => 'warning',
                        default => 'success',
                    })
                    ->weight(fn ($state) => $state <= 2 ? 'bold' : 'normal'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('plant_by_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'approved' => 'Approved',
                        'generating' => 'Generating',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                Tables\Filters\Filter::make('urgent')
                    ->label('Urgent (Plant within 2 days)')
                    ->query(fn (Builder $query) => $query->where('plant_by_date', '<=', now()->addDays(2))),

                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query) => $query->where('plant_by_date', '<', now())),
            ])
            ->headerActions([
                Tables\Actions\Action::make('manual_planning')
                    ->label('Manual Crop Planning')
                    ->icon('heroicon-o-calculator')
                    ->color('success')
                    ->url(static::getUrl('manual-planning'))
                    ->button(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->canBeApproved())
                    ->action(function ($record) {
                        $record->approve(Auth::user());
                        Notification::make()
                            ->title('Crop plan approved successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('recalculate')
                    ->label('Recalculate with Latest Harvest Data')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function ($record) {
                        $yieldCalculator = app(HarvestYieldCalculator::class);
                        $recipe = $record->recipe;

                        if (! $recipe) {
                            Notification::make()
                                ->title('Cannot recalculate')
                                ->body('No recipe associated with this crop plan')
                                ->danger()
                                ->send();

                            return;
                        }

                        $oldYield = $record->grams_per_tray;
                        $oldTrays = $record->trays_needed;

                        // Get new yield calculation
                        $newYield = $yieldCalculator->calculatePlanningYield($recipe);
                        $newTrays = ceil($record->grams_needed / $newYield);

                        // Get yield stats for display
                        $stats = $yieldCalculator->getYieldStats($recipe);

                        // Update the crop plan
                        $record->update([
                            'grams_per_tray' => $newYield,
                            'trays_needed' => $newTrays,
                            'calculation_details' => array_merge(
                                $record->calculation_details ?? [],
                                [
                                    'recalculated_at' => now()->toISOString(),
                                    'harvest_data_used' => $stats['harvest_count'] > 0,
                                    'old_yield' => $oldYield,
                                    'new_yield' => $newYield,
                                    'old_trays' => $oldTrays,
                                    'new_trays' => $newTrays,
                                    'yield_stats' => $stats,
                                ]
                            ),
                        ]);

                        $message = "Recalculated: {$oldTrays} → {$newTrays} trays";
                        if ($stats['harvest_count'] > 0) {
                            $message .= " (using {$stats['harvest_count']} harvest records)";
                        } else {
                            $message .= ' (using recipe expected yield)';
                        }

                        Notification::make()
                            ->title('Crop plan recalculated')
                            ->body($message)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalDescription('This will update the crop plan using the latest harvest data and current buffer settings.'),

                Tables\Actions\Action::make('generate_crops')
                    ->label('Generate Crops')
                    ->icon('heroicon-o-plus-circle')
                    ->color('primary')
                    ->visible(fn ($record) => $record->canGenerateCrops())
                    ->action(function ($record) {
                        // TODO: Implement crop generation logic
                        Notification::make()
                            ->title('Crop generation not yet implemented')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $approved = 0;
                            foreach ($records as $record) {
                                if ($record->canBeApproved()) {
                                    $record->approve(Auth::user());
                                    $approved++;
                                }
                            }

                            Notification::make()
                                ->title("Approved {$approved} crop plans")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCropPlans::route('/'),
            'edit' => Pages\EditCropPlan::route('/{record}/edit'),
            'manual-planning' => Pages\ManualCropPlanning::route('/manual-planning'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Crop plans are auto-generated from orders
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'draft')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }
}
