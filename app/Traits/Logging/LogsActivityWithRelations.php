<?php

namespace App\Traits\Logging;

use Illuminate\Database\Eloquent\Collection;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Logs Activity With Relations Trait
 * 
 * Enhanced activity logging for agricultural Eloquent models that automatically
 * includes related model data for comprehensive agricultural workflow tracking.
 * Provides deep relationship logging for complex agricultural entity relationships.
 * 
 * @logging_trait Enhanced activity logging with relationship data inclusion
 * @agricultural_use Activity logging for complex agricultural relationships (orders->items->products->varieties)
 * @audit_trail Comprehensive audit trails for agricultural entity changes with related data
 * @relationship_tracking Deep relationship tracking for agricultural supply chain workflows
 * 
 * Key features:
 * - Configurable relationship inclusion for agricultural entity logging
 * - Nested relationship loading with depth control for complex agricultural hierarchies
 * - Sensitive data filtering for secure agricultural data logging
 * - Performance-optimized relationship loading for large agricultural datasets
 * - Flexible attribute selection for targeted agricultural relationship logging
 * 
 * @package App\Traits\Logging
 * @author Shawn
 * @since 2024
 */
trait LogsActivityWithRelations
{
    /**
     * Get the relationships that should be logged with this agricultural model.
     * 
     * @agricultural_context Specify agricultural relationships to include in activity logging
     * @return array Relationship names for agricultural entity logging
     * @override Override in agricultural models to specify relevant relationships
     * @example ['masterSeedCatalog', 'supplier', 'priceVariations', 'orderItems']
     */
    public function getLoggedRelationships(): array
    {
        return [];
    }

    /**
     * Get the maximum depth for loading nested relationships.
     *
     * @return int
     */
    public function getRelationshipLoggingDepth(): int
    {
        return 2;
    }

    /**
     * Get specific attributes to include from related models.
     * Return an array with relationship names as keys and attribute arrays as values.
     * Use '*' to include all attributes.
     *
     * @return array
     */
    public function getRelationshipAttributesToLog(): array
    {
        return [];
    }

    /**
     * Load and format agricultural relationships for comprehensive activity logging.
     * 
     * @agricultural_context Load related agricultural data for complete audit trail
     * @param array $relationships Specific relationships to load (empty for all configured)
     * @param int $depth Current relationship loading depth for nested agricultural hierarchies
     * @return array Formatted relationship data for agricultural activity logging
     * @performance Optimized for agricultural entity relationship complexity
     */
    public function loadRelationshipsForLogging(array $relationships = [], int $depth = 0): array
    {
        if ($depth >= $this->getRelationshipLoggingDepth()) {
            return [];
        }

        $relationshipsToLoad = empty($relationships) ? $this->getLoggedRelationships() : $relationships;
        $relationshipData = [];

        foreach ($relationshipsToLoad as $relation) {
            if (!method_exists($this, $relation)) {
                continue;
            }

            try {
                // Load the relationship if not already loaded
                if (!$this->relationLoaded($relation)) {
                    $this->load($relation);
                }

                $relatedData = $this->getRelation($relation);
                
                if ($relatedData === null) {
                    $relationshipData[$relation] = null;
                    continue;
                }

                // Get attributes to log for this relationship
                $attributesToLog = $this->getRelationshipAttributesToLog()[$relation] ?? ['id', 'name'];
                
                // Handle different relationship types
                if ($relatedData instanceof Collection) {
                    // HasMany, BelongsToMany, MorphMany, etc.
                    $relationshipData[$relation] = $relatedData->map(function ($model) use ($attributesToLog, $depth, $relation) {
                        return $this->formatRelatedModel($model, $attributesToLog, $depth);
                    })->toArray();
                } else {
                    // BelongsTo, HasOne, MorphOne, etc.
                    $relationshipData[$relation] = $this->formatRelatedModel($relatedData, $attributesToLog, $depth);
                }
            } catch (Exception $e) {
                // Log the error but don't break the activity logging
                $relationshipData[$relation] = ['error' => 'Failed to load relationship: ' . $e->getMessage()];
            }
        }

        return $relationshipData;
    }

    /**
     * Format a related agricultural model for activity logging.
     * 
     * @agricultural_context Format related agricultural entities with essential data and metadata
     * @param Model $model Related agricultural model to format for logging
     * @param array $attributes Attributes to include from the related agricultural model
     * @param int $depth Current relationship depth for nested agricultural hierarchies
     * @return array Formatted model data with agricultural metadata and timestamps
     * @security Automatically excludes sensitive attributes for secure agricultural logging
     */
    protected function formatRelatedModel(Model $model, array $attributes, int $depth): array
    {
        $data = [
            '_model' => class_basename($model),
            '_id' => $model->getKey(),
        ];

        // If all attributes requested, get them but exclude sensitive ones
        if (in_array('*', $attributes)) {
            $allAttributes = $model->toArray();
            // Remove sensitive attributes
            $sensitiveAttributes = ['password', 'remember_token', 'api_token', 'secret', 'token'];
            $data = array_merge($data, Arr::except($allAttributes, $sensitiveAttributes));
        } else {
            // Get only specified attributes
            foreach ($attributes as $attribute) {
                if ($model->hasAttribute($attribute) || $model->relationLoaded($attribute)) {
                    $data[$attribute] = $model->getAttribute($attribute);
                }
            }
        }

        // Add timestamps if available
        if ($model->timestamps) {
            $data['_created_at'] = $model->created_at?->toIso8601String();
            $data['_updated_at'] = $model->updated_at?->toIso8601String();
        }

        // Check if this model also uses relationship logging and load nested relationships
        if (in_array(LogsActivityWithRelations::class, class_uses_recursive($model))) {
            $nestedRelations = $model->loadRelationshipsForLogging([], $depth + 1);
            if (!empty($nestedRelations)) {
                $data['_relations'] = $nestedRelations;
            }
        }

        return $data;
    }

    /**
     * Override the tapActivity method to include relationships.
     */
    public function tapActivity($activity, string $eventName)
    {
        // Call parent tapActivity if it exists
        if (method_exists(parent::class, 'tapActivity')) {
            parent::tapActivity($activity, $eventName);
        }

        // Add relationship data
        $relationships = $this->loadRelationshipsForLogging();
        if (!empty($relationships)) {
            $properties = $activity->properties ?: collect();
            $properties->put('relationships', $relationships);
            $activity->properties = $properties;
        }

        return $activity;
    }
}