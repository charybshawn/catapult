<?php

namespace App\Filament\Resources\Base;

use Filament\Resources\Resource;
use Filament\Tables;
use App\Filament\Tables\Components\Common as TableCommon;

abstract class BaseResource extends Resource
{
    /**
     * Get default table actions (View, Edit, Delete)
     */
    protected static function getDefaultTableActions(): array
    {
        return TableCommon::defaultActions();
    }

    /**
     * Get default bulk actions (Delete + Active/Inactive)
     */
    protected static function getDefaultBulkActions(): Tables\Actions\BulkActionGroup
    {
        return TableCommon::defaultBulkActions();
    }

    /**
     * Get timestamp columns for created_at and updated_at
     */
    protected static function getTimestampColumns(): array
    {
        return TableCommon::timestampColumns();
    }

    /**
     * Get active/inactive badge column
     */
    protected static function getActiveBadgeColumn(): Tables\Columns\IconColumn
    {
        return TableCommon::activeBadge();
    }

    /**
     * Get status badge column with standard color mapping
     */
    protected static function getStatusBadgeColumn(string $field = 'status'): Tables\Columns\TextColumn
    {
        return TableCommon::statusBadge($field);
    }

    /**
     * Get relationship column for foreign key relationships
     */
    protected static function getRelationshipColumn(
        string $field,
        string $label,
        string $attribute = 'name'
    ): Tables\Columns\TextColumn {
        return TableCommon::relationshipColumn($field, $label, $attribute);
    }

    /**
     * Get price column with currency formatting
     */
    protected static function getPriceColumn(
        string $field = 'price',
        string $label = 'Price',
        string $currency = 'USD'
    ): Tables\Columns\TextColumn {
        return TableCommon::priceColumn($field, $label, $currency);
    }

    /**
     * Get date column
     */
    protected static function getDateColumn(string $field, string $label): Tables\Columns\TextColumn
    {
        return TableCommon::dateColumn($field, $label);
    }

    /**
     * Get datetime column
     */
    protected static function getDateTimeColumn(string $field, string $label): Tables\Columns\TextColumn
    {
        return TableCommon::datetimeColumn($field, $label);
    }

    /**
     * Get numeric column with formatting
     */
    protected static function getNumericColumn(
        string $field,
        string $label,
        int $decimalPlaces = 2,
        ?string $suffix = null
    ): Tables\Columns\TextColumn {
        return TableCommon::numericColumn($field, $label, $decimalPlaces, $suffix);
    }

    /**
     * Get searchable text column
     */
    protected static function getTextColumn(string $field, string $label): Tables\Columns\TextColumn
    {
        return TableCommon::textColumn($field, $label);
    }

    /**
     * Get truncated text column for long content
     */
    protected static function getTruncatedTextColumn(
        string $field,
        string $label,
        int $limit = 50
    ): Tables\Columns\TextColumn {
        return TableCommon::truncatedTextColumn($field, $label, $limit);
    }

    /**
     * Check if the resource should use active/inactive functionality
     */
    protected static function hasActiveStatus(): bool
    {
        return property_exists(static::getModel(), 'is_active') || 
               in_array('is_active', (new (static::getModel()))->getFillable());
    }

    /**
     * Get standard table configuration
     */
    public static function getStandardTableConfiguration(): array
    {
        return [
            'defaultSort' => 'created_at',
            'defaultSortDirection' => 'desc',
            'striped' => true,
        ];
    }

    /**
     * Get standard pagination options
     */
    public static function getStandardPaginationOptions(): array
    {
        return [10, 25, 50, 100];
    }

    /**
     * Get standard record title attribute
     */
    public static function getRecordTitleAttribute(): ?string
    {
        return 'name';
    }

    /**
     * Default redirect behavior after creating a record.
     * Returns to the index page unless overridden.
     */
    protected static function getRedirectUrlAfterCreate(): string
    {
        return static::getUrl('index');
    }

    /**
     * Default redirect behavior after editing a record.
     * Returns to the index page unless overridden.
     */
    protected static function getRedirectUrlAfterEdit(): string
    {
        return static::getUrl('index');
    }

    /**
     * Default redirect behavior after deleting a record.
     * Returns to the index page unless overridden.
     */
    protected static function getRedirectUrlAfterDelete(): string
    {
        return static::getUrl('index');
    }
}