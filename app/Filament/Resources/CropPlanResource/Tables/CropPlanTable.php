<?php

namespace App\Filament\Resources\CropPlanResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Tables\Grouping\Group;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Actions\CropPlan\GenerateCropPlansAction;
use App\Actions\CropPlan\RecalculateCropPlanAction;
use App\Actions\CropPlan\ApproveCropPlanAction;

/**
 * Table configuration class for agricultural crop planning interface with
 * production timeline visualization, resource summaries, and workflow management.
 *
 * This class handles sophisticated table presentation for crop planning management,
 * including timeline urgency indicators, resource aggregation, workflow actions,
 * and agricultural production coordination features. Supports both detailed list
 * views and calendar-based planning interfaces.
 *
 * @filament_table_class Dedicated table builder for CropPlanResource
 * @business_domain Agricultural production planning and timeline coordination
 * @agricultural_concepts Plan timelines, resource requirements, production urgency
 * 
 * @table_features
 * - Timeline visualization with urgency indicators (days until planting)
 * - Resource summaries with totals for trays and grams needed
 * - Missing recipe detection with workflow integration
 * - Customer and order context for production coordination
 * - Status indicators with agricultural workflow color coding
 * 
 * @agricultural_intelligence
 * - Automatic urgency calculation with color-coded deadline warnings
 * - Resource aggregation supporting production capacity planning
 * - Timeline grouping for efficient production scheduling
 * - Variety grouping for cultivation coordination and efficiency
 * 
 * @workflow_integration
 * - Approval actions with agricultural validation and business rules
 * - Recipe creation workflow for completing missing cultivation instructions
 * - Bulk operations for production scheduling efficiency
 * - Plan generation automation for order-driven production planning
 * 
 * @production_management
 * - Overdue and urgent filtering for critical production deadlines
 * - Status-based filtering for workflow state management
 * - Missing recipe filtering for cultivation workflow completion
 * - Resource summaries supporting production capacity analysis
 * 
 * @performance_optimization
 * - Eager loading of complex relationships prevents N+1 queries
 * - Efficient grouping and summarization for large production datasets
 * - Optimized urgency calculations with cached date comparisons
 * - Session persistence for production workflow continuity
 */
class CropPlanTable
{
    /**
     * Get table columns optimized for agricultural production planning display.
     *
     * Provides comprehensive column set showing essential production planning
     * information including timelines, resource requirements, urgency indicators,
     * and workflow status needed for effective agricultural production coordination.
     *
     * @return array Column definitions with agricultural production context
     * @agricultural_display Timeline urgency, resource summaries, variety information
     * @business_intelligence Customer context, order linkage, production requirements
     * @workflow_visualization Status indicators and approval tracking
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('id')
                ->label('Plan #')
                ->sortable(),

            TextColumn::make('order.id')
                ->label('Order')
                ->formatStateUsing(fn ($record) => $record->order ? "#{$record->order->id}" : "#{$record->order_id}")
                ->url(fn ($record) => $record->order_id ? route('filament.admin.resources.orders.edit', $record->order_id) : null)
                ->sortable(),

            TextColumn::make('order.customer.contact_name')
                ->label('Customer')
                ->formatStateUsing(fn ($record) => $record->order?->customer?->contact_name ?? 'Unknown')
                ->searchable()
                ->sortable(),
            
            TextColumn::make('variety.common_name')
                ->label('Variety')
                ->searchable()
                ->sortable(),
            
            TextColumn::make('recipe.cultivar_name')
                ->label('Cultivar')
                ->searchable()
                ->sortable(),

            TextColumn::make('recipe.name')
                ->label('Recipe')
                ->searchable()
                ->sortable()
                ->getStateUsing(fn ($record) => $record->is_missing_recipe ? '⚠️ Missing Recipe' : $record->recipe?->name)
                ->color(fn ($record) => $record->is_missing_recipe ? 'danger' : 'default')
                ->weight(fn ($record) => $record->is_missing_recipe ? 'bold' : 'normal')
                ->description(fn ($record) => $record->is_missing_recipe ? $record->missing_recipe_notes : null),

            BadgeColumn::make('status.name')
                ->label('Status')
                ->getStateUsing(fn ($record) => $record->is_missing_recipe ? 'Incomplete' : $record->status?->name)
                ->color(fn ($record) => $record->is_missing_recipe ? 'danger' : ($record->status?->color ?? 'gray'))
                ->sortable(),

            TextColumn::make('trays_needed')
                ->label('Trays')
                ->numeric()
                ->sortable()
                ->summarize(Sum::make()),
                
            TextColumn::make('grams_needed')
                ->label('Grams')
                ->numeric()
                ->formatStateUsing(fn ($state) => number_format($state, 1) . 'g')
                ->sortable()
                ->summarize(Sum::make()),

            TextColumn::make('plant_by_date')
                ->label('Plant By')
                ->date()
                ->sortable(),

            TextColumn::make('days_until_planting')
                ->label('Days Until')
                ->getStateUsing(fn ($record) => $record->days_until_planting)
                ->color(fn ($state) => match (true) {
                    $state < 0 => 'danger',
                    $state <= 2 => 'warning',
                    default => 'success',
                })
                ->weight(fn ($state) => $state <= 2 ? 'bold' : 'normal'),

            TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Get table filters for agricultural production workflow management.
     *
     * Provides essential filtering capabilities for production planning including
     * status-based workflow filtering, urgency-based deadline management, and
     * missing recipe identification for cultivation workflow completion.
     *
     * @return array Filter definitions with agricultural production workflow context
     * @agricultural_filtering Status, urgency, and timeline-based production filtering
     * @business_workflow Missing recipe and deadline management filtering
     * @production_coordination Efficient filtering for large production planning datasets
     */
    public static function filters(): array
    {
        return [
            SelectFilter::make('status_id')
                ->label('Status')
                ->relationship('status', 'name'),

            Filter::make('urgent')
                ->label('Urgent (Plant within 2 days)')
                ->query(fn (Builder $query) => $query->where('plant_by_date', '<=', now()->addDays(2))),

            Filter::make('overdue')
                ->label('Overdue')
                ->query(fn (Builder $query) => $query->where('plant_by_date', '<', now())),

            Filter::make('missing_recipe')
                ->label('Missing Recipe')
                ->query(fn (Builder $query) => $query->where('is_missing_recipe', true)),
        ];
    }

    /**
     * Get header actions for automated crop plan generation and management.
     *
     * Provides essential header actions for production planning automation including
     * bulk crop plan generation from orders and agricultural planning workflow
     * coordination with comprehensive result reporting.
     *
     * @return array Header action definitions with agricultural automation features
     * @agricultural_automation Bulk plan generation from order analysis
     * @business_efficiency Automated production planning for order fulfillment
     * @workflow_integration Modal-based result reporting with generation details
     */
    public static function headerActions(): array
    {
        return [
            Action::make('generate_crop_plans')
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
     * Get row-level actions for crop plan workflow management and coordination.
     *
     * Provides comprehensive actions for individual crop plan management including
     * approval workflows, recalculation with updated data, crop generation,
     * and recipe creation integration for complete production planning.
     *
     * @return array Action definitions with agricultural workflow integration
     * @agricultural_actions Approval, recalculation, crop generation workflows
     * @business_operations Recipe creation, plan validation, production coordination
     * @workflow_integration Status-based action visibility with agricultural context
     */
    public static function actions(): array
    {
        return [
            Action::make('approve')
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

            Action::make('recalculate')
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

            Action::make('generate_crops')
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

            Action::make('create_recipe')
                ->label('Create Recipe')
                ->icon('heroicon-o-plus-circle')
                ->color('warning')
                ->visible(fn ($record) => $record->is_missing_recipe)
                ->url(fn ($record) => '/admin/recipes/create?variety=' . $record->variety_id)
                ->openUrlInNewTab(),

            ViewAction::make(),
            EditAction::make(),
        ];
    }

    /**
     * Get bulk operations for efficient crop plan workflow management.
     *
     * Provides efficient bulk operations for production planning including
     * bulk approval workflows and batch deletion with appropriate agricultural
     * validation and business rule enforcement.
     *
     * @return array Bulk action definitions with agricultural workflow efficiency
     * @agricultural_efficiency Bulk approval for production authorization
     * @business_operations Batch processing for large production planning datasets
     * @workflow_coordination Efficient approval workflows for production scheduling
     */
    public static function bulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make(),

                BulkAction::make('approve_selected')
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
     * Get table grouping options for agricultural production organization.
     *
     * Provides intelligent grouping capabilities for production planning including
     * variety-based cultivation coordination, timeline-based production scheduling,
     * and harvest-based fulfillment organization.
     *
     * @return array Group definitions with agricultural production organization
     * @agricultural_organization Variety grouping for cultivation coordination
     * @production_scheduling Timeline grouping for efficient production planning
     * @harvest_coordination Date-based grouping for fulfillment scheduling
     */
    public static function groups(): array
    {
        return [
            Group::make('variety.common_name')
                ->label('Variety')
                ->collapsible(),
            Group::make('plant_by_date')
                ->label('Plant Date')
                ->date()
                ->collapsible(),
            Group::make('expected_harvest_date')
                ->label('Harvest Date')
                ->date()
                ->collapsible(),
        ];
    }

    /**
     * Configure query optimizations for agricultural production planning display.
     *
     * Implements eager loading strategies to prevent N+1 queries when displaying
     * crop planning information including orders, customers, recipes, and approval
     * tracking. Essential for performance with large production planning datasets.
     *
     * @param Builder $query Base Eloquent query builder
     * @return Builder Optimized query with agricultural relationship eager loading
     * @performance_optimization Prevents N+1 queries for related production data
     * @relationship_loading Orders, customers, recipes, approval tracking
     * @agricultural_efficiency Optimized display of complex production relationships
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