<?php

namespace App\Filament\Resources\CropPlanResource\Tables;

use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Actions\CropPlan\GenerateCropPlansAction;
use App\Actions\CropPlan\RecalculateCropPlanAction;
use App\Actions\CropPlan\ApproveCropPlanAction;

/**
 * Table component for CropPlan resource following Filament Resource Architecture Guide
 * Returns Filament table components - organized Filament components, not custom table system
 */
class CropPlanTable
{
    /**
     * Returns Filament table columns array
     */
    public static function columns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label('Plan #')
                ->sortable(),

            Tables\Columns\TextColumn::make('order.id')
                ->label('Order')
                ->formatStateUsing(fn ($record) => $record->order ? "#{$record->order->id}" : "#{$record->order_id}")
                ->url(fn ($record) => $record->order_id ? route('filament.admin.resources.orders.edit', $record->order_id) : null)
                ->sortable(),

            Tables\Columns\TextColumn::make('order.customer.contact_name')
                ->label('Customer')
                ->formatStateUsing(fn ($record) => $record->order?->customer?->contact_name ?? 'Unknown')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('variety.common_name')
                ->label('Variety')
                ->searchable()
                ->sortable(),
            
            Tables\Columns\TextColumn::make('recipe.cultivar_name')
                ->label('Cultivar')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('recipe.name')
                ->label('Recipe')
                ->searchable()
                ->sortable()
                ->getStateUsing(fn ($record) => $record->is_missing_recipe ? '⚠️ Missing Recipe' : $record->recipe?->name)
                ->color(fn ($record) => $record->is_missing_recipe ? 'danger' : 'default')
                ->weight(fn ($record) => $record->is_missing_recipe ? 'bold' : 'normal')
                ->description(fn ($record) => $record->is_missing_recipe ? $record->missing_recipe_notes : null),

            Tables\Columns\BadgeColumn::make('status.name')
                ->label('Status')
                ->getStateUsing(fn ($record) => $record->is_missing_recipe ? 'Incomplete' : $record->status?->name)
                ->color(fn ($record) => $record->is_missing_recipe ? 'danger' : ($record->status?->color ?? 'gray'))
                ->sortable(),

            Tables\Columns\TextColumn::make('trays_needed')
                ->label('Trays')
                ->numeric()
                ->sortable()
                ->summarize(Tables\Columns\Summarizers\Sum::make()),
                
            Tables\Columns\TextColumn::make('grams_needed')
                ->label('Grams')
                ->numeric()
                ->formatStateUsing(fn ($state) => number_format($state, 1) . 'g')
                ->sortable()
                ->summarize(Tables\Columns\Summarizers\Sum::make()),

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
        ];
    }

    /**
     * Returns Filament table filters array
     */
    public static function filters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('status_id')
                ->label('Status')
                ->relationship('status', 'name'),

            Tables\Filters\Filter::make('urgent')
                ->label('Urgent (Plant within 2 days)')
                ->query(fn (Builder $query) => $query->where('plant_by_date', '<=', now()->addDays(2))),

            Tables\Filters\Filter::make('overdue')
                ->label('Overdue')
                ->query(fn (Builder $query) => $query->where('plant_by_date', '<', now())),

            Tables\Filters\Filter::make('missing_recipe')
                ->label('Missing Recipe')
                ->query(fn (Builder $query) => $query->where('is_missing_recipe', true)),
        ];
    }

    /**
     * Returns Filament header actions array
     */
    public static function headerActions(): array
    {
        return [
            Tables\Actions\Action::make('generate_crop_plans')
                ->label('Generate Crop Plans')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->modalHeading('Crop Plan Generation Results')
                ->modalWidth('4xl')
                ->action(function () {
                    $results = app(GenerateCropPlansAction::class)->execute();
                    session(['crop_plan_results' => $results]);
                })
                ->modalContent(view('filament.modals.crop-plan-generation-results'))
                ->requiresConfirmation()
                ->modalHeading('Generate Crop Plans')
                ->modalDescription('This will generate crop plans for all valid orders in the next 30 days. Existing draft plans for the same orders will be replaced.')
                ->modalSubmitActionLabel('Generate Plans'),
        ];
    }

    /**
     * Returns Filament table actions array
     */
    public static function actions(): array
    {
        return [
            Tables\Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->canBeApproved() && !$record->is_missing_recipe)
                ->action(function ($record) {
                    $result = app(ApproveCropPlanAction::class)->approveSingle($record, Auth::user());
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Approval failed')
                            ->body($result['error'])
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation(),

            Tables\Actions\Action::make('recalculate')
                ->label('Recalculate with Latest Harvest Data')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->visible(fn ($record) => $record->status?->code === 'draft' && !$record->is_missing_recipe)
                ->action(function ($record) {
                    $result = app(RecalculateCropPlanAction::class)->execute($record);
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Crop plan recalculated')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Cannot recalculate')
                            ->body($result['error'])
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalDescription('This will update the crop plan using the latest harvest data and current buffer settings.'),

            Tables\Actions\Action::make('generate_crops')
                ->label('Generate Crops')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->visible(fn ($record) => $record->canGenerateCrops() && !$record->is_missing_recipe)
                ->action(function ($record) {
                    // TODO: Implement crop generation logic
                    Notification::make()
                        ->title('Crop generation not yet implemented')
                        ->warning()
                        ->send();
                }),

            Tables\Actions\Action::make('create_recipe')
                ->label('Create Recipe')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->visible(fn ($record) => $record->is_missing_recipe)
                ->url(fn ($record) => '/admin/recipes/create?variety=' . $record->variety_id)
                ->openUrlInNewTab(),

            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ];
    }

    /**
     * Returns Filament bulk actions array
     */
    public static function bulkActions(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),

                Tables\Actions\BulkAction::make('approve_selected')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function ($records) {
                        $result = app(ApproveCropPlanAction::class)->approveBulk($records, Auth::user());
                        
                        if ($result['success']) {
                            Notification::make()
                                ->title($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Bulk approval failed')
                                ->body($result['error'])
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
            ]),
        ];
    }

    /**
     * Returns Filament table groups array
     */
    public static function groups(): array
    {
        return [
            Tables\Grouping\Group::make('variety.common_name')
                ->label('Variety')
                ->collapsible(),
            Tables\Grouping\Group::make('plant_by_date')
                ->label('Plant Date')
                ->date()
                ->collapsible(),
            Tables\Grouping\Group::make('expected_harvest_date')
                ->label('Harvest Date')
                ->date()
                ->collapsible(),
        ];
    }

    /**
     * Configure table query modifications
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with([
            'order.customer',
            'recipe',
            'createdBy',
            'approvedBy'
        ]);
    }
}