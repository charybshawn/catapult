<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Filament\Pages\BaseCreateRecord;
use Filament\Actions;

class CreateTask extends BaseCreateRecord
{
    protected static string $resource = TaskResource::class;
}
