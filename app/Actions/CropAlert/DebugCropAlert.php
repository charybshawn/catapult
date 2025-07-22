<?php

namespace App\Actions\CropAlert;

use App\Models\Crop;
use App\Models\CropAlert;

class DebugCropAlert
{
    /**
     * Generate debug information for a crop alert
     */
    public function execute(CropAlert $record): array
    {
        $crop = Crop::find($record->conditions['crop_id'] ?? null);
        
        $alertData = $this->formatAlertData($record);
        $cropData = $this->formatCropData($crop);
        
        return [
            'alert_data' => $alertData,
            'crop_data' => $cropData,
            'html' => $this->formatDebugHtml($alertData, $cropData),
        ];
    }

    /**
     * Format alert data for display
     */
    protected function formatAlertData(CropAlert $record): array
    {
        return [
            'ID' => $record->id,
            'Alert Type' => $record->alert_type,
            'Resource Type' => $record->resource_type,
            'Frequency' => $record->frequency,
            'Is Active' => $record->is_active ? 'Yes' : 'No',
            'Scheduled For' => $record->next_run_at->format('Y-m-d H:i'),
            'Last Executed' => $record->last_run_at ? $record->last_run_at->format('Y-m-d H:i') : 'Never',
            'Conditions' => json_encode($record->conditions, JSON_PRETTY_PRINT),
        ];
    }

    /**
     * Format crop data for display
     */
    protected function formatCropData(?Crop $crop): array
    {
        if (!$crop) {
            return [];
        }

        return [
            'ID' => $crop->id,
            'Tray Number' => $crop->tray_number,
            'Current Stage' => $crop->current_stage,
            'Planted At' => $crop->planting_at->format('Y-m-d H:i'),
            'Germination At' => $crop->germination_at ? $crop->germination_at->format('Y-m-d H:i') : 'N/A',
            'Blackout At' => $crop->blackout_at ? $crop->blackout_at->format('Y-m-d H:i') : 'N/A',
            'Light At' => $crop->light_at ? $crop->light_at->format('Y-m-d H:i') : 'N/A',
            'Harvested At' => $crop->harvested_at ? $crop->harvested_at->format('Y-m-d H:i') : 'N/A',
            'Recipe ID' => $crop->recipe_id,
            'Recipe Name' => $crop->recipe?->name ?? 'N/A',
            'Seed Entry ID' => $crop->recipe?->seed_entry_id ?? 'N/A',
            'Seed Cultivar Name' => $crop->recipe?->seedEntry 
                ? $crop->recipe->seedEntry->common_name . ' - ' . $crop->recipe->seedEntry->cultivar_name 
                : 'N/A',
            'Germination Days' => $crop->recipe?->germination_days ?? 'N/A',
            'Blackout Days' => $crop->recipe?->blackout_days ?? 'N/A',
            'Light Days' => $crop->recipe?->light_days ?? 'N/A',
        ];
    }

    /**
     * Format debug data as HTML for notification display
     */
    protected function formatDebugHtml(array $alertData, array $cropData): string
    {
        $html = '<div class="mb-4">';
        $html .= '<h3 class="text-lg font-medium mb-2">Alert Data</h3>';
        $html .= '<div class="overflow-auto max-h-48 space-y-1">';
        
        foreach ($alertData as $key => $value) {
            $html .= '<div class="flex">';
            $html .= '<span class="font-medium w-32">' . $key . ':</span>';
            $html .= '<span class="text-gray-600">' . $value . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div></div>';
        
        // Format crop data if available
        if (!empty($cropData)) {
            $html .= '<div>';
            $html .= '<h3 class="text-lg font-medium mb-2">Crop Data</h3>';
            $html .= '<div class="overflow-auto max-h-48 space-y-1">';
            
            foreach ($cropData as $key => $value) {
                $html .= '<div class="flex">';
                $html .= '<span class="font-medium w-32">' . $key . ':</span>';
                $html .= '<span class="text-gray-600">' . $value . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div></div>';
        } else {
            $html .= '<div class="text-gray-500">Crop not found</div>';
        }

        return $html;
    }
}