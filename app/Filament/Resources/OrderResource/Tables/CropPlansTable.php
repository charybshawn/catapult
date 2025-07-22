<?php

namespace App\Filament\Resources\OrderResource\Tables;

use App\Actions\Order\ApproveAllPlansAction;
use App\Actions\Order\GenerateOrderPlansAction;
use App\Actions\Order\ValidateOrderPlanAction;
use App\Models\CropPlan;
use Filament\Tables;

/**
 * Table component for Crop Plans - organized Filament table components
 * Following Filament Resource Architecture Guide patterns
 */
class CropPlansTable
{
    /**
     * Returns Filament table columns - NOT a custom table system
     */
    public static function columns(): array
    {
        return [
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
        ];
    }

    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status')
                ->relationship('status', 'name')
                ->preload(),
        ];
    }

    public static function headerActions($getOwnerRecord): array
    {
        return [
            Tables\Actions\Action::make('generate_plans')
                ->label('Generate Crop Plans')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->visible(fn (): bool => 
                    app(GenerateOrderPlansAction::class)->canGenerate($getOwnerRecord())
                )
                ->requiresConfirmation()
                ->modalHeading('Generate Crop Plans')
                ->modalDescription('This will analyze the order items and generate crop plans based on delivery date and product requirements.'),
            Tables\Actions\Action::make('approve_all')
                ->label('Approve All Plans')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => 
                    app(ApproveAllPlansAction::class)->canApproveAll($getOwnerRecord())
                )
                ->requiresConfirmation()
                ->modalHeading('Approve All Draft Plans')
                ->modalDescription('This will approve all draft crop plans for this order, allowing crops to be generated.'),
        ];
    }

    public static function actions(): array
    {
        return [
            Tables\Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn (CropPlan $record): bool => $record->canBeApproved())
                ->requiresConfirmation(),
            Tables\Actions\Action::make('cancel')
                ->label('Cancel')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn (CropPlan $record): bool => 
                    app(ValidateOrderPlanAction::class)->canCancelPlan($record)
                )
                ->requiresConfirmation()
                ->modalHeading('Cancel Crop Plan')
                ->modalDescription('Are you sure you want to cancel this crop plan? This action cannot be undone.'),
            Tables\Actions\EditAction::make()
                ->visible(fn (CropPlan $record): bool => 
                    app(ValidateOrderPlanAction::class)->canEditPlan($record)
                ),
        ];
    }

    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('approve_selected')
                ->label('Approve Selected')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation(),
        ];
    }
}