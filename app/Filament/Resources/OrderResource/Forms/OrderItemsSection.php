<?php

namespace App\Filament\Resources\OrderResource\Forms;

use App\Models\Product;
use Filament\Forms;

/**
 * Order Items Section for Orders - Handles order items with complex validation
 * Extracted from OrderResource lines 308-359
 * Following Filament Resource Architecture Guide patterns
 */
class OrderItemsSection
{
    /**
     * Get the order items section schema
     */
    public static function make(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Order Items')
            ->schema([
                static::getOrderItemsField(),
            ]);
    }

    /**
     * Get the order items field with complex validation
     */
    protected static function getOrderItemsField(): \App\Forms\Components\InvoiceOrderItems
    {
        return \App\Forms\Components\InvoiceOrderItems::make('orderItems')
            ->label('Items')
            ->productOptions(fn () => Product::query()->orderBy('name')->pluck('name', 'id')->toArray())
            ->required()
            ->rules([
                'array',
                'min:1',
                function () {
                    return static::getOrderItemsValidationRule();
                }
            ]);
    }

    /**
     * Get complex validation rule for order items
     * TODO: Consider extracting to OrderItemValidationAction for complex business logic
     */
    protected static function getOrderItemsValidationRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            if (!is_array($value)) {
                $fail('Order must have at least one item.');
                return;
            }
            
            $hasValidItems = false;
            foreach ($value as $index => $item) {
                // Check if item has required fields
                if (empty($item['item_id'])) {
                    continue; // Skip empty rows
                }
                
                // Validate quantity
                if (!static::validateItemQuantity($item, $index, $fail)) {
                    continue;
                }
                
                // Validate price
                if (!static::validateItemPrice($item, $index, $fail)) {
                    continue;
                }
                
                if (!empty($item['item_id'])) {
                    $hasValidItems = true;
                }
            }
            
            if (!$hasValidItems) {
                $fail('Order must have at least one valid item.');
            }
        };
    }

    /**
     * Validate item quantity field
     */
    protected static function validateItemQuantity(array $item, int $index, \Closure $fail): bool
    {
        if (!isset($item['quantity']) || $item['quantity'] === null || $item['quantity'] === '') {
            $fail("Item " . ($index + 1) . ": Quantity is required.");
            return false;
        }

        // Handle both string and numeric values
        $qty = is_string($item['quantity']) ? trim($item['quantity']) : $item['quantity'];
        if (!is_numeric($qty) || floatval($qty) <= 0) {
            $fail("Item " . ($index + 1) . ": Quantity must be a number greater than 0.");
            return false;
        }

        return true;
    }

    /**
     * Validate item price field
     */
    protected static function validateItemPrice(array $item, int $index, \Closure $fail): bool
    {
        if (!isset($item['price']) || $item['price'] === null || $item['price'] === '') {
            $fail("Item " . ($index + 1) . ": Price is required.");
            return false;
        }

        if (!is_numeric($item['price']) || floatval($item['price']) < 0) {
            $fail("Item " . ($index + 1) . ": Price must be 0 or greater.");
            return false;
        }

        return true;
    }
}