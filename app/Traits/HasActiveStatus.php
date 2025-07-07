<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasActiveStatus
 *
 * Provides common functionality for models with an active/inactive status.
 * This trait assumes the model has either an 'is_active' or 'active' boolean field.
 *
 * @package App\Traits
 */
trait HasActiveStatus
{
    /**
     * Initialize the trait for the model.
     *
     * @return void
     */
    public function initializeHasActiveStatus(): void
    {
        // Add 'is_active' or 'active' to fillable if not already present
        $activeField = $this->getActiveFieldName();
        if (!in_array($activeField, $this->fillable)) {
            $this->fillable[] = $activeField;
        }

        // Add casting for the active field
        if (!isset($this->casts[$activeField])) {
            $this->casts[$activeField] = 'boolean';
        }
    }

    /**
     * Get the name of the active field for this model.
     *
     * @return string
     */
    public function getActiveFieldName(): string
    {
        // Check if model has 'is_active' column
        if (in_array('is_active', $this->getFillable()) || 
            (method_exists($this, 'getConnection') && 
             $this->getConnection() && 
             $this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), 'is_active'))) {
            return 'is_active';
        }
        
        // Default to 'active'
        return 'active';
    }

    /**
     * Scope a query to only include active records.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->getActiveFieldName(), true);
    }

    /**
     * Scope a query to only include inactive records.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where($this->getActiveFieldName(), false);
    }

    /**
     * Determine if the model is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        $field = $this->getActiveFieldName();
        return (bool) $this->getAttribute($field);
    }

    /**
     * Determine if the model is inactive.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return !$this->isActive();
    }

    /**
     * Activate the model.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update([
            $this->getActiveFieldName() => true
        ]);
    }

    /**
     * Deactivate the model.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update([
            $this->getActiveFieldName() => false
        ]);
    }

    /**
     * Toggle the active status.
     *
     * @return bool
     */
    public function toggleActive(): bool
    {
        $field = $this->getActiveFieldName();
        return $this->update([
            $field => !$this->getAttribute($field)
        ]);
    }

    /**
     * Get a human-readable status.
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        return $this->isActive() ? 'Active' : 'Inactive';
    }

    /**
     * Get a badge-friendly status array.
     *
     * @return array
     */
    public function getStatusBadgeAttribute(): array
    {
        return [
            'label' => $this->status,
            'color' => $this->isActive() ? 'success' : 'danger',
            'icon' => $this->isActive() ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle',
        ];
    }
}