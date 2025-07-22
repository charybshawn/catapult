<?php

namespace App\Actions\Order;

use Carbon\Carbon;

/**
 * Handle crop data validation and transformation for Order relation manager
 */
class ValidateCropDataAction
{
    /**
     * Transform form data for creating a new crop
     */
    public function transformForCreate(array $data, int $orderId): array
    {
        $transformedData = $this->transformWateringField($data);
        $transformedData['order_id'] = $orderId;
        
        return $transformedData;
    }

    /**
     * Transform form data for updating an existing crop
     */
    public function transformForUpdate(array $data): array
    {
        return $this->transformWateringField($data);
    }

    /**
     * Transform the watering_suspended boolean field to watering_suspended_at timestamp
     */
    private function transformWateringField(array $data): array
    {
        $watering_suspended = $data['watering_suspended'] ?? false;
        unset($data['watering_suspended']);
        
        $data['watering_suspended_at'] = $watering_suspended ? Carbon::now() : null;
        
        return $data;
    }

    /**
     * Get recipe selection options with formatted display
     */
    public function getRecipeOptionLabel($record): string
    {
        if (!$record->seedEntry) {
            return $record->name;
        }
        
        return "{$record->seedEntry->cultivar_name} ({$record->name})";
    }

    /**
     * Get stage options for form select
     */
    public function getStageOptions(): array
    {
        return [
            'germination' => 'Germination',
            'blackout' => 'Blackout',
            'light' => 'Light',
            'harvested' => 'Harvested',
        ];
    }

    /**
     * Get stage badge colors for table display
     */
    public function getStageBadgeColor(string $state): string
    {
        return match ($state) {
            'germination' => 'info',
            'blackout' => 'warning',
            'light' => 'success',
            'harvested' => 'gray',
            default => 'gray',
        };
    }
}