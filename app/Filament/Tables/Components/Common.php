<?php

namespace App\Filament\Tables\Components;

use Filament\Tables;

class Common
{
    /**
     * Create a status badge column with color mapping
     */
    public static function statusBadge(string $field = 'status'): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make($field)
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
    public static function activeBadge(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('is_active')
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
            Tables\Actions\ViewAction::make()
                ->tooltip('View details'),
            Tables\Actions\EditAction::make()
                ->tooltip('Edit record'),
            Tables\Actions\DeleteAction::make()
                ->tooltip('Delete record'),
        ];
    }

    /**
     * Create activate/deactivate bulk actions
     */
    public static function activeInactiveBulkActions(): array
    {
        return [
            Tables\Actions\BulkAction::make('activate')
                ->label('Activate')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action(function ($records) {
                    foreach ($records as $record) {
                        $record->update(['is_active' => true]);
                    }
                })
                ->deselectRecordsAfterCompletion(),
            Tables\Actions\BulkAction::make('deactivate')
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
    public static function defaultBulkActions(): Tables\Actions\BulkActionGroup
    {
        return Tables\Actions\BulkActionGroup::make([
            Tables\Actions\DeleteBulkAction::make(),
            ...self::activeInactiveBulkActions(),
        ]);
    }

    /**
     * Create a price column with currency formatting
     */
    public static function priceColumn(
        string $field = 'price', 
        string $label = 'Price',
        string $currency = 'USD'
    ): Tables\Columns\TextColumn {
        return Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->money($currency)
            ->sortable();
    }

    /**
     * Create a date column
     */
    public static function dateColumn(string $field, string $label): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->date()
            ->sortable();
    }

    /**
     * Create a datetime column
     */
    public static function datetimeColumn(string $field, string $label): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make($field)
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
    ): Tables\Columns\TextColumn {
        $column = Tables\Columns\TextColumn::make($field)
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
    ): Tables\Columns\TextColumn {
        return Tables\Columns\TextColumn::make($field . '.' . $attribute)
            ->label($label)
            ->searchable()
            ->sortable();
    }

    /**
     * Create a weight column with unit display
     */
    public static function weightColumn(
        string $weightField = 'weight',
        string $unitField = 'weight_unit',
        string $label = 'Weight'
    ): Tables\Columns\TextColumn {
        return Tables\Columns\TextColumn::make($weightField)
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
            Tables\Columns\TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->label('Updated')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * Create a toggle column for boolean values
     */
    public static function toggleColumn(string $field, string $label): Tables\Columns\ToggleColumn
    {
        return Tables\Columns\ToggleColumn::make($field)
            ->label($label)
            ->tooltip(fn ($state): string => $state ? 'Enabled' : 'Disabled');
    }

    /**
     * Create a searchable text column
     */
    public static function textColumn(string $field, string $label): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make($field)
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
    ): Tables\Columns\TextColumn {
        return Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->limit($limit)
            ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
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