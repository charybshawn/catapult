<?php

namespace App\Filament\Resources;

use App\Filament\Traits\CsvExportAction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

abstract class BaseResource extends Resource
{
    use CsvExportAction;
    /**
     * Configure default table settings with persistence
     */
    public static function configureTableDefaults(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistColumnSearchesInSession()
            ->persistSearchInSession()
            ->striped();
    }
    
    /**
     * Get a standard text column
     */
    protected static function getTextColumn(
        string $field,
        string $label,
        bool $searchable = true,
        bool $sortable = true,
        bool $toggleable = true
    ): TextColumn {
        return Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->searchable($searchable)
            ->sortable($sortable)
            ->toggleable($toggleable);
    }

    /**
     * Get an active badge column
     */
    protected static function getActiveBadgeColumn(): IconColumn
    {
        return Tables\Columns\IconColumn::make('is_active')
            ->label('Active')
            ->boolean()
            ->sortable()
            ->toggleable();
    }

    /**
     * Get standard timestamp columns
     */
    protected static function getTimestampColumns(): array
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
     * Get default table actions
     */
    protected static function getDefaultTableActions(): array
    {
        return [
            Tables\Actions\ViewAction::make()
                ->tooltip('View record'),
            Tables\Actions\EditAction::make()
                ->tooltip('Edit record'),
            Tables\Actions\DeleteAction::make()
                ->tooltip('Delete record'),
        ];
    }

    /**
     * Get default bulk actions
     */
    protected static function getDefaultBulkActions(): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make(),
            Tables\Actions\BulkAction::make('activate')
                ->label('Activate')
                ->icon('heroicon-o-check-circle')
                ->action(function ($records) {
                    foreach ($records as $record) {
                        $record->update(['is_active' => true]);
                    }
                })
                ->requiresConfirmation()
                ->color('success'),
            Tables\Actions\BulkAction::make('deactivate')
                ->label('Deactivate')
                ->icon('heroicon-o-x-circle')
                ->action(function ($records) {
                    foreach ($records as $record) {
                        $record->update(['is_active' => false]);
                    }
                })
                ->requiresConfirmation()
                ->color('danger'),
        ];
    }

    /**
     * Get default header actions including CSV export
     */
    protected static function getDefaultHeaderActions(): array
    {
        return [
            static::getCsvExportAction(),
        ];
    }

    /**
     * Default CSV export columns - automatically includes common fields
     * Override this method in your resource to customize export columns
     */
    protected static function getCsvExportColumns(): array
    {
        $model = new (static::getModel());
        $table = $model->getTable();
        $schemaColumns = $model->getConnection()->getSchemaBuilder()->getColumnListing($table);
        
        // Standard columns that most models have
        $standardColumns = [
            'id' => 'ID',
            'name' => 'Name', 
            'title' => 'Title',
            'description' => 'Description',
            'status' => 'Status',
            'is_active' => 'Active',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At'
        ];
        
        // Filter to only include columns that exist in this table
        $availableColumns = [];
        foreach ($standardColumns as $column => $label) {
            if (in_array($column, $schemaColumns)) {
                $availableColumns[$column] = $label;
            }
        }
        
        // Add any remaining columns from schema (excluding sensitive ones)
        $excludeColumns = ['password', 'remember_token', 'email_verified_at'];
        foreach ($schemaColumns as $column) {
            if (!isset($availableColumns[$column]) && !in_array($column, $excludeColumns)) {
                $availableColumns[$column] = static::formatColumnLabel($column);
            }
        }
        
        return $availableColumns;
    }

    /**
     * Default relationships to include in export
     * Override this method in your resource to specify relationships
     */
    protected static function getCsvExportRelationships(): array
    {
        return [];
    }

    /**
     * Format column name into a readable label
     */
    protected static function formatColumnLabel(string $columnName): string
    {
        return \Illuminate\Support\Str::title(str_replace('_', ' ', $columnName));
    }

    /**
     * Get a status badge column with standard colors
     */
    protected static function getStatusBadgeColumn(
        string $field = 'status',
        string $label = 'Status',
        array $colorMap = []
    ): Tables\Columns\TextColumn {
        $defaultColorMap = [
            'active' => 'success',
            'inactive' => 'danger',
            'pending' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            'draft' => 'gray',
        ];

        $colors = array_merge($defaultColorMap, $colorMap);

        return Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->badge()
            ->color(fn (string $state): string => $colors[$state] ?? 'gray')
            ->toggleable();
    }

    /**
     * Get a price column formatted with currency
     */
    protected static function getPriceColumn(
        string $field = 'price',
        string $label = 'Price',
        string $currency = '$'
    ): TextColumn {
        return Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->money($currency)
            ->sortable()
            ->toggleable();
    }

    /**
     * Get a relationship column
     */
    protected static function getRelationshipColumn(
        string $field,
        string $label,
        bool $searchable = true,
        bool $sortable = true
    ): TextColumn {
        return Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->searchable($searchable)
            ->sortable($sortable)
            ->toggleable();
    }
}