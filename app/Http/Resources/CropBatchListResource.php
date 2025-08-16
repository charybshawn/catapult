<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Data Transfer Object for crop batch list display
 * Provides clean data contract for Filament tables
 */
class CropBatchListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Transform the already-computed data from CropBatchDisplayService
        return [
            'id' => $this['id'],
            'recipe_name' => $this['recipe_name'],
            'crop_count' => $this['crop_count'],
            'tray_count' => $this['crop_count'], // Alias for crop_count
            'current_stage_name' => $this['current_stage_name'],
            'current_stage_code' => $this['current_stage_code'],
            'stage_age_display' => $this['stage_age_display'],
            'time_to_next_stage_display' => $this['time_to_next_stage_display'],
            'total_age_display' => $this['total_age_display'],
            'tray_numbers' => $this['tray_numbers'],
            'tray_numbers_formatted' => $this['tray_numbers_formatted'],
            'germination_at' => $this['germination_at'],
            'germination_date_formatted' => $this['germination_date_formatted'],
            'expected_harvest_at' => $this['expected_harvest_at'],
            'expected_harvest_formatted' => $this['expected_harvest_formatted'],
            'created_at' => $this['created_at'],
            'updated_at' => $this['updated_at'],
            
            // Additional computed fields for backward compatibility
            'recipe_id' => $this['id'], // Can be retrieved from the original model if needed
            'variety_name' => $this['variety_name'] ?? $this['recipe_name'],
        ];
    }

    /**
     * Create a resource collection from crop batch data
     */
    public static function collection($resource)
    {
        return parent::collection($resource);
    }

    /**
     * Transform a single item for detailed display
     */
    public static function forDetail(array $data): array
    {
        return array_merge($data, [
            // Additional fields specific to detailed view
            'formatted_created_at' => $data['created_at']?->format('M j, Y g:i A'),
            'formatted_updated_at' => $data['updated_at']?->diffForHumans(),
        ]);
    }
}