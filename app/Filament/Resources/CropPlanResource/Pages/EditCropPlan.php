<?php

namespace App\Filament\Resources\CropPlanResource\Pages;

use App\Filament\Resources\CropPlanResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditCropPlan extends BaseEditRecord
{
    protected static string $resource = CropPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
