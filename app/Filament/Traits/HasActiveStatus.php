<?php

namespace App\Filament\Traits;

use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

trait HasActiveStatus
{
    /**
     * Get active status toggle field for forms
     */
    public static function getActiveStatusField(): Forms\Components\Toggle
    {
        return Forms\Components\Toggle::make('is_active')
            ->label('Active')
            ->default(true)
            ->helperText('Toggle to activate or deactivate this record')
            ->inline(false);
    }
    
    /**
     * Get active status badge column for tables
     */
    public static function getActiveStatusColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('is_active')
            ->label('Active')
            ->boolean()
            ->sortable()
            ->toggleable();
    }
    
    /**
     * Get active status text badge column for tables
     */
    public static function getActiveStatusBadgeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('is_active')
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
    public static function getActiveStatusFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('is_active')
            ->label('Active Status')
            ->boolean()
            ->trueLabel('Active only')
            ->falseLabel('Inactive only')
            ->placeholder('All');
    }
    
    /**
     * Get activate bulk action
     */
    public static function getActivateBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('activate')
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
    public static function getDeactivateBulkAction(): Tables\Actions\BulkAction
    {
        return Tables\Actions\BulkAction::make('deactivate')
            ->label('Deactivate')
            ->icon('heroicon-o-x-circle')
            ->action(fn ($records) => $records->each->update(['is_active' => false]))
            ->requiresConfirmation()
            ->color('danger')
            ->deselectRecordsAfterCompletion();
    }
    
    /**
     * Get both activate and deactivate bulk actions
     */
    public static function getActiveStatusBulkActions(): array
    {
        return [
            static::getActivateBulkAction(),
            static::getDeactivateBulkAction(),
        ];
    }
}