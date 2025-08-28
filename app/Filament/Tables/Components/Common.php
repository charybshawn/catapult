<?php

namespace App\Filament\Tables\Components;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables;

/**
 * Common Table Components
 * 
 * Reusable Filament table component library providing standardized column
 * patterns, actions, and bulk operations for agricultural business management.
 * Ensures consistent table UI and functionality across all agricultural resources.
 * 
 * @filament_support Reusable table component patterns and actions
 * @agricultural_use Standardized table patterns for agricultural resource management
 * @consistency Uniform table UI across agricultural entities (products, crops, orders, inventory)
 * @business_context Agricultural status badges, measurements, pricing, and relationship columns
 * 
 * Key features:
 * - Agricultural status badge patterns with color coding
 * - Standardized actions (view, edit, delete) with tooltips
 * - Bulk operations for activation/deactivation workflows
 * - Agricultural measurement columns (weight, price, quantity)
 * - Relationship columns for agricultural entity connections
 * 
 * @package App\Filament\Tables\Components
 * @author Shawn
 * @since 2024
 */
class Common
{
    /**
     * Create a status badge column with agricultural color mapping.
     * 
     * @agricultural_context Status badges for crops, orders, inventory, and production stages
     * @param string $field Status field name
     * @return TextColumn Status badge column with agricultural workflow color coding
     * @color_mapping Success (active, completed, harvested), Warning (pending, processing), Danger (cancelled, failed)
     */
    public static function statusBadge(string $field = 'status'): TextColumn
    {
        return TextColumn::make($field)
            ->badge()
            ->color(fn (string $state): string => match ($state) {
                'active', 'completed', 'delivered', 'harvested', 'success' => 'success',
                'inactive', 'cancelled', 'failed', 'error' => 'danger',
                'pending', 'processing', 'germination', 'blackout', 'light', 'warning' => 'warning',
                'draft', 'paused', 'info' => 'info',
                default => 'gray',
            })
            ->sortable();
    }

    /**
     * Create an active/inactive badge column
     */
    public static function activeBadge(): IconColumn
    {
        return IconColumn::make('is_active')
            ->label('Active')
            ->boolean()
            ->tooltip(fn ($state): string => $state ? 'Active' : 'Inactive')
            ->sortable();
    }

    /**
     * Create common table actions (View, Edit, Delete)
     */
    public static function defaultActions(): array
    {
        return [
            ViewAction::make()
                ->tooltip('View details'),
            EditAction::make()
                ->tooltip('Edit record'),
            DeleteAction::make()
                ->tooltip('Delete record'),
        ];
    }

    /**
     * Create activate/deactivate bulk actions for agricultural entities.
     * 
     * @agricultural_context Bulk activation/deactivation for products, suppliers, customers, recipes
     * @return array Bulk action array for managing active status across agricultural entities
     * @workflow_pattern Common pattern for enabling/disabling agricultural resources
     */
    public static function activeInactiveBulkActions(): array
    {
        return [
            BulkAction::make('activate')
                ->label('Activate')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(function ($records) {
                    foreach ($records as $record) {
                        $record->update(['is_active' => true]);
                    }
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('deactivate')
                ->label('Deactivate')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->action(function ($records) {
                    foreach ($records as $record) {
                        $record->update(['is_active' => false]);
                    }
                })
                ->deselectRecordsAfterCompletion(),
        ];
    }

    /**
     * Create default bulk actions (Delete + Active/Inactive)
     */
    public static function defaultBulkActions(): BulkActionGroup
    {
        return BulkActionGroup::make([
            DeleteBulkAction::make(),
            ...self::activeInactiveBulkActions(),
        ]);
    }

    /**
     * Create a price column with currency formatting for agricultural products.
     * 
     * @agricultural_context Price display for agricultural products, seeds, and services
     * @param string $field Price field name
     * @param string $label Column display label
     * @param string $currency Currency code for formatting
     * @return TextColumn Money-formatted price column with currency symbol
     * @business_context Handles agricultural product pricing display with proper currency formatting
     */
    public static function priceColumn(
        string $field = 'price', 
        string $label = 'Price',
        string $currency = 'USD'
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->money($currency)
            ->sortable();
    }

    /**
     * Create a date column
     */
    public static function dateColumn(string $field, string $label): TextColumn
    {
        return TextColumn::make($field)
            ->label($label)
            ->date()
            ->sortable();
    }

    /**
     * Create a datetime column
     */
    public static function datetimeColumn(string $field, string $label): TextColumn
    {
        return TextColumn::make($field)
            ->label($label)
            ->dateTime()
            ->sortable();
    }

    /**
     * Create a numeric column with formatting
     */
    public static function numericColumn(
        string $field,
        string $label,
        int $decimalPlaces = 2,
        ?string $suffix = null
    ): TextColumn {
        $column = TextColumn::make($field)
            ->label($label)
            ->numeric(decimalPlaces: $decimalPlaces)
            ->sortable();
            
        if ($suffix) {
            $column->suffix(' ' . $suffix);
        }
        
        return $column;
    }

    /**
     * Create a relationship column
     */
    public static function relationshipColumn(
        string $field,
        string $label,
        string $attribute = 'name'
    ): TextColumn {
        return TextColumn::make($field . '.' . $attribute)
            ->label($label)
            ->searchable()
            ->sortable();
    }

    /**
     * Create a weight column with unit display for agricultural measurements.
     * 
     * @agricultural_context Weight display for seeds, harvest yields, product packaging
     * @param string $weightField Field containing weight value
     * @param string $unitField Field containing weight unit
     * @param string $label Column display label
     * @return TextColumn Formatted weight column with unit display (e.g., "150.25 g")
     * @format_pattern Combines weight value and unit for clear agricultural measurement display
     */
    public static function weightColumn(
        string $weightField = 'weight',
        string $unitField = 'weight_unit',
        string $label = 'Weight'
    ): TextColumn {
        return TextColumn::make($weightField)
            ->label($label)
            ->formatStateUsing(function ($record) use ($weightField, $unitField) {
                $weight = $record->$weightField;
                $unit = $record->$unitField;
                
                if (!$weight || !$unit) {
                    return '-';
                }
                
                return number_format($weight, 2) . ' ' . $unit;
            })
            ->sortable();
    }

    /**
     * Create timestamp columns (created_at, updated_at)
     */
    public static function timestampColumns(): array
    {
        return [
            TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('updated_at')
                ->label('Updated')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Create a toggle column for boolean values
     */
    public static function toggleColumn(string $field, string $label): ToggleColumn
    {
        return ToggleColumn::make($field)
            ->label($label)
            ->tooltip(fn ($state): string => $state ? 'Enabled' : 'Disabled');
    }

    /**
     * Create a searchable text column
     */
    public static function textColumn(string $field, string $label): TextColumn
    {
        return TextColumn::make($field)
            ->label($label)
            ->searchable()
            ->sortable()
            ->wrap();
    }

    /**
     * Create a truncated text column for long content
     */
    public static function truncatedTextColumn(
        string $field,
        string $label,
        int $limit = 50
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->limit($limit)
            ->tooltip(function (TextColumn $column): ?string {
                $state = $column->getState();
                
                if (strlen($state) <= $column->getCharacterLimit()) {
                    return null;
                }
                
                return $state;
            })
            ->searchable()
            ->sortable();
    }
}