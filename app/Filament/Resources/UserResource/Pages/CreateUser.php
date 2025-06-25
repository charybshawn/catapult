<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use App\Filament\Pages\Base\BaseCreateRecord;

class CreateUser extends BaseCreateRecord
{
    protected static string $resource = UserResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set email_verified_at if the toggle was checked
        if (isset($data['email_verified']) && $data['email_verified']) {
            $data['email_verified_at'] = now();
        }
        unset($data['email_verified']);
        
        return $data;
    }
} 