<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseCreateRecord;

class CreateCustomer extends BaseCreateRecord
{
    protected static string $resource = CustomerResource::class;
}
