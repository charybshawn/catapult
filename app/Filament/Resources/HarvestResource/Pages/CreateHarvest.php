<?php

namespace App\Filament\Resources\HarvestResource\Pages;

use App\Actions\Harvest\CreateHarvestAction;
use App\Filament\Resources\HarvestResource;
use App\Filament\Pages\Base\BaseCreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateHarvest extends BaseCreateRecord
{
    protected static string $resource = HarvestResource::class;

    /**
     * Use the CreateHarvestAction for record creation
     */
    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateHarvestAction::class)->execute($data);
    }

    /**
     * Redirect to harvest list after creation
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Custom success notification message
     */
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Harvest recorded successfully';
    }
}
