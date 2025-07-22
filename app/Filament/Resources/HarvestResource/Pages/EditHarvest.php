<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use App\Actions\Harvest\CreateHarvestAction;
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

    /**
     * Load existing crop relationships for form display
     */
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

    /**
     * Use the CreateHarvestAction for record updates
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(CreateHarvestAction::class)->update($record, $data);
    }

    /**
     * Custom success notification message
     */
    protected function getSavedNotificationTitle(): ?string
    {
        return 'Harvest updated successfully';
    }
}
