<?php

namespace App\Filament\Support;

class ValidationRules
{
    /**
     * Common validation rules for names
     */
    public static function name(): array
    {
        return [
            'required',
            'string',
            'max:255',
        ];
    }

    /**
     * Common validation rules for descriptions
     */
    public static function description(): array
    {
        return [
            'nullable',
            'string',
            'max:1000',
        ];
    }

    /**
     * Common validation rules for email addresses
     */
    public static function email(): array
    {
        return [
            'nullable',
            'email',
            'max:255',
        ];
    }

    /**
     * Common validation rules for phone numbers
     */
    public static function phone(): array
    {
        return [
            'nullable',
            'string',
            'max:20',
        ];
    }

    /**
     * Common validation rules for prices
     */
    public static function price(): array
    {
        return [
            'required',
            'numeric',
            'min:0',
            'max:999999.99',
        ];
    }

    /**
     * Common validation rules for quantities
     */
    public static function quantity(): array
    {
        return [
            'required',
            'numeric',
            'min:0',
        ];
    }

    /**
     * Common validation rules for weights
     */
    public static function weight(): array
    {
        return [
            'nullable',
            'numeric',
            'min:0',
            'max:999999.999',
        ];
    }

    /**
     * Common validation rules for percentages
     */
    public static function percentage(): array
    {
        return [
            'nullable',
            'numeric',
            'min:0',
            'max:100',
        ];
    }

    /**
     * Common validation rules for currency codes
     */
    public static function currency(): array
    {
        return [
            'required',
            'string',
            'in:USD,CAD,EUR,GBP',
        ];
    }

    /**
     * Common validation rules for lot numbers
     */
    public static function lotNumber(): array
    {
        return [
            'nullable',
            'string',
            'max:50',
            'uppercase',
        ];
    }

    /**
     * Common validation rules for SKUs
     */
    public static function sku(): array
    {
        return [
            'nullable',
            'string',
            'max:100',
            'alpha_dash',
        ];
    }

    /**
     * Common validation rules for URLs
     */
    public static function url(): array
    {
        return [
            'nullable',
            'url',
            'max:2048',
        ];
    }

    /**
     * Common validation rules for dates
     */
    public static function date(): array
    {
        return [
            'required',
            'date',
        ];
    }

    /**
     * Common validation rules for future dates
     */
    public static function futureDate(): array
    {
        return [
            'required',
            'date',
            'after:today',
        ];
    }

    /**
     * Common validation rules for past dates
     */
    public static function pastDate(): array
    {
        return [
            'required',
            'date',
            'before_or_equal:today',
        ];
    }

    /**
     * Common validation rules for measurement units
     */
    public static function measurementUnit(): array
    {
        return [
            'required',
            'string',
            'in:g,kg,oz,lb,ml,l,tsp,tbsp,cup,pieces,units',
        ];
    }

    /**
     * Common validation rules for supplier types
     */
    public static function supplierType(): array
    {
        return [
            'required',
            'string',
            'in:seed,soil,packaging,other',
        ];
    }

    /**
     * Common validation rules for consumable types
     */
    public static function consumableType(): array
    {
        return [
            'required',
            'string',
            'in:packaging,soil,seed,label,other',
        ];
    }

    /**
     * Common validation rules for customer types
     */
    public static function customerType(): array
    {
        return [
            'required',
            'string',
            'in:retail,wholesale',
        ];
    }

    /**
     * Common validation rules for status fields
     */
    public static function status(array $allowedStatuses): array
    {
        return [
            'required',
            'string',
            'in:' . implode(',', $allowedStatuses),
        ];
    }

    /**
     * Common validation rules for boolean flags
     */
    public static function boolean(): array
    {
        return [
            'boolean',
        ];
    }

    /**
     * Common validation rules for positive integers
     */
    public static function positiveInteger(): array
    {
        return [
            'required',
            'integer',
            'min:1',
        ];
    }

    /**
     * Common validation rules for non-negative integers
     */
    public static function nonNegativeInteger(): array
    {
        return [
            'required',
            'integer',
            'min:0',
        ];
    }
}