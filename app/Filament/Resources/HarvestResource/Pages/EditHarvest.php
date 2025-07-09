<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use App\Filament\Resources\HarvestResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;
use Illuminate\Database\Eloquent\Model;

class EditHarvest extends BaseEditRecord
{
    protected static string $resource = HarvestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing crop relationships
        $data['crops'] = $this->record->crops->map(function ($crop) {
            return [
                'crop_id' => $crop->id,
                'harvested_weight_grams' => $crop->pivot->harvested_weight_grams,
                'percentage_harvested' => $crop->pivot->percentage_harvested,
                'notes' => $crop->pivot->notes,
            ];
        })->toArray();
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $crops = $data['crops'] ?? [];
        unset($data['crops']);
        
        // Update the harvest record
        $record->update($data);
        
        // Sync crops with pivot data
        $syncData = [];
        foreach ($crops as $crop) {
            $syncData[$crop['crop_id']] = [
                'harvested_weight_grams' => $crop['harvested_weight_grams'],
                'percentage_harvested' => $crop['percentage_harvested'] ?? 100,
                'notes' => $crop['notes'] ?? null,
            ];
        }
        
        $record->crops()->sync($syncData);
        
        return $record;
    }
}
