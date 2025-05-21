<?php

namespace App\Filament\Pages\Base;

use Filament\Resources\Pages\EditRecord;

class BaseEditRecord extends EditRecord
{
    /**
     * Global override to redirect back to list page after saving
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 