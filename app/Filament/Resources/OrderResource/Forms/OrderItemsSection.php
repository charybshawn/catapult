<?php

namespace App\Filament\Resources\OrderResource\Forms;

use Filament\Schemas\Components\Section;
use App\Forms\Components\InvoiceOrderItems;
use Closure;
use App\Models\Product;
use Filament\Forms;

/**
 * Order items section with comprehensive agricultural product validation.
 * 
 * Manages order line items with complex validation for agricultural products
 * including quantity, pricing, and product selection. Provides user-friendly
 * error handling and business rule validation for order integrity.
 * 
 * @filament_section Order items management with validation
 * @business_context Agricultural product ordering with complex pricing
 * @validation_rules Quantity, pricing, and product selection validation
 */
class OrderItemsSection
{
    /**
     * Create order items section with product selection interface.
     * 
     * Returns configured section containing order items field with product
     * selection, quantity input, and pricing management for agricultural
     * products with complex validation rules.
     * 
     * @return Section Order items section with product management
     * @filament_usage Order form section for item management
     * @business_logic Product selection and pricing configuration
     */
    public static function make(): Section
    {
        return Section::make('Order Items')
            ->schema([
                static::getOrderItemsField(),
            ]);
    }

    /**
     * Create order items field with agricultural product validation.
     * 
     * Returns configured InvoiceOrderItems component with product options,
     * complex validation rules, and business logic for agricultural product
     * ordering including quantity and pricing validation.
     * 
     * @return InvoiceOrderItems Order items field with validation
     * @agricultural_context Product selection for microgreens and agricultural items
     * @business_validation Complex order item validation rules
     */
    protected static function getOrderItemsField(): InvoiceOrderItems
    {
        return InvoiceOrderItems::make('orderItems')
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
     * Create comprehensive validation rule for order items.
     * 
     * Provides business-rule validation ensuring order has valid items with
     * proper quantities and pricing. Handles agricultural product ordering
     * requirements and provides detailed error messaging.
     * 
     * @return Closure Validation closure for order items
     * @business_validation Order integrity and completeness validation
     * @agricultural_rules Product quantity and pricing business rules
     */
    protected static function getOrderItemsValidationRule(): Closure
    {
        return function (string $attribute, $value, Closure $fail) {
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
     * Validate individual order item quantity requirements.
     * 
     * Ensures quantity field is present, numeric, and greater than zero
     * for agricultural product ordering. Provides user-friendly error
     * messages with item positioning for easy correction.
     * 
     * @param array $item Order item data for validation
     * @param int $index Item position for error messaging
     * @param Closure $fail Validation failure callback
     * @return bool True if quantity is valid, false otherwise
     * @business_validation Agricultural product quantity requirements
     */
    protected static function validateItemQuantity(array $item, int $index, Closure $fail): bool
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
     * Validate individual order item pricing requirements.
     * 
     * Ensures price field is present, numeric, and non-negative for
     * agricultural product pricing. Supports complex pricing structures
     * including wholesale discounts and promotional pricing.
     * 
     * @param array $item Order item data for validation
     * @param int $index Item position for error messaging
     * @param Closure $fail Validation failure callback
     * @return bool True if price is valid, false otherwise
     * @business_validation Agricultural product pricing validation
     */
    protected static function validateItemPrice(array $item, int $index, Closure $fail): bool
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