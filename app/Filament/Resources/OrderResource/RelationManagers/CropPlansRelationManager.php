<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\CropPlan;
use App\Services\OrderPlanningService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CropPlansRelationManager extends RelationManager
{
    protected static string $relationship = 'cropPlans';
    protected static ?string $title = 'Crop Plans';
    protected static ?string $navigationLabel = 'Crop Plans';
    protected static ?string $icon = 'heroicon-o-calendar';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('recipe.name')
                    ->label('Recipe')
                    ->disabled(),
                Forms\Components\TextInput::make('variety.name')
                    ->label('Variety')
                    ->disabled(),
                Forms\Components\TextInput::make('trays_needed')
                    ->label('Trays')
                    ->numeric()
                    ->disabled(),
                Forms\Components\DatePicker::make('plant_by_date')
                    ->label('Plant By')
                    ->disabled(),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(3),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Plan ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status.name')
                    ->badge()
                    ->color(fn (CropPlan $record): string => $record->status->color ?? 'gray'),
                Tables\Columns\TextColumn::make('variety.name')
                    ->label('Variety')
                    ->searchable()
                    ->default('Unknown'),
                Tables\Columns\TextColumn::make('recipe.name')
                    ->label('Recipe')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('trays_needed')
                    ->label('Trays')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('grams_needed')
                    ->label('Grams')
                    ->numeric()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('plant_by_date')
                    ->label('Plant By')
                    ->date()
                    ->sortable()
                    ->color(function (CropPlan $record): string {
                        if ($record->isOverdue()) {
                            return 'danger';
                        } elseif ($record->isUrgent()) {
                            return 'warning';
                        }
                        return 'gray';
                    }),
                Tables\Columns\TextColumn::make('days_until_planting')
                    ->label('Days Until')
                    ->getStateUsing(function (CropPlan $record): string {
                        $days = $record->days_until_planting;
                        if ($days < 0) {
                            return abs($days) . ' days overdue';
                        } elseif ($days === 0) {
                            return 'Today';
                        } elseif ($days === 1) {
                            return 'Tomorrow';
                        }
                        return $days . ' days';
                    })
                    ->badge()
                    ->color(function (CropPlan $record): string {
                        if ($record->isOverdue()) {
                            return 'danger';
                        } elseif ($record->isUrgent()) {
                            return 'warning';
                        } elseif ($record->days_until_planting <= 7) {
                            return 'info';
                        }
                        return 'gray';
                    }),
                Tables\Columns\TextColumn::make('expected_harvest_date')
                    ->label('Harvest')
                    ->date()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('crops_count')
                    ->label('Crops')
                    ->counts('crops')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('plant_by_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->relationship('status', 'name')
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate_plans')
                    ->label('Generate Crop Plans')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->visible(fn (): bool => 
                        $this->getOwnerRecord()->requiresCropProduction() &&
                        !$this->getOwnerRecord()->isInFinalState()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Generate Crop Plans')
                    ->modalDescription('This will analyze the order items and generate crop plans based on delivery date and product requirements.')
                    ->action(function () {
                        $order = $this->getOwnerRecord();
                        $orderPlanningService = app(OrderPlanningService::class);
                        
                        // Check if plans already exist
                        if ($order->cropPlans()->exists()) {
                            Notification::make()
                                ->title('Plans Already Exist')
                                ->body('This order already has crop plans. Use the update action to regenerate them.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        $result = $orderPlanningService->generatePlansForOrder($order);
                        
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
                Tables\Actions\Action::make('approve_all')
                    ->label('Approve All Plans')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (): bool => 
                        $this->getOwnerRecord()->cropPlans()
                            ->whereHas('status', fn($q) => $q->where('code', 'draft'))
                            ->exists()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Approve All Draft Plans')
                    ->modalDescription('This will approve all draft crop plans for this order, allowing crops to be generated.')
                    ->action(function () {
                        $order = $this->getOwnerRecord();
                        $orderPlanningService = app(OrderPlanningService::class);
                        
                        $result = $orderPlanningService->approveAllPlansForOrder($order, auth()->user());
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title('Plans Approved')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Approval Failed')
                                ->body($result['message'])
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (CropPlan $record): bool => $record->canBeApproved())
                    ->requiresConfirmation()
                    ->action(function (CropPlan $record) {
                        $record->approve(auth()->user());
                        Notification::make()
                            ->title('Plan Approved')
                            ->body('Crop plan has been approved and is ready for planting.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (CropPlan $record): bool => 
                        in_array($record->status->code, ['draft', 'active']) &&
                        $record->crops()->count() === 0
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Crop Plan')
                    ->modalDescription('Are you sure you want to cancel this crop plan? This action cannot be undone.')
                    ->action(function (CropPlan $record) {
                        $record->cancel();
                        Notification::make()
                            ->title('Plan Cancelled')
                            ->body('Crop plan has been cancelled.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn (CropPlan $record): bool => $record->isDraft()),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve_selected')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $approved = 0;
                        foreach ($records as $record) {
                            if ($record->canBeApproved()) {
                                $record->approve(auth()->user());
                                $approved++;
                            }
                        }
                        
                        Notification::make()
                            ->title('Plans Approved')
                            ->body("Approved {$approved} crop plans.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return $this->getOwnerRecord()->isInFinalState();
    }
}