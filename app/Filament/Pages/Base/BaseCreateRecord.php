<?php

namespace App\Filament\Pages\Base;

use Filament\Resources\Pages\CreateRecord;

class BaseCreateRecord extends CreateRecord
{
    /**
     * Global override to redirect back to list page after creation
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 