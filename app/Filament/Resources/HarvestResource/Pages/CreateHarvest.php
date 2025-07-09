<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use App\Filament\Resources\HarvestResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseCreateRecord;
use App\Models\Harvest;
use Illuminate\Database\Eloquent\Model;

class CreateHarvest extends BaseCreateRecord
{
    protected static string $resource = HarvestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Calculate total weight and tray count from selected crops
        $totalWeight = 0;
        $totalTrays = 0;
        
        if (isset($data['crops']) && is_array($data['crops'])) {
            foreach ($data['crops'] as $crop) {
                $totalWeight += $crop['harvested_weight_grams'] ?? 0;
                $totalTrays += ($crop['percentage_harvested'] ?? 100) / 100;
            }
        }
        
        $data['total_weight_grams'] = $totalWeight;
        $data['tray_count'] = round($totalTrays, 2);
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $crops = $data['crops'] ?? [];
        unset($data['crops']);
        
        // Create the harvest record
        $harvest = static::getModel()::create($data);
        
        // Attach crops with pivot data
        if (!empty($crops)) {
            foreach ($crops as $crop) {
                $harvest->crops()->attach($crop['crop_id'], [
                    'harvested_weight_grams' => $crop['harvested_weight_grams'],
                    'percentage_harvested' => $crop['percentage_harvested'] ?? 100,
                    'notes' => $crop['notes'] ?? null,
                ]);
            }
        }
        
        return $harvest;
    }
}
