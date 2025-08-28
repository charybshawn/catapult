<?php

namespace App\Filament\Traits;

use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\BulkAction;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

/**
 * Has Active Status Trait
 * 
 * Standardized active/inactive status management for agricultural Filament resources.
 * Provides consistent UI components for managing entity status across agricultural
 * workflows including products, suppliers, recipes, and customers.
 * 
 * @filament_trait Reusable active status management for agricultural resources
 * @agricultural_use Status management for products, suppliers, recipes, customers, inventory
 * @ui_consistency Standardized active status UI patterns across agricultural resources
 * @workflow_pattern Common enable/disable pattern for agricultural business entities
 * 
 * Key features:
 * - Standardized active status toggle fields for forms
 * - Consistent active status columns and badges for tables
 * - Active status filtering capabilities
 * - Bulk activation/deactivation actions for agricultural entities
 * - Agricultural workflow-appropriate status management
 * 
 * @package App\Filament\Traits
 * @author Shawn
 * @since 2024
 */
trait HasActiveStatus
{
    /**
     * Get active status toggle field for agricultural resource forms.
     * 
     * @agricultural_context Active status toggle for agricultural entities (products, suppliers, recipes)
     * @return Toggle Standardized active status toggle with agricultural workflow labeling
     * @ui_pattern Consistent active status field across agricultural resources
     */
    public static function getActiveStatusField(): Toggle
    {
        return Toggle::make('is_active')
            ->label('Active')
            ->default(true)
            ->helperText('Toggle to activate or deactivate this record')
            ->inline(false);
    }
    
    /**
     * Get active status icon column for agricultural resource tables.
     * 
     * @agricultural_context Active status icon display for agricultural entities
     * @return IconColumn Boolean icon column for active status visualization
     * @ui_pattern Consistent active status display across agricultural resource tables
     */
    public static function getActiveStatusColumn(): IconColumn
    {
        return IconColumn::make('is_active')
            ->label('Active')
            ->boolean()
            ->sortable()
            ->toggleable();
    }
    
    /**
     * Get active status text badge column for tables
     */
    public static function getActiveStatusBadgeColumn(): TextColumn
    {
        return TextColumn::make('is_active')
            ->label('Status')
            ->badge()
            ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive')
            ->color(fn (bool $state): string => $state ? 'success' : 'danger')
            ->sortable()
            ->toggleable();
    }
    
    /**
     * Get active status filter for tables
     */
    public static function getActiveStatusFilter(): TernaryFilter
    {
        return TernaryFilter::make('is_active')
            ->label('Active Status')
            ->boolean()
            ->trueLabel('Active only')
            ->falseLabel('Inactive only')
            ->placeholder('All');
    }
    
    /**
     * Get bulk activation action for agricultural entities.
     * 
     * @agricultural_context Bulk activation for agricultural resources (products, suppliers, recipes)
     * @return BulkAction Bulk action for activating multiple agricultural entities
     * @workflow_pattern Mass activation for agricultural business entity management
     */
    public static function getActivateBulkAction(): BulkAction
    {
        return BulkAction::make('activate')
            ->label('Activate')
            ->icon('heroicon-o-check-circle')
            ->action(fn ($records) => $records->each->update(['is_active' => true]))
            ->requiresConfirmation()
            ->color('success')
            ->deselectRecordsAfterCompletion();
    }
    
    /**
     * Get deactivate bulk action
     */
    public static function getDeactivateBulkAction(): BulkAction
    {
        return BulkAction::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-x-circle')
            ->action(fn ($records) => $records->each->update(['is_active' => false]))
            ->requiresConfirmation()
            ->color('danger')
            ->deselectRecordsAfterCompletion();
    }
    
    /**
     * Get complete set of active status bulk actions for agricultural entities.
     * 
     * @agricultural_context Combined activation/deactivation bulk actions for agricultural workflows
     * @return array Both activate and deactivate bulk actions for agricultural resource management
     * @workflow_pattern Complete active status management for agricultural business entities
     */
    public static function getActiveStatusBulkActions(): array
    {
        return [
            static::getActivateBulkAction(),
            static::getDeactivateBulkAction(),
        ];
    }
}