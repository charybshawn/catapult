<?php

namespace App\Filament\Traits;

use Filament\Tables;

trait HasStatusBadge
{
    /**
     * Get a status badge column with standard colors
     */
    public static function getStatusBadgeColumn(
        string $field = 'status',
        string $label = 'Status',
        array $colorMap = null,
        array $stateFormatting = null
    ): Tables\Columns\TextColumn {
        $defaultColorMap = [
            'active' => 'success',
            'inactive' => 'danger',
            'pending' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            'draft' => 'gray',
            'in_progress' => 'info',
            'in_stock' => 'success',
            'out_of_stock' => 'danger',
            'reorder_needed' => 'warning',
            'low_stock' => 'warning',
            'expired' => 'danger',
            'available' => 'success',
            'unavailable' => 'danger',
            'scheduled' => 'info',
            'published' => 'success',
            'unpublished' => 'gray',
            'paid' => 'success',
            'unpaid' => 'danger',
            'partial' => 'warning',
            'refunded' => 'gray',
            'failed' => 'danger',
            'processing' => 'info',
            'shipped' => 'info',
            'delivered' => 'success',
            'returned' => 'warning',
        ];

        $colors = $colorMap ? array_merge($defaultColorMap, $colorMap) : $defaultColorMap;

        $column = Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->badge()
            ->toggleable();

        // Apply color mapping
        $column->color(fn (string $state): string => $colors[$state] ?? 'gray');

        // Apply state formatting if provided
        if ($stateFormatting) {
            $column->formatStateUsing(fn (string $state): string => $stateFormatting[$state] ?? ucfirst(str_replace('_', ' ', $state)));
        }

        return $column;
    }
    
    /**
     * Get inventory status badge column
     */
    public static function getInventoryStatusBadgeColumn(
        string $field = 'status',
        string $label = 'Status'
    ): Tables\Columns\TextColumn {
        return static::getStatusBadgeColumn($field, $label, [
            'in_stock' => 'success',
            'low_stock' => 'warning',
            'out_of_stock' => 'danger',
            'reorder_needed' => 'warning',
            'discontinued' => 'gray',
        ], [
            'in_stock' => 'In Stock',
            'low_stock' => 'Low Stock',
            'out_of_stock' => 'Out of Stock',
            'reorder_needed' => 'Reorder Needed',
            'discontinued' => 'Discontinued',
        ]);
    }
    
    /**
     * Get order status badge column
     */
    public static function getOrderStatusBadgeColumn(
        string $field = 'status',
        string $label = 'Status'
    ): Tables\Columns\TextColumn {
        return static::getStatusBadgeColumn($field, $label, [
            'pending' => 'warning',
            'processing' => 'info',
            'shipped' => 'info',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'returned' => 'warning',
            'refunded' => 'gray',
        ], [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'returned' => 'Returned',
            'refunded' => 'Refunded',
        ]);
    }
    
    /**
     * Get boolean status badge
     */
    public static function getBooleanStatusBadge(
        string $field,
        string $label,
        string $trueLabel = 'Yes',
        string $falseLabel = 'No',
        string $trueColor = 'success',
        string $falseColor = 'danger'
    ): Tables\Columns\TextColumn {
        return Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->badge()
            ->formatStateUsing(fn (bool $state): string => $state ? $trueLabel : $falseLabel)
            ->color(fn (bool $state): string => $state ? $trueColor : $falseColor)
            ->sortable()
            ->toggleable();
    }
}