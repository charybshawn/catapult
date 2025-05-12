<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Pages\BaseCreateRecord;

class CreateOrder extends BaseCreateRecord
{
    protected static string $resource = OrderResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 