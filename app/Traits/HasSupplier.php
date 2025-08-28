<?php

namespace App\Traits;

use App\Models\SupplierType;
use Illuminate\Support\Collection;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Has Supplier Trait
 * 
 * Comprehensive supplier relationship management for agricultural Eloquent models.
 * Provides standardized supplier integration, filtering, and relationship handling
 * essential for agricultural supply chain management.
 * 
 * @model_trait Supplier relationship management for agricultural entities
 * @agricultural_use Supplier tracking for seeds, soil, packaging, and agricultural consumables
 * @supply_chain Agricultural supply chain relationship management and vendor tracking
 * @business_context Supplier performance analysis, vendor management, and procurement workflows
 * 
 * Key features:
 * - Automatic supplier relationship setup with proper foreign key handling
 * - Supplier-based query scopes for agricultural supply filtering
 * - Active supplier filtering for operational supplier management
 * - Supplier type-based filtering for agricultural vendor categorization
 * - Supplier name resolution and automatic supplier creation
 * - Agricultural supplier analytics and usage tracking
 * 
 * @package App\Traits
 * @author Shawn
 * @since 2024
 */
trait HasSupplier
{
    /**
     * Initialize the trait for the model.
     *
     * @return void
     */
    public function initializeHasSupplier(): void
    {
        // Add 'supplier_id' to fillable if not already present
        if (!in_array('supplier_id', $this->fillable)) {
            $this->fillable[] = 'supplier_id';
        }

        // Add casting for the supplier_id field
        if (!isset($this->casts['supplier_id'])) {
            $this->casts['supplier_id'] = 'integer';
        }
    }

    /**
     * Get the supplier that owns this model.
     *
     * @return BelongsTo
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Scope a query to only include records from a specific supplier.
     *
     * @param Builder $query
     * @param int|Supplier $supplier
     * @return Builder
     */
    public function scopeFromSupplier(Builder $query, $supplier): Builder
    {
        $supplierId = $supplier instanceof Supplier ? $supplier->id : $supplier;
        return $query->where('supplier_id', $supplierId);
    }

    /**
     * Scope a query to only include records from active suppliers.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeFromActiveSuppliers(Builder $query): Builder
    {
        return $query->whereHas('supplier', function (Builder $q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Scope a query to only include records from suppliers of a specific type.
     *
     * @param Builder $query
     * @param string $typeCode
     * @return Builder
     */
    public function scopeFromSupplierType(Builder $query, string $typeCode): Builder
    {
        return $query->whereHas('supplier.supplierType', function (Builder $q) use ($typeCode) {
            $q->where('code', $typeCode);
        });
    }

    /**
     * Scope a query to exclude records from a specific supplier.
     *
     * @param Builder $query
     * @param int|Supplier $supplier
     * @return Builder
     */
    public function scopeNotFromSupplier(Builder $query, $supplier): Builder
    {
        $supplierId = $supplier instanceof Supplier ? $supplier->id : $supplier;
        return $query->where('supplier_id', '!=', $supplierId);
    }

    /**
     * Check if this model has a supplier assigned.
     *
     * @return bool
     */
    public function hasSupplier(): bool
    {
        return !is_null($this->supplier_id);
    }

    /**
     * Check if this model is from a specific supplier.
     *
     * @param int|Supplier $supplier
     * @return bool
     */
    public function isFromSupplier($supplier): bool
    {
        $supplierId = $supplier instanceof Supplier ? $supplier->id : $supplier;
        return $this->supplier_id === $supplierId;
    }

    /**
     * Check if the supplier is active.
     *
     * @return bool
     */
    public function hasActiveSupplier(): bool
    {
        return $this->supplier && $this->supplier->is_active;
    }

    /**
     * Get the supplier name.
     *
     * @return string|null
     */
    public function getSupplierNameAttribute(): ?string
    {
        return $this->supplier?->name;
    }

    /**
     * Get the supplier type code.
     *
     * @return string|null
     */
    public function getSupplierTypeCodeAttribute(): ?string
    {
        return $this->supplier?->supplierType?->code;
    }

    /**
     * Get the supplier type name.
     *
     * @return string|null
     */
    public function getSupplierTypeNameAttribute(): ?string
    {
        return $this->supplier?->supplierType?->name;
    }

    /**
     * Check if this model is from a supplier of a specific type.
     *
     * @param string $typeCode
     * @return bool
     */
    public function isFromSupplierType(string $typeCode): bool
    {
        return $this->supplier_type_code === $typeCode;
    }

    /**
     * Set the supplier by name (creates if doesn't exist).
     *
     * @param string $supplierName
     * @param array $additionalData
     * @return void
     */
    public function setSupplierByName(string $supplierName, array $additionalData = []): void
    {
        // First try to find existing supplier
        $supplier = Supplier::where('name', $supplierName)->first();
        
        if (!$supplier) {
            // Get the default supplier type if not provided
            if (!isset($additionalData['supplier_type_id'])) {
                $otherType = SupplierType::where('code', 'other')->first();
                if ($otherType) {
                    $additionalData['supplier_type_id'] = $otherType->id;
                }
            }
            
            $supplier = Supplier::create(
                array_merge(
                    ['name' => $supplierName, 'is_active' => true],
                    $additionalData
                )
            );
        }
        
        $this->supplier_id = $supplier->id;
    }

    /**
     * Get all unique supplier IDs used by this model class.
     *
     * @return Collection
     */
    public static function getUsedSupplierIds(): Collection
    {
        return static::query()
            ->whereNotNull('supplier_id')
            ->distinct()
            ->pluck('supplier_id');
    }

    /**
     * Get all unique suppliers used by this model class.
     *
     * @return Collection
     */
    public static function getUsedSuppliers(): Collection
    {
        $supplierIds = static::getUsedSupplierIds();
        return Supplier::whereIn('id', $supplierIds)->get();
    }
}