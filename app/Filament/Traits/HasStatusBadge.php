<?php

namespace App\Filament\Traits;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables;

/**
 * Has Status Badge Trait
 * 
 * Standardized status badge column patterns for agricultural Filament resources.
 * Provides comprehensive status visualization with agricultural workflow-specific
 * color coding and formatting for various entity states and conditions.
 * 
 * @filament_trait Status badge patterns for agricultural resource tables
 * @agricultural_use Status visualization for crops, orders, inventory, and agricultural processes
 * @status_types Production stages, order fulfillment, inventory levels, payment status
 * @color_coding Agricultural workflow-appropriate color schemes for status visualization
 * 
 * Key features:
 * - Comprehensive agricultural status color mapping
 * - Specialized inventory status badges for agricultural supplies
 * - Order status badges for agricultural fulfillment workflows
 * - Boolean status badges for agricultural entity properties
 * - Customizable color and formatting overrides for specific agricultural contexts
 * 
 * @package App\Filament\Traits
 * @author Shawn
 * @since 2024
 */
trait HasStatusBadge
{
    /**
     * Get a status badge column with agricultural workflow color mapping.
     * 
     * @agricultural_context Status badges for agricultural entities with workflow-appropriate colors
     * @param string $field Status field name
     * @param string $label Column display label
     * @param array|null $colorMap Custom color overrides for specific agricultural statuses
     * @param array|null $stateFormatting Custom formatting overrides for status display
     * @return TextColumn Status badge column with agricultural color coding
     * @color_scheme Success (active, completed, harvested), Warning (pending, low stock), Danger (cancelled, out of stock)
     */
    public static function getStatusBadgeColumn(
        string $field = 'status',
        string $label = 'Status',
        array $colorMap = null,
        array $stateFormatting = null
    ): TextColumn {
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

        $column = TextColumn::make($field)
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
     * Get inventory status badge column for agricultural supplies.
     * 
     * @agricultural_context Inventory status badges for seeds, soil, packaging, and consumables
     * @param string $field Status field name
     * @param string $label Column display label
     * @return TextColumn Inventory-specific status badge with agricultural supply management colors
     * @status_types In Stock, Low Stock, Out of Stock, Reorder Needed, Discontinued
     */
    public static function getInventoryStatusBadgeColumn(
        string $field = 'status',
        string $label = 'Status'
    ): TextColumn {
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
     * Get order status badge column for agricultural product orders.
     * 
     * @agricultural_context Order status badges for agricultural product fulfillment workflow
     * @param string $field Status field name
     * @param string $label Column display label
     * @return TextColumn Order-specific status badge with fulfillment workflow colors
     * @fulfillment_stages Pending, Processing, Shipped, Delivered, Cancelled, Returned, Refunded
     */
    public static function getOrderStatusBadgeColumn(
        string $field = 'status',
        string $label = 'Status'
    ): TextColumn {
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
     * Get boolean status badge for agricultural entity properties.
     * 
     * @agricultural_context Boolean property badges for agricultural entities (active/inactive, available/unavailable)
     * @param string $field Boolean field name
     * @param string $label Column display label
     * @param string $trueLabel Display label for true state
     * @param string $falseLabel Display label for false state
     * @param string $trueColor Color for true state
     * @param string $falseColor Color for false state
     * @return TextColumn Boolean status badge with customizable labels and colors
     * @use_cases Active status, availability, organic certification, seasonal availability
     */
    public static function getBooleanStatusBadge(
        string $field,
        string $label,
        string $trueLabel = 'Yes',
        string $falseLabel = 'No',
        string $trueColor = 'success',
        string $falseColor = 'danger'
    ): TextColumn {
        return TextColumn::make($field)
            ->label($label)
            ->badge()
            ->formatStateUsing(fn (bool $state): string => $state ? $trueLabel : $falseLabel)
            ->color(fn (bool $state): string => $state ? $trueColor : $falseColor)
            ->sortable()
            ->toggleable();
    }
}