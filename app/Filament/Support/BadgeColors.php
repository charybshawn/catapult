<?php

namespace App\Filament\Support;

class BadgeColors
{
    /**
     * Standard color mapping for common status values
     */
    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            // Success states
            'active', 'completed', 'delivered', 'harvested', 'success', 'confirmed', 'paid' => 'success',
            
            // Danger states  
            'inactive', 'cancelled', 'failed', 'error', 'deleted', 'expired' => 'danger',
            
            // Warning states
            'pending', 'processing', 'germination', 'blackout', 'light', 'warning', 'due_soon', 'overdue' => 'warning',
            
            // Info states
            'draft', 'paused', 'info', 'ready', 'planted' => 'info',
            
            // Default
            default => 'gray',
        };
    }

    /**
     * Color mapping for crop stages
     */
    public static function getCropStageColor(string $stage): string
    {
        return match ($stage) {
            'germination' => 'info',
            'blackout' => 'warning', 
            'light' => 'primary',
            'harvested' => 'success',
            default => 'gray',
        };
    }

    /**
     * Color mapping for order statuses
     */
    public static function getOrderStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'planted' => 'primary',
            'harvested' => 'success', 
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Color mapping for payment statuses
     */
    public static function getPaymentStatusColor(string $status): string
    {
        return match ($status) {
            'pending' => 'warning',
            'paid' => 'success',
            'failed' => 'danger',
            'refunded' => 'info',
            default => 'gray',
        };
    }

    /**
     * Color mapping for invoice statuses
     */
    public static function getInvoiceStatusColor(string $status): string
    {
        return match ($status) {
            'draft' => 'gray',
            'sent' => 'info',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Color mapping for stock levels
     */
    public static function getStockLevelColor(float $currentStock, float $threshold): string
    {
        if ($currentStock <= 0) {
            return 'danger';
        }
        
        if ($currentStock <= $threshold) {
            return 'warning';
        }
        
        if ($currentStock <= $threshold * 2) {
            return 'info';
        }
        
        return 'success';
    }

    /**
     * Color mapping for priority levels
     */
    public static function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            'critical', 'urgent' => 'danger',
            'high' => 'warning',
            'medium', 'normal' => 'info',
            'low' => 'success',
            default => 'gray',
        };
    }

    /**
     * Color mapping for time-based statuses (age, due dates, etc.)
     */
    public static function getTimeStatusColor(string $timeStatus): string
    {
        return match ($timeStatus) {
            'on_track', 'early' => 'success',
            'due_soon', 'nearly_ready' => 'warning',
            'overdue', 'past_due', 'late' => 'danger',
            'ready_to_harvest', 'ready' => 'info',
            default => 'gray',
        ];
    }

    /**
     * Color mapping for boolean active/inactive states
     */
    public static function getActiveColor(bool $isActive): string
    {
        return $isActive ? 'success' : 'danger';
    }

    /**
     * Color mapping for availability states
     */
    public static function getAvailabilityColor(string $availability): string
    {
        return match ($availability) {
            'in_stock', 'available' => 'success',
            'low_stock', 'limited' => 'warning',
            'out_of_stock', 'unavailable' => 'danger',
            'backordered', 'on_order' => 'info',
            default => 'gray',
        };
    }

    /**
     * Get all available badge colors
     */
    public static function getAllColors(): array
    {
        return [
            'primary',
            'secondary', 
            'success',
            'danger',
            'warning',
            'info',
            'gray',
        ];
    }

    /**
     * Get semantic color names with descriptions
     */
    public static function getColorMeanings(): array
    {
        return [
            'success' => 'Positive states, completed actions, active items',
            'danger' => 'Negative states, errors, cancellations, inactive items',
            'warning' => 'Caution states, pending actions, items needing attention',
            'info' => 'Informational states, draft items, neutral status',
            'primary' => 'Important items, current actions, highlighted states',
            'gray' => 'Default state, unknown status, disabled items',
        ];
    }
}