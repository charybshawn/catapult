<?php

namespace App\Filament\Resources\SettingsResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\SettingsResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseEditRecord;

class EditSetting extends BaseEditRecord
{
    protected static string $resource = SettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
} 